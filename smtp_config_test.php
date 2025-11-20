<?php
/**
 * SMTP Multi-Configuration Test
 * Tests multiple SMTP configurations to find one that works
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Test configurations to try
$smtp_configs = [
    'ionos_com_587' => [
        'name' => 'Ionos .com with TLS (Port 587)',
        'host' => 'smtp.ionos.com',
        'port' => 587,
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS,
        'user' => 'info@securirota.co.uk',
        'pass' => '(S3cur!R0t@123!)'
    ],
    'ionos_uk_587' => [
        'name' => 'Ionos .co.uk with TLS (Port 587)',
        'host' => 'smtp.ionos.co.uk',
        'port' => 587,
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS,
        'user' => 'info@securirota.co.uk',
        'pass' => '(S3cur!R0t@123!)'
    ],
    'ionos_com_465' => [
        'name' => 'Ionos .com with SSL (Port 465)',
        'host' => 'smtp.ionos.com',
        'port' => 465,
        'encryption' => PHPMailer::ENCRYPTION_SMTPS,
        'user' => 'info@securirota.co.uk',
        'pass' => '(S3cur!R0t@123!)'
    ],
    'ionos_uk_465' => [
        'name' => 'Ionos .co.uk with SSL (Port 465)',
        'host' => 'smtp.ionos.co.uk',
        'port' => 465,
        'encryption' => PHPMailer::ENCRYPTION_SMTPS,
        'user' => 'info@securirota.co.uk',
        'pass' => '(S3cur!R0t@123!)'
    ],
    'ionos_com_25' => [
        'name' => 'Ionos .com Port 25 (No encryption)',
        'host' => 'smtp.ionos.com',
        'port' => 25,
        'encryption' => false,
        'user' => 'info@securirota.co.uk',
        'pass' => '(S3cur!R0t@123!)'
    ],
    'basic_mail' => [
        'name' => 'Basic mail() function (for comparison)',
        'host' => null,
        'port' => null,
        'encryption' => null,
        'user' => 'info@securirota.co.uk',
        'pass' => null
    ]
];

function testSMTPConnection($config) {
    if ($config['host'] === null) {
        // Test basic mail() function
        $headers = [
            'From: ' . $config['user'],
            'Reply-To: ' . $config['user'],
            'X-Test: SMTP Multi-Config Test'
        ];
        
        $result = mail(
            'smamir3@gmail.com',
            'Test Subject - ' . date('H:i:s'),
            'Test message from basic mail() function',
            implode("\r\n", $headers)
        );
        
        return [
            'success' => $result,
            'error' => $result ? 'None' : 'mail() function failed',
            'debug' => 'Used basic PHP mail() function'
        ];
    }
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'error' => 'PHPMailer not available',
            'debug' => 'PHPMailer classes not found'
        ];
    }
    
    $mail = new PHPMailer(true);
    $debug_output = '';
    
    try {
        // Capture debug output
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) use (&$debug_output) {
            $debug_output .= "Level $level: " . trim($str) . "\n";
        };
        
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['user'];
        $mail->Password = $config['pass'];
        
        if ($config['encryption']) {
            $mail->SMTPSecure = $config['encryption'];
        }
        
        $mail->Port = $config['port'];
        $mail->Timeout = 10; // 10 second timeout
        
        // Set up email
        $mail->setFrom($config['user'], 'SecuriRota Test');
        $mail->addAddress('smamir3@gmail.com', 'Test User');
        
        $mail->isHTML(true);
        $mail->Subject = 'SMTP Test - ' . $config['name'] . ' - ' . date('H:i:s');
        $mail->Body = 'This is a test email from ' . $config['name'];
        
        // Try to send
        $result = $mail->send();
        
        return [
            'success' => true,
            'error' => 'None',
            'debug' => $debug_output
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => $debug_output
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMTP Multi-Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f7fa; }
        .container { max-width: 1000px; margin: 0 auto; }
        .config-test { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { border-left: 5px solid #28a745; background: #d4edda; }
        .error { border-left: 5px solid #dc3545; background: #f8d7da; }
        .testing { border-left: 5px solid #ffc107; background: #fff3cd; }
        .debug { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; margin: 10px 0; white-space: pre-wrap; }
        .config-details { background: #e9ecef; padding: 10px; border-radius: 4px; margin: 10px 0; }
        h1 { color: #2c3e50; text-align: center; }
        h3 { color: #495057; margin-top: 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SMTP Multi-Configuration Test</h1>
        <p><strong>Testing multiple SMTP configurations to find one that works on your server.</strong></p>
        
        <?php if (!isset($_POST['run_tests'])): ?>
        <div class="config-test">
            <h3>📋 Available Test Configurations</h3>
            <p>This will test the following SMTP configurations:</p>
            <ul>
                <?php foreach ($smtp_configs as $key => $config): ?>
                <li><strong><?php echo $config['name']; ?></strong> - 
                    <?php if ($config['host']): ?>
                        <?php echo $config['host']; ?>:<?php echo $config['port']; ?>
                        <?php if ($config['encryption']): ?>
                            (<?php echo $config['encryption'] === PHPMailer::ENCRYPTION_STARTTLS ? 'TLS' : 'SSL'; ?>)
                        <?php else: ?>
                            (No encryption)
                        <?php endif; ?>
                    <?php else: ?>
                        Basic PHP mail() function
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <form method="POST">
                <button type="submit" name="run_tests" class="btn">🚀 Run All Tests</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (isset($_POST['run_tests'])): ?>
        <h2>📊 Test Results</h2>
        
        <?php foreach ($smtp_configs as $key => $config): ?>
        <div class="config-test testing">
            <h3>Testing: <?php echo $config['name']; ?></h3>
            <div class="config-details">
                <?php if ($config['host']): ?>
                <strong>Host:</strong> <?php echo $config['host']; ?><br>
                <strong>Port:</strong> <?php echo $config['port']; ?><br>
                <strong>Encryption:</strong> <?php echo $config['encryption'] ? ($config['encryption'] === PHPMailer::ENCRYPTION_STARTTLS ? 'STARTTLS' : 'SMTPS') : 'None'; ?><br>
                <strong>User:</strong> <?php echo $config['user']; ?>
                <?php else: ?>
                <strong>Method:</strong> Basic PHP mail() function
                <?php endif; ?>
            </div>
            
            <?php
            echo "<p>🔄 Testing connection...</p>";
            flush();
            
            $result = testSMTPConnection($config);
            ?>
            
            <div class="config-test <?php echo $result['success'] ? 'success' : 'error'; ?>">
                <?php if ($result['success']): ?>
                    <h4 style="color: #28a745;">✅ SUCCESS!</h4>
                    <p><strong>This configuration works!</strong></p>
                    <?php if ($config['host']): ?>
                    <div style="background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0;">
                        <h5>📋 Working Configuration for config.php:</h5>
                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px;">define('SMTP_HOST', '<?php echo $config['host']; ?>');
define('SMTP_PORT', <?php echo $config['port']; ?>);
define('SMTP_USER', '<?php echo $config['user']; ?>');
define('SMTP_PASS', '<?php echo $config['pass']; ?>');

// In email_helper.php PHPMailer functions:
$mail->SMTPSecure = <?php echo $config['encryption'] ? 'PHPMailer::' . ($config['encryption'] === PHPMailer::ENCRYPTION_STARTTLS ? 'ENCRYPTION_STARTTLS' : 'ENCRYPTION_SMTPS') : 'false'; ?>;</pre>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h4 style="color: #dc3545;">❌ FAILED</h4>
                    <p><strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($result['debug'])): ?>
                <details>
                    <summary>🔍 Debug Output</summary>
                    <div class="debug"><?php echo htmlspecialchars($result['debug']); ?></div>
                </details>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="config-test">
            <h3>🎯 Next Steps</h3>
            <p>If any configuration shows <strong style="color: #28a745;">SUCCESS</strong>:</p>
            <ol>
                <li>Copy the working configuration to your <code>config/config.php</code> file</li>
                <li>Update the PHPMailer settings in <code>includes/email_helper.php</code></li>
                <li>Test PIN email generation in your application</li>
            </ol>
            
            <p>If all configurations fail:</p>
            <ul>
                <li>Your server may have firewall restrictions blocking SMTP</li>
                <li>Contact your hosting provider about SMTP/email sending</li>
                <li>Consider using a different email service (SendGrid, Mailgun, etc.)</li>
            </ul>
        </div>
        
        <form method="GET">
            <button type="submit" class="btn">🔄 Run Tests Again</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
