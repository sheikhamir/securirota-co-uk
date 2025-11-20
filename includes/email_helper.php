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
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
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
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
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
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
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
