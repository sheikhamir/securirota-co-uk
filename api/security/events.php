<?php
/**
 * Security API - Security Events
 * Retrieves security events with filtering
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
    
    $filter = $_GET['filter'] ?? 'all';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $where_clause = "WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $params = [];
    
    if ($filter !== 'all') {
        $where_clause .= " AND event_type = ?";
        $params[] = $filter;
    }
    
    // Get security events
    $stmt = $pdo->prepare("
        SELECT se.*, u.username 
        FROM security_events se
        LEFT JOIN users u ON se.user_id = u.id
        {$where_clause}
        ORDER BY se.created_at DESC 
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format events for display
    $formatted_events = [];
    foreach ($events as $event) {
        $details = json_decode($event['details'], true);
        $details_text = '';
        
        if (is_array($details)) {
            $details_array = [];
            foreach ($details as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $details_array[] = ucfirst($key) . ': ' . $value;
                }
            }
            $details_text = implode(', ', $details_array);
        } else {
            $details_text = $event['details'];
        }
        
        $formatted_events[] = [
            'id' => $event['id'],
            'event_type' => $event['event_type'],
            'username' => $event['username'],
            'ip_address' => $event['ip_address'],
            'details' => $details_text,
            'created_at' => $event['created_at'],
            'identifier' => $event['username'] ?: $event['ip_address']
        ];
    }
    
    // Get total count for pagination
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM security_events 
        {$where_clause}
    ");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'events' => $formatted_events,
        'total_count' => $total_count,
        'has_more' => ($offset + $limit) < $total_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'events' => []
    ]);
}
?>
