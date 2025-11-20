<?php
$page_title = 'Dashboard';

// Check if user is root and redirect to special dashboard
session_start();
require_once 'config/config.php';

if (isRootUser()) {
    header('Location: ' . BASE_URL . 'root/root_dashboard.php');
    exit();
}

// Officers should use their own portal instead of the admin dashboard
if (hasRole('officer')) {
    header('Location: ' . BASE_URL . 'pages/officer_portal.php');
    exit();
}

// Check if user has permission to access admin dashboard
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'login.php?error=access_denied');
    exit();
}

require_once 'includes/header.php';

// Initialize variables
$recent_shifts = [];
$stats = [];
$expiring_docs = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get dashboard statistics
    // Note: Company filtering only applies after migration (when company_id column exists)
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM shifts LIKE 'company_id'");
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
    
    // Total shifts of this week (excluding unallocated and cancelled)
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM shifts 
            WHERE YEARWEEK(shift_date, 1) = YEARWEEK(CURDATE(), 1)
            AND status NOT IN ('unallocated', 'cancelled')
            AND officer_id IS NOT NULL
            AND company_id = ?
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM shifts 
            WHERE YEARWEEK(shift_date, 1) = YEARWEEK(CURDATE(), 1)
            AND status NOT IN ('unallocated', 'cancelled')
            AND officer_id IS NOT NULL
        ");
        $stmt->execute();
    }
    $stats['weekly_shifts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total sites
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM sites 
            WHERE status = 'active' AND company_id = ?
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sites WHERE status = 'active'");
        $stmt->execute();
    }
    $stats['total_sites'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Today's shifts
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM shifts 
            WHERE shift_date = CURDATE() AND company_id = ?
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM shifts WHERE shift_date = CURDATE()");
        $stmt->execute();
    }
    $stats['todays_shifts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // This week's total hours
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT SUM(
                CASE 
                    WHEN end_time < start_time 
                    THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(end_time, '24:00:00'), start_time)) / 3600
                    ELSE TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600
                END
            ) as total_hours 
            FROM shifts 
            WHERE YEARWEEK(shift_date, 1) = YEARWEEK(CURDATE(), 1)
            AND status IN ('confirmed', 'completed')
            AND company_id = ?
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT SUM(
                CASE 
                    WHEN end_time < start_time 
                    THEN TIME_TO_SEC(TIMEDIFF(ADDTIME(end_time, '24:00:00'), start_time)) / 3600
                    ELSE TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600
                END
            ) as total_hours 
            FROM shifts 
            WHERE YEARWEEK(shift_date, 1) = YEARWEEK(CURDATE(), 1)
            AND status IN ('confirmed', 'completed')
        ");
        $stmt->execute();
    }
    $stats['weekly_hours'] = round($stmt->fetch(PDO::FETCH_ASSOC)['total_hours'] ?? 0, 1);
    
    // Recent shifts
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT s.*, 
                   o.first_name, 
                   o.last_name, 
                   st.site_name, 
                   c.company_name as client_name
            FROM shifts s
            LEFT JOIN officers o ON s.officer_id = o.id
            LEFT JOIN sites st ON s.site_id = st.id
            LEFT JOIN clients c ON st.client_id = c.id
            WHERE s.shift_date >= CURDATE()
            AND s.company_id = ?
            ORDER BY s.shift_date ASC, s.start_time ASC
            LIMIT 10
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT s.*, 
                   o.first_name, 
                   o.last_name, 
                   st.site_name, 
                   c.company_name as client_name
            FROM shifts s
            LEFT JOIN officers o ON s.officer_id = o.id
            LEFT JOIN sites st ON s.site_id = st.id
            LEFT JOIN clients c ON st.client_id = c.id
            WHERE s.shift_date >= CURDATE()
            ORDER BY s.shift_date ASC, s.start_time ASC
            LIMIT 10
        ");
        $stmt->execute();
    }
    $recent_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Upcoming document expiries - Check both officers table and documents table
    $expiring_docs = [];
    
    // First, get expiries from officers table (SIA and Visa)
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT o.first_name, o.last_name, o.sia_badge_number, 
                   o.sia_expiry_date, o.visa_expiry_date, 'officers' as source
            FROM officers o
            WHERE o.employment_status != 'Inactive' 
            AND o.company_id = ?
            AND (o.sia_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) 
                 OR o.visa_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY))
            ORDER BY o.sia_expiry_date ASC, o.visa_expiry_date ASC
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT o.first_name, o.last_name, o.sia_badge_number, 
                   o.sia_expiry_date, o.visa_expiry_date, 'officers' as source
            FROM officers o
            WHERE o.employment_status != 'Inactive' 
            AND (o.sia_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) 
                 OR o.visa_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY))
            ORDER BY o.sia_expiry_date ASC, o.visa_expiry_date ASC
        ");
        $stmt->execute();
    }
    $officer_expiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Then, get expiries from documents table
    if ($use_company_filter && $company_id) {
        $stmt = $conn->prepare("
            SELECT o.first_name, o.last_name, d.document_type, d.expiry_date, 'documents' as source
            FROM documents d
            JOIN officers o ON d.officer_id = o.id
            WHERE o.employment_status != 'Inactive' 
            AND o.company_id = ?
            AND d.expiry_date IS NOT NULL
            AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
            ORDER BY d.expiry_date ASC
        ");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT o.first_name, o.last_name, d.document_type, d.expiry_date, 'documents' as source
            FROM documents d
            JOIN officers o ON d.officer_id = o.id
            WHERE o.employment_status != 'Inactive' 
            AND d.expiry_date IS NOT NULL
            AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
            ORDER BY d.expiry_date ASC
        ");
        $stmt->execute();
    }
    $document_expiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and sort all expiries by date
    $all_expiries = [];
    
    // Add officer expiries
    foreach ($officer_expiries as $officer) {
        if ($officer['sia_expiry_date'] && $officer['sia_expiry_date'] <= date('Y-m-d', strtotime('+60 days'))) {
            $all_expiries[] = [
                'first_name' => $officer['first_name'],
                'last_name' => $officer['last_name'],
                'document_type' => 'SIA Badge',
                'expiry_date' => $officer['sia_expiry_date'],
                'source' => 'officers'
            ];
        }
        if ($officer['visa_expiry_date'] && $officer['visa_expiry_date'] <= date('Y-m-d', strtotime('+60 days'))) {
            $all_expiries[] = [
                'first_name' => $officer['first_name'],
                'last_name' => $officer['last_name'],
                'document_type' => 'Visa',
                'expiry_date' => $officer['visa_expiry_date'],
                'source' => 'officers'
            ];
        }
    }
    
    // Add document table expiries
    foreach ($document_expiries as $doc) {
        $all_expiries[] = [
            'first_name' => $doc['first_name'],
            'last_name' => $doc['last_name'],
            'document_type' => ucwords(str_replace('_', ' ', $doc['document_type'])),
            'expiry_date' => $doc['expiry_date'],
            'source' => 'documents'
        ];
    }
    
    // Sort by expiry date and limit to 10
    usort($all_expiries, function($a, $b) {
        return strtotime($a['expiry_date']) - strtotime($b['expiry_date']);
    });
    
    $expiring_docs = array_slice($all_expiries, 0, 10);
    
} catch (Exception $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Get monthly hours data for chart (separate try-catch for better error handling)
$monthly_hours = array_fill(0, 12, 0);
try {
    // Simplified query for monthly hours
    $monthly_sql = "
        SELECT 
            MONTH(shift_date) as month,
            SUM(
                CASE 
                    WHEN end_time >= start_time 
                    THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60.0
                    ELSE (TIMESTAMPDIFF(MINUTE, start_time, '23:59:59') + TIMESTAMPDIFF(MINUTE, '00:00:00', end_time)) / 60.0 + 1/60.0
                END
            ) as total_hours
        FROM shifts 
        WHERE YEAR(shift_date) = YEAR(CURDATE())
        AND status IN ('confirmed', 'completed')
        AND officer_id IS NOT NULL
    ";
    
    // Add company filter if needed
    if ($use_company_filter && $company_id) {
        $monthly_sql .= " AND company_id = ?";
        $stmt = $conn->prepare($monthly_sql . " GROUP BY MONTH(shift_date) ORDER BY MONTH(shift_date)");
        $stmt->execute([$company_id]);
    } else {
        $stmt = $conn->prepare($monthly_sql . " GROUP BY MONTH(shift_date) ORDER BY MONTH(shift_date)");
        $stmt->execute();
    }
    
    $monthly_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results into 12-month array
    foreach ($monthly_results as $row) {
        $month_index = (int)$row['month'] - 1; // Convert to 0-based index
        if ($month_index >= 0 && $month_index < 12) {
            $monthly_hours[$month_index] = round((float)$row['total_hours'], 1);
        }
    }
    
} catch (Exception $e) {
    error_log("Monthly hours query error: " . $e->getMessage());
    // monthly_hours already initialized to zeros above
}
?>

<!-- Dashboard Statistics -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase small fw-semibold mb-1">Total Shifts This Week</div>
                        <div class="h2 mb-0"><?php echo $stats['weekly_shifts'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-week fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase small fw-semibold mb-1">Active Sites</div>
                        <div class="h2 mb-0"><?php echo $stats['total_sites'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase small fw-semibold mb-1">Today's Shifts</div>
                        <div class="h2 mb-0"><?php echo $stats['todays_shifts'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase small fw-semibold mb-1">This Week's Hours</div>
                        <div class="h2 mb-0"><?php echo $stats['weekly_hours'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Recent Shifts -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Upcoming Shifts
                </h5>
            </div>
            <div class="card-body p-3">
                <?php if (empty($recent_shifts)): ?>
                    <?php if ($use_company_filter && $company_id && ($stats['total_sites'] ?? 0) == 0): ?>
                        <div class="text-center" style="padding: 40px 20px;">
                            <i class="fas fa-rocket" style="font-size: 48px; color: #667eea; margin-bottom: 20px;"></i>
                            <h4 style="color: #495057; margin-bottom: 15px;">Welcome to SecuriRota!</h4>
                            <p class="text-muted mb-4">Your company workspace is ready. Let's get you started by setting up your first site and adding officers.</p>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <a href="pages/sites.php?action=add" class="btn btn-primary">
                                    <i class="fas fa-building me-1"></i>Add First Site
                                </a>
                                <a href="pages/officers.php?action=add" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-1"></i>Add Officers
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted mb-0">No upcoming shifts scheduled.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="dashboardShiftsTable" class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Date & Time</th>
                                    <th>Officer</th>
                                    <th>Site</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" data-orderable="false">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_shifts as $shift): ?>
                                    <tr class="<?php 
                                        echo $shift['status'] === 'confirmed' ? 'table-success' : 
                                            ($shift['status'] === 'declined' ? 'table-danger' : 
                                            ($shift['status'] === 'cancelled' ? 'table-secondary' : 
                                            ($shift['status'] === 'allocated' ? 'table-warning' : 'table-light'))); 
                                    ?>"<?php echo $shift['status'] === 'cancelled' ? ' style="text-decoration: line-through; opacity: 0.7;"' : ''; ?>>
                                        <td class="text-nowrap small">
                                            <div><?php echo date('d/m', strtotime($shift['shift_date'])); ?></div>
                                            <small class="text-muted"><?php echo formatTime($shift['start_time']) . '-' . formatTime($shift['end_time']); ?></small>
                                        </td>
                                        <td class="small">
                                            <?php if ($shift['officer_id']): ?>
                                                <div class="text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']); ?>">
                                                    <?php echo htmlspecialchars($shift['first_name'] . ' ' . substr($shift['last_name'], 0, 1) . '.'); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Unallocated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($shift['site_name'] . ' - ' . $shift['client_name']); ?>">
                                                <?php echo htmlspecialchars($shift['site_name']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($shift['client_name']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm bg-<?php 
                                                echo $shift['status'] === 'confirmed' ? 'success' : 
                                                    ($shift['status'] === 'declined' ? 'danger' : 
                                                    ($shift['status'] === 'cancelled' ? 'secondary' : 
                                                    ($shift['status'] === 'allocated' ? 'warning' : 'secondary'))); 
                                            ?>" style="font-size: 0.7rem;">
                                                <?php echo ucfirst($shift['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="pages/site_rotas.php?site_id=<?php echo $shift['site_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               style="font-size: 0.7rem; padding: 2px 8px;"
                                               title="View <?php echo htmlspecialchars($shift['site_name']); ?> Rota">
                                                View Rota
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center py-2">
                <a href="pages/rota.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-calendar-alt me-1"></i>View Full Rota
                </a>
            </div>
        </div>
    </div>
    
    <!-- Alerts & Notifications -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Document Expiries (60 days)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($expiring_docs)): ?>
                    <p class="text-center text-muted">No documents expiring soon.</p>
                <?php else: ?>
                    <?php foreach ($expiring_docs as $doc): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="fw-bold"><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></div>
                            <div class="text-warning small">
                                <i class="fas fa-<?php 
                                    echo $doc['document_type'] === 'SIA Badge' ? 'id-card' : 
                                        ($doc['document_type'] === 'Visa' ? 'passport' : 
                                        ($doc['document_type'] === 'Passport' ? 'passport' : 
                                        ($doc['document_type'] === 'Brp Card' ? 'id-card' : 'file-alt'))); 
                                ?> me-1"></i>
                                <?php echo htmlspecialchars($doc['document_type']); ?> expires: <?php echo formatDate($doc['expiry_date']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <a href="pages/officers.php" class="btn btn-warning btn-sm">
                    <i class="fas fa-users me-2"></i>Manage Officers
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lightning-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="pages/officers.php?action=add" class="btn btn-primary w-100 p-3">
                            <i class="fas fa-user-plus d-block mb-2"></i>
                            <small>Add New Officer</small>
                        </a>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="pages/sites.php?action=add" class="btn btn-success w-100 p-3">
                            <i class="fas fa-building d-block mb-2"></i>
                            <small>Add New Site</small>
                        </a>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="pages/rota.php" class="btn btn-warning w-100 p-3">
                            <i class="fas fa-calendar-plus d-block mb-2"></i>
                            <small>Schedule Shifts</small>
                        </a>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="pages/reports.php" class="btn btn-info w-100 p-3">
                            <i class="fas fa-chart-bar d-block mb-2"></i>
                            <small>Generate Reports</small>
                        </a>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="pages/invoices.php" class="btn btn-secondary w-100 p-3">
                            <i class="fas fa-file-invoice d-block mb-2"></i>
                            <small>Create Invoices</small>
                        </a>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="pages/deployment.php" class="btn btn-dark w-100 p-3">
                            <i class="fas fa-clipboard-list d-block mb-2"></i>
                            <small>View Deployment</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Overview Chart -->
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Monthly Hours Overview
                </h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 400px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    console.log('Dashboard initialization started');
    
    // Initialize DataTables
    if ($('#dashboardShiftsTable').length) {
        $('#dashboardShiftsTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 20], [5, 10, 20]],
            order: [[0, 'asc']],
            dom: 'rtip',
            language: {
                info: "_START_ to _END_ of _TOTAL_ shifts",
                infoEmpty: "No shifts found",
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: 4 }
            ]
        });
        console.log('DataTables initialized');
    }
    
    // Initialize Monthly Hours Chart
    initMonthlyChart();
});

function initMonthlyChart() {
    console.log('Starting chart initialization...');
    
    // Check for Chart.js
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not found');
        $('#monthlyChart').parent().html('<div class="alert alert-warning">Chart library not loaded. Please refresh the page.</div>');
        return;
    }
    
    // Get canvas element
    const canvas = document.getElementById('monthlyChart');
    if (!canvas) {
        console.error('Chart canvas not found');
        return;
    }
    
    // Get monthly data from PHP
    const monthlyData = <?php echo json_encode($monthly_hours); ?>;
    console.log('Monthly hours data:', monthlyData);
    
    // Verify data is valid
    if (!Array.isArray(monthlyData) || monthlyData.length !== 12) {
        console.error('Invalid monthly data format');
        return;
    }
    
    // Create chart
    try {
        const ctx = canvas.getContext('2d');
        const monthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Monthly Hours',
                    data: monthlyData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Hours: ' + context.parsed.y.toFixed(1);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    },
                    y: {
                        display: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
        
        console.log('Monthly hours chart created successfully');
        
    } catch (error) {
        console.error('Error creating chart:', error);
        $('#monthlyChart').parent().html('<div class="alert alert-danger">Error creating chart: ' + error.message + '</div>');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
