<?php
/**
 * Migration API - Create Default Company
 * Creates the default company for migration
 */
header('Content-Type: application/json');

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get company details
    $company_name = $input['company_name'] ?? 'Default Company';
    $company_slug = $input['company_slug'] ?? sanitizeSlug($company_name);
    $admin_email = $input['admin_email'] ?? '';
    $admin_username = $input['admin_username'] ?? 'admin';
    $admin_password = $input['admin_password'] ?? '';
    
    // Validate required fields
    if (empty($company_name) || empty($admin_email) || empty($admin_password)) {
        throw new Exception('Company name, admin email, and password are required');
    }
    
    // Check if companies table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'companies'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        throw new Exception('Companies table not found. Please run migration first.');
    }
    
    $pdo->beginTransaction();
    
    // Create the company
    $stmt = $pdo->prepare("
        INSERT INTO companies (name, slug, status, subscription_plan, created_at) 
        VALUES (?, ?, 'active', 'premium', NOW())
    ");
    $stmt->execute([$company_name, $company_slug]);
    $company_id = $pdo->lastInsertId();
    
    // Hash the admin password
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Create the admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (company_id, username, email, password, role, status, created_at) 
        VALUES (?, ?, ?, ?, 'admin', 'active', NOW())
    ");
    $stmt->execute([$company_id, $admin_username, $admin_email, $hashed_password]);
    $admin_user_id = $pdo->lastInsertId();
    
    // Create default branding settings
    $default_branding = [
        'primary_color' => '#007bff',
        'secondary_color' => '#6c757d',
        'accent_color' => '#28a745',
        'logo_url' => '',
        'features' => [
            'dark_mode' => true,
            'notifications' => true,
            'reports' => true,
            'bulk_operations' => true
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO company_settings (company_id, setting_key, setting_value, created_at) 
        VALUES (?, 'branding', ?, NOW())
    ");
    $stmt->execute([$company_id, json_encode($default_branding)]);
    
    // Log the activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (company_id, user_id, action, details, created_at) 
        VALUES (?, ?, 'company_created', ?, NOW())
    ");
    $stmt->execute([
        $company_id, 
        $admin_user_id, 
        json_encode([
            'company_name' => $company_name,
            'admin_email' => $admin_email,
            'migration_type' => 'single_to_multi_tenant'
        ])
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Default company created successfully',
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_slug' => $company_slug,
        'admin_user_id' => $admin_user_id,
        'admin_username' => $admin_username
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function sanitizeSlug($string) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}
?>
