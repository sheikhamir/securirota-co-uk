<?php
/**
 * Email Helper Functions
 * Handles email sending functionality for the application
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer via Composer if available, or include manually
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // For systems without Composer, we'll use a simple mail() fallback
    // In production, you should use PHPMailer or similar
}

function configureMailerSmtp($mail) {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->Port = SMTP_PORT;

    $smtp_secure = defined('SMTP_SECURE') ? SMTP_SECURE : envValue('SMTP_SECURE', '');
    $smtp_secure = strtolower(trim((string)$smtp_secure));

    if ($smtp_secure === '') {
        $smtp_secure = ((int)SMTP_PORT === 465) ? 'ssl' : (((int)SMTP_PORT === 587) ? 'tls' : '');
    }

    if ($smtp_secure === 'ssl' || $smtp_secure === 'smtps') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtp_secure === 'tls' || $smtp_secure === 'starttls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    }
}

/**
 * Generate branded HTML email template
 */
function generateBrandedEmailTemplate($title, $content, $template_type = 'general') {
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6; 
            color: #333; 
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            text-align: center; 
            padding: 30px 20px;
        }
        .header h1 { 
            font-size: 28px; 
            font-weight: 600; 
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .header .subtitle { 
            font-size: 16px; 
            opacity: 0.9; 
            font-weight: 300;
        }
        .shield-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .content { 
            padding: 40px 30px; 
        }
        .greeting { 
            font-size: 20px; 
            font-weight: 600; 
            color: #2d3748; 
            margin-bottom: 20px;
        }
        .message { 
            font-size: 16px; 
            line-height: 1.6; 
            color: #4a5568; 
            margin-bottom: 30px;
        }
        .info-box { 
            background: #f7fafc; 
            border: 2px solid #48bb78; 
            border-radius: 8px; 
            padding: 25px; 
            margin: 25px 0; 
            text-align: center;
        }
        .info-box h3 { 
            color: #2d3748; 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 15px;
        }
        .info-item { 
            margin: 10px 0; 
            font-size: 16px;
        }
        .info-label { 
            font-weight: 600; 
            color: #2d3748;
        }
        .info-value { 
            color: #4a5568;
            margin-left: 5px;
        }
        .highlight-value { 
            font-size: 36px; 
            font-weight: 700; 
            color: #48bb78; 
            margin: 15px 0;
            font-family: "Courier New", monospace;
            letter-spacing: 2px;
        }
        .instructions { 
            background: #fff5b2; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 25px 0;
        }
        .instructions h4 { 
            color: #744210; 
            font-weight: 600; 
            margin-bottom: 15px;
            font-size: 16px;
        }
        .instructions ol { 
            padding-left: 20px; 
            color: #744210;
        }
        .instructions li { 
            margin: 8px 0; 
            font-size: 14px;
        }
        .login-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .footer { 
            background: #edf2f7; 
            padding: 20px; 
            text-align: center; 
            border-top: 1px solid #e2e8f0;
        }
        .footer p { 
            color: #718096; 
            font-size: 14px; 
            margin: 5px 0;
        }
        .security-notice {
            background: #fed7d7;
            border: 1px solid #fc8181;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #742a2a;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><span class="shield-icon">🛡️</span> SecuriRota System</h1>
            <div class="subtitle">' . htmlspecialchars($title) . '</div>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p><strong>SecuriRota Security Management</strong></p>
            <p>Professional Security Solutions</p>
            <p style="font-size: 12px; margin-top: 10px;">This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

/**
 * Generate PIN email content in the branded style
 */
function generatePINEmailContent($name, $mobile, $pin) {
    $content = '
        <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
        <div class="message">Your login PIN for the SecuriRota system has been generated successfully.</div>
        
        <div class="info-box">
            <h3>Your Login Details</h3>
            <div class="info-item">
                <span class="info-label">Mobile Number:</span>
                <span class="info-value">' . htmlspecialchars($mobile) . '</span>
            </div>
            <div style="margin: 20px 0;">
                <div class="info-label">Your PIN:</div>
                <div class="highlight-value">' . htmlspecialchars($pin) . '</div>
            </div>
        </div>
        
        <div class="instructions">
            <h4>📱 How to Login:</h4>
            <ol>
                <li>Go to <a href="http://securirota.co.uk/login.php" class="login-link">SecuriRota Login Page</a></li>
                <li>Enter your mobile number: <strong>' . htmlspecialchars($mobile) . '</strong></li>
                <li>Enter your PIN: <strong>' . htmlspecialchars($pin) . '</strong></li>
                <li>Click Login</li>
            </ol>
        </div>
        
        <div class="security-notice">
            <strong>🔒 Security Notice:</strong><br>
            Keep your PIN secure and do not share it with anyone. If you suspect your PIN has been compromised, please contact your supervisor immediately.
        </div>';
    
    return $content;
}

/**
 * Generate shift assignment email content
 */
function generateShiftAssignmentEmailContent($name, $site_name, $shift_date, $shift_start_time, $shift_end_time, $hourly_rate) {
    $content = '
        <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
        <div class="message">You have been assigned to a new shift. Please review the details below and confirm your availability.</div>
        
        <div class="info-box">
            <h3>📅 Shift Assignment Details</h3>
            <div class="info-item">
                <span class="info-label">Site:</span>
                <span class="info-value">' . htmlspecialchars($site_name) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date:</span>
                <span class="info-value">' . htmlspecialchars($shift_date) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Time:</span>
                <span class="info-value">' . htmlspecialchars($shift_start_time) . ' - ' . htmlspecialchars($shift_end_time) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Rate:</span>
                <span class="info-value">$' . htmlspecialchars($hourly_rate) . '/hour</span>
            </div>
        </div>
        
        <div class="instructions">
            <h4>📋 Next Steps:</h4>
            <ol>
                <li>Review the shift details carefully</li>
                <li>Confirm your availability as soon as possible</li>
                <li>Contact your supervisor if you have any questions</li>
                <li>Arrive at the site 15 minutes before your scheduled time</li>
            </ol>
        </div>
        
        <div class="message">Please contact us immediately if you cannot fulfill this assignment.</div>';
    
    return $content;
}

/**
 * Generate shift reminder email content
 */
function generateShiftReminderEmailContent($name, $site_name, $shift_date, $shift_time) {
    $content = '
        <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
        <div class="message">This is a friendly reminder about your upcoming shift.</div>
        
        <div class="info-box">
            <h3>⏰ Shift Reminder</h3>
            <div class="info-item">
                <span class="info-label">Site:</span>
                <span class="info-value">' . htmlspecialchars($site_name) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date:</span>
                <span class="info-value">' . htmlspecialchars($shift_date) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Time:</span>
                <span class="info-value">' . htmlspecialchars($shift_time) . '</span>
            </div>
        </div>
        
        <div class="instructions">
            <h4>📝 Preparation Checklist:</h4>
            <ol>
                <li>Ensure you have your uniform and ID ready</li>
                <li>Check the weather and dress appropriately</li>
                <li>Arrive 15 minutes early</li>
                <li>Bring any required equipment</li>
            </ol>
        </div>
        
        <div class="security-notice">
            <strong>⚠️ Important:</strong><br>
            If you cannot make this shift, please contact us immediately. No-shows may result in disciplinary action.
        </div>';
    
    return $content;
}

/**
 * Generate welcome officer email content
 */
function generateWelcomeOfficerEmailContent($name, $password) {
    $content = '
        <div class="greeting">Welcome ' . htmlspecialchars($name) . '!</div>
        <div class="message">We\'re excited to have you join the SecuriRota security team. Your officer account has been created successfully.</div>
        
        <div class="info-box">
            <h3>🔐 Your Account Details</h3>
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value">' . htmlspecialchars($name) . '</span>
            </div>
            <div style="margin: 20px 0;">
                <div class="info-label">Temporary Password:</div>
                <div class="highlight-value" style="font-size: 24px;">' . htmlspecialchars($password) . '</div>
            </div>
        </div>
        
        <div class="instructions">
            <h4>🚀 Getting Started:</h4>
            <ol>
                <li>Go to <a href="http://securirota.co.uk/login.php" class="login-link">SecuriRota Portal</a></li>
                <li>Log in with your credentials</li>
                <li>Change your password immediately for security</li>
                <li>Complete your profile setup</li>
                <li>Review your assigned shifts</li>
            </ol>
        </div>
        
        <div class="security-notice">
            <strong>🔒 Security First:</strong><br>
            Please change your temporary password on first login. Keep your login credentials secure and never share them with anyone.
        </div>';
    
    return $content;
}

/**
 * Generate dynamic branded email content from admin's template
 */
function generateDynamicBrandedContent($template_type, $subject, $body, $variables = []) {
    // Replace variables in the admin's content
    $processedSubject = $subject;
    $processedBody = $body;
    
    // Default sample data for missing variables
    $defaultSampleData = [
        '{{company_name}}' => 'SecuriRota',
        '{{officer_name}}' => 'Officer Name',
        '{{site_name}}' => 'Site Location',
        '{{shift_date}}' => date('Y-m-d'),
        '{{shift_start_time}}' => '09:00',
        '{{shift_end_time}}' => '17:00',
        '{{shift_time}}' => '09:00 - 17:00',
        '{{shift_duration}}' => '8 hours',
        '{{hourly_rate}}' => '25.00',
        '{{pin_code}}' => '123456',
        '{{mobile}}' => 'Mobile',
        '{{password}}' => 'TempPass123',
        '{{new_password}}' => 'NewPass456',
        '{{login_url}}' => 'http://securirota.co.uk/'
    ];
    
    // Merge provided variables with defaults
    $allVariables = array_merge($defaultSampleData, $variables);
    
    // Replace variables
    foreach ($allVariables as $variable => $value) {
        $processedSubject = str_replace($variable, $value, $processedSubject);
        $processedBody = str_replace($variable, $value, $processedBody);
    }
    
    // Parse the admin's content and make it look professional
    $content = formatAdminContentForBrandedEmail($processedBody, $template_type, $allVariables);
    
    return generateBrandedEmailTemplate($processedSubject, $content, $template_type);
}

/**
 * Format admin's content for branded email template
 */
function formatAdminContentForBrandedEmail($body, $template_type, $variables) {
    // Split content into paragraphs
    $paragraphs = explode("\n\n", trim($body));
    $formattedContent = '';
    
    // First paragraph is usually the greeting
    if (!empty($paragraphs)) {
        $firstParagraph = trim($paragraphs[0]);
        if (preg_match('/^(Dear|Hello|Hi)\s+(.+)/', $firstParagraph, $matches)) {
            $formattedContent .= '<div class="greeting">' . htmlspecialchars($firstParagraph) . '</div>';
            array_shift($paragraphs); // Remove the greeting from remaining paragraphs
        }
    }
    
    // Process remaining paragraphs
    $infoBoxContent = '';
    $instructionsContent = '';
    $regularContent = '';
    
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if (empty($paragraph)) continue;
        
        // Check if this looks like structured information (contains colons)
        if (preg_match_all('/^(.+?):\s*(.+)$/m', $paragraph, $matches, PREG_SET_ORDER)) {
            // This looks like structured data - put in info box
            $infoBoxContent .= '<div class="info-box"><h3>';
            
            // Add appropriate icon based on template type
            switch($template_type) {
                case 'pin_generation':
                    $infoBoxContent .= '🔐 Your Login Details';
                    break;
                case 'shift_assigned':
                    $infoBoxContent .= '📅 Shift Assignment Details';
                    break;
                case 'shift_reminder':
                    $infoBoxContent .= '⏰ Shift Reminder';
                    break;
                case 'welcome_officer':
                    $infoBoxContent .= '👋 Account Information';
                    break;
                default:
                    $infoBoxContent .= '📋 Details';
            }
            $infoBoxContent .= '</h3>';
            
            foreach ($matches as $match) {
                $label = trim($match[1]);
                $value = trim($match[2]);
                
                // Special formatting for certain fields
                if (stripos($label, 'pin') !== false || stripos($label, 'password') !== false) {
                    $infoBoxContent .= '<div style="margin: 20px 0;"><div class="info-label">' . htmlspecialchars($label) . ':</div><div class="highlight-value">' . htmlspecialchars($value) . '</div></div>';
                } else {
                    $infoBoxContent .= '<div class="info-item"><span class="info-label">' . htmlspecialchars($label) . ':</span><span class="info-value">' . htmlspecialchars($value) . '</span></div>';
                }
            }
            $infoBoxContent .= '</div>';
            
        } elseif (preg_match('/^\d+\.\s+/', $paragraph) || strpos($paragraph, "\n1.") !== false || strpos($paragraph, "\n-") !== false) {
            // This looks like instructions - put in instructions box
            $instructionsContent .= '<div class="instructions"><h4>📋 Instructions:</h4>';
            
            // Convert to HTML list
            $lines = explode("\n", $paragraph);
            $instructionsContent .= '<ol>';
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^\d+\.\s*(.+)/', $line, $match)) {
                    $instructionsContent .= '<li>' . htmlspecialchars($match[1]) . '</li>';
                } elseif (preg_match('/^[-*]\s*(.+)/', $line, $match)) {
                    $instructionsContent .= '<li>' . htmlspecialchars($match[1]) . '</li>';
                } elseif (!empty($line) && !preg_match('/^\d+\./', $line)) {
                    $instructionsContent .= '<li>' . htmlspecialchars($line) . '</li>';
                }
            }
            $instructionsContent .= '</ol></div>';
            
        } else {
            // Regular paragraph content
            $regularContent .= '<div class="message">' . nl2br(htmlspecialchars($paragraph)) . '</div>';
        }
    }
    
    // Combine all content in logical order
    $finalContent = $formattedContent . $regularContent . $infoBoxContent . $instructionsContent;
    
    // If no structured content was found, just format as regular message
    if (empty($infoBoxContent) && empty($instructionsContent) && empty($formattedContent)) {
        $finalContent = '<div class="message">' . nl2br(htmlspecialchars($body)) . '</div>';
    }
    
    return $finalContent;
}

