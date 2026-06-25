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
    
    if ($_POST && isset($_POST['id'])) {
        // Validate required fields
        $required_fields = ['site_id', 'shift_date', 'start_time', 'end_time'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        // Check for role field (can be either 'role' or 'role_id')
        if ((!isset($_POST['role']) || empty($_POST['role'])) && 
            (!isset($_POST['role_id']) || empty($_POST['role_id']))) {
            $missing_fields[] = 'role/role_id';
        }
        
        if (!empty($missing_fields)) {
            error_log("Missing fields detected: " . implode(', ', $missing_fields));
            echo json_encode([
                'success' => false, 
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                'received_data' => $_POST // Add this for debugging
            ]);
            exit();
        }
        
        // Validate site_id exists
        $stmt = $conn->prepare("SELECT id FROM sites WHERE id = ?");
        $stmt->execute([$_POST['site_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid site ID']);
            exit();
        }
        
        // Validate officer_id if provided
        $officer_id = !empty($_POST['officer_id']) ? $_POST['officer_id'] : null;
        if ($officer_id) {
            $stmt = $conn->prepare("SELECT id FROM officers WHERE id = ?");
            $stmt->execute([$officer_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid officer ID']);
                exit();
            }
        }
        
        $old_shift_stmt = $conn->prepare("SELECT * FROM shifts WHERE id = ?");
        $old_shift_stmt->execute([$_POST['id']]);
        $old_shift_data = $old_shift_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old_shift_data) {
            echo json_encode(['success' => false, 'message' => 'Shift not found']);
            exit();
        }

        $is_active_shift = $old_shift_data['status'] === 'in_progress' ||
            (!empty($old_shift_data['checkin_timestamp']) && empty($old_shift_data['checkout_timestamp']));

        if ($is_active_shift) {
            $locked_fields = [
                'site_id' => 'site',
                'officer_id' => 'officer',
                'shift_date' => 'date',
                'start_time' => 'start time',
                'role_id' => 'role',
                'notes' => 'notes',
                'custom_officer_rate' => 'custom officer rate'
            ];

            foreach ($locked_fields as $field => $label) {
                $old_value = $old_shift_data[$field] ?? '';
                $new_value = $_POST[$field] ?? '';

                if ($field === 'officer_id') {
                    $new_value = $officer_id ?? '';
                }

                if ($field === 'custom_officer_rate') {
                    $new_value = !empty($_POST['custom_officer_rate']) && $_POST['custom_officer_rate'] > 0 ? $_POST['custom_officer_rate'] : '';
                    $old_value = !empty($old_value) ? $old_value : '';
                }

                if ((string)$old_value !== (string)$new_value) {
                    echo json_encode([
                        'success' => false,
                        'message' => "This shift is already active. Only the end time can be changed."
                    ]);
                    exit();
                }
            }

            if (isset($_POST['status']) && $_POST['status'] !== $old_shift_data['status']) {
                echo json_encode([
                    'success' => false,
                    'message' => "This shift is already active. Only the end time can be changed."
                ]);
                exit();
            }
        }

        $status = $_POST['status'] ?? ($officer_id ? 'allocated' : 'unallocated');
        
        if ($old_shift_data && $officer_id && (string)($old_shift_data['officer_id'] ?? '') !== (string)$officer_id) {
            $status = 'allocated';
        }
        
        // Handle role field (can be either 'role' or 'role_id')
        $role_input = !empty($_POST['role_id']) ? $_POST['role_id'] : ($_POST['role'] ?? null);
        
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
        
        // Get updated rates
        $client_rate = getEffectiveClientRate($_POST['site_id'], $conn);
        // When clearing custom rate (null), don't use shift_id to avoid getting old rate from database
        // Instead, use null for shift_id to force calculation from officer default rate
        $shift_id_for_rate = ($custom_officer_rate === null) ? null : $_POST['id'];
        $officer_rate = $officer_id ? getShiftOfficerRate($shift_id_for_rate, $officer_id, $custom_officer_rate, $conn) : 0.00;

        $shift_details_changed = (string)$old_shift_data['site_id'] !== (string)$_POST['site_id'] ||
            (string)$old_shift_data['officer_id'] !== (string)($officer_id ?? '') ||
            (string)$old_shift_data['shift_date'] !== (string)$_POST['shift_date'] ||
            (string)$old_shift_data['start_time'] !== (string)$_POST['start_time'] ||
            (string)$old_shift_data['end_time'] !== (string)$_POST['end_time'] ||
            (string)$old_shift_data['role_id'] !== (string)$role_id;

        if ($old_shift_data['status'] === 'confirmed' && $shift_details_changed) {
            $status = 'allocated';
        }

        if ($is_active_shift) {
            $status = $old_shift_data['status'];
        }
        
        $stmt = $conn->prepare("
            UPDATE shifts SET 
                site_id = ?, officer_id = ?, shift_date = ?, start_time = ?, end_time = ?, 
                role_id = ?, status = ?, notes = ?, client_rate = ?, officer_rate = ?, custom_officer_rate = ?
            WHERE id = ?
        ");
        
        $updateData = [
            $_POST['site_id'],
            $officer_id,
            $_POST['shift_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $role_id,
            $status,
            $_POST['notes'] ?? '',
            $client_rate,
            $officer_rate,
            $custom_officer_rate,
            $_POST['id']
        ];
        
        // Debug: Log the SQL and parameters
        error_log("Update SQL: UPDATE shifts SET site_id = ?, officer_id = ?, shift_date = ?, start_time = ?, end_time = ?, role_id = ?, status = ?, notes = ?, client_rate = ?, officer_rate = ?, custom_officer_rate = ? WHERE id = ?");
        error_log("Update parameters: " . print_r($updateData, true));
        
        $result = $stmt->execute($updateData);
        
        // Debug: Check if the update actually affected any rows
        $rowCount = $stmt->rowCount();
        error_log("Rows affected by update: " . $rowCount);
        
        if ($rowCount === 0) {
            // Even though no rows were updated, we should still log the drag action
            // as the user performed an action, even if the data is identical
            
            $shift_id = $_POST['id'];
            
            // Get site name for logging
            $stmt = $conn->prepare("SELECT site_name FROM sites WHERE id = ?");
            $stmt->execute([$_POST['site_id']]);
            $site_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $site_name = $site_result['site_name'] ?? 'Unknown Site';
            
            // Get officer name for logging
            $officer_name = 'Unallocated';
            if ($officer_id) {
                $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM officers WHERE id = ?");
                $stmt->execute([$officer_id]);
                $officer_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $officer_name = $officer_result['name'] ?? 'Unknown Officer';
            }
            
            // Log the drag activity even though no data changed
            $rate_description = $custom_officer_rate ? " (custom rate: £{$custom_officer_rate}/hr)" : " (default rate: £{$officer_rate}/hr)";
            $description = "Attempted to move shift at {$site_name} on {$_POST['shift_date']} ({$_POST['start_time']}-{$_POST['end_time']}) for {$officer_name}" . ($officer_id ? $rate_description : "") . " (no changes made - data identical)";
            
            $metadata = [
                'drag_action' => true,
                'no_changes' => true,
                'submitted_data' => [
                    'site_id' => $_POST['site_id'],
                    'site_name' => $site_name,
                    'officer_id' => $officer_id,
                    'officer_name' => $officer_name,
                    'shift_date' => $_POST['shift_date'],
                    'start_time' => $_POST['start_time'],
                    'end_time' => $_POST['end_time'],
                    'role' => $role_id,
                    'status' => $status,
                    'notes' => $_POST['notes'] ?? '',
                    'custom_officer_rate' => $custom_officer_rate
                ]
            ];
            
            $activityResult = $logger->logShiftAction($_SESSION['user_id'], 'update', $shift_id, $description, $metadata);
            
            echo json_encode([
                'success' => true, // Changed to true - drag operation was completed, even if no data changed
                'message' => 'Shift position confirmed (no changes needed - data identical).',
                'shift_id' => $_POST['id'],
                'rows_affected' => $rowCount,
                'no_changes' => true
            ]);
            exit();
        }
        
        // Log the activity after successful update
        $shift_id = $_POST['id'];
        
        // Get site name for logging
        $stmt = $conn->prepare("SELECT site_name FROM sites WHERE id = ?");
        $stmt->execute([$_POST['site_id']]);
        $site_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $site_name = $site_result['site_name'] ?? 'Unknown Site';
        
        // Get officer name for logging
        $officer_name = 'Unallocated';
        if ($officer_id) {
            $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM officers WHERE id = ?");
            $stmt->execute([$officer_id]);
            $officer_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $officer_name = $officer_result['name'] ?? 'Unknown Officer';
        }
        
        // Log the activity
        $rate_description = $custom_officer_rate ? " (custom rate: £{$custom_officer_rate}/hr)" : " (default rate: £{$officer_rate}/hr)";
        $description = "Updated shift at {$site_name} on {$_POST['shift_date']} ({$_POST['start_time']}-{$_POST['end_time']}) for {$officer_name}" . ($officer_id ? $rate_description : "");
        
        $metadata = [
            'old_data' => $old_shift_data,
            'updated_data' => [
                'site_id' => $_POST['site_id'],
                'site_name' => $site_name,
                'officer_id' => $officer_id,
                'officer_name' => $officer_name,
                'shift_date' => $_POST['shift_date'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'role' => $role_id,
                'status' => $status,
                'notes' => $_POST['notes'] ?? '',
                'custom_officer_rate' => $custom_officer_rate
            ]
        ];
        
        $activityResult = $logger->logShiftAction($_SESSION['user_id'], 'update', $shift_id, $description, $metadata);
        
        try {
            $old_officer_id = $old_shift_data['officer_id'] ?? null;
            $officer_changed = (string)($old_officer_id ?? '') !== (string)($officer_id ?? '');
            $status_changed = (string)$old_shift_data['status'] !== (string)$status;
            $shift_details_changed = $shift_details_changed || $status_changed;
            
            if ($old_officer_id && $officer_changed) {
                $recipient = getOfficerEmailRecipient($conn, $old_officer_id);
                $email_sent = sendShiftRemovedEmail($conn, $shift_id, $old_officer_id, 'This shift has been removed from your schedule.');
                logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'removal', $recipient, $email_sent);
            }
            
            if ($officer_id && ($officer_changed || $shift_details_changed)) {
                $recipient = getShiftEmailRecipient($conn, $shift_id);
                if ($officer_changed) {
                    $email_sent = sendShiftAssignmentEmail($conn, $shift_id);
                    logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'assignment', $recipient, $email_sent);
                } else {
                    $email_sent = sendShiftChangedEmail($conn, $shift_id, $old_shift_data);
                    logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'change', $recipient, $email_sent);
                }
            }
        } catch (Exception $email_error) {
            error_log("Shift update email notification failed for shift {$shift_id}: " . $email_error->getMessage());
        }
        
        echo json_encode(['success' => true, 'message' => 'Shift updated successfully', 'rows_affected' => $rowCount]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid request - no POST data or missing ID',
            'post_data_exists' => !empty($_POST),
            'id_exists' => isset($_POST['id']),
            'received_data' => $_POST
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
