<?php
/**
 * Root User - Backup Management System
 * Create, manage, and restore database and file backups
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'Backup Management';
$page_description = 'Create, manage, and restore database and file backups';
$active_page = 'backup_management';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'System Management', 'url' => 'cache_management.php'],
    ['title' => 'Backup Management']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Handle backup actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create_database_backup':
                $result = createDatabaseBackup();
                $_SESSION['success'] = $result['message'];
                break;
                
            case 'create_full_backup':
                $result = createFullBackup();
                $_SESSION['success'] = $result['message'];
                break;
                
            case 'delete_backup':
                $filename = $_POST['filename'];
                if (deleteBackup($filename)) {
                    $_SESSION['success'] = "Backup '{$filename}' deleted successfully.";
                } else {
                    throw new Exception("Failed to delete backup '{$filename}'.");
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: backup_management.php');
    exit();
}

// Backup functions
function createDatabaseBackup() {
    $backup_dir = '../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "database_backup_{$timestamp}.sql";
    $filepath = $backup_dir . $filename;
    
    // Create a simple backup using PHP
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get all tables
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $sql_dump = "-- Database Backup Created: " . date('Y-m-d H:i:s') . "\n\n";
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $result = $conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $sql_dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql_dump .= $row['Create Table'] . ";\n\n";
            
            // Get table data
            $result = $conn->query("SELECT * FROM `{$table}`");
            if ($result->rowCount() > 0) {
                $sql_dump .= "INSERT INTO `{$table}` VALUES\n";
                $values = [];
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $escaped_values = array_map(function($value) use ($conn) {
                        return $value === null ? 'NULL' : $conn->quote($value);
                    }, $row);
                    $values[] = '(' . implode(', ', $escaped_values) . ')';
                }
                $sql_dump .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        if (file_put_contents($filepath, $sql_dump)) {
            return [
                'success' => true,
                'message' => "Database backup created successfully: {$filename}",
                'filename' => $filename
            ];
        } else {
            throw new Exception('Failed to write backup file');
        }
        
    } catch (Exception $e) {
        throw new Exception('Backup failed: ' . $e->getMessage());
    }
}

function createFullBackup() {
    $backup_dir = '../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "full_backup_{$timestamp}.tar.gz";
    $filepath = $backup_dir . $filename;
    
    // Create tar.gz backup of entire application
    $command = "cd .. && tar -czf {$filepath} --exclude='backups' --exclude='error_log' .";
    exec($command, $output, $return_code);
    
    if ($return_code === 0 && file_exists($filepath)) {
        return [
            'success' => true,
            'message' => "Full backup created successfully: {$filename}",
            'filename' => $filename
        ];
    } else {
        throw new Exception('Full backup failed');
    }
}

function deleteBackup($filename) {
    $backup_dir = '../backups/';
    $filepath = $backup_dir . $filename;
    
    if (file_exists($filepath) && strpos($filename, '..') === false) {
        return unlink($filepath);
    }
    
    return false;
}

// Get existing backups
$backup_dir = '../backups/';
$backups = [];

if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($backup_dir . $file)) {
            $filepath = $backup_dir . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filepath),
                'created' => filemtime($filepath),
                'type' => strpos($file, 'database_') === 0 ? 'Database' : 'Full'
            ];
        }
    }
    
    // Sort by creation time, newest first
    usort($backups, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Template configuration - no special features needed
// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-shield-alt text-warning me-2"></i>
                Backup Management
            </h1>
            <p class="mb-0 text-white-50">Create, manage, and restore database and file backups</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-crown-outline" onclick="createBackup('database')">
                    <i class="fas fa-database me-1"></i> DB Backup
                </button>
                <button type="button" class="btn btn-crown-outline" onclick="createBackup('full')">
                    <i class="fas fa-archive me-1"></i> Full Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Backup Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= count($backups) ?></div>
                    <div class="stats-label">Total Backups</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-archive"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        <?= count(array_filter($backups, function($b) { return $b['type'] === 'Database'; })) ?>
                    </div>
                    <div class="stats-label">Database Backups</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-database"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        <?= count(array_filter($backups, function($b) { return $b['type'] === 'Full'; })) ?>
                    </div>
                    <div class="stats-label">Full Backups</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        <?= formatBytes(array_sum(array_column($backups, 'size'))) ?>
                    </div>
                    <div class="stats-label">Total Size</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-hdd"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="crown-card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-bolt text-primary me-2"></i>
            Quick Backup Actions
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center p-3 border rounded">
                    <div class="me-3">
                        <i class="fas fa-database fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Database Backup</h6>
                        <p class="mb-2 text-muted small">Creates a SQL dump of the entire database</p>
                        <button type="button" class="btn btn-crown btn-sm" onclick="createBackup('database')">
                            <i class="fas fa-plus me-1"></i> Create Now
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center p-3 border rounded">
                    <div class="me-3">
                        <i class="fas fa-archive fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Full System Backup</h6>
                        <p class="mb-2 text-muted small">Archives all files and database</p>
                        <button type="button" class="btn btn-crown btn-sm" onclick="createBackup('full')">
                            <i class="fas fa-plus me-1"></i> Create Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Tip:</strong> Regular automated backups are recommended. Database backups are faster, 
            while full backups include all files but take longer to create.
        </div>
    </div>
</div>

<!-- Existing Backups -->
<div class="crown-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Existing Backups
        </h5>
        <div class="text-muted small">
            <i class="fas fa-folder me-1"></i>
            <?= count($backups) ?> backups stored
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($backups)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-archive fa-3x mb-3"></i>
                <h5>No Backups Found</h5>
                <p class="text-muted">Start by creating your first backup to protect your data.</p>
                <div class="btn-group">
                    <button type="button" class="btn btn-crown" onclick="createBackup('database')">
                        <i class="fas fa-database me-1"></i> Database Backup
                    </button>
                    <button type="button" class="btn btn-crown-outline" onclick="createBackup('full')">
                        <i class="fas fa-archive me-1"></i> Full Backup
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table crown-table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-<?= $backup['type'] === 'Database' ? 'database' : 'archive' ?> me-2 text-<?= $backup['type'] === 'Database' ? 'primary' : 'success' ?>"></i>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($backup['filename']) ?></div>
                                            <small class="text-muted">
                                                <?= date('M j, Y', $backup['created']) ?> at <?= date('H:i', $backup['created']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="crown-badge crown-badge-<?= $backup['type'] === 'Database' ? 'primary' : 'success' ?>">
                                        <?= $backup['type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= formatBytes($backup['size']) ?></div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><?= date('M j, Y', $backup['created']) ?></div>
                                        <div class="text-muted"><?= date('H:i:s', $backup['created']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../backups/<?= urlencode($backup['filename']) ?>" 
                                           class="btn btn-crown-outline btn-sm" 
                                           download
                                           data-bs-toggle="tooltip" 
                                           title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="deleteBackup('<?= htmlspecialchars($backup['filename']) ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Create backup function
function createBackup(type) {
    const messages = {
        'database': 'database backup',
        'full': 'full system backup'
    };
    
    const message = 'Are you sure you want to create a ' + messages[type] + '? This may take a few minutes.';
    
    RootCommon.confirmAction(message, () => {
        const action = type === 'database' ? 'create_database_backup' : 'create_full_backup';
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type='hidden' name='action' value='\${action}'>`;
        document.body.appendChild(form);
        
        // Show loading message
        RootCommon.showAlert('Creating backup... Please wait.', 'info');
        
        form.submit();
    });
}

// Delete backup function
function deleteBackup(filename) {
    const message = 'Are you sure you want to delete backup \"' + filename + '\"? This action cannot be undone.';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='delete_backup'>
            <input type='hidden' name='filename' value='\${filename}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });
}
";

include 'includes/footer.php';
?>