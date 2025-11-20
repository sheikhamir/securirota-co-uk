<?php
/**
 * Single-Tenant to Multi-Tenant Migration Tool
 * Helps convert existing single-tenant installations to multi-tenant
 */
$page_title = 'Data Migration Tool';
require_once '../config/config.php';

// Start session and check authentication
session_start();
requireSuperAdmin();

require_once '../config/database.php';

$step = $_GET['step'] ?? 1;
$migration_status = $_SESSION['migration_status'] ?? [];

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-database"></i> Single-Tenant to Multi-Tenant Migration Tool
                </h1>
                <div class="page-subtitle">Convert existing single-tenant installations to multi-tenant SAAS</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <!-- Migration Steps Sidebar -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Migration Steps</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item <?= $step == 1 ? 'active' : '' ?>">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">1</span>
                                <div>
                                    <div class="fw-medium">Pre-Migration Check</div>
                                    <div class="text-muted small">Validate system compatibility</div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item <?= $step == 2 ? 'active' : '' ?>">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">2</span>
                                <div>
                                    <div class="fw-medium">Backup & Prepare</div>
                                    <div class="text-muted small">Create backup and prepare database</div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item <?= $step == 3 ? 'active' : '' ?>">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">3</span>
                                <div>
                                    <div class="fw-medium">Create Default Company</div>
                                    <div class="text-muted small">Set up the primary company</div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item <?= $step == 4 ? 'active' : '' ?>">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">4</span>
                                <div>
                                    <div class="fw-medium">Migrate Data</div>
                                    <div class="text-muted small">Convert existing data</div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item <?= $step == 5 ? 'active' : '' ?>">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">5</span>
                                <div>
                                    <div class="fw-medium">Verification</div>
                                    <div class="text-muted small">Validate migration results</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if ($step == 1): ?>
            <!-- Step 1: Pre-Migration Check -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 1: Pre-Migration Compatibility Check</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Important:</strong> This tool will convert your single-tenant installation to support multiple companies. 
                        Ensure you have a full backup before proceeding.
                    </div>

                    <div id="compatibilityResults">
                        <div class="text-center">
                            <button class="btn btn-primary" onclick="runCompatibilityCheck()">
                                <i class="fas fa-search"></i> Run Compatibility Check
                            </button>
                        </div>
                    </div>

                    <div id="checkResults" style="display: none;">
                        <h5 class="mt-4">System Requirements</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr id="php-version">
                                        <td><i class="fas fa-circle text-warning"></i> PHP Version</td>
                                        <td>Checking...</td>
                                    </tr>
                                    <tr id="mysql-version">
                                        <td><i class="fas fa-circle text-warning"></i> MySQL/MariaDB Version</td>
                                        <td>Checking...</td>
                                    </tr>
                                    <tr id="backup-writable">
                                        <td><i class="fas fa-circle text-warning"></i> Backup Directory Writable</td>
                                        <td>Checking...</td>
                                    </tr>
                                    <tr id="existing-data">
                                        <td><i class="fas fa-circle text-warning"></i> Existing Data Found</td>
                                        <td>Checking...</td>
                                    </tr>
                                    <tr id="companies-table">
                                        <td><i class="fas fa-circle text-warning"></i> Companies Table Exists</td>
                                        <td>Checking...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <div></div>
                            <button class="btn btn-primary" onclick="window.location.href='?step=2'" disabled id="nextBtn">
                                Next: Backup & Prepare <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($step == 2): ?>
            <!-- Step 2: Backup & Prepare -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 2: Backup & Database Preparation</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Critical Step:</strong> A complete backup will be created before making any changes.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Backup Options</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="backup_database" checked>
                                <label class="form-check-label" for="backup_database">
                                    Create database backup
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="backup_files" checked>
                                <label class="form-check-label" for="backup_files">
                                    Create files backup
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Backup Location</h5>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="backup_location" 
                                       value="../backups/migration_<?= date('Y-m-d_H-i-s') ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div id="backupProgress" style="display: none;">
                        <h5>Backup Progress</h5>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="backupStatus">Preparing backup...</div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="?step=1" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Previous
                        </a>
                        <button class="btn btn-warning" onclick="createBackup()">
                            <i class="fas fa-save"></i> Create Backup & Continue
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($step == 3): ?>
            <!-- Step 3: Create Default Company -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 3: Create Default Company</h3>
                </div>
                <div class="card-body">
                    <p>Set up the primary company that will contain all existing data.</p>

                    <form id="defaultCompanyForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control" name="company_name" 
                                           placeholder="Your Company Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company Slug</label>
                                    <input type="text" class="form-control" name="company_slug" 
                                           placeholder="your-company" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primary Email</label>
                                    <input type="email" class="form-control" name="company_email" 
                                           placeholder="contact@company.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="company_phone" 
                                           placeholder="+44 20 1234 5678">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="company_address" rows="3"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            All existing users, officers, sites, and shifts will be assigned to this company.
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=2" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Previous
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Create Company & Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($step == 4): ?>
            <!-- Step 4: Migrate Data -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 4: Data Migration</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Data Migration in Progress:</strong> Do not close this window or navigate away.
                    </div>

                    <div id="migrationProgress">
                        <div class="mb-3">
                            <h5>Migration Steps</h5>
                            <div class="list-group">
                                <div class="list-group-item" id="step-companies">
                                    <i class="fas fa-clock text-muted"></i> Create companies table
                                </div>
                                <div class="list-group-item" id="step-schema">
                                    <i class="fas fa-clock text-muted"></i> Update database schema
                                </div>
                                <div class="list-group-item" id="step-users">
                                    <i class="fas fa-clock text-muted"></i> Migrate users data
                                </div>
                                <div class="list-group-item" id="step-officers">
                                    <i class="fas fa-clock text-muted"></i> Migrate officers data
                                </div>
                                <div class="list-group-item" id="step-sites">
                                    <i class="fas fa-clock text-muted"></i> Migrate sites data
                                </div>
                                <div class="list-group-item" id="step-shifts">
                                    <i class="fas fa-clock text-muted"></i> Migrate shifts data
                                </div>
                                <div class="list-group-item" id="step-cleanup">
                                    <i class="fas fa-clock text-muted"></i> Cleanup and validation
                                </div>
                            </div>
                        </div>

                        <div class="progress mb-3">
                            <div class="progress-bar" id="migrationProgressBar" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <div id="migrationStatus">Ready to start migration...</div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="?step=3" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Previous
                        </a>
                        <button class="btn btn-success" onclick="startMigration()" id="startMigrationBtn">
                            <i class="fas fa-play"></i> Start Migration
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($step == 5): ?>
            <!-- Step 5: Verification -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 5: Migration Verification</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Migration Complete!</strong> Your system has been successfully converted to multi-tenant.
                    </div>

                    <div id="verificationResults">
                        <button class="btn btn-primary" onclick="runVerification()">
                            <i class="fas fa-check"></i> Run Verification Tests
                        </button>
                    </div>

                    <div id="verificationReport" style="display: none;">
                        <h5>Migration Summary</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td>Companies Created</td>
                                        <td id="companies-migrated">-</td>
                                    </tr>
                                    <tr>
                                        <td>Users Migrated</td>
                                        <td id="users-migrated">-</td>
                                    </tr>
                                    <tr>
                                        <td>Officers Migrated</td>
                                        <td id="officers-migrated">-</td>
                                    </tr>
                                    <tr>
                                        <td>Sites Migrated</td>
                                        <td id="sites-migrated">-</td>
                                    </tr>
                                    <tr>
                                        <td>Shifts Migrated</td>
                                        <td id="shifts-migrated">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info">
                            <h5>Next Steps</h5>
                            <ul class="mb-0">
                                <li>Review the migration results above</li>
                                <li>Test key functionality with existing users</li>
                                <li>Configure additional companies if needed</li>
                                <li>Set up company-specific branding</li>
                                <li>Train administrators on multi-tenant features</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="../root/root_dashboard.php" class="btn btn-success">
                                <i class="fas fa-tachometer-alt"></i> Go to Super Admin Dashboard
                            </a>
                            <a href="../dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home"></i> Go to Main Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let migrationInProgress = false;

