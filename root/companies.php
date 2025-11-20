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
                                    <i class="fas fa-building text-warning"></i> Company Management
                                </h1>
                                <p class="text-muted mb-0">Manage all companies in the multi-tenant system</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-circle"></i> System Active
                                </span>
                                <a href="../root/onboarding.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add New Company
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x mb-3"></i>
                                <div class="stat-number"><?= count($companies) ?></div>
                                <div class="small">Total Companies</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Registered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <div class="stat-number"><?= count(array_filter($companies, function($c) { return $c['status'] === 'active'; })) ?></div>
                                <div class="small">Active Companies</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Operational</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-3"></i>
                                <div class="stat-number"><?= array_sum(array_column($companies, 'user_count')) ?></div>
                                <div class="small">Total Users</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Platform Wide</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-2x mb-3"></i>
                                <div class="stat-number"><?= array_sum(array_column($companies, 'officer_count')) ?></div>
                                <div class="small">Security Officers</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <?php /*<div class="row mb-4">
                    <div class="col-12">
                        <h4 class="text-dark mb-3">
                            <i class="fas fa-bolt"></i> Company Management Actions
                        </h4>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-primary-gradient text-white mx-auto">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <h5 class="card-title">Add Company</h5>
                                <p class="card-text text-muted">Onboard new company to platform</p>
                                <a href="../root/onboarding.php" class="btn btn-primary">Add Company</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-success-gradient text-white mx-auto">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h5 class="card-title">Analytics</h5>
                                <p class="card-text text-muted">View company performance metrics</p>
                                <a href="#" class="btn btn-success">View Analytics</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-warning-gradient text-white mx-auto">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h5 class="card-title">Bulk Operations</h5>
                                <p class="card-text text-muted">Mass update company settings</p>
                                <a href="#" class="btn btn-warning">Bulk Edit</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-info-gradient text-white mx-auto">
                                    <i class="fas fa-file-export"></i>
                                </div>
                                <h5 class="card-title">Export Data</h5>
                                <p class="card-text text-muted">Export company information</p>
                                <a href="#" class="btn btn-info">Export</a>
                            </div>
                        </div>
                    </div>
                </div> */ ?>

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
                    <div class="card-header bg-gradient">
                        <h5 class="card-title mb-0 text-white">
                            <i class="fas fa-table me-2"></i>Companies Overview
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Company Details</th>
                                        <th>Contact Information</th>
                                        <th>Subscription</th>
                                        <th>Resources</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($companies)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="fas fa-building fa-3x mb-3 text-muted"></i><br>
                                                <strong>No companies found.</strong><br>
                                                <small>Add your first company to get started.</small>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($companies as $company): ?>
                                            <tr class="border-bottom">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                            <i class="fas fa-building text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark"><?php echo htmlspecialchars($company['name']); ?></strong>
                                                            <br><small class="text-muted">ID: <?php echo $company['id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <?php if (!empty($company['contact_email'])): ?>
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <span class="text-dark"><?php echo htmlspecialchars($company['contact_email']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($company['contact_phone'])): ?>
                                                        <div>
                                                            <i class="fas fa-phone text-success me-2"></i>
                                                            <span class="text-dark"><?php echo htmlspecialchars($company['contact_phone']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <?php if ($company['tier_name']): ?>
                                                        <span class="badge bg-info mb-1"><?php echo htmlspecialchars($company['tier_name']); ?></span><br>
                                                        <small class="text-muted fw-bold">
                                                            £<?php echo number_format($company['billing_cycle'] === 'yearly' ? $company['yearly_price'] : $company['monthly_price'], 2); ?>/<?php echo $company['billing_cycle'] ?? 'month'; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">No subscription</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="mb-1">
                                                            <i class="fas fa-users text-primary me-2"></i>
                                                            <span class="fw-bold"><?php echo $company['user_count']; ?></span> users
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-shield-alt text-success me-2"></i>
                                                            <span class="fw-bold"><?php echo $company['officer_count']; ?></span> officers
                                                        </div>
                                                        <div>
                                                            <i class="fas fa-building text-info me-2"></i>
                                                            <span class="fw-bold"><?php echo $company['site_count']; ?></span> sites
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
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
                                                <td class="py-3">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editSubscriptionModal"
                                                                data-company-id="<?php echo $company['id']; ?>"
                                                                data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                                data-current-tier="<?php echo $company['subscription_tier_id']; ?>"
                                                                data-current-cycle="<?php echo $company['billing_cycle']; ?>"
                                                                title="Edit Subscription">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($company['status'] !== 'suspended'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#suspendModal"
                                                                    data-company-id="<?php echo $company['id']; ?>"
                                                                    data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                                    title="Suspend Company">
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