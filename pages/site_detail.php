<?php
session_start();
require_once '../config/config.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Initialize company filtering for security
$use_company_filter = false;
$company_id = null;

// Check if we're in multi-tenant mode (post-migration)
try {
    $column_check = $conn->query("SHOW COLUMNS FROM sites LIKE 'company_id'");
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

// Get site ID from URL
$site_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$site_id) {
    header('Location: sites.php');
    exit();
}

// Get site details with client information - WITH COMPANY SECURITY CHECK
$sql = "
    SELECT s.*, c.company_name, c.contact_person, c.email as client_email, c.phone as client_phone
    FROM sites s 
    JOIN clients c ON s.client_id = c.id 
    WHERE s.id = ?";

$params = [$site_id];

// SECURITY: Add company filtering to prevent cross-company data access
if ($use_company_filter && $company_id) {
    $sql .= " AND s.company_id = ?";
    $params[] = $company_id;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: sites.php');
    exit();
}

// Get recent shifts for this site - WITH COMPANY SECURITY CHECK
$shifts_sql = "
    SELECT s.*, o.first_name, o.last_name, r.name as role_name 
    FROM shifts s 
    LEFT JOIN officers o ON s.officer_id = o.id 
    LEFT JOIN roles r ON s.role_id = r.id
    WHERE s.site_id = ?";

$shifts_params = [$site_id];

if ($use_company_filter && $company_id) {
    $shifts_sql .= " AND s.company_id = ?";
    $shifts_params[] = $company_id;
}

$shifts_sql .= " ORDER BY s.shift_date DESC, s.start_time DESC LIMIT 10";

$stmt = $conn->prepare($shifts_sql);
$stmt->execute($shifts_params);
$recent_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get shift statistics - WITH COMPANY SECURITY CHECK
$stats_sql = "
    SELECT 
        COUNT(*) as total_shifts,
        COUNT(officer_id) as allocated_shifts,
        COUNT(*) - COUNT(officer_id) as unallocated_shifts
    FROM shifts 
    WHERE site_id = ? AND shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$stats_params = [$site_id];

if ($use_company_filter && $company_id) {
    $stats_sql .= " AND company_id = ?";
    $stats_params[] = $company_id;
}

$stmt = $conn->prepare($stats_sql);
$stmt->execute($stats_params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Site Details - ' . $site['site_name'];
$currentPage = 'sites';

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-building"></i> Site Details
                </h1>
                <div>
                    <a href="sites.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Sites
                    </a>
                    <a href="sites.php?edit=<?php echo $site['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Site
                    </a>
                    <a href="rota.php?site_filter=<?php echo $site['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> View Schedule
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Site Information Card -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Site Information</h6>
                            <span class="badge badge-<?php echo $site['status'] == 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($site['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary"><?php echo htmlspecialchars($site['site_name']); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($site['company_name']); ?></p>
                                    
                                    <?php if (!empty($site['address'])): ?>
                                    <div class="mb-3">
                                        <strong>Address:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($site['address'])); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($site['site_instructions'])): ?>
                                    <div class="mb-3">
                                        <strong>Site Instructions:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($site['site_instructions'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="text-secondary">Client Contact Information</h6>
                                    <div class="mb-2">
                                        <strong>Contact Name:</strong> <?php echo htmlspecialchars($site['contact_person']); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Email:</strong> 
                                        <a href="mailto:<?php echo htmlspecialchars($site['client_email']); ?>">
                                            <?php echo htmlspecialchars($site['client_email']); ?>
                                        </a>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Phone:</strong> 
                                        <a href="tel:<?php echo htmlspecialchars($site['client_phone']); ?>">
                                            <?php echo htmlspecialchars($site['client_phone']); ?>
                                        </a>
                                    </div>
                                    
                                    <?php if (!empty($site['site_instructions'])): ?>
                                    <div class="mt-3">
                                        <strong>Special Instructions:</strong><br>
                                        <div class="alert alert-info">
                                            <?php echo nl2br(htmlspecialchars($site['site_instructions'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Statistics (Last 30 Days)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row no-gutters align-items-center mb-3">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Shifts
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_shifts']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            
                            <div class="row no-gutters align-items-center mb-3">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Allocated Shifts
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['allocated_shifts']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Unallocated Shifts
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['unallocated_shifts']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Shifts -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Shifts</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_shifts)): ?>
                        <p class="text-muted">No shifts found for this site.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="siteShiftsTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Officer</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_shifts as $shift): ?>
                                    <tr>
                                        <td><?php echo date('D, M j, Y', strtotime($shift['shift_date'])); ?></td>
                                        <td>
                                            <?php echo date('H:i', strtotime($shift['start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($shift['end_time'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($shift['officer_id']): ?>
                                                <?php echo htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unallocated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($shift['role_name'] ?? $shift['role'] ?? 'Unknown Role'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $shift['status'] == 'completed' ? 'success' : 
                                                    ($shift['status'] == 'in_progress' ? 'info' :
                                                    ($shift['status'] == 'confirmed' ? 'primary' : 
                                                    ($shift['status'] == 'allocated' ? 'warning' : 
                                                    ($shift['status'] == 'declined' ? 'danger' : 'secondary')))); 
                                            ?>">
                                                <?php echo ucfirst($shift['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTables for Site Shifts
$(document).ready(function() {
    $('#siteShiftsTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        order: [[0, 'desc']], // Sort by date descending
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "Search shifts:",
            lengthMenu: "Show _MENU_ shifts per page",
            info: "Showing _START_ to _END_ of _TOTAL_ shifts",
            infoEmpty: "No shifts found",
            infoFiltered: "(filtered from _MAX_ total shifts)"
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
