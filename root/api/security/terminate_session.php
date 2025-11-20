<?php
/**
 * Security API - Terminate Session
 * Terminates a specific user session
 */
header('Content-Type: application/json');

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/SecurityManager.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $security = new SecurityManager($pdo);
    $session_id = $input['session_id'] ?? '';
    
    if (empty($session_id)) {
        throw new Exception('Session ID is required');
    }
    
    // Get session details before terminating
    $stmt = $pdo->prepare("
        SELECT us.*, u.username 
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
        WHERE us.session_id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        throw new Exception('Session not found');
    }
    
    // Terminate the session
    $stmt = $pdo->prepare("
        UPDATE user_sessions 
        SET is_active = 0 
        WHERE session_id = ?
    ");
    $stmt->execute([$session_id]);
    
    // Log the security event
    $security->logSecurityEvent('session_terminated', $session['username'], [
        'terminated_by' => $_SESSION['username'],
        'session_id' => $session_id,
        'target_user' => $session['username'],
        'ip_address' => $session['ip_address']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Session terminated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
