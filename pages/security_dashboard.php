<?php
/**
 * Security Dashboard for Super Admin
 * Provides comprehensive security monitoring and management
 */

$page_title = 'Security Dashboard';
require_once '../config/config.php';

// Start session and check authentication
session_start();
requireSuperAdmin();

require_once '../config/database.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    header('Location: ../dashboard.php');
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Handle security actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    
    try {
        switch ($action) {
            case 'block_ip':
                $ip = filter_input(INPUT_POST, 'ip_address', FILTER_VALIDATE_IP);
                $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
                
                if ($ip) {
                    $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, reason, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$ip, $reason ?: 'Manual block', $_SESSION['user_id']]);
                    $success_message = "IP address $ip has been blocked";
                } else {
                    $error_message = "Invalid IP address provided";
                }
                break;
                
            case 'unblock_ip':
                $ip = filter_input(INPUT_POST, 'ip_address', FILTER_VALIDATE_IP);
                
                if ($ip) {
                    $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
                    $stmt->execute([$ip]);
                    $success_message = "IP address $ip has been unblocked";
                } else {
                    $error_message = "Invalid IP address provided";
                }
                break;
                
            case 'reset_user_lockout':
                $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                
                if ($user_id) {
                    $stmt = $pdo->prepare("DELETE FROM failed_login_attempts WHERE identifier = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "User lockout has been reset";
                } else {
                    $error_message = "Invalid user ID provided";
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Security action error: " . $e->getMessage());
        $error_message = "Action failed: " . $e->getMessage();
    }
}

// Get security statistics
$stats = [];

