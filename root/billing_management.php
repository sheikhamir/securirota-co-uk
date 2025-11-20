<?php
/**
 * Root User - Billing Management
 * Comprehensive billing management for all companies
 */
session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

$page_title = 'Billing Management';

// Get companies with basic information (simplified to avoid billing_history table issues)
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get companies with their subscription tiers (simplified query)
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.monthly_price,
            st.yearly_price,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.name ASC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Simple stats
    $billing_stats = [
        'total_companies' => count($companies),
        'total_revenue' => 0,
        'pending_revenue' => 0,
        'overdue_revenue' => 0
    ];
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading billing data: " . $e->getMessage();
    $companies = [];
    $billing_stats = [
        'total_companies' => 0,
        'total_revenue' => 0,
        'pending_revenue' => 0,
        'overdue_revenue' => 0
    ];
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
                                    <i class="fas fa-credit-card text-warning"></i> Billing Management
                                </h1>
                                <p class="text-muted mb-0">Manage billing, invoices, and payment tracking for all companies</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-circle"></i> Billing System Online
                                </span>
                                <small class="text-muted">Last updated: <?php echo date('H:i:s'); ?></small>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x mb-3"></i>
                                <div class="stat-number"><?= $billing_stats['total_companies'] ?></div>
                                <div class="small">Total Companies</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Billing Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-pound-sign fa-2x mb-3"></i>
                                <div class="stat-number">£<?= number_format($billing_stats['total_revenue'], 0) ?></div>
                                <div class="small">Total Revenue</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Monthly</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-3"></i>
                                <div class="stat-number">£<?= number_format($billing_stats['pending_revenue'], 0) ?></div>
                                <div class="small">Pending Revenue</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Awaiting</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <div class="stat-number">£<?= number_format($billing_stats['overdue_revenue'], 0) ?></div>
                                <div class="small">Overdue Revenue</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Urgent</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company Billing Table -->
                <div class="card">
                    <div class="card-header bg-gradient">
                        <h5 class="card-title mb-0 text-white">
                            <i class="fas fa-table me-2"></i>Company Billing Overview
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Company</th>
                                        <th>Contact</th>
                                        <th>Subscription</th>
                                        <th>Billing Cycle</th>
                                        <th>Users</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($companies)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="fas fa-credit-card fa-3x mb-3 text-muted"></i><br>
                                                <strong>No companies with billing information found.</strong><br>
                                                <small>Companies will appear here once they have billing data.</small>
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
                                                    <?php if (empty($company['contact_email']) && empty($company['contact_phone'])): ?>
                                                        <small class="text-muted">No contact info</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <?php if ($company['tier_name']): ?>
                                                        <span class="badge bg-info mb-1"><?php echo htmlspecialchars($company['tier_name']); ?></span><br>
                                                        <small class="text-muted fw-bold">
                                                            £<?php echo number_format($company['billing_cycle'] === 'yearly' ? $company['yearly_price'] : $company['monthly_price'], 2); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">No subscription</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-secondary"><?php echo ucfirst($company['billing_cycle'] ?? 'monthly'); ?></span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-users text-info me-2"></i>
                                                        <span class="fw-bold text-info"><?php echo $company['user_count']; ?> users</span>
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
                                                        <button type="button" class="btn btn-sm btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-success" title="Generate Invoice">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info" title="Payment History">
                                                            <i class="fas fa-history"></i>
                                                        </button>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>