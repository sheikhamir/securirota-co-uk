<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $site_id = $_GET['site_id'] ?? '';
    
    if (!$site_id) {
        echo json_encode(['success' => false, 'message' => 'Site ID required']);
        exit();
    }
    
    // Get site templates from site_instructions field
    $stmt = $conn->prepare("SELECT site_instructions FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $templates = [];
    
    if ($site && $site['site_instructions']) {
        $decoded = json_decode($site['site_instructions'], true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['templates'])) {
            foreach ($decoded['templates'] as $name => $template) {
                $templates[] = [
                    'name' => $name,
                    'shifts_count' => count($template['shifts'] ?? []),
                    'created_at' => date('M j, Y', strtotime($template['created_at'] ?? 'now')),
                    'created_by' => $template['created_by'] ?? 'Unknown'
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
    
} catch (Exception $e) {
    error_log("Get Site Templates Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
