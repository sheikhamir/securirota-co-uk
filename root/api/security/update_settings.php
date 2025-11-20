<?php
/**
 * Security API - Update Settings
 * Updates security configuration settings
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
    
    // Validate settings
    $settings = [
        'rate_limit' => max(100, min(10000, (int)($input['rate_limit'] ?? 1000))),
        'max_login_attempts' => max(3, min(10, (int)($input['max_login_attempts'] ?? 5))),
        'lockout_duration' => max(5, min(60, (int)($input['lockout_duration'] ?? 15))) * 60, // Convert to seconds
        'session_timeout' => max(30, min(480, (int)($input['session_timeout'] ?? 60))) * 60, // Convert to seconds
        'require_2fa' => isset($input['require_2fa']) && $input['require_2fa'] === 'on',
        'password_complexity' => isset($input['password_complexity']) && $input['password_complexity'] === 'on'
    ];
    
    // Check if security_settings table exists, create if not
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'security_settings'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE security_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by INT,
                FOREIGN KEY (updated_by) REFERENCES users(id)
            )
        ");
    }
    
    // Update each setting
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO security_settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            updated_by = VALUES(updated_by),
            updated_at = NOW()
        ");
        $stmt->execute([$key, json_encode($value), $_SESSION['user_id']]);
    }
    
    // Log the security configuration change
    $security->logSecurityEvent('security_settings_updated', $_SESSION['username'], [
        'updated_by' => $_SESSION['username'],
        'settings' => $settings
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Security settings updated successfully',
        'settings' => $settings
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
