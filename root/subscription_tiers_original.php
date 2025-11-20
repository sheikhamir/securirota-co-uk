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
        body {
            background: #1a1a1a;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 0;
            border-bottom: 3px solid #5a67d8;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            color: white !important;
            font-weight: 600;
        }
        
        .container-fluid {
            background: #2d2d2d;
            min-height: 100vh;
            padding-top: 30px;
        }
        
        .card {
            background: #3a3a3a;
            border: 1px solid #555;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .table-dark {
            background-color: #2a2a2a;
        }
        
        .table-dark th {
            border-top: none;
            border-bottom: 2px solid #555;
            color: #fff;
            font-weight: 600;
        }
        
        .table-dark td {
            border-top: 1px solid #444;
            color: #e0e0e0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
            border: none;
            color: #333;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            border: none;
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
        
        .text-success { color: #28a745 !important; }
        .text-warning { color: #ffc107 !important; }
        .text-danger { color: #dc3545 !important; }
        
        .modal-content {
            background: #3a3a3a;
            color: #e0e0e0;
            border-radius: 15px;
        }
        
        .modal-header {
            border-bottom: 1px solid #555;
        }
        
        .modal-footer {
            border-top: 1px solid #555;
        }
        
        .form-control, .form-select {
            background: #2a2a2a;
            border: 1px solid #555;
            color: #e0e0e0;
            border-radius: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            background: #2a2a2a;
            border-color: #667eea;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="root_dashboard.php">
                <i class="fas fa-crown me-2"></i>ROOT CONTROL
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="root_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="companies.php">
                            <i class="fas fa-building me-1"></i>Companies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="subscription_tiers.php">
                            <i class="fas fa-layer-group me-1"></i>Subscription Tiers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="billing_management.php">
                            <i class="fas fa-credit-card me-1"></i>Billing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="all_users.php">
                            <i class="fas fa-users me-1"></i>All Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system_resources.php">
                            <i class="fas fa-server me-1"></i>System Resources
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-layer-group text-warning me-2"></i>
                    Subscription Tier Management
                </h1>
                <p class="text-muted mb-0">Manage subscription plans, pricing, and feature sets</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTierModal">
                    <i class="fas fa-plus me-2"></i>Add New Tier
                </button>
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
            <div class="card-header">
                <h5 class="card-title mb-0 text-white">
                    <i class="fas fa-table me-2"></i>Subscription Tiers
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-striped" id="tiersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
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
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        No subscription tiers found. Create your first tier to get started.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tiers as $tier): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white"><?php echo htmlspecialchars($tier['name']); ?></strong>
                                            <?php if ($tier['is_custom']): ?>
                                                <span class="badge bg-info ms-2">Custom</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo htmlspecialchars($tier['description']); ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-users"></i> <?php echo $tier['max_officers']; ?> officers<br>
                                                <i class="fas fa-building"></i> <?php echo $tier['max_sites']; ?> sites<br>
                                                <i class="fas fa-handshake"></i> <?php echo $tier['max_clients']; ?> clients
                                            </small>
                                        </td>
                                        <td>
                                            <div class="text-success">
                                                <strong>£<?php echo number_format($tier['monthly_price'], 2); ?>/mo</strong>
                                            </div>
                                            <small class="text-muted">
                                                £<?php echo number_format($tier['yearly_price'], 2); ?>/year
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $features = json_decode($tier['features'], true) ?: [];
                                            if (!empty($features)): 
                                            ?>
                                                <small class="text-muted">
                                                    <?php echo count($features); ?> features
                                                    <i class="fas fa-info-circle ms-1" 
                                                       data-bs-toggle="tooltip" 
                                                       title="<?php echo implode(', ', array_map(function($f) use ($available_features) { return $available_features[$f] ?? $f; }, $features)); ?>"></i>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">No features</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tier['companies_using'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $tier['companies_using']; ?> companies</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not used</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tier['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary edit-tier-btn"
                                                        data-tier='<?php echo json_encode($tier); ?>'
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTierModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($tier['companies_using'] == 0): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger delete-tier-btn"
                                                            data-tier-id="<?php echo $tier['id']; ?>"
                                                            data-tier-name="<?php echo htmlspecialchars($tier['name']); ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteTierModal">
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