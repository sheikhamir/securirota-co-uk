<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Deployment & Operations';
require_once '../includes/header.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Initialize company filtering
    $use_company_filter = false;
    $company_id = null;

    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM shifts LIKE 'company_id'");
        if ($column_check->rowCount() > 0) {
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
    
    // Get filter parameters
    $date_filter = $_GET['date'] ?? '';  // Show all dates by default
    $site_filter = $_GET['site_id'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    // Build deployment query
    $sql = "
        SELECT 
            s.id as shift_id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.status,
            r.name as role_name,
            s.notes,
            CONCAT(o.first_name, ' ', o.last_name) as officer_name,
            o.phone as officer_phone,
            st.site_name as site_name,
            st.address as site_address,
            st.contact_person as site_contact,
            st.contact_phone as site_phone,
            c.company_name as client_name,
            CASE 
                WHEN s.end_time < s.start_time 
                THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(s.end_time, '24:00:00'), s.start_time)) / 3600
                ELSE TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600
            END as hours
        FROM shifts s
        LEFT JOIN officers o ON s.officer_id = o.id
        LEFT JOIN roles r ON s.role_id = r.id
        JOIN sites st ON s.site_id = st.id
        JOIN clients c ON st.client_id = c.id
        WHERE 1=1
    ";
    
    $params = [];

    if ($use_company_filter && $company_id) {
        $sql .= " AND s.company_id = ?";
        $params[] = $company_id;
    }
    
    if ($date_filter) {
        $sql .= " AND s.shift_date = ?";
        $params[] = $date_filter;
    }
    
    if ($site_filter) {
        $sql .= " AND st.id = ?";
        $params[] = $site_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND s.status = ?";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY s.shift_date DESC, s.start_time";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading deployment data: " . $e->getMessage();
        $deployments = [];
    }
    
    // Get sites for filter
    try {
        if ($use_company_filter && $company_id) {
            $sites_stmt = $conn->prepare("
                SELECT s.id, s.site_name, c.company_name as client_name
                FROM sites s
                JOIN clients c ON s.client_id = c.id
                WHERE s.status = 'active'
                AND s.company_id = ?
                ORDER BY c.company_name, s.site_name
            ");
            $sites_stmt->execute([$company_id]);
        } else {
            $sites_stmt = $conn->query("
                SELECT s.id, s.site_name, c.company_name as client_name
                FROM sites s
                JOIN clients c ON s.client_id = c.id
                WHERE s.status = 'active'
                ORDER BY c.company_name, s.site_name
            ");
        }
        $sites = $sites_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $sites = [];
    }
    
    // Get statistics (initialize with safe defaults)
    $total_shifts = count($deployments);
    $confirmed_shifts = count(array_filter($deployments, function($d) { return $d['status'] === 'confirmed'; }));
    $unallocated_shifts = count(array_filter($deployments, function($d) { return $d['status'] === 'unallocated'; }));
    $total_hours = array_sum(array_column($deployments, 'hours'));
    
    // Group by status for overview
    $status_overview = [];
    foreach ($deployments as $deployment) {
        $status = $deployment['status'];
        if (!isset($status_overview[$status])) {
            $status_overview[$status] = 0;
        }
        $status_overview[$status]++;
    }
    
} catch (Exception $e) {
    $error = "Error loading deployment data: " . $e->getMessage();
}
?>

<style>
.deployment-filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.deployment-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.stat-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-box.total::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.stat-box.confirmed::before { background: linear-gradient(90deg, #28a745, #20c997); }
.stat-box.unallocated::before { background: linear-gradient(90deg, #6c757d, #5a6268); }
.stat-box.hours::before { background: linear-gradient(90deg, #17a2b8, #138496); }

.stat-box h3 {
    font-size: 2rem;
    margin-bottom: 5px;
    color: #333;
}

.stat-box p {
    color: #666;
    margin: 0;
}

.deployment-item {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 15px;
    overflow: hidden;
    border-left: 4px solid;
}

.deployment-item.confirmed { border-left-color: #28a745; }
.deployment-item.allocated { border-left-color: #ffc107; }
.deployment-item.unallocated { border-left-color: #6c757d; }
.deployment-item.declined { border-left-color: #dc3545; }
.deployment-item.completed { border-left-color: #17a2b8; }

.deployment-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.deployment-content {
    padding: 20px;
}

.deployment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-section h5 {
    color: #667eea;
    margin-bottom: 10px;
    font-size: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    gap: 10px;
}

.info-item i {
    width: 16px;
    color: #666;
}

.deployment-actions {
    display: flex;
    gap: 10px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.time-tracking {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.quick-actions {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}
</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Deployment Filters -->
<div class="deployment-filters">
    <h3><i class="fas fa-filter"></i> Deployment Filters</h3>
    
    <form method="GET" class="mt-20">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>" placeholder="All dates">
                <small class="text-muted">Leave empty to show all dates</small>
            </div>
            
            <div class="form-group">
                <label>Site:</label>
                <select name="site_id" class="form-control">
                    <option value="">All Sites</option>
                    <?php foreach ($sites as $site): ?>
                        <option value="<?php echo $site['id']; ?>" <?php echo $site_filter == $site['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($site['site_name'] . ' (' . $site['client_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="unallocated" <?php echo $status_filter === 'unallocated' ? 'selected' : ''; ?>>Unallocated</option>
                    <option value="allocated" <?php echo $status_filter === 'allocated' ? 'selected' : ''; ?>>Allocated</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="declined" <?php echo $status_filter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
        </div>
        
        <div class="mt-20">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter Results
            </button>
            <a href="deployment.php" class="btn btn-secondary">
                <i class="fas fa-refresh"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h3><i class="fas fa-lightning-bolt"></i> Quick Actions</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
        <button onclick="markAllConfirmed()" class="btn btn-success">
            <i class="fas fa-check-circle"></i> Mark All Confirmed
        </button>
        
        <button onclick="exportDeployment()" class="btn btn-info">
            <i class="fas fa-download"></i> Export Deployment
        </button>
        
        <a href="rota.php?week=<?php echo date('Y-m-d', strtotime('monday this week')); ?>" class="btn btn-primary">
            <i class="fas fa-calendar-alt"></i> View Rota
        </a>
        
        <button onclick="sendNotifications()" class="btn btn-warning">
            <i class="fas fa-bell"></i> Send Notifications
        </button>
    </div>
</div>

<!-- Statistics -->
<div class="deployment-stats">
    <div class="stat-box total">
        <h3><?php echo $total_shifts; ?></h3>
        <p><i class="fas fa-calendar-check"></i> Total Shifts</p>
    </div>
    
    <div class="stat-box confirmed">
        <h3><?php echo $confirmed_shifts; ?></h3>
        <p><i class="fas fa-check-circle"></i> Confirmed</p>
    </div>
    
    <div class="stat-box unallocated">
        <h3><?php echo $unallocated_shifts; ?></h3>
        <p><i class="fas fa-exclamation-triangle"></i> Unallocated</p>
    </div>
    
    <div class="stat-box hours">
        <h3><?php echo number_format($total_hours, 1); ?></h3>
        <p><i class="fas fa-clock"></i> Total Hours</p>
    </div>
</div>

<!-- Deployment List -->
<div class="deployment-list">
    <?php if (empty($deployments)): ?>
        <div class="text-center p-20">
            <p class="text-muted">No deployments found for the selected criteria.</p>
            <a href="rota.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Schedule Shifts
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($deployments as $deployment): ?>
            <div class="deployment-item <?php echo $deployment['status']; ?>">
                <div class="deployment-header">
                    <div>
                        <h4><?php echo htmlspecialchars($deployment['site_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($deployment['client_name']); ?></p>
                    </div>
                    
                    <div class="text-right">
                        <span class="badge badge-<?php 
                            echo $deployment['status'] === 'confirmed' ? 'success' : 
                                ($deployment['status'] === 'declined' ? 'danger' : 
                                ($deployment['status'] === 'allocated' ? 'warning' : 
                                ($deployment['status'] === 'completed' ? 'info' : 'secondary'))); 
                        ?>">
                            <?php echo ucfirst($deployment['status']); ?>
                        </span>
                        
                        <div class="mt-5">
                            <small class="text-muted">
                                <?php echo formatDate($deployment['shift_date']); ?> • 
                                <?php echo formatTime($deployment['start_time']) . ' - ' . formatTime($deployment['end_time']); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="deployment-content">
                    <div class="deployment-grid">
                        <!-- Officer Information -->
                        <div class="info-section">
                            <h5><i class="fas fa-user"></i> Officer Assignment</h5>
                            <?php if ($deployment['officer_name']): ?>
                                <div class="info-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($deployment['officer_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($deployment['officer_phone'] ?: 'No phone'); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-id-badge"></i>
                                    <span><?php echo htmlspecialchars($deployment['role_name'] ?? $deployment['role'] ?? 'Unknown Role'); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="info-item">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span class="text-warning">Unallocated</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Site Information -->
                        <div class="info-section">
                            <h5><i class="fas fa-map-marker-alt"></i> Site Details</h5>
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <span><?php echo htmlspecialchars($deployment['site_name']); ?></span>
                            </div>
                            <?php if ($deployment['site_address']): ?>
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($deployment['site_address']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($deployment['site_contact']): ?>
                                <div class="info-item">
                                    <i class="fas fa-user-tie"></i>
                                    <span><?php echo htmlspecialchars($deployment['site_contact']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($deployment['site_phone']): ?>
                                <div class="info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($deployment['site_phone']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Time Tracking -->
                        <div class="info-section">
                            <h5><i class="fas fa-clock"></i> Time Tracking</h5>
                            <div class="info-item">
                                <i class="fas fa-play"></i>
                                <span>Scheduled: <?php echo formatTime($deployment['start_time']) . ' - ' . formatTime($deployment['end_time']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>Duration: <?php echo number_format($deployment['hours'], 2); ?> hours</span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($deployment['notes']): ?>
                        <div class="time-tracking">
                            <h6><i class="fas fa-sticky-note"></i> Notes</h6>
                            <p><?php echo nl2br(htmlspecialchars($deployment['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="deployment-actions">
                    <button onclick="editShift(<?php echo $deployment['shift_id']; ?>)" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    
                    <?php if ($deployment['status'] !== 'completed'): ?>
                        <button onclick="markAsCompleted(<?php echo $deployment['shift_id']; ?>)" class="btn btn-success btn-sm">
                            <i class="fas fa-check"></i> Mark Complete
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!$deployment['officer_name']): ?>
                        <button onclick="allocateOfficer(<?php echo $deployment['shift_id']; ?>)" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus"></i> Allocate Officer
                        </button>
                    <?php endif; ?>
                    
                    <button onclick="viewShiftHistory(<?php echo $deployment['shift_id']; ?>)" class="btn btn-info btn-sm">
                        <i class="fas fa-history"></i> History
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function markAsCompleted(shiftId) {
    if (confirm('Mark this shift as completed?')) {
        const formData = new FormData();
        formData.append('id', shiftId);
        formData.append('status', 'completed');
        
        fetch('../api/update_shift.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Shift marked as completed', 'success');
                location.reload();
            } else {
                showNotification('Error updating shift: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating shift', 'error');
        });
    }
}

function allocateOfficer(shiftId) {
    // This would open a modal to select an officer
    fetch('../api/get_shift.php?id=' + shiftId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = `
                    <form onsubmit="updateShiftOfficer(event, ${shiftId})">
                        <div class="form-group">
                            <label>Select Officer:</label>
                            <div class="officer-search-wrap">
                                <input type="hidden" name="officer_id" id="deploy_officer_select" value="" data-officer-name="" required>
                                <input type="text"
                                       id="deploy_officer_select_search"
                                       class="form-control"
                                       placeholder="Search officer by name, staff ID, or phone"
                                       autocomplete="off"
                                       required>
                                <div id="deploy_officer_select_results" class="officer-search-results"></div>
                            </div>
                            <div id="deploy_officer_link_container"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Allocate Officer</button>
                    </form>
                `;
                showModal('Allocate Officer', content);
                
                // Setup officer info link after modal is shown
                setTimeout(() => {
                    initOfficerAjaxPicker({
                        hiddenInputId: 'deploy_officer_select',
                        searchInputId: 'deploy_officer_select_search',
                        resultsId: 'deploy_officer_select_results',
                        linkContainerId: 'deploy_officer_link_container'
                    });
                }, 100);
            }
        });
}

function updateShiftOfficer(event, shiftId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    if (!formData.get('officer_id')) {
        showNotification('Please select an officer', 'error');
        return;
    }
    formData.append('id', shiftId);
    formData.append('status', 'allocated');

    fetch('../api/update_shift.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Officer allocated successfully', 'success');
            location.reload();
        } else {
            showNotification('Error allocating officer: ' + data.message, 'error');
        }
    });
}

function markAllConfirmed() {
    if (confirm('Mark all shifts as confirmed?')) {
        showNotification('Feature coming soon', 'info');
    }
}

function exportDeployment() {
    const params = new URLSearchParams(window.location.search);
    const url = '../api/export_deployment.php?' + params.toString();
    window.open(url, '_blank');
}

function sendNotifications() {
    if (confirm('Send notifications to all assigned officers?')) {
        showNotification('Feature coming soon', 'info');
    }
}

function viewShiftHistory(shiftId) {
    showNotification('Shift history feature coming soon', 'info');
}
</script>

<?php require_once '../includes/footer.php'; ?>
