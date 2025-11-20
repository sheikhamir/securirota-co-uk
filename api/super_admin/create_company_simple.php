<?php
/**
 * API endpoint to create a new company
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Validate required fields
    $required_fields = ['name', 'slug'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $subscription_plan = $_POST['subscription_plan'] ?? 'basic';
    $max_officers = (int)($_POST['max_officers'] ?? 50);
    $max_sites = (int)($_POST['max_sites'] ?? 20);
    
    // Validate slug uniqueness
    $stmt = $conn->prepare("SELECT id FROM companies WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->rowCount() > 0) {
        throw new Exception("Company slug already exists. Please choose a different one.");
    }
    
    // Validate email if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Insert company
    $stmt = $conn->prepare("
        INSERT INTO companies (
            name, slug, email, phone, address, 
            subscription_plan, subscription_status, 
            max_officers, max_sites, status, 
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, 'active', NOW(), NOW())
    ");
    
    $stmt->execute([
        $name, $slug, $email, $phone, $address,
        $subscription_plan, $max_officers, $max_sites
    ]);
    
    $company_id = $conn->lastInsertId();
    
    // Create default admin user if credentials provided
    if (!empty($_POST['admin_username']) && !empty($_POST['admin_email']) && !empty($_POST['admin_password'])) {
        $admin_username = trim($_POST['admin_username']);
        $admin_email = trim($_POST['admin_email']);
        $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$admin_username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists. Please choose a different one.");
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$admin_email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already exists. Please choose a different one.");
        }
        
        // Insert admin user
        $stmt = $conn->prepare("
            INSERT INTO users (
                company_id, username, email, password, 
                role, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        
        $stmt->execute([$company_id, $admin_username, $admin_email, $admin_password]);
    }
    
    // Log the activity
    $stmt = $conn->prepare("
        INSERT INTO activity_log (
            user_id, action, details, ip_address, user_agent, created_at
        ) VALUES (?, 'company_created', ?, ?, ?, NOW())
    ");
    
    $details = json_encode([
        'company_id' => $company_id,
        'company_name' => $name,
        'company_slug' => $slug,
        'subscription_plan' => $subscription_plan
    ]);
    
    $stmt->execute([
        $_SESSION['user_id'],
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Company created successfully',
        'company_id' => $company_id,
        'redirect' => '../../root/companies.php'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Create company error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
