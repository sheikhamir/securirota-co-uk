<?php
/**
 * API endpoint for removing officer documents
 * Handles AJAX requests to delete documents from both database and filesystem
 */

session_start();
require_once '../config/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated and has proper permissions
if (!hasRole('admin') && !hasRole('manager')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['officer_id']) || !isset($input['document_type'])) {
        throw new Exception('Missing required parameters');
    }
    
    $officer_id = intval($input['officer_id']);
    $document_type = trim($input['document_type']);
    
    // Validate officer ID
    if ($officer_id <= 0) {
        throw new Exception('Invalid officer ID');
    }
    
    // Validate document type
    $allowed_document_types = [
        'passport', 
        'full_body_photo', 
        'sia_badge_front', 
        'sia_badge_back', 
        'proof_of_address_1', 
        'proof_of_address_2', 
        'brp_card', 
        'visa_share_code_screenshot'
    ];
    
    if (!in_array($document_type, $allowed_document_types)) {
        throw new Exception('Invalid document type');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get document information first
    $stmt = $conn->prepare("
        SELECT file_path 
        FROM documents 
        WHERE officer_id = ? AND document_type = ?
    ");
    $stmt->execute([$officer_id, $document_type]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception('Document not found');
    }
    
    // Delete from database
    $stmt = $conn->prepare("
        DELETE FROM documents 
        WHERE officer_id = ? AND document_type = ?
    ");
    $stmt->execute([$officer_id, $document_type]);
    
    // Delete physical file
    $file_path = __DIR__ . '/../' . $document['file_path'];
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            // Log warning but don't fail the operation
            error_log("Warning: Failed to delete file: " . $file_path);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    if (function_exists('logActivity')) {
        logActivity($conn, $_SESSION['user_id'], 'document_removed', 
                   "Removed {$document_type} document for officer ID {$officer_id}");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Document removed successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>