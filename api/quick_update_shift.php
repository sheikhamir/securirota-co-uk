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
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Debug: Log what data we're receiving
    error_log("Quick update data received: " . print_r($data, true));
    
    if ($data && isset($data['shift_id']) && isset($data['action'])) {
        $shift_id = intval($data['shift_id']);
        $action = $data['action'];
        
        // Validate action
        $allowed_actions = ['confirm', 'reschedule', 'unconfirm'];
        if (!in_array($action, $allowed_actions)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action. Allowed actions: ' . implode(', ', $allowed_actions)
            ]);
            exit();
        }
        
        // Set the new status based on action
        $new_status = 'allocated'; // Default for reschedule and unconfirm
        if ($action === 'confirm') {
            $new_status = 'confirmed';
        }
        
        // Get current shift details for validation
        $stmt = $conn->prepare("SELECT * FROM shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shift) {
            echo json_encode([
                'success' => false, 
                'message' => 'Shift not found'
            ]);
            exit();
        }

        $is_active_shift = $shift['status'] === 'in_progress' ||
            (!empty($shift['checkin_timestamp']) && empty($shift['checkout_timestamp']));

        if ($is_active_shift) {
            echo json_encode([
                'success' => false,
                'message' => 'This shift is already active. Only the end time can be changed from the edit shift form.'
            ]);
            exit();
        }
        
        // Update the shift status
        $stmt = $conn->prepare("UPDATE shifts SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_status, $shift_id]);
        
        if ($result) {
            // Get officer name for logging
            $officer_name = 'Unallocated';
            if ($shift['officer_id']) {
                $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM officers WHERE id = ?");
                $stmt->execute([$shift['officer_id']]);
                $officer_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $officer_name = $officer_result['name'] ?? 'Unknown Officer';
            }
            
            // Get site name for logging
            $stmt = $conn->prepare("SELECT site_name FROM sites WHERE id = ?");
            $stmt->execute([$shift['site_id']]);
            $site_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $site_name = $site_result['site_name'] ?? 'Unknown Site';
            
            // Log the activity
            $action_descriptions = [
                'confirm' => "Confirmed shift at {$site_name} on {$shift['shift_date']} for {$officer_name}",
                'reschedule' => "Marked shift at {$site_name} on {$shift['shift_date']} for rescheduling",
                'unconfirm' => "Unconfirmed shift at {$site_name} on {$shift['shift_date']} for {$officer_name}"
            ];
            
            $description = $action_descriptions[$action] ?? "Updated shift status to {$new_status}";
            
            $metadata = [
                'old_status' => $shift['status'],
                'new_status' => $new_status,
                'action' => $action,
                'shift_data' => $shift
            ];
            
            $log_action = ($action === 'confirm') ? 'confirm' : 'update';
            $logger->logShiftAction($_SESSION['user_id'], $log_action, $shift_id, $description, $metadata);
            
            if ($shift['officer_id'] && $new_status === 'allocated') {
                try {
                    $recipient = getShiftEmailRecipient($conn, $shift_id);
                    $email_sent = sendShiftAssignmentEmail($conn, $shift_id);
                    logShiftEmailAttempt($logger, $_SESSION['user_id'], $shift_id, 'assignment', $recipient, $email_sent, ['action' => $action]);
                } catch (Exception $email_error) {
                    error_log("Quick shift update email notification failed for shift {$shift_id}: " . $email_error->getMessage());
                }
            }
            
            // Log the update
            error_log("Shift {$shift_id} status updated to {$new_status} by user {$_SESSION['user_id']}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Shift status updated successfully',
                'new_status' => $new_status,
                'shift_id' => $shift_id
            ]);
        } else {
            error_log("Failed to update shift {$shift_id}");
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to update shift status'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: shift_id and action'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Quick update shift error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
