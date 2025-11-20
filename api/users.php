<?php
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ActivityLogger.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check subscription status
requireActiveSubscriptionAPI();

try {
    $db = new Database();
    $conn = $db->getConnection();
    $logger = new ActivityLogger($conn);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'profile':
                    // Get current user profile
                    $stmt = $conn->prepare("
                        SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, u.updated_at,
                               CASE 
                                   WHEN o.id IS NOT NULL THEN CONCAT(o.first_name, ' ', o.last_name)
                                   ELSE NULL 
                               END as officer_name,
                               o.id as officer_id
                        FROM users u 
                        LEFT JOIN officers o ON u.id = o.user_id 
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        // Remove sensitive data
                        unset($user['password']);
                        echo json_encode(['success' => true, 'data' => $user]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                    }
                    break;
                    
                case 'check_username':
                    // Check if username is available
                    $username = $_GET['username'] ?? '';
                    $exclude_id = $_GET['exclude_id'] ?? 0;
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $exclude_id]);
                    $exists = $stmt->fetchColumn() > 0;
                    
                    echo json_encode(['success' => true, 'available' => !$exists]);
                    break;
                    
                case 'check_email':
                    // Check if email is available
                    $email = $_GET['email'] ?? '';
                    $exclude_id = $_GET['exclude_id'] ?? 0;
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $exclude_id]);
                    $exists = $stmt->fetchColumn() > 0;
                    
                    echo json_encode(['success' => true, 'available' => !$exists]);
                    break;
                    
                case 'users':
                    // Get all users (admin only)
                    if (!isAdmin()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Admin access required']);
                        exit();
                    }
                    
                    $stmt = $conn->prepare("
                        SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, u.updated_at,
                               CASE 
                                   WHEN o.id IS NOT NULL THEN CONCAT(o.first_name, ' ', o.last_name)
                                   ELSE NULL 
                               END as officer_name,
                               o.id as officer_id
                        FROM users u 
                        LEFT JOIN officers o ON u.id = o.user_id 
                        ORDER BY u.created_at DESC
                    ");
                    $stmt->execute();
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $users]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'update_profile':
                    // Update current user profile
                    $username = trim($input['username'] ?? '');
                    $email = trim($input['email'] ?? '');
                    
                    if (empty($username) || empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Username and email are required']);
                        break;
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        break;
                    }
                    
                    // Check for duplicates
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->execute([$username, $email, $_SESSION['user_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                        break;
                    }
                    
                    // Update profile
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $_SESSION['user_id']]);
                    
                    $_SESSION['username'] = $username;
                    
                    // Log the activity
                    $description = "Updated profile - username: {$username}, email: {$email}";
                    $metadata = [
                        'new_username' => $username,
                        'new_email' => $email
                    ];
                    $logger->logUserAction($_SESSION['user_id'], 'update', $_SESSION['user_id'], $description, $metadata);
                    
                    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                    break;
                    
                case 'change_password':
                    // Change current user password
                    $current_password = $input['current_password'] ?? '';
                    $new_password = $input['new_password'] ?? '';
                    
                    if (empty($current_password) || empty($new_password)) {
                        echo json_encode(['success' => false, 'message' => 'Both passwords are required']);
                        break;
                    }
                    
                    // Get current password hash
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_hash = $stmt->fetchColumn();
                    
                    if (!password_verify($current_password, $current_hash)) {
                        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                        break;
                    }
                    
                    if (strlen($new_password) < 6) {
                        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                        break;
                    }
                    
                    // Update password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$new_hash, $_SESSION['user_id']]);
                    
                    // Log the activity
                    $description = "Changed password";
                    $logger->logUserAction($_SESSION['user_id'], 'update', $_SESSION['user_id'], $description, ['action' => 'password_change']);
                    
                    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                    break;
                    
                case 'create_user':
                    // Create new user (admin only)
                    if (!isAdmin()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Admin access required']);
                        break;
                    }
                    
                    $username = str_replace(' ', '', trim($input['username'] ?? ''));
                    $email = trim($input['email'] ?? '');
                    $password = $input['password'] ?? '';
                    $role = $input['role'] ?? 'officer';
                    $status = $input['status'] ?? 'active';
                    
                    if (empty($username) || empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'message' => 'Username, email, and password are required']);
                        break;
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        break;
                    }
                    
                    if (strlen($password) < 6) {
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                        break;
                    }
                    
                    // Check for duplicates
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                        break;
                    }
                    
                    // Create user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password, role, status) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $email, $password_hash, $role, $status]);
                    
                    $new_user_id = $conn->lastInsertId();
                    
                    // Log the activity
                    $description = "Created new user: {$username} ({$email}) with role: {$role}";
                    $metadata = [
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                        'status' => $status
                    ];
                    $logger->logUserAction($_SESSION['user_id'], 'create', $new_user_id, $description, $metadata);
                    
                    echo json_encode(['success' => true, 'message' => 'User created successfully']);
                    break;
                    
                case 'update_user':
                    // Update user (admin only)
                    if (!isAdmin()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Admin access required']);
                        break;
                    }
                    
                    $user_id = (int)($input['user_id'] ?? 0);
                    $username = trim($input['username'] ?? '');
                    $email = trim($input['email'] ?? '');
                    $role = $input['role'] ?? 'officer';
                    $status = $input['status'] ?? 'active';
                    
                    if (empty($username) || empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Username and email are required']);
                        break;
                    }
                    
                    // Check for duplicates
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->execute([$username, $email, $user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                        break;
                    }
                    
                    // Update user
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, role = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $role, $status, $user_id]);
                    
                    // Log the activity
                    $description = "Updated user: {$username} ({$email}) - role: {$role}, status: {$status}";
                    $metadata = [
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                        'status' => $status
                    ];
                    $logger->logUserAction($_SESSION['user_id'], 'update', $user_id, $description, $metadata);
                    
                    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'DELETE':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            $user_id = (int)($_GET['id'] ?? 0);
            
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                break;
            }
            
            // Check for officer associations
            $stmt = $conn->prepare("SELECT COUNT(*) FROM officers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete user with officer associations']);
                break;
            }
            
            // Get user info before deletion for logging
            $stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Log the activity
            if ($user_data) {
                $description = "Deleted user: {$user_data['username']} ({$user_data['email']}) - role: {$user_data['role']}";
                $metadata = [
                    'deleted_user' => $user_data
                ];
                $logger->logUserAction($_SESSION['user_id'], 'delete', $user_id, $description, $metadata);
            }
            
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
