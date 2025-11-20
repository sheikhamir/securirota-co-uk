<?php
/**
 * Root User - Company Management System
 * Complete interface for managing all companies in the system
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'Company Management';
$page_description = 'Manage all companies in the multi-tenant system';
$active_page = 'companies';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'Company Management', 'url' => 'companies.php'],
    ['title' => 'Companies']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        switch ($_POST['action']) {
            case 'update_company_status':
                $company_id = (int)$_POST['company_id'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE companies SET status = ? WHERE id = ?");
                $stmt->execute([$status, $company_id]);
                
                $_SESSION['success'] = "Company status updated successfully.";
                break;
                
            case 'update_subscription':
                $company_id = (int)$_POST['company_id'];
                $tier_id = (int)$_POST['subscription_tier_id'];
                $billing_cycle = $_POST['billing_cycle'];
                
                // Calculate subscription dates
                $start_date = date('Y-m-d');
                $end_date = $billing_cycle === 'yearly' ? 
                    date('Y-m-d', strtotime('+1 year')) : 
                    date('Y-m-d', strtotime('+1 month'));
                
                $stmt = $conn->prepare("
                    UPDATE companies 
                    SET subscription_tier_id = ?, 
                        billing_cycle = ?, 
                        subscription_start_date = ?,
                        subscription_end_date = ?,
                        next_billing_date = ?,
                        billing_status = 'active'
                    WHERE id = ?
                ");
                $stmt->execute([$tier_id, $billing_cycle, $start_date, $end_date, $end_date, $company_id]);
                
                $_SESSION['success'] = "Subscription updated successfully.";
                break;
                
            case 'add_company':
                $name = trim($_POST['company_name']);
                $contact_email = trim($_POST['contact_email']);
                $contact_phone = trim($_POST['contact_phone']);
                $tier_id = (int)$_POST['subscription_tier_id'];
                $billing_cycle = $_POST['billing_cycle'];
                
                if (empty($name)) {
                    throw new Exception('Company name is required.');
                }
                
                // Create company
                $stmt = $conn->prepare("
                    INSERT INTO companies 
                    (name, contact_email, contact_phone, subscription_tier_id, billing_cycle, 
                     subscription_start_date, subscription_end_date, next_billing_date, 
                     status, billing_status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'active', NOW())
                ");
                
                $start_date = date('Y-m-d');
                $end_date = $billing_cycle === 'yearly' ? 
                    date('Y-m-d', strtotime('+1 year')) : 
                    date('Y-m-d', strtotime('+1 month'));
                
                $stmt->execute([
                    $name, $contact_email, $contact_phone, $tier_id, $billing_cycle,
                    $start_date, $end_date, $end_date
                ]);
                
                $_SESSION['success'] = "Company created successfully.";
                break;
        }
        
        header('Location: companies.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get companies with subscription information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all companies with subscription details
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.max_officers,
            st.max_sites,
            st.max_clients,
            st.monthly_price,
            st.yearly_price,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
            (SELECT COUNT(*) FROM officers WHERE company_id = c.id) as officer_count,
            (SELECT COUNT(*) FROM sites WHERE company_id = c.id) as site_count,
            (SELECT COUNT(*) FROM clients WHERE company_id = c.id) as client_count
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.created_at DESC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subscription tiers for dropdowns
    $stmt = $conn->query("SELECT * FROM subscription_tiers WHERE is_active = 1 ORDER BY monthly_price ASC");
    $subscription_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading companies: " . $e->getMessage();
}

// Template configuration
$enable_live_search = true;
$search_config = [
    'input_id' => 'companySearch',
    'table_id' => 'companiesTable',
    'columns' => [0, 1, 3] // Search company name, tier, and contact
];

$enable_table_sorting = true;
$sortable_table_id = 'companiesTable';

// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-building text-warning me-2"></i>
                Company Management
            </h1>
            <p class="mb-0 text-white-50">Manage all companies, subscriptions, and billing</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                <i class="fas fa-plus me-1"></i> Add Company
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
                    <div class="stats-number"><?= count($companies) ?></div>
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
                    <div class="stats-number">
                        <?= count(array_filter($companies, function($c) { return $c['status'] === 'active'; })) ?>
                    </div>
                    <div class="stats-label">Active Companies</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        <?= count(array_filter($companies, function($c) { return $c['status'] === 'suspended'; })) ?>
                    </div>
                    <div class="stats-label">Suspended</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        $<?php 
                        $total_revenue = 0;
                        foreach($companies as $company) {
                            if ($company['billing_cycle'] === 'yearly') {
                                $total_revenue += $company['yearly_price'];
                            } else {
                                $total_revenue += $company['monthly_price'] * 12;
                            }
                        }
                        echo number_format($total_revenue, 0);
                        ?>
                    </div>
                    <div class="stats-label">Annual Revenue</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-dollar-sign"></i>
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
                    <input type="text" class="form-control" id="companySearch" placeholder="Search companies...">
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($companies) ?> companies total
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Companies Table -->
<div class="crown-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            All Companies
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($companies)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-building fa-3x mb-3"></i>
                <h5>No Companies Found</h5>
                <p class="text-muted">Start by adding your first company.</p>
                <button type="button" class="btn btn-crown" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                    <i class="fas fa-plus me-1"></i> Add Company
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table crown-table" id="companiesTable">
                    <thead>
                        <tr>
                            <th data-sortable>Company</th>
                            <th data-sortable>Subscription</th>
                            <th data-sortable>Usage</th>
                            <th data-sortable>Billing</th>
                            <th data-sortable>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="crown-avatar-circle me-3">
                                            <?= strtoupper(substr($company['name'] ?? 'UK', 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($company['name'] ?? 'Unknown Company') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($company['contact_email'] ?? 'No email') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="crown-badge crown-badge-info">
                                        <?= htmlspecialchars($company['tier_name'] ?? 'No Tier') ?>
                                    </span>
                                    <div class="text-muted small mt-1">
                                        <?= $company['max_officers'] ?> officers, <?= $company['max_sites'] ?> sites
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="mb-1">
                                            <i class="fas fa-users me-1"></i> <?= $company['user_count'] ?> users
                                        </div>
                                        <div class="mb-1">
                                            <i class="fas fa-user-shield me-1"></i> <?= $company['officer_count'] ?> officers
                                        </div>
                                        <div>
                                            <i class="fas fa-map-marker-alt me-1"></i> <?= $company['site_count'] ?> sites
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="fw-bold mb-1">
                                            <?php if ($company['billing_cycle'] === 'yearly'): ?>
                                                $<?= number_format($company['yearly_price'], 2) ?>/year
                                            <?php else: ?>
                                                $<?= number_format($company['monthly_price'], 2) ?>/month
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted">
                                            Next: <?= $company['next_billing_date'] ? date('M j, Y', strtotime($company['next_billing_date'])) : 'N/A' ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = match($company['status']) {
                                        'active' => 'success',
                                        'suspended' => 'warning',
                                        'inactive' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="crown-badge crown-badge-<?= $status_class ?>">
                                        <?= ucfirst($company['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="editCompany(<?= htmlspecialchars(json_encode($company)) ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Edit Company">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="viewCompanyDetails(<?= $company['id'] ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="toggleCompanyStatus(<?= $company['id'] ?>, '<?= $company['status'] ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Toggle Status">
                                            <i class="fas fa-power-off"></i>
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

<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Add New Company
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_company">
                    
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="subscription_tier_id" class="form-label">Subscription Tier *</label>
                        <select class="form-select" id="subscription_tier_id" name="subscription_tier_id" required>
                            <?php foreach ($subscription_tiers as $tier): ?>
                                <option value="<?= $tier['id'] ?>">
                                    <?= htmlspecialchars($tier['name']) ?> - 
                                    $<?= number_format($tier['monthly_price'], 2) ?>/month
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="billing_cycle" class="form-label">Billing Cycle *</label>
                        <select class="form-select" id="billing_cycle" name="billing_cycle" required>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-plus me-1"></i> Create Company
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Company Modal -->
<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Edit Company
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCompanyForm">
                <div class="modal-body">
                    <input type="hidden" name="company_id" id="edit_company_id">
                    
                    <!-- Status Update -->
                    <div class="crown-card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Company Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <button type="submit" name="action" value="update_company_status" class="btn btn-crown btn-sm">
                                <i class="fas fa-save me-1"></i> Update Status
                            </button>
                        </div>
                    </div>
                    
                    <!-- Subscription Update -->
                    <div class="crown-card">
                        <div class="card-header">
                            <h6 class="mb-0">Subscription Management</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="edit_subscription_tier_id" class="form-label">Subscription Tier</label>
                                <select class="form-select" name="subscription_tier_id" id="edit_subscription_tier_id">
                                    <?php foreach ($subscription_tiers as $tier): ?>
                                        <option value="<?= $tier['id'] ?>">
                                            <?= htmlspecialchars($tier['name']) ?> - 
                                            $<?= number_format($tier['monthly_price'], 2) ?>/month
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_billing_cycle" class="form-label">Billing Cycle</label>
                                <select class="form-select" name="billing_cycle" id="edit_billing_cycle">
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="action" value="update_subscription" class="btn btn-crown btn-sm">
                                <i class="fas fa-sync me-1"></i> Update Subscription
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Edit company function
function editCompany(company) {
    document.getElementById('edit_company_id').value = company.id;
    document.getElementById('edit_status').value = company.status;
    document.getElementById('edit_subscription_tier_id').value = company.subscription_tier_id || '';
    document.getElementById('edit_billing_cycle').value = company.billing_cycle || 'monthly';
    
    RootCommon.showModal('editCompanyModal');
}

// View company details
function viewCompanyDetails(companyId) {
    window.open('../pages/company_details.php?id=' + companyId, '_blank');
}

// Toggle company status
function toggleCompanyStatus(companyId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
    const message = 'Change company status to ' + newStatus + '?';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='update_company_status'>
            <input type='hidden' name='company_id' value='\${companyId}'>
            <input type='hidden' name='status' value='\${newStatus}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });
}
";

include 'includes/footer.php';
?>