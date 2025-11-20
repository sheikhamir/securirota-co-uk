<?php
/**
 * BILLING MANAGEMENT - FIXED VERSION
 * This is the correct billing management page
 */
session_start();
require_once '../config/config.php';

if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Get companies
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.monthly_price,
            st.yearly_price
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.name ASC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $companies = [];
    $error = $e->getMessage();
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
        
        .billing-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 15px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .billing-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .billing-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 5px 8px;
            border-radius: 6px;
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
        
        <!-- Clear identifier that this is billing management -->
        <div class="billing-card">
            <div class="billing-number">💳</div>
            <div class="billing-label">BILLING MANAGEMENT SYSTEM</div>
            <p class="mt-3 mb-0">Manage company subscriptions, invoices, and payments</p>
        </div>

        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-credit-card text-warning me-2"></i>
                    Company Billing Overview
                </h1>
                <p class="text-muted mb-0">Monitor subscription status and billing for all companies</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary">
                    <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?= count($companies) ?></h3>
                        <p class="text-muted mb-0">Total Companies</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-warning">£0</h3>
                        <p class="text-muted mb-0">Monthly Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-info">£0</h3>
                        <p class="text-muted mb-0">Pending Payments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-danger">£0</h3>
                        <p class="text-muted mb-0">Overdue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Companies Billing Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0 text-white">
                    <i class="fas fa-table me-2"></i>Company Billing Status
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error: <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Subscription Tier</th>
                                <th>Monthly Cost</th>
                                <th>Billing Cycle</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No companies found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white"><?= htmlspecialchars($company['name']) ?></strong>
                                            <br><small class="text-muted">ID: <?= $company['id'] ?></small>
                                        </td>
                                        <td>
                                            <?php if ($company['tier_name']): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($company['tier_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Tier</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($company['monthly_price']): ?>
                                                <span class="text-success">£<?= number_format($company['monthly_price'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?= ucfirst($company['billing_cycle'] ?? 'monthly') ?></span>
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
                                            <span class="badge <?= $status_class ?>"><?= ucfirst($company['status']) ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="View Billing">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Generate Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Payment History">
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

        <!-- Notice -->
        <div class="card mt-4">
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> This is the billing management interface. Advanced billing features will be implemented based on business requirements.
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>