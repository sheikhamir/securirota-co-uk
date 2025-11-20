<?php
/**
 * Super Admin API - Create Company via Wizard
 * Handles the complete company creation process from the wizard
 */
header('Content-Type: application/json');

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/helpers.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log("Company Wizard Input: " . print_r($input, true));

// Validate required fields
$required_fields = ['company_name', 'company_slug', 'admin_username', 'admin_email', 'admin_password'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        error_log("Missing required field: $field");
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit();
    }
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check if company slug already exists
    $stmt = $conn->prepare("SELECT id FROM companies WHERE slug = ?");
    $stmt->execute([$input['company_slug']]);
    if ($stmt->fetch()) {
        throw new Exception('Company slug already exists');
    }
    
    // Check if admin username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$input['admin_username']]);
    if ($stmt->fetch()) {
        throw new Exception('Admin username already exists');
    }
    
    // Check if admin email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$input['admin_email']]);
    if ($stmt->fetch()) {
        throw new Exception('Admin email already exists');
    }
    
    // Determine subscription status and expiry
    $subscription_status = isset($input['trial_period']) && $input['trial_period'] ? 'active' : 'active';
    $subscription_expires_at = isset($input['trial_period']) && $input['trial_period'] 
        ? date('Y-m-d H:i:s', strtotime('+30 days'))
        : date('Y-m-d H:i:s', strtotime('+1 year')); // Default 1 year subscription
    
    // Create company
    $stmt = $conn->prepare("
        INSERT INTO companies (
            name, slug, email, phone, address, 
            subscription_plan, subscription_status, subscription_expires_at,
            max_officers, max_sites, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([
        $input['company_name'],
        $input['company_slug'],
        $input['company_email'] ?? null,
        $input['company_phone'] ?? null,
        $input['company_address'] ?? null,
        $input['subscription_plan'] ?? 'professional',
        $subscription_status,
        $subscription_expires_at,
        $input['max_officers'] ?? 200,
        $input['max_sites'] ?? 50
    ]);
    
    $company_id = $conn->lastInsertId();
    
    // Create admin user for the company
    $password_hash = password_hash($input['admin_password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (
            username, email, password, role, status, 
            mobile_number, company_id, created_at
        ) VALUES (?, ?, ?, 'admin', 'active', ?, ?, NOW())
    ");
    
    $stmt->execute([
        $input['admin_username'],
        $input['admin_email'],
        $password_hash,
        $input['admin_mobile'] ?? null,
        $company_id
    ]);
    
    $admin_user_id = $conn->lastInsertId();
    
    // Create default roles for the company
    $default_roles = [
        ['name' => 'Security Officer', 'description' => 'Standard security officer role'],
        ['name' => 'Senior Officer', 'description' => 'Senior security officer with additional responsibilities'],
        ['name' => 'Supervisor', 'description' => 'Site supervisor role'],
        ['name' => 'Manager', 'description' => 'Site manager role']
    ];
    
    foreach ($default_roles as $role) {
        $stmt = $conn->prepare("
            INSERT INTO roles (name, description, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$role['name'], $role['description']]);
    }
    
    // Create welcome email template for the company
    $stmt = $conn->prepare("
        INSERT INTO email_templates (
            template_type, template_name, subject, body, 
            is_active, created_by, created_at
        ) VALUES (
            'welcome', 'Welcome to SecuriRota', 
            'Welcome to {{company_name}} - Your SecuriRota Account',
            'Dear {{admin_name}},\n\nWelcome to SecuriRota! Your company account has been successfully created.\n\nYou can now log in using:\nUsername: {{admin_username}}\nCompany: {{company_name}}\n\nGet started by adding your officers and sites.\n\nBest regards,\nSecuriRota Team',
            1, ?, NOW()
        )
    ");
    $stmt->execute([$admin_user_id]);
    
    // Log the activity
    require_once '../../includes/ActivityLogger.php';
    $logger = new ActivityLogger($conn);
    $logger->log(
        $_SESSION['user_id'],
        'create_company_wizard',
        'company',
        $company_id,
        "Created new company via wizard: {$input['company_name']} with admin user: {$input['admin_username']}",
        [
            'company_name' => $input['company_name'],
            'company_slug' => $input['company_slug'],
            'admin_username' => $input['admin_username'],
            'subscription_plan' => $input['subscription_plan'] ?? 'basic',
            'subscription_status' => $subscription_status,
            'subscription_expires_at' => $subscription_expires_at,
            'wizard_version' => '1.0'
        ]
    );
    
    // TODO: Send welcome email to admin
    // $this->sendWelcomeEmail($admin_user_id, $company_id, $input);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Company created successfully via wizard',
        'company_id' => $company_id,
        'admin_user_id' => $admin_user_id,
        'company_name' => $input['company_name'],
        'admin_username' => $input['admin_username'],
        'subscription_status' => $subscription_status,
        'subscription_expires_at' => $subscription_expires_at
    ]);

} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the error for debugging
    error_log("Company Wizard Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
