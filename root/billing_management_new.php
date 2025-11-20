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

// Get companies with basic billing information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get companies with their subscription tiers
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
    
    // Calculate basic stats
    $total_companies = count($companies);
    $total_monthly_revenue = 0;
    $active_companies = 0;
    
    foreach ($companies as $company) {
        if ($company['status'] === 'active') {
            $active_companies++;
            if ($company['billing_cycle'] === 'yearly') {
                $total_monthly_revenue += $company['yearly_price'] / 12;
            } else {
                $total_monthly_revenue += $company['monthly_price'];
            }
        }
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $companies = [];
    $total_companies = 0;
    $total_monthly_revenue = 0;
    $active_companies = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - ROOT CONTROL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            padding: 30px;
        }
        
        .billing-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
        }
        
        .stats-row {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .billing-table {
            background: #3a3a3a;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: white;
        }
        
        .table-dark {
            background-color: #3a3a3a;
            margin: 0;
        }
        
        .table-dark th {
            border: none;
            background: #2d2d2d;
            color: #fff;
            font-weight: 600;
            padding: 15px;
        }
        
        .table-dark td {
            border: none;
            background: #3a3a3a;
            color: #e0e0e0;
            padding: 15px;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 6px 12px;
        }
        
        .btn-billing {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-billing:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            color: white;
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
                        <a class="nav-link" href="subscription_tiers.php">
                            <i class="fas fa-layer-group me-1"></i>Subscription Tiers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="billing_management.php">
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
        <!-- Header -->
        <div class="billing-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-credit-card me-3"></i>
                        Billing Management System
                    </h1>
                    <p class="mb-0 opacity-75">Comprehensive billing oversight for all companies in the platform</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-billing">
                        <i class="fas fa-file-invoice me-2"></i>Generate Reports
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row stats-row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_companies; ?></div>
                    <div class="stat-label">Total Companies</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $active_companies; ?></div>
                    <div class="stat-label">Active Subscribers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">£<?php echo number_format($total_monthly_revenue, 0); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">£<?php echo number_format($total_monthly_revenue * 12, 0); ?></div>
                    <div class="stat-label">Annual Revenue</div>
                </div>
            </div>
        </div>

        <!-- Company Billing Table -->
        <div class="billing-table">
            <div class="table-header">
                <h4 class="mb-0">
                    <i class="fas fa-table me-2"></i>Company Billing Overview
                </h4>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Database Error: <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-dark mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-building me-2"></i>Company</th>
                            <th><i class="fas fa-envelope me-2"></i>Contact</th>
                            <th><i class="fas fa-layer-group me-2"></i>Subscription</th>
                            <th><i class="fas fa-calendar me-2"></i>Billing Cycle</th>
                            <th><i class="fas fa-users me-2"></i>Users</th>
                            <th><i class="fas fa-chart-line me-2"></i>Status</th>
                            <th><i class="fas fa-cogs me-2"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                                    <p class="mb-0">No companies found in the system</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="text-white"><?php echo htmlspecialchars($company['name']); ?></strong>
                                            <br><small class="text-muted">ID: #<?php echo $company['id']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($company['contact_email']): ?>
                                            <div><small><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($company['contact_email']); ?></small></div>
                                        <?php endif; ?>
                                        <?php if ($company['contact_phone']): ?>
                                            <div><small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($company['contact_phone']); ?></small></div>
                                        <?php endif; ?>
                                        <?php if (!$company['contact_email'] && !$company['contact_phone']): ?>
                                            <small class="text-muted">No contact info</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($company['tier_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($company['tier_name']); ?></span>
                                            <br><small class="text-success">£<?php echo number_format($company['monthly_price'], 2); ?>/month</small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No Plan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo ucfirst($company['billing_cycle'] ?? 'monthly'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $company['user_count']; ?> users</span>
                                    </td>
                                    <td>
                                        <?php
                                        switch($company['status']) {
                                            case 'active':
                                                echo '<span class="badge bg-success">Active</span>';
                                                break;
                                            case 'suspended':
                                                echo '<span class="badge bg-danger">Suspended</span>';
                                                break;
                                            case 'inactive':
                                                echo '<span class="badge bg-warning">Inactive</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" title="Generate Invoice">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" title="Edit Billing">
                                                <i class="fas fa-edit"></i>
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

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-billing me-2 mb-2">
                            <i class="fas fa-file-invoice me-1"></i>Generate Monthly Invoices
                        </button>
                        <button class="btn btn-outline-info me-2 mb-2">
                            <i class="fas fa-download me-1"></i>Export Billing Report
                        </button>
                        <button class="btn btn-outline-warning mb-2">
                            <i class="fas fa-bell me-1"></i>Send Payment Reminders
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Billing System Status</h5>
                    </div>
                    <div class="card-body">
                        <p><span class="badge bg-success">✓</span> Database Connection: Active</p>
                        <p><span class="badge bg-success">✓</span> Companies Loaded: <?php echo $total_companies; ?></p>
                        <p><span class="badge bg-info">ℹ</span> This is a basic billing overview</p>
                        <small class="text-muted">Advanced billing features require additional setup</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>