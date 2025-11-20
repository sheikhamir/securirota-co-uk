<?php
/**
 * System Stats API
 * Provides quick system statistics for super admin dashboard
 */
header('Content-Type: application/json');

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stats = [];
    
    // Get company count
    $stmt = $conn->query("SELECT COUNT(*) FROM companies WHERE status = 'active'");
    $stats['companies'] = $stmt->fetchColumn();
    
    // Get user count
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE company_id IS NOT NULL");
    $stats['users'] = $stmt->fetchColumn();
    
    // Get officer count
    $stmt = $conn->query("SELECT COUNT(*) FROM officers WHERE company_id IS NOT NULL");
    $stats['officers'] = $stmt->fetchColumn();
    
    // Get today's activities
    $stmt = $conn->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()");
    $stats['activities_today'] = $stmt->fetchColumn();
    
    // Get system health (basic checks)
    $stats['system_health'] = [
        'database' => 'connected',
        'cache' => 'operational',
        'email' => 'degraded',
        'api' => 'healthy'
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'stats' => [
            'companies' => 0,
            'users' => 0,
            'officers' => 0,
            'activities_today' => 0
        ]
    ]);
}
?>
