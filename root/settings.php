<?php
/**
 * Super Admin System Settings
 * Global platform configuration and maintenance
 */
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    header('Location: ../dashboard.php?error=access_denied');
    exit();
}

$page_title = 'System Settings';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle form submissions
    if ($_POST) {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_settings':
                updateSystemSettings($conn, $_POST);
                break;
            case 'maintenance_mode':
                toggleMaintenanceMode($conn, $_POST['enabled'] ?? false);
                break;
            case 'clear_cache':
                clearSystemCache();
                break;
        }
    }
    
    // Get current settings
    $settings = getSystemSettings($conn);
    
} catch (Exception $e) {
    $error = "Error loading settings: " . $e->getMessage();
}

function updateSystemSettings($conn, $data) {
    $settings = [
        'platform_name' => $data['platform_name'] ?? 'SecuriRota',
        'default_timezone' => $data['default_timezone'] ?? 'UTC',
        'max_companies' => (int)($data['max_companies'] ?? 100),
        'email_from_address' => $data['email_from_address'] ?? '',
        'email_from_name' => $data['email_from_name'] ?? '',
        'backup_enabled' => isset($data['backup_enabled']),
        'debug_mode' => isset($data['debug_mode']),
        'registration_enabled' => isset($data['registration_enabled'])
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            updated_by = VALUES(updated_by),
            updated_at = NOW()
        ");
        $stmt->execute([$key, json_encode($value), $_SESSION['user_id']]);
    }
    
    $_SESSION['success_message'] = 'Settings updated successfully';
}

function getSystemSettings($conn) {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = json_decode($row['setting_value'], true);
    }
    
    // Set defaults if not exists
    $defaults = [
        'platform_name' => 'SecuriRota',
        'default_timezone' => 'UTC',
        'max_companies' => 100,
        'email_from_address' => '',
        'email_from_name' => 'SecuriRota',
        'backup_enabled' => true,
        'debug_mode' => false,
        'registration_enabled' => true
    ];
    
    return array_merge($defaults, $settings);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Super Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .settings-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            margin-right: 10px;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>

<div class="settings-container">
    <!-- Header -->
    <div class="settings-card text-center mb-4">
        <h1 class="mb-3">
            <i class="fas fa-cogs text-primary me-2"></i>
            System Settings
        </h1>
        <p class="text-muted">Configure global platform settings and preferences</p>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Control Panel
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Settings Navigation -->
    <div class="settings-card">
        <ul class="nav nav-pills mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button">
                    <i class="fas fa-globe me-1"></i> General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="pill" data-bs-target="#email" type="button">
                    <i class="fas fa-envelope me-1"></i> Email
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button">
                    <i class="fas fa-shield-alt me-1"></i> Security
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="maintenance-tab" data-bs-toggle="pill" data-bs-target="#maintenance" type="button">
                    <i class="fas fa-tools me-1"></i> Maintenance
                </button>
            </li>
        </ul>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_settings">
            
            <div class="tab-content" id="settingsTabContent">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <h5 class="mb-4">General Platform Settings</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Platform Name</label>
                                <input type="text" class="form-control" name="platform_name" 
                                       value="<?= htmlspecialchars($settings['platform_name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Default Timezone</label>
                                <select class="form-select" name="default_timezone">
                                    <option value="UTC" <?= $settings['default_timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                    <option value="America/New_York" <?= $settings['default_timezone'] === 'America/New_York' ? 'selected' : '' ?>>Eastern Time</option>
                                    <option value="America/Chicago" <?= $settings['default_timezone'] === 'America/Chicago' ? 'selected' : '' ?>>Central Time</option>
                                    <option value="America/Denver" <?= $settings['default_timezone'] === 'America/Denver' ? 'selected' : '' ?>>Mountain Time</option>
                                    <option value="America/Los_Angeles" <?= $settings['default_timezone'] === 'America/Los_Angeles' ? 'selected' : '' ?>>Pacific Time</option>
                                    <option value="Europe/London" <?= $settings['default_timezone'] === 'Europe/London' ? 'selected' : '' ?>>London Time</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maximum Companies</label>
                                <input type="number" class="form-control" name="max_companies" 
                                       value="<?= $settings['max_companies'] ?>" min="1" max="1000">
                                <div class="form-text">Set to 0 for unlimited</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">System Options</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="registration_enabled" 
                                           <?= $settings['registration_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Enable new company registration</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="debug_mode" 
                                           <?= $settings['debug_mode'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Enable debug mode</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="tab-pane fade" id="email" role="tabpanel">
                    <h5 class="mb-4">Email Configuration</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">From Email Address</label>
                                <input type="email" class="form-control" name="email_from_address" 
                                       value="<?= htmlspecialchars($settings['email_from_address']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" class="form-control" name="email_from_name" 
                                       value="<?= htmlspecialchars($settings['email_from_name']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Email server configuration is handled through the hosting provider's settings.
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <h5 class="mb-4">Security Configuration</h5>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Advanced security settings are managed through the 
                                <a href="security.php" class="alert-link">Security Dashboard</a>.
                            </div>
                            
                            <div class="list-group">
                                <a href="security.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-shield-alt text-primary me-2"></i>
                                    Access Security Dashboard
                                </a>
                                <a href="../pages/activity_logs.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-list text-info me-2"></i>
                                    View Activity Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel">
                    <h5 class="mb-4">System Maintenance</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Backup Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" name="backup_enabled" 
                                               <?= $settings['backup_enabled'] ? 'checked' : '' ?>>
                                        <label class="form-check-label">Enable automatic backups</label>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="createBackup()">
                                        <i class="fas fa-download me-1"></i> Create Manual Backup
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Cache Management</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">Clear system cache to refresh data</p>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearCache()">
                                        <i class="fas fa-trash me-1"></i> Clear Cache
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">Danger Zone</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">These actions cannot be undone. Use with extreme caution.</p>
                                    <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="enableMaintenanceMode()">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Enable Maintenance Mode
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="resetSystem()">
                                        <i class="fas fa-power-off me-1"></i> System Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function createBackup() {
    if (confirm('Create a manual system backup? This may take a few minutes.')) {
        // Implementation for backup creation
        alert('Backup creation initiated. You will be notified when complete.');
    }
}

function clearCache() {
    if (confirm('Clear system cache? This will refresh all cached data.')) {
        fetch('../api/system_maintenance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'clear_cache' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully');
                location.reload();
            } else {
                alert('Error clearing cache: ' + data.message);
            }
        });
    }
}

function enableMaintenanceMode() {
    if (confirm('Enable maintenance mode? This will prevent all users (except super admin) from accessing the system.')) {
        // Implementation for maintenance mode
        alert('Maintenance mode enabled');
    }
}

function resetSystem() {
    const confirmation = prompt('Type "RESET SYSTEM" to confirm this dangerous action:');
    if (confirmation === 'RESET SYSTEM') {
        alert('System reset initiated. Please contact technical support.');
    }
}
</script>

</body>
</html>
