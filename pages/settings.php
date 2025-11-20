<?php
$page_title = 'Settings';
require_once '../includes/header.php';

// Only allow admin access
if (!isAdmin()) {
    header('Location: ../dashboard.php');
    exit();
}
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-cog"></i> System Settings</h3>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; padding: 30px;">
        
        <!-- Site Records Settings -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-building"></i> Site Records
            </h4>
            <p class="text-muted">Manage site configurations, default rates, and site-specific settings.</p>
            
            <div class="mt-20">
                <a href="sites.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-building"></i> Manage Sites
                </a>
                <a href="clients.php" class="btn btn-success btn-sm">
                    <i class="fas fa-handshake"></i> Manage Clients
                </a>
            </div>
        </div>
        
        <!-- Staff Records Settings -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-users"></i> Staff Records
            </h4>
            <p class="text-muted">Configure officer profiles, pay rates, roles, and employment settings.</p>
            
            <div class="mt-20">
                <a href="officers.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-users"></i> Manage Officers
                </a>
                <button class="btn btn-info btn-sm" onclick="showPayRateSettings()">
                    <i class="fas fa-pound-sign"></i> Pay Rates
                </button>
            </div>
        </div>
        
        <!-- Document Management -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-file-alt"></i> Uploaded Documents
            </h4>
            <p class="text-muted">Manage document storage, categories, and expiry notifications.</p>
            
            <div class="mt-20">
                <button class="btn btn-primary btn-sm" onclick="showDocumentSettings()">
                    <i class="fas fa-folder"></i> Document Categories
                </button>
                <button class="btn btn-warning btn-sm" onclick="showExpirySettings()">
                    <i class="fas fa-bell"></i> Expiry Alerts
                </button>
            </div>
        </div>
        
        <!-- Export Settings -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-download"></i> Export Settings
            </h4>
            <p class="text-muted">Configure export formats, templates, and automated report generation.</p>
            
            <div class="mt-20">
                <button class="btn btn-success btn-sm" onclick="showExportTemplates()">
                    <i class="fas fa-file-excel"></i> Excel Templates
                </button>
                <button class="btn btn-danger btn-sm" onclick="showPDFSettings()">
                    <i class="fas fa-file-pdf"></i> PDF Settings
                </button>
            </div>
        </div>
        
        <!-- System Configuration -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-server"></i> System Configuration
            </h4>
            <p class="text-muted">General system settings, database configuration, and security options.</p>
            
            <div class="mt-20">
                <button class="btn btn-info btn-sm" onclick="showSystemSettings()">
                    <i class="fas fa-cogs"></i> General Settings
                </button>
                <button class="btn btn-warning btn-sm" onclick="showSecuritySettings()">
                    <i class="fas fa-shield-alt"></i> Security
                </button>
            </div>
        </div>
        
        <!-- Email Templates -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-envelope-open-text"></i> Email Templates
            </h4>
            <p class="text-muted">Customize email templates for notifications, PIN generation, and system communications.</p>
            
            <div class="mt-20">
                <a href="email_templates.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> Manage Templates
                </a>
                <button class="btn btn-success btn-sm" onclick="showQuickTestModal()">
                    <i class="fas fa-paper-plane"></i> Quick Test
                </button>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="settings-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-user-cog"></i> User Management
            </h4>
            <p class="text-muted">Manage user accounts, permissions, and access levels.</p>
            
            <div class="mt-20">
                <a href="<?php echo BASE_URL; ?>pages/users.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-users-cog"></i> User Accounts
                </a>
                <button class="btn btn-secondary btn-sm" onclick="showPermissions()">
                    <i class="fas fa-key"></i> Permissions
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- System Information -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> System Information</h3>
    </div>
    
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="info-item">
                <strong>System Version:</strong>
                <span class="badge badge-info">v1.0.0</span>
            </div>
            
            <div class="info-item">
                <strong>Database Version:</strong>
                <span class="badge badge-success">MySQL 8.0</span>
            </div>
            
            <div class="info-item">
                <strong>PHP Version:</strong>
                <span class="badge badge-warning"><?php echo phpversion(); ?></span>
            </div>
            
            <div class="info-item">
                <strong>Last Backup:</strong>
                <span class="text-muted">Not configured</span>
            </div>
        </div>
        
        <div class="mt-30">
            <h5>Quick Actions</h5>
            <div class="d-flex gap-10 mt-15">
                <button onclick="createBackup()" class="btn btn-success">
                    <i class="fas fa-download"></i> Create Backup
                </button>
                <button onclick="clearCache()" class="btn btn-warning">
                    <i class="fas fa-broom"></i> Clear Cache
                </button>
                <button onclick="viewLogs()" class="btn btn-info">
                    <i class="fas fa-list"></i> View Logs
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showQuickTestModal() {
    const content = `
        <h4>Quick Email Test</h4>
        <p>Send a quick test email to verify your email configuration is working.</p>
        <div class="mb-20">
            <label for="quickTestEmail" class="form-label">Test Email Address:</label>
            <input type="email" id="quickTestEmail" class="form-control mb-15" placeholder="Enter email address...">
            
            <button onclick="sendQuickTestEmail()" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Send Test Email
            </button>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            This will send a simple test email to verify email delivery is working.
            <br><br>
            For full template testing and editing, use the <a href="email_templates.php">Template Manager</a>.
        </div>
    `;
    showModal('Quick Email Test', content);
}

