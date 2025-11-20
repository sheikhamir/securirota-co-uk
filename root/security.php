<?php
/**
 * Security Dashboard for Super Admin
 * Provides comprehensive security monitoring and management
 */

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Initialize database connection
$db = new Database();
$pdo = $db->getConnection();

$page_title = 'Security Dashboard';

// Create blocked_ips table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blocked_ips (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason VARCHAR(255) DEFAULT 'Manual block',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_address (ip_address),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
} catch (Exception $e) {
    // If table creation fails, log it but continue
    error_log("Failed to create blocked_ips table: " . $e->getMessage());
}

// Handle security actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'block_ip':
            $ip = $_POST['ip_address'] ?? '';
            if ($ip) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, reason, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$ip, $_POST['reason'] ?? 'Manual block', $_SESSION['user_id']]);
                    $success_message = "IP address $ip has been blocked";
                } catch (Exception $e) {
                    $error_message = "Failed to block IP address: " . $e->getMessage();
                }
            }
            break;
            
        case 'unblock_ip':
            $ip = $_POST['ip_address'] ?? '';
            if ($ip) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
                    $stmt->execute([$ip]);
                    $success_message = "IP address $ip has been unblocked";
                } catch (Exception $e) {
                    $error_message = "Failed to unblock IP address: " . $e->getMessage();
                }
            }
            break;
            
        case 'reset_user_lockout':
            $user_id = $_POST['user_id'] ?? '';
            if ($user_id) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM failed_login_attempts WHERE identifier = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "User lockout has been reset";
                } catch (Exception $e) {
                    $error_message = "Failed to reset user lockout: " . $e->getMessage();
                }
            }
            break;
            
        case 'reset_all_lockouts':
            try {
                $stmt = $pdo->prepare("DELETE FROM failed_login_attempts WHERE created_at < NOW()");
                $stmt->execute();
                $success_message = "All user lockouts have been reset";
            } catch (Exception $e) {
                $error_message = "Failed to reset all lockouts: " . $e->getMessage();
            }
            break;
    }
}

// Get security statistics
$stats = [];

