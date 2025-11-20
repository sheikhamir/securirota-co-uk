<?php
$page_title = 'Email Template Management';
require_once '../includes/header.php';

// Only allow admin access
if (!isAdmin()) {
    header('Location: ../dashboard.php');
    exit();
}
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-envelope-open-text"></i> Email Template Management</h3>
                <div>
                    <button class="btn btn-success btn-sm" onclick="showTestEmailModal()">
                        <i class="fas fa-paper-plane"></i> Send Test Email
                    </button>
                    <a href="../dashboard.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Template Selection Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Templates</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('pin_generation')" id="template-pin_generation">
                            <i class="fas fa-key"></i> PIN Generation
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('shift_reminder')" id="template-shift_reminder">
                            <i class="fas fa-bell"></i> Shift Reminder
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('welcome_officer')" id="template-welcome_officer">
                            <i class="fas fa-user-plus"></i> Welcome Officer
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('password_reset')" id="template-password_reset">
                            <i class="fas fa-lock"></i> Password Reset
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('shift_assigned')" id="template-shift_assigned">
                            <i class="fas fa-calendar-plus"></i> Shift Assignment
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('shift_cancelled')" id="template-shift_cancelled">
                            <i class="fas fa-calendar-times"></i> Shift Cancellation
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9">
            <!-- Welcome Message -->
            <div id="welcomeMessage" class="card">
                <div class="card-body text-center">
                    <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                    <h4>Email Template Management</h4>
                    <p class="text-muted">Select a template from the sidebar to start editing</p>
                </div>
            </div>

            <!-- Template Editor -->
            <div id="templateEditor" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-edit"></i> <span id="currentTemplateName">Email Template</span></h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-sm" onclick="saveTemplate()" id="saveBtn">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-info btn-sm" onclick="showBrandedPreview()" id="previewBtn">
                                    <i class="fas fa-eye"></i> Preview Email
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="resetToDefault()" id="resetBtn">
                                    <i class="fas fa-undo"></i> Reset to Default
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Email Subject -->
                                <div class="mb-3">
                                    <label for="emailSubject" class="form-label">Email Subject:</label>
                                    <input type="text" class="form-control" id="emailSubject" placeholder="Enter email subject">
                                </div>

                                <!-- Editor Mode Toggle -->
                                <div class="mb-3">
                                    <label class="form-label">Email Body:</label>
                                    <div class="btn-group w-100 mb-2" role="group">
                                        <button type="button" class="btn btn-outline-primary active" id="textMode" onclick="switchToTextMode()">
                                            <i class="fas fa-align-left"></i> Text
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="htmlMode" onclick="switchToHTMLMode()">
                                            <i class="fas fa-code"></i> HTML
                                        </button>
                                    </div>
                                </div>

                                <!-- Text Editor -->
                                <div id="textEditor">
                                    <textarea class="form-control" id="emailBody" rows="12" placeholder="Enter email content"></textarea>
                                </div>

                                <!-- HTML Editor -->
                                <div id="htmlEditor" style="display: none;">
                                    <textarea class="form-control" id="htmlContent" rows="12" placeholder="Enter HTML content"></textarea>
                                </div>

                                <!-- Tips Section -->
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> <strong>Tips:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Use variables like {{officer_name}} for dynamic content</li>
                                        <li>Click on variable tags to insert them automatically</li>
                                        <li>Start with Text mode for simple, clean emails</li>
                                        <li>Switch to HTML mode if you need rich formatting</li>
                                        <li>Changes are saved automatically to the database</li>
                                        <li>Use the preview to see how emails will look</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Live Preview -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6><i class="fas fa-eye"></i> Live Preview:</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="emailPreview">
                                            <div class="text-muted">
                                                <strong>Subject:</strong> <span id="previewSubject">No subject</span>
                                            </div>
                                            <hr>
                                            <div id="previewBody">No content to preview</div>
                                            <hr>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i> Preview shows sample data for variables
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Variables Card -->
                <div class="card mt-3" id="variablesCard" style="display: none;">
                    <div class="card-header">
                        <h6><i class="fas fa-tags"></i> Available Variables</h6>
                        <small class="text-muted">Click to Insert into template:</small>
                    </div>
                    <div class="card-body">
                        <div id="availableVariables">
                            <!-- Variables will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testEmailAddress" class="form-label">Send to Email Address:</label>
                    <input type="email" class="form-control" id="testEmailAddress" placeholder="Enter email address" required>
                </div>
                <div class="mb-3">
                    <label for="testTemplateSelect" class="form-label">Select Template:</label>
                    <select class="form-select" id="testTemplateSelect">
                        <option value="pin_generation">PIN Generation</option>
                        <option value="shift_reminder">Shift Reminder</option>
                        <option value="welcome_officer">Welcome Officer</option>
                        <option value="password_reset">Password Reset</option>
                        <option value="shift_assigned">Shift Assignment</option>
                        <option value="shift_cancelled">Shift Cancellation</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmail()">Send Test Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Preview Modal -->
