<?php
/**
 * Root User - System Resources Monitoring
 * Monitor server resources, database performance, and system health
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'System Resources';
$page_description = 'Monitor server resources, database performance, and system health';
$active_page = 'system_resources';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'System Management', 'url' => 'cache_management.php'],
    ['title' => 'System Resources']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Get system information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Database statistics
    $db_stats = [];
    
    // Get database size
    $stmt = $conn->prepare("
        SELECT 
            table_schema AS 'database_name',
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
        FROM information_schema.tables 
        WHERE table_schema = ?
        GROUP BY table_schema
    ");
    $stmt->execute([DB_NAME]);
    $db_size = $stmt->fetch(PDO::FETCH_ASSOC);
    $db_stats['size_mb'] = $db_size['size_mb'] ?? 0;
    
    // Get table information
    $stmt = $conn->prepare("
        SELECT 
            table_name,
            table_rows,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
        FROM information_schema.tables 
        WHERE table_schema = ?
        ORDER BY (data_length + index_length) DESC
        LIMIT 10
    ");
    $stmt->execute([DB_NAME]);
    $db_stats['tables'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system-wide counts
    $system_counts = [];
    
    $tables_to_count = [
        'companies' => 'Total Companies',
        'users' => 'Total Users', 
        'officers' => 'Total Officers',
        'sites' => 'Total Sites',
        'clients' => 'Total Clients',
        'shifts' => 'Total Shifts',
        'activity_log' => 'Activity Logs'
    ];
    
    foreach ($tables_to_count as $table => $label) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM {$table}");
            $system_counts[$table] = [
                'label' => $label,
                'count' => $stmt->fetchColumn()
            ];
        } catch (Exception $e) {
            $system_counts[$table] = [
                'label' => $label,
                'count' => 'N/A'
            ];
        }
    }
    
    // Get server information
    $server_info = [
        'php_version' => phpversion(),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'mysql_version' => $conn->query('SELECT VERSION()')->fetchColumn()
    ];
    
    // Get disk usage (if possible)
    $disk_info = [];
    if (function_exists('disk_free_space')) {
        $disk_info['free'] = disk_free_space('/');
        $disk_info['total'] = disk_total_space('/');
        $disk_info['used'] = $disk_info['total'] - $disk_info['free'];
        $disk_info['percent_used'] = round(($disk_info['used'] / $disk_info['total']) * 100, 2);
    }
    
    // Get memory usage
    $memory_info = [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ];
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading system resources: " . $e->getMessage();
}

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Template configuration
$enable_auto_refresh = true;
$auto_refresh_interval = 30; // 30 seconds

// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-server text-warning me-2"></i>
                System Resources
            </h1>
            <p class="mb-0 text-white-50">Monitor server resources, database performance, and system health</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- System Overview Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $db_stats['size_mb'] ?> MB</div>
                    <div class="stats-label">Database Size</div>
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
                    <div class="stats-number"><?= formatBytes($memory_info['current']) ?></div>
                    <div class="stats-label">Memory Usage</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-memory"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $server_info['php_version'] ?></div>
                    <div class="stats-label">PHP Version</div>
                </div>
                <div class="stats-icon">
                    <i class="fab fa-php"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <?php if (!empty($disk_info)): ?>
                        <div class="stats-number"><?= $disk_info['percent_used'] ?>%</div>
                        <div class="stats-label">Disk Usage</div>
                    <?php else: ?>
                        <div class="stats-number">N/A</div>
                        <div class="stats-label">Disk Usage</div>
                    <?php endif; ?>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-hdd"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Tabs -->
<ul class="nav nav-tabs crown-tabs mb-4" id="resourceTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab">
            <i class="fas fa-database me-2"></i>Database
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="server-tab" data-bs-toggle="tab" data-bs-target="#server" type="button" role="tab">
            <i class="fas fa-server me-2"></i>Server Info
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
            <i class="fas fa-chart-bar me-2"></i>System Stats
        </button>
    </li>
</ul>

<div class="tab-content" id="resourceTabsContent">
    <!-- Database Tab -->
    <div class="tab-pane fade show active" id="database" role="tabpanel">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="crown-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table text-primary me-2"></i>
                            Largest Tables
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm crown-table">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th>Rows</th>
                                        <th>Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($db_stats['tables'] as $table): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($table['table_name']) ?></td>
                                            <td><?= number_format($table['table_rows']) ?></td>
                                            <td><?= $table['size_mb'] ?> MB</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="crown-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Database Info
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Database Name</div>
                                    <div class="info-value"><?= DB_NAME ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">MySQL Version</div>
                                    <div class="info-value"><?= $server_info['mysql_version'] ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Total Size</div>
                                    <div class="info-value"><?= $db_stats['size_mb'] ?> MB</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Tables</div>
                                    <div class="info-value"><?= count($db_stats['tables']) ?>+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Server Info Tab -->
    <div class="tab-pane fade" id="server" role="tabpanel">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="crown-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog text-primary me-2"></i>
                            PHP Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">PHP Version</div>
                                    <div class="info-value"><?= $server_info['php_version'] ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Memory Limit</div>
                                    <div class="info-value"><?= $server_info['memory_limit'] ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Max Execution</div>
                                    <div class="info-value"><?= $server_info['max_execution_time'] ?>s</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Upload Max</div>
                                    <div class="info-value"><?= $server_info['upload_max_filesize'] ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Post Max Size</div>
                                    <div class="info-value"><?= $server_info['post_max_size'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="crown-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-memory text-primary me-2"></i>
                            Memory Usage
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Current Usage</div>
                                    <div class="info-value"><?= formatBytes($memory_info['current']) ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-item">
                                    <div class="info-label">Peak Usage</div>
                                    <div class="info-value"><?= formatBytes($memory_info['peak']) ?></div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="info-item">
                                    <div class="info-label">Memory Limit</div>
                                    <div class="info-value"><?= $memory_info['limit'] ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($disk_info)): ?>
                            <hr>
                            <h6>Disk Space</h6>
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <div class="info-item">
                                        <div class="info-label">Used</div>
                                        <div class="info-value"><?= formatBytes($disk_info['used']) ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="info-item">
                                        <div class="info-label">Free</div>
                                        <div class="info-value"><?= formatBytes($disk_info['free']) ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="info-item">
                                        <div class="info-label">Total</div>
                                        <div class="info-value"><?= formatBytes($disk_info['total']) ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress mt-3">
                                <div class="progress-bar bg-<?= $disk_info['percent_used'] > 80 ? 'danger' : ($disk_info['percent_used'] > 60 ? 'warning' : 'success') ?>" 
                                     style="width: <?= $disk_info['percent_used'] ?>%">
                                    <?= $disk_info['percent_used'] ?>%
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Stats Tab -->
    <div class="tab-pane fade" id="system" role="tabpanel">
        <div class="crown-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    System Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($system_counts as $table => $data): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card-sm">
                                <div class="stats-number-sm"><?= number_format($data['count']) ?></div>
                                <div class="stats-label-sm"><?= $data['label'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Auto-refresh every 30 seconds
if (typeof autoRefreshInterval !== 'undefined') {
    clearInterval(autoRefreshInterval);
}

let refreshCounter = 30;
const autoRefreshInterval = setInterval(() => {
    refreshCounter--;
    if (refreshCounter <= 0) {
        location.reload();
    }
}, 1000);

// Optional: Show countdown
console.log('System Resources page loaded - auto-refresh enabled');
";

include 'includes/footer.php';
?>