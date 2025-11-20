<?php
session_start();
require_once '../config/config.php';

// Check authentication
if (!hasRole('admin') && !hasRole('manager')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$document_id = $_GET['id'] ?? null;
$officer_id = $_GET['officer_id'] ?? null;
$doc_type = $_GET['type'] ?? null;
$download = isset($_GET['download']) && $_GET['download'] == '1';

if (!$document_id && !($officer_id && $doc_type)) {
    header('HTTP/1.0 404 Not Found');
    exit('Document not found');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($document_id) {
        // Get document by ID
        $stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$document_id]);
    } else {
        // Get document by officer_id and type
        $stmt = $conn->prepare("SELECT * FROM documents WHERE officer_id = ? AND document_type = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$officer_id, $doc_type]);
    }
    
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        header('HTTP/1.0 404 Not Found');
        exit('Document not found');
    }
    
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $document['file_path'];
    
    if (!file_exists($file_path)) {
        header('HTTP/1.0 404 Not Found');
        exit('File not found');
    }
    
    // Get file info
    $file_info = pathinfo($file_path);
    $mime_type = mime_content_type($file_path);
    
    // Set appropriate headers
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($file_path));
    
    if ($download) {
        // Force download
        header('Content-Disposition: attachment; filename="' . $document['document_name'] . '"');
    } else {
        // Display inline
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    }
    
    // Output file
    readfile($file_path);
    
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Error loading document');
}
?>
