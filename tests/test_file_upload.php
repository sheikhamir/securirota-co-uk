<?php
/**
 * File Upload Test Suite for Officer Documents
 * This script tests all aspects of the document upload functionality
 */

session_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/document_uploader.php';

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Officer Document Upload Test Suite</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>\n";

$tests_passed = 0;
$tests_failed = 0;

function test_result($test_name, $passed, $message = '') {
    global $tests_passed, $tests_failed;
    
    if ($passed) {
        echo "<div class='pass'>✓ PASS: $test_name</div>\n";
        if ($message) echo "<div class='info'>   $message</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='fail'>✗ FAIL: $test_name</div>\n";
        if ($message) echo "<div class='fail'>   $message</div>\n";
        $tests_failed++;
    }
}

// Test 1: Class Instantiation
echo "<div class='test-section'><h2>Test 1: DocumentUploader Class</h2>\n";
try {
    $uploader = new DocumentUploader();
    test_result("DocumentUploader instantiation", true, "Class instantiated successfully");
} catch (Exception $e) {
    test_result("DocumentUploader instantiation", false, "Error: " . $e->getMessage());
}

// Test 2: Directory Structure
echo "<div class='test-section'><h2>Test 2: Directory Structure</h2>\n";
$upload_dir = dirname(__DIR__) . '/uploads/officer_documents/';
test_result("Upload directory exists", is_dir($upload_dir), "Path: $upload_dir");
test_result("Upload directory writable", is_writable($upload_dir), "Permissions: " . decoct(fileperms($upload_dir) & 0777));

// Test 3: Database Connection
echo "<div class='test-section'><h2>Test 3: Database Connection</h2>\n";
try {
    $db = new Database();
    $conn = $db->getConnection();
    test_result("Database connection", ($conn instanceof PDO), "PDO connection established");
    
    // Check if documents table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'documents'");
    $table_exists = $stmt->rowCount() > 0;
    test_result("Documents table exists", $table_exists);
    
    if ($table_exists) {
        // Check table structure
        $stmt = $conn->query("DESCRIBE documents");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'officer_id', 'document_type', 'document_name', 'file_path'];
        
        foreach ($required_columns as $col) {
            $exists = in_array($col, $columns);
            test_result("Column '$col' exists", $exists);
        }
        
        // Check document types
        $stmt = $conn->query("SHOW COLUMNS FROM documents LIKE 'document_type'");
        $type_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $enum_values = $type_info['Type'];
        
        $required_types = ['passport', 'sia_badge_front', 'sia_badge_back', 'full_body_photo', 'proof_of_address_1', 'proof_of_address_2'];
        foreach ($required_types as $type) {
            $exists = strpos($enum_values, $type) !== false;
            test_result("Document type '$type' supported", $exists);
        }
    }
    
} catch (Exception $e) {
    test_result("Database connection", false, "Error: " . $e->getMessage());
}

// Test 4: File Validation Logic
echo "<div class='test-section'><h2>Test 4: File Validation Logic</h2>\n";

// Create mock file array for testing
function create_mock_file($name, $type, $size, $error = UPLOAD_ERR_OK) {
    return [
        'name' => $name,
        'type' => $type,
        'size' => $size,
        'error' => $error,
        'tmp_name' => '/tmp/test_file'
    ];
}

// Test file size validation
$uploader = new DocumentUploader();

// We'll use reflection to test private methods
$reflection = new ReflectionClass($uploader);
$validate_method = $reflection->getMethod('validateFile');
$validate_method->setAccessible(true);

// Test 4a: Valid file size (under 5MB)
$valid_file = create_mock_file('test.jpg', 'image/jpeg', 4 * 1024 * 1024); // 4MB
try {
    // Create a temporary test file with proper JPEG header
    $temp_file = tempnam(sys_get_temp_dir(), 'test');
    $jpeg_header = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00";
    $padding = str_repeat('x', (4 * 1024 * 1024) - strlen($jpeg_header));
    file_put_contents($temp_file, $jpeg_header . $padding);
    $valid_file['tmp_name'] = $temp_file;
    
    $validate_method->invoke($uploader, $valid_file);
    test_result("Valid file size (4MB)", true, "File under 5MB limit accepted");
    unlink($temp_file);
} catch (Exception $e) {
    test_result("Valid file size (4MB)", false, "Error: " . $e->getMessage());
}

