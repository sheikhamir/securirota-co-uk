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
require_once dirname(__DIR__) . '/includes/email_helper.php';

// Check for AJAX requests - don't redirect, return JSON error
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'redirect' => true]);
    exit();
}

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
    
    // Debug: Log what POST data we're receiving
    error_log("Reschedule data received: " . print_r($_POST, true));
    
    if ($_POST && isset($_POST['id'])) {
        // Validate required fields for reschedule
        $required_fields = ['site_id', 'shift_date', 'start_time', 'end_time', 'role_id', 'reschedule_reason'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log("Missing fields detected: " . implode(', ', $missing_fields));
            echo json_encode([
                'success' => false, 
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                'received_data' => $_POST
            ]);
            exit();
        }
        
        // Handle role field validation (same logic as create/update)
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
        
        $shift_id = intval($_POST['id']);
        
        // Get the current shift data before updating for activity logging
        $stmt = $conn->prepare("SELECT * FROM shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        $old_shift_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_shift_data) {
            echo json_encode([
                'success' => false, 
                'message' => 'Shift not found'
            ]);
            exit();
        }

        $is_active_shift = $old_shift_data['status'] === 'in_progress' ||
            (!empty($old_shift_data['checkin_timestamp']) && empty($old_shift_data['checkout_timestamp']));

        if ($is_active_shift) {
            echo json_encode([
                'success' => false,
                'message' => 'This shift is already active. Only the end time can be changed from the edit shift form.'
            ]);
            exit();
        }
        
        // Prepare the update query with reschedule fields
        $sql = "UPDATE shifts SET 
                site_id = ?, 
                officer_id = ?, 
                shift_date = ?, 
                start_time = ?, 
                end_time = ?, 
                role_id = ?, 
                status = ?,
                rescheduled = TRUE,
                reschedule_reason = ?,
                notes = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        
        // Set officer_id to null if empty
        $officer_id = !empty($_POST['officer_id']) ? intval($_POST['officer_id']) : null;

        if ($officer_id) {
            $officer_check_sql = "SELECT id FROM officers WHERE id = ?";
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
        
        // Set status - if officer is assigned and was previously allocated/confirmed, keep as allocated
        // If no officer, set as unallocated
        $status = $officer_id ? 'allocated' : 'unallocated';
        
        $params = [
            $_POST['site_id'],
            $officer_id,
            $_POST['shift_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $role_id,
            $status,
            $_POST['reschedule_reason'],
            $_POST['notes'] ?? '',
            $shift_id
        ];
        
        // Execute the update
        $result = $stmt->execute($params);
        
        if ($result) {
            // Get the updated shift data for comparison
            $stmt = $conn->prepare("SELECT * FROM shifts WHERE id = ?");
            $stmt->execute([$shift_id]);
            $new_shift_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get officer name for description
            $officer_name = 'Unallocated';
            if ($officer_id) {
                $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM officers WHERE id = ?");
                $stmt->execute([$officer_id]);
                $officer_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $officer_name = $officer_result['name'] ?? 'Unknown Officer';
            }
            
            // Get site name for description
            $stmt = $conn->prepare("SELECT site_name FROM sites WHERE id = ?");
            $stmt->execute([$_POST['site_id']]);
            $site_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $site_name = $site_result['site_name'] ?? 'Unknown Site';
            
            // Log the reschedule activity
            $description = "Rescheduled shift at {$site_name} on {$_POST['shift_date']} ({$_POST['start_time']}-{$_POST['end_time']}) for {$officer_name}. Reason: {$_POST['reschedule_reason']}";
            
            $metadata = [
                'old_data' => $old_shift_data,
                'new_data' => $new_shift_data,
                'reschedule_reason' => $_POST['reschedule_reason'],
                'changes' => []
            ];
            
            // Track specific changes
            $change_fields = ['shift_date', 'start_time', 'end_time', 'officer_id', 'site_id', 'role_id'];
            foreach ($change_fields as $field) {
                if ($old_shift_data[$field] != $new_shift_data[$field]) {
                    $metadata['changes'][$field] = [
                        'from' => $old_shift_data[$field],
                        'to' => $new_shift_data[$field]
                    ];
                }
            }
            
            $logger->logShiftAction($_SESSION['user_id'], 'reschedule', $shift_id, $description, $metadata);
            
            try {
                $old_officer_id = $old_shift_data['officer_id'] ?? null;
                $officer_changed = (string)($old_officer_id ?? '') !== (string)($officer_id ?? '');
                $details_changed = !empty($metadata['changes']);
                
                if ($old_officer_id && $officer_changed) {
                    $recipient = getOfficerEmailRecipient($conn, $old_officer_id);
                    $email_sent = sendShiftRemovedEmail($conn, $shift_id, $old_officer_id, 'This shift has been removed from your schedule due to a reschedule.');
                    logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'removal', $recipient, $email_sent, ['reason' => $_POST['reschedule_reason']]);
                }
                
                if ($officer_id && $status === 'allocated' && ($officer_changed || $details_changed)) {
                    $recipient = getShiftEmailRecipient($conn, $shift_id);
                    if ($officer_changed) {
                        $email_sent = sendShiftAssignmentEmail($conn, $shift_id);
                        logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'assignment', $recipient, $email_sent, ['reason' => $_POST['reschedule_reason']]);
                    } else {
                        $email_sent = sendShiftChangedEmail($conn, $shift_id, $old_shift_data, $_POST['reschedule_reason']);
                        logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'change', $recipient, $email_sent, ['reason' => $_POST['reschedule_reason']]);
                    }
                }
            } catch (Exception $email_error) {
                error_log("Shift reschedule email notification failed for shift {$shift_id}: " . $email_error->getMessage());
            }
            
            error_log("Shift {$shift_id} rescheduled successfully by user {$_SESSION['user_id']}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Shift rescheduled successfully',
                'shift_id' => $shift_id,
                'new_status' => $status
            ]);
        } else {
            error_log("Failed to reschedule shift {$shift_id}");
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to reschedule shift'
            ]);
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing shift ID or form data'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Reschedule shift error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
