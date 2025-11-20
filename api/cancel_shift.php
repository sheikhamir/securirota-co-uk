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
    error_log("Cancel shift data received: " . print_r($_POST, true));
    
    if ($_POST && isset($_POST['id'])) {
        // Validate required fields for cancellation
        $required_fields = ['cancellation_reason'];
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
        
        $shift_id = intval($_POST['id']);
        
        // Get the current shift data before updating for activity logging - WITH COMPANY SECURITY CHECK
        $shift_sql = "SELECT * FROM shifts WHERE id = ?";
        $shift_params = [$shift_id];
        
        // SECURITY: Add company filtering to prevent cross-company access
        if ($use_company_filter && $company_id) {
            $shift_sql .= " AND company_id = ?";
            $shift_params[] = $company_id;
        }
        
        $stmt = $conn->prepare($shift_sql);
        $stmt->execute($shift_params);
        $old_shift_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_shift_data) {
            echo json_encode([
                'success' => false, 
                'message' => 'Shift not found'
            ]);
            exit();
        }
        
        // Check if shift can be cancelled (not already completed or cancelled)
        if (in_array($old_shift_data['status'], ['completed', 'cancelled'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot cancel a shift that is already ' . $old_shift_data['status']
            ]);
            exit();
        }
        
        // Prepare the update query to cancel the shift
        $sql = "UPDATE shifts SET 
                status = 'cancelled',
                cancelled_at = NOW(),
                cancellation_reason = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            $_POST['cancellation_reason'],
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
            if ($old_shift_data['officer_id']) {
                $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM officers WHERE id = ?");
                $stmt->execute([$old_shift_data['officer_id']]);
                $officer_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $officer_name = $officer_result['name'] ?? 'Unknown Officer';
            }
            
            // Get site name for description
            $stmt = $conn->prepare("SELECT site_name FROM sites WHERE id = ?");
            $stmt->execute([$old_shift_data['site_id']]);
            $site_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $site_name = $site_result['site_name'] ?? 'Unknown Site';
            
            // Log the cancellation activity
            $description = "Cancelled shift at {$site_name} on {$old_shift_data['shift_date']} ({$old_shift_data['start_time']}-{$old_shift_data['end_time']}) for {$officer_name}. Reason: {$_POST['cancellation_reason']}";
            
            $metadata = [
                'old_data' => $old_shift_data,
                'new_data' => $new_shift_data,
                'cancellation_reason' => $_POST['cancellation_reason'],
                'cancelled_by' => $_SESSION['user_id'],
                'previous_status' => $old_shift_data['status']
            ];
            
            $logger->logShiftAction($_SESSION['user_id'], 'cancel', $shift_id, $description, $metadata);
            
            error_log("Shift {$shift_id} cancelled successfully by user {$_SESSION['user_id']}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Shift cancelled successfully',
                'shift_id' => $shift_id,
                'new_status' => 'cancelled'
            ]);
        } else {
            error_log("Failed to cancel shift {$shift_id}");
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to cancel shift'
            ]);
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing shift ID or form data'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Cancel shift error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
