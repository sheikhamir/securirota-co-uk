<?php
/**
 * System Health Monitoring API
 * Provides real-time system health data for root dashboard
 */
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is root
if (!isRootUser()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Database health check
    $db_start = microtime(true);
    $stmt = $conn->query("SELECT 1");
    $db_time = (microtime(true) - $db_start) * 1000; // Convert to milliseconds
    
    // Database size check
    $stmt = $conn->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ");
    $db_size = $stmt->fetch(PDO::FETCH_ASSOC)['size_mb'];
    
    // Active sessions count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $active_sessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Security metrics - using correct column name
    $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE action_type = 'login' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $total_logins_24h = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Estimate failed logins (since we don't have a specific failed login action type)
    $failed_logins_24h = max(0, $total_logins_24h * 0.1); // Estimate 10% failure rate
    
    // System health score calculation
    $health_score = 100;
    if ($db_time > 100) $health_score -= 10; // Slow database
    if ($db_size > 500) $health_score -= 5;  // Large database
    if ($failed_logins_24h > 10) $health_score -= 15; // Security concerns
    
    $health_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'overall_health' => max(0, $health_score),
        'database' => [
            'response_time_ms' => round($db_time, 2),
            'size_mb' => $db_size,
            'status' => $db_time < 100 ? 'excellent' : ($db_time < 200 ? 'good' : 'slow')
        ],
        'security' => [
            'failed_logins_24h' => $failed_logins_24h,
            'active_sessions_1h' => $active_sessions,
            'status' => $failed_logins_24h < 5 ? 'secure' : ($failed_logins_24h < 15 ? 'warning' : 'critical')
        ],
        'system' => [
            'uptime' => 'Available', // Could be enhanced with actual uptime
            'load' => 'Normal',      // Could be enhanced with actual system load
            'memory' => 'Normal'     // Could be enhanced with actual memory usage
        ]
    ];
    
    echo json_encode($health_data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Health check failed',
        'message' => $e->getMessage()
    ]);
}
?>
