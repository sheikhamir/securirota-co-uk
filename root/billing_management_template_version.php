<?php
/**
 * Root User - Billing Management
 * Comprehensive billing management for all companies
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'Billing Management';
$page_description = 'Manage billing, invoices, and payment tracking for all companies';
$active_page = 'billing_management';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'Company Management', 'url' => 'companies.php'],
    ['title' => 'Billing Management']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Handle billing actions
if ($_POST && isset($_POST['action'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        switch ($_POST['action']) {
            case 'update_billing_cycle':
                $company_id = (int)$_POST['company_id'];
                $billing_cycle = $_POST['billing_cycle'];
                $next_billing_date = $_POST['next_billing_date'];
                
                $stmt = $conn->prepare("
                    UPDATE companies 
                    SET billing_cycle = ?, next_billing_date = ?
                    WHERE id = ?
                ");
                $stmt->execute([$billing_cycle, $next_billing_date, $company_id]);
                
                $_SESSION['success'] = "Billing cycle updated successfully.";
                break;
                
            case 'add_billing_entry':
                $company_id = (int)$_POST['company_id'];
                $amount = (float)$_POST['amount'];
                $billing_date = $_POST['billing_date'];
                $status = $_POST['status'];
                $notes = trim($_POST['description']);
                
                // Get the company's subscription tier for the billing entry
                $tier_stmt = $conn->prepare("SELECT subscription_tier_id FROM companies WHERE id = ?");
                $tier_stmt->execute([$company_id]);
                $company_data = $tier_stmt->fetch(PDO::FETCH_ASSOC);
                $subscription_tier_id = $company_data['subscription_tier_id'] ?? 1;
                
                $stmt = $conn->prepare("
                    INSERT INTO billing_history (company_id, subscription_tier_id, amount, billing_date, billing_cycle, status, notes, created_at) 
                    VALUES (?, ?, ?, ?, 'monthly', ?, ?, NOW())
                ");
                $stmt->execute([$company_id, $subscription_tier_id, $amount, $billing_date, $status, $notes]);
                
                $_SESSION['success'] = "Billing entry added successfully.";
                break;
                
            case 'update_billing_status':
                $billing_id = (int)$_POST['billing_id'];
                $new_status = $_POST['new_status'];
                
                $stmt = $conn->prepare("
                    UPDATE billing_history 
                    SET status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $billing_id]);
                
                $_SESSION['success'] = "Billing status updated successfully.";
                break;
                
            case 'generate_invoice':
                $company_id = (int)$_POST['company_id'];
                $amount = (float)$_POST['amount'];
                $notes = trim($_POST['description']);
                
                // Get the company's subscription tier for the billing entry
                $tier_stmt = $conn->prepare("SELECT subscription_tier_id FROM companies WHERE id = ?");
                $tier_stmt->execute([$company_id]);
                $company_data = $tier_stmt->fetch(PDO::FETCH_ASSOC);
                $subscription_tier_id = $company_data['subscription_tier_id'] ?? 1;
                
                // Generate invoice number
                $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($company_id, 4, '0', STR_PAD_LEFT);
                
                // Create billing entry
                $stmt = $conn->prepare("
                    INSERT INTO billing_history (company_id, subscription_tier_id, amount, billing_date, billing_cycle, status, invoice_number, notes, created_at) 
                    VALUES (?, ?, ?, CURDATE(), 'monthly', 'pending', ?, ?, NOW())
                ");
                $stmt->execute([$company_id, $subscription_tier_id, $amount, $invoice_number, $notes]);
                
                $_SESSION['success'] = "Invoice generated successfully.";
                break;
        }
        
        header('Location: billing_management.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get companies with billing information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get companies with their subscription tiers and billing info
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.monthly_price,
            st.yearly_price,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
            (SELECT COUNT(*) FROM officers WHERE company_id = c.id) as officer_count,
            (SELECT COUNT(*) FROM sites WHERE company_id = c.id) as site_count,
            (SELECT SUM(amount) FROM billing_history WHERE company_id = c.id AND status = 'paid') as total_paid,
            (SELECT SUM(amount) FROM billing_history WHERE company_id = c.id AND status = 'pending') as pending_amount,
            (SELECT COUNT(*) FROM billing_history WHERE company_id = c.id) as billing_count
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.name ASC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent billing history
    $stmt = $conn->query("
        SELECT 
            bh.*,
            c.name as company_name
        FROM billing_history bh
        JOIN companies c ON bh.company_id = c.id
        ORDER BY bh.created_at DESC
        LIMIT 50
    ");
    $recent_billing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subscription tiers for dropdowns
    $stmt = $conn->query("SELECT * FROM subscription_tiers WHERE is_active = 1 ORDER BY monthly_price ASC");
    $subscription_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $stmt = $conn->query("
        SELECT 
            COUNT(DISTINCT c.id) as total_companies,
            COALESCE(SUM(CASE WHEN bh.status = 'paid' THEN bh.amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN bh.status = 'pending' THEN bh.amount ELSE 0 END), 0) as pending_revenue,
            COALESCE(SUM(CASE WHEN bh.status = 'overdue' THEN bh.amount ELSE 0 END), 0) as overdue_revenue
        FROM companies c
        LEFT JOIN billing_history bh ON c.id = bh.company_id
    ");
    $billing_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading billing data: " . $e->getMessage();
}

// Template configuration
$enable_live_search = true;
$search_config = [
    'input_id' => 'billingSearch',
    'table_id' => 'billingTable',
    'columns' => [0, 1] // Search company name and tier
];

$enable_table_sorting = true;
$sortable_table_id = 'billingTable';

// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-credit-card text-warning me-2"></i>
                Billing Management
            </h1>
            <p class="mb-0 text-white-50">Manage billing, invoices, and payment tracking for all companies</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" data-bs-toggle="modal" data-bs-target="#addBillingModal">
                <i class="fas fa-plus me-1"></i> Add Billing Entry
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $billing_stats['total_companies'] ?></div>
                    <div class="stats-label">Total Companies</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">$<?= number_format($billing_stats['total_revenue'], 0) ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">$<?= number_format($billing_stats['pending_revenue'], 0) ?></div>
                    <div class="stats-label">Pending</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">$<?= number_format($billing_stats['overdue_revenue'], 0) ?></div>
                    <div class="stats-label">Overdue</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="crown-card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="billingSearch" placeholder="Search companies or billing...">
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($companies) ?> companies with billing
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Tabs -->
<ul class="nav nav-tabs crown-tabs mb-4" id="billingTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="companies-tab" data-bs-toggle="tab" data-bs-target="#companies" type="button" role="tab">
            <i class="fas fa-building me-2"></i>Company Billing
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
            <i class="fas fa-history me-2"></i>Billing History
        </button>
    </li>
</ul>

<div class="tab-content" id="billingTabsContent">
    <!-- Company Billing Tab -->
    <div class="tab-pane fade show active" id="companies" role="tabpanel">
        <div class="crown-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list text-primary me-2"></i>
                    Company Billing Overview
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($companies)): ?>
                    <div class="empty-state text-center py-5">
                        <i class="fas fa-building fa-3x mb-3"></i>
                        <h5>No Companies Found</h5>
                        <p class="text-muted">No companies are available for billing management.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table crown-table" id="billingTable">
                            <thead>
                                <tr>
                                    <th data-sortable>Company</th>
                                    <th data-sortable>Subscription</th>
                                    <th data-sortable>Usage</th>
                                    <th data-sortable>Revenue</th>
                                    <th data-sortable>Next Billing</th>
                                    <th data-sortable>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($company['name']) ?></div>
                                                <small class="text-muted">ID: <?= $company['id'] ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div class="fw-bold mb-1"><?= htmlspecialchars($company['tier_name'] ?? 'No Tier') ?></div>
                                                <div class="text-muted">
                                                    $<?= number_format($company['monthly_price'] ?? 0, 2) ?>/month
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div><?= $company['user_count'] ?> users</div>
                                                <div><?= $company['officer_count'] ?> officers</div>
                                                <div><?= $company['site_count'] ?> sites</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div class="fw-bold text-success mb-1">
                                                    $<?= number_format($company['total_paid'] ?? 0, 2) ?> paid
                                                </div>
                                                <?php if ($company['pending_amount'] > 0): ?>
                                                    <div class="text-warning">
                                                        $<?= number_format($company['pending_amount'], 2) ?> pending
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <?php if ($company['next_billing_date']): ?>
                                                    <?= date('M j, Y', strtotime($company['next_billing_date'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($company['subscription_expired']): ?>
                                                <span class="crown-badge crown-badge-danger">Expired</span>
                                            <?php elseif ($company['pending_amount'] > 0): ?>
                                                <span class="crown-badge crown-badge-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="crown-badge crown-badge-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-crown-outline btn-sm" 
                                                        onclick="manageBilling(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>')"
                                                        data-bs-toggle="tooltip" 
                                                        title="Manage Billing">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <button type="button" class="btn btn-crown-outline btn-sm" 
                                                        onclick="generateInvoice(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>')"
                                                        data-bs-toggle="tooltip" 
                                                        title="Generate Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </button>
                                                <button type="button" class="btn btn-crown-outline btn-sm" 
                                                        onclick="viewBillingHistory(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>')"
                                                        data-bs-toggle="tooltip" 
                                                        title="View History">
                                                    <i class="fas fa-history"></i>
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
    </div>
    
    <!-- Billing History Tab -->
    <div class="tab-pane fade" id="history" role="tabpanel">
        <div class="crown-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history text-primary me-2"></i>
                    Recent Billing History
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_billing)): ?>
                    <div class="empty-state text-center py-5">
                        <i class="fas fa-history fa-3x mb-3"></i>
                        <h5>No Billing History</h5>
                        <p class="text-muted">No billing transactions have been recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table crown-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Company</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Invoice</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_billing as $billing): ?>
                                    <tr>
                                        <td>
                                            <div class="small">
                                                <div><?= date('M j, Y', strtotime($billing['billing_date'])) ?></div>
                                                <div class="text-muted"><?= date('H:i', strtotime($billing['created_at'])) ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($billing['company_name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">$<?= number_format($billing['amount'], 2) ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'overdue' => 'danger',
                                                'cancelled' => 'secondary'
                                            ][$billing['status']] ?? 'secondary';
                                            ?>
                                            <span class="crown-badge crown-badge-<?= $status_class ?>">
                                                <?= ucfirst($billing['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($billing['invoice_number']): ?>
                                                <span class="font-monospace small"><?= htmlspecialchars($billing['invoice_number']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <?= htmlspecialchars($billing['notes'] ?: '-') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-crown-outline btn-sm" 
                                                        onclick="updateBillingStatus(<?= $billing['id'] ?>, '<?= $billing['status'] ?>')"
                                                        data-bs-toggle="tooltip" 
                                                        title="Update Status">
                                                    <i class="fas fa-edit"></i>
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
    </div>
</div>

<!-- Add Billing Entry Modal -->
<div class="modal fade" id="addBillingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Add Billing Entry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_billing_entry">
                    
                    <div class="mb-3">
                        <label for="company_id" class="form-label">Company *</label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= $company['id'] ?>">
                                    <?= htmlspecialchars($company['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($) *</label>
                                <input type="number" class="form-control" id="amount" name="amount" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="billing_date" class="form-label">Billing Date *</label>
                                <input type="date" class="form-control" id="billing_date" name="billing_date" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Notes</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Optional billing notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-plus me-1"></i> Add Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Manage billing function
function manageBilling(companyId, companyName) {
    RootCommon.showAlert('Billing management for ' + companyName + ' - Feature coming soon!', 'info');
}

// Generate invoice function
function generateInvoice(companyId, companyName) {
    const amount = prompt('Enter invoice amount:');
    if (amount && !isNaN(amount) && parseFloat(amount) > 0) {
        const description = prompt('Enter invoice description (optional):') || '';
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='generate_invoice'>
            <input type='hidden' name='company_id' value='\${companyId}'>
            <input type='hidden' name='amount' value='\${amount}'>
            <input type='hidden' name='description' value='\${description}'>
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// View billing history function
function viewBillingHistory(companyId, companyName) {
    RootCommon.showAlert('Billing history for ' + companyName + ' - Feature coming soon!', 'info');
}

// Update billing status function
function updateBillingStatus(billingId, currentStatus) {
    const statuses = ['pending', 'paid', 'overdue', 'cancelled'];
    const currentIndex = statuses.indexOf(currentStatus);
    const options = statuses.map(status => 
        `<option value='\${status}' \${status === currentStatus ? 'selected' : ''}>\${status.charAt(0).toUpperCase() + status.slice(1)}</option>`
    ).join('');
    
    const selectHtml = `<select class='form-select' id='newStatus'>\${options}</select>`;
    
    const message = `Update billing status:<br><br>\${selectHtml}`;
    
    RootCommon.confirmAction(message, () => {
        const newStatus = document.getElementById('newStatus').value;
        if (newStatus !== currentStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type='hidden' name='action' value='update_billing_status'>
                <input type='hidden' name='billing_id' value='\${billingId}'>
                <input type='hidden' name='new_status' value='\${newStatus}'>
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
";

include 'includes/footer.php';
?>