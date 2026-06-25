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

// Debug log
error_log("GET_SHIFT DEBUG: Session ID = " . session_id());
error_log("GET_SHIFT DEBUG: User ID = " . ($_SESSION['user_id'] ?? 'not set'));

// Check for AJAX requests - don't redirect, return JSON error
if (!isset($_SESSION['user_id'])) {
    error_log("GET_SHIFT ERROR: No user_id in session");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'redirect' => true]);
    exit();
}

// Check subscription status for API requests
requireActiveSubscriptionAPI();

// Use proper database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode
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
    
    if (isset($_GET['id'])) {
        // Debug log
        error_log("GET_SHIFT DEBUG: Requesting shift ID = " . $_GET['id']);
        
        // Get shift details with company filtering
        $sql = "
            SELECT s.*, st.site_name as site_name, c.company_name as client_name,
                   r.id as role_id, r.name as role_name,
                   CONCAT(o.first_name, ' ', o.last_name) as officer_name,
                   CONCAT(
                       o.first_name, ' ', o.last_name,
                       CASE WHEN o.staff_id IS NOT NULL AND o.staff_id != '' THEN CONCAT(' - ', o.staff_id) ELSE '' END,
                       CASE WHEN o.phone IS NOT NULL AND o.phone != '' THEN CONCAT(' - ', o.phone) ELSE '' END
                   ) as officer_display_name
            FROM shifts s
            LEFT JOIN sites st ON s.site_id = st.id
            LEFT JOIN clients c ON st.client_id = c.id
            LEFT JOIN roles r ON s.role_id = r.id
            LEFT JOIN officers o ON s.officer_id = o.id
            WHERE s.id = ?";
        
        $params = [$_GET['id']];
        
        if ($use_company_filter && $company_id) {
            $sql .= " AND s.company_id = ?";
            $params[] = $company_id;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shift) {
            error_log("GET_SHIFT ERROR: Shift not found for ID = " . $_GET['id']);
            echo json_encode(['success' => false, 'message' => 'Shift not found']);
            exit();
        }
        
        // Get sites list with company filtering
        if ($use_company_filter && $company_id) {
            $stmt = $conn->prepare("
                SELECT s.id, s.site_name, c.company_name as client_name 
                FROM sites s 
                JOIN clients c ON s.client_id = c.id 
                WHERE s.status = 'active' AND s.company_id = ?
                ORDER BY c.company_name, s.site_name
            ");
            $stmt->execute([$company_id]);
        } else {
            $stmt = $conn->query("
                SELECT s.id, s.site_name, c.company_name as client_name 
                FROM sites s 
                JOIN clients c ON s.client_id = c.id 
                WHERE s.status = 'active' 
                ORDER BY c.company_name, s.site_name
            ");
        }
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'shift' => $shift,
            'officers' => [],
            'sites' => $sites
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Shift ID required']);
    }
    
} catch (Exception $e) {
    error_log("GET_SHIFT EXCEPTION: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
