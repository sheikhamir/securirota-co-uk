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

// Handle billing actions
if ($_POST && isset($_POST['action'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        switch ($_POST['action']) {
            case 'update_billing_cycle':
                $company_id = (int)$_POST['company_id'];
                $billing_cycle = $_POST['billing_cycle'];
                $next_billing_date = $_POST['next_billing_date'];
                
                $stmt = $conn->prepare("
                    UPDATE companies 
                    SET billing_cycle = ?, next_billing_date = ?
                    WHERE id = ?
                ");
                $stmt->execute([$billing_cycle, $next_billing_date, $company_id]);
                
                $_SESSION['success'] = "Billing cycle updated successfully.";
                break;
                
            case 'update_billing_status':
                $billing_id = (int)$_POST['billing_id'];
                $new_status = $_POST['status'];
                
                $stmt = $conn->prepare("
                    UPDATE billing_history 
                    SET status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $billing_id]);
                
                $_SESSION['success'] = "Billing status updated successfully.";
                break;
                
            case 'generate_invoice':
                $company_id = (int)$_POST['company_id'];
                $amount = (float)$_POST['amount'];
                $notes = trim($_POST['description']);
                
                // Get the company's subscription tier for the billing entry
                $tier_stmt = $conn->prepare("SELECT subscription_tier_id FROM companies WHERE id = ?");
                $tier_stmt->execute([$company_id]);
                $company_data = $tier_stmt->fetch(PDO::FETCH_ASSOC);
                $subscription_tier_id = $company_data['subscription_tier_id'] ?? 1;
                
                // Generate invoice number
                $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($company_id, 4, '0', STR_PAD_LEFT);
                
                // Create billing entry
                $stmt = $conn->prepare("
                    INSERT INTO billing_history (company_id, subscription_tier_id, amount, billing_date, billing_cycle, status, invoice_number, notes, created_at) 
                    VALUES (?, ?, ?, CURDATE(), 'monthly', 'pending', ?, ?, NOW())
                ");
                $stmt->execute([$company_id, $subscription_tier_id, $amount, $invoice_number, $notes]);
                
                $_SESSION['success'] = "Invoice generated successfully.";
                break;
        }
        
        header('Location: billing_management.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get companies with billing information
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get companies with their subscription tiers and billing info
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.monthly_price,
            st.yearly_price,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
            (SELECT COUNT(*) FROM officers WHERE company_id = c.id) as officer_count,
            (SELECT COUNT(*) FROM sites WHERE company_id = c.id) as site_count,
            (SELECT SUM(amount) FROM billing_history WHERE company_id = c.id AND status = 'paid') as total_paid,
            (SELECT SUM(amount) FROM billing_history WHERE company_id = c.id AND status = 'pending') as pending_amount,
            (SELECT COUNT(*) FROM billing_history WHERE company_id = c.id) as billing_count
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.name ASC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent billing history
    $stmt = $conn->query("
        SELECT 
            bh.*,
            c.name as company_name
        FROM billing_history bh
        JOIN companies c ON bh.company_id = c.id
        ORDER BY bh.created_at DESC
        LIMIT 50
    ");
    $recent_billing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $stmt = $conn->query("
        SELECT 
            COUNT(DISTINCT c.id) as total_companies,
            COALESCE(SUM(CASE WHEN bh.status = 'paid' THEN bh.amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN bh.status = 'pending' THEN bh.amount ELSE 0 END), 0) as pending_revenue,
            COALESCE(SUM(CASE WHEN bh.status = 'overdue' THEN bh.amount ELSE 0 END), 0) as overdue_revenue
        FROM companies c
        LEFT JOIN billing_history bh ON c.id = bh.company_id
    ");
    $billing_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading billing data: " . $e->getMessage();
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
                                <th>Subscription</th>
                                <th>Billing Cycle</th>
                                <th>Total Paid</th>
                                <th>Pending</th>
                                <th>Next Billing</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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
                                            <span class="text-success">£<?php echo number_format($company['total_paid'] ?? 0, 2); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($company['pending_amount'] > 0): ?>
                                                <span class="text-warning">£<?php echo number_format($company['pending_amount'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">£0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($company['next_billing_date']): ?>
                                                <?php echo date('d/m/Y', strtotime($company['next_billing_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
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
                                                        data-bs-target="#generateInvoiceModal"
                                                        data-company-id="<?php echo $company['id']; ?>"
                                                        data-company-name="<?php echo htmlspecialchars($company['name']); ?>">
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
    </div>

    <!-- Generate Invoice Modal -->
    <div class="modal fade" id="generateInvoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="billing_management.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="generate_invoice">
                        <input type="hidden" name="company_id" id="invoice_company_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" id="invoice_company_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (£)</label>
                            <input type="number" class="form-control" name="amount" id="amount" min="0" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="3" 
                                      placeholder="Enter invoice description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate invoice modal
        document.getElementById('generateInvoiceModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var companyId = button.getAttribute('data-company-id');
            var companyName = button.getAttribute('data-company-name');
            
            document.getElementById('invoice_company_id').value = companyId;
            document.getElementById('invoice_company_name').value = companyName;
        });
    </script>
</body>
</html>