<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM roles LIKE 'company_id'");
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
    
    // Get all active roles ordered by name - WITH COMPANY FILTERING
    $sql = "SELECT id, name FROM roles WHERE is_active = 1";
    $params = [];
    
    // SECURITY: Add company filtering to prevent accessing other companies' roles
    if ($use_company_filter && $company_id) {
        $sql .= " AND company_id = ?";
        $params[] = $company_id;
    }
    
    $sql .= " ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);
    
} catch (Exception $e) {
    error_log("GET_ROLES ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch roles'
    ]);
}
?>
