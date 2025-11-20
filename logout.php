<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/ActivityLogger.php';

// Start session
session_start();

// Log the logout event
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'Unknown';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $logger = new ActivityLogger($conn);
        
        // Log the logout activity
        $description = "User logged out: {$username}";
        $metadata = [
            'username' => $username,
            'logout_time' => date('Y-m-d H:i:s')
        ];
        $logger->logSystemAction($user_id, 'logout', $description, $metadata);
    } catch (Exception $e) {
        // Fallback to error log if database logging fails
        error_log("User logout: ID=$user_id, Username=$username, IP=" . $_SERVER['REMOTE_ADDR'] . ", Time=" . date('Y-m-d H:i:s'));
    }
}

// Destroy all session data
$_SESSION = array();

// If it's desired to kill the session cookie as well
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

// Redirect to login page with logout message
header('Location: ' . BASE_URL . 'login.php?message=logged_out');
exit();
?>
