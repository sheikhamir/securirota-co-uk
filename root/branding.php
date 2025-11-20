<?php
/**
 * Company Branding & Customization Settings
 * Allows companies to customize their interface appearance
 */
$page_title = 'Company Branding & Settings';
require_once '../config/config.php';

// Start session and check authentication
session_start();
requireLogin();

// Check if user is admin (company admin or super admin)
if (!isCompanyAdmin() && !isSuperAdmin()) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get company ID
$company_id = isSuperAdmin() ? ($_GET['company_id'] ?? null) : getCurrentCompanyId();

if (!$company_id) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit();
}

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_branding':
                $stmt = $conn->prepare("
                    UPDATE companies SET 
                        brand_primary_color = ?, 
                        brand_secondary_color = ?,
                        brand_logo_url = ?,
                        brand_favicon_url = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['primary_color'] ?? null,
                    $_POST['secondary_color'] ?? null,
                    $_POST['logo_url'] ?? null,
                    $_POST['favicon_url'] ?? null,
                    $company_id
                ]);
                $success = "Branding settings updated successfully!";
                break;
                
            case 'update_display':
                $stmt = $conn->prepare("
                    UPDATE companies SET 
                        display_company_name = ?,
                        display_timezone = ?,
                        display_date_format = ?,
                        display_time_format = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['display_name'] ?? null,
                    $_POST['timezone'] ?? 'Europe/London',
                    $_POST['date_format'] ?? 'Y-m-d',
                    $_POST['time_format'] ?? 'H:i',
                    $company_id
                ]);
                $success = "Display settings updated successfully!";
                break;
                
            case 'update_features':
                $features = json_encode([
                    'enable_mobile_app' => isset($_POST['enable_mobile_app']),
                    'enable_notifications' => isset($_POST['enable_notifications']),
                    'enable_reports' => isset($_POST['enable_reports']),
                    'enable_advanced_scheduling' => isset($_POST['enable_advanced_scheduling']),
                    'enable_client_portal' => isset($_POST['enable_client_portal'])
                ]);
                
                $stmt = $conn->prepare("
                    UPDATE companies SET 
                        feature_settings = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$features, $company_id]);
                $success = "Feature settings updated successfully!";
                break;
                
            case 'update_custom_css':
                $stmt = $conn->prepare("
                    UPDATE companies SET 
                        custom_css = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['custom_css'] ?? null,
                    $company_id
                ]);
                $success = "Custom CSS updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get company settings
$stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit();
}