// Auto-generate slug
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="company_name"]');
    const slugInput = document.querySelector('input[name="company_slug"]');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            slugInput.value = slug;
        });
    }
});

function runCompatibilityCheck() {
    document.getElementById('checkResults').style.display = 'block';
    
    fetch('../api/migration/compatibility_check.php')
        .then(response => response.json())
        .then(data => {
            updateCheckStatus('php-version', data.php_version);
            updateCheckStatus('mysql-version', data.mysql_version);
            updateCheckStatus('backup-writable', data.backup_writable);
            updateCheckStatus('existing-data', data.existing_data);
            updateCheckStatus('companies-table', data.companies_table);
            
            if (data.all_checks_passed) {
                document.getElementById('nextBtn').disabled = false;
            }
        })
        .catch(error => {
            console.error('Compatibility check failed:', error);
        });
}

function updateCheckStatus(id, status) {
    const row = document.getElementById(id);
    const icon = row.querySelector('i');
    const statusCell = row.cells[1];
    
    if (status.passed) {
        icon.className = 'fas fa-check-circle text-success';
        statusCell.innerHTML = `<span class="text-success">${status.message}</span>`;
    } else {
        icon.className = 'fas fa-times-circle text-danger';
        statusCell.innerHTML = `<span class="text-danger">${status.message}</span>`;
    }
}

