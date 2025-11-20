<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['company_id']) || !isset($input['status'])) {
        throw new Exception("Company ID and status are required");
    }
    
    $company_id = intval($input['company_id']);
    $status = $input['status'];
    
    // Validate status
    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
        throw new Exception("Invalid status value");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get company details for logging
    $stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        throw new Exception("Company not found");
    }
    
    // Update company status
    $stmt = $conn->prepare("UPDATE companies SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$status, $company_id]);
    
    // Log the activity
    require_once '../../includes/ActivityLogger.php';
    $logger = new ActivityLogger($conn);
    $logger->logSystemAction(
        $_SESSION['user_id'], 
        'company_status_updated', 
        "Updated company status: " . $company['name'] . " -> " . $status, 
        [
            'company_id' => $company_id,
            'company_name' => $company['name'],
            'old_status' => 'unknown', // We could query this if needed
            'new_status' => $status
        ]
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'Company status updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Update Company Status Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
