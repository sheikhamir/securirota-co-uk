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
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
        $shift_id = intval($_GET['id']);
        
        // Get shift data before deletion for logging - WITH COMPANY SECURITY CHECK
        $shift_sql = "
            SELECT s.*, 
                   st.site_name,
                   CONCAT(o.first_name, ' ', o.last_name) as officer_name
            FROM shifts s
            LEFT JOIN sites st ON s.site_id = st.id
            LEFT JOIN officers o ON s.officer_id = o.id
            WHERE s.id = ?";
        
        $shift_params = [$shift_id];
        
        // SECURITY: Add company filtering to prevent cross-company access
        if ($use_company_filter && $company_id) {
            $shift_sql .= " AND s.company_id = ?";
            $shift_params[] = $company_id;
        }
        
        $stmt = $conn->prepare($shift_sql);
        $stmt->execute($shift_params);
        $shift_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shift_data) {
            echo json_encode(['success' => false, 'message' => 'Shift not found']);
            exit();
        }
        
        // Delete the shift
        $stmt = $conn->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        
        // Log the activity
        $officer_name = $shift_data['officer_name'] ?? 'Unallocated';
        $site_name = $shift_data['site_name'] ?? 'Unknown Site';
        $description = "Deleted shift at {$site_name} on {$shift_data['shift_date']} ({$shift_data['start_time']}-{$shift_data['end_time']}) for {$officer_name}";
        
        $metadata = [
            'deleted_shift_data' => $shift_data
        ];
        
        $logger->logShiftAction($_SESSION['user_id'], 'delete', $shift_id, $description, $metadata);
        
        echo json_encode(['success' => true, 'message' => 'Shift deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