function createBackup() {
    document.getElementById('backupProgress').style.display = 'block';
    
    fetch('../api/migration/create_backup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            backup_database: document.getElementById('backup_database').checked,
            backup_files: document.getElementById('backup_files').checked,
            backup_location: document.getElementById('backup_location').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '?step=3';
        } else {
            alert('Backup failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Backup failed:', error);
        alert('Backup failed. Please check the console for details.');
    });
}

// Handle default company form
document.getElementById('defaultCompanyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    fetch('../api/migration/create_default_company.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '?step=4';
        } else {
            alert('Error creating company: ' + data.message);
        }
    });
});

function startMigration() {
    if (migrationInProgress) return;
    
    migrationInProgress = true;
    document.getElementById('startMigrationBtn').disabled = true;
    
    const steps = [
        'step-companies',
        'step-schema', 
        'step-users',
        'step-officers',
        'step-sites',
        'step-shifts',
        'step-cleanup'
    ];
    
    let currentStep = 0;
    
    function runNextStep() {
        if (currentStep >= steps.length) {
            window.location.href = '?step=5';
            return;
        }
        
        const stepId = steps[currentStep];
        const stepElement = document.getElementById(stepId);
        
        // Mark as in progress
        stepElement.querySelector('i').className = 'fas fa-spinner fa-spin text-primary';
        
        fetch('../api/migration/migrate_step.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ step: currentStep + 1 })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mark as complete
                stepElement.querySelector('i').className = 'fas fa-check-circle text-success';
                
                // Update progress
                const progress = ((currentStep + 1) / steps.length) * 100;
                document.getElementById('migrationProgressBar').style.width = progress + '%';
                document.getElementById('migrationStatus').textContent = data.message;
                
                currentStep++;
                setTimeout(runNextStep, 1000);
            } else {
                // Mark as failed
                stepElement.querySelector('i').className = 'fas fa-times-circle text-danger';
                alert('Migration failed: ' + data.message);
                migrationInProgress = false;
            }
        })
        .catch(error => {
            console.error('Migration step failed:', error);
            stepElement.querySelector('i').className = 'fas fa-times-circle text-danger';
            migrationInProgress = false;
        });
    }
    
    runNextStep();
}

function runVerification() {
    document.getElementById('verificationReport').style.display = 'block';
    
    fetch('../api/migration/verify_migration.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('companies-migrated').textContent = data.companies || '0';
            document.getElementById('users-migrated').textContent = data.users || '0';
            document.getElementById('officers-migrated').textContent = data.officers || '0';
            document.getElementById('sites-migrated').textContent = data.sites || '0';
            document.getElementById('shifts-migrated').textContent = data.shifts || '0';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
