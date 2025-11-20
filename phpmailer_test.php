<?php
/**
 * PHPMailer Test Script - Matches PIN Email System Configuration
 * This tests PHPMailer exactly as used in the PIN email functionality
 */

// Enable full error reporting and display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load configuration
require_once 'config/config.php';

// Try to load PHPMailer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$phpmailer_available = false;
$phpmailer_error = '';

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $phpmailer_available = true;
    echo "<p style='color: green;'>✅ PHPMailer classes loaded successfully</p>";
} else {
    echo "<p style='color: red;'>❌ PHPMailer classes not available</p>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer Test - SecuriRota PIN Email System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 30px; margin: 20px 0; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .config-item { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; }
        .form-group { margin: 20px 0; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group textarea { width: 100%; max-width: 400px; padding: 12px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 16px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #007bff; }
        .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .debug-output { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; }
        .test-results { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; border-radius: 4px; }
        @media (max-width: 600px) { .container { padding: 10px; } .card { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 PHPMailer Test - PIN Email System</h1>
            <p>This test uses PHPMailer exactly as your PIN email system does, with full error logging.</p>
            
            <h3>📋 Current Configuration (from config.php)</h3>
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

            <?php if (!$phpmailer_available): ?>
            <div class="error">
                <h4>❌ PHPMailer Not Available</h4>
                <p>PHPMailer is required for the PIN email system but is not available.</p>
                <?php if ($phpmailer_error): ?>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($phpmailer_error); ?></p>
                <?php endif; ?>
                <p><strong>Solutions:</strong></p>
                <ul>
                    <li>Run: <code>composer require phpmailer/phpmailer</code></li>
                    <li>Or download and include PHPMailer manually</li>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!isset($_POST['test_email']) && $phpmailer_available): ?>
            <div class="card">
                <h3>📧 Send PHPMailer Test Email</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="test_email">Test Email Address:</label>
                        <input type="email" id="test_email" name="test_email" required 
                               placeholder="Enter email address to receive test">
                    </div>
                    <div class="form-group">
                        <label for="debug_level">Debug Level:</label>
                        <select id="debug_level" name="debug_level" style="width: 200px; padding: 12px; border: 2px solid #e1e8ed; border-radius: 8px;">
                            <option value="0">0 - No debug output</option>
                            <option value="1" selected>1 - Client messages</option>
                            <option value="2">2 - Client and server messages</option>
                            <option value="3">3 - Client, server, and connection status</option>
                            <option value="4">4 - Low-level data output</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">🚀 Test PHPMailer Email</button>
                </form>
            </div>
            <?php endif; ?>

            <?php
            // Handle PHPMailer email test
            if (isset($_POST['test_email']) && $phpmailer_available) {
                $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
                $debug_level = (int)($_POST['debug_level'] ?? 1);
                
                if (!$test_email) {
                    echo "<div class='error'>❌ Invalid email address provided!</div>";
                } else {
                    echo "<h3>📤 Testing PHPMailer (PIN Email System Configuration)</h3>";
                    echo "<div class='info'>Sending test email to: <strong>$test_email</strong></div>";
                    echo "<div class='info'>Debug Level: <strong>$debug_level</strong></div>";
                    
                    // Test data (same as PIN email system)
                    $test_name = "Test User";
                    $test_mobile = "07700123456";
                    $test_pin = "123456";
                    $subject = 'PHPMailer Test - PIN Email System - ' . date('Y-m-d H:i:s');
                    
                    // Start capturing debug output
                    ob_start();
                    
                    $mail = new PHPMailer(true);
                    $success = false;
                    $error_message = '';
                    
                    try {
                        echo "<div class='step'><strong>Step 1:</strong> Configuring SMTP settings...</div>";
                        
                        // Server settings - EXACTLY as used in PIN email system
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USER;
                        $mail->Password   = SMTP_PASS;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // This is the key difference!
                        $mail->Port       = SMTP_PORT;
                        $mail->SMTPDebug  = $debug_level;
                        
                        // Enable verbose debug output
                        $mail->Debugoutput = function($str, $level) {
                            echo "<div style='margin: 5px 0; padding: 5px; background: #e9ecef; border-radius: 3px; font-size: 12px;'>";
                            echo "<strong>Debug Level $level:</strong> " . htmlspecialchars($str);
                            echo "</div>";
                        };
                        
                        echo "<div class='step'><strong>Step 2:</strong> Setting up recipients...</div>";
                        
                        // Recipients
                        $mail->setFrom(SMTP_USER, 'SecuriRota System');
                        $mail->addAddress($test_email, $test_name);
                        $mail->addReplyTo(SMTP_USER, 'SecuriRota Support');
                        
                        echo "<div class='step'><strong>Step 3:</strong> Creating email content...</div>";
                        
                        // Content - HTML email like PIN system
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        
                        $html_body = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <title>PHPMailer Test</title>
                        </head>
                        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <div style='background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                                    <h1>🔧 PHPMailer Test</h1>
                                    <p>PIN Email System Configuration Test</p>
                                </div>
                                
                                <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px;'>
                                    <h2>Hello " . htmlspecialchars($test_name) . ",</h2>
                                    
                                    <p>This is a test email using PHPMailer with the exact same configuration as your PIN email system.</p>
                                    
                                    <div style='background: #fff; border: 2px solid #28a745; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px;'>
                                        <h3>Test PIN Details</h3>
                                        <p><strong>Mobile Number:</strong> " . htmlspecialchars($test_mobile) . "</p>
                                        <p><strong>Test PIN:</strong></p>
                                        <div style='font-size: 32px; font-weight: bold; color: #28a745; letter-spacing: 5px;'>" . htmlspecialchars($test_pin) . "</div>
                                    </div>
                                    
                                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                                        <p><strong>Configuration Used:</strong></p>
                                        <ul>
                                            <li>SMTP Host: " . SMTP_HOST . "</li>
                                            <li>SMTP Port: " . SMTP_PORT . "</li>
                                            <li>SMTP User: " . SMTP_USER . "</li>
                                            <li>Encryption: SMTPS (SSL)</li>
                                            <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
                                        </ul>
                                    </div>
                                    
                                    <p><strong>✅ If you received this email:</strong></p>
                                    <ul>
                                        <li>PHPMailer is working correctly</li>
                                        <li>SMTP configuration is valid</li>
                                        <li>Your PIN email system should work</li>
                                    </ul>
                                </div>
                            </div>
                        </body>
                        </html>";
                        
                        $text_body = "PHPMailer Test - PIN Email System
                        
Hello $test_name,

This is a test email using PHPMailer with the same configuration as your PIN email system.

Test PIN Details:
Mobile Number: $test_mobile
Test PIN: $test_pin

Configuration Used:
- SMTP Host: " . SMTP_HOST . "
- SMTP Port: " . SMTP_PORT . "
- SMTP User: " . SMTP_USER . "
- Encryption: SMTPS (SSL)
- Sent at: " . date('Y-m-d H:i:s') . "

If you received this email:
✅ PHPMailer is working correctly
✅ SMTP configuration is valid
✅ Your PIN email system should work";
                        
                        $mail->Body = $html_body;
                        $mail->AltBody = $text_body;
                        
                        echo "<div class='step'><strong>Step 4:</strong> Attempting to send email...</div>";
                        echo "<div class='debug-output'>";
                        
                        $mail->send();
                        $success = true;
                        
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        echo "</div>";
                        $error_message = $e->getMessage();
                        $success = false;
                    }
                    
                    // Capture any additional output
                    $debug_output = ob_get_clean();
                    
                    if ($success) {
                        echo "<div class='success'>
                            <h4>✅ PHPMailer Test Successful!</h4>
                            <p>The email was sent successfully using PHPMailer with your PIN email system configuration.</p>
                            <p><strong>This means:</strong></p>
                            <ul>
                                <li>PHPMailer is working correctly</li>
                                <li>Your SMTP settings are valid</li>
                                <li>SSL encryption on port " . SMTP_PORT . " is working</li>
                                <li>Your PIN email system should work</li>
                            </ul>
                        </div>";
                    } else {
                        echo "<div class='error'>
                            <h4>❌ PHPMailer Test Failed</h4>
                            <p><strong>Error Message:</strong> " . htmlspecialchars($error_message) . "</p>
                            <p><strong>This explains why PIN emails aren't working!</strong></p>
                        </div>";
                    }
                    
                    if (!empty($debug_output)) {
                        echo "<h4>📋 Debug Output:</h4>";
                        echo "<div class='debug-output'>" . $debug_output . "</div>";
                    }
                    
                    // Show comparison with working test
                    echo "<div class='warning'>
                        <h4>🔍 Comparison Analysis</h4>
                        <p><strong>Your working simple test uses:</strong></p>
                        <ul>
                            <li>Basic mail() function</li>
                            <li>Simple SMTP configuration</li>
                            <li>Server handles SMTP details</li>
                        </ul>
                        <p><strong>PIN email system uses:</strong></p>
                        <ul>
                            <li>PHPMailer with SSL encryption</li>
                            <li>Port " . SMTP_PORT . " with SMTPS</li>
                            <li>Full SMTP authentication</li>
                        </ul>
                        " . ($success ? 
                            "<p style='color: green;'><strong>✅ Both methods work - PIN emails should be working!</strong></p>" : 
                            "<p style='color: red;'><strong>❌ PHPMailer configuration needs fixing for PIN emails to work.</strong></p>"
                        ) . "
                    </div>";
                    
                    echo "<div style='margin-top: 20px;'>
                        <form method='GET'>
                            <button type='submit' class='btn'>🔄 Test Another Email</button>
                        </form>
                    </div>";
                }
            }
            ?>

            <div class="info">
                <h4>💡 Understanding the Difference</h4>
                <p><strong>Simple Email Test:</strong> Uses basic PHP mail() function</p>
                <p><strong>PIN Email System:</strong> Uses PHPMailer with SSL authentication</p>
                <p>If this PHPMailer test fails but the simple test works, that's why PIN emails aren't being sent!</p>
            </div>
        </div>
    </div>
</body>
</html>
