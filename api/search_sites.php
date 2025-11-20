<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM sites LIKE 'company_id'");
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
    
    // Get search query
    $query = $_GET['q'] ?? '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    if (strlen($query) < 2) {
        echo json_encode(['sites' => []]);
        exit;
    }
    
    // Search sites by name and client name with improved sorting - WITH COMPANY FILTERING
    $sql = "
        SELECT s.id, s.site_name, c.company_name as client_name 
        FROM sites s 
        JOIN clients c ON s.client_id = c.id 
        WHERE s.status = 'active' 
        AND (
            LOWER(s.site_name) LIKE LOWER(?) 
            OR LOWER(c.company_name) LIKE LOWER(?)
        )";
    
    $params = ['%' . $query . '%', '%' . $query . '%'];
    
    // SECURITY: Add company filtering to prevent cross-company access
    if ($use_company_filter && $company_id) {
        $sql .= " AND s.company_id = ?";
        $params[] = $company_id;
    }
    
    $sql .= " ORDER BY 
            CASE 
                WHEN LOWER(s.site_name) = LOWER(?) THEN 1
                WHEN LOWER(c.company_name) = LOWER(?) THEN 2
                WHEN LOWER(s.site_name) LIKE LOWER(?) THEN 3
                WHEN LOWER(c.company_name) LIKE LOWER(?) THEN 4
                WHEN LOWER(s.site_name) LIKE LOWER(?) THEN 5
                WHEN LOWER(c.company_name) LIKE LOWER(?) THEN 6
                ELSE 7
            END,
            LENGTH(s.site_name),
            s.site_name, c.company_name
        LIMIT ?";
    
    $searchParam = '%' . $query . '%';
    $exactParam = $query;
    $startParam = $query . '%';
    
    // Add ordering parameters and limit
    $params = array_merge($params, [
        $exactParam,   // site name = query (exact match)
        $exactParam,   // client name = query (exact match)
        $startParam,   // site name LIKE query% (starts with)
        $startParam,   // client name LIKE query% (starts with)
        $searchParam,  // site name LIKE %query% (contains - for ORDER BY)
        $searchParam,  // client name LIKE %query% (contains - for ORDER BY)
        $limit
    ]);
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'sites' => $sites,
        'query' => $query,
        'count' => count($sites)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
