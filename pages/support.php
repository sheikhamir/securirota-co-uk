<?php
$page_title = 'Support Guides';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-question-circle"></i> Support & User Guides</h3>
    </div>
    
    <div style="padding: 30px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            
            <!-- Getting Started -->
            <div class="guide-section" style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #28a745;">
                <h4 style="color: #28a745; margin-bottom: 15px;">
                    <i class="fas fa-play-circle"></i> Getting Started
                </h4>
                <p>Learn the basics of using the Rota Management System.</p>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>System overview and navigation</li>
                    <li>Setting up your first officers</li>
                    <li>Adding clients and sites</li>
                    <li>Creating your first rota</li>
                </ul>
                <button onclick="showGuide('getting-started')" class="btn btn-success btn-sm">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
            <!-- Officer Management -->
            <div class="guide-section" style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #667eea;">
                <h4 style="color: #667eea; margin-bottom: 15px;">
                    <i class="fas fa-users"></i> Officer Management
                </h4>
                <p>Complete guide to managing officer records and profiles.</p>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>Adding new officers</li>
                    <li>Managing compliance documents</li>
                    <li>Setting pay rates and roles</li>
                    <li>Tracking SIA and visa expiries</li>
                </ul>
                <button onclick="showGuide('officer-management')" class="btn btn-primary btn-sm">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
            <!-- Rota Planning -->
            <div class="guide-section" style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #17a2b8;">
                <h4 style="color: #17a2b8; margin-bottom: 15px;">
                    <i class="fas fa-calendar-alt"></i> Rota Planning
                </h4>
                <p>Master the art of efficient shift scheduling and planning.</p>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>Creating and editing shifts</li>
                    <li>Allocating officers to shifts</li>
                    <li>Using drag-and-drop functionality</li>
                    <li>Managing shift confirmations</li>
                </ul>
                <button onclick="showGuide('rota-planning')" class="btn btn-info btn-sm">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
            <!-- Reports & Invoicing -->
            <div class="guide-section" style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #ffc107;">
                <h4 style="color: #856404; margin-bottom: 15px;">
                    <i class="fas fa-chart-bar"></i> Reports & Invoicing
                </h4>
                <p>Generate comprehensive reports and manage invoicing.</p>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>Creating deployment reports</li>
                    <li>Generating officer invoices</li>
                    <li>Client billing and charges</li>
                    <li>Exporting to Excel and PDF</li>
                </ul>
                <button onclick="showGuide('reports-invoicing')" class="btn btn-warning btn-sm">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
            <!-- Troubleshooting -->
            <div class="guide-section" style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #dc3545;">
                <h4 style="color: #dc3545; margin-bottom: 15px;">
                    <i class="fas fa-tools"></i> Troubleshooting
                </h4>
                <p>Common issues and their solutions.</p>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>Login and access issues</li>
                    <li>Data not saving properly</li>
                    <li>Export problems</li>
                    <li>Performance optimization</li>
                </ul>
                <button onclick="showGuide('troubleshooting')" class="btn btn-danger btn-sm">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
            <!-- System Administration -->
            <div class="guide-section" style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #6c757d;">
                <h4 style="color: #6c757d; margin-bottom: 15px;">
                    <i class="fas fa-cog"></i> System Administration
                </h4>
                <p>Advanced configuration and system management.</p>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>User account management</li>
                    <li>System settings configuration</li>
                    <li>Backup and maintenance</li>
                    <li>Security best practices</li>
                </ul>
                <button onclick="showGuide('administration')" class="btn btn-secondary btn-sm">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
        </div>
        
        <!-- Quick Tips -->
        <div class="card mt-30">
            <div class="card-header">
                <h4><i class="fas fa-lightbulb"></i> Quick Tips</h4>
            </div>
            
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="tip-item" style="background: #e3f2fd; padding: 15px; border-radius: 8px;">
                        <h6 style="color: #1976d2; margin-bottom: 10px;">
                            <i class="fas fa-mouse-pointer"></i> Drag & Drop
                        </h6>
                        <p style="margin: 0; font-size: 0.9rem;">
                            You can drag shifts between officers and dates in the rota view for quick rescheduling.
                        </p>
                    </div>
                    
                    <div class="tip-item" style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                        <h6 style="color: #388e3c; margin-bottom: 10px;">
                            <i class="fas fa-keyboard"></i> Keyboard Shortcuts
                        </h6>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Double-click on empty calendar cells to quickly create new shifts.
                        </p>
                    </div>
                    
                    <div class="tip-item" style="background: #fff3e0; padding: 15px; border-radius: 8px;">
                        <h6 style="color: #f57c00; margin-bottom: 10px;">
                            <i class="fas fa-bell"></i> Notifications
                        </h6>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Set up document expiry alerts to stay on top of SIA license renewals.
                        </p>
                    </div>
                    
                    <div class="tip-item" style="background: #fce4ec; padding: 15px; border-radius: 8px;">
                        <h6 style="color: #c2185b; margin-bottom: 10px;">
                            <i class="fas fa-download"></i> Bulk Export
                        </h6>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Use filters in reports to export data for specific clients or date ranges.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Support -->
        <div class="card mt-30">
            <div class="card-header">
                <h4><i class="fas fa-headset"></i> Need More Help?</h4>
            </div>
            
            <div style="padding: 20px;">
                <p>If you can't find the answer you're looking for in these guides, don't hesitate to contact our support team.</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div class="contact-method" style="text-align: center; padding: 20px;">
                        <i class="fas fa-envelope" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                        <h6>Email Support</h6>
                        <p class="text-muted">support@securirota.com</p>
                        <small>Response within 24 hours</small>
                    </div>
                    
                    <div class="contact-method" style="text-align: center; padding: 20px;">
                        <i class="fas fa-phone" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                        <h6>Phone Support</h6>
                        <p class="text-muted">+44 20 1234 5678</p>
                        <small>Mon-Fri 9AM-5PM GMT</small>
                    </div>
                    
                    <div class="contact-method" style="text-align: center; padding: 20px;">
                        <i class="fas fa-comments" style="font-size: 2rem; color: #17a2b8; margin-bottom: 10px;"></i>
                        <h6>Live Chat</h6>
                        <p class="text-muted">Available on website</p>
                        <small>Mon-Fri 9AM-5PM GMT</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showGuide(guideName) {
    let content = '';
    
    switch(guideName) {
        case 'getting-started':
            content = `
                <h4>Getting Started Guide</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <h5>1. System Overview</h5>
                    <p>The SecuriRota system is designed to streamline security company operations with features for:</p>
                    <ul>
                        <li>Officer management and compliance tracking</li>
                        <li>Client and site management</li>
                        <li>Shift scheduling and rota planning</li>
                        <li>Deployment tracking and reporting</li>
                        <li>Invoicing and billing</li>
                    </ul>
                    
                    <h5>2. Navigation</h5>
                    <p>Use the sidebar menu to navigate between different sections:</p>
                    <ul>
                        <li><strong>Dashboard:</strong> Overview of key metrics</li>
                        <li><strong>Staff Records:</strong> Manage officer profiles</li>
                        <li><strong>Site Records:</strong> Manage client sites</li>
                        <li><strong>Planner (Rota):</strong> Schedule and manage shifts</li>
                        <li><strong>Reports:</strong> Generate deployment and billing reports</li>
                    </ul>
                    
                    <h5>3. First Steps</h5>
                    <ol>
                        <li>Add your first client in the Clients section</li>
                        <li>Add sites for that client</li>
                        <li>Add officer profiles with their details</li>
                        <li>Start scheduling shifts in the Rota planner</li>
                    </ol>
                </div>
            `;
            break;
            
        case 'officer-management':
            content = `
                <h4>Officer Management Guide</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <h5>Adding New Officers</h5>
                    <ol>
                        <li>Go to Staff Records in the sidebar</li>
                        <li>Click "Add New Officer"</li>
                        <li>Fill in personal information, address, and contact details</li>
                        <li>Add compliance information (SIA badge, visa status)</li>
                        <li>Set employment details and pay rate</li>
                        <li>Add bank details for payments</li>
                    </ol>
                    
                    <h5>Document Management</h5>
                    <p>Keep track of important documents:</p>
                    <ul>
                        <li>SIA License with expiry date</li>
                        <li>Visa status and expiry</li>
                        <li>ID documents and proof of address</li>
                        <li>Employment contracts</li>
                    </ul>
                    
                    <h5>Compliance Tracking</h5>
                    <p>The system will alert you when:</p>
                    <ul>
                        <li>SIA licenses are expiring within 30 days</li>
                        <li>Visa status needs renewal</li>
                        <li>Training certifications expire</li>
                    </ul>
                </div>
            `;
            break;
            
        case 'rota-planning':
            content = `
                <h4>Rota Planning Guide</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <h5>Creating Shifts</h5>
                    <ol>
                        <li>Go to Planner (Rota) in the sidebar</li>
                        <li>Navigate to the desired week</li>
                        <li>Click "Add Shift" or double-click on a calendar cell</li>
                        <li>Select the site, officer, times, and role</li>
                        <li>Save the shift</li>
                    </ol>
                    
                    <h5>Drag & Drop Functionality</h5>
                    <p>You can easily reschedule shifts by:</p>
                    <ul>
                        <li>Dragging shifts between different officers</li>
                        <li>Moving shifts to different dates</li>
                        <li>Reallocating unassigned shifts</li>
                    </ul>
                    
                    <h5>Shift Status Management</h5>
                    <p>Track shift progress through these statuses:</p>
                    <ul>
                        <li><strong>Unallocated:</strong> No officer assigned</li>
                        <li><strong>Allocated:</strong> Officer assigned but not confirmed</li>
                        <li><strong>Confirmed:</strong> Officer has confirmed attendance</li>
                        <li><strong>Declined:</strong> Officer declined the shift</li>
                        <li><strong>Completed:</strong> Shift finished</li>
                    </ul>
                </div>
            `;
            break;
            
        case 'reports-invoicing':
            content = `
                <h4>Reports & Invoicing Guide</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <h5>Deployment Reports</h5>
                    <ol>
                        <li>Go to Reports in the sidebar</li>
                        <li>Set your date range and filters</li>
                        <li>Click "Generate Report"</li>
                        <li>Review the data and export to Excel or PDF</li>
                    </ol>
                    
                    <h5>Officer Invoices</h5>
                    <p>Generate invoices for officer payments:</p>
                    <ul>
                        <li>Go to Invoices section</li>
                        <li>Select "Officer Invoices" tab</li>
                        <li>Choose date range and specific officers</li>
                        <li>Export individual or bulk invoices</li>
                    </ul>
                    
                    <h5>Client Billing</h5>
                    <p>Create billing reports for clients:</p>
                    <ul>
                        <li>Select "Client Billing" tab</li>
                        <li>Filter by specific clients or sites</li>
                        <li>Review total hours and charges</li>
                        <li>Export billing summaries</li>
                    </ul>
                    
                    <h5>Export Options</h5>
                    <p>All reports can be exported as:</p>
                    <ul>
                        <li>Excel spreadsheets for further analysis</li>
                        <li>PDF documents for sending to clients</li>
                        <li>Printed reports for physical filing</li>
                    </ul>
                </div>
            `;
            break;
            
        case 'troubleshooting':
            content = `
                <h4>Troubleshooting Guide</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <h5>Login Issues</h5>
                    <p>If you can't log in:</p>
                    <ul>
                        <li>Check your username and password are correct</li>
                        <li>Ensure Caps Lock is not on</li>
                        <li>Try refreshing the page</li>
                        <li>Contact admin if account is locked</li>
                    </ul>
                    
                    <h5>Data Not Saving</h5>
                    <p>If information isn't saving properly:</p>
                    <ul>
                        <li>Check all required fields are filled</li>
                        <li>Ensure date formats are correct</li>
                        <li>Try refreshing and re-entering data</li>
                        <li>Check your internet connection</li>
                    </ul>
                    
                    <h5>Export Problems</h5>
                    <p>If exports aren't working:</p>
                    <ul>
                        <li>Check your browser allows downloads</li>
                        <li>Try a different browser</li>
                        <li>Ensure popup blockers are disabled</li>
                        <li>Try smaller date ranges</li>
                    </ul>
                    
                    <h5>Performance Issues</h5>
                    <p>If the system is running slowly:</p>
                    <ul>
                        <li>Close unnecessary browser tabs</li>
                        <li>Clear your browser cache</li>
                        <li>Use a modern, updated browser</li>
                        <li>Check your internet connection speed</li>
                    </ul>
                </div>
            `;
            break;
            
        case 'administration':
            content = `
                <h4>System Administration Guide</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <h5>User Management</h5>
                    <p>Admin users can:</p>
                    <ul>
                        <li>Create new user accounts</li>
                        <li>Set user roles (Admin/Officer)</li>
                        <li>Reset passwords</li>
                        <li>Deactivate accounts</li>
                    </ul>
                    
                    <h5>System Settings</h5>
                    <p>Configure system-wide settings:</p>
                    <ul>
                        <li>Default pay rates and billing rates</li>
                        <li>Document categories and requirements</li>
                        <li>Notification preferences</li>
                        <li>Export templates</li>
                    </ul>
                    
                    <h5>Backup & Maintenance</h5>
                    <p>Regular maintenance tasks:</p>
                    <ul>
                        <li>Create regular database backups</li>
                        <li>Monitor system performance</li>
                        <li>Update officer compliance status</li>
                        <li>Clean up old data periodically</li>
                    </ul>
                    
                    <h5>Security Best Practices</h5>
                    <ul>
                        <li>Use strong passwords</li>
                        <li>Regular password changes</li>
                        <li>Monitor user activity logs</li>
                        <li>Keep software updated</li>
                    </ul>
                </div>
            `;
            break;
            
        default:
            content = '<p>Guide not found.</p>';
    }
    
    showModal('User Guide', content);
}
</script>

<?php require_once '../includes/footer.php'; ?>
