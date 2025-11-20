<?php
/**
 * File Upload Helper for Officer Documents
 * Handles secure file uploads for officer profile documents
 */

class DocumentUploader {
    private $upload_dir;
    private $max_file_size;
    private $allowed_types;
    
    public function __construct() {
        $this->upload_dir = __DIR__ . '/../uploads/officer_documents/';
        $this->max_file_size = 5 * 1024 * 1024; // 5MB
        $this->allowed_types = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'application/pdf'
        ];
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    /**
     * Upload a file and return the file path
     */
    public function uploadFile($file, $officer_id, $document_type) {
        try {
            // Validate file
            $this->validateFile($file);
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $officer_id . '_' . $document_type . '_' . time() . '.' . $extension;
            $filepath = $this->upload_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file.');
            }
            
            // Return relative path for database storage
            return 'uploads/officer_documents/' . $filename;
            
        } catch (Exception $e) {
            throw new Exception('Upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File is too large.');
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('File was only partially uploaded.');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No file was uploaded.');
                default:
                    throw new Exception('Unknown upload error.');
            }
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            throw new Exception('File size exceeds maximum allowed size (5MB).');
        }
        
        // Check file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            throw new Exception('File type not allowed. Only JPEG, PNG, GIF, and PDF files are permitted.');
        }
        
        // Additional security checks
        $this->performSecurityChecks($file);
    }
    
    /**
     * Perform additional security checks
     */
    private function performSecurityChecks($file) {
        // Check for PHP code in file (basic protection)
        $content = file_get_contents($file['tmp_name']);
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            throw new Exception('File contains potentially malicious content.');
        }
        
        // Check actual file extension matches MIME type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        $valid_extensions = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'application/pdf' => ['pdf']
        ];
        
        if (!isset($valid_extensions[$mime_type]) || 
            !in_array($extension, $valid_extensions[$mime_type])) {
            throw new Exception('File extension does not match file content.');
        }
    }
    
    /**
     * Save document information to database
     */
    public function saveDocumentToDatabase($conn, $officer_id, $document_type, $file_path, $original_filename) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO documents (officer_id, document_type, document_name, file_path) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                document_name = VALUES(document_name),
                file_path = VALUES(file_path),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            return $stmt->execute([$officer_id, $document_type, $original_filename, $file_path]);
            
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete old file when replacing
     */
    public function deleteOldFile($file_path) {
        $full_path = __DIR__ . '/../' . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    /**
     * Get uploaded documents for an officer
     */
    public function getOfficerDocuments($conn, $officer_id) {
        $stmt = $conn->prepare("
            SELECT document_type, document_name, file_path, created_at, updated_at 
            FROM documents 
            WHERE officer_id = ? 
            ORDER BY document_type, updated_at DESC
        ");
        $stmt->execute([$officer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}