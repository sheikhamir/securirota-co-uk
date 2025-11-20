<?php
/**
 * Security API - Chart Data
 * Provides data for security dashboard charts
 */
header('Content-Type: application/json');

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Timeline data for last 24 hours
    $timeline_labels = [];
    $timeline_data = [];
    
    for ($i = 23; $i >= 0; $i--) {
        $hour = date('H:00', strtotime("-{$i} hours"));
        $timeline_labels[] = $hour;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM security_events 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR) 
            AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$i + 1, $i]);
        $timeline_data[] = (int)$stmt->fetchColumn();
    }
    
    // Event types distribution
    $stmt = $pdo->prepare("
        SELECT event_type, COUNT(*) as count 
        FROM security_events 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY event_type 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $event_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $event_labels = [];
    $event_data = [];
    
    foreach ($event_types as $type) {
        $event_labels[] = ucfirst(str_replace('_', ' ', $type['event_type']));
        $event_data[] = (int)$type['count'];
    }
    
    echo json_encode([
        'success' => true,
        'timeline' => [
            'labels' => $timeline_labels,
            'data' => $timeline_data
        ],
        'event_types' => [
            'labels' => $event_labels,
            'data' => $event_data
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timeline' => ['labels' => [], 'data' => []],
        'event_types' => ['labels' => [], 'data' => []]
    ]);
}
?>