<div class="modal fade" id="emailPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope"></i> Email Preview - <span id="previewModalTitle">Template</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div style="background: #f5f5f5; padding: 20px;">
                    <iframe id="emailPreviewFrame" style="width: 100%; height: 600px; border: none; border-radius: 8px; background: white;"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="showTestEmailModal()">Send Test Email</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTemplate = null;
let isHtmlMode = false; // Start in text mode

document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for live preview
    const subjectField = document.getElementById('emailSubject');
    const textEditor = document.getElementById('emailBody');
    const htmlEditor = document.getElementById('htmlContent');
    
    if (subjectField) {
        subjectField.addEventListener('input', updatePreview);
    }
    if (textEditor) {
        textEditor.addEventListener('input', updatePreview);
    }
    if (htmlEditor) {
        htmlEditor.addEventListener('input', updatePreview);
    }
});

function loadTemplate(templateType) {
    console.log('Loading template:', templateType);
    
    // Update active state in sidebar
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const templateButton = document.getElementById('template-' + templateType);
    if (templateButton) {
        templateButton.classList.add('active');
    }
    
    // Show editor and hide welcome message
    const templateEditor = document.getElementById('templateEditor');
    const welcomeMessage = document.getElementById('welcomeMessage');
    const variablesCard = document.getElementById('variablesCard');
    
    if (templateEditor) templateEditor.style.display = 'block';
    if (welcomeMessage) welcomeMessage.style.display = 'none';
    if (variablesCard) variablesCard.style.display = 'block';
    
    currentTemplate = templateType;
    
    // Update template name
    const templateNames = {
        'pin_generation': 'PIN Generation Email',
        'shift_reminder': 'Shift Reminder Email', 
        'welcome_officer': 'Welcome Officer Email',
        'password_reset': 'Password Reset Email',
        'shift_assigned': 'Shift Assignment Email',
        'shift_cancelled': 'Shift Cancellation Email'
    };
    
    const currentTemplateName = document.getElementById('currentTemplateName');
    if (currentTemplateName) {
        currentTemplateName.textContent = templateNames[templateType] || 'Email Template';
    }
    
    // Load template from API
    const timestamp = new Date().getTime();
    fetch(`../api/email_templates.php?type=${templateType}&_t=${timestamp}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            console.log('Template data received:', data);
            if (data.success) {
                const template = data.data;
                console.log('Template object:', template);
                console.log('Template subject:', template.subject);
                console.log('Template type:', template.template_type);
                
                const emailSubject = document.getElementById('emailSubject');
                const emailBody = document.getElementById('emailBody');
                const htmlContent = document.getElementById('htmlContent');
                
                if (emailSubject) emailSubject.value = template.subject || '';
                if (emailBody) emailBody.value = template.body || '';
                if (htmlContent) htmlContent.value = template.body || '';
                
                // Set mode based on template
                if (template.is_html === '1' || template.is_html === 1) {
                    switchToHTMLMode();
                } else {
                    switchToTextMode();
                }
                
                // Show available variables
                const availableVariables = document.getElementById('availableVariables');
                if (availableVariables) {
                    if (template.variables && template.variables.length > 0) {
                        availableVariables.innerHTML = template.variables.map(variable => 
                            `<span class="badge bg-primary me-1 mb-1" onclick="insertVariable('${variable}')" style="cursor: pointer;">${variable}</span>`
                        ).join('');
                    } else {
                        // Fallback to default variables
                        const defaultVars = getDefaultVariables(templateType);
                        availableVariables.innerHTML = defaultVars.map(variable => 
                            `<span class="badge bg-primary me-1 mb-1" onclick="insertVariable('${variable}')" style="cursor: pointer;">${variable}</span>`
                        ).join('');
                    }
                }
                
                updatePreview();
            } else {
                console.error('Error loading template:', data.message);
                showNotification('Error loading template: ' + data.message, 'error');
                loadDefaultTemplate(templateType);
            }
        })
        .catch(error => {
            console.error('Error loading template:', error);
            showNotification('Error loading template', 'error');
            loadDefaultTemplate(templateType);
        });
}

function loadDefaultTemplate(templateType) {
    const templates = {
        pin_generation: {
            subject: 'Your Security PIN - {{company_name}}',
            body: `Dear {{officer_name}},

Your security PIN has been generated for accessing the officer portal.

PIN: {{pin_code}}

Please keep this PIN secure and do not share it with anyone. You can use this PIN to:
- Access your shift schedules
- Check in/out of shifts
- View your profile information

If you have any questions or need assistance, please contact us.

Best regards,
{{company_name}} Team`
        },
        shift_reminder: {
            subject: 'Shift Reminder - {{site_name}} on {{shift_date}}',
            body: `Dear {{officer_name}},

This is a reminder about your upcoming shift:

Site: {{site_name}}
Date: {{shift_date}}
Time: {{shift_time}}

Please make sure to arrive on time and bring all necessary equipment.

If you cannot make this shift, please contact us as soon as possible.

Best regards,
{{company_name}} Team`
        },
        welcome_officer: {
            subject: 'Welcome to {{company_name}} - Officer Portal Access',
            body: `Dear {{officer_name}},

Welcome to {{company_name}}! We're excited to have you join our security team.

Your officer portal account has been created with the following details:
- Username: {{login_url}}
- Temporary Password: {{password}}

Please log in and change your password on first access.

You can access the portal at: {{login_url}}

If you have any questions, please don't hesitate to contact us.

Best regards,
{{company_name}} Team`
        },
        password_reset: {
            subject: 'Password Reset Request - {{company_name}}',
            body: `Dear {{officer_name}},

We received a request to reset your password for the officer portal.

Your new temporary password is: {{new_password}}

Please log in using this temporary password and change it immediately for security purposes.

Login URL: {{login_url}}

If you did not request this password reset, please contact us immediately.

Best regards,
{{company_name}} Team`
        },
        shift_assigned: {
            subject: 'New Shift Assignment - {{site_name}} on {{shift_date}}',
            body: `Dear {{officer_name}},

You have been assigned to a new shift:

Site: {{site_name}}
Date: {{shift_date}}
Time: {{shift_start_time}} - {{shift_end_time}}
Rate: ${{hourly_rate}}/hour

Please confirm your availability and make note of any special instructions for this site.

If you have any questions or concerns about this assignment, please contact us.

Best regards,
{{company_name}} Team`
        },
        shift_cancelled: {
            subject: 'Shift Cancellation - {{site_name}} on {{shift_date}}',
            body: `Dear {{officer_name}},

Your scheduled shift has been cancelled:

Site: {{site_name}}
Date: {{shift_date}}
Time: {{shift_time}}

We apologize for any inconvenience this may cause. We will do our best to find alternative shifts for you.

If you have any questions, please contact us.

Best regards,
{{company_name}} Team`
        }
    };
    
    if (templates[templateType]) {
        const template = templates[templateType];
        const emailSubject = document.getElementById('emailSubject');
        const emailBody = document.getElementById('emailBody');
        const htmlContent = document.getElementById('htmlContent');
        const availableVariables = document.getElementById('availableVariables');
        
        if (emailSubject) emailSubject.value = template.subject;
        if (emailBody) emailBody.value = template.body;
        if (htmlContent) htmlContent.value = template.body;
        
        // Show default variables
        const defaultVars = getDefaultVariables(templateType);
        if (availableVariables) {
            availableVariables.innerHTML = defaultVars.map(variable => 
                `<span class="badge bg-primary me-1 mb-1" onclick="insertVariable('${variable}')" style="cursor: pointer;">${variable}</span>`
            ).join('');
        }
        updatePreview();
    }
}

function getDefaultVariables(templateType) {
    const commonVars = ['{{company_name}}', '{{officer_name}}', '{{login_url}}'];
    const specificVars = {
        'pin_generation': ['{{pin_code}}'],
        'shift_reminder': ['{{site_name}}', '{{shift_date}}', '{{shift_time}}'],
        'welcome_officer': ['{{password}}'],
        'password_reset': ['{{new_password}}'],
        'shift_assigned': ['{{site_name}}', '{{shift_date}}', '{{shift_start_time}}', '{{shift_end_time}}', '{{hourly_rate}}'],
        'shift_cancelled': ['{{site_name}}', '{{shift_date}}', '{{shift_time}}']
    };
    
    return [...commonVars, ...(specificVars[templateType] || [])];
}

function switchToTextMode() {
    isHtmlMode = false;
    document.getElementById('textEditor').style.display = 'block';
    document.getElementById('htmlEditor').style.display = 'none';
    document.getElementById('textMode').classList.add('active');
    document.getElementById('htmlMode').classList.remove('active');
    
    // Copy content from HTML to text if switching
    const htmlContent = document.getElementById('htmlContent').value;
    if (htmlContent && !document.getElementById('emailBody').value) {
        document.getElementById('emailBody').value = htmlContent;
    }
    
    updatePreview();
}

function switchToHTMLMode() {
    isHtmlMode = true;
    document.getElementById('textEditor').style.display = 'none';
    document.getElementById('htmlEditor').style.display = 'block';
    document.getElementById('htmlMode').classList.add('active');
    document.getElementById('textMode').classList.remove('active');
    
    // Copy content from text to HTML if switching
    const textContent = document.getElementById('emailBody').value;
    if (textContent && !document.getElementById('htmlContent').value) {
        document.getElementById('htmlContent').value = textContent;
    }
    
    updatePreview();
}

function insertVariable(variable) {
    const activeEditor = isHtmlMode ? document.getElementById('htmlContent') : document.getElementById('emailBody');
    const startPos = activeEditor.selectionStart;
    const endPos = activeEditor.selectionEnd;
    const currentValue = activeEditor.value;
    
    activeEditor.value = currentValue.substring(0, startPos) + variable + currentValue.substring(endPos);
    activeEditor.focus();
    activeEditor.setSelectionRange(startPos + variable.length, startPos + variable.length);
    
    updatePreview();
}

function updatePreview() {
    const emailSubjectEl = document.getElementById('emailSubject');
    const emailBodyEl = document.getElementById('emailBody');
    const htmlContentEl = document.getElementById('htmlContent');
    const previewSubjectEl = document.getElementById('previewSubject');
    const previewBodyEl = document.getElementById('previewBody');
    
    if (!emailSubjectEl || !previewSubjectEl || !previewBodyEl) {
        return; // Elements not ready yet
    }
    
    const subject = emailSubjectEl.value || '';
    const body = isHtmlMode && htmlContentEl ? htmlContentEl.value : (emailBodyEl ? emailBodyEl.value : '');
    
    // Sample data for preview
    const sampleData = {
        '{{company_name}}': 'SecuriRota',
        '{{officer_name}}': 'John Doe',
        '{{site_name}}': 'Downtown Mall',
        '{{shift_date}}': '2025-08-30',
        '{{shift_time}}': '09:00 - 17:00',
        '{{shift_start_time}}': '09:00',
        '{{shift_end_time}}': '17:00',
        '{{shift_duration}}': '8 hours',
        '{{hourly_rate}}': '25.00',
        '{{pin_code}}': '1234',
        '{{password}}': 'TempPass123',
        '{{new_password}}': 'NewPass456',
        '{{login_url}}': 'https://rohab.ae/rota'
    };
    
    let previewSubject = subject;
    let previewBody = body;
    
    // Replace variables with sample data
    Object.keys(sampleData).forEach(variable => {
        const regex = new RegExp(escapeRegExp(variable), 'g');
        previewSubject = previewSubject.replace(regex, sampleData[variable]);
        previewBody = previewBody.replace(regex, sampleData[variable]);
    });
    
    previewSubjectEl.textContent = previewSubject || 'No subject';
    
    if (isHtmlMode) {
        previewBodyEl.innerHTML = previewBody || 'No content to preview';
    } else {
        previewBodyEl.innerHTML = previewBody.replace(/\n/g, '<br>') || 'No content to preview';
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function saveTemplate() {
    if (!currentTemplate) {
        showNotification('No template selected', 'error');
        return;
    }
    
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    const subject = document.getElementById('emailSubject').value;
    const body = isHtmlMode ? document.getElementById('htmlContent').value : document.getElementById('emailBody').value;
    
    const templateData = {
        template_type: currentTemplate,
        subject: subject,
        body: body,
        is_html: isHtmlMode ? 1 : 0
    };
    
    fetch('../api/email_templates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(templateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Template saved successfully!', 'success');
        } else {
            showNotification('Error saving template: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving template:', error);
        showNotification('Error saving template. Please try again.', 'error');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function resetToDefault() {
    if (!currentTemplate) {
        showNotification('No template selected', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to reset this template to default? This will lose all your current changes.')) {
        return;
    }
    
    const resetBtn = document.getElementById('resetBtn');
    const originalText = resetBtn.innerHTML;
    resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
    resetBtn.disabled = true;
    
    fetch(`../api/email_templates.php?type=${currentTemplate}&action=reset`, {
        method: 'DELETE',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Template reset to default successfully!', 'success');
            loadTemplate(currentTemplate);
        } else {
            showNotification('Error resetting template: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error resetting template:', error);
        showNotification('Error resetting template. Please try again.', 'error');
    })
    .finally(() => {
        resetBtn.innerHTML = originalText;
        resetBtn.disabled = false;
    });
}

function showTestEmailModal() {
    const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
    
    // Set the current template in the select
    if (currentTemplate) {
        document.getElementById('testTemplateSelect').value = currentTemplate;
    }
    
    modal.show();
}

function showBrandedPreview() {
    if (!currentTemplate) {
        showNotification('Please select a template first', 'error');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('emailPreviewModal'));
    const iframe = document.getElementById('emailPreviewFrame');
    const modalTitle = document.getElementById('previewModalTitle');
    
    // Set modal title
    const templateNames = {
        'pin_generation': 'PIN Generation Email',
        'shift_reminder': 'Shift Reminder Email',
        'welcome_officer': 'Welcome Officer Email',
        'password_reset': 'Password Reset Email',
        'shift_assigned': 'Shift Assignment Email',
        'shift_cancelled': 'Shift Cancellation Email'
    };
    modalTitle.textContent = templateNames[currentTemplate] || 'Email Template';
    
    // Generate preview HTML
    const previewHTML = generateBrandedEmailPreview();
    
    // Set iframe content
    iframe.onload = function() {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(previewHTML);
        doc.close();
    };
    
    // Trigger load
    iframe.src = 'about:blank';
    
    modal.show();
}

function generateBrandedEmailPreview() {
    const subject = document.getElementById('emailSubject')?.value || 'Email Subject';
    const body = isHtmlMode ? 
        (document.getElementById('htmlContent')?.value || '') : 
        (document.getElementById('emailBody')?.value || '');
    
    // Sample data for preview
    const sampleData = {
        '{{company_name}}': 'SecuriRota',
        '{{officer_name}}': 'Arbaz khan',
        '{{site_name}}': 'Downtown Mall',
        '{{shift_date}}': '2025-08-30',
        '{{shift_time}}': '09:00 - 17:00',
        '{{shift_start_time}}': '09:00',
        '{{shift_end_time}}': '17:00',
        '{{shift_duration}}': '8 hours',
        '{{hourly_rate}}': '25.00',
        '{{pin_code}}': '677229',
        '{{mobile}}': 'Arbazkhan',
        '{{password}}': 'TempPass123',
        '{{new_password}}': 'NewPass456',
        '{{login_url}}': 'https://rohab.ae/rota'
    };
    
    let processedSubject = subject;
    let processedBody = body;
    
    // Replace variables with sample data
    Object.keys(sampleData).forEach(variable => {
        const regex = new RegExp(escapeRegExp(variable), 'g');
        processedSubject = processedSubject.replace(regex, sampleData[variable]);
        processedBody = processedBody.replace(regex, sampleData[variable]);
    });
    
    // Parse admin content dynamically to generate branded content
    const content = parseAdminContentToBrandedHTML(processedBody);
    
    return generateEmailHTML(processedSubject, content);
}

function parseAdminContentToBrandedHTML(content) {
    // Split content into lines for processing
    const lines = content.split('\n').map(line => line.trim()).filter(line => line);
    let html = '';
    let inInstructions = false;
    let instructionItems = [];
    let currentInfoBox = null;
    let infoBoxItems = [];
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        
        // Check for key:value pairs (info box items)
        if (line.includes(':') && !line.match(/^\d+\.|^[\-\*]/)) {
            const [key, value] = line.split(':').map(s => s.trim());
            if (key && value) {
                if (!currentInfoBox && i > 0) {
                    // Start a new info box if we found key:value pairs
                    currentInfoBox = { title: 'Details', items: [] };
                }
                if (currentInfoBox) {
                    currentInfoBox.items.push({ key, value });
                }
                continue;
            }
        }
        
        // Close info box if we're done with key:value pairs
        if (currentInfoBox && (!line.includes(':') || line.match(/^\d+\.|^[\-\*]/))) {
            html += generateInfoBox(currentInfoBox.title, currentInfoBox.items);
            currentInfoBox = null;
        }
        
        // Check for numbered or bulleted lists (instructions)
        if (line.match(/^\d+\.|^[\-\*]/)) {
            if (!inInstructions) {
                inInstructions = true;
                instructionItems = [];
            }
            const itemText = line.replace(/^\d+\.|^[\-\*]\s*/, '');
            instructionItems.push(itemText);
            continue;
        }
        
        // Close instructions if we're done with the list
        if (inInstructions && !line.match(/^\d+\.|^[\-\*]/)) {
            html += generateInstructions('📋 Instructions:', instructionItems);
            inInstructions = false;
            instructionItems = [];
        }
        
        // Check for special highlighted values (PIN, passwords, etc.)
        if (line.match(/\b\d{6}\b/) || line.toLowerCase().includes('pin') || line.toLowerCase().includes('password')) {
            html += `<div class="highlight-section">${line}</div>`;
            continue;
        }
        
        // Check if this is a greeting
        if (i === 0 && (line.toLowerCase().includes('hello') || line.toLowerCase().includes('dear'))) {
            html += `<div class="greeting">${line}</div>`;
            continue;
        }
        
        // Regular message content
        html += `<div class="message">${line}</div>`;
    }
    
    // Close any remaining sections
    if (currentInfoBox) {
        html += generateInfoBox(currentInfoBox.title, currentInfoBox.items);
    }
    if (inInstructions) {
        html += generateInstructions('📋 Instructions:', instructionItems);
    }
    
    return html || `<div class="message">${content.replace(/\n/g, '<br>')}</div>`;
}

function generateInfoBox(title, items) {
    let itemsHTML = '';
    items.forEach(item => {
        if (item.key.toLowerCase().includes('pin') || item.key.toLowerCase().includes('code')) {
            itemsHTML += `
                <div class="info-item">
                    <span class="info-label">${item.key}:</span>
                </div>
                <div class="highlight-value">${item.value}</div>`;
        } else {
            itemsHTML += `
                <div class="info-item">
                    <span class="info-label">${item.key}:</span>
                    <span class="info-value">${item.value}</span>
                </div>`;
        }
    });
    
    return `
        <div class="info-box">
            <h3>${title}</h3>
            ${itemsHTML}
        </div>`;
}

function generateInstructions(title, items) {
    const listItems = items.map(item => `<li>${item}</li>`).join('');
    return `
        <div class="instructions">
            <h4>${title}</h4>
            <ol>${listItems}</ol>
        </div>`;
}

function generateEmailHTML(title, content) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6; 
            color: #333; 
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            text-align: center; 
            padding: 30px 20px;
        }
        .header h1 { 
            font-size: 28px; 
            font-weight: 600; 
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .header .subtitle { 
            font-size: 16px; 
            opacity: 0.9; 
            font-weight: 300;
        }
        .shield-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .content { 
            padding: 40px 30px; 
        }
        .greeting { 
            font-size: 20px; 
            font-weight: 600; 
            color: #2d3748; 
            margin-bottom: 20px;
        }
        .message { 
            font-size: 16px; 
            line-height: 1.6; 
            color: #4a5568; 
            margin-bottom: 30px;
        }
        .info-box { 
            background: #f7fafc; 
            border: 2px solid #48bb78; 
            border-radius: 8px; 
            padding: 25px; 
            margin: 25px 0; 
            text-align: center;
        }
        .info-box h3 { 
            color: #2d3748; 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 15px;
        }
        .info-item { 
            margin: 10px 0; 
            font-size: 16px;
        }
        .info-label { 
            font-weight: 600; 
            color: #2d3748;
        }
        .info-value { 
            color: #4a5568;
            margin-left: 5px;
        }
        .highlight-value { 
            font-size: 36px; 
            font-weight: 700; 
            color: #48bb78; 
            margin: 15px 0;
            font-family: "Courier New", monospace;
            letter-spacing: 2px;
        }
        .instructions { 
            background: #fff5b2; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 25px 0;
        }
        .instructions h4 { 
            color: #744210; 
            font-weight: 600; 
            margin-bottom: 15px;
            font-size: 16px;
        }
        .instructions ol { 
            padding-left: 20px; 
            color: #744210;
        }
        .instructions li { 
            margin: 8px 0; 
            font-size: 14px;
        }
        .login-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .footer { 
            background: #edf2f7; 
            padding: 20px; 
            text-align: center; 
            border-top: 1px solid #e2e8f0;
        }
        .footer p { 
            color: #718096; 
            font-size: 14px; 
            margin: 5px 0;
        }
        .security-notice {
            background: #fed7d7;
            border: 1px solid #fc8181;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #742a2a;
        }
        .highlight-section {
            background: #f0fff4;
            border: 2px solid #48bb78;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><span class="shield-icon">🛡️</span> SecuriRota System</h1>
            <div class="subtitle">${title}</div>
        </div>
        <div class="content">
            ${content}
        </div>
        <div class="footer">
            <p><strong>SecuriRota Security Management</strong></p>
            <p>Professional Security Solutions</p>
            <p style="font-size: 12px; margin-top: 10px;">This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>`;
}

function sendTestEmail() {
    const email = document.getElementById('testEmailAddress').value;
    const templateType = document.getElementById('testTemplateSelect').value;
    
    if (!email) {
        showNotification('Please enter an email address', 'error');
        return;
    }
    
    const sendBtn = document.querySelector('#testEmailModal .btn-primary');
    const originalText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    sendBtn.disabled = true;
    
    fetch('../api/email_templates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            action: 'send_test',
            template_type: templateType,
            email: email
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Test email sent successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('testEmailModal'));
            modal.hide();
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

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}
</script>

<?php require_once '../includes/footer.php'; ?>
