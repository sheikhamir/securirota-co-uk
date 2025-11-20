<?php
/**
 * Migration API - Compatibility Check
 * Checks if the system is ready for migration
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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $checks = [];
    $all_passed = true;
    
    // PHP Version Check
    $php_version = PHP_VERSION;
    $checks['php_version'] = [
        'passed' => version_compare($php_version, '7.4.0', '>='),
        'message' => version_compare($php_version, '7.4.0', '>=') 
            ? "PHP $php_version (✓ Compatible)" 
            : "PHP $php_version (✗ Requires 7.4+)"
    ];
    
    // MySQL Version Check
    $mysql_version = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
    $checks['mysql_version'] = [
        'passed' => version_compare($mysql_version, '5.7.0', '>='),
        'message' => version_compare($mysql_version, '5.7.0', '>=') 
            ? "MySQL/MariaDB $mysql_version (✓ Compatible)" 
            : "MySQL/MariaDB $mysql_version (✗ Requires 5.7+)"
    ];
    
    // Backup Directory Check
    $backup_dir = dirname(dirname(__DIR__)) . '/backups';
    if (!is_dir($backup_dir)) {
        @mkdir($backup_dir, 0755, true);
    }
    $backup_writable = is_writable($backup_dir);
    $checks['backup_writable'] = [
        'passed' => $backup_writable,
        'message' => $backup_writable 
            ? 'Backup directory is writable (✓)' 
            : 'Backup directory is not writable (✗)'
    ];
    
    // Check for Existing Data
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM officers");
    $officer_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $has_data = $user_count > 0 || $officer_count > 0;
    $checks['existing_data'] = [
        'passed' => $has_data,
        'message' => $has_data 
            ? "Found $user_count users, $officer_count officers (✓ Ready to migrate)" 
            : 'No existing data found (✗ Nothing to migrate)'
    ];
    
    // Check if Companies Table Exists
    $stmt = $conn->query("SHOW TABLES LIKE 'companies'");
    $companies_exists = $stmt->rowCount() > 0;
    $checks['companies_table'] = [
        'passed' => $companies_exists,
        'message' => $companies_exists 
            ? 'Companies table exists (✓ Multi-tenant ready)' 
            : 'Companies table missing (✗ Needs creation)'
    ];
    
    // Overall status
    foreach ($checks as $check) {
        if (!$check['passed']) {
            $all_passed = false;
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'all_checks_passed' => $all_passed,
        'php_version' => $checks['php_version'],
        'mysql_version' => $checks['mysql_version'],
        'backup_writable' => $checks['backup_writable'],
        'existing_data' => $checks['existing_data'],
        'companies_table' => $checks['companies_table']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
