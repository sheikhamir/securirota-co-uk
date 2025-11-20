<?php
/**
 * Root User - System Logs Management
 * View and manage system logs including activity logs and error logs
 */

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';
require_once '../includes/ActivityLogger.php';

$page_title = 'System Logs Management';

// Initialize database connection
$db = new Database();
$pdo = $db->getConnection();
$logger = new ActivityLogger($pdo);

// Handle AJAX requests for log data
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    try {
        // Ensure session is started for AJAX requests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Re-check authentication for AJAX
        if (!isRootUser()) {
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
        
        $log_type = $_GET['log_type'] ?? 'activity';
        $search = $_GET['search'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
        $action_type = $_GET['action_type'] ?? '';
        
        // Debug logging
        error_log("AJAX Request - Log Type: $log_type, Page: $page, Search: $search");
        
        if ($log_type === 'activity') {
            // Test database connection first
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            // Check if activity_log table exists first
            try {
                $check_stmt = $pdo->query("SHOW TABLES LIKE 'activity_log'");
                if ($check_stmt->rowCount() === 0) {
                    echo json_encode(['success' => false, 'message' => 'Activity log table does not exist']);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
            
            // Since we're root user, let's query directly without company filtering
            $offset = ($page - 1) * $per_page;
            
            $sql = "SELECT al.*, u.username, u.full_name 
                    FROM activity_log al 
                    LEFT JOIN users u ON al.user_id = u.id ";
            
            $params = [];
            $whereConditions = [];
            
            if ($action_type) {
                $whereConditions[] = "al.action_type = ?";
                $params[] = $action_type;
            }
            
            if ($search) {
                $whereConditions[] = "(al.description LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($date_from) {
                $whereConditions[] = "DATE(al.created_at) >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $whereConditions[] = "DATE(al.created_at) <= ?";
                $params[] = $date_to;
            }
            
            if (!empty($whereConditions)) {
                $sql .= "WHERE " . implode(" AND ", $whereConditions) . " ";
            }
            
            $sql .= "ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $per_page;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ";
            if (!empty($whereConditions)) {
                $countSql .= "WHERE " . implode(" AND ", $whereConditions);
            }
            
            $countParams = array_slice($params, 0, -2); // Remove LIMIT and OFFSET params
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $total_count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response = [
                'success' => true,
                'data' => $activities,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total_count / $per_page),
                    'total_records' => $total_count,
                    'per_page' => $per_page,
                    'has_next' => $page < ceil($total_count / $per_page),
                    'has_prev' => $page > 1
                ]
            ];
            
            echo json_encode($response);
        } elseif ($log_type === 'error') {
            $error_logs = getErrorLogs($search, $date_from, $date_to, $page, $per_page);
            echo json_encode($error_logs);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid log type specified']);
        }
    } catch (Exception $e) {
        error_log("System Logs AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle log actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'clear_activity_logs':
                $days = intval($_POST['days'] ?? 30);
                clearActivityLogs($days);
                $_SESSION['success'] = "Activity logs older than {$days} days have been cleared.";
                break;
                
            case 'clear_error_logs':
                clearErrorLogs();
                $_SESSION['success'] = "Error logs have been cleared.";
                break;
                
            case 'download_logs':
                $log_type = $_POST['log_type'] ?? 'activity';
                downloadLogs($log_type);
                exit;
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: system_logs.php');
    exit();
}

// Function to get error logs
function getErrorLogs($search = '', $date_from = '', $date_to = '', $page = 1, $per_page = 50) {
    $error_log_file = '../error_log';
    $api_error_log_file = '../api/error_log';
    $includes_error_log_file = '../includes/error_log';
    
    $logs = [];
    $files_to_check = [
        $error_log_file => 'Main',
        $api_error_log_file => 'API',
        $includes_error_log_file => 'Includes'
    ];
    
    foreach ($files_to_check as $file => $source) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                // Parse error log line
                if (preg_match('/^\[(.*?)\]/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $message = substr($line, strlen($matches[0]) + 1);
                    
                    // Apply filters
                    if (!empty($search) && stripos($message, $search) === false) {
                        continue;
                    }
                    
                    if (!empty($date_from) && strtotime($timestamp) < strtotime($date_from)) {
                        continue;
                    }
                    
                    if (!empty($date_to) && strtotime($timestamp) > strtotime($date_to . ' 23:59:59')) {
                        continue;
                    }
                    
                    $logs[] = [
                        'timestamp' => $timestamp,
                        'message' => $message,
                        'source' => $source,
                        'level' => determineLogLevel($message)
                    ];
                }
            }
        }
    }
    
    // Sort by timestamp (newest first)
    usort($logs, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Paginate
    $total = count($logs);
    $offset = ($page - 1) * $per_page;
    $logs = array_slice($logs, $offset, $per_page);
    
    return [
        'success' => true,
        'data' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page),
            'total_records' => $total,
            'per_page' => $per_page
        ]
    ];
}

