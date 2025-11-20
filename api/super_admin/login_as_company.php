<?php
/**
 * API endpoint to login as a company admin (Super Admin feature)
 */

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    header('Location: ../../dashboard.php');
    exit();
}

$company_id = (int)($_GET['company_id'] ?? 0);

if (!$company_id) {
    header('Location: ../../root/companies.php?error=invalid_company');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get company details
    $stmt = $conn->prepare("SELECT * FROM companies WHERE id = ? AND status != 'deleted'");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        header('Location: ../../root/companies.php?error=company_not_found');
        exit();
    }
    
    // Find a company admin user
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE company_id = ? AND role = 'admin' AND status = 'active' 
        ORDER BY created_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$company_id]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_user) {
        header('Location: ../../root/companies.php?error=no_admin_found');
        exit();
    }
    
    // Store current super admin session info
    $original_super_admin_id = $_SESSION['user_id'];
    $original_super_admin_role = $_SESSION['user_role'];
    
    // Log the impersonation activity
    $stmt = $conn->prepare("
        INSERT INTO activity_log (
            user_id, action, details, ip_address, user_agent, created_at
        ) VALUES (?, 'super_admin_impersonation', ?, ?, ?, NOW())
    ");
    
    $details = json_encode([
        'target_company_id' => $company_id,
        'target_company_name' => $company['name'],
        'target_user_id' => $admin_user['id'],
        'target_username' => $admin_user['username']
    ]);
    
    $stmt->execute([
        $original_super_admin_id,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Switch session to company admin
    $_SESSION['user_id'] = $admin_user['id'];
    $_SESSION['username'] = $admin_user['username'];
    $_SESSION['user_role'] = $admin_user['role'];
    $_SESSION['company_id'] = $company_id;
    $_SESSION['company_name'] = $company['name'];
    $_SESSION['company_slug'] = $company['slug'];
    
    // Store super admin info for switching back
    $_SESSION['original_super_admin_id'] = $original_super_admin_id;
    $_SESSION['original_super_admin_role'] = $original_super_admin_role;
    $_SESSION['is_impersonating'] = true;
    
    // Redirect to company dashboard
    header('Location: ../../dashboard.php?impersonating=1');
    exit();
    
} catch (Exception $e) {
    error_log("Login as company error: " . $e->getMessage());
    header('Location: ../../root/companies.php?error=login_failed');
    exit();
}
?>
