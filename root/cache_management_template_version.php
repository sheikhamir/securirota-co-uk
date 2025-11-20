<?php
/**
 * Root User - Cache Management System
 * Clear various types of cache and temporary files
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'Cache Management';
$page_description = 'Clear various types of cache and temporary files';
$active_page = 'cache_management';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'System Management', 'url' => 'system_resources.php'],
    ['title' => 'Cache Management']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

// Handle cache clearing actions
if ($_POST && isset($_POST['action'])) {
    $results = [];
    
    switch ($_POST['action']) {
        case 'clear_all':
            // Clear all cache types
            $results['opcache'] = clearOPCache();
            $results['session'] = clearSessionFiles();
            $results['temp'] = clearTempFiles();
            $results['logs'] = clearOldLogs();
            break;
            
        case 'clear_opcache':
            $results['opcache'] = clearOPCache();
            break;
            
        case 'clear_sessions':
            $results['session'] = clearSessionFiles();
            break;
            
        case 'clear_temp':
            $results['temp'] = clearTempFiles();
            break;
            
        case 'clear_logs':
            $results['logs'] = clearOldLogs();
            break;
    }
    
    $_SESSION['cache_results'] = $results;
    header('Location: cache_management.php');
    exit();
}

// Cache clearing functions
function clearOPCache() {
    if (function_exists('opcache_reset')) {
        if (opcache_reset()) {
            return ['success' => true, 'message' => 'OPCache cleared successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to clear OPCache'];
        }
    } else {
        return ['success' => false, 'message' => 'OPCache is not available'];
    }
}

function clearSessionFiles() {
    $session_path = session_save_path();
    if (empty($session_path)) {
        $session_path = sys_get_temp_dir();
    }
    
    $cleared = 0;
    $errors = 0;
    
    if (is_dir($session_path)) {
        $files = glob($session_path . '/sess_*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < time() - 3600) { // Older than 1 hour
                if (unlink($file)) {
                    $cleared++;
                } else {
                    $errors++;
                }
            }
        }
    }
    
    return [
        'success' => $errors == 0,
        'message' => "Cleared {$cleared} session files" . ($errors > 0 ? " with {$errors} errors" : '')
    ];
}

function clearTempFiles() {
    $temp_paths = [
        sys_get_temp_dir(),
        '../uploads/temp',
        '../cache'
    ];
    
    $cleared = 0;
    $errors = 0;
    
    foreach ($temp_paths as $path) {
        if (is_dir($path)) {
            $files = array_merge(
                glob($path . '/*.tmp'),
                glob($path . '/*.temp'),
                glob($path . '/cache_*')
            );
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < time() - 7200) { // Older than 2 hours
                    if (unlink($file)) {
                        $cleared++;
                    } else {
                        $errors++;
                    }
                }
            }
        }
    }
    
    return [
        'success' => $errors == 0,
        'message' => "Cleared {$cleared} temporary files" . ($errors > 0 ? " with {$errors} errors" : '')
    ];
}

function clearOldLogs() {
    $log_paths = [
        '../error_log',
        '../api/error_log',
        '../includes/error_log'
    ];
    
    $cleared = 0;
    $errors = 0;
    
    foreach ($log_paths as $log_file) {
        if (is_file($log_file) && filesize($log_file) > 1024 * 1024) { // Larger than 1MB
            if (file_put_contents($log_file, '')) {
                $cleared++;
            } else {
                $errors++;
            }
        }
    }
    
    return [
        'success' => $errors == 0,
        'message' => "Cleared {$cleared} log files" . ($errors > 0 ? " with {$errors} errors" : '')
    ];
}

// Get cache information
$cache_info = [];

// OPCache info
if (function_exists('opcache_get_status')) {
    $opcache_status = opcache_get_status();
    $cache_info['opcache'] = [
        'enabled' => $opcache_status !== false,
        'memory_usage' => $opcache_status['memory_usage'] ?? null,
        'statistics' => $opcache_status['opcache_statistics'] ?? null
    ];
} else {
    $cache_info['opcache'] = ['enabled' => false];
}

// Session info
$session_path = session_save_path() ?: sys_get_temp_dir();
$session_files = is_dir($session_path) ? count(glob($session_path . '/sess_*')) : 0;
$cache_info['sessions'] = [
    'path' => $session_path,
    'count' => $session_files
];

// Temp files info
$temp_count = 0;
$temp_size = 0;
$temp_paths = [sys_get_temp_dir(), '../uploads/temp', '../cache'];

foreach ($temp_paths as $path) {
    if (is_dir($path)) {
        $files = array_merge(
            glob($path . '/*.tmp'),
            glob($path . '/*.temp'),
            glob($path . '/cache_*')
        );
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $temp_count++;
                $temp_size += filesize($file);
            }
        }
    }
}

$cache_info['temp'] = [
    'count' => $temp_count,
    'size' => $temp_size
];

// Get previous results
$cache_results = $_SESSION['cache_results'] ?? null;
unset($_SESSION['cache_results']);

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
                <i class="fas fa-broom text-warning me-2"></i>
                Cache Management
            </h1>
            <p class="mb-0 text-white-50">Clear various types of cache and temporary files</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" onclick="clearAllCache()">
                <i class="fas fa-trash-alt me-1"></i> Clear All
            </button>
        </div>
    </div>
</div>

<!-- Results Display -->
<?php if ($cache_results): ?>
    <div class="crown-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-check-circle text-success me-2"></i>
                Cache Clearing Results
            </h5>
        </div>
        <div class="card-body">
            <?php foreach ($cache_results as $type => $result): ?>
                <div class="alert alert-<?= $result['success'] ? 'success' : 'danger' ?> mb-2">
                    <i class="fas fa-<?= $result['success'] ? 'check' : 'exclamation-triangle' ?> me-2"></i>
                    <strong><?= ucfirst($type) ?>:</strong> <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Cache Status Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        <?= $cache_info['opcache']['enabled'] ? 'ON' : 'OFF' ?>
                    </div>
                    <div class="stats-label">OPCache</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-rocket"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $cache_info['sessions']['count'] ?></div>
                    <div class="stats-label">Session Files</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $cache_info['temp']['count'] ?></div>
                    <div class="stats-label">Temp Files</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= formatBytes($cache_info['temp']['size']) ?></div>
                    <div class="stats-label">Temp Size</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-hdd"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cache Management Tools -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="crown-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-rocket text-primary me-2"></i>
                    OPCache Management
                </h5>
            </div>
            <div class="card-body">
                <?php if ($cache_info['opcache']['enabled']): ?>
                    <div class="mb-3">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="crown-badge crown-badge-success">Enabled</span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($cache_info['opcache']['memory_usage']): ?>
                        <div class="mb-3">
                            <div class="info-item">
                                <div class="info-label">Memory Usage</div>
                                <div class="info-value">
                                    <?= formatBytes($cache_info['opcache']['memory_usage']['used_memory']) ?> / 
                                    <?= formatBytes($cache_info['opcache']['memory_usage']['free_memory'] + $cache_info['opcache']['memory_usage']['used_memory']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-crown" onclick="clearCache('opcache')">
                        <i class="fas fa-broom me-1"></i> Clear OPCache
                    </button>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        OPCache is not enabled on this server.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="crown-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-clock text-primary me-2"></i>
                    Session Management
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="info-item">
                        <div class="info-label">Session Path</div>
                        <div class="info-value">
                            <code><?= htmlspecialchars($cache_info['sessions']['path']) ?></code>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="info-item">
                        <div class="info-label">Active Sessions</div>
                        <div class="info-value">
                            <span class="crown-badge crown-badge-info">
                                <?= $cache_info['sessions']['count'] ?> files
                            </span>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-crown" onclick="clearCache('sessions')">
                    <i class="fas fa-broom me-1"></i> Clear Old Sessions
                </button>
                
                <div class="form-text mt-2">
                    Only clears sessions older than 1 hour
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="crown-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Temporary Files
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="info-item">
                        <div class="info-label">Temp Files</div>
                        <div class="info-value">
                            <span class="crown-badge crown-badge-light">
                                <?= $cache_info['temp']['count'] ?> files
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="info-item">
                        <div class="info-label">Total Size</div>
                        <div class="info-value">
                            <?= formatBytes($cache_info['temp']['size']) ?>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-crown" onclick="clearCache('temp')">
                    <i class="fas fa-broom me-1"></i> Clear Temp Files
                </button>
                
                <div class="form-text mt-2">
                    Only clears files older than 2 hours
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="crown-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-text text-primary me-2"></i>
                    Log Files
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    This will clear error logs that are larger than 1MB.
                </div>
                
                <button type="button" class="btn btn-crown" onclick="clearCache('logs')">
                    <i class="fas fa-broom me-1"></i> Clear Large Logs
                </button>
                
                <div class="form-text mt-2">
                    Clears error_log files larger than 1MB
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions -->
<div class="crown-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tools text-primary me-2"></i>
            Bulk Operations
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-3">
                    Use these options to clear multiple cache types at once. This can help resolve 
                    performance issues and free up disk space.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-crown" onclick="clearAllCache()">
                    <i class="fas fa-trash-alt me-1"></i> Clear All Caches
                </button>
            </div>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> Clearing all caches may temporarily slow down the system 
            while caches are rebuilt.
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Clear specific cache type
function clearCache(type) {
    const messages = {
        'opcache': 'OPCache',
        'sessions': 'session files',
        'temp': 'temporary files',
        'logs': 'log files'
    };
    
    const message = 'Are you sure you want to clear ' + messages[type] + '?';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type='hidden' name='action' value='clear_\${type}'>`;
        document.body.appendChild(form);
        form.submit();
    });
}

// Clear all caches
function clearAllCache() {
    const message = 'Are you sure you want to clear ALL caches? This may temporarily slow down the system.';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type='hidden' name='action' value='clear_all'>`;
        document.body.appendChild(form);
        form.submit();
    });
}
";

include 'includes/footer.php';
?>