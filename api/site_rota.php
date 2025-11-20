<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/error_log');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/ActivityLogger.php';

// Debug: Log that the file was accessed
error_log("site_rota.php accessed at " . date('Y-m-d H:i:s'));

function getSiteStats($conn) {
    $site_id = $_POST['site_id'] ?? '';
    
    if (!$site_id) {
        echo json_encode(['success' => false, 'message' => 'Site ID required']);
        return;
    }
    
    // Initialize company filtering
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode and get user's company
    session_start();
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM shifts LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
            $use_company_filter = true;
            $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
            if (!$is_super_admin) {
                $company_id = $_SESSION['company_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        $use_company_filter = false;
    }
    
    // Get basic site statistics with company filtering
    $sql = "
        SELECT 
            COUNT(*) as total_shifts,
            COUNT(CASE WHEN officer_id IS NULL THEN 1 END) as unallocated_shifts,
            COUNT(CASE WHEN officer_id IS NOT NULL THEN 1 END) as allocated_shifts
        FROM shifts 
        WHERE site_id = ? AND shift_date >= CURDATE()";
    
    $params = [$site_id];
    
    if ($use_company_filter && $company_id) {
        $sql .= " AND company_id = ?";
        $params[] = $company_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

// Main execution logic
try {
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Debug: Log request details
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST data: " . print_r($_POST, true));
    error_log("GET data: " . print_r($_GET, true));
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    error_log("Action requested: " . $action);
    
    if (empty($action)) {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
        exit;
    }
    
    // Initialize logger
    $logger = new ActivityLogger($conn);
    
    switch ($action) {
        case 'get_stats':
            getSiteStats($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in site_rota.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
