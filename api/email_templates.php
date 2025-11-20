<?php
/**
 * Email Templates API
 * Handles CRUD operations for email templates
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in first
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Only allow admin access
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required. Your role: ' . ($_SESSION['user_role'] ?? 'not set')]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['type'])) {
                // Get specific template
                $response = getTemplate($_GET['type']);
            } else {
                // Get all templates
                $response = getAllTemplates();
            }
            break;
            
        case 'POST':
            // Create or update template
            $input = json_decode(file_get_contents('php://input'), true);
            $response = saveTemplate($input);
            break;
            
        case 'PUT':
            // Update template
            $input = json_decode(file_get_contents('php://input'), true);
            $response = updateTemplate($input);
            break;
            
        case 'DELETE':
            // Reset template to default (we don't actually delete)
            if (isset($_GET['type'])) {
                $response = resetTemplate($_GET['type']);
            } else {
                $response['message'] = 'Template type required';
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

header('Content-Type: application/json');
echo json_encode($response);

/**
 * Get all email templates
 */
function getAllTemplates() {
    global $conn;
    
    try {
        $query = "SELECT template_type, template_name, subject, body, variables, is_active, 
                         created_at, updated_at 
                  FROM email_templates 
                  ORDER BY template_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON variables
        foreach ($templates as &$template) {
            $template['variables'] = json_decode($template['variables'], true) ?? [];
        }
        
        return [
            'success' => true,
            'data' => $templates,
            'message' => 'Templates retrieved successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error retrieving templates: ' . $e->getMessage()
        ];
    }
}

/**
 * Get specific email template
 */
function getTemplate($type) {
    global $conn;
    
    try {
        $query = "SELECT template_type, template_name, subject, body, variables, is_active, is_html,
                         created_at, updated_at 
                  FROM email_templates 
                  WHERE template_type = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$type]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template) {
            $template['variables'] = json_decode($template['variables'], true) ?? [];
            
            return [
                'success' => true,
                'data' => $template,
                'message' => 'Template retrieved successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Template not found'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error retrieving template: ' . $e->getMessage()
        ];
    }
}

/**
 * Save email template (create or update)
 */
function saveTemplate($data) {
    global $conn;
    
    if (!isset($data['template_type']) || !isset($data['subject']) || !isset($data['body'])) {
        return [
            'success' => false,
            'message' => 'Template type, subject, and body are required'
        ];
    }
    
    try {
        // Check if template exists
        $checkQuery = "SELECT id FROM email_templates WHERE template_type = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$data['template_type']]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            // Update existing template
            $query = "UPDATE email_templates 
                      SET subject = ?, body = ?, updated_by = ?, updated_at = NOW()
                      WHERE template_type = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $data['subject'],
                $data['body'],
                $_SESSION['user_id'],
                $data['template_type']
            ]);
            
            $message = 'Template updated successfully';
        } else {
            // Create new template
            $query = "INSERT INTO email_templates (template_type, template_name, subject, body, variables, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $variables = isset($data['variables']) ? json_encode($data['variables']) : '[]';
            $templateName = ucwords(str_replace('_', ' ', $data['template_type']));
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $data['template_type'],
                $templateName,
                $data['subject'],
                $data['body'],
                $variables,
                $_SESSION['user_id']
            ]);
            
            $message = 'Template created successfully';
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error saving template: ' . $e->getMessage()
        ];
    }
}

/**
 * Update email template
 */
function updateTemplate($data) {
    return saveTemplate($data); // Same logic as save
}

/**
 * Reset template to default values
 */
function resetTemplate($type) {
    global $conn;
    
    // Default templates
    $defaults = [
        'pin_generation' => [
            'subject' => 'Your Security PIN - {{company_name}}',
            'body' => "Dear {{officer_name}},\n\nYour security PIN has been generated for accessing the officer portal.\n\nPIN: {{pin_code}}\n\nPlease keep this PIN secure and do not share it with anyone. You can use this PIN to:\n- Access your shift schedules\n- Check in/out of shifts\n- View your profile information\n\nIf you have any questions or need assistance, please contact us.\n\nBest regards,\n{{company_name}} Team"
        ],
        'shift_reminder' => [
            'subject' => 'Shift Reminder - {{site_name}} on {{shift_date}}',
            'body' => "Dear {{officer_name}},\n\nThis is a reminder about your upcoming shift:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\nDuration: {{shift_duration}} hours\n\nPlease ensure you arrive on time and bring all necessary equipment.\n\nBest regards,\n{{company_name}} Team"
        ],
        // Add other defaults as needed
    ];
    
    if (!isset($defaults[$type])) {
        return [
            'success' => false,
            'message' => 'Default template not found for type: ' . $type
        ];
    }
    
    try {
        $query = "UPDATE email_templates 
                  SET subject = ?, body = ?, updated_by = ?, updated_at = NOW()
                  WHERE template_type = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $defaults[$type]['subject'],
            $defaults[$type]['body'],
            $_SESSION['user_id'],
            $type
        ]);
        
        return [
            'success' => true,
            'message' => 'Template reset to default successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error resetting template: ' . $e->getMessage()
        ];
    }
}

/**
 * Send test email
 */
if (isset($_POST['action']) && $_POST['action'] === 'test_email') {
    $templateType = $_POST['template_type'] ?? '';
    $testEmail = $_POST['test_email'] ?? '';
    
    if (empty($templateType) || empty($testEmail)) {
        echo json_encode(['success' => false, 'message' => 'Template type and email address are required']);
        exit();
    }
    
    try {
        // Get template
        $templateResponse = getTemplate($templateType);
        if (!$templateResponse['success']) {
            echo json_encode($templateResponse);
            exit();
        }
        
        $template = $templateResponse['data'];
        
        // Replace variables with sample data for testing
        $sampleData = [
            'name' => 'John Doe',
            'mobile' => 'Johndoe',
            'pin' => '677229',
            'site_name' => 'Downtown Mall',
            'shift_date' => date('Y-m-d'),
            'shift_start_time' => '09:00',
            'shift_end_time' => '17:00',
            'shift_time' => '09:00 - 17:00',
            'shift_duration' => '8 hours',
            'hourly_rate' => '25.00',
            'password' => 'TempPass123',
            'new_password' => 'NewPass456',
            'login_url' => 'https://rohab.ae/rota',
            'company_name' => 'SecuriRota'
        ];
        
        // Use the new branded email system
        require_once '../includes/email_helper.php';
        $result = sendBrandedEmail(
            $testEmail, 
            $sampleData['name'],
            $template['subject'], 
            $templateType, 
            $sampleData
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Test email sent successfully with branded template']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send test email']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error sending test email: ' . $e->getMessage()]);
    }
    exit();
}
?>
