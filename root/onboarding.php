<?php
/**
 * Company Onboarding Wizard
 * Step-by-step company creation process with validation and setup
 */
$page_title = 'Company Onboarding Wizard';
require_once '../config/config.php';

// Start session and check authentication
session_start();
requireSuperAdmin();

require_once '../config/database.php';

$step = $_GET['step'] ?? 1;
$max_steps = 4;

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-building-user"></i> Company Onboarding Wizard
                </h1>
                <div class="page-subtitle">Step-by-step company setup and configuration</div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="steps">
                        <div class="step-item <?= $step >= 1 ? 'active' : '' ?>">
                            <div class="step-counter">1</div>
                            <div class="step-title">Company Details</div>
                        </div>
                        <div class="step-item <?= $step >= 2 ? 'active' : '' ?>">
                            <div class="step-counter">2</div>
                            <div class="step-title">Admin User</div>
                        </div>
                        <div class="step-item <?= $step >= 3 ? 'active' : '' ?>">
                            <div class="step-counter">3</div>
                            <div class="step-title">Subscription & Limits</div>
                        </div>
                        <div class="step-item <?= $step >= 4 ? 'active' : '' ?>">
                            <div class="step-counter">4</div>
                            <div class="step-title">Review & Create</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-body">
                    <form id="onboardingForm" data-step="<?= $step ?>">
                        
                        <?php if ($step == 1): ?>
                        <!-- Step 1: Company Details -->
                        <div class="step-content" id="step1">
                            <h3 class="mb-4">Company Information</h3>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label required">Company Name</label>
                                        <input type="text" class="form-control" name="company_name" 
                                               placeholder="Enter company name" required>
                                        <div class="form-hint">Full legal name of the company</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Company Slug</label>
                                        <input type="text" class="form-control" name="company_slug" 
                                               placeholder="company-name" required readonly>
                                        <div class="form-hint">URL-friendly identifier</div>
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
                                        <label class="form-label">Phone Number</label>
                                        <div class="phone-input-group" id="company-phone-input"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Company Address</label>
                                <textarea class="form-control" name="company_address" rows="3" 
                                          placeholder="Enter complete address..."></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Industry/Sector</label>
                                        <select class="form-select" name="industry">
                                            <option value="">Select industry</option>
                                            <option value="security">Security Services</option>
                                            <option value="cleaning">Cleaning Services</option>
                                            <option value="facilities">Facilities Management</option>
                                            <option value="retail">Retail</option>
                                            <option value="healthcare">Healthcare</option>
                                            <option value="education">Education</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Company Size</label>
                                        <select class="form-select" name="company_size">
                                            <option value="">Select size</option>
                                            <option value="small">Small (1-10 employees)</option>
                                            <option value="medium">Medium (11-50 employees)</option>
                                            <option value="large">Large (51-200 employees)</option>
                                            <option value="enterprise">Enterprise (200+ employees)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($step == 2): ?>
                        <!-- Step 2: Admin User -->
                        <div class="step-content" id="step2">
                            <h3 class="mb-4">Administrator Account</h3>
                            <p class="text-muted mb-4">Create the primary administrator account for this company. This user will have full access to manage the company's data and settings.</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Admin Username</label>
                                        <input type="text" class="form-control" name="admin_username" 
                                               placeholder="admin" required>
                                        <div class="form-hint">Used for login (must be unique)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Admin Email</label>
                                        <input type="email" class="form-control" name="admin_email" 
                                               placeholder="admin@company.com" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Admin First Name</label>
                                        <input type="text" class="form-control" name="admin_first_name" 
                                               placeholder="John">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Admin Last Name</label>
                                        <input type="text" class="form-control" name="admin_last_name" 
                                               placeholder="Smith">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Initial Password</label>
                                        <input type="password" class="form-control" name="admin_password" 
                                               placeholder="Enter secure password" required>
                                        <div class="form-hint">Admin can change this after first login</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Confirm Password</label>
                                        <input type="password" class="form-control" name="admin_password_confirm" 
                                               placeholder="Confirm password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Admin Mobile Number</label>
                                <div class="phone-input-group" id="admin-mobile-input"></div>
                                <div class="form-hint">Optional: For SMS notifications and mobile login</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($step == 3): ?>
                        <!-- Step 3: Subscription & Limits -->
                        <div class="step-content" id="step3">
                            <h3 class="mb-4">Subscription Plan & Limits</h3>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-4">
                                        <label class="form-label">Subscription Plan</label>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card subscription-plan" data-plan="basic">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title">Basic</h5>
                                                        <div class="h1 text-primary">Free</div>
                                                        <ul class="list-unstyled text-start">
                                                            <li><i class="fas fa-check text-success"></i> Up to 25 officers</li>
                                                            <li><i class="fas fa-check text-success"></i> Up to 10 sites</li>
                                                            <li><i class="fas fa-check text-success"></i> Basic reporting</li>
                                                            <li><i class="fas fa-check text-success"></i> Email support</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card subscription-plan border-primary" data-plan="professional">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title">Professional</h5>
                                                        <div class="h1 text-primary">£29/mo</div>
                                                        <ul class="list-unstyled text-start">
                                                            <li><i class="fas fa-check text-success"></i> Up to 100 officers</li>
                                                            <li><i class="fas fa-check text-success"></i> Up to 50 sites</li>
                                                            <li><i class="fas fa-check text-success"></i> Advanced reporting</li>
                                                            <li><i class="fas fa-check text-success"></i> Priority support</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card subscription-plan" data-plan="enterprise">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title">Enterprise</h5>
                                                        <div class="h1 text-primary">£99/mo</div>
                                                        <ul class="list-unstyled text-start">
                                                            <li><i class="fas fa-check text-success"></i> Unlimited officers</li>
                                                            <li><i class="fas fa-check text-success"></i> Unlimited sites</li>
                                                            <li><i class="fas fa-check text-success"></i> Custom features</li>
                                                            <li><i class="fas fa-check text-success"></i> 24/7 support</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="subscription_plan" value="professional">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Maximum Officers</label>
                                        <input type="number" class="form-control" name="max_officers" 
                                               value="100" min="1" max="9999">
                                        <div class="form-hint">Set based on subscription plan</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Maximum Sites</label>
                                        <input type="number" class="form-control" name="max_sites" 
                                               value="50" min="1" max="9999">
                                        <div class="form-hint">Set based on subscription plan</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="trial_period" id="trial_period" checked>
                                    <label class="form-check-label" for="trial_period">
                                        Start with 30-day free trial
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($step == 4): ?>
                        <!-- Step 4: Review & Create -->
                        <div class="step-content" id="step4">
                            <h3 class="mb-4">Review & Confirm</h3>
                            <p class="text-muted mb-4">Please review the information below and confirm to create the company.</p>
                            
                            <div id="reviewContent">
                                <!-- Content will be populated via JavaScript -->
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>What happens next?</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Company account will be created with all specified settings</li>
                                    <li>Administrator user will be created and can log in immediately</li>
                                    <li>Company will be set to "active" status</li>
                                    <li>Email notification will be sent to the administrator</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Navigation Buttons -->
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <?php if ($step > 1): ?>
                                <a href="?step=<?= $step - 1 ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($step < $max_steps): ?>
                                <button type="button" class="btn btn-primary" onclick="nextStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                                <?php else: ?>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Create Company
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 40px;
    right: 40px;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    background: white;
    padding: 0 15px;
}

