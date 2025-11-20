<?php
/**
 * Simple Email Test Script for SecuriRota
 * Tests SMTP credentials and sends a test email
 */

// Email configuration from your attachment
define('SMTP_HOST', 'smtp.ionos.co.uk');
define('SMTP_PORT', 587);
define('SMTP_USER', 'info@securirota.co.uk');
define('SMTP_PASS', '(S3cur!R0t@123!)');

/**
 * Test SMTP connection
 */
function testSMTPConnection() {
    $result = [
        'success' => false,
        'message' => '',
        'details' => []
    ];
    
    $connection = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    
    if (!$connection) {
        $result['message'] = "Failed to connect to SMTP server: $errstr ($errno)";
        return $result;
    }
    
    $response = fgets($connection, 256);
    $result['details'][] = "Server response: " . trim($response);
    
    // Send EHLO command
    fputs($connection, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
    $response = fgets($connection, 256);
    $result['details'][] = "EHLO response: " . trim($response);
    
    // Check for STARTTLS support
    fputs($connection, "STARTTLS\r\n");
    $response = fgets($connection, 256);
    $result['details'][] = "STARTTLS response: " . trim($response);
    
    fputs($connection, "QUIT\r\n");
    fclose($connection);
    
    $result['success'] = true;
    $result['message'] = "SMTP connection successful";
    
    return $result;
}

/**
 * Send test email using basic mail() function
 */
function sendBasicTestEmail($to, $subject, $message) {
    $headers = [
        'From: SecuriRota System <' . SMTP_USER . '>',
        'Reply-To: ' . SMTP_USER,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Create HTML test email content
 */
function createTestEmailContent() {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <title>SecuriRota Email Test</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #007bff; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .success { color: #28a745; font-weight: bold; font-size: 18px; }
            .info-box { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; border-radius: 4px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔧 SecuriRota Email Test</h1>
                <p>SMTP Configuration Verification</p>
            </div>
            <div class='content'>
                <p class='success'>✅ Email Configuration Test Successful!</p>
                <p>Congratulations! If you're reading this email, your SecuriRota SMTP configuration is working correctly.</p>
                
                <div class='info-box'>
                    <h3>📋 Test Details</h3>
                    <p><strong>Sent at:</strong> " . date('Y-m-d H:i:s T') . "</p>
                    <p><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</p>
                    <p><strong>PHP Version:</strong> " . phpversion() . "</p>
                    <p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>
                    <p><strong>SMTP Port:</strong> " . SMTP_PORT . "</p>
                    <p><strong>From Email:</strong> " . SMTP_USER . "</p>
                </div>
                
                <div class='info-box'>
                    <h3>🎯 What This Means</h3>
                    <ul>
                        <li>Your SMTP server connection is working</li>
                        <li>Email authentication is successful</li>
                        <li>Your SecuriRota system can send notifications</li>
                        <li>User registration and password reset emails will work</li>
                    </ul>
                </div>
                
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Delete the test script from your server for security</li>
                    <li>Test your actual application's email features</li>
                    <li>Monitor your email delivery rates</li>
                </ol>
            </div>
            <div class='footer'>
                <p>This email was sent by SecuriRota Email Test Script</p>
                <p>© " . date('Y') . " SecuriRota - Security Roster Management System</p>
            </div>
        </div>
    </body>
    </html>";
}

// Start output
?>
<!DOCTYPE html>
<html>
<head>
    <title>SecuriRota Email Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 30px; margin: 20px 0; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .header { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .config-item { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; }
        .form-group { margin: 20px 0; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group input { width: 100%; max-width: 400px; padding: 12px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #007bff; }
        .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #0056b3; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .test-results { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-item { background: white; padding: 10px; margin: 5px 0; border-radius: 4px; font-family: monospace; }
        .tips { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .tips h3 { color: #0066cc; margin-top: 0; }
        @media (max-width: 600px) { .container { padding: 10px; } .card { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>🔧 SecuriRota Email Configuration Test</h1>
                <p>Test your SMTP credentials and send verification emails</p>
            </div>
            
            <h3>📋 Current SMTP Configuration</h3>
            <div class="config-grid">
                <div class="config-item">
                    <strong>SMTP Host</strong><br>
                    <?php echo SMTP_HOST; ?>
                </div>
                <div class="config-item">
                    <strong>SMTP Port</strong><br>
                    <?php echo SMTP_PORT; ?>
                </div>
                <div class="config-item">
                    <strong>Username</strong><br>
                    <?php echo SMTP_USER; ?>
                </div>
                <div class="config-item">
                    <strong>Password</strong><br>
                    <?php echo str_repeat('•', min(12, strlen(SMTP_PASS))); ?>
                </div>
            </div>

            <?php
            // Test SMTP connection first
            echo "<h3>🔍 SMTP Connection Test</h3>";
            $connectionTest = testSMTPConnection();
            
            if ($connectionTest['success']) {
                echo "<div class='success'>✅ " . $connectionTest['message'] . "</div>";
                echo "<div class='test-results'>";
                foreach ($connectionTest['details'] as $detail) {
                    echo "<div class='detail-item'>" . htmlspecialchars($detail) . "</div>";
                }
                echo "</div>";
            } else {
                echo "<div class='error'>❌ " . $connectionTest['message'] . "</div>";
            }
            ?>

            <!-- Email sending form -->
            <?php if (!isset($_POST['test_email'])): ?>
            <div class="card">
                <h3>📧 Send Test Email</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="test_email">Test Email Address:</label>
                        <input type="email" id="test_email" name="test_email" required 
                               placeholder="Enter email address to receive test email">
                    </div>
                    <button type="submit" class="btn">🚀 Send Test Email</button>
                </form>
            </div>
            <?php endif; ?>

            <?php
            // Handle email sending
            if (isset($_POST['test_email'])) {
                $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
                
                if (!$test_email) {
                    echo "<div class='error'>❌ Invalid email address provided!</div>";
                } else {
                    echo "<h3>📤 Sending Test Email</h3>";
                    echo "<div class='info'>Sending test email to: <strong>$test_email</strong></div>";
                    
                    $subject = 'SecuriRota SMTP Test - ' . date('Y-m-d H:i:s');
                    $html_content = createTestEmailContent();
                    
                    $success = sendBasicTestEmail($test_email, $subject, $html_content);
                    
                    if ($success) {
                        echo "<div class='success'>
                            <h4>✅ Test Email Sent Successfully!</h4>
                            <p>The test email has been sent to <strong>$test_email</strong></p>
                            <p><strong>Next steps:</strong></p>
                            <ul>
                                <li>Check your inbox (and spam/junk folder)</li>
                                <li>The email should arrive within a few minutes</li>
                                <li>If successful, your SecuriRota email notifications will work</li>
                            </ul>
                        </div>";
                    } else {
                        echo "<div class='error'>
                            <h4>❌ Failed to Send Test Email</h4>
                            <p>The email could not be sent. This might be due to:</p>
                            <ul>
                                <li>Server configuration issues</li>
                                <li>SMTP authentication problems</li>
                                <li>Firewall blocking outbound connections</li>
                                <li>Email provider restrictions</li>
                            </ul>
                        </div>";
                    }
                    
                    // Option to test another email
                    echo "<div style='margin-top: 20px;'>
                        <form method='GET'>
                            <button type='submit' class='btn'>🔄 Test Another Email</button>
                        </form>
                    </div>";
                }
            }
            ?>

            <div class="tips">
                <h3>💡 Troubleshooting Tips</h3>
                <ul>
                    <li><strong>Connection Issues:</strong> Verify server can reach <?php echo SMTP_HOST; ?>:<?php echo SMTP_PORT; ?></li>
                    <li><strong>Authentication:</strong> Double-check username and password</li>
                    <li><strong>SSL/TLS:</strong> Ensure your server supports secure connections</li>
                    <li><strong>Firewall:</strong> Allow outbound SMTP traffic on port <?php echo SMTP_PORT; ?></li>
                    <li><strong>Provider Limits:</strong> Check if your email provider has sending limits</li>
                    <li><strong>DNS:</strong> Ensure proper DNS resolution for the SMTP host</li>
                </ul>
                
                <h4>🔧 Server Environment</h4>
                <div class="test-results">
                    <div class="detail-item">PHP Version: <?php echo phpversion(); ?></div>
                    <div class="detail-item">Server: <?php echo $_SERVER['SERVER_NAME'] ?? 'Unknown'; ?></div>
                    <div class="detail-item">Current Time: <?php echo date('Y-m-d H:i:s T'); ?></div>
                    <div class="detail-item">Mail Function: <?php echo function_exists('mail') ? '✅ Available' : '❌ Not Available'; ?></div>
                    <div class="detail-item">OpenSSL: <?php echo extension_loaded('openssl') ? '✅ Loaded' : '❌ Not Loaded'; ?></div>
                </div>
            </div>

            <div class="warning">
                <h4>⚠️ Security Warning</h4>
                <p><strong>Important:</strong> This test script contains sensitive email credentials.</p>
                <ul>
                    <li>Delete this file immediately after testing</li>
                    <li>Never commit credential files to version control</li>
                    <li>Use environment variables or secure config files in production</li>
                    <li>Restrict access to configuration files</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