try {
    // Recent security events
    $stmt = $pdo->prepare("
        SELECT event_type, COUNT(*) as count 
        FROM security_logs 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        GROUP BY event_type 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['recent_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Failed login attempts
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_attempts,
               COUNT(DISTINCT identifier) as unique_users
        FROM failed_login_attempts 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $stats['failed_logins'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Rate limit violations
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as violations 
        FROM security_logs 
        WHERE event_type = 'rate_limit_exceeded' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $stats['rate_limit_violations'] = $stmt->fetchColumn();

    // Active sessions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_sessions 
        FROM user_sessions 
        WHERE is_active = TRUE AND expires_at > NOW()
    ");
    $stmt->execute();
    $stats['active_sessions'] = $stmt->fetchColumn();

    // Get recent security events
    $stmt = $pdo->prepare("
        SELECT sl.*, u.username 
        FROM security_logs sl
        LEFT JOIN users u ON sl.identifier = u.id
        ORDER BY sl.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top IP addresses with failed attempts
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

    // Get locked users
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

    // Get currently blocked IPs
    $stmt = $pdo->prepare("
        SELECT bi.*, u.username as blocked_by 
        FROM blocked_ips bi
        LEFT JOIN users u ON bi.created_by = u.id
        ORDER BY bi.created_at DESC
    ");
    $stmt->execute();
    $blocked_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Security stats error: " . $e->getMessage());
    $error_message = "Failed to load security data: " . $e->getMessage();
    // Initialize with empty arrays
    $stats = [
        'recent_events' => [],
        'failed_logins' => ['total_attempts' => 0, 'unique_users' => 0],
        'rate_limit_violations' => 0,
        'active_sessions' => 0
    ];
    $recent_events = [];
    $top_failed_ips = [];
    $locked_users = [];
    $blocked_ips = [];
}

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-shield-alt text-danger me-2"></i>Security Dashboard
                </h1>
                <div class="page-subtitle">Monitor and manage system security</div>
                <div class="page-actions">
                    <button class="btn btn-info" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Security Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-danger text-white me-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        <div>
                            <div class="h1 mb-0"><?= $stats['failed_logins']['total_attempts'] ?? 0 ?></div>
                            <div class="text-muted">Failed Logins (24h)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-warning text-white me-3">
                            <i class="fas fa-ban"></i>
                        </span>
                        <div>
                            <div class="h1 mb-0"><?= $stats['rate_limit_violations'] ?? 0 ?></div>
                            <div class="text-muted">Rate Limit Violations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-success text-white me-3">
                            <i class="fas fa-users"></i>
                        </span>
                        <div>
                            <div class="h1 mb-0"><?= $stats['active_sessions'] ?? 0 ?></div>
                            <div class="text-muted">Active Sessions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-red text-white me-3">
                            <i class="fas fa-lock"></i>
                        </span>
                        <div>
                            <div class="h1 mb-0"><?= count($locked_users) ?></div>
                            <div class="text-muted">Locked Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Events Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie me-2"></i>Security Events (24h)
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="securityEventsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line me-2"></i>Failed Login Attempts
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="failedLoginsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" data-bs-tabs="true" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="#events" class="nav-link active" data-bs-toggle="tab" role="tab">
                        <i class="fas fa-list me-2"></i>Recent Events
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#failed-logins" class="nav-link" data-bs-toggle="tab" role="tab">
                        <i class="fas fa-exclamation-triangle me-2"></i>Failed Logins
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#locked-users" class="nav-link" data-bs-toggle="tab" role="tab">
                        <i class="fas fa-user-lock me-2"></i>Locked Users
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#blocked-ips" class="nav-link" data-bs-toggle="tab" role="tab">
                        <i class="fas fa-ban me-2"></i>Blocked IPs
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Recent Events Tab -->
                <div class="tab-pane fade show active" id="events" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-vcenter" id="eventsTable">
                            <thead>
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
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-shield-alt fa-3x mb-3 text-muted"></i>
                                        <br>No recent security events found.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recent_events as $event): ?>
                                <tr>
                                    <td>
                                        <div class="text-truncate">
                                            <?= date('M j, Y H:i', strtotime($event['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($event['event_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($event['username'] ?? $event['identifier'] ?? 'Unknown') ?></td>
                                    <td>
                                        <code><?= htmlspecialchars($event['ip_address']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($event['details'])): ?>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="showDetails('<?= htmlspecialchars(addslashes($event['details'])) ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                                <td>
                                                    <span class="badge badge-<?= $event['severity'] === 'critical' ? 'danger' : ($event['severity'] === 'high' ? 'warning' : 'secondary') ?>">
                                                        <?= ucfirst($event['severity']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Failed Logins Tab -->
                <div class="tab-pane fade" id="failed-logins" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="failedLoginsTable">
                                    <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Attempts</th>
                                            <th>Last Attempt</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_failed_ips as $ip_data): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ip_data['ip_address']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $ip_data['attempts'] > 10 ? 'danger' : 'warning' ?>">
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Locked Users Tab -->
                <div class="tab-pane fade" id="locked-users" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="lockedUsersTable">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Failed Attempts</th>
                                            <th>Last Attempt</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locked_users as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></td>
                                                <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge badge-danger"><?= $user['attempts'] ?></span>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blocked IPs Tab -->
                <div class="tab-pane fade" id="blocked-ips" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Blocked IP Addresses</h6>
                                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addBlockedIPModal">
                                    <i class="fas fa-plus"></i> Add Blocked IP
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="blockedIPsTable">
                                    <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Reason</th>
                                            <th>Blocked By</th>
                                            <th>Blocked At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="block_ip">
                    <div class="form-group">
                        <label>IP Address</label>
                        <input type="text" name="ip_address" class="form-control" required pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                        <small class="form-text text-muted">Enter a valid IPv4 address</small>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Reason for blocking this IP">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <pre id="detailsContent"></pre>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Security Events Chart
const eventsCtx = document.getElementById('securityEventsChart').getContext('2d');
const eventsData = <?= json_encode($stats['recent_events']) ?>;
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

// Initialize DataTables
$(document).ready(function() {
    $('#eventsTable, #failedLoginsTable, #lockedUsersTable, #blockedIPsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });
});

function showDetails(details) {
    document.getElementById('detailsContent').textContent = JSON.stringify(JSON.parse(details), null, 2);
    $('#detailsModal').modal('show');
}

function refreshDashboard() {
    location.reload();
}
</script>

<?php require_once '../includes/footer.php'; ?>
