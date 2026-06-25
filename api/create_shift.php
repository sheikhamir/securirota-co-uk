<?php
// Prevent any output before JSON
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_clean();
header('Content-Type: application/json');

// Include config for proper session handling
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/ActivityLogger.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/email_helper.php';

// Check for AJAX requests - don't redirect, return JSON error
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'redirect' => true]);
    exit();
}

// Check subscription status for API requests
requireActiveSubscriptionAPI();

try {
    $db = new Database();
    $conn = $db->getConnection();
    $logger = new ActivityLogger($conn);
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM shifts LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
            // Multi-tenant mode active
            $use_company_filter = true;
            $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
            if (!$is_super_admin) {
                $company_id = $_SESSION['company_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        // Pre-migration mode, no company filtering
        $use_company_filter = false;
    }
    
    if ($_POST) {
        // SECURITY: Verify the site belongs to the user's company before creating shift
        if ($use_company_filter && $company_id) {
            $site_check = $conn->prepare("SELECT id FROM sites WHERE id = ? AND company_id = ?");
            $site_check->execute([$_POST['site_id'], $company_id]);
            if (!$site_check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Access denied: Site not found or not accessible']);
                exit();
            }
        }
        
        // Build the INSERT query with or without company_id
        if ($use_company_filter) {
            $stmt = $conn->prepare("
                INSERT INTO shifts (site_id, officer_id, shift_date, start_time, end_time, role_id, status, notes, officer_rate, client_rate, custom_officer_rate, company_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
        } else {
            $stmt = $conn->prepare("
                INSERT INTO shifts (site_id, officer_id, shift_date, start_time, end_time, role_id, status, notes, officer_rate, client_rate, custom_officer_rate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
        }
        
        $officer_id = !empty($_POST['officer_id']) && $_POST['officer_id'] !== 'null' ? $_POST['officer_id'] : null;
        $status = $officer_id ? 'allocated' : 'unallocated';

        if ($officer_id) {
            $officer_check_sql = "SELECT id FROM officers WHERE id = ? AND employment_status != 'Inactive'";
            $officer_check_params = [$officer_id];

            if ($use_company_filter && $company_id) {
                $officer_check_sql .= " AND company_id = ?";
                $officer_check_params[] = $company_id;
            }

            $officer_check = $conn->prepare($officer_check_sql);
            $officer_check->execute($officer_check_params);
            if (!$officer_check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid officer specified or officer does not belong to your company']);
                exit();
            }
        }
        
        // Handle role field (can be either 'role' or 'role_id')
        $role_input = $_POST['role_id'] ?? $_POST['role'] ?? null;
        
        // Convert role name to ID if necessary
        if ($role_input && !is_numeric($role_input)) {
            $role_sql = "SELECT id FROM roles WHERE name = ? AND is_active = 1";
            $role_params = [$role_input];
            
            // SECURITY: Add company filtering to role validation
            if ($use_company_filter && $company_id) {
                $role_sql .= " AND company_id = ?";
                $role_params[] = $company_id;
            }
            
            $role_stmt = $conn->prepare($role_sql);
            $role_stmt->execute($role_params);
            $role_result = $role_stmt->fetch(PDO::FETCH_ASSOC);
            $role_id = $role_result ? $role_result['id'] : null;
        } else {
            $role_id = $role_input;
        }
        
        // Validate role_id exists and belongs to user's company
        if ($role_id) {
            $validate_role_sql = "SELECT id FROM roles WHERE id = ? AND is_active = 1";
            $validate_role_params = [$role_id];
            
            // SECURITY: Add company filtering to role validation
            if ($use_company_filter && $company_id) {
                $validate_role_sql .= " AND company_id = ?";
                $validate_role_params[] = $company_id;
            }
            
            $validate_stmt = $conn->prepare($validate_role_sql);
            $validate_stmt->execute($validate_role_params);
            if (!$validate_stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid role specified or role does not belong to your company']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
            exit();
        }
        
        // Get custom officer rate if provided
        $custom_officer_rate = !empty($_POST['custom_officer_rate']) && $_POST['custom_officer_rate'] > 0 ? 
            floatval($_POST['custom_officer_rate']) : null;
        
        // Get hierarchical rates
        $client_rate = getEffectiveClientRate($_POST['site_id'], $conn);
        
        // Get effective officer rate (custom rate or default)
        $officer_rate = $officer_id ? getShiftOfficerRate(null, $officer_id, $custom_officer_rate, $conn) : 0.00;
        
        // Prepare parameters for execution based on whether company_id is needed
        $execute_params = [
            $_POST['site_id'],
            $officer_id,
            $_POST['shift_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $role_id,
            $status,
            $_POST['notes'] ?? '',
            $officer_rate,
            $client_rate,
            $custom_officer_rate
        ];
        
        // Add company_id parameter if using company filtering
        if ($use_company_filter) {
            $execute_params[] = $company_id;
        }
        
        $stmt->execute($execute_params);
        
        $shift_id = $conn->lastInsertId();
        
        // Get site name for logging
        $site_stmt = $conn->prepare("SELECT site_name FROM sites WHERE id = ?");
        $site_stmt->execute([$_POST['site_id']]);
        $site_result = $site_stmt->fetch(PDO::FETCH_ASSOC);
        $site_name = $site_result['site_name'] ?? 'Unknown Site';
        
        // Get role name for logging
        $role_stmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
        $role_stmt->execute([$role_id]);
        $role_result = $role_stmt->fetch(PDO::FETCH_ASSOC);
        $role_name = $role_result['name'] ?? 'Unknown Role';
        
        // Get officer name for logging
        $officer_name = 'Unallocated';
        if ($officer_id) {
            $officer_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM officers WHERE id = ?");
            $officer_stmt->execute([$officer_id]);
            $officer_result = $officer_stmt->fetch(PDO::FETCH_ASSOC);
            $officer_name = $officer_result['name'] ?? 'Unknown Officer';
        }
        
        // Log the activity
        $rate_description = $custom_officer_rate ? " (custom rate: £{$custom_officer_rate}/hr)" : " (default rate: £{$officer_rate}/hr)";
        $description = "Created new shift at {$site_name} on {$_POST['shift_date']} ({$_POST['start_time']}-{$_POST['end_time']}) for {$officer_name}" . ($officer_id ? $rate_description : "");
        
        $metadata = [
            'shift_data' => [
                'site_id' => $_POST['site_id'],
                'site_name' => $site_name,
                'officer_id' => $officer_id,
                'officer_name' => $officer_name,
                'shift_date' => $_POST['shift_date'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'role_id' => $role_id,
                'role_name' => $role_name,
                'status' => $status,
                'notes' => $_POST['notes'] ?? '',
                'officer_rate' => $officer_rate,
                'client_rate' => $client_rate,
                'custom_officer_rate' => $custom_officer_rate
            ]
        ];
        
        // Try to log activity, but don't fail if it doesn't work
        try {
            $logger->logShiftAction($_SESSION['user_id'], 'create', $shift_id, $description, $metadata);
        } catch (Exception $log_error) {
            error_log("Activity logging failed for shift creation: " . $log_error->getMessage());
        }
        
        if ($officer_id) {
            try {
                $recipient = getShiftEmailRecipient($conn, $shift_id);
                $email_sent = sendShiftAssignmentEmail($conn, $shift_id);
                logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'assignment', $recipient, $email_sent);
            } catch (Exception $email_error) {
                error_log("Shift assignment email failed for shift {$shift_id}: " . $email_error->getMessage());
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Shift created successfully', 'shift_id' => $shift_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