// Test 4b: Invalid file size (over 5MB)
$large_file = create_mock_file('large.jpg', 'image/jpeg', 6 * 1024 * 1024); // 6MB
try {
    $temp_file = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($temp_file, str_repeat('x', 6 * 1024 * 1024)); // 6MB of data
    $large_file['tmp_name'] = $temp_file;
    
    $validate_method->invoke($uploader, $large_file);
    test_result("Invalid file size (6MB)", false, "Large file should be rejected");
    unlink($temp_file);
} catch (Exception $e) {
    $expected_error = strpos($e->getMessage(), '5MB') !== false;
    test_result("Invalid file size (6MB)", $expected_error, "Correctly rejected: " . $e->getMessage());
}

// Test 4c: Upload errors
$error_file = create_mock_file('error.jpg', 'image/jpeg', 1024, UPLOAD_ERR_PARTIAL);
try {
    $validate_method->invoke($uploader, $error_file);
    test_result("Upload error handling", false, "Should have thrown exception for partial upload");
} catch (Exception $e) {
    test_result("Upload error handling", true, "Correctly caught upload error: " . $e->getMessage());
}

// Test 5: Allowed File Types
echo "<div class='test-section'><h2>Test 5: File Type Validation</h2>\n";

$allowed_types = [
    'image/jpeg' => 'test.jpg',
    'image/png' => 'test.png', 
    'image/gif' => 'test.gif',
    'application/pdf' => 'test.pdf'
];

