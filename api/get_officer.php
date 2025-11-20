<?php
// API endpoint to get officer data for editing
header('Content-Type: application/json');

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$officer_id = $_GET['id'] ?? '';

if (empty($officer_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Officer ID is required']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get officer data with user information
    // Check if we're in multi-tenant mode (post-migration)
    $use_company_filter = false;
    $company_id = null;
    
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM officers LIKE 'company_id'");
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
    
    if ($use_company_filter) {
        if ($company_id) {
            // Company admin - filter by company
            $stmt = $conn->prepare("
                SELECT o.*, u.mobile_number, u.email as user_email, u.username, c.name as company_name
                FROM officers o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN companies c ON o.company_id = c.id
                WHERE o.id = ? AND o.company_id = ?
            ");
            $stmt->execute([$officer_id, $company_id]);
        } else {
            // Super admin - no filter
            $stmt = $conn->prepare("
                SELECT o.*, u.mobile_number, u.email as user_email, u.username, c.name as company_name
                FROM officers o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN companies c ON o.company_id = c.id
                WHERE o.id = ?
            ");
            $stmt->execute([$officer_id]);
        }
    } else {
        // Pre-migration mode - simple query
        $stmt = $conn->prepare("
            SELECT o.*, u.mobile_number, u.email as user_email, u.username 
            FROM officers o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$officer_id]);
    }
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$officer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Officer not found']);
        exit();
    }
    
    // Use user email if officer email is empty
    if (empty($officer['email']) && !empty($officer['user_email'])) {
        $officer['email'] = $officer['user_email'];
    }
    
    echo json_encode([
        'success' => true,
        'officer' => $officer
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
