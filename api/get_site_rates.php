<?php
// Prevent any output before JSON
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_clean();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Check for AJAX requests - don't redirect, return JSON error
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($_GET && isset($_GET['site_id'])) {
        $site_id = $_GET['site_id'];
        
        // Validate site exists
        $stmt = $conn->prepare("SELECT id FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Site not found']);
            exit();
        }
        
        // Get rate information with source details
        $rateInfo = getSiteRatesWithSource($site_id, $conn);
        
        if (empty($rateInfo)) {
            echo json_encode(['success' => false, 'message' => 'Unable to retrieve rate information']);
            exit();
        }
        
        echo json_encode([
            'success' => true,
            'site_id' => (int)$site_id,
            'site_name' => $rateInfo['site_name'],
            'client_name' => $rateInfo['client_name'],
            'rates' => [
                'client' => [
                    'effective_rate' => (float)$rateInfo['effective_client_rate'],
                    'site_rate' => $rateInfo['site_client_rate'] ? (float)$rateInfo['site_client_rate'] : null,
                    'client_default_rate' => $rateInfo['client_billing_rate'] ? (float)$rateInfo['client_billing_rate'] : null,
                    'source' => $rateInfo['client_rate_source']
                ]
            ],
            'note' => 'Officer rates are individual - use GET /api/get_officer.php?id={officer_id} to get officer rate'
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Site ID parameter is required']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>