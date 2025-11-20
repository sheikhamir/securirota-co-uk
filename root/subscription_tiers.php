<?php
/**
 * Root User - Subscription Tier Management
 * Complete CRUD interface for managing subscription tiers
 */
session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

$page_title = 'Subscription Tier Management';

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
                        if ($enabled === 'on') {
                            $features[] = $feature;
                        }
                    }
                }
                $features_json = json_encode($features);
                
                $stmt = $conn->prepare("INSERT INTO subscription_tiers (name, description, max_officers, max_sites, max_clients, monthly_price, yearly_price, features, is_custom) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $max_officers, $max_sites, $max_clients, $monthly_price, $yearly_price, $features_json, $is_custom]);
                
                $_SESSION['success'] = "Subscription tier added successfully.";
                break;
                
            case 'edit_tier':
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
                        if ($enabled === 'on') {
                            $features[] = $feature;
                        }
                    }
                }
                $features_json = json_encode($features);
                
                $stmt = $conn->prepare("UPDATE subscription_tiers SET name = ?, description = ?, max_officers = ?, max_sites = ?, max_clients = ?, monthly_price = ?, yearly_price = ?, features = ?, is_custom = ? WHERE id = ?");
                $stmt->execute([$name, $description, $max_officers, $max_sites, $max_clients, $monthly_price, $yearly_price, $features_json, $is_custom, $tier_id]);
                
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ROOT CONTROL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .quick-action-card {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 15px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #5a5c69;
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .bg-primary-gradient { background: linear-gradient(45deg, #4e73df, #224abe); }
        .bg-success-gradient { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .bg-warning-gradient { background: linear-gradient(45deg, #f6c23e, #dda20a); }
        .bg-danger-gradient { background: linear-gradient(45deg, #e74a3b, #c82333); }
        .bg-info-gradient { background: linear-gradient(45deg, #36b9cc, #258391); }
        .bg-secondary-gradient { background: linear-gradient(45deg, #858796, #5a5c69); }
        
        .card {
            border: 1px solid #e3e6f0;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header.bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .table th {
            border-top: none;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            border-top: 1px solid #e3e6f0;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .avatar-sm {
            width: 40px;
            height: 40px;
        }
        
        .btn-group .btn {
            transition: all 0.3s ease;
        }
        
        .btn-group .btn:hover {
            transform: translateY(-1px);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 5px 8px;
            border-radius: 6px;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.175);
        }
        
        .modal-header {
            border-bottom: 1px solid #e3e6f0;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-footer {
            border-top: 1px solid #e3e6f0;
            border-radius: 0 0 15px 15px;
        }
        
        .form-control, .form-select {
            border: 1px solid #d1d3e2;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Include root navigation -->
        <?php include 'components/navigation.php'; ?>
        
        <div id="content">
            <div class="container-fluid">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 text-dark mb-0">
                                    <i class="fas fa-layer-group text-warning"></i> Subscription Tier Management
                                </h1>
                                <p class="text-muted mb-0">Manage subscription plans, pricing, and feature sets</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-circle"></i> Tier System Active
                                </span>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTierModal">
                                    <i class="fas fa-plus me-2"></i>Add New Tier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-layer-group fa-2x mb-3"></i>
                                <div class="stat-number"><?= count($tiers) ?></div>
                                <div class="small">Total Tiers</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Active Plans</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x mb-3"></i>
                                <div class="stat-number"><?= array_sum(array_column($tiers, 'companies_using')) ?></div>
                                <div class="small">Companies Subscribed</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Active Users</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-pound-sign fa-2x mb-3"></i>
                                <div class="stat-number">£<?= !empty($tiers) ? number_format(min(array_column($tiers, 'monthly_price')), 0) : '0' ?></div>
                                <div class="small">Starting Price</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Per Month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-crown fa-2x mb-3"></i>
                                <div class="stat-number"><?= !empty($tiers) ? number_format(max(array_column($tiers, 'monthly_price')), 0) : '0' ?></div>
                                <div class="small">Premium Price</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Top Tier</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

                <!-- Subscription Tiers Table -->
                <div class="card">
                    <div class="card-header bg-gradient">
                        <h5 class="card-title mb-0 text-white">
                            <i class="fas fa-table me-2"></i>Subscription Tiers
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tiersTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Tier Details</th>
                                        <th>Limits</th>
                                        <th>Pricing</th>
                                        <th>Features</th>
                                        <th>Usage</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tiers)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="fas fa-layer-group fa-3x mb-3 text-muted"></i><br>
                                                <strong>No subscription tiers found.</strong><br>
                                                <small>Create your first tier to get started.</small>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tiers as $tier): ?>
                                            <tr class="border-bottom">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                            <i class="fas fa-layer-group text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark"><?php echo htmlspecialchars($tier['name']); ?></strong>
                                                            <?php if ($tier['is_custom']): ?>
                                                                <span class="badge bg-info ms-2">Custom</span>
                                                            <?php endif; ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($tier['description']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="mb-1">
                                                            <i class="fas fa-users text-primary me-2"></i>
                                                            <span class="fw-bold"><?php echo $tier['max_officers']; ?></span> officers
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-building text-success me-2"></i>
                                                            <span class="fw-bold"><?php echo $tier['max_sites']; ?></span> sites
                                                        </div>
                                                        <div>
                                                            <i class="fas fa-handshake text-info me-2"></i>
                                                            <span class="fw-bold"><?php echo $tier['max_clients']; ?></span> clients
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="text-success fw-bold mb-1">
                                                        £<?php echo number_format($tier['monthly_price'], 2); ?>/mo
                                                    </div>
                                                    <small class="text-muted">
                                                        £<?php echo number_format($tier['yearly_price'], 2); ?>/year
                                                    </small>
                                                </td>
                                                <td class="py-3">
                                                    <?php
                                                    $features = json_decode($tier['features'], true) ?: [];
                                                    if (!empty($features)): 
                                                    ?>
                                                        <span class="badge bg-info me-1"><?php echo count($features); ?> features</span>
                                                        <i class="fas fa-info-circle text-muted" 
                                                           data-bs-toggle="tooltip" 
                                                           title="<?php echo implode(', ', array_map(function($f) use ($available_features) { return $available_features[$f] ?? $f; }, $features)); ?>"></i>
                                                    <?php else: ?>
                                                        <span class="text-muted">No features</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <?php if ($tier['companies_using'] > 0): ?>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-building text-success me-2"></i>
                                                            <span class="badge bg-success"><?php echo $tier['companies_using']; ?> companies</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not used</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <?php if ($tier['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary edit-tier-btn"
                                                                data-tier='<?php echo json_encode($tier); ?>'
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editTierModal"
                                                                title="Edit Tier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-info"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($tier['companies_using'] == 0): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger delete-tier-btn"
                                                                    data-tier-id="<?php echo $tier['id']; ?>"
                                                                    data-tier-name="<?php echo htmlspecialchars($tier['name']); ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#deleteTierModal"
                                                                    title="Delete Tier">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Tier Modal -->
    <div class="modal fade" id="addTierModal" tabindex="-1" aria-labelledby="addTierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTierModalLabel">
                        <i class="fas fa-plus me-2"></i>Add New Subscription Tier
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="subscription_tiers.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_tier">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Tier Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_custom" name="is_custom">
                                    <label class="form-check-label" for="is_custom">
                                        Custom Tier (Enterprise/Special)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_officers" class="form-label">Max Officers</label>
                                    <input type="number" class="form-control" id="max_officers" name="max_officers" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_sites" class="form-label">Max Sites</label>
                                    <input type="number" class="form-control" id="max_sites" name="max_sites" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_clients" class="form-label">Max Clients</label>
                                    <input type="number" class="form-control" id="max_clients" name="max_clients" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="monthly_price" class="form-label">Monthly Price (£)</label>
                                    <input type="number" class="form-control" id="monthly_price" name="monthly_price" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="yearly_price" class="form-label">Yearly Price (£)</label>
                                    <input type="number" class="form-control" id="yearly_price" name="yearly_price" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Features</label>
                            <div class="row">
                                <?php foreach ($available_features as $key => $label): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="feature_<?php echo $key; ?>" name="features[<?php echo $key; ?>]">
                                            <label class="form-check-label" for="feature_<?php echo $key; ?>">
                                                <?php echo $label; ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Tier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>