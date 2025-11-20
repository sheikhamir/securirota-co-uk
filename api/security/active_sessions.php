<?php
/**
 * Security API - Active Sessions
 * Retrieves active user sessions
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
    
    // Get active sessions
    $stmt = $pdo->prepare("
        SELECT us.*, u.username, u.email, c.name as company_name
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE us.is_active = 1 AND us.expires_at > NOW()
        ORDER BY us.last_activity DESC
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format sessions for display
    $formatted_sessions = [];
    foreach ($sessions as $session) {
        $formatted_sessions[] = [
            'session_id' => $session['session_id'],
            'user_id' => $session['user_id'],
            'username' => $session['username'],
            'email' => $session['email'],
            'company_name' => $session['company_name'],
            'ip_address' => $session['ip_address'],
            'user_agent' => substr($session['user_agent'], 0, 100) . '...',
            'created_at' => $session['created_at'],
            'last_activity' => $session['last_activity'],
            'expires_at' => $session['expires_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'sessions' => $formatted_sessions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'sessions' => []
    ]);
}
?>
