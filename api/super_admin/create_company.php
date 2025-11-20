<?php
/**
 * Super Admin API - Create New Company
 * Creates a new company with admin user and proper isolation
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

// Validate required fields
$required_fields = ['name', 'slug', 'admin_username', 'admin_email', 'admin_password'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
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
    $stmt->execute([$_POST['slug']]);
    if ($stmt->fetch()) {
        throw new Exception('Company slug already exists');
    }
    
    // Check if admin username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$_POST['admin_username']]);
    if ($stmt->fetch()) {
        throw new Exception('Admin username already exists');
    }
    
    // Check if admin email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_POST['admin_email']]);
    if ($stmt->fetch()) {
        throw new Exception('Admin email already exists');
    }
    
    // Create company
    $stmt = $conn->prepare("
        INSERT INTO companies (
            name, slug, email, phone, address, 
            subscription_plan, subscription_status, 
            max_officers, max_sites, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['slug'],
        $_POST['email'] ?? null,
        $_POST['phone'] ?? null,
        $_POST['address'] ?? null,
        $_POST['subscription_plan'] ?? 'basic',
        $_POST['max_officers'] ?? 50,
        $_POST['max_sites'] ?? 20
    ]);
    
    $company_id = $conn->lastInsertId();
    
    // Create admin user for the company
    $password_hash = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (
            username, email, password, role, status, 
            company_id, created_at
        ) VALUES (?, ?, ?, 'admin', 'active', ?, NOW())
    ");
    
    $stmt->execute([
        $_POST['admin_username'],
        $_POST['admin_email'],
        $password_hash,
        $company_id
    ]);
    
    $admin_user_id = $conn->lastInsertId();
    
    // Log the activity
    require_once '../../includes/ActivityLogger.php';
    $logger = new ActivityLogger($conn);
    $logger->log(
        $_SESSION['user_id'],
        'create_company',
        'company',
        $company_id,
        "Created new company: {$_POST['name']} with admin user: {$_POST['admin_username']}",
        [
            'company_name' => $_POST['name'],
            'company_slug' => $_POST['slug'],
            'admin_username' => $_POST['admin_username'],
            'subscription_plan' => $_POST['subscription_plan'] ?? 'basic'
        ]
    );
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Company created successfully',
        'company_id' => $company_id,
        'admin_user_id' => $admin_user_id
    ]);

} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
    $stmt = $conn->prepare("
        INSERT INTO companies (
            name, slug, email, phone, address, subscription_plan, 
            subscription_status, subscription_start_date, max_officers, max_sites, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'active', CURDATE(), ?, ?, 'active')
    ");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['slug'],
        $_POST['email'] ?? null,
        $_POST['phone'] ?? null,
        $_POST['address'] ?? null,
        $_POST['subscription_plan'] ?? 'basic',
        $_POST['max_officers'] ?? 50,
        $_POST['max_sites'] ?? 20
    ]);
    
    $company_id = $conn->lastInsertId();
    
    // Create admin user for the company
    $hashed_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, role, company_id, status) 
        VALUES (?, ?, ?, 'admin', ?, 'active')
    ");
    
    $stmt->execute([
        $_POST['admin_username'],
        $_POST['admin_email'],
        $hashed_password,
        $company_id
    ]);
    
    $admin_user_id = $conn->lastInsertId();
    
    // Log the activity
    require_once '../../includes/ActivityLogger.php';
    $logger = new ActivityLogger($conn);
    $logger->logSystemAction(
        $_SESSION['user_id'], 
        'company_created', 
        "Created new company: " . $_POST['name'], 
        [
            'company_id' => $company_id,
            'company_name' => $_POST['name'],
            'company_slug' => $_POST['slug'],
            'admin_user_id' => $admin_user_id,
            'admin_username' => $_POST['admin_username']
        ]
    );
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Company created successfully',
        'company_id' => $company_id,
        'admin_user_id' => $admin_user_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    error_log("Create Company Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
