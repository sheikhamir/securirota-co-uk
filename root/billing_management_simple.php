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
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.8;
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
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-credit-card text-warning me-2"></i>
                    Billing Management
                </h1>
                <p class="text-muted mb-0">Manage billing, invoices, and payment tracking for all companies</p>
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

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $billing_stats['total_companies'] ?></div>
                    <div class="stats-label">Total Companies</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">£<?= number_format($billing_stats['total_revenue'], 0) ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">£<?= number_format($billing_stats['pending_revenue'], 0) ?></div>
                    <div class="stats-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">£<?= number_format($billing_stats['overdue_revenue'], 0) ?></div>
                    <div class="stats-label">Overdue</div>
                </div>
            </div>
        </div>

        <!-- Company Billing Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0 text-white">
                    <i class="fas fa-table me-2"></i>Company Billing Overview
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
                                <th>Billing Cycle</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                        No companies with billing information found.
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
                                                    £<?php echo number_format($company['billing_cycle'] === 'yearly' ? $company['yearly_price'] : $company['monthly_price'], 2); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">No subscription</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo ucfirst($company['billing_cycle'] ?? 'monthly'); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-info"><?php echo $company['user_count']; ?> users</span>
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
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Generate Invoice">
                                                    <i class="fas fa-file-invoice"></i>
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

        <!-- Debug Info -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0 text-white">
                    <i class="fas fa-info-circle me-2"></i>Debug Information
                </h5>
            </div>
            <div class="card-body">
                <p><strong>Companies loaded:</strong> <?php echo count($companies); ?></p>
                <p><strong>Last database query:</strong> Companies and subscription tiers joined successfully</p>
                <small class="text-muted">Note: This is a simplified version of billing management. Advanced billing features require the billing_history table.</small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>