// Recent security events
try {
    $stmt = $pdo->prepare("
        SELECT event_type, COUNT(*) as count 
        FROM security_logs 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        GROUP BY event_type 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['recent_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats['recent_events'] = [];
}

// Failed login attempts
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_attempts,
               COUNT(DISTINCT identifier) as unique_users
        FROM failed_login_attempts 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $stats['failed_logins'] = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats['failed_logins'] = ['total_attempts' => 0, 'unique_users' => 0];
}

// Rate limit violations
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as violations 
        FROM security_logs 
        WHERE event_type = 'rate_limit_exceeded' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $stats['rate_limit_violations'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['rate_limit_violations'] = 0;
}

// Active sessions
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_sessions 
        FROM user_sessions 
        WHERE is_active = TRUE AND expires_at > NOW()
    ");
    $stmt->execute();
    $stats['active_sessions'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['active_sessions'] = 0;
}

// Get recent security events
try {
    $stmt = $pdo->prepare("
        SELECT sl.*, u.username 
        FROM security_logs sl
        LEFT JOIN users u ON sl.identifier = u.id
        ORDER BY sl.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_events = [];
}

// Get top IP addresses with failed attempts
try {
    $stmt = $pdo->prepare("
        SELECT ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt
        FROM failed_login_attempts 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY ip_address 
        ORDER BY attempts DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $top_failed_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_failed_ips = [];
}

// Get locked users
try {
    $stmt = $pdo->prepare("
        SELECT fla.identifier, u.username, u.email, COUNT(*) as attempts, MAX(fla.created_at) as last_attempt
        FROM failed_login_attempts fla
        LEFT JOIN users u ON fla.identifier = u.id
        WHERE fla.created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        GROUP BY fla.identifier
        HAVING attempts >= 5
        ORDER BY attempts DESC
    ");
    $stmt->execute();
    $locked_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $locked_users = [];
}

// Get currently blocked IPs
try {
    $stmt = $pdo->prepare("
        SELECT bi.*, u.username as blocked_by 
        FROM blocked_ips bi
        LEFT JOIN users u ON bi.created_by = u.id
        ORDER BY bi.created_at DESC
    ");
    $stmt->execute();
    $blocked_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $blocked_ips = [];
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
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 5px 8px;
            border-radius: 6px;
        }
        
        .security-stat-card {
            transition: transform 0.3s ease;
        }
        
        .security-stat-card:hover {
            transform: translateY(-3px);
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
        
        .tab-content .card {
            border-top: none;
            border-radius: 0 0 15px 15px;
        }
        
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            background: #f8f9fc;
            color: #5a5c69;
            margin-right: 2px;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
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
                                    <i class="fas fa-shield-alt text-danger"></i> Security Dashboard
                                </h1>
                                <p class="text-muted mb-0">Comprehensive security monitoring and threat management</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-circle"></i> Security Online
                                </span>
                                <small class="text-muted">Last updated: <?php echo date('H:i:s'); ?></small>
                                <button class="btn btn-outline-primary btn-sm ms-2" onclick="refreshDashboard()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <div class="stat-number"><?= $stats['failed_logins']['total_attempts'] ?? 0 ?></div>
                                <div class="small">Failed Logins</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><?= $stats['failed_logins']['unique_users'] ?? 0 ?> Users</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-ban fa-2x mb-3"></i>
                                <div class="stat-number"><?= $stats['rate_limit_violations'] ?? 0 ?></div>
                                <div class="small">Rate Violations</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">24h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-3"></i>
                                <div class="stat-number"><?= $stats['active_sessions'] ?? 0 ?></div>
                                <div class="small">Active Sessions</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Current</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-lock fa-2x mb-3"></i>
                                <div class="stat-number"><?= count($locked_users) ?></div>
                                <div class="small">Locked Users</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Blocked</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-2x mb-3"></i>
                                <div class="stat-number"><?= count($blocked_ips) ?></div>
                                <div class="small">Blocked IPs</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-eye fa-2x mb-3"></i>
                                <div class="stat-number"><?= count($stats['recent_events']) ?></div>
                                <div class="small">Security Events</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">24h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health and Activity -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-gradient">
                                <h5 class="card-title mb-0 text-white">
                                    <i class="fas fa-chart-pie me-2"></i>Security Events (24h)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="securityEventsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-gradient">
                                <h5 class="card-title mb-0 text-white">
                                    <i class="fas fa-heartbeat me-2"></i>Security Health
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="system-health-item">
                                    <span>Threat Detection</span>
                                    <span class="health-status status-excellent">Excellent</span>
                                </div>
                                <div class="system-health-item">
                                    <span>Failed Login Rate</span>
                                    <span class="health-status <?= ($stats['failed_logins']['total_attempts'] ?? 0) > 10 ? 'status-warning' : 'status-good' ?>">
                                        <?= ($stats['failed_logins']['total_attempts'] ?? 0) > 10 ? 'Warning' : 'Good' ?>
                                    </span>
                                </div>
                                <div class="system-health-item">
                                    <span>Session Security</span>
                                    <span class="health-status status-good">Good</span>
                                </div>
                                <div class="system-health-item">
                                    <span>IP Blocking</span>
                                    <span class="health-status status-excellent">Active</span>
                                </div>
                                <div class="system-health-item">
                                    <span>Rate Limiting</span>
                                    <span class="health-status status-good">Enforced</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-gradient">
                                <h5 class="card-title mb-0 text-white">
                                    <i class="fas fa-clock me-2"></i>Recent Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_events)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-shield-alt fa-2x mb-2"></i><br>
                                        <small>No recent security events</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($recent_events, 0, 5) as $event): ?>
                                        <div class="activity-item">
                                            <strong><?= htmlspecialchars($event['event_type']) ?></strong><br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($event['username'] ?? $event['identifier']) ?> - 
                                                <?= date('H:i', strtotime($event['created_at'])) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Management Tabs -->
                <div class="card">
                    <div class="card-header bg-gradient p-0">
                        <ul class="nav nav-tabs border-0" id="securityTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active text-white" id="events-tab" data-bs-toggle="tab" href="#events" role="tab">
                                    <i class="fas fa-list me-2"></i>Recent Events
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" id="failed-logins-tab" data-bs-toggle="tab" href="#failed-logins" role="tab">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Failed Logins
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" id="locked-users-tab" data-bs-toggle="tab" href="#locked-users" role="tab">
                                    <i class="fas fa-user-lock me-2"></i>Locked Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" id="blocked-ips-tab" data-bs-toggle="tab" href="#blocked-ips" role="tab">
                                    <i class="fas fa-ban me-2"></i>Blocked IPs
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content p-0" id="securityTabsContent">
                        <!-- Recent Events Tab -->
                        <div class="tab-pane fade show active" id="events" role="tabpanel">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="eventsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Time</th>
                                                <th>Event Type</th>
                                                <th>User</th>
                                                <th>IP Address</th>
                                                <th>Details</th>
                                                <th>Severity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_events)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-5">
                                                        <i class="fas fa-shield-alt fa-3x mb-3 text-muted"></i><br>
                                                        <strong>No recent security events found.</strong><br>
                                                        <small>Security events will appear here when they occur.</small>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_events as $event): ?>
                                                    <tr>
                                                        <td><?= date('M j, Y H:i', strtotime($event['created_at'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?= htmlspecialchars($event['event_type']) ?></span>
                                                        </td>
                                                        <td><?= htmlspecialchars($event['username'] ?? $event['identifier']) ?></td>
                                                        <td><?= htmlspecialchars($event['ip_address']) ?></td>
                                                        <td>
                                                            <?php if (!empty($event['details'])): ?>
                                                                <button class="btn btn-sm btn-outline-secondary" onclick="showDetails('<?= htmlspecialchars($event['details']) ?>')">
                                                                    View Details
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $event['severity'] === 'critical' ? 'danger' : ($event['severity'] === 'high' ? 'warning' : 'secondary') ?>">
                                                                <?= ucfirst($event['severity']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Logins Tab -->
                        <div class="tab-pane fade" id="failed-logins" role="tabpanel">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="failedLoginsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Attempts</th>
                                                <th>Last Attempt</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($top_failed_ips)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-5">
                                                        <i class="fas fa-exclamation-triangle fa-3x mb-3 text-muted"></i><br>
                                                        <strong>No failed login attempts found.</strong><br>
                                                        <small>Failed login attempts will appear here.</small>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($top_failed_ips as $ip_data): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ip_data['ip_address']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $ip_data['attempts'] > 10 ? 'danger' : 'warning' ?>">
                                                                <?= $ip_data['attempts'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('M j, Y H:i', strtotime($ip_data['last_attempt'])) ?></td>
                                                        <td>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="block_ip">
                                                                <input type="hidden" name="ip_address" value="<?= htmlspecialchars($ip_data['ip_address']) ?>">
                                                                <input type="hidden" name="reason" value="Multiple failed login attempts">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Block this IP address?')">
                                                                    <i class="fas fa-ban"></i> Block IP
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Locked Users Tab -->
                        <div class="tab-pane fade" id="locked-users" role="tabpanel">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="lockedUsersTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Failed Attempts</th>
                                                <th>Last Attempt</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($locked_users)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-5">
                                                        <i class="fas fa-user-lock fa-3x mb-3 text-muted"></i><br>
                                                        <strong>No locked users found.</strong><br>
                                                        <small>Locked users will appear here when accounts are locked due to failed attempts.</small>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($locked_users as $user): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></td>
                                                        <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                                        <td>
                                                            <span class="badge bg-danger"><?= $user['attempts'] ?></span>
                                                        </td>
                                                        <td><?= date('M j, Y H:i', strtotime($user['last_attempt'])) ?></td>
                                                        <td>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="reset_user_lockout">
                                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['identifier']) ?>">
                                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Reset lockout for this user?')">
                                                                    <i class="fas fa-unlock"></i> Reset Lockout
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Blocked IPs Tab -->
                        <div class="tab-pane fade" id="blocked-ips" role="tabpanel">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Blocked IP Addresses</h6>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBlockedIPModal">
                                        <i class="fas fa-plus"></i> Add Blocked IP
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="blockedIPsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Reason</th>
                                                <th>Blocked By</th>
                                                <th>Blocked At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($blocked_ips)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-5">
                                                        <i class="fas fa-ban fa-3x mb-3 text-muted"></i><br>
                                                        <strong>No blocked IP addresses found.</strong><br>
                                                        <small>Blocked IPs will appear here when addresses are blocked.</small>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($blocked_ips as $blocked_ip): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($blocked_ip['ip_address']) ?></td>
                                                        <td><?= htmlspecialchars($blocked_ip['reason']) ?></td>
                                                        <td><?= htmlspecialchars($blocked_ip['blocked_by'] ?? 'System') ?></td>
                                                        <td><?= date('M j, Y H:i', strtotime($blocked_ip['created_at'])) ?></td>
                                                        <td>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="unblock_ip">
                                                                <input type="hidden" name="ip_address" value="<?= htmlspecialchars($blocked_ip['ip_address']) ?>">
                                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Unblock this IP address?')">
                                                                    <i class="fas fa-check"></i> Unblock
                                                                </button>
                                                            </form>
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
        </div>
    </div>

<!-- Add Blocked IP Modal -->
<div class="modal fade" id="addBlockedIPModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Block IP Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="block_ip">
                    <div class="mb-3">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-control" required pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                        <div class="form-text">Enter a valid IPv4 address</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Reason for blocking this IP">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Block IP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="detailsContent"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<script>
// Security Events Chart
if (document.getElementById('securityEventsChart')) {
    const eventsCtx = document.getElementById('securityEventsChart').getContext('2d');
    const eventsData = <?= json_encode($stats['recent_events'] ?? []) ?>;
    
    if (eventsData.length > 0) {
        new Chart(eventsCtx, {
            type: 'pie',
            data: {
                labels: eventsData.map(e => e.event_type),
                datasets: [{
                    data: eventsData.map(e => e.count),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    } else {
        document.getElementById('securityEventsChart').style.display = 'none';
        document.getElementById('securityEventsChart').parentElement.innerHTML = '<p class="text-center text-muted">No security events data available</p>';
    }
}

// Initialize DataTables
$(document).ready(function() {
    $('#eventsTable, #failedLoginsTable, #lockedUsersTable, #blockedIPsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });
});

function showDetails(details) {
    try {
        document.getElementById('detailsContent').textContent = JSON.stringify(JSON.parse(details), null, 2);
    } catch (e) {
        document.getElementById('detailsContent').textContent = details;
    }
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function refreshDashboard() {
    location.reload();
}

function resetAllLockouts() {
    if (confirm('Are you sure you want to reset all user lockouts? This will clear all failed login attempts.')) {
        // Create a form to submit the reset all action
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="reset_all_lockouts">';
        document.body.appendChild(form);
        form.submit();
    }
}

function generateReport() {
    alert('Security report generation feature coming soon!');
}
</script>

</body>
</html>