.step-counter {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
}

.step-item.active .step-counter {
    background: #0066cc;
    color: white;
}

.step-title {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: center;
}

.step-item.active .step-title {
    color: #0066cc;
    font-weight: 500;
}

.subscription-plan {
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.subscription-plan:hover {
    border-color: #0066cc;
    transform: translateY(-2px);
}

.subscription-plan.selected {
    border-color: #0066cc;
    background: #f8f9fa;
}

.form-label.required::after {
    content: ' *';
    color: #dc3545;
}

.phone-input-container .input-group {
    display: flex;
    align-items: stretch;
}

.phone-input-container .country-selector {
    flex: 0 0 120px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: 0;
    font-size: 0.875rem;
}

.phone-input-container .phone-input {
    flex: 1;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.phone-input-container .form-select:focus,
.phone-input-container .form-control:focus {
    border-color: #0066cc;
    box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
}

.phone-input-wrapper .input-group {
    display: flex;
    align-items: stretch;
}

.phone-input-wrapper .country-selector {
    flex: 0 0 120px;
    border-radius: 0.375rem 0 0 0.375rem;
    border-right: 0;
    font-size: 0.875rem;
}
</style>

<link rel="stylesheet" href="../assets/css/simple-phone.css">
<script src="../assets/js/simple-phone.js"></script>
<script>
let wizardData = {};

// Auto-generate company slug
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
    
    // Handle subscription plan selection
    document.querySelectorAll('.subscription-plan').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.subscription-plan').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            document.querySelector('input[name="subscription_plan"]').value = this.dataset.plan;
            
            // Update limits based on plan
            const limits = {
                basic: { officers: 25, sites: 10 },
                professional: { officers: 100, sites: 50 },
                enterprise: { officers: 9999, sites: 9999 }
            };
            
            const plan = this.dataset.plan;
            document.querySelector('input[name="max_officers"]').value = limits[plan].officers;
            document.querySelector('input[name="max_sites"]').value = limits[plan].sites;
        });
    });
    
    // Initialize simple phone inputs
    enhancePhoneInputs();
    
    // Load existing data if available
    loadWizardData();
});

