<?php
/**
 * Root User Dashboard - Ultimate System Control
 * Special dashboard exclusively for the root superuser
 */
session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

$page_title = 'Root Control Center';

// Get comprehensive system statistics
$stats = [
    'companies' => ['total' => 0, 'active' => 0, 'suspended' => 0, 'inactive' => 0],
    'users' => ['total' => 0, 'admins' => 0, 'managers' => 0, 'officers' => 0, 'super_admins' => 0],
    'officers' => 0,
    'sites' => 0,
    'shifts' => 0,
    'recent_activities' => 0,
    'db_size' => 0
];
$recent_activities = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Total companies
    $stmt = $conn->query("SELECT COUNT(*) as total, 
                                 COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                                 COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended,
                                 COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
                          FROM companies");
    $stats['companies'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total users across all companies
    $stmt = $conn->query("SELECT COUNT(*) as total,
                                 COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
                                 COUNT(CASE WHEN role = 'manager' THEN 1 END) as managers,
                                 COUNT(CASE WHEN role = 'officer' THEN 1 END) as officers,
                                 COUNT(CASE WHEN role = 'super_admin' THEN 1 END) as super_admins
                          FROM users");
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // System resources
    $stmt = $conn->query("SELECT COUNT(*) as total FROM officers WHERE company_id IS NOT NULL");
    $stats['officers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM sites WHERE company_id IS NOT NULL");
    $stats['sites'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM shifts WHERE created_at >= CURDATE()");
    $stats['shifts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Security metrics
    $stmt = $conn->query("SELECT COUNT(*) as total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['recent_activities'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Database size
    $stmt = $conn->query("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'db_size_mb' 
                          FROM information_schema.tables 
                          WHERE table_schema = 'rohabae1_rota'");
    $db_sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['db_size'] = array_sum(array_column($db_sizes, 'db_size_mb')) ?? 0;
    
    // Recent critical activities (last 48 hours) - using correct column names
    $stmt = $conn->query("SELECT * FROM activity_log 
                          WHERE action_type IN ('login', 'logout', 'create_user', 'delete_user') 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) 
                          ORDER BY created_at DESC LIMIT 10");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Root Dashboard Error: " . $e->getMessage());
    $error = "Failed to load system statistics: " . $e->getMessage();
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
        
        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .system-health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .health-status {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-excellent { background: #d1edff; color: #0c5460; }
        .status-good { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-critical { background: #f8d7da; color: #721c24; }
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
                                    <i class="fas fa-crown text-warning"></i> Root Control Center
                                </h1>
                                <p class="text-muted mb-0">Ultimate system control and monitoring dashboard</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-circle"></i> System Online
                                </span>
                                <small class="text-muted">Last updated: <?php echo date('H:i:s'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo $stats['companies']['total']; ?></div>
                                <div class="small">Total Companies</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><?php echo $stats['companies']['active']; ?> Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo $stats['users']['total']; ?></div>
                                <div class="small">Total Users</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><?php echo $stats['users']['admins']; ?> Admins</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo $stats['officers']; ?></div>
                                <div class="small">Security Officers</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo $stats['sites']; ?></div>
                                <div class="small">Protected Sites</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Monitored</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo $stats['shifts'] ?? 0; ?></div>
                                <div class="small">Today's Shifts</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo number_format($stats['db_size'] ?? 0, 1); ?></div>
                                <div class="small">DB Size (MB)</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Storage</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="text-dark mb-3">
                            <i class="fas fa-bolt"></i> Root Actions
                        </h4>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-primary-gradient text-white mx-auto">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <h5 class="card-title">Control Panel</h5>
                                <p class="card-text text-muted">Access main control panel with all administrative tools</p>
                                <a href="index.php" class="btn btn-primary">Access Panel</a>
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
                                <p class="card-text text-muted">View comprehensive system analytics and reports</p>
                                <a href="#" class="btn btn-success">View Analytics</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-warning-gradient text-white mx-auto">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h5 class="card-title">Security</h5>
                                <p class="card-text text-muted">Manage security settings and access controls</p>
                                <a href="security.php" class="btn btn-warning">Security Center</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-info-gradient text-white mx-auto">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <h5 class="card-title">Company Management</h5>
                                <p class="card-text text-muted">Manage companies, assign subscription tiers</p>
                                <a href="companies.php" class="btn btn-info">Manage Companies</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-danger-gradient text-white mx-auto">
                                    <i class="fas fa-database"></i>
                                </div>
                                <h5 class="card-title">Migration</h5>
                                <p class="card-text text-muted">Database migration and data management tools</p>
                                <a href="migration.php" class="btn btn-danger">Migration Tools</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-info-gradient text-white mx-auto">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <h5 class="card-title">Subscription Tiers</h5>
                                <p class="card-text text-muted">Manage subscription tiers and pricing</p>
                                <a href="subscription_tiers.php" class="btn btn-info">Manage Tiers</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-primary-gradient text-white mx-auto">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="card-title">User Management</h5>
                                <p class="card-text text-muted">Manage all users across all companies</p>
                                <a href="all_users.php" class="btn btn-primary">Manage Users</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-warning-gradient text-white mx-auto">
                                    <i class="fas fa-user-crown"></i>
                                </div>
                                <h5 class="card-title">Root Users</h5>
                                <p class="card-text text-muted">Manage root users and billing calculator users</p>
                                <a href="root_users.php" class="btn btn-warning">Manage Root Users</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-success-gradient text-white mx-auto">
                                    <i class="fas fa-server"></i>
                                </div>
                                <h5 class="card-title">System Resources</h5>
                                <p class="card-text text-muted">Monitor system resources and performance</p>
                                <a href="system_resources.php" class="btn btn-success">View Resources</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-info-gradient text-white mx-auto">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <h5 class="card-title">Cache Management</h5>
                                <p class="card-text text-muted">Clear system cache and optimize performance</p>
                                <a href="cache_management.php" class="btn btn-info">Manage Cache</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-primary-gradient text-white mx-auto">
                                    <i class="fas fa-database"></i>
                                </div>
                                <h5 class="card-title">Backup Management</h5>
                                <p class="card-text text-muted">Create and manage system backups</p>
                                <a href="backup_management.php" class="btn btn-primary">Manage Backups</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-primary-gradient text-white mx-auto">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h5 class="card-title">Settings</h5>
                                <p class="card-text text-muted">System-wide configuration and settings</p>
                                <a href="settings.php" class="btn btn-primary">System Settings</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-danger-gradient text-white mx-auto">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <h5 class="card-title">Emergency</h5>
                                <p class="card-text text-muted">Emergency maintenance and system controls</p>
                                <a href="#" class="btn btn-danger" onclick="confirmEmergency()">Emergency Mode</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health and Recent Activity Row -->
                <div class="row">
                    <!-- System Health -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-heartbeat"></i> System Health
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="system-health-item">
                                    <span>Database Performance</span>
                                    <span class="health-status status-excellent">Excellent</span>
                                </div>
                                <div class="system-health-item">
                                    <span>Security Status</span>
                                    <span class="health-status status-good">Secure</span>
                                </div>
                                <div class="system-health-item">
                                    <span>System Load</span>
                                    <span class="health-status status-warning">Moderate</span>
                                </div>
                                <div class="system-health-item">
                                    <span>Memory Usage</span>
                                    <span class="health-status status-good">Normal</span>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="system_resources.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-chart-bar"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock"></i> Critical System Activities (Last 24 Hours)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recent_activities)): ?>
                                    <?php foreach (array_slice($recent_activities, 0, 6) as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                                        <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($activity['user'] ?? 'System'); ?></span>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?php echo htmlspecialchars($activity['details']); ?>
                                                    </div>
                                                    <?php if (!empty($activity['ip_address'])): ?>
                                                        <div class="text-muted small">
                                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($activity['ip_address']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small text-end">
                                                    <?php echo date('M j', strtotime($activity['created_at'])); ?><br>
                                                    <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="../pages/activity_log.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-list"></i> View All Activities
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                        <h6>No Recent Activities</h6>
                                        <p class="text-muted">System activities will appear here</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmEmergency() {
            if (confirm('Are you sure you want to enter Emergency Maintenance Mode? This will make the system unavailable to all users except root.')) {
                // Implement emergency mode logic
                alert('Emergency mode functionality would be implemented here.');
            }
        }
        
        // Auto-refresh system health every 30 seconds
        setInterval(function() {
            // You could implement AJAX refresh here
            const statusElement = document.querySelector('.badge.bg-success');
            if (statusElement) {
                statusElement.innerHTML = '<i class="fas fa-circle"></i> System Online';
            }
        }, 30000);
    </script>
</body>
</html>
