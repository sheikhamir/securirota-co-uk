<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

// Validate client ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . 'pages/clients.php?error=invalid_client');
    exit();
}

$client_id = (int)$_GET['id'];
$page_title = 'Client Details';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM clients LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
            // Multi-tenant mode active
            $use_company_filter = true;
            $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
            if (!$is_super_admin) {
                $company_id = $_SESSION['company_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        // Pre-migration mode, no company filtering
        $use_company_filter = false;
    }
    
    // Get client information - WITH COMPANY SECURITY CHECK
    $client_sql = "SELECT * FROM clients WHERE id = ?";
    $client_params = [$client_id];
    
    // SECURITY: Add company filtering to prevent cross-company data access
    if ($use_company_filter && $company_id) {
        $client_sql .= " AND company_id = ?";
        $client_params[] = $company_id;
    }
    
    $client_stmt = $conn->prepare($client_sql);
    $client_stmt->execute($client_params);
    $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        header('Location: ' . BASE_URL . 'pages/clients.php?error=client_not_found');
        exit();
    }
    
    $page_title = $client['company_name'] . ' - Client Details';
    
    // Get client sites - WITH COMPANY SECURITY CHECK
    $sites_sql = "
        SELECT s.*, 
               COUNT(DISTINCT sh.id) as total_shifts,
               COUNT(DISTINCT CASE WHEN sh.status = 'completed' THEN sh.id END) as completed_shifts
        FROM sites s
        LEFT JOIN shifts sh ON s.id = sh.site_id
        WHERE s.client_id = ?";
    
    $sites_params = [$client_id];
    
    if ($use_company_filter && $company_id) {
        $sites_sql .= " AND s.company_id = ?";
        $sites_params[] = $company_id;
    }
    
    $sites_sql .= " GROUP BY s.id ORDER BY s.site_name";
    
    $sites_stmt = $conn->prepare($sites_sql);
    $sites_stmt->execute($sites_params);
    $sites = $sites_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get revenue statistics (weekly data)
    $revenue_stmt = $conn->prepare("
        SELECT 
            YEARWEEK(sh.shift_date, 1) as week_year,
            DATE(DATE_SUB(sh.shift_date, INTERVAL WEEKDAY(sh.shift_date) DAY)) as week_start,
            DATE(DATE_ADD(DATE_SUB(sh.shift_date, INTERVAL WEEKDAY(sh.shift_date) DAY), INTERVAL 6 DAY)) as week_end,
            COUNT(sh.id) as total_shifts,
            COUNT(CASE WHEN sh.status = 'completed' THEN 1 END) as completed_shifts,
            SUM(CASE WHEN sh.status = 'completed' THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.client_rate, s.client_rate, c.billing_rate, 0)
            ELSE 0 END) as total_revenue,
            SUM(CASE WHEN sh.status = 'completed' THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.custom_officer_rate, sh.officer_rate, 0)
            ELSE 0 END) as total_officer_expense,
            SUM(CASE WHEN sh.status = 'completed' THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                )
            ELSE 0 END) as total_hours
        FROM shifts sh
        JOIN sites s ON sh.site_id = s.id
        JOIN clients c ON s.client_id = c.id
        WHERE c.id = ?
        AND sh.shift_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND sh.shift_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY YEARWEEK(sh.shift_date, 1), week_start, week_end
        ORDER BY week_year
    ");
    $revenue_stmt->execute([$client_id]);
    $revenue_data = $revenue_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get overall statistics
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT s.id) as total_sites,
            COUNT(DISTINCT sh.id) as total_shifts,
            COUNT(DISTINCT CASE WHEN sh.status = 'completed' THEN sh.id END) as completed_shifts,
            COUNT(DISTINCT CASE WHEN sh.shift_date >= CURDATE() THEN sh.id END) as future_shifts,
            COUNT(DISTINCT sh.officer_id) as unique_officers,
            SUM(CASE WHEN sh.status = 'completed' THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.client_rate, s.client_rate, c.billing_rate, 0)
            ELSE 0 END) as lifetime_revenue,
            SUM(CASE WHEN sh.status = 'completed' THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.custom_officer_rate, sh.officer_rate, 0)
            ELSE 0 END) as lifetime_officer_expense,
            SUM(CASE WHEN sh.status = 'completed' AND sh.shift_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.client_rate, s.client_rate, c.billing_rate, 0)
            ELSE 0 END) as last_month_revenue,
            SUM(CASE WHEN sh.status = 'completed' AND sh.shift_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.custom_officer_rate, sh.officer_rate, 0)
            ELSE 0 END) as last_month_officer_expense,
            SUM(CASE WHEN sh.status = 'completed' AND YEAR(sh.shift_date) = YEAR(CURDATE()) THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.client_rate, s.client_rate, c.billing_rate, 0)
            ELSE 0 END) as this_year_revenue,
            SUM(CASE WHEN sh.status = 'completed' AND YEAR(sh.shift_date) = YEAR(CURDATE()) THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.custom_officer_rate, sh.officer_rate, 0)
            ELSE 0 END) as this_year_officer_expense
        FROM clients c
        LEFT JOIN sites s ON c.id = s.client_id
        LEFT JOIN shifts sh ON s.id = sh.site_id
        WHERE c.id = ?
    ");
    $stats_stmt->execute([$client_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure we have default values for all stats
    $stats = array_merge([
        'total_sites' => 0,
        'total_shifts' => 0,
        'completed_shifts' => 0,
        'future_shifts' => 0,
        'unique_officers' => 0,
        'lifetime_revenue' => 0,
        'lifetime_officer_expense' => 0,
        'last_month_revenue' => 0,
        'last_month_officer_expense' => 0,
        'this_year_revenue' => 0,
        'this_year_officer_expense' => 0
    ], $stats ?: []);
    
    // Get top officers for this client
    $officers_stmt = $conn->prepare("
        SELECT 
            o.id, o.first_name, o.last_name,
            COUNT(sh.id) as shifts_worked,
            SUM(CASE WHEN sh.status = 'completed' THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                )
            ELSE 0 END) as hours_worked,
            AVG(CASE WHEN sh.status = 'completed' AND 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) > 0 THEN 
                GREATEST(
                    CASE 
                        WHEN sh.end_time < sh.start_time THEN 
                            (24 - HOUR(sh.start_time) - MINUTE(sh.start_time)/60) + 
                            (HOUR(sh.end_time) + MINUTE(sh.end_time)/60)
                        ELSE 
                            TIMESTAMPDIFF(MINUTE, sh.start_time, sh.end_time) / 60
                    END, 0
                ) * COALESCE(sh.client_rate, s.client_rate, c.billing_rate, 0)
            END) as avg_shift_value
        FROM officers o
        JOIN shifts sh ON o.id = sh.officer_id
        JOIN sites s ON sh.site_id = s.id
        JOIN clients c ON s.client_id = c.id
        WHERE c.id = ? AND sh.status = 'completed'
        GROUP BY o.id
        HAVING shifts_worked > 0
        ORDER BY shifts_worked DESC
        LIMIT 10
    ");
    $officers_stmt->execute([$client_id]);
    $top_officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get site rates for billing information
    $site_rates_stmt = $conn->prepare("
        SELECT 
            id,
            site_name,
            client_rate,
            default_rate,
            COALESCE(client_rate, default_rate, ?) as effective_rate
        FROM sites 
        WHERE client_id = ? 
        AND status = 'active'
        AND (client_rate IS NOT NULL OR default_rate IS NOT NULL)
        ORDER BY site_name
    ");
    $site_rates_stmt->execute([$client['billing_rate'] ?: 0, $client_id]);
    $site_rates = $site_rates_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading client details: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #667eea;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.client-info-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.info-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.info-section h3 {
    color: #667eea;
    margin-bottom: 20px;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 10px;
}

.info-item {
    margin-bottom: 15px;
}

.info-label {
    font-weight: bold;
    color: #495057;
    margin-bottom: 5px;
}

.info-value {
    color: #6c757d;
}

.revenue-chart {
    height: 300px;
    margin: 20px 0;
}

.site-rates-list {
    /* max-height: 200px; */
    overflow-y: auto;
}

.site-rate-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f1f1;
}