// Parse feature settings
$features = json_decode($company['feature_settings'] ?? '{}', true) ?: [];

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-palette"></i> Company Branding & Settings
                </h1>
                <div class="page-subtitle">
                    Customize the appearance and features for <?= htmlspecialchars($company['name']) ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Branding Settings -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paint-brush"></i> Brand Colors & Logo
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_branding">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primary Brand Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" 
                                               name="primary_color" 
                                               value="<?= $company['brand_primary_color'] ?? '#0066cc' ?>"
                                               title="Choose primary color">
                                        <input type="text" class="form-control" 
                                               name="primary_color_hex" 
                                               value="<?= $company['brand_primary_color'] ?? '#0066cc' ?>"
                                               readonly>
                                    </div>
                                    <div class="form-hint">Used for buttons, links, and accents</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Secondary Brand Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" 
                                               name="secondary_color" 
                                               value="<?= $company['brand_secondary_color'] ?? '#6c757d' ?>"
                                               title="Choose secondary color">
                                        <input type="text" class="form-control" 
                                               name="secondary_color_hex" 
                                               value="<?= $company['brand_secondary_color'] ?? '#6c757d' ?>"
                                               readonly>
                                    </div>
                                    <div class="form-hint">Used for secondary elements</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Logo URL</label>
                            <input type="url" class="form-control" name="logo_url" 
                                   value="<?= htmlspecialchars($company['brand_logo_url'] ?? '') ?>"
                                   placeholder="https://example.com/logo.png">
                            <div class="form-hint">URL to your company logo (recommended: 200x60px, PNG/SVG)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Favicon URL</label>
                            <input type="url" class="form-control" name="favicon_url" 
                                   value="<?= htmlspecialchars($company['brand_favicon_url'] ?? '') ?>"
                                   placeholder="https://example.com/favicon.ico">
                            <div class="form-hint">URL to your favicon (recommended: 32x32px, ICO/PNG)</div>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Preview</h5>
                            <div class="brand-preview p-3 border rounded" style="background: <?= $company['brand_primary_color'] ?? '#0066cc' ?>; color: white;">
                                <div class="d-flex align-items-center">
                                    <?php if ($company['brand_logo_url']): ?>
                                    <img src="<?= htmlspecialchars($company['brand_logo_url']) ?>" 
                                         alt="Logo" style="height: 40px; margin-right: 15px;">
                                    <?php endif; ?>
                                    <h4 class="mb-0"><?= htmlspecialchars($company['name']) ?></h4>
                                </div>
                                <p class="mt-2 mb-0">This is how your brand colors will look in the interface.</p>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Branding
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-desktop"></i> Display Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_display">
                        
                        <div class="mb-3">
                            <label class="form-label">Display Company Name</label>
                            <input type="text" class="form-control" name="display_name" 
                                   value="<?= htmlspecialchars($company['display_company_name'] ?? $company['name']) ?>"
                                   placeholder="Company Name">
                            <div class="form-hint">How the company name appears in the interface</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Timezone</label>
                            <select class="form-select" name="timezone">
                                <option value="Europe/London" <?= ($company['display_timezone'] ?? 'Europe/London') === 'Europe/London' ? 'selected' : '' ?>>London (Europe/London)</option>
                                <option value="America/New_York" <?= ($company['display_timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>New York (America/New_York)</option>
                                <option value="America/Los_Angeles" <?= ($company['display_timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : '' ?>>Los Angeles (America/Los_Angeles)</option>
                                <option value="Europe/Paris" <?= ($company['display_timezone'] ?? '') === 'Europe/Paris' ? 'selected' : '' ?>>Paris (Europe/Paris)</option>
                                <option value="Asia/Dubai" <?= ($company['display_timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' ?>>Dubai (Asia/Dubai)</option>
                                <option value="Australia/Sydney" <?= ($company['display_timezone'] ?? '') === 'Australia/Sydney' ? 'selected' : '' ?>>Sydney (Australia/Sydney)</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date Format</label>
                                    <select class="form-select" name="date_format">
                                        <option value="Y-m-d" <?= ($company['display_date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>2024-03-15</option>
                                        <option value="d/m/Y" <?= ($company['display_date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>15/03/2024</option>
                                        <option value="m/d/Y" <?= ($company['display_date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>03/15/2024</option>
                                        <option value="d-m-Y" <?= ($company['display_date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' ?>>15-03-2024</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Time Format</label>
                                    <select class="form-select" name="time_format">
                                        <option value="H:i" <?= ($company['display_time_format'] ?? 'H:i') === 'H:i' ? 'selected' : '' ?>>24-hour (15:30)</option>
                                        <option value="g:i A" <?= ($company['display_time_format'] ?? '') === 'g:i A' ? 'selected' : '' ?>>12-hour (3:30 PM)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Display Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Settings -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-toggle-on"></i> Feature Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_features">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_mobile_app" 
                                           id="enable_mobile_app" <?= ($features['enable_mobile_app'] ?? false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enable_mobile_app">
                                        <strong>Mobile App Access</strong>
                                        <div class="text-muted small">Allow officers to use mobile app for check-ins</div>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_notifications" 
                                           id="enable_notifications" <?= ($features['enable_notifications'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enable_notifications">
                                        <strong>Email Notifications</strong>
                                        <div class="text-muted small">Send automated email notifications for events</div>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_reports" 
                                           id="enable_reports" <?= ($features['enable_reports'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enable_reports">
                                        <strong>Advanced Reports</strong>
                                        <div class="text-muted small">Access to detailed reporting and analytics</div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_advanced_scheduling" 
                                           id="enable_advanced_scheduling" <?= ($features['enable_advanced_scheduling'] ?? false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enable_advanced_scheduling">
                                        <strong>Advanced Scheduling</strong>
                                        <div class="text-muted small">Recurring shifts, templates, and auto-scheduling</div>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_client_portal" 
                                           id="enable_client_portal" <?= ($features['enable_client_portal'] ?? false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enable_client_portal">
                                        <strong>Client Portal</strong>
                                        <div class="text-muted small">Allow clients to view their site schedules and reports</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Feature Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS Override -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-code"></i> Custom CSS (Advanced)
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Advanced Feature:</strong> Add custom CSS to override default styles. Use with caution as incorrect CSS may break the interface.
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_custom_css">
                        
                        <div class="mb-3">
                            <label class="form-label">Custom CSS</label>
                            <textarea class="form-control font-monospace" name="custom_css" rows="10" 
                                      placeholder="/* Add your custom CSS here */
.sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.btn-primary { background-color: #your-color; }"><?= htmlspecialchars($company['custom_css'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Save Custom CSS
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="previewCSS()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.brand-preview {
    transition: all 0.3s ease;
}

.form-control-color {
    width: 60px;
    height: 38px;
    padding: 4px;
    border: 1px solid #ced4da;
}

.form-check-label strong {
    font-weight: 500;
}

.form-switch .form-check-input {
    width: 2.5em;
    height: 1.25em;
}

.font-monospace {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.875rem;
}
</style>

<script>
// Sync color picker with hex input
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const hexInput = colorInput.parentElement.querySelector('input[name$="_hex"]');
    
    colorInput.addEventListener('change', function() {
        hexInput.value = this.value;
        updatePreview();
    });
    
    hexInput.addEventListener('input', function() {
        if (this.value.match(/^#[0-9a-fA-F]{6}$/)) {
            colorInput.value = this.value;
            updatePreview();
        }
    });
});

function updatePreview() {
    const primaryColor = document.querySelector('input[name="primary_color"]').value;
    const secondaryColor = document.querySelector('input[name="secondary_color"]').value;
    const preview = document.querySelector('.brand-preview');
    
    preview.style.background = `linear-gradient(135deg, ${primaryColor} 0%, ${secondaryColor} 100%)`;
}

function previewCSS() {
    const css = document.querySelector('textarea[name="custom_css"]').value;
    
    // Remove existing preview styles
    const existingStyle = document.getElementById('css-preview');
    if (existingStyle) {
        existingStyle.remove();
    }
    
    // Add new preview styles
    const style = document.createElement('style');
    style.id = 'css-preview';
    style.textContent = css;
    document.head.appendChild(style);
    
    // Show notification
    alert('CSS preview applied! Refresh the page to revert changes.');
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', updatePreview);
</script>

<?php require_once '../includes/footer.php'; ?>