/**
 * Send PIN notification email to officer
 * @param string $email Officer email address
 * @param string $name Officer name  
 * @param string $mobile Mobile number
 * @param string $pin Generated PIN
 * @return bool Success status
 */
function sendPINEmail($email, $name, $mobile, $pin) {
    try {
        // Check if PHPMailer is available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return sendPINEmailViaPHPMailer($email, $name, str_replace(' ', '', $mobile), $pin);
        } else {
            return sendPINEmailViaMail($email, $name, str_replace(' ', '', $mobile), $pin);
        }
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send PIN email using PHPMailer (preferred method)
 */
function sendPINEmailViaPHPMailer($email, $name, $mobile, $pin) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        configureMailerSmtp($mail);
        
        // Recipients
        $mail->setFrom(SMTP_USER, 'SecuriRota System');
        $mail->addAddress($email, $name);
        $mail->addReplyTo(SMTP_USER, 'SecuriRota Support');
        
        // Content - Use new branded template
        $mail->isHTML(true);
        $mail->Subject = 'Your Login PIN has been generated';
        
        // Generate branded HTML content
        $content = generatePINEmailContent($name, str_replace(' ', '', $mobile), $pin);
        $mail->Body = generateBrandedEmailTemplate('Your Login PIN has been generated', $content, 'pin_generation');
        $mail->AltBody = getPINEmailText($name, str_replace(' ', '', $mobile), $pin);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send PIN email using basic mail() function (fallback)
 */
function sendPINEmailViaMail($email, $name, $mobile, $pin) {
    $subject = 'Your SecuriRota Login PIN';
    $message = getPINEmailText($name, str_replace(' ', '', $mobile), $pin);
    
    $headers = [
        'From: SecuriRota System <' . SMTP_USER . '>',
        'Reply-To: ' . SMTP_USER,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    return mail($email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate HTML email content for PIN notification
 */
function getPINEmailHTML($name, $mobile, $pin) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .pin-box { background: #fff; border: 2px solid #28a745; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; }
            .pin-number { font-size: 32px; font-weight: bold; color: #28a745; letter-spacing: 5px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛡️ SecuriRota System</h1>
                <p>Your Login PIN has been generated</p>
            </div>
            
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                
                <p>Your login PIN for the SecuriRota system has been generated successfully.</p>
                
                <div class='pin-box'>
                    <h3>Your Login Details</h3>
                    <p><strong>Mobile Number:</strong> " . htmlspecialchars($mobile) . "</p>
                    <p><strong>Your PIN:</strong></p>
                    <div class='pin-number'>" . htmlspecialchars($pin) . "</div>
                </div>
                
                <h3>How to Login:</h3>
                <ol>
                    <li>Go to <a href='" . BASE_URL . "login.php'>SecuriRota Login Page</a></li>
                    <li>Enter your mobile number: <strong>" . htmlspecialchars($mobile) . "</strong></li>
                    <li>Enter your PIN: <strong>" . htmlspecialchars($pin) . "</strong></li>
                    <li>Click Login</li>
                </ol>
                
                <div class='warning'>
                    <strong>⚠️ Security Notice:</strong>
                    <ul>
                        <li>Keep your PIN confidential</li>
                        <li>Do not share it with anyone</li>
                        <li>Contact admin if you suspect unauthorized access</li>
                    </ul>
                </div>
                
                <p>If you have any issues accessing the system, please contact your administrator.</p>
                
                <hr style='margin: 30px 0;'>
                <p><small>This is an automated message from SecuriRota System. Please do not reply to this email.</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Generate plain text email content for PIN notification
 */
function getPINEmailText($name, $mobile, $pin) {
    return "
SecuriRota System - Login PIN Generated
=======================================

Hello " . $name . ",

Your login PIN for the SecuriRota system has been generated successfully.

LOGIN DETAILS:
Mobile Number: " . $mobile . "
Your PIN: " . $pin . "

HOW TO LOGIN:
1. Go to " . BASE_URL . "login.php
2. Enter your mobile number: " . $mobile . "
3. Enter your PIN: " . $pin . "
4. Click Login

SECURITY NOTICE:
- Keep your PIN confidential
- Do not share it with anyone  
- Contact admin if you suspect unauthorized access

If you have any issues accessing the system, please contact your administrator.

---
This is an automated message from SecuriRota System.
    ";
}

/**
 * Send welcome email to new officer
 * @param string $email Officer email address
 * @param string $name Officer name
 * @param string $mobile Mobile number  
 * @param string $pin Generated PIN
 * @return bool Success status
 */
function sendWelcomeEmail($email, $name, $mobile, $pin) {
    try {
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return sendWelcomeEmailViaPHPMailer($email, $name, str_replace(' ', '', $mobile), $pin);
        } else {
            return sendWelcomeEmailViaMail($email, $name, str_replace(' ', '', $mobile), $pin);
        }
    } catch (Exception $e) {
        error_log("Welcome email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send welcome email using PHPMailer
 */
function sendWelcomeEmailViaPHPMailer($email, $name, $mobile, $pin) {
    $mail = new PHPMailer(true);
    
    try {
        configureMailerSmtp($mail);
        
        $mail->setFrom(SMTP_USER, 'SecuriRota System');
        $mail->addAddress($email, $name);
        $mail->addReplyTo(SMTP_USER, 'SecuriRota Support');
        
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to SecuriRota System';
        $mail->Body = getWelcomeEmailHTML($name, str_replace(' ', '', $mobile), $pin);
        $mail->AltBody = getWelcomeEmailText($name, str_replace(' ', '', $mobile), $pin);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send welcome email using basic mail() function
 */
function sendWelcomeEmailViaMail($email, $name, $mobile, $pin) {
    $subject = 'Welcome to SecuriRota System';
    $message = getWelcomeEmailText($name, $mobile, $pin);
    
    $headers = [
        'From: SecuriRota System <' . SMTP_USER . '>',
        'Reply-To: ' . SMTP_USER,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    return mail($email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate HTML welcome email content
 */
function getWelcomeEmailHTML($name, $mobile, $pin) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .pin-box { background: #fff; border: 2px solid #667eea; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; }
            .pin-number { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
            .features { background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to SecuriRota!</h1>
                <p>Your account has been created successfully</p>
            </div>
            
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                
                <p>Welcome to the SecuriRota Security Management System! Your officer account has been created and you can now access the system.</p>
                
                <div class='pin-box'>
                    <h3>Your Login Credentials</h3>
                    <p><strong>Mobile Number:</strong> " . htmlspecialchars($mobile) . "</p>
                    <p><strong>Your PIN:</strong></p>
                    <div class='pin-number'>" . htmlspecialchars($pin) . "</div>
                </div>
                
                <div class='features'>
                    <h3>What you can do in SecuriRota:</h3>
                    <ul>
                        <li>✅ View your upcoming shifts and assignments</li>
                        <li>✅ Accept or decline shift invitations</li>
                        <li>✅ Check in/out of your shifts</li>
                        <li>✅ Track your earnings and payments</li>
                        <li>✅ Update your profile and documents</li>
                        <li>✅ View site instructions and requirements</li>
                    </ul>
                </div>
                
                <h3>Getting Started:</h3>
                <ol>
                    <li>Click here to login: <a href='" . BASE_URL . "login.php' style='color: #667eea; font-weight: bold;'>SecuriRota Login</a></li>
                    <li>Enter your mobile number: <strong>" . htmlspecialchars($mobile) . "</strong></li>
                    <li>Enter your PIN: <strong>" . htmlspecialchars($pin) . "</strong></li>
                    <li>Complete your profile and upload required documents</li>
                </ol>
                
                <p style='margin-top: 30px;'>If you need help or have questions, don't hesitate to contact your administrator.</p>
                
                <p><strong>Welcome aboard!</strong><br>The SecuriRota Team</p>
                
                <hr style='margin: 30px 0;'>
                <p><small>This is an automated welcome message from SecuriRota System.</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Generate plain text welcome email content
 */
function getWelcomeEmailText($name, $mobile, $pin) {
    return "
Welcome to SecuriRota System!
============================

Hello " . $name . ",

Welcome to the SecuriRota Security Management System! Your officer account has been created and you can now access the system.

YOUR LOGIN CREDENTIALS:
Mobile Number: " . $mobile . "
Your PIN: " . $pin . "

WHAT YOU CAN DO IN SECURIROTA:
- View your upcoming shifts and assignments
- Accept or decline shift invitations  
- Check in/out of your shifts
- Track your earnings and payments
- Update your profile and documents
- View site instructions and requirements

GETTING STARTED:
1. Go to " . BASE_URL . "login.php
2. Enter your mobile number: " . $mobile . "
3. Enter your PIN: " . $pin . "
4. Complete your profile and upload required documents

If you need help or have questions, don't hesitate to contact your administrator.

Welcome aboard!
The SecuriRota Team

---
This is an automated welcome message from SecuriRota System.
    ";
}

/**
 * Generic email sending function with branded templates using admin's editable content
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param string $subject Email subject (ignored, taken from template)
 * @param string $template_type Template type (pin_generation, shift_assigned, etc.)
 * @param array $variables Associative array of variables to replace
 * @return bool Success status
 */
function sendBrandedEmail($to_email, $to_name, $subject, $template_type, $variables = []) {
    global $pdo;
    
    try {
        // Get admin template from database
        $stmt = $pdo->prepare("SELECT subject, body FROM email_templates WHERE template_type = ?");
        $stmt->execute([$template_type]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            error_log("Email template not found: " . $template_type);
            return false;
        }
        
        // Replace variables in subject and body
        $admin_subject = $template['subject'];
        $admin_body = $template['body'];
        
        foreach ($variables as $key => $value) {
            $admin_subject = str_replace("{{$key}}", $value, $admin_subject);
            $admin_body = str_replace("{{$key}}", $value, $admin_body);
        }
        
        // Add admin template data to variables for the email handler
        $variables['admin_subject'] = $admin_subject;
        $variables['admin_body'] = $admin_body;
        
        // Send using the admin template with branded styling
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return sendBrandedEmailViaPHPMailer($to_email, $to_name, $admin_subject, $template_type, $variables);
        } else {
            return sendBrandedEmailViaMail($to_email, $to_name, $admin_subject, $template_type, $variables);
        }
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send branded email via PHPMailer
 */
function sendBrandedEmailViaPHPMailer($to_email, $to_name, $subject, $template_type, $variables) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        configureMailerSmtp($mail);
        
        // Recipients
        $mail->setFrom(SMTP_USER, 'SecuriRota System');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_USER, 'SecuriRota Support');
        
        // If admin template content is provided, use it
        if (isset($variables['admin_subject']) && isset($variables['admin_body'])) {
            // Use admin's template content with branded styling
            $mail->isHTML(true);
            $mail->Subject = $variables['admin_subject'];
            $mail->Body = generateDynamicBrandedContent($template_type, $variables['admin_subject'], $variables['admin_body'], $variables);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $variables['admin_body']));
        } else {
            // Fallback to hardcoded content (for backward compatibility)
            $content = '';
            switch ($template_type) {
                case 'pin_generation':
                    $content = generatePINEmailContent(
                        $variables['name'] ?? $to_name,
                        $variables['mobile'] ?? '',
                        $variables['pin'] ?? ''
                    );
                    break;
                    
                case 'shift_assigned':
                    $content = generateShiftAssignmentEmailContent(
                        $variables['name'] ?? $to_name,
                        $variables['site_name'] ?? '',
                        $variables['shift_date'] ?? '',
                        $variables['shift_start_time'] ?? '',
                        $variables['shift_end_time'] ?? '',
                        $variables['hourly_rate'] ?? ''
                    );
                    break;
                    
                case 'shift_reminder':
                    $content = generateShiftReminderEmailContent(
                        $variables['name'] ?? $to_name,
                        $variables['site_name'] ?? '',
                        $variables['shift_date'] ?? '',
                        $variables['shift_time'] ?? ''
                    );
                    break;
                    
                case 'welcome_officer':
                    $content = generateWelcomeOfficerEmailContent(
                        $variables['name'] ?? $to_name,
                        $variables['password'] ?? ''
                    );
                    break;
                    
                default:
                    // Generic template for other types
                    $body = $variables['body'] ?? 'Thank you for using SecuriRota.';
                    $content = '<div class="message">' . nl2br(htmlspecialchars($body)) . '</div>';
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = generateBrandedEmailTemplate($subject, $content, $template_type);
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $content));
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function shiftEmailBase64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function shiftEmailBase64UrlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function createShiftEmailActionToken($shift_id, $officer_id, $action, $expires_in_seconds = 604800) {
    $payload = [
        'shift_id' => (int)$shift_id,
        'officer_id' => (int)$officer_id,
        'action' => $action,
        'expires' => time() + $expires_in_seconds
    ];
    
    $encoded_payload = shiftEmailBase64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', $encoded_payload, ENCRYPTION_KEY);
    
    return $encoded_payload . '.' . $signature;
}

function verifyShiftEmailActionToken($token, $expected_action = null) {
    if (!$token || strpos($token, '.') === false) {
        return false;
    }
    
    [$encoded_payload, $signature] = explode('.', $token, 2);
    $expected_signature = hash_hmac('sha256', $encoded_payload, ENCRYPTION_KEY);
    
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    $payload = json_decode(shiftEmailBase64UrlDecode($encoded_payload), true);
    if (!$payload || empty($payload['shift_id']) || empty($payload['officer_id']) || empty($payload['action']) || empty($payload['expires'])) {
        return false;
    }
    
    if ($payload['expires'] < time()) {
        return false;
    }
    
    if ($expected_action && $payload['action'] !== $expected_action) {
        return false;
    }
    
    return $payload;
}

function getShiftEmailDetails($conn, $shift_id, $officer_id = null) {
    $sql = "
        SELECT 
            s.*,
            si.site_name,
            si.address AS site_address,
            c.company_name AS client_name,
            r.name AS role_name,
            o.first_name,
            o.last_name,
            o.email AS officer_email,
            u.email AS user_email,
            co.name AS company_name
        FROM shifts s
        JOIN sites si ON s.site_id = si.id
        LEFT JOIN clients c ON si.client_id = c.id
        LEFT JOIN roles r ON s.role_id = r.id
        LEFT JOIN officers o ON s.officer_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN companies co ON s.company_id = co.id
        WHERE s.id = ?
    ";
    
    $params = [$shift_id];
    if ($officer_id !== null) {
        $sql .= " AND s.officer_id = ?";
        $params[] = $officer_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOfficerEmailDetails($conn, $officer_id) {
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.first_name,
            o.last_name,
            o.email AS officer_email,
            u.email AS user_email
        FROM officers o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$officer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function formatShiftDateForEmail($date) {
    return $date ? date('D, M j, Y', strtotime($date)) : '';
}

function formatShiftTimeForEmail($time) {
    return $time ? date('H:i', strtotime($time)) : '';
}

function sendShiftEmailMessage($email, $name, $subject, $html_body, $text_body) {
    if (empty($email)) {
        return false;
    }
    
    try {
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer(true);
            configureMailerSmtp($mail);
            
            $mail->setFrom(SMTP_USER, 'SecuriRota System');
            $mail->addAddress($email, $name);
            $mail->addReplyTo(SMTP_USER, 'SecuriRota Support');
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->AltBody = $text_body;
            
            $mail->send();
            return true;
        }
        
        $headers = [
            'From: SecuriRota System <' . SMTP_USER . '>',
            'Reply-To: ' . SMTP_USER,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        return mail($email, $subject, $text_body, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Shift email sending failed: " . $e->getMessage());
        return false;
    }
}

function getShiftEmailRecipient($conn, $shift_id) {
    $shift = getShiftEmailDetails($conn, $shift_id);
    if (!$shift) {
        return null;
    }
    
    return [
        'email' => $shift['user_email'] ?: $shift['officer_email'],
        'name' => trim(($shift['first_name'] ?? '') . ' ' . ($shift['last_name'] ?? '')),
        'officer_id' => $shift['officer_id'] ?? null
    ];
}

function getOfficerEmailRecipient($conn, $officer_id) {
    $officer = getOfficerEmailDetails($conn, $officer_id);
    if (!$officer) {
        return null;
    }
    
    return [
        'email' => $officer['user_email'] ?: $officer['officer_email'],
        'name' => trim(($officer['first_name'] ?? '') . ' ' . ($officer['last_name'] ?? '')),
        'officer_id' => $officer_id
    ];
}

function logShiftEmailAttempt($logger, $user_id, $shift_id, $email_type, $recipient, $success, $details = []) {
    $recipient_email = is_array($recipient) ? ($recipient['email'] ?? '') : (string)$recipient;
    $status = $success ? 'sent' : 'failed';
    $description = "Shift {$email_type} email {$status}" . ($recipient_email ? " to {$recipient_email}" : '');
    
    $metadata = array_merge([
        'email_type' => $email_type,
        'recipient' => $recipient,
        'success' => (bool)$success,
        'logged_at' => date('c')
    ], $details);
    
    try {
        if ($logger && method_exists($logger, 'log')) {
            $logger->log($user_id, 'update_shift', 'shift', $shift_id, $description, array_merge($metadata, [
                'email_notification' => true
            ]));
        }
    } catch (Exception $e) {
        error_log("Shift email audit logging failed for shift {$shift_id}: " . $e->getMessage());
    }
    
    error_log($description);
}

function buildShiftAssignmentEmailContent($shift, $accept_url, $decline_url) {
    $site_address = trim($shift['site_address'] ?? '');
    $role = $shift['role_name'] ?? $shift['role'] ?? 'Security Officer';
    $rate = isset($shift['officer_rate']) && $shift['officer_rate'] !== null ? number_format((float)$shift['officer_rate'], 2) : '0.00';
    
    return '
        <div class="greeting">Hello ' . htmlspecialchars(trim(($shift['first_name'] ?? '') . ' ' . ($shift['last_name'] ?? ''))) . ',</div>
        <div class="message">You have been assigned to a shift. Please review the details and confirm or decline your availability.</div>
        
        <div class="info-box">
            <h3>Shift Assignment Details</h3>
            <div class="info-item"><span class="info-label">Site:</span><span class="info-value">' . htmlspecialchars($shift['site_name'] ?? '') . '</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">' . htmlspecialchars($site_address ?: 'Address not provided') . '</span></div>
            <div class="info-item"><span class="info-label">Date:</span><span class="info-value">' . htmlspecialchars(formatShiftDateForEmail($shift['shift_date'] ?? '')) . '</span></div>
            <div class="info-item"><span class="info-label">Time:</span><span class="info-value">' . htmlspecialchars(formatShiftTimeForEmail($shift['start_time'] ?? '') . ' - ' . formatShiftTimeForEmail($shift['end_time'] ?? '')) . '</span></div>
            <div class="info-item"><span class="info-label">Role:</span><span class="info-value">' . htmlspecialchars($role) . '</span></div>
            <div class="info-item"><span class="info-label">Rate:</span><span class="info-value">£' . htmlspecialchars($rate) . '/hour</span></div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . htmlspecialchars($accept_url) . '" style="display: inline-block; background: #198754; color: #ffffff; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin: 5px;">Confirm Shift</a>
            <a href="' . htmlspecialchars($decline_url) . '" style="display: inline-block; background: #dc3545; color: #ffffff; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin: 5px;">Decline Shift</a>
        </div>
        
        <div class="message">If the buttons do not work, log in to the officer portal and respond from your unconfirmed shifts.</div>';
}

function buildShiftAssignmentTextContent($shift, $accept_url, $decline_url) {
    $name = trim(($shift['first_name'] ?? '') . ' ' . ($shift['last_name'] ?? ''));
    $role = $shift['role_name'] ?? $shift['role'] ?? 'Security Officer';
    $rate = isset($shift['officer_rate']) && $shift['officer_rate'] !== null ? number_format((float)$shift['officer_rate'], 2) : '0.00';
    
    return "Hello {$name},\n\n" .
        "You have been assigned to a shift.\n\n" .
        "Site: " . ($shift['site_name'] ?? '') . "\n" .
        "Address: " . (($shift['site_address'] ?? '') ?: 'Address not provided') . "\n" .
        "Date: " . formatShiftDateForEmail($shift['shift_date'] ?? '') . "\n" .
        "Time: " . formatShiftTimeForEmail($shift['start_time'] ?? '') . " - " . formatShiftTimeForEmail($shift['end_time'] ?? '') . "\n" .
        "Role: {$role}\n" .
        "Rate: £{$rate}/hour\n\n" .
        "Confirm shift: {$accept_url}\n" .
        "Decline shift: {$decline_url}\n\n" .
        "You can also respond from the officer portal.";
}

function sendShiftAssignmentEmail($conn, $shift_id) {
    $shift = getShiftEmailDetails($conn, $shift_id);
    if (!$shift || empty($shift['officer_id'])) {
        return false;
    }
    
    $email = $shift['user_email'] ?: $shift['officer_email'];
    if (empty($email)) {
        error_log("Shift assignment email skipped: no email address for officer {$shift['officer_id']}");
        return false;
    }
    
    $name = trim(($shift['first_name'] ?? '') . ' ' . ($shift['last_name'] ?? ''));
    $accept_token = createShiftEmailActionToken($shift_id, $shift['officer_id'], 'accept');
    $decline_token = createShiftEmailActionToken($shift_id, $shift['officer_id'], 'decline');
    $accept_url = BASE_URL . 'api/shift_email_action.php?action=accept&token=' . urlencode($accept_token);
    $decline_url = BASE_URL . 'api/shift_email_action.php?action=decline&token=' . urlencode($decline_token);
    $subject = 'New Shift Assignment - ' . ($shift['site_name'] ?? 'SecuriRota');
    $content = buildShiftAssignmentEmailContent($shift, $accept_url, $decline_url);
    $html = generateBrandedEmailTemplate($subject, $content, 'shift_assigned');
    $text = buildShiftAssignmentTextContent($shift, $accept_url, $decline_url);
    
    return sendShiftEmailMessage($email, $name, $subject, $html, $text);
}

function sendShiftRemovedEmail($conn, $shift_id, $old_officer_id, $reason = 'This shift has been removed from your schedule.') {
    $shift = getShiftEmailDetails($conn, $shift_id);
    $officer = getOfficerEmailDetails($conn, $old_officer_id);
    
    if (!$shift || !$officer) {
        return false;
    }
    
    $email = $officer['user_email'] ?: $officer['officer_email'];
    if (empty($email)) {
        error_log("Shift removal email skipped: no email address for officer {$old_officer_id}");
        return false;
    }
    
    $name = trim(($officer['first_name'] ?? '') . ' ' . ($officer['last_name'] ?? ''));
    $subject = 'Shift Removed - ' . ($shift['site_name'] ?? 'SecuriRota');
    $content = '
        <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
        <div class="message">' . htmlspecialchars($reason) . '</div>
        <div class="info-box">
            <h3>Removed Shift Details</h3>
            <div class="info-item"><span class="info-label">Site:</span><span class="info-value">' . htmlspecialchars($shift['site_name'] ?? '') . '</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">' . htmlspecialchars(($shift['site_address'] ?? '') ?: 'Address not provided') . '</span></div>
            <div class="info-item"><span class="info-label">Date:</span><span class="info-value">' . htmlspecialchars(formatShiftDateForEmail($shift['shift_date'] ?? '')) . '</span></div>
            <div class="info-item"><span class="info-label">Time:</span><span class="info-value">' . htmlspecialchars(formatShiftTimeForEmail($shift['start_time'] ?? '') . ' - ' . formatShiftTimeForEmail($shift['end_time'] ?? '')) . '</span></div>
        </div>
        <div class="message">Please check the officer portal for your latest schedule.</div>';
    
    $html = generateBrandedEmailTemplate($subject, $content, 'shift_removed');
    $text = "Hello {$name},\n\n{$reason}\n\n" .
        "Site: " . ($shift['site_name'] ?? '') . "\n" .
        "Address: " . (($shift['site_address'] ?? '') ?: 'Address not provided') . "\n" .
        "Date: " . formatShiftDateForEmail($shift['shift_date'] ?? '') . "\n" .
        "Time: " . formatShiftTimeForEmail($shift['start_time'] ?? '') . " - " . formatShiftTimeForEmail($shift['end_time'] ?? '') . "\n\n" .
        "Please check the officer portal for your latest schedule.";
    
    return sendShiftEmailMessage($email, $name, $subject, $html, $text);
}

function getShiftChangeLabel($conn, $table, $id, $label_column) {
    if (!$id) {
        return 'Not set';
    }
    
    try {
        $allowed = [
            'sites' => ['site_name'],
            'roles' => ['name']
        ];
        
        if (!isset($allowed[$table]) || !in_array($label_column, $allowed[$table], true)) {
            return (string)$id;
        }
        
        $stmt = $conn->prepare("SELECT {$label_column} FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row[$label_column] ?? (string)$id;
    } catch (Exception $e) {
        return (string)$id;
    }
}

function buildShiftChangeList($conn, $old_shift, $new_shift) {
    $changes = [];
    
    if (!$old_shift || !$new_shift) {
        return $changes;
    }
    
    if ((string)($old_shift['site_id'] ?? '') !== (string)($new_shift['site_id'] ?? '')) {
        $changes[] = [
            'label' => 'Site',
            'from' => getShiftChangeLabel($conn, 'sites', $old_shift['site_id'] ?? null, 'site_name'),
            'to' => $new_shift['site_name'] ?? getShiftChangeLabel($conn, 'sites', $new_shift['site_id'] ?? null, 'site_name')
        ];
    }
    
    if ((string)($old_shift['shift_date'] ?? '') !== (string)($new_shift['shift_date'] ?? '')) {
        $changes[] = [
            'label' => 'Date',
            'from' => formatShiftDateForEmail($old_shift['shift_date'] ?? ''),
            'to' => formatShiftDateForEmail($new_shift['shift_date'] ?? '')
        ];
    }
    
    if ((string)($old_shift['start_time'] ?? '') !== (string)($new_shift['start_time'] ?? '')) {
        $changes[] = [
            'label' => 'Start time',
            'from' => formatShiftTimeForEmail($old_shift['start_time'] ?? ''),
            'to' => formatShiftTimeForEmail($new_shift['start_time'] ?? '')
        ];
    }
    
    if ((string)($old_shift['end_time'] ?? '') !== (string)($new_shift['end_time'] ?? '')) {
        $changes[] = [
            'label' => 'End time',
            'from' => formatShiftTimeForEmail($old_shift['end_time'] ?? ''),
            'to' => formatShiftTimeForEmail($new_shift['end_time'] ?? '')
        ];
    }
    
    if ((string)($old_shift['role_id'] ?? '') !== (string)($new_shift['role_id'] ?? '')) {
        $changes[] = [
            'label' => 'Role',
            'from' => getShiftChangeLabel($conn, 'roles', $old_shift['role_id'] ?? null, 'name'),
            'to' => $new_shift['role_name'] ?? getShiftChangeLabel($conn, 'roles', $new_shift['role_id'] ?? null, 'name')
        ];
    }
    
    if ((string)($old_shift['officer_rate'] ?? '') !== (string)($new_shift['officer_rate'] ?? '')) {
        $changes[] = [
            'label' => 'Rate',
            'from' => isset($old_shift['officer_rate']) ? '£' . number_format((float)$old_shift['officer_rate'], 2) . '/hour' : 'Not set',
            'to' => isset($new_shift['officer_rate']) ? '£' . number_format((float)$new_shift['officer_rate'], 2) . '/hour' : 'Not set'
        ];
    }
    
    if ((string)($old_shift['status'] ?? '') !== (string)($new_shift['status'] ?? '')) {
        $changes[] = [
            'label' => 'Status',
            'from' => ucfirst($old_shift['status'] ?? 'Not set'),
            'to' => ucfirst($new_shift['status'] ?? 'Not set')
        ];
    }
    
    return $changes;
}

function sendShiftChangedEmail($conn, $shift_id, $old_shift_data, $reason = '') {
    $shift = getShiftEmailDetails($conn, $shift_id);
    
    if (!$shift || empty($shift['officer_id'])) {
        return false;
    }
    
    $email = $shift['user_email'] ?: $shift['officer_email'];
    if (empty($email)) {
        error_log("Shift change email skipped: no email address for officer {$shift['officer_id']}");
        return false;
    }
    
    $changes = buildShiftChangeList($conn, $old_shift_data, $shift);
    if (empty($changes) && !$reason) {
        return false;
    }
    
    $name = trim(($shift['first_name'] ?? '') . ' ' . ($shift['last_name'] ?? ''));
    $subject = 'Shift Updated - ' . ($shift['site_name'] ?? 'SecuriRota');
    $change_html = '';
    $change_text = '';
    
    foreach ($changes as $change) {
        $change_html .= '<div class="info-item"><span class="info-label">' . htmlspecialchars($change['label']) . ':</span><span class="info-value">' . htmlspecialchars($change['from']) . ' to ' . htmlspecialchars($change['to']) . '</span></div>';
        $change_text .= $change['label'] . ': ' . $change['from'] . ' to ' . $change['to'] . "\n";
    }
    
    $content = '
        <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
        <div class="message">A shift on your schedule has been updated. Please review the change details below.</div>
        ' . ($reason ? '<div class="message"><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</div>' : '') . '
        <div class="info-box">
            <h3>What Changed</h3>
            ' . ($change_html ?: '<div class="info-item">Shift details were updated.</div>') . '
        </div>
        <div class="info-box">
            <h3>Current Shift Details</h3>
            <div class="info-item"><span class="info-label">Site:</span><span class="info-value">' . htmlspecialchars($shift['site_name'] ?? '') . '</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">' . htmlspecialchars(($shift['site_address'] ?? '') ?: 'Address not provided') . '</span></div>
            <div class="info-item"><span class="info-label">Date:</span><span class="info-value">' . htmlspecialchars(formatShiftDateForEmail($shift['shift_date'] ?? '')) . '</span></div>
            <div class="info-item"><span class="info-label">Time:</span><span class="info-value">' . htmlspecialchars(formatShiftTimeForEmail($shift['start_time'] ?? '') . ' - ' . formatShiftTimeForEmail($shift['end_time'] ?? '')) . '</span></div>
            <div class="info-item"><span class="info-label">Status:</span><span class="info-value">' . htmlspecialchars(ucfirst($shift['status'] ?? '')) . '</span></div>
        </div>
        <div class="message">Please check the officer portal for your latest schedule.</div>';
    
    $html = generateBrandedEmailTemplate($subject, $content, 'shift_updated');
    $text = "Hello {$name},\n\nA shift on your schedule has been updated.\n\n" .
        ($reason ? "Reason: {$reason}\n\n" : '') .
        "What changed:\n" . ($change_text ?: "Shift details were updated.\n") . "\n" .
        "Current shift:\n" .
        "Site: " . ($shift['site_name'] ?? '') . "\n" .
        "Address: " . (($shift['site_address'] ?? '') ?: 'Address not provided') . "\n" .
        "Date: " . formatShiftDateForEmail($shift['shift_date'] ?? '') . "\n" .
        "Time: " . formatShiftTimeForEmail($shift['start_time'] ?? '') . " - " . formatShiftTimeForEmail($shift['end_time'] ?? '') . "\n" .
        "Status: " . ucfirst($shift['status'] ?? '') . "\n\n" .
        "Please check the officer portal for your latest schedule.";
    
    return sendShiftEmailMessage($email, $name, $subject, $html, $text);
}

function sendShiftCancelledEmail($conn, $shift_id, $officer_id, $cancellation_reason = '') {
    $shift = getShiftEmailDetails($conn, $shift_id);
    $officer = getOfficerEmailDetails($conn, $officer_id);
    
    if (!$shift || !$officer) {
        return false;
    }
    
    $email = $officer['user_email'] ?: $officer['officer_email'];
    if (empty($email)) {
        error_log("Shift cancellation email skipped: no email address for officer {$officer_id}");
        return false;
    }
    
    $name = trim(($officer['first_name'] ?? '') . ' ' . ($officer['last_name'] ?? ''));
    $subject = 'Shift Cancelled - ' . ($shift['site_name'] ?? 'SecuriRota');
    $content = '
        <div class="greeting">Hello ' . htmlspecialchars($name) . ',</div>
        <div class="message">A shift on your schedule has been cancelled.</div>
        ' . ($cancellation_reason ? '<div class="message"><strong>Reason:</strong> ' . htmlspecialchars($cancellation_reason) . '</div>' : '') . '
        <div class="info-box">
            <h3>Cancelled Shift Details</h3>
            <div class="info-item"><span class="info-label">Site:</span><span class="info-value">' . htmlspecialchars($shift['site_name'] ?? '') . '</span></div>
            <div class="info-item"><span class="info-label">Address:</span><span class="info-value">' . htmlspecialchars(($shift['site_address'] ?? '') ?: 'Address not provided') . '</span></div>
            <div class="info-item"><span class="info-label">Date:</span><span class="info-value">' . htmlspecialchars(formatShiftDateForEmail($shift['shift_date'] ?? '')) . '</span></div>
            <div class="info-item"><span class="info-label">Time:</span><span class="info-value">' . htmlspecialchars(formatShiftTimeForEmail($shift['start_time'] ?? '') . ' - ' . formatShiftTimeForEmail($shift['end_time'] ?? '')) . '</span></div>
        </div>
        <div class="message">Please check the officer portal for your latest schedule.</div>';
    
    $html = generateBrandedEmailTemplate($subject, $content, 'shift_cancelled');
    $text = "Hello {$name},\n\nA shift on your schedule has been cancelled.\n\n" .
        ($cancellation_reason ? "Reason: {$cancellation_reason}\n\n" : '') .
        "Site: " . ($shift['site_name'] ?? '') . "\n" .
        "Address: " . (($shift['site_address'] ?? '') ?: 'Address not provided') . "\n" .
        "Date: " . formatShiftDateForEmail($shift['shift_date'] ?? '') . "\n" .
        "Time: " . formatShiftTimeForEmail($shift['start_time'] ?? '') . " - " . formatShiftTimeForEmail($shift['end_time'] ?? '') . "\n\n" .
        "Please check the officer portal for your latest schedule.";
    
    return sendShiftEmailMessage($email, $name, $subject, $html, $text);
}

/**
 * Test email configuration
 * @return bool Success status
 */
function testEmailConfig() {
    try {
        return sendPINEmail(
            SMTP_USER, 
            'Test User', 
            '07700000000', 
            '123456'
        );
    } catch (Exception $e) {
        error_log("Email test failed: " . $e->getMessage());
        return false;
    }
}
?>