// Function to determine log level from message
function determineLogLevel($message) {
    $message_lower = strtolower($message);
    if (strpos($message_lower, 'fatal') !== false) return 'fatal';
    if (strpos($message_lower, 'error') !== false) return 'error';
    if (strpos($message_lower, 'warning') !== false) return 'warning';
    if (strpos($message_lower, 'notice') !== false) return 'notice';
    return 'info';
}

// Function to clear activity logs
function clearActivityLogs($days) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
}

// Function to clear error logs
function clearErrorLogs() {
    $files = ['../error_log', '../api/error_log', '../includes/error_log'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
}

// Function to download logs
function downloadLogs($log_type) {
    $filename = "system_logs_" . date('Y-m-d_H-i-s') . ".txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    if ($log_type === 'activity') {
        global $logger;
        $activities = $logger->getAllActivitiesPaginated(1, 10000);
        
        echo "ACTIVITY LOGS - Generated: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";
        
        foreach ($activities['data'] as $activity) {
            echo "[{$activity['created_at']}] {$activity['action_type']} - {$activity['username']}\n";
            echo "Details: {$activity['details']}\n";
            echo "IP: {$activity['ip_address']}\n\n";
        }
    } elseif ($log_type === 'error') {
        echo "ERROR LOGS - Generated: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $files = ['../error_log', '../api/error_log', '../includes/error_log'];
        foreach ($files as $file) {
            if (file_exists($file)) {
                echo "=== " . basename(dirname($file)) . " ===\n";
                echo file_get_contents($file);
                echo "\n\n";
            }
        }
    }
}

// Get summary statistics
function getLogSummary() {
    global $pdo, $logger;
    
    // Activity log stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_activities FROM activity_log");
    $total_activities = $stmt->fetch()['total_activities'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as today_activities FROM activity_log WHERE DATE(created_at) = CURDATE()");
    $today_activities = $stmt->fetch()['today_activities'];
    
    // Error log stats
    $error_count = 0;
    $files = ['../error_log', '../api/error_log', '../includes/error_log'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $error_count += substr_count($content, "\n");
        }
    }
    
    return [
        'total_activities' => $total_activities,
        'today_activities' => $today_activities,
        'error_count' => $error_count
    ];
}

