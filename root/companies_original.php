<?php
/**
 * Root User - Company Management System
 * Complete interface for managing all companies in the system
 */
session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

$page_title = 'Company Management';

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
                        next_billing_date = ?
                    WHERE id = ?
                ");
                $stmt->execute([$tier_id, $billing_cycle, $start_date, $end_date, $end_date, $company_id]);
                
                $_SESSION['success'] = "Subscription updated successfully.";
                break;
                
            case 'suspend_company':
                $company_id = (int)$_POST['company_id'];
                $reason = trim($_POST['reason']);
                
                $stmt = $conn->prepare("UPDATE companies SET status = 'suspended', suspension_reason = ? WHERE id = ?");
                $stmt->execute([$reason, $company_id]);
                
                $_SESSION['success'] = "Company suspended successfully.";
                break;
        }
        
        header('Location: companies.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get companies with detailed information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.monthly_price,
            st.yearly_price,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
            (SELECT COUNT(*) FROM officers WHERE company_id = c.id) as officer_count,
            (SELECT COUNT(*) FROM sites WHERE company_id = c.id) as site_count
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.name ASC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subscription tiers for dropdowns
    $stmt = $conn->query("SELECT * FROM subscription_tiers WHERE is_active = 1 ORDER BY monthly_price ASC");
    $subscription_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading companies: " . $e->getMessage();
}
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
                        <a class="nav-link active" href="companies.php">
                            <i class="fas fa-building me-1"></i>Companies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscription_tiers.php">
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
                    <i class="fas fa-building text-warning me-2"></i>
                    Company Management
                </h1>
                <p class="text-muted mb-0">Manage all companies in the multi-tenant system</p>
            </div>
            <div>
                <a href="../root/onboarding.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Company
                </a>
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

        <!-- Companies Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0 text-white">
                    <i class="fas fa-table me-2"></i>Companies Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Subscription</th>
                                <th>Resources</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-building fa-2x mb-2"></i><br>
                                        No companies found. Add your first company to get started.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white"><?php echo htmlspecialchars($company['name']); ?></strong>
                                            <br><small class="text-muted">ID: <?php echo $company['id']; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($company['contact_email']): ?>
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($company['contact_email']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($company['contact_phone']): ?>
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($company['contact_phone']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($company['tier_name']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($company['tier_name']); ?></span><br>
                                                <small class="text-muted">
                                                    £<?php echo number_format($company['billing_cycle'] === 'yearly' ? $company['yearly_price'] : $company['monthly_price'], 2); ?>/<?php echo $company['billing_cycle'] ?? 'month'; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">No subscription</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-users"></i> <?php echo $company['user_count']; ?> users<br>
                                                <i class="fas fa-shield-alt"></i> <?php echo $company['officer_count']; ?> officers<br>
                                                <i class="fas fa-building"></i> <?php echo $company['site_count']; ?> sites
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch($company['status']) {
                                                case 'active': $status_class = 'bg-success'; break;
                                                case 'suspended': $status_class = 'bg-danger'; break;
                                                case 'inactive': $status_class = 'bg-warning'; break;
                                                default: $status_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($company['status']); ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editSubscriptionModal"
                                                        data-company-id="<?php echo $company['id']; ?>"
                                                        data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                        data-current-tier="<?php echo $company['subscription_tier_id']; ?>"
                                                        data-current-cycle="<?php echo $company['billing_cycle']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($company['status'] !== 'suspended'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#suspendModal"
                                                            data-company-id="<?php echo $company['id']; ?>"
                                                            data-company-name="<?php echo htmlspecialchars($company['name']); ?>">
                                                        <i class="fas fa-ban"></i>
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

    <!-- Edit Subscription Modal -->
    <div class="modal fade" id="editSubscriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Update Subscription
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="companies.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_subscription">
                        <input type="hidden" name="company_id" id="edit_company_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" id="edit_company_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subscription_tier_id" class="form-label">Subscription Tier</label>
                            <select class="form-select" name="subscription_tier_id" id="subscription_tier_id" required>
                                <option value="">Select Tier</option>
                                <?php foreach ($subscription_tiers as $tier): ?>
                                    <option value="<?php echo $tier['id']; ?>" 
                                            data-monthly="<?php echo $tier['monthly_price']; ?>"
                                            data-yearly="<?php echo $tier['yearly_price']; ?>">
                                        <?php echo htmlspecialchars($tier['name']); ?> - 
                                        £<?php echo number_format($tier['monthly_price'], 2); ?>/month
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="billing_cycle" class="form-label">Billing Cycle</label>
                            <select class="form-select" name="billing_cycle" id="billing_cycle" required>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly (Save 20%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Subscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suspend Company Modal -->
    <div class="modal fade" id="suspendModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">
                        <i class="fas fa-ban me-2"></i>Suspend Company
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="companies.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="suspend_company">
                        <input type="hidden" name="company_id" id="suspend_company_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will suspend the company and block all user access.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" id="suspend_company_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Suspension Reason</label>
                            <textarea class="form-control" name="reason" id="reason" rows="3" 
                                      placeholder="Enter reason for suspension..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-ban me-2"></i>Suspend Company
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit subscription modal
        document.getElementById('editSubscriptionModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var companyId = button.getAttribute('data-company-id');
            var companyName = button.getAttribute('data-company-name');
            var currentTier = button.getAttribute('data-current-tier');
            var currentCycle = button.getAttribute('data-current-cycle');
            
            document.getElementById('edit_company_id').value = companyId;
            document.getElementById('edit_company_name').value = companyName;
            
            if (currentTier) {
                document.getElementById('subscription_tier_id').value = currentTier;
            }
            
            if (currentCycle) {
                document.getElementById('billing_cycle').value = currentCycle;
            }
        });

        // Suspend modal
        document.getElementById('suspendModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var companyId = button.getAttribute('data-company-id');
            var companyName = button.getAttribute('data-company-name');
            
            document.getElementById('suspend_company_id').value = companyId;
            document.getElementById('suspend_company_name').value = companyName;
        });
    </script>
</body>
</html>