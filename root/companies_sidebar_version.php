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
                        next_billing_date = ?,
                        billing_status = 'active'
                    WHERE id = ?
                ");
                $stmt->execute([$tier_id, $billing_cycle, $start_date, $end_date, $end_date, $company_id]);
                
                $_SESSION['success'] = "Subscription updated successfully.";
                break;
                
            case 'add_company':
                $name = trim($_POST['company_name']);
                $contact_email = trim($_POST['contact_email']);
                $contact_phone = trim($_POST['contact_phone']);
                $tier_id = (int)$_POST['subscription_tier_id'];
                $billing_cycle = $_POST['billing_cycle'];
                
                if (empty($name)) {
                    throw new Exception('Company name is required.');
                }
                
                // Create company
                $stmt = $conn->prepare("
                    INSERT INTO companies 
                    (name, contact_email, contact_phone, subscription_tier_id, billing_cycle, 
                     subscription_start_date, subscription_end_date, next_billing_date, 
                     status, billing_status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'active', NOW())
                ");
                
                $start_date = date('Y-m-d');
                $end_date = $billing_cycle === 'yearly' ? 
                    date('Y-m-d', strtotime('+1 year')) : 
                    date('Y-m-d', strtotime('+1 month'));
                
                $stmt->execute([
                    $name, $contact_email, $contact_phone, $tier_id, $billing_cycle,
                    $start_date, $end_date, $end_date
                ]);
                
                $_SESSION['success'] = "Company created successfully.";
                break;
        }
        
        header('Location: companies.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get companies with subscription information
$companies = [];
$subscription_tiers = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all companies with subscription details
    $stmt = $conn->query("
        SELECT 
            c.*,
            st.name as tier_name,
            st.max_officers,
            st.max_sites,
            st.max_clients,
            st.monthly_price,
            st.yearly_price,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
            (SELECT COUNT(*) FROM officers WHERE company_id = c.id) as officer_count,
            (SELECT COUNT(*) FROM sites WHERE company_id = c.id) as site_count,
            (SELECT COUNT(*) FROM clients WHERE company_id = c.id) as client_count
        FROM companies c
        LEFT JOIN subscription_tiers st ON c.subscription_tier_id = st.id
        ORDER BY c.created_at DESC
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
    <title><?php echo $page_title; ?> - Root Control Center</title>
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
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
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
                                <p class="text-muted mb-0">Manage all companies, subscriptions, and billing</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                                    <i class="fas fa-plus me-1"></i> Add Company
                                </button>
                                <small class="text-muted">Last updated: <?php echo date('H:i:s'); ?></small>
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
                                <div class="stat-number"><?php echo count($companies); ?></div>
                                <div class="small">Total Companies</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><?php echo count(array_filter($companies, function($c) { return $c['status'] === 'active'; })); ?> Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo count(array_filter($companies, function($c) { return $c['status'] === 'active'; })); ?></div>
                                <div class="small">Active Companies</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Running</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-pause-circle fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo count(array_filter($companies, function($c) { return $c['status'] === 'suspended'; })); ?></div>
                                <div class="small">Suspended</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Paused</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                                <div class="stat-number">
                                    $<?php 
                                    $total_revenue = 0;
                                    foreach($companies as $company) {
                                        if ($company['billing_cycle'] === 'yearly') {
                                            $total_revenue += $company['yearly_price'];
                                        } else {
                                            $total_revenue += $company['monthly_price'] * 12;
                                        }
                                    }
                                    echo number_format($total_revenue, 0);
                                    ?>
                                </div>
                                <div class="small">Annual Revenue</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Projected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="companySearch" placeholder="Search companies...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i><?php echo count($companies); ?> companies total</small>
                    </div>
                </div>

                <!-- Companies Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Companies</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="companiesTable">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Subscription</th>
                                        <th>Usage</th>
                                        <th>Billing</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?= strtoupper(substr($company['name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($company['name']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($company['contact_email'] ?: 'No email') ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($company['tier_name'] ?: 'No tier') ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($company['max_officers'] ?: '0') ?> officers, <?= htmlspecialchars($company['max_sites'] ?: '0') ?> sites</small>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <i class="fas fa-users me-1"></i><?= htmlspecialchars($company['user_count']) ?> users<br>
                                                <i class="fas fa-shield-alt me-1"></i><?= htmlspecialchars($company['officer_count']) ?> officers<br>
                                                <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($company['site_count']) ?> sites
                                            </div>
                                        </td>
                                        <td>
                                            <strong>$<?= htmlspecialchars($company['billing_cycle'] === 'yearly' ? $company['yearly_price'] : $company['monthly_price']) ?>/<?= htmlspecialchars($company['billing_cycle'] === 'yearly' ? 'year' : 'month') ?></strong>
                                            <br><small class="text-muted">Next: <?= htmlspecialchars($company['next_billing_date'] ? date('M j, Y', strtotime($company['next_billing_date'])) : 'Not set') ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary';
                                            switch($company['status']) {
                                                case 'active': $status_class = 'success'; break;
                                                case 'suspended': $status_class = 'warning'; break;
                                                case 'inactive': $status_class = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?= $status_class ?>"><?= ucfirst(htmlspecialchars($company['status'])) ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-<?= $company['status'] === 'active' ? 'warning' : 'success' ?>" 
                                                        onclick="toggleCompanyStatus(<?= $company['id'] ?>, '<?= $company['status'] ?>')" 
                                                        title="<?= $company['status'] === 'active' ? 'Suspend' : 'Activate' ?>">
                                                    <i class="fas fa-<?= $company['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> <!-- End container-fluid -->
        </div> <!-- End content -->
    </div> <!-- End wrapper -->

    <!-- Add Company Modal -->
    <div class="modal fade" id="addCompanyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email">
                        </div>
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="contact_phone" name="contact_phone">
                        </div>
                        <div class="mb-3">
                            <label for="subscription_tier_id" class="form-label">Subscription Tier</label>
                            <select class="form-control" id="subscription_tier_id" name="subscription_tier_id" required>
                                <option value="">Select a tier</option>
                                <?php foreach ($subscription_tiers as $tier): ?>
                                    <option value="<?= $tier['id'] ?>"><?= htmlspecialchars($tier['name']) ?> - $<?= $tier['monthly_price'] ?>/month</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="billing_cycle" class="form-label">Billing Cycle</label>
                            <select class="form-control" id="billing_cycle" name="billing_cycle" required>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <input type="hidden" name="action" value="add_company">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Search functionality
    document.getElementById('companySearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('companiesTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        }
    });

    // Toggle company status
    function toggleCompanyStatus(companyId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
        const message = 'Change company status to ' + newStatus + '?';
        
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type='hidden' name='action' value='update_company_status'>
                <input type='hidden' name='company_id' value='${companyId}'>
                <input type='hidden' name='status' value='${newStatus}'>
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

</body>
</html>