$log_summary = getLogSummary();
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
        
        .logs-tab-card {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .logs-filter-card {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border: none;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        
        .log-level-badge {
            font-size: 0.75rem;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            text-transform: uppercase;
        }
        
        .nav-tabs-custom {
            border-bottom: 2px solid #e3e6f0;
        }
        
        .nav-tabs-custom .nav-link {
            background: transparent;
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
            margin-bottom: -2px;
            border-bottom: 2px solid transparent;
        }
        
        .nav-tabs-custom .nav-link.active {
            color: #4e73df;
            border-bottom-color: #4e73df;
            background: transparent;
        }
        
        .nav-tabs-custom .nav-link:hover {
            color: #4e73df;
            border-color: transparent;
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
                                    <i class="fas fa-file-alt text-info"></i> System Logs Management
                                </h1>
                                <p class="text-muted mb-0">Monitor and manage system activity logs and error logs</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-circle"></i> Logging Active
                                </span>
                                <small class="text-muted">Last updated: <?php echo date('H:i:s'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo number_format($log_summary['total_activities']); ?></div>
                                <div class="small">Total Activity Logs</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">All Time</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo number_format($log_summary['today_activities']); ?></div>
                                <div class="small">Today's Activities</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">Last 24h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <div class="stat-number"><?php echo number_format($log_summary['error_count']); ?></div>
                                <div class="small">Error Log Entries</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">System</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="text-dark mb-3">
                            <i class="fas fa-tools"></i> Log Management Actions
                        </h4>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-primary-gradient text-white mx-auto">
                                    <i class="fas fa-download"></i>
                                </div>
                                <h5 class="card-title">Download Logs</h5>
                                <p class="card-text text-muted">Export activity and error logs for analysis</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#downloadModal">
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-warning-gradient text-white mx-auto">
                                    <i class="fas fa-trash"></i>
                                </div>
                                <h5 class="card-title">Clear Logs</h5>
                                <p class="card-text text-muted">Clean up old activity and error logs</p>
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                                    Clear Logs
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-info-gradient text-white mx-auto">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h5 class="card-title">Search Logs</h5>
                                <p class="card-text text-muted">Advanced search and filtering options</p>
                                <button class="btn btn-info" onclick="focusSearch()">
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card quick-action-card">
                            <div class="card-body text-center">
                                <div class="action-icon bg-success-gradient text-white mx-auto">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <h5 class="card-title">Refresh Logs</h5>
                                <p class="card-text text-muted">Reload and refresh log data</p>
                                <button class="btn btn-success" onclick="refreshLogs()">
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="card logs-filter-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter"></i> Filter Options
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Search Term</label>
                                <input type="text" id="search" class="form-control" placeholder="Search logs...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" id="date_from" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" id="date_to" class="form-control">
                            </div>
                            <div class="col-md-2" id="action-type-filter" style="display: none;">
                                <label class="form-label">Action Type</label>
                                <select id="action_type" class="form-select">
                                    <option value="">All Actions</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="create">Create</option>
                                    <option value="update">Update</option>
                                    <option value="delete">Delete</option>
                                    <option value="access">Access</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="filter-btn" class="btn btn-primary me-2">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button type="button" id="clear-filters-btn" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Logs Card -->
                <div class="card logs-tab-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-database"></i> System Logs Viewer
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Log Type Tabs -->
                        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="activity-logs-tab" data-bs-toggle="tab" data-bs-target="#activity-logs" type="button" role="tab">
                                    <i class="fas fa-user-clock"></i> Activity Logs
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="error-logs-tab" data-bs-toggle="tab" data-bs-target="#error-logs" type="button" role="tab">
                                    <i class="fas fa-exclamation-circle"></i> Error Logs
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content mt-4">
                            <!-- Activity Logs Tab -->
                            <div class="tab-pane fade show active" id="activity-logs" role="tabpanel">
                                <div id="activity-logs-container">
                                    <div class="text-center py-5">
                                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                        <p class="mt-3 text-muted">Loading activity logs...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Logs Tab -->
                            <div class="tab-pane fade" id="error-logs" role="tabpanel">
                                <div id="error-logs-container">
                                    <div class="text-center py-5">
                                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                        <p class="mt-3 text-muted">Loading error logs...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="row mt-4">
                            <div class="col-sm-12 col-md-5">
                                <div id="logs-info" class="text-muted"></div>
                            </div>
                            <div class="col-sm-12 col-md-7">
                                <div id="logs-pagination" class="d-flex justify-content-end"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Download Modal -->
<div class="modal fade" id="downloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download"></i> Download System Logs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="download_logs">
                    <div class="mb-3">
                        <label class="form-label">Select Log Type:</label>
                        <select name="log_type" class="form-select" required>
                            <option value="activity">Activity Logs (User Actions)</option>
                            <option value="error">Error Logs (System Errors)</option>
                        </select>
                        <div class="form-text">Choose which type of logs to download</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download Logs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning"></i> Clear System Logs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone! Please proceed with caution.
                </div>
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Clear Activity Logs</h6>
                            </div>
                            <div class="card-body">
                                <form method="post" class="mb-0">
                                    <input type="hidden" name="action" value="clear_activity_logs">
                                    <div class="mb-3">
                                        <label class="form-label">Clear activity logs older than:</label>
                                        <select name="days" class="form-select">
                                            <option value="30">30 days</option>
                                            <option value="60">60 days</option>
                                            <option value="90">90 days</option>
                                            <option value="180">180 days</option>
                                            <option value="365">1 year</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100" 
                                            onclick="return confirm('Are you sure you want to clear old activity logs?')">
                                        <i class="fas fa-trash"></i> Clear Activity Logs
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Clear Error Logs</h6>
                            </div>
                            <div class="card-body">
                                <form method="post" class="mb-0">
                                    <input type="hidden" name="action" value="clear_error_logs">
                                    <p class="text-muted small">This will clear all PHP error logs from the system.</p>
                                    <button type="submit" class="btn btn-danger w-100" 
                                            onclick="return confirm('Are you sure you want to clear all error logs?')">
                                        <i class="fas fa-trash"></i> Clear All Error Logs
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery and Bootstrap JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentLogType = 'activity';
    
    // Tab switching with Bootstrap 5
    const triggerTabList = document.querySelectorAll('[data-bs-toggle="tab"]');
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('shown.bs.tab', event => {
            const target = event.target.getAttribute('data-bs-target');
            currentLogType = target === '#activity-logs' ? 'activity' : 'error';
            currentPage = 1;
            
            // Show/hide action type filter for activity logs
            if (currentLogType === 'activity') {
                $('#action-type-filter').show();
            } else {
                $('#action-type-filter').hide();
            }
            
            loadLogs();
        });
    });
    
    // Filter button
    $('#filter-btn').click(function() {
        currentPage = 1;
        loadLogs();
    });
    
    // Clear filters button
    $('#clear-filters-btn').click(function() {
        $('#search').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        $('#action_type').val('');
        currentPage = 1;
        loadLogs();
    });
    
    // Search on enter
    $('#search').keypress(function(e) {
        if (e.which === 13) {
            currentPage = 1;
            loadLogs();
        }
    });
    
    // Load logs function
    function loadLogs() {
        const container = currentLogType === 'activity' ? '#activity-logs-container' : '#error-logs-container';
        
        $(container).html(`
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-3 text-muted">Loading ${currentLogType} logs...</p>
            </div>
        `);
        
        const params = {
            ajax: '1',
            log_type: currentLogType,
            search: $('#search').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            page: currentPage,
            per_page: 50
        };
        
        if (currentLogType === 'activity') {
            params.action_type = $('#action_type').val();
        }
        
        $.get('system_logs.php', params)
            .done(function(response) {
                console.log('Response received:', response);
                if (response && response.success) {
                    displayLogs(response.data, container);
                    displayPagination(response.pagination);
                } else {
                    $(container).html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + (response.message || 'Unknown error occurred') + '</div>');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                $(container).html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Failed to load logs: ' + error + '</div>');
            });
    }
    
    // Make loadLogs available globally
    window.loadLogs = loadLogs;
    
    // Display logs function
    function displayLogs(logs, container) {
        if (logs.length === 0) {
            $(container).html('<div class="alert alert-info"><i class="fas fa-info-circle"></i> No logs found matching your criteria</div>');
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-hover table-sm">';
        
        if (currentLogType === 'activity') {
            html += '<thead class="table-dark"><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead><tbody>';
            
            logs.forEach(function(log) {
                html += '<tr>';
                html += '<td><small>' + (log.created_at || '') + '</small></td>';
                html += '<td>' + (log.full_name || log.username || 'Unknown') + '</td>';
                html += '<td><span class="badge bg-primary">' + (log.action_type || 'N/A') + '</span></td>';
                html += '<td><small>' + (log.description || log.details || '') + '</small></td>';
                html += '<td><small>' + (log.ip_address || 'N/A') + '</small></td>';
                html += '</tr>';
            });
        } else {
            html += '<thead class="table-dark"><tr><th>Date/Time</th><th>Source</th><th>Level</th><th>Message</th></tr></thead><tbody>';
            
            logs.forEach(function(log) {
                const levelClass = {
                    'fatal': 'danger',
                    'error': 'danger',
                    'warning': 'warning',
                    'notice': 'info',
                    'info': 'secondary'
                }[log.level] || 'secondary';
                
                html += '<tr>';
                html += '<td><small>' + log.timestamp + '</small></td>';
                html += '<td><span class="badge bg-secondary">' + log.source + '</span></td>';
                html += '<td><span class="log-level-badge bg-' + levelClass + ' text-white">' + log.level.toUpperCase() + '</span></td>';
                html += '<td><small>' + log.message + '</small></td>';
                html += '</tr>';
            });
        }
        
        html += '</tbody></table></div>';
        $(container).html(html);
    }
    
    // Display pagination
    function displayPagination(pagination) {
        const info = `Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total_records)} of ${pagination.total_records} entries`;
        $('#logs-info').html(info);
        
        let paginationHtml = '<nav><ul class="pagination pagination-sm mb-0">';
        
        // Previous button
        if (pagination.current_page > 1) {
            paginationHtml += '<li class="page-item"><a class="page-link" href="#" data-page="' + (pagination.current_page - 1) + '">Previous</a></li>';
        }
        
        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === pagination.current_page ? ' active' : '';
            paginationHtml += '<li class="page-item' + activeClass + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }
        
        // Next button
        if (pagination.current_page < pagination.total_pages) {
            paginationHtml += '<li class="page-item"><a class="page-link" href="#" data-page="' + (pagination.current_page + 1) + '">Next</a></li>';
        }
        
        paginationHtml += '</ul></nav>';
        $('#logs-pagination').html(paginationHtml);
        
        // Bind pagination clicks
        $('#logs-pagination a').click(function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            loadLogs();
        });
    }
    
    // Initial load
    loadLogs();
});

// Utility functions (define in global scope)
window.focusSearch = function() {
    $('#search').focus();
};

window.refreshLogs = function() {
    if (typeof loadLogs === 'function') {
        loadLogs();
    } else {
        location.reload();
    }
};

// Make loadLogs available globally
window.loadLogs = null;
</script>

</body>
</html>