<?php
// Handle AJAX requests first, before any output
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once dirname(__DIR__) . '/config/config.php';
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/includes/ActivityLogger.php';
    
    // Check if user is logged in (same as requireLogin but without redirect)
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $logger = new ActivityLogger($conn);
        
        // Get filter parameters
        $action_type = $_GET['action_type'] ?? null;
        $search = $_GET['search'] ?? null;
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
        $sort_by = $_GET['sort_by'] ?? 'created_at';
        $sort_order = $_GET['sort_order'] ?? 'DESC';
        
        // Validate sort parameters
        $allowed_sort_fields = ['created_at', 'action_type', 'username', 'full_name'];
        $allowed_sort_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'created_at';
        }
        if (!in_array($sort_order, $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        // Get activities with pagination
        $activities = $logger->getAllActivitiesPaginated($page, $per_page, $action_type, $search, $date_from, $date_to, $sort_by, strtolower($sort_order));
        
        // Get total count for pagination
        $total_count = $logger->getActivitiesCount($action_type, $search, $date_from, $date_to);
        $total_pages = ceil($total_count / $per_page);
        
        echo json_encode([
            'success' => true,
            'activities' => $activities,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_count' => $total_count,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading activities: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Regular page load
$page_title = 'Activity Log';
require_once '../includes/header.php';
require_once '../includes/ActivityLogger.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $logger = new ActivityLogger($conn);
    
    // Get filter parameters
    $action_type = $_GET['action_type'] ?? null;
    $search = $_GET['search'] ?? null;
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
    $sort_by = $_GET['sort_by'] ?? 'created_at';
    $sort_order = $_GET['sort_order'] ?? 'DESC';
    
    // Validate sort parameters
    $allowed_sort_fields = ['created_at', 'action_type', 'username', 'full_name'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'created_at';
    }
    if (!in_array($sort_order, $allowed_sort_orders)) {
        $sort_order = 'DESC';
    }
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Handle export request
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $activities = $logger->getAllActivities(10000, $action_type, $search, $date_from, $date_to); // Get more for export
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Time', 'User', 'Action', 'Description', 'IP Address']);
        
        foreach ($activities as $activity) {
            fputcsv($output, [
                date('Y-m-d', strtotime($activity['created_at'])),
                date('H:i:s', strtotime($activity['created_at'])),
                $activity['full_name'] ?: $activity['username'],
                ucwords(str_replace('_', ' ', $activity['action_type'])),
                $activity['description'],
                $activity['ip_address']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // Get initial activities for non-AJAX requests
    $activities = $logger->getAllActivitiesPaginated($page, $per_page, $action_type, $search, $date_from, $date_to, $sort_by, strtolower($sort_order));
    $total_count = $logger->getActivitiesCount($action_type, $search, $date_from, $date_to);
    $total_pages = ceil($total_count / $per_page);
    
    // Get unique action types for filter
    $stmt = $conn->query("SELECT DISTINCT action_type FROM activity_log ORDER BY action_type");
    $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get activity statistics
    $stats = [];
    $stmt = $conn->query("SELECT action_type, COUNT(*) as count FROM activity_log GROUP BY action_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['action_type']] = $row['count'];
    }
    $total_activities = array_sum($stats);
    
    // Get today's activity count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $today_activities = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error = "Error loading activity log: " . $e->getMessage();
    $activities = [];
    $action_types = [];
}
?>

<style>
.activity-log-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    padding: 1px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    margin-bottom: 15px;
}

.activity-log {
    background: white;
    border-radius: 7px;
    overflow: hidden;
}

.activity-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
    position: relative;
    margin: 0 5px;
    border-radius: 4px;
    margin-bottom: 3px;
}

.activity-item:hover {
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.1);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 6px;
    flex-wrap: wrap;
    gap: 8px;
}

.activity-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.activity-action {
    font-weight: bold;
    color: #667eea;
}

.activity-time {
    color: #8b95a7;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.activity-user {
    color: #2d3748;
    font-weight: 600;
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.activity-description {
    color: #4a5568;
    margin-bottom: 6px;
    font-size: 0.95rem;
    line-height: 1.4;
}

.activity-metadata {
    font-size: 0.85rem;
    color: #718096;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    padding: 8px;
    border-radius: 4px;
    margin-top: 6px;
    border-left: 3px solid #667eea;
}

.filter-search-bar {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}

.search-section {
    margin-bottom: 15px;
}

.search-box {
    position: relative;
    margin-bottom: 10px;
}

.search-input {
    width: 100%;
    padding: 8px 35px 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    pointer-events: none;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    align-items: end;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #4a5568;
}

.form-control {
    width: 100%;
    padding: 6px 8px;
    border: 2px solid #e2e8f0;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    font-size: 0.85rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
}

.btn-secondary:hover {
    background: #cbd5e0;
    transform: translateY(-1px);
}

.action-badge {
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.action-badge.create_shift { 
    background: linear-gradient(135deg, #68d391 0%, #48bb78 100%); 
    color: white; 
    box-shadow: 0 2px 10px rgba(72, 187, 120, 0.3);
}
.action-badge.update_shift { 
    background: linear-gradient(135deg, #f6e05e 0%, #ecc94b 100%); 
    color: #744210; 
    box-shadow: 0 2px 10px rgba(246, 224, 94, 0.3);
}
.action-badge.delete_shift { 
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%); 
    color: white; 
    box-shadow: 0 2px 10px rgba(245, 101, 101, 0.3);
}
.action-badge.confirm_shift { 
    background: linear-gradient(135deg, #63b3ed 0%, #4299e1 100%); 
    color: white; 
    box-shadow: 0 2px 10px rgba(66, 153, 225, 0.3);
}
.action-badge.reschedule_shift { 
    background: linear-gradient(135deg, #a78bfa 0%, #9f7aea 100%); 
    color: white; 
    box-shadow: 0 2px 10px rgba(159, 122, 234, 0.3);
}
.action-badge.login { 
    background: linear-gradient(135deg, #68d391 0%, #48bb78 100%); 
    color: white; 
    box-shadow: 0 2px 10px rgba(72, 187, 120, 0.3);
}
.action-badge.logout { 
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%); 
    color: white; 
    box-shadow: 0 2px 10px rgba(245, 101, 101, 0.3);
}

.activity-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
    margin-right: 8px;
    flex-shrink: 0;
}

.activity-icon.create_shift { background: linear-gradient(135deg, #68d391 0%, #48bb78 100%); }
.activity-icon.update_shift { background: linear-gradient(135deg, #f6e05e 0%, #ecc94b 100%); color: #744210; }
.activity-icon.delete_shift { background: linear-gradient(135deg, #fc8181 0%, #f56565 100%); }
.activity-icon.confirm_shift { background: linear-gradient(135deg, #63b3ed 0%, #4299e1 100%); }
.activity-icon.reschedule_shift { background: linear-gradient(135deg, #a78bfa 0%, #9f7aea 100%); }
.activity-icon.login { background: linear-gradient(135deg, #68d391 0%, #48bb78 100%); }
.activity-icon.logout { background: linear-gradient(135deg, #fc8181 0%, #f56565 100%); }

.no-activities {
    text-align: center;
    padding: 40px 20px;
    color: #718096;
}

.no-activities i {
    font-size: 2rem;
    margin-bottom: 10px;
    color: #cbd5e0;
}

.ip-address {
    font-size: 0.7rem;
    color: #a0aec0;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 3px;
}

.activity-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.stat-card {
    background: white;
    padding: 12px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border-left: 3px solid #667eea;
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 3px;
}

.stat-label {
    color: #718096;
    font-weight: 500;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .activity-left {
        width: 100%;
        margin-bottom: 6px;
    }
    
    .activity-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .activity-icon {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
    
    .filter-search-bar {
        padding: 12px;
    }
    
    .activity-item {
        padding: 10px;
        margin: 0 3px;
    }
}

@media (max-width: 480px) {
    .activity-stats {
        grid-template-columns: 1fr;
    }
    
    .activity-left {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .activity-icon {
        align-self: flex-start;
    }
}

/* Loading animation */
.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Highlight search results */
mark {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}

/* Improved scrollbar for activity log */
.activity-log::-webkit-scrollbar {
    width: 8px;
}

.activity-log::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.activity-log::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
}

.activity-log::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

.activity-details-toggle {
    margin-top: 6px;
    margin-bottom: 4px;
}

.toggle-details-btn {
    background: none;
    border: none;
    color: #667eea;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 2px 0;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.toggle-details-btn:hover {
    color: #5a67d8;
    text-decoration: underline;
}

.toggle-details-btn i {
    transition: transform 0.2s ease;
}

.toggle-details-btn.expanded i {
    transform: rotate(180deg);
}

.activity-details {
    overflow: hidden;
    transition: all 0.3s ease;
    max-height: 0;
    opacity: 0;
}

.activity-details.show {
    max-height: 500px;
    opacity: 1;
    margin-top: 6px;
}

/* Compact mode styles */
.activity-log.compact .activity-item {
    padding: 8px 12px;
    margin-bottom: 2px;
}

.activity-log.compact .activity-icon {
    width: 24px;
    height: 24px;
    font-size: 0.75rem;
    margin-right: 6px;
}

.activity-log.compact .activity-description {
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.activity-log.compact .activity-header {
    margin-bottom: 4px;
    gap: 6px;
}

.activity-log.compact .action-badge {
    padding: 2px 6px;
    font-size: 0.65rem;
}

.activity-log.compact .activity-time {
    font-size: 0.8rem;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.75rem;
    margin-right: 3px;
    margin-bottom: 3px;
}

.btn-sm:hover {
    transform: translateY(-1px);
}

/* Pagination styles */
.pagination-container {
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-top: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.pagination-info {
    color: #718096;
    font-size: 0.9rem;
    font-weight: 500;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-numbers {
    display: flex;
    gap: 2px;
    margin: 0 10px;
}

.pagination-btn {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #4a5568;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.pagination-btn:hover:not(:disabled) {
    background: #f7fafc;
    border-color: #667eea;
    color: #667eea;
}

.pagination-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f8f9fa;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading .activity-item {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 0.8; }
}

/* Responsive pagination */
@media (max-width: 768px) {
    .pagination-container {
        flex-direction: column;
        text-align: center;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination-numbers {
        order: -1;
        margin: 0 0 10px 0;
    }
}

/* Pulse animation for new activities */
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
    100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
}

.activity-item.new {
    animation: pulse 2s;
}
</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="page-header">
    <h1><i class="fas fa-history"></i> Activity Log</h1>
    <p>Track all user actions and system activities</p>
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin'): ?>
    <div class="alert alert-info" style="margin-top: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
        <i class="fas fa-crown"></i> <strong>Super Admin View:</strong> You are viewing activities from ALL companies and system-wide operations.
    </div>
    <?php elseif (isset($_SESSION['company_id'])): ?>
    <div class="alert alert-secondary" style="margin-top: 10px;">
        <i class="fas fa-building"></i> <strong>Company View:</strong> Showing activities for your company only.
    </div>
    <?php endif; ?>
</div>

<!-- Activity Statistics -->
<div class="activity-stats">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($total_count); ?></div>
        <div class="stat-label">Total Activities</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($activities); ?></div>
        <div class="stat-label">Showing Results</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($action_types); ?></div>
        <div class="stat-label">Action Types</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($today_activities); ?></div>
        <div class="stat-label">Today's Activities</div>
    </div>
</div>

<!-- Filter and Search Bar -->
<div class="filter-search-bar">
    <div class="search-section">
        <h4 style="margin-bottom: 15px; color: #4a5568;"><i class="fas fa-search"></i> Search Activities</h4>
        <div class="search-box">
            <input type="text" id="searchInput" class="search-input" 
                   placeholder="Search by description, user, or any detail..." 
                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <i class="fas fa-search search-icon"></i>
        </div>
        
        <!-- Quick Date Filters -->
        <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setDateRange('today')">Today</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setDateRange('yesterday')">Yesterday</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setDateRange('week')">This Week</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setDateRange('month')">This Month</button>
            </div>
            <div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCompactMode()" id="compactModeBtn">
                    <i class="fas fa-compress-alt"></i> Compact View
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllDetails(true)">
                    <i class="fas fa-chevron-down"></i> Show All Details
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllDetails(false)">
                    <i class="fas fa-chevron-up"></i> Hide All Details
                </button>
            </div>
        </div>
    </div>
    
    <form method="GET" id="filterForm">
        <input type="hidden" name="search" id="hiddenSearch" value="<?php echo htmlspecialchars($search ?? ''); ?>">
        <div class="filter-row">
            <div class="form-group">
                <label for="action_type"><i class="fas fa-filter"></i> Filter by Action:</label>
                <select name="action_type" id="action_type" class="form-control">
                    <option value="">All Actions</option>
                    <?php foreach ($action_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $action_type === $type ? 'selected' : ''; ?>>
                            <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_from"><i class="fas fa-calendar"></i> From Date:</label>
                <input type="date" name="date_from" id="date_from" class="form-control" 
                       value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to"><i class="fas fa-calendar"></i> To Date:</label>
                <input type="date" name="date_to" id="date_to" class="form-control" 
                       value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="per_page"><i class="fas fa-list-ol"></i> Per Page:</label>
                <select name="per_page" id="per_page" class="form-control">
                    <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25 entries</option>
                    <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50 entries</option>
                    <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100 entries</option>
                    <option value="200" <?php echo $per_page === 200 ? 'selected' : ''; ?>>200 entries</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="sort_by"><i class="fas fa-sort"></i> Sort By:</label>
                <select name="sort_by" id="sort_by" class="form-control">
                    <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date & Time</option>
                    <option value="action_type" <?php echo $sort_by === 'action_type' ? 'selected' : ''; ?>>Action Type</option>
                    <option value="username" <?php echo $sort_by === 'username' ? 'selected' : ''; ?>>Username</option>
                    <option value="full_name" <?php echo $sort_by === 'full_name' ? 'selected' : ''; ?>>Full Name</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="sort_order"><i class="fas fa-sort-amount-down"></i> Order:</label>
                <select name="sort_order" id="sort_order" class="form-control">
                    <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>&nbsp;</label>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear All
                    </a>
                    
                    <button type="submit" name="export" value="csv" class="btn" style="background: linear-gradient(135deg, #38a169 0%, #2f855a 100%); color: white;">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Activity Log -->
<div class="activity-log-container">
    <div class="activity-log" id="activityLogContainer">
        <!-- Loading indicator -->
        <div id="loadingIndicator" style="display: none; text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
            <p style="margin-top: 10px; color: #718096;">Loading activities...</p>
        </div>
        
        <!-- Activities will be loaded here -->
        <div id="activitiesContent">
            <?php if (empty($activities)): ?>
                <div class="no-activities">
                    <i class="fas fa-inbox"></i>
                    <h3>No activities found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <?php 
                    // Check if there are actually details to show
                    $hasMetadata = !empty($activity['metadata']);
                    $hasIpAddress = !empty($activity['ip_address']);
                    $hasDetails = $hasMetadata || $hasIpAddress;
                    ?>
                    <div class="activity-item">
                        <div class="activity-header">
                            <div class="activity-left">
                                <div class="activity-icon <?php echo $activity['action_type']; ?>">
                                    <?php
                                    $icons = [
                                        'create_shift' => 'fas fa-plus',
                                        'update_shift' => 'fas fa-edit',
                                        'delete_shift' => 'fas fa-trash',
                                        'confirm_shift' => 'fas fa-check',
                                        'reschedule_shift' => 'fas fa-calendar-alt',
                                        'login' => 'fas fa-sign-in-alt',
                                        'logout' => 'fas fa-sign-out-alt'
                                    ];
                                    $icon = $icons[$activity['action_type']] ?? 'fas fa-info-circle';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div>
                                    <span class="action-badge <?php echo $activity['action_type']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $activity['action_type'])); ?>
                                        <?php if ($hasDetails): ?>
                                            <i class="fas fa-info-circle" style="font-size: 0.55rem; opacity: 0.7;" title="Has additional details"></i>
                                        <?php endif; ?>
                                    </span>
                                    <div class="activity-user">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($activity['full_name'] ?: $activity['username']); ?>
                                    </div>
                                </div>
                            </div>
                            <span class="activity-time">
                                <i class="fas fa-clock"></i>
                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="activity-description">
                            <?php echo htmlspecialchars($activity['description']); ?>
                        </div>
                        
                        <!-- Compact details toggle -->
                        <div class="activity-details-toggle">
                            <?php if ($hasDetails): ?>
                                <button type="button" class="toggle-details-btn" onclick="toggleDetails(this)">
                                    <i class="fas fa-chevron-down"></i> Show Details
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Collapsible details section -->
                        <div class="activity-details">
                            <?php if ($hasMetadata): ?>
                                <div class="activity-metadata">
                                    <strong><i class="fas fa-info-circle"></i> Details:</strong>
                                    <?php
                                    $metadata = json_decode($activity['metadata'], true);
                                    if ($metadata) {
                                        if (isset($metadata['changes']) && !empty($metadata['changes'])) {
                                            echo "<br><strong>Changes:</strong>";
                                            foreach ($metadata['changes'] as $field => $change) {
                                                echo "<br>• " . ucwords(str_replace('_', ' ', $field)) . ": " . 
                                                     "<span style='color: #e53e3e;'>" . htmlspecialchars($change['from']) . "</span> → " . 
                                                     "<span style='color: #38a169;'>" . htmlspecialchars($change['to']) . "</span>";
                                            }
                                        }
                                        if (isset($metadata['reschedule_reason'])) {
                                            echo "<br><strong>Reason:</strong> " . htmlspecialchars($metadata['reschedule_reason']);
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($hasIpAddress): ?>
                                <div class="ip-address">
                                    <i class="fas fa-globe"></i>
                                    IP: <?php echo htmlspecialchars($activity['ip_address']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pagination Controls -->
<div class="pagination-container" id="paginationContainer">
    <?php if ($total_pages > 1): ?>
        <div class="pagination-info">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_count); ?> of <?php echo number_format($total_count); ?> activities
        </div>
        <div class="pagination-controls">
            <button class="pagination-btn" onclick="loadPage(1)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                <i class="fas fa-angle-double-left"></i> First
            </button>
            <button class="pagination-btn" onclick="loadPage(<?php echo $page - 1; ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                <i class="fas fa-angle-left"></i> Previous
            </button>
            
            <div class="pagination-numbers">
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <button class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>" 
                            onclick="loadPage(<?php echo $i; ?>)">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
            
            <button class="pagination-btn" onclick="loadPage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                Next <i class="fas fa-angle-right"></i>
            </button>
            <button class="pagination-btn" onclick="loadPage(<?php echo $total_pages; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                Last <i class="fas fa-angle-double-right"></i>
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
// Global variables for pagination
let currentPage = <?php echo $page; ?>;
let currentFilters = {
    search: '<?php echo htmlspecialchars($search ?? ''); ?>',
    action_type: '<?php echo htmlspecialchars($action_type ?? ''); ?>',
    date_from: '<?php echo htmlspecialchars($date_from ?? ''); ?>',
    date_to: '<?php echo htmlspecialchars($date_to ?? ''); ?>',
    per_page: <?php echo $per_page; ?>,
    sort_by: '<?php echo $sort_by; ?>',
    sort_order: '<?php echo $sort_order; ?>'
};

// Load page function
function loadPage(page) {
    if (page < 1) return;
    
    currentPage = page;
    loadActivities();
}

// Load activities with AJAX
function loadActivities() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const activitiesContent = document.getElementById('activitiesContent');
    const paginationContainer = document.getElementById('paginationContainer');
    
    // Show loading state
    loadingIndicator.style.display = 'block';
    activitiesContent.style.display = 'none';
    
    // Build query parameters
    const params = new URLSearchParams({
        ajax: '1',
        page: currentPage,
        ...currentFilters
    });
    
    fetch(window.location.pathname + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update activities content
                activitiesContent.innerHTML = renderActivities(data.activities);
                
                // Update pagination
                updatePagination(data.pagination);
                
                // Update stats
                updateStats(data.pagination);
                
                // Update URL without reloading page
                const newUrl = window.location.pathname + '?' + new URLSearchParams({
                    page: currentPage,
                    ...currentFilters
                }).toString();
                window.history.replaceState({}, '', newUrl);
                
            } else {
                activitiesContent.innerHTML = `
                    <div class="no-activities">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Activities</h3>
                        <p>${data.message || 'Please try again later'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading activities:', error);
            activitiesContent.innerHTML = `
                <div class="no-activities">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Connection Error</h3>
                    <p>Unable to load activities. Please check your connection and try again.</p>
                </div>
            `;
        })
        .finally(() => {
            // Hide loading state
            loadingIndicator.style.display = 'none';
            activitiesContent.style.display = 'block';
        });
}

// Render activities HTML
function renderActivities(activities) {
    if (activities.length === 0) {
        return `
            <div class="no-activities">
                <i class="fas fa-inbox"></i>
                <h3>No activities found</h3>
                <p>Try adjusting your search or filter criteria</p>
            </div>
        `;
    }
    
    return activities.map(activity => {
        const hasMetadata = activity.metadata && activity.metadata.trim() !== '';
        const hasIpAddress = activity.ip_address && activity.ip_address.trim() !== '';
        const hasDetails = hasMetadata || hasIpAddress;
        
        const icons = {
            'create_shift': 'fas fa-plus',
            'update_shift': 'fas fa-edit',
            'delete_shift': 'fas fa-trash',
            'confirm_shift': 'fas fa-check',
            'reschedule_shift': 'fas fa-calendar-alt',
            'login': 'fas fa-sign-in-alt',
            'logout': 'fas fa-sign-out-alt'
        };
        const icon = icons[activity.action_type] || 'fas fa-info-circle';
        
        let detailsHtml = '';
        if (hasDetails) {
            let metadataHtml = '';
            if (hasMetadata) {
                try {
                    const metadata = JSON.parse(activity.metadata);
                    metadataHtml = '<div class="activity-metadata"><strong><i class="fas fa-info-circle"></i> Details:</strong>';
                    
                    if (metadata.changes && Object.keys(metadata.changes).length > 0) {
                        metadataHtml += '<br><strong>Changes:</strong>';
                        Object.entries(metadata.changes).forEach(([field, change]) => {
                            const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            metadataHtml += `<br>• ${fieldName}: <span style="color: #e53e3e;">${escapeHtml(change.from)}</span> → <span style="color: #38a169;">${escapeHtml(change.to)}</span>`;
                        });
                    }
                    
                    if (metadata.reschedule_reason) {
                        metadataHtml += `<br><strong>Reason:</strong> ${escapeHtml(metadata.reschedule_reason)}`;
                    }
                    
                    metadataHtml += '</div>';
                } catch (e) {
                    metadataHtml = `<div class="activity-metadata"><strong>Details:</strong><br>${escapeHtml(activity.metadata)}</div>`;
                }
            }
            
            let ipHtml = '';
            if (hasIpAddress) {
                ipHtml = `<div class="ip-address"><i class="fas fa-globe"></i> IP: ${escapeHtml(activity.ip_address)}</div>`;
            }
            
            detailsHtml = `
                <div class="activity-details-toggle">
                    <button type="button" class="toggle-details-btn" onclick="toggleDetails(this)">
                        <i class="fas fa-chevron-down"></i> Show Details
                    </button>
                </div>
                <div class="activity-details">
                    ${metadataHtml}
                    ${ipHtml}
                </div>
            `;
        }
        
        const actionTypeDisplay = activity.action_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const fullName = activity.full_name || activity.username;
        const createdAt = new Date(activity.created_at);
        const timeString = createdAt.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        return `
            <div class="activity-item">
                <div class="activity-header">
                    <div class="activity-left">
                        <div class="activity-icon ${activity.action_type}">
                            <i class="${icon}"></i>
                        </div>
                        <div>
                            <span class="action-badge ${activity.action_type}">
                                ${actionTypeDisplay}
                                ${hasDetails ? '<i class="fas fa-info-circle" style="font-size: 0.55rem; opacity: 0.7;" title="Has additional details"></i>' : ''}
                            </span>
                            <div class="activity-user">
                                <i class="fas fa-user"></i>
                                ${escapeHtml(fullName)}
                            </div>
                        </div>
                    </div>
                    <span class="activity-time">
                        <i class="fas fa-clock"></i>
                        ${timeString}
                    </span>
                </div>
                <div class="activity-description">
                    ${escapeHtml(activity.description)}
                </div>
                ${detailsHtml}
            </div>
        `;
    }).join('');
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Update pagination controls
function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.total_pages <= 1) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    
    const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_count);
    
    // Generate pagination numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    let numbersHtml = '';
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === pagination.current_page ? 'active' : '';
        numbersHtml += `<button class="pagination-btn ${activeClass}" onclick="loadPage(${i})">${i}</button>`;
    }
    
    container.innerHTML = `
        <div class="pagination-info">
            Showing ${start.toLocaleString()} to ${end.toLocaleString()} of ${pagination.total_count.toLocaleString()} activities
        </div>
        <div class="pagination-controls">
            <button class="pagination-btn" onclick="loadPage(1)" ${pagination.current_page <= 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-double-left"></i> First
            </button>
            <button class="pagination-btn" onclick="loadPage(${pagination.current_page - 1})" ${pagination.current_page <= 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-left"></i> Previous
            </button>
            <div class="pagination-numbers">
                ${numbersHtml}
            </div>
            <button class="pagination-btn" onclick="loadPage(${pagination.current_page + 1})" ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}>
                Next <i class="fas fa-angle-right"></i>
            </button>
            <button class="pagination-btn" onclick="loadPage(${pagination.total_pages})" ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}>
                Last <i class="fas fa-angle-double-right"></i>
            </button>
        </div>
    `;
}

// Update statistics
function updateStats(pagination) {
    const statCards = document.querySelectorAll('.stat-number');
    if (statCards.length >= 2) {
        statCards[0].textContent = pagination.total_count.toLocaleString();
        statCards[1].textContent = pagination.per_page.toLocaleString();
    }
}

// Update filters and reload
function updateFilters() {
    // Get current filter values
    currentFilters = {
        search: document.getElementById('searchInput').value,
        action_type: document.getElementById('action_type').value,
        date_from: document.getElementById('date_from').value,
        date_to: document.getElementById('date_to').value,
        per_page: parseInt(document.getElementById('per_page').value),
        sort_by: document.getElementById('sort_by').value,
        sort_order: document.getElementById('sort_order').value
    };
    
    // Reset to first page when filters change
    currentPage = 1;
    
    // Load activities with new filters
    loadActivities();
}

// Search functionality
function performSearch() {
    updateFilters();
}

// Debounced search for real-time filtering
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 300);
}

// Date filter presets
function setDateFilter(preset) {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const today = new Date();
    
    switch(preset) {
        case 'today':
            dateFrom.value = dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            dateFrom.value = dateTo.value = yesterday.toISOString().split('T')[0];
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            dateFrom.value = weekAgo.toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(monthAgo.getMonth() - 1);
            dateFrom.value = monthAgo.toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'clear':
            dateFrom.value = dateTo.value = '';
            break;
    }
    performSearch();
}

// Event listeners for AJAX functionality
document.addEventListener('DOMContentLoaded', function() {
    // Search input event listener
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
    }
    
    // Filter change event listeners
    const filterElements = ['action_type', 'date_from', 'date_to', 'per_page', 'sort_by', 'sort_order'];
    filterElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', performSearch);
        }
    });
    
    // Filter form submission
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Arrow keys for pagination
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT') {
            if (e.key === 'ArrowLeft' && currentPage > 1) {
                e.preventDefault();
                loadPage(currentPage - 1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                loadPage(currentPage + 1);
            }
        }
    });
});

// Toggle details functionality
function toggleDetails(button) {
    const activityItem = button.closest('.activity-item');
    const detailsSection = activityItem.querySelector('.activity-details');
    const isExpanded = button.classList.contains('expanded');
    
    if (!detailsSection) {
        console.error('Details section not found!');
        return;
    }
    
    if (isExpanded) {
        // Collapse
        detailsSection.classList.remove('show');
        button.classList.remove('expanded');
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
    } else {
        // Expand
        detailsSection.classList.add('show');
        button.classList.add('expanded');
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
    }
}

// Toggle compact mode
function toggleCompactMode() {
    const activityLog = document.querySelector('.activity-log');
    const compactBtn = document.getElementById('compactModeBtn');
    const isCompact = activityLog.classList.contains('compact');
    
    if (isCompact) {
        activityLog.classList.remove('compact');
        compactBtn.innerHTML = '<i class="fas fa-compress-alt"></i> Compact View';
        localStorage.setItem('activityLogCompact', 'false');
    } else {
        activityLog.classList.add('compact');
        compactBtn.innerHTML = '<i class="fas fa-expand-alt"></i> Normal View';
        localStorage.setItem('activityLogCompact', 'true');
    }
}

// Load compact mode preference
document.addEventListener('DOMContentLoaded', function() {
    const isCompact = localStorage.getItem('activityLogCompact') === 'true';
    if (isCompact) {
        const activityLog = document.querySelector('.activity-log');
        const compactBtn = document.getElementById('compactModeBtn');
        activityLog.classList.add('compact');
        compactBtn.innerHTML = '<i class="fas fa-expand-alt"></i> Normal View';
    }
});

// Toggle all details at once
function toggleAllDetails(show = null) {
    const buttons = document.querySelectorAll('.toggle-details-btn');
    
    buttons.forEach(button => {
        const activityItem = button.closest('.activity-item');
        const detailsSection = activityItem.querySelector('.activity-details');
        const isCurrentlyExpanded = button.classList.contains('expanded');
        
        if (show === null) {
            // Auto-determine based on majority state
            const expandedCount = document.querySelectorAll('.toggle-details-btn.expanded').length;
            show = expandedCount < buttons.length / 2;
        }
        
        if (show && !isCurrentlyExpanded) {
            detailsSection.classList.add('show');
            button.classList.add('expanded');
            button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
        } else if (!show && isCurrentlyExpanded) {
            detailsSection.classList.remove('show');
            button.classList.remove('expanded');
            button.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
        }
    });
}

// Quick date range functions
function setDateRange(range) {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const today = new Date();
    
    switch(range) {
        case 'today':
            dateFrom.value = today.toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);
            dateFrom.value = yesterday.toISOString().split('T')[0];
            dateTo.value = yesterday.toISOString().split('T')[0];
            break;
        case 'week':
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            dateFrom.value = weekStart.toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'month':
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            dateFrom.value = monthStart.toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
    }
    
    // Submit form automatically
    document.getElementById('filterForm').submit();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Escape to clear search
    if (e.key === 'Escape' && document.activeElement.id === 'searchInput') {
        const searchInput = document.getElementById('searchInput');
        const hiddenSearch = document.getElementById('hiddenSearch');
        const filterForm = document.getElementById('filterForm');
        if (searchInput && hiddenSearch && filterForm) {
            searchInput.value = '';
            hiddenSearch.value = '';
            filterForm.submit();
        }
    }
    
    // Ctrl/Cmd + D to toggle all details
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        toggleAllDetails();
    }
    
    // Space to toggle details when focused on a toggle button
    if (e.key === ' ' && document.activeElement.classList.contains('toggle-details-btn')) {
        e.preventDefault();
        toggleDetails(document.activeElement);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