function nextStep() {
    if (validateCurrentStep()) {
        saveCurrentStep();
        const currentStep = parseInt(document.getElementById('onboardingForm').dataset.step);
        window.location.href = '?step=' + (currentStep + 1);
    }
}

function validateCurrentStep() {
    const currentStep = parseInt(document.getElementById('onboardingForm').dataset.step);
    const form = document.getElementById('onboardingForm');
    const requiredFields = form.querySelectorAll('[required]');
    
    let isValid = true;
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Step-specific validation
    if (currentStep === 2) {
        const password = form.querySelector('input[name="admin_password"]').value;
        const confirmPassword = form.querySelector('input[name="admin_password_confirm"]').value;
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            isValid = false;
        }
    }
    
    return isValid;
}

function saveCurrentStep() {
    const form = document.getElementById('onboardingForm');
    const formData = new FormData(form);
    
    formData.forEach((value, key) => {
        wizardData[key] = value;
    });
    
    localStorage.setItem('companyWizardData', JSON.stringify(wizardData));
}

function loadWizardData() {
    const saved = localStorage.getItem('companyWizardData');
    if (saved) {
        wizardData = JSON.parse(saved);
        
        // Populate form fields
        Object.keys(wizardData).forEach(key => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = wizardData[key];
            }
        });
        
        // Update review content if on step 4
        if (parseInt(document.getElementById('onboardingForm').dataset.step) === 4) {
            updateReviewContent();
        }
    }
}

function updateReviewContent() {
    const reviewDiv = document.getElementById('reviewContent');
    if (!reviewDiv) return;
    
    reviewDiv.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Company Information</h5>
                <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${wizardData.company_name || 'Not set'}</td></tr>
                    <tr><td><strong>Slug:</strong></td><td>${wizardData.company_slug || 'Not set'}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${wizardData.company_email || 'Not set'}</td></tr>
                    <tr><td><strong>Phone:</strong></td><td>${wizardData.company_phone || 'Not set'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Administrator</h5>
                <table class="table table-sm">
                    <tr><td><strong>Username:</strong></td><td>${wizardData.admin_username || 'Not set'}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${wizardData.admin_email || 'Not set'}</td></tr>
                    <tr><td><strong>Name:</strong></td><td>${(wizardData.admin_first_name || '') + ' ' + (wizardData.admin_last_name || '')}</td></tr>
                </table>
                
                <h5 class="mt-3">Subscription</h5>
                <table class="table table-sm">
                    <tr><td><strong>Plan:</strong></td><td>${wizardData.subscription_plan || 'basic'}</td></tr>
                    <tr><td><strong>Max Officers:</strong></td><td>${wizardData.max_officers || '25'}</td></tr>
                    <tr><td><strong>Max Sites:</strong></td><td>${wizardData.max_sites || '10'}</td></tr>
                </table>
            </div>
        </div>
    `;
}

// Handle form submission
document.getElementById('onboardingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    saveCurrentStep();
    
    // Submit to API
    fetch('../api/super_admin/create_company_wizard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(wizardData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            localStorage.removeItem('companyWizardData');
            alert('Company created successfully!');
            window.location.href = 'super_admin_dashboard.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the company.');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