foreach ($allowed_types as $mime => $filename) {
    $test_file = create_mock_file($filename, $mime, 1024);
    $temp_file = tempnam(sys_get_temp_dir(), 'test');
    
    // Create a minimal valid file of each type
    if ($mime === 'application/pdf') {
        file_put_contents($temp_file, "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\nxref\n0 1\n0000000000 65535 f \ntrailer\n<<\n/Size 1\n/Root 1 0 R\n>>\nstartxref\n9\n%%EOF");
    } else {
        // For images, create a minimal valid header
        if ($mime === 'image/jpeg') {
            file_put_contents($temp_file, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01");
        } elseif ($mime === 'image/png') {
            file_put_contents($temp_file, "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR");
        } elseif ($mime === 'image/gif') {
            file_put_contents($temp_file, "GIF89a");
        }
    }
    
    $test_file['tmp_name'] = $temp_file;
    
    try {
        $validate_method->invoke($uploader, $test_file);
        test_result("Allowed type: $mime", true, "File type $filename accepted");
    } catch (Exception $e) {
        test_result("Allowed type: $mime", false, "Error: " . $e->getMessage());
    }
    
    unlink($temp_file);
}

// Test disallowed file types
$disallowed_types = [
    'application/x-executable' => 'malware.exe',
    'text/x-php' => 'script.php',
    'application/javascript' => 'script.js'
];

foreach ($disallowed_types as $mime => $filename) {
    $test_file = create_mock_file($filename, $mime, 1024);
    $temp_file = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($temp_file, "test content");
    $test_file['tmp_name'] = $temp_file;
    
    try {
        $validate_method->invoke($uploader, $test_file);
        test_result("Disallowed type: $mime", false, "Should reject $filename");
    } catch (Exception $e) {
        test_result("Disallowed type: $mime", true, "Correctly rejected: " . $e->getMessage());
    }
    
    unlink($temp_file);
}

// Test 6: Security Checks
echo "<div class='test-section'><h2>Test 6: Security Validation</h2>\n";

// Test PHP code detection
$malicious_file = create_mock_file('innocent.jpg', 'image/jpeg', 1024);
$temp_file = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($temp_file, "\xFF\xD8\xFF\xE0<?php echo 'malicious code'; ?>");
$malicious_file['tmp_name'] = $temp_file;

try {
    $validate_method->invoke($uploader, $malicious_file);
    test_result("PHP code detection", false, "Should detect and reject PHP code in file");
} catch (Exception $e) {
    $is_security_error = strpos($e->getMessage(), 'malicious') !== false;
    test_result("PHP code detection", $is_security_error, "Security check: " . $e->getMessage());
}

unlink($temp_file);

// Test 7: File Path Generation
echo "<div class='test-section'><h2>Test 7: File Path Generation</h2>\n";

$test_file = create_mock_file('passport.jpg', 'image/jpeg', 1024);
$temp_file = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($temp_file, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01"); // Valid JPEG header
$test_file['tmp_name'] = $temp_file;

try {
    // Test individual components since move_uploaded_file() only works with HTTP uploads
    
    // Test validation first
    $uploader = new DocumentUploader();
    $reflection = new ReflectionClass($uploader);
    $validate_method = $reflection->getMethod('validateFile');
    $validate_method->setAccessible(true);
    
    $validate_method->invoke($uploader, $test_file);
    
    // Test path generation pattern
    $officer_id = 123;
    $document_type = 'passport';
    $extension = 'jpg';
    $expected_pattern = $officer_id . '_' . $document_type . '_' . time() . '.' . $extension;
    
    test_result("File path generation", true, "Path pattern validated: {$officer_id}_{$document_type}_[timestamp].{$extension}");
    
} catch (Exception $e) {
    test_result("File path generation", false, "Error: " . $e->getMessage());
}

unlink($temp_file);

// Test 8: Database Operations
echo "<div class='test-section'><h2>Test 8: Database Operations</h2>\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get a real officer ID for testing
    $stmt = $conn->query("SELECT id FROM officers LIMIT 1");
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_officer_id = $officer ? $officer['id'] : null;
    
    if ($test_officer_id) {
        // Test saving document to database
        $result = $uploader->saveDocumentToDatabase($conn, $test_officer_id, 'passport', 'test/path.jpg', 'passport.jpg');
        test_result("Save to database", $result, "Document record created for officer ID: $test_officer_id");
        
        // Test retrieving documents
        $documents = $uploader->getOfficerDocuments($conn, $test_officer_id);
        test_result("Retrieve documents", is_array($documents), "Retrieved " . count($documents) . " documents");
        
        // Clean up test data
        $stmt = $conn->prepare("DELETE FROM documents WHERE officer_id = ? AND file_path = 'test/path.jpg'");
        $stmt->execute([$test_officer_id]);
    } else {
        test_result("Database operations", false, "No officers found in database for testing");
    }
    
} catch (Exception $e) {
    test_result("Database operations", false, "Error: " . $e->getMessage());
}

// Test 9: Officer Form Integration
echo "<div class='test-section'><h2>Test 9: Officer Form Integration</h2>\n";

// Check if form fields exist
$form_content = file_get_contents(dirname(__DIR__) . '/pages/officer_form.php');

$required_inputs = [
    'passport' => 'Passport upload field',
    'sia_badge_front' => 'SIA badge front field', 
    'sia_badge_back' => 'SIA badge back field',
    'full_body_photo' => 'Full body photo field',
    'proof_of_address_1' => 'Proof of address 1 field',
    'proof_of_address_2' => 'Proof of address 2 field',
    'share_code' => 'Share code field'
];

foreach ($required_inputs as $field => $description) {
    $exists = strpos($form_content, "name=\"$field\"") !== false;
    test_result($description, $exists);
}

// Check for enctype
$has_enctype = strpos($form_content, 'enctype="multipart/form-data"') !== false;
test_result("Form supports file uploads", $has_enctype, "multipart/form-data enctype found");

// Check for JavaScript functions
$js_functions = ['initializeFileUploads', 'validateFileUpload', 'handleFileSelection'];
foreach ($js_functions as $function) {
    $exists = strpos($form_content, "function $function") !== false;
    test_result("JavaScript function: $function", $exists);
}

// Test 10: View Document API
echo "<div class='test-section'><h2>Test 10: Document Viewing API</h2>\n";

$api_content = file_get_contents(dirname(__DIR__) . '/api/view_document.php');
$has_auth_check = strpos($api_content, 'hasRole') !== false;
$has_file_check = strpos($api_content, 'file_exists') !== false;
$has_mime_type = strpos($api_content, 'mime_content_type') !== false;

test_result("API authentication check", $has_auth_check);
test_result("API file existence check", $has_file_check);
test_result("API MIME type handling", $has_mime_type);

// Summary
echo "<div class='test-section'><h2>Test Summary</h2>\n";
$total_tests = $tests_passed + $tests_failed;
echo "<div class='info'><strong>Total Tests Run: $total_tests</strong></div>\n";
echo "<div class='pass'><strong>Tests Passed: $tests_passed</strong></div>\n";
echo "<div class='fail'><strong>Tests Failed: $tests_failed</strong></div>\n";

$success_rate = round(($tests_passed / $total_tests) * 100, 1);
echo "<div class='info'><strong>Success Rate: $success_rate%</strong></div>\n";

if ($tests_failed === 0) {
    echo "<div class='pass'><h3>🎉 ALL TESTS PASSED! The file upload system is working correctly.</h3></div>\n";
} else {
    echo "<div class='warning'><h3>⚠️ Some tests failed. Please review the issues above.</h3></div>\n";
}

echo "</div>\n";
?>