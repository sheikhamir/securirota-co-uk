<?php
/**
 * Migration API - Create Backup
 * Creates database and file backups before migration
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
    $backup_location = $input['backup_location'] ?? '../backups/migration_' . date('Y-m-d_H-i-s');
    $backup_database = $input['backup_database'] ?? true;
    $backup_files = $input['backup_files'] ?? true;
    
    // Create backup directory
    $backup_dir = dirname(dirname(__DIR__)) . '/' . $backup_location;
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $results = [];
    
    // Database Backup
    if ($backup_database) {
        $db_backup_file = $backup_dir . '/database_backup.sql';
        
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            escapeshellarg($db_backup_file)
        );
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($db_backup_file)) {
            $results['database_backup'] = [
                'success' => true,
                'file' => $db_backup_file,
                'size' => filesize($db_backup_file)
            ];
        } else {
            throw new Exception('Database backup failed');
        }
    }
    
    // Files Backup
    if ($backup_files) {
        $files_backup_file = $backup_dir . '/files_backup.tar.gz';
        $source_dir = dirname(dirname(__DIR__));
        
        // Exclude backup directory itself and vendor directory
        $command = sprintf(
            'tar -czf %s -C %s --exclude=backups --exclude=vendor --exclude=.git .',
            escapeshellarg($files_backup_file),
            escapeshellarg($source_dir)
        );
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($files_backup_file)) {
            $results['files_backup'] = [
                'success' => true,
                'file' => $files_backup_file,
                'size' => filesize($files_backup_file)
            ];
        } else {
            throw new Exception('Files backup failed');
        }
    }
    
    // Create backup info file
    $info = [
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['username'],
        'migration_type' => 'single_to_multi_tenant',
        'php_version' => PHP_VERSION,
        'database_version' => null,
        'results' => $results
    ];
    
    file_put_contents($backup_dir . '/backup_info.json', json_encode($info, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup created successfully',
        'backup_location' => $backup_location,
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
