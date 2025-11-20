<?php
/**
 * Root User - Subscription Tier Management
 * Complete CRUD interface for managing subscription tiers
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'Subscription Tier Management';
$page_description = 'Manage subscription plans, pricing, and features';
$active_page = 'subscription_tiers';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'Company Management', 'url' => 'companies.php'],
    ['title' => 'Subscription Tiers']
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
            case 'add_tier':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $max_officers = (int)$_POST['max_officers'];
                $max_sites = (int)$_POST['max_sites'];
                $max_clients = (int)$_POST['max_clients'];
                $monthly_price = (float)$_POST['monthly_price'];
                $yearly_price = (float)$_POST['yearly_price'];
                $is_custom = isset($_POST['is_custom']) ? 1 : 0;
                
                // Handle features JSON
                $features = [];
                if (isset($_POST['features'])) {
                    foreach ($_POST['features'] as $feature => $enabled) {
                        $features[$feature] = $enabled === 'on';
                    }
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO subscription_tiers 
                    (name, description, max_officers, max_sites, max_clients, 
                     monthly_price, yearly_price, is_custom, features) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $description, $max_officers, $max_sites, $max_clients,
                    $monthly_price, $yearly_price, $is_custom, json_encode($features)
                ]);
                
                $_SESSION['success'] = "Subscription tier created successfully.";
                break;
                
            case 'update_tier':
                $tier_id = (int)$_POST['tier_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $max_officers = (int)$_POST['max_officers'];
                $max_sites = (int)$_POST['max_sites'];
                $max_clients = (int)$_POST['max_clients'];
                $monthly_price = (float)$_POST['monthly_price'];
                $yearly_price = (float)$_POST['yearly_price'];
                $is_custom = isset($_POST['is_custom']) ? 1 : 0;
                
                // Handle features JSON
                $features = [];
                if (isset($_POST['features'])) {
                    foreach ($_POST['features'] as $feature => $enabled) {
                        $features[$feature] = $enabled === 'on';
                    }
                }
                
                $stmt = $conn->prepare("
                    UPDATE subscription_tiers 
                    SET name = ?, description = ?, max_officers = ?, max_sites = ?, 
                        max_clients = ?, monthly_price = ?, yearly_price = ?, 
                        is_custom = ?, features = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $description, $max_officers, $max_sites, $max_clients,
                    $monthly_price, $yearly_price, $is_custom, json_encode($features), $tier_id
                ]);
                
                $_SESSION['success'] = "Subscription tier updated successfully.";
                break;
                
            case 'delete_tier':
                $tier_id = (int)$_POST['tier_id'];
                
                // Check if tier is being used
                $stmt = $conn->prepare("SELECT COUNT(*) FROM companies WHERE subscription_tier_id = ?");
                $stmt->execute([$tier_id]);
                $usage_count = $stmt->fetchColumn();
                
                if ($usage_count > 0) {
                    throw new Exception("Cannot delete this tier as it is being used by {$usage_count} company(ies).");
                }
                
                $stmt = $conn->prepare("DELETE FROM subscription_tiers WHERE id = ?");
                $stmt->execute([$tier_id]);
                
                $_SESSION['success'] = "Subscription tier deleted successfully.";
                break;
        }
        
        header('Location: subscription_tiers.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get subscription tiers with usage information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            st.*,
            COUNT(c.id) as companies_using
        FROM subscription_tiers st
        LEFT JOIN companies c ON st.id = c.subscription_tier_id
        GROUP BY st.id
        ORDER BY st.monthly_price ASC
    ");
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading subscription tiers: " . $e->getMessage();
}

// Define available features
$available_features = [
    'shift_scheduling' => 'Shift Scheduling',
    'basic_reporting' => 'Basic Reporting',
    'advanced_reporting' => 'Advanced Reporting',
    'officer_management' => 'Officer Management',
    'site_management' => 'Site Management',
    'client_management' => 'Client Management',
    'email_notifications' => 'Email Notifications',
    'mobile_app_access' => 'Mobile App Access',
    'custom_branding' => 'Custom Branding',
    'api_access' => 'API Access',
    'priority_support' => 'Priority Support',
    'dedicated_account_manager' => 'Dedicated Account Manager',
    'custom_integrations' => 'Custom Integrations',
    'custom_development' => 'Custom Development',
    'on_premise_deployment' => 'On-Premise Deployment'
];

// Template configuration
$enable_live_search = true;
$search_config = [
    'input_id' => 'tierSearch',
    'table_id' => 'tiersTable',
    'columns' => [0, 1] // Search tier name and description
];

$enable_table_sorting = true;
$sortable_table_id = 'tiersTable';

// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-layer-group text-warning me-2"></i>
                Subscription Tier Management
            </h1>
            <p class="mb-0 text-white-50">Manage subscription plans, pricing, and feature sets</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" data-bs-toggle="modal" data-bs-target="#addTierModal">
                <i class="fas fa-plus me-1"></i> Add Tier
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
                    <div class="stats-number"><?= count($tiers) ?></div>
                    <div class="stats-label">Total Tiers</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number">
                        <?= count(array_filter($tiers, function($t) { return $t['is_active']; })) ?>
                    </div>
                    <div class="stats-label">Active Tiers</div>
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
                        <?= array_sum(array_column($tiers, 'companies_using')) ?>
                    </div>
                    <div class="stats-label">Companies Using</div>
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
                        $<?= number_format(array_sum(array_column($tiers, 'monthly_price')), 0) ?>
                    </div>
                    <div class="stats-label">Total Monthly Value</div>
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
                    <input type="text" class="form-control" id="tierSearch" placeholder="Search subscription tiers...">
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($tiers) ?> tiers total
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Subscription Tiers Table -->
<div class="crown-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            All Subscription Tiers
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($tiers)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-layer-group fa-3x mb-3"></i>
                <h5>No Subscription Tiers Found</h5>
                <p class="text-muted">Start by creating your first subscription tier.</p>
                <button type="button" class="btn btn-crown" data-bs-toggle="modal" data-bs-target="#addTierModal">
                    <i class="fas fa-plus me-1"></i> Add Tier
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table crown-table" id="tiersTable">
                    <thead>
                        <tr>
                            <th data-sortable>Tier Name</th>
                            <th data-sortable>Limits</th>
                            <th data-sortable>Pricing</th>
                            <th>Features</th>
                            <th data-sortable>Usage</th>
                            <th data-sortable>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $tier): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($tier['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($tier['description']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="mb-1">
                                            <i class="fas fa-user-shield me-1"></i> <?= $tier['max_officers'] ?> officers
                                        </div>
                                        <div class="mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i> <?= $tier['max_sites'] ?> sites
                                        </div>
                                        <div>
                                            <i class="fas fa-users me-1"></i> <?= $tier['max_clients'] ?> clients
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="fw-bold mb-1">
                                            $<?= number_format($tier['monthly_price'], 2) ?>/month
                                        </div>
                                        <div class="text-muted">
                                            $<?= number_format($tier['yearly_price'], 2) ?>/year
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $features = json_decode($tier['features'], true) ?: [];
                                    $active_features = array_filter($features);
                                    ?>
                                    <div class="small">
                                        <?php if (count($active_features) > 0): ?>
                                            <span class="crown-badge crown-badge-info">
                                                <?= count($active_features) ?> features
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No features</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="crown-badge crown-badge-light me-2">
                                            <?= $tier['companies_using'] ?>
                                        </span>
                                        <small class="text-muted">companies</small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($tier['is_custom']): ?>
                                        <span class="crown-badge crown-badge-warning">Custom</span>
                                    <?php else: ?>
                                        <span class="crown-badge crown-badge-<?= $tier['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $tier['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="editTier(<?= htmlspecialchars(json_encode($tier)) ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Edit Tier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($tier['companies_using'] == 0): ?>
                                            <button type="button" class="btn btn-crown-outline btn-sm" 
                                                    onclick="deleteTier(<?= $tier['id'] ?>, '<?= htmlspecialchars($tier['name']) ?>')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Delete Tier">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Add Tier Modal -->
<div class="modal fade" id="addTierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Add Subscription Tier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_tier">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Tier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_officers" class="form-label">Max Officers *</label>
                                <input type="number" class="form-control" id="max_officers" name="max_officers" required min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_sites" class="form-label">Max Sites *</label>
                                <input type="number" class="form-control" id="max_sites" name="max_sites" required min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_clients" class="form-label">Max Clients *</label>
                                <input type="number" class="form-control" id="max_clients" name="max_clients" required min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="monthly_price" class="form-label">Monthly Price ($) *</label>
                                <input type="number" class="form-control" id="monthly_price" name="monthly_price" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="yearly_price" class="form-label">Yearly Price ($) *</label>
                                <input type="number" class="form-control" id="yearly_price" name="yearly_price" required min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_custom" name="is_custom">
                            <label class="form-check-label" for="is_custom">
                                Custom Tier (for special arrangements)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features</label>
                        <div class="row">
                            <?php foreach ($available_features as $key => $label): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_<?= $key ?>" name="features[<?= $key ?>]">
                                        <label class="form-check-label" for="feature_<?= $key ?>">
                                            <?= htmlspecialchars($label) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-plus me-1"></i> Create Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tier Modal -->
<div class="modal fade" id="editTierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Edit Subscription Tier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editTierForm" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_tier">
                    <input type="hidden" name="tier_id" id="edit_tier_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Tier Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="edit_description" name="description">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_max_officers" class="form-label">Max Officers *</label>
                                <input type="number" class="form-control" id="edit_max_officers" name="max_officers" required min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_max_sites" class="form-label">Max Sites *</label>
                                <input type="number" class="form-control" id="edit_max_sites" name="max_sites" required min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_max_clients" class="form-label">Max Clients *</label>
                                <input type="number" class="form-control" id="edit_max_clients" name="max_clients" required min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_monthly_price" class="form-label">Monthly Price ($) *</label>
                                <input type="number" class="form-control" id="edit_monthly_price" name="monthly_price" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_yearly_price" class="form-label">Yearly Price ($) *</label>
                                <input type="number" class="form-control" id="edit_yearly_price" name="yearly_price" required min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_custom" name="is_custom">
                            <label class="form-check-label" for="edit_is_custom">
                                Custom Tier (for special arrangements)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features</label>
                        <div class="row" id="edit_features">
                            <?php foreach ($available_features as $key => $label): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_feature_<?= $key ?>" name="features[<?= $key ?>]">
                                        <label class="form-check-label" for="edit_feature_<?= $key ?>">
                                            <?= htmlspecialchars($label) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-save me-1"></i> Update Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Edit tier function
function editTier(tier) {
    document.getElementById('edit_tier_id').value = tier.id;
    document.getElementById('edit_name').value = tier.name;
    document.getElementById('edit_description').value = tier.description || '';
    document.getElementById('edit_max_officers').value = tier.max_officers;
    document.getElementById('edit_max_sites').value = tier.max_sites;
    document.getElementById('edit_max_clients').value = tier.max_clients;
    document.getElementById('edit_monthly_price').value = tier.monthly_price;
    document.getElementById('edit_yearly_price').value = tier.yearly_price;
    document.getElementById('edit_is_custom').checked = tier.is_custom == 1;
    
    // Reset all feature checkboxes
    const featureCheckboxes = document.querySelectorAll('#edit_features input[type=\"checkbox\"]');
    featureCheckboxes.forEach(checkbox => checkbox.checked = false);
    
    // Set feature checkboxes based on tier data
    const features = tier.features ? JSON.parse(tier.features) : {};
    Object.keys(features).forEach(feature => {
        const checkbox = document.getElementById('edit_feature_' + feature);
        if (checkbox && features[feature]) {
            checkbox.checked = true;
        }
    });
    
    RootCommon.showModal('editTierModal');
}

// Delete tier function
function deleteTier(tierId, tierName) {
    const message = 'Are you sure you want to delete the tier \"' + tierName + '\"? This action cannot be undone.';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='delete_tier'>
            <input type='hidden' name='tier_id' value='\${tierId}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });
}
";

include 'includes/footer.php';
?>