.site-rate-item:last-child {
    border-bottom: none;
}

.site-name {
    font-weight: 500;
    color: #495057;
}

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

.btn-group .btn {
    margin-right: 5px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.btn-sm {
    margin-right: 3px;
}

.btn-sm:last-child {
    margin-right: 0;
}

.me-1 {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    .client-info-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .site-rate-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .d-flex.gap-10 {
        flex-direction: column;
        gap: 10px;
    }
    
    .d-flex.gap-10 .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php else: ?>

<!-- Page Header -->
<div class="d-flex justify-between align-center mb-30">
    <div>
        <h2><i class="fas fa-handshake"></i> <?php echo htmlspecialchars($client['company_name']); ?></h2>
        <p class="text-muted">Comprehensive client overview and analytics</p>
    </div>
    <div class="d-flex gap-10">
        <a href="clients.php?edit=<?php echo $client['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Client
        </a>
        <a href="clients.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Clients
        </a>
    </div>
</div>

<!-- Statistics Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_sites'] ?? 0); ?></div>
        <div class="stat-label">Total Sites</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_shifts'] ?? 0); ?></div>
        <div class="stat-label">Total Shifts</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['unique_officers'] ?? 0); ?></div>
        <div class="stat-label">Officers Deployed</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo formatCurrency($stats['lifetime_revenue'] ?? 0); ?></div>
        <div class="stat-label">Lifetime Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo formatCurrency($stats['lifetime_officer_expense'] ?? 0); ?></div>
        <div class="stat-label">Lifetime Officer Expense</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php 
            $lifetime_profit = ($stats['lifetime_revenue'] ?? 0) - ($stats['lifetime_officer_expense'] ?? 0);
            echo formatCurrency($lifetime_profit); 
        ?></div>
        <div class="stat-label">Lifetime Profit</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo formatCurrency($stats['this_year_revenue'] ?? 0); ?></div>
        <div class="stat-label">This Year Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo formatCurrency($stats['this_year_officer_expense'] ?? 0); ?></div>
        <div class="stat-label">This Year Officer Expense</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['future_shifts'] ?? 0); ?></div>
        <div class="stat-label">Upcoming Shifts</div>
    </div>