function sendQuickTestEmail() {
    const email = document.getElementById('quickTestEmail').value;
    
    if (!email) {
        showNotification('Please enter an email address', 'error');
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Please enter a valid email address', 'error');
        return;
    }
    
    const sendBtn = document.querySelector('button[onclick="sendQuickTestEmail()"]');
    const originalText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    sendBtn.disabled = true;
    
    // Send a simple test email using PIN generation template
    const formData = new FormData();
    formData.append('action', 'test_email');
    formData.append('template_type', 'pin_generation');
    formData.append('test_email', email);
    
    fetch('../api/email_templates.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Test email sent successfully to ' + email, 'success');
        } else {
            showNotification('Error sending test email: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error sending test email:', error);
        showNotification('Error sending test email. Please try again.', 'error');
    })
    .finally(() => {
        sendBtn.innerHTML = originalText;
        sendBtn.disabled = false;
    });
}

function showPayRateSettings() {
    const content = `
        <h4>Pay Rate Configuration</h4>
        <p>Configure default pay rates for different roles and experience levels.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('Pay Rate Settings', content);
}

function showDocumentSettings() {
    const content = `
        <h4>Document Management</h4>
        <p>Configure document categories, file types, and storage settings.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('Document Settings', content);
}

function showExpirySettings() {
    const content = `
        <h4>Expiry Alert Configuration</h4>
        <p>Set up notifications for document and certification expiries.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('Expiry Settings', content);
}

function showExportTemplates() {
    const content = `
        <h4>Export Template Settings</h4>
        <p>Customize Excel templates for reports and invoices.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('Export Templates', content);
}

function showPDFSettings() {
    const content = `
        <h4>PDF Configuration</h4>
        <p>Configure PDF generation settings, letterheads, and branding.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('PDF Settings', content);
}

function showSystemSettings() {
    const content = `
        <h4>General System Settings</h4>
        <p>Configure system-wide settings and preferences.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('System Settings', content);
}

function showSecuritySettings() {
    const content = `
        <h4>Security Configuration</h4>
        <p>Configure security policies, password requirements, and access controls.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('Security Settings', content);
}

function showPermissions() {
    const content = `
        <h4>Permission Management</h4>
        <p>Configure role-based permissions and access levels.</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This feature is under development.
        </div>
    `;
    showModal('Permissions', content);
}

function createBackup() {
    if (confirm('Create a system backup? This may take a few minutes.')) {
        showNotification('Backup feature coming soon', 'info');
    }
}

function clearCache() {
    if (confirm('Clear system cache?')) {
        showNotification('Cache cleared successfully', 'success');
    }
}

function viewLogs() {
    showNotification('Log viewer coming soon', 'info');
}
</script>

<?php require_once '../includes/footer.php'; ?>
