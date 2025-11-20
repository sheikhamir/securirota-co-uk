<?php
/**
 * Emergency Maintenance Mode API
 * Allows root user to enable/disable maintenance mode
 */
session_start();
require_once '../../config/config.php';

// Check if user is root
if (!isRootUser()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'enable_maintenance') {
    // Create maintenance mode file
    $maintenance_file = '../../maintenance.lock';
    $maintenance_data = [
        'enabled' => true,
        'enabled_by' => $_SESSION['username'],
        'enabled_at' => date('Y-m-d H:i:s'),
        'message' => 'Emergency maintenance mode activated by system administrator.'
    ];
    
    file_put_contents($maintenance_file, json_encode($maintenance_data));
    
    echo json_encode([
        'success' => true,
        'message' => 'Emergency maintenance mode enabled successfully'
    ]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