</div>

<!-- Client Information and Contact Details -->
<div class="client-info-grid">
    <div class="info-section">
        <h3><i class="fas fa-info-circle"></i> Client Information</h3>
        
        <div class="info-item">
            <div class="info-label">Company Name</div>
            <div class="info-value"><?php echo htmlspecialchars($client['company_name']); ?></div>
        </div>
        
        <?php if ($client['contact_person']): ?>
        <div class="info-item">
            <div class="info-label">Contact Person</div>
            <div class="info-value"><?php echo htmlspecialchars($client['contact_person']); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($client['email']): ?>
        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value">
                <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                    <?php echo htmlspecialchars($client['email']); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($client['phone']): ?>
        <div class="info-item">
            <div class="info-label">Phone</div>
            <div class="info-value">
                <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>">
                    <?php echo htmlspecialchars($client['phone']); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($client['address']): ?>
        <div class="info-item">
            <div class="info-label">Address</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($client['address'])); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($client['notes']): ?>
        <div class="info-item">
            <div class="info-label">Notes</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($client['notes'])); ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="info-section">
        <h3><i class="fas fa-pound-sign"></i> Billing Information</h3>
        
        <div class="info-item">
            <div class="info-label">Default Billing Rate</div>
            <div class="info-value">
                <?php if ($client['billing_rate']): ?>
                    <span class="badge badge-success"><?php echo formatCurrency($client['billing_rate']); ?>/hour</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Not Set</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Payment Terms</div>
            <div class="info-value"><?php echo htmlspecialchars($client['payment_terms'] ?: 'Net 30'); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">
                <span class="badge badge-<?php echo $client['status'] === 'active' ? 'success' : 'danger'; ?>">
                    <?php echo ucfirst($client['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Client Since</div>
            <div class="info-value"><?php echo date('M j, Y', strtotime($client['created_at'])); ?></div>
        </div>
        
        <?php if (!empty($site_rates)): ?>
        <div class="info-item">
            <div class="info-label">Site-Specific Rates</div>
            <div class="info-value">
                <div class="site-rates-list">
                    <?php foreach ($site_rates as $site_rate): ?>
                        <div class="site-rate-item">
                            <small class="site-name">
                                <?php echo htmlspecialchars($site_rate['site_name']); ?><br>
                                <a href="site_detail.php?id=<?php echo $site_rate['id']; ?>" target="_blank">Site Details</a>
                            </small>
                            <span class="badge badge-info">
                                <?php echo formatCurrency($site_rate['effective_rate']); ?>/hour
                            </span>
                            <?php if ($site_rate['client_rate'] && $site_rate['client_rate'] != $client['billing_rate']): ?>
                                <small class="text-muted">(Custom rate)</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Revenue Analytics -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Revenue Analytics (Weekly Data - Last 12 Months + 6 Months Future)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($revenue_data)): ?>
            <canvas id="revenueChart" class="revenue-chart"></canvas>
        <?php else: ?>
            <div class="text-center p-30">
                <i class="fas fa-chart-line fa-3x text-muted mb-20"></i>
                <h4>No Revenue Data Available</h4>
                <p class="text-muted">This client has no completed shifts in the selected time period.</p>
                <a href="shifts.php?action=add&client_id=<?php echo $client['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Schedule First Shift
                </a>
            </div>
        <?php endif; ?>
        
        <div class="row mt-20">
            <div class="col-md-3">
                <div class="text-center">
                    <h4><?php echo formatCurrency($stats['last_month_revenue'] ?? 0); ?></h4>
                    <p class="text-muted">Last Month Revenue</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h4><?php echo formatCurrency($stats['last_month_officer_expense'] ?? 0); ?></h4>
                    <p class="text-muted">Last Month Officer Expense</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h4><?php echo number_format($stats['completed_shifts'] ?? 0); ?></h4>
                    <p class="text-muted">Completed Shifts</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h4><?php 
                        $total_shifts = $stats['total_shifts'] ?? 0;
                        $completed_shifts = $stats['completed_shifts'] ?? 0;
                        $completion_rate = $total_shifts > 0 
                            ? round(($completed_shifts / $total_shifts) * 100, 1) 
                            : 0;
                        echo $completion_rate . '%';
                    ?></h4>
                    <p class="text-muted">Completion Rate</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-15">
            <div class="col-md-4">
                <div class="text-center">
                    <h4><?php 
                        $last_month_profit = ($stats['last_month_revenue'] ?? 0) - ($stats['last_month_officer_expense'] ?? 0);
                        echo formatCurrency($last_month_profit); 
                    ?></h4>
                    <p class="text-muted">Last Month Profit</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <h4><?php 
                        $last_month_revenue = $stats['last_month_revenue'] ?? 0;
                        $last_month_expense = $stats['last_month_officer_expense'] ?? 0;
                        $last_month_profit = $last_month_revenue - $last_month_expense;
                        $last_month_margin = $last_month_revenue > 0 
                            ? round(($last_month_profit / $last_month_revenue) * 100, 1)
                            : 0;
                        echo $last_month_margin . '%';
                    ?></h4>
                    <p class="text-muted">Last Month Profit Margin</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <h4><?php 
                        $lifetime_revenue = $stats['lifetime_revenue'] ?? 0;
                        $lifetime_expense = $stats['lifetime_officer_expense'] ?? 0;
                        $lifetime_profit = $lifetime_revenue - $lifetime_expense;
                        $lifetime_margin = $lifetime_revenue > 0 
                            ? round(($lifetime_profit / $lifetime_revenue) * 100, 1)
                            : 0;
                        echo $lifetime_margin . '%';
                    ?></h4>
                    <p class="text-muted">Lifetime Profit Margin</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sites Overview -->
<div class="card">
    <div class="card-header d-flex justify-between align-center">
        <h3><i class="fas fa-building"></i> Client Sites (<?php echo count($sites); ?>)</h3>
        <a href="sites.php?action=add&client_id=<?php echo $client['id']; ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New Site
        </a>
    </div>
    
    <?php if (empty($sites)): ?>
        <div class="text-center p-30">
            <p class="text-muted">No sites found for this client.</p>
            <a href="sites.php?action=add&client_id=<?php echo $client['id']; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add First Site
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table id="sitesTable" class="table">
                <thead>
                    <tr>
                        <th>Site Name</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Total Shifts</th>
                        <th>Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($site['site_name']); ?></strong>
                                <?php if ($site['address']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($site['address'], 0, 50)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($site['contact_person']): ?>
                                    <?php echo htmlspecialchars($site['contact_person']); ?>
                                    <?php if ($site['contact_phone']): ?>
                                        <br><small><?php echo htmlspecialchars($site['contact_phone']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No contact</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $site['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($site['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($site['total_shifts'] ?? 0); ?></td>
                            <td><?php echo number_format($site['completed_shifts'] ?? 0); ?></td>
                            <td>
                                <a href="sites.php?edit=<?php echo $site['id']; ?>&return_url=<?php echo rawurlencode($_SERVER['REQUEST_URI'] ?? 'sites.php'); ?>" class="btn btn-outline-primary btn-sm me-1" title="Edit Site">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="shifts.php?site_id=<?php echo $site['id']; ?>" class="btn btn-outline-info btn-sm" title="View Shifts">
                                    <i class="fas fa-calendar"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Top Officers -->
<?php if (!empty($top_officers)): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> Top Officers (Most Active)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Officer</th>
                    <th>Shifts Worked</th>
                    <th>Hours Worked</th>
                    <th>Avg Shift Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_officers as $officer): ?>
                    <tr>
                        <td>
                            <a href="officer_detail.php?id=<?php echo $officer['id']; ?>" target="_blank" class="text-decoration-none">
                                <?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>
                            </a>
                        </td>
                        <td><?php echo number_format($officer['shifts_worked'] ?? 0); ?></td>
                        <td><?php echo number_format($officer['hours_worked'] ?? 0, 1); ?> hours</td>
                        <td><?php echo formatCurrency($officer['avg_shift_value'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTables for sites
    $('#sitesTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']]
    });
    
    <?php if (!empty($revenue_data)): ?>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php 
                echo implode(',', array_map(function($item) {
                    $week_start = date('M j', strtotime($item['week_start']));
                    $week_end = date('M j', strtotime($item['week_end']));
                    return '"' . $week_start . ' - ' . $week_end . '"';
                }, $revenue_data));
            ?>],
            datasets: [{
                label: 'Revenue',
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['total_revenue'] ?: 0;
                    }, $revenue_data));
                ?>],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Officer Expense',
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['total_officer_expense'] ?: 0;
                    }, $revenue_data));
                ?>],
                borderColor: '#ff6b6b',
                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Hours',
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['total_hours'] ?: 0;
                    }, $revenue_data));
                ?>],
                borderColor: '#f093fb',
                backgroundColor: 'rgba(240, 147, 251, 0.1)',
                fill: false,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: true,
                    ticks: {
                        maxTicksLimit: 12,
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (£)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Hours'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Revenue: £' + context.parsed.y.toLocaleString();
                            } else if (context.datasetIndex === 1) {
                                return 'Officer Expense: £' + context.parsed.y.toLocaleString();
                            } else {
                                return 'Hours: ' + context.parsed.y.toLocaleString();
                            }
                        },
                        afterBody: function(tooltipItems) {
                            let revenue = 0;
                            let expense = 0;
                            tooltipItems.forEach(function(tooltipItem) {
                                if (tooltipItem.datasetIndex === 0) {
                                    revenue = tooltipItem.parsed.y;
                                } else if (tooltipItem.datasetIndex === 1) {
                                    expense = tooltipItem.parsed.y;
                                }
                            });
                            if (revenue > 0 && expense > 0) {
                                const profit = revenue - expense;
                                const margin = ((profit / revenue) * 100).toFixed(1);
                                return ['Profit: £' + profit.toLocaleString(), 'Margin: ' + margin + '%'];
                            }
                            return [];
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
