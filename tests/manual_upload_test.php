<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Manual Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .upload-area { border: 2px dashed #ccc; padding: 20px; margin: 10px 0; text-align: center; }
        .upload-area:hover { border-color: #999; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>File Upload Manual Test</h1>
    
    <?php
    session_start();
    require_once '../config/config.php';
    require_once '../includes/document_uploader.php';
    
    // Set up fake session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    
    if ($_POST && isset($_FILES)) {
        echo "<h2>Upload Results:</h2>";
        
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Get first officer for testing
            $stmt = $conn->query("SELECT id, first_name, last_name FROM officers LIMIT 1");
            $officer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$officer) {
                echo "<div class='error'>No officers found in database for testing.</div>";
            } else {
                $officer_id = $officer['id'];
                echo "<div class='info'>Testing with Officer: {$officer['first_name']} {$officer['last_name']} (ID: $officer_id)</div>";
                
                $uploader = new DocumentUploader();
                $upload_count = 0;
                $error_count = 0;
                
                foreach ($_FILES as $field_name => $file) {
                    if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
                    
                    echo "<h3>Processing: $field_name</h3>";
                    
                    try {
                        // Show file info
                        echo "<div class='info'>";
                        echo "File: {$file['name']}<br>";
                        echo "Type: {$file['type']}<br>";
                        echo "Size: " . number_format($file['size'] / 1024, 2) . " KB<br>";
                        echo "</div>";
                        
                        // Upload file
                        $file_path = $uploader->uploadFile($file, $officer_id, $field_name);
                        echo "<div class='success'>✓ File uploaded successfully to: $file_path</div>";
                        
                        // Save to database
                        $saved = $uploader->saveDocumentToDatabase($conn, $officer_id, $field_name, $file_path, $file['name']);
                        if ($saved) {
                            echo "<div class='success'>✓ Database record created</div>";
                            $upload_count++;
                        } else {
                            echo "<div class='error'>✗ Failed to save to database</div>";
                            $error_count++;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='error'>✗ Upload failed: " . $e->getMessage() . "</div>";
                        $error_count++;
                    }
                }
                
                echo "<h3>Summary:</h3>";
                echo "<div class='info'>Successful uploads: $upload_count</div>";
                echo "<div class='info'>Failed uploads: $error_count</div>";
                
                // Show uploaded documents
                $documents = $uploader->getOfficerDocuments($conn, $officer_id);
                if (!empty($documents)) {
                    echo "<h3>Current Documents for Officer:</h3>";
                    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
                    echo "<tr><th>Type</th><th>Name</th><th>Path</th><th>Uploaded</th><th>Actions</th></tr>";
                    foreach ($documents as $doc) {
                        echo "<tr>";
                        echo "<td>{$doc['document_type']}</td>";
                        echo "<td>{$doc['document_name']}</td>";
                        echo "<td>{$doc['file_path']}</td>";
                        echo "<td>{$doc['updated_at']}</td>";
                        echo "<td><a href='../api/view_document.php?officer_id=$officer_id&type={$doc['document_type']}' target='_blank'>View</a></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>Test failed: " . $e->getMessage() . "</div>";
        }
    }
    ?>
    
    <h2>Upload Test Files:</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="upload-area">
            <label for="passport"><strong>Passport Photo:</strong></label><br>
            <input type="file" name="passport" id="passport" accept="image/*">
            <p>Upload a passport photo (JPEG, PNG, GIF up to 5MB)</p>
        </div>
        
        <div class="upload-area">
            <label for="sia_badge_front"><strong>SIA Badge Front:</strong></label><br>
            <input type="file" name="sia_badge_front" id="sia_badge_front" accept="image/*">
            <p>Upload SIA badge front photo (JPEG, PNG, GIF up to 5MB)</p>
        </div>
        
        <div class="upload-area">
            <label for="sia_badge_back"><strong>SIA Badge Back:</strong></label><br>
            <input type="file" name="sia_badge_back" id="sia_badge_back" accept="image/*">
            <p>Upload SIA badge back photo (JPEG, PNG, GIF up to 5MB)</p>
        </div>
        
        <div class="upload-area">
            <label for="full_body_photo"><strong>Full Body Photo:</strong></label><br>
            <input type="file" name="full_body_photo" id="full_body_photo" accept="image/*">
            <p>Upload full body photo (JPEG, PNG, GIF up to 5MB)</p>
        </div>
        
        <div class="upload-area">
            <label for="proof_of_address_1"><strong>Proof of Address 1:</strong></label><br>
            <input type="file" name="proof_of_address_1" id="proof_of_address_1" accept="image/*,.pdf">
            <p>Upload proof of address (JPEG, PNG, PDF up to 5MB)</p>
            <p style="color: #0066cc; font-size: 0.9em;"><strong>Accepted documents:</strong> Driving License, Bank Statement, or Utility Bill</p>
        </div>
        
        <div class="upload-area">
            <label for="proof_of_address_2"><strong>Proof of Address 2:</strong></label><br>
            <input type="file" name="proof_of_address_2" id="proof_of_address_2" accept="image/*,.pdf">
            <p>Upload second proof of address (JPEG, PNG, PDF up to 5MB)</p>
            <p style="color: #0066cc; font-size: 0.9em;"><strong>Accepted documents:</strong> Bank Statement or Utility Bill</p>
        </div>
        
        <div class="upload-area">
            <label for="brp_card"><strong>BRP Card (Optional):</strong></label><br>
            <input type="file" name="brp_card" id="brp_card" accept="image/*,.pdf">
            <p>Upload BRP card for foreign passport holders (JPEG, PNG, PDF up to 5MB)</p>
        </div>
        
        <div class="upload-area">
            <label for="visa_share_code_screenshot"><strong>Visa Share Code Screenshot (Optional):</strong></label><br>
            <input type="file" name="visa_share_code_screenshot" id="visa_share_code_screenshot" accept="image/*,.pdf">
            <p>Upload visa share code screenshot (JPEG, PNG, PDF up to 5MB)</p>
        </div>
        
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Upload Test Files
        </button>
    </form>
    
    <h2>Test Cases to Try:</h2>
    <ul>
        <li><strong>Valid files:</strong> Upload small JPEG, PNG, GIF, or PDF files under 5MB</li>
        <li><strong>Large files:</strong> Try uploading files over 5MB (should be rejected)</li>
        <li><strong>Invalid types:</strong> Try uploading .exe, .php, .js files (should be rejected)</li>
        <li><strong>Empty files:</strong> Try uploading empty files (should be rejected)</li>
        <li><strong>Multiple uploads:</strong> Upload files to different document types</li>
        <li><strong>Replace files:</strong> Upload new files for the same document type</li>
    </ul>
    
    <script>
    // Add some basic validation
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const maxSize = 5 * 1024 * 1024; // 5MB
            const parentDiv = e.target.closest('.upload-area');
            
            // Remove previous messages
            const existingMsg = parentDiv.querySelector('.validation-msg');
            if (existingMsg) existingMsg.remove();
            
            let message = '';
            let messageClass = 'info';
            
            if (file.size > maxSize) {
                message = '⚠️ File too large! Maximum size is 5MB.';
                messageClass = 'error';
            } else {
                message = `✓ File selected: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                messageClass = 'success';
            }
            
            const msgDiv = document.createElement('div');
            msgDiv.className = `validation-msg ${messageClass}`;
            msgDiv.innerHTML = message;
            msgDiv.style.marginTop = '10px';
            parentDiv.appendChild(msgDiv);
        });
    });
    </script>
</body>
</html>