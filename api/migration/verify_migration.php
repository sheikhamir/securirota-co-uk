<?php
/**
 * Migration API - Verify Migration
 * Verifies that migration completed successfully
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
    
    $company_id = $input['company_id'] ?? null;
    
    if (!$company_id) {
        throw new Exception('Company ID is required');
    }
    
    $verification_results = [];
    
    // Check company exists and is active
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        throw new Exception('Company not found');
    }
    
    $verification_results['company'] = [
        'status' => 'verified',
        'name' => $company['name'],
        'slug' => $company['slug'],
        'subscription_plan' => $company['subscription_plan']
    ];
    
    // Check users migration
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN company_id = ? THEN 1 END) as migrated_users,
            COUNT(CASE WHEN (company_id IS NULL OR company_id = 0) AND role != 'super_admin' THEN 1 END) as orphaned_users
        FROM users
    ");
    $stmt->execute([$company_id]);
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['users'] = [
        'status' => $user_stats['orphaned_users'] == 0 ? 'verified' : 'warning',
        'total' => $user_stats['total_users'],
        'migrated' => $user_stats['migrated_users'],
        'orphaned' => $user_stats['orphaned_users']
    ];
    
    // Check sites migration
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sites,
            COUNT(CASE WHEN company_id = ? THEN 1 END) as migrated_sites,
            COUNT(CASE WHEN company_id IS NULL OR company_id = 0 THEN 1 END) as orphaned_sites
        FROM sites
    ");
    $stmt->execute([$company_id]);
    $site_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['sites'] = [
        'status' => $site_stats['orphaned_sites'] == 0 ? 'verified' : 'warning',
        'total' => $site_stats['total_sites'],
        'migrated' => $site_stats['migrated_sites'],
        'orphaned' => $site_stats['orphaned_sites']
    ];
    
    // Check officers migration
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_officers,
            COUNT(CASE WHEN company_id = ? THEN 1 END) as migrated_officers,
            COUNT(CASE WHEN company_id IS NULL OR company_id = 0 THEN 1 END) as orphaned_officers
        FROM officers
    ");
    $stmt->execute([$company_id]);
    $officer_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['officers'] = [
        'status' => $officer_stats['orphaned_officers'] == 0 ? 'verified' : 'warning',
        'total' => $officer_stats['total_officers'],
        'migrated' => $officer_stats['migrated_officers'],
        'orphaned' => $officer_stats['orphaned_officers']
    ];
    
    // Check shifts migration
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_shifts,
            COUNT(CASE WHEN company_id = ? THEN 1 END) as migrated_shifts,
            COUNT(CASE WHEN company_id IS NULL OR company_id = 0 THEN 1 END) as orphaned_shifts
        FROM shifts
    ");
    $stmt->execute([$company_id]);
    $shift_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['shifts'] = [
        'status' => $shift_stats['orphaned_shifts'] == 0 ? 'verified' : 'warning',
        'total' => $shift_stats['total_shifts'],
        'migrated' => $shift_stats['migrated_shifts'],
        'orphaned' => $shift_stats['orphaned_shifts']
    ];
    
    // Check activity logs migration
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_activities,
            COUNT(CASE WHEN company_id = ? THEN 1 END) as migrated_activities,
            COUNT(CASE WHEN company_id IS NULL OR company_id = 0 THEN 1 END) as orphaned_activities
        FROM activity_log
    ");
    $stmt->execute([$company_id]);
    $activity_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['activity_logs'] = [
        'status' => $activity_stats['orphaned_activities'] == 0 ? 'verified' : 'warning',
        'total' => $activity_stats['total_activities'],
        'migrated' => $activity_stats['migrated_activities'],
        'orphaned' => $activity_stats['orphaned_activities']
    ];
    
    // Check email templates migration
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_templates,
            COUNT(CASE WHEN company_id = ? THEN 1 END) as migrated_templates,
            COUNT(CASE WHEN company_id IS NULL OR company_id = 0 THEN 1 END) as orphaned_templates
        FROM email_templates
    ");
    $stmt->execute([$company_id]);
    $template_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['email_templates'] = [
        'status' => $template_stats['orphaned_templates'] == 0 ? 'verified' : 'warning',
        'total' => $template_stats['total_templates'],
        'migrated' => $template_stats['migrated_templates'],
        'orphaned' => $template_stats['orphaned_templates']
    ];
    
    // Check branding settings
    $stmt = $pdo->prepare("SELECT * FROM company_settings WHERE company_id = ? AND setting_key = 'branding'");
    $stmt->execute([$company_id]);
    $branding = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $verification_results['branding'] = [
        'status' => $branding ? 'verified' : 'warning',
        'configured' => $branding ? true : false
    ];
    
    // Calculate overall status
    $all_verified = true;
    $has_warnings = false;
    
    foreach ($verification_results as $key => $result) {
        if ($key === 'company') continue; // Skip company check for overall status
        
        if ($result['status'] === 'warning') {
            $has_warnings = true;
        }
        if ($result['status'] !== 'verified') {
            $all_verified = false;
        }
    }
    
    $overall_status = $all_verified ? 'success' : ($has_warnings ? 'warning' : 'error');
    
    // Create migration summary
    $migration_summary = [
        'migration_date' => date('Y-m-d H:i:s'),
        'company_id' => $company_id,
        'company_name' => $company['name'],
        'overall_status' => $overall_status,
        'verification_results' => $verification_results
    ];
    
    // Log the migration completion
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (company_id, user_id, action, details, created_at) 
        VALUES (?, ?, 'migration_completed', ?, NOW())
    ");
    $stmt->execute([
        $company_id, 
        $_SESSION['user_id'] ?? null, 
        json_encode($migration_summary)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Migration verification completed',
        'overall_status' => $overall_status,
        'verification_results' => $verification_results,
        'summary' => $migration_summary
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
