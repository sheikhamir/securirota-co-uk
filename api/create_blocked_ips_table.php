<?php
/**
 * Create missing blocked_ips table for security dashboard
 */

header('Content-Type: application/json');

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create blocked_ips table if it doesn't exist
    $sql = "
        CREATE TABLE IF NOT EXISTS blocked_ips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) UNIQUE NOT NULL,
            reason TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_ip_address (ip_address),
            INDEX idx_is_active (is_active),
            INDEX idx_expires_at (expires_at)
        )
    ";
    
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Blocked IPs table created successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
