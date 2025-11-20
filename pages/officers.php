<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Officer Management';
require_once '../includes/header.php';
require_once '../includes/email_helper.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM officers LIKE 'company_id'");
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
    
    // Handle PIN generation
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'generate_pin') {
        $new_pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Get officer details for email
        $stmt = $conn->prepare("
            SELECT o.first_name, o.last_name, o.email, u.mobile_number
            FROM officers o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$_POST['officer_id']]);
        $officer_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("
            UPDATE users u 
            JOIN officers o ON u.id = o.user_id 
            SET u.pin = ?, u.pin_generated_at = NOW(), u.password = ?
            WHERE o.id = ?
        ");
        $hashed_password = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt->execute([$new_pin, $hashed_password, $_POST['officer_id']]);
        
        // Send PIN email
        if ($officer_info && $officer_info['email']) {
            $officer_name = $officer_info['first_name'] . ' ' . $officer_info['last_name'];
            $email_sent = sendPINEmail(
                $officer_info['email'], 
                $officer_name, 
                $officer_info['mobile_number'], 
                $new_pin
            );
            
            if ($email_sent) {
                $success = "New PIN generated: $new_pin - Email sent to " . $officer_info['email'];
            } else {
                $success = "New PIN generated: $new_pin - Warning: Email could not be sent to " . $officer_info['email'];
            }
        } else {
            $success = "New PIN generated: $new_pin - Warning: No email address found for this officer";
        }
    }
    
    // Handle delete
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $conn->beginTransaction();
        
        try {
            // Get the user_id before deleting the officer
            $stmt = $conn->prepare("SELECT user_id FROM officers WHERE id = ?");
            $stmt->execute([$_POST['officer_id']]);
            $officer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete the officer record
            $stmt = $conn->prepare("DELETE FROM officers WHERE id = ?");
            $stmt->execute([$_POST['officer_id']]);
            
            // Delete the linked user account if it exists
            if ($officer && $officer['user_id']) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$officer['user_id']]);
            }
            
            $conn->commit();
            $success = "Officer deleted successfully!";
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    // Get all officers with their linked user information and shift counts
    $sql = "SELECT o.*, u.username, u.email as user_email, u.status as user_status, u.mobile_number, u.pin,
                   s.name as subcontractor_name,
                   COALESCE(shift_stats.confirmed_shifts, 0) as confirmed_shifts,
                   COALESCE(shift_stats.completed_shifts, 0) as completed_shifts,
                   COALESCE(shift_stats.allocated_shifts, 0) as allocated_shifts,
                   COALESCE(shift_stats.declined_shifts, 0) as declined_shifts,
                   COALESCE(shift_stats.total_shifts, 0) as total_shifts
            FROM officers o 
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN subcontractors s ON o.subcontractor_id = s.id
            LEFT JOIN (
                SELECT officer_id,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_shifts,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_shifts,
                    SUM(CASE WHEN status = 'allocated' THEN 1 ELSE 0 END) as allocated_shifts,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_shifts,
                    COUNT(*) as total_shifts
                FROM shifts 
                WHERE officer_id IS NOT NULL";
    
    if ($use_company_filter && $company_id) {
        $sql .= " AND company_id = ?";
    }
    
    $sql .= " GROUP BY officer_id
            ) shift_stats ON o.id = shift_stats.officer_id";
    
    if ($use_company_filter && $company_id) {
        $sql .= " WHERE o.company_id = ?";
    }
    
    $sql .= " ORDER BY o.first_name, o.last_name";
    
    $stmt = $conn->prepare($sql);
    
    if ($use_company_filter && $company_id) {
        $stmt->execute([$company_id, $company_id]); // Two parameters: one for shifts subquery, one for officers WHERE
    } else {
        $stmt->execute();
    }
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-users me-2 text-primary"></i>Officer Management
        </h4>
        <a href="officer_form.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Add Officer
        </a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Compact Officers Table -->
    <div class="card">
        <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Officers Management</h6>
                <small class="text-muted">Click name to view/edit details</small>
            </div>
        </div>
        <div class="table-responsive">
            <table id="officersTable" class="table table-hover table-sm mb-0" style="font-size: 0.9em;">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 70px;">Staff ID</th>
                        <th>Name & Details</th>
                        <th>Contact</th>
                        <th>Employment</th>
                        <th>SIA & Visa</th>
                        <th>Rate</th>
                        <th>Shifts</th>
                        <th data-orderable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($officers as $officer): ?>
                        <tr class="<?php echo $officer['suspend'] ? 'table-warning' : ''; ?>" style="vertical-align: middle;">
                            <td class="text-center">
                                <span class="badge bg-primary" style="font-size: 0.7em;"><?php echo safe_html($officer['staff_id']); ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 32px; height: 32px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8em;">
                                        <?php echo strtoupper(substr($officer['first_name'], 0, 1) . substr($officer['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div>
                                            <a href="officer_form.php?id=<?php echo $officer['id']; ?>" 
                                               class="text-decoration-none fw-bold text-dark">
                                                <?php echo safe_html($officer['first_name'] . ' ' . $officer['last_name']); ?>
                                            </a>
                                            <?php if ($officer['suspend']): ?>
                                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6em;">SUSPENDED</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($officer['subcontractor_name'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-building" style="width: 12px;"></i> <?php echo safe_html($officer['subcontractor_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($officer['nationality']) && $officer['nationality'] !== 'British'): ?>
                                            <small class="text-info d-block">
                                                <i class="fas fa-globe" style="width: 12px;"></i> <?php echo safe_html($officer['nationality']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="fas fa-mobile-alt text-success" style="width: 12px;"></i> <strong><?php echo str_replace(' ', '', safe_html($officer['mobile_number'])); ?></strong></div>
                                    <?php if (!empty($officer['pin'])): ?>
                                        <div class="text-muted"><i class="fas fa-key" style="width: 12px;"></i> PIN: <?php echo safe_html($officer['pin']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-truncate" style="max-width: 120px;" title="<?php echo safe_html($officer['email']); ?>">
                                        <i class="fas fa-envelope text-primary" style="width: 12px;"></i> <?php echo safe_html($officer['email']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-<?php echo $officer['employment_status'] === 'Full-time' ? 'success' : ($officer['employment_status'] === 'Part-time' ? 'info' : 'secondary'); ?>" style="font-size: 0.65em;">
                                        <?php echo safe_html($officer['employment_status']); ?>
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <?php if ($officer['user_status'] === 'active'): ?>
                                        <span class="badge bg-success" style="font-size: 0.6em;">
                                            <i class="fas fa-check"></i> Login OK
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger" style="font-size: 0.6em;">
                                            <i class="fas fa-times"></i> No Login
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($officer['date_started']): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-calendar" style="width: 12px;"></i> <?php echo date('M Y', strtotime($officer['date_started'])); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <?php if (!empty($officer['sia_badge_number'])): ?>
                                        <div class="mb-1">
                                            <strong class="text-success">
                                                <i class="fas fa-id-badge"></i> SIA: <?php echo safe_html(substr($officer['sia_badge_number'], -6)); ?>
                                            </strong>
                                            <?php if ($officer['sia_expiry_date']): ?>
                                                <div class="text-<?php echo strtotime($officer['sia_expiry_date']) < strtotime('+30 days') ? 'danger' : 'muted'; ?>">
                                                    <i class="fas fa-calendar-alt" style="width: 12px;"></i> <?php echo date('m/y', strtotime($officer['sia_expiry_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted mb-1">
                                            <i class="fas fa-id-badge"></i> No SIA
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($officer['visa_status']) && $officer['visa_status'] !== 'British'): ?>
                                        <div>
                                            <span class="badge bg-info" style="font-size: 0.6em;"><?php echo safe_html($officer['visa_status']); ?></span>
                                            <?php if ($officer['visa_expiry_date']): ?>
                                                <div class="text-<?php echo strtotime($officer['visa_expiry_date']) < strtotime('+60 days') ? 'danger' : 'muted'; ?>" style="font-size: 0.7em;">
                                                    <i class="fas fa-calendar-alt" style="width: 12px;"></i> <?php echo date('m/y', strtotime($officer['visa_expiry_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div>
                                    <strong class="text-success">£<?php echo number_format($officer['hourly_rate'] ?? 0, 2); ?></strong>
                                </div>
                                <?php if (!empty($officer['bank_account']) && !empty($officer['sort_code'])): ?>
                                    <small class="text-success"><i class="fas fa-check" title="Bank details complete"></i> Bank ✓</small>
                                <?php else: ?>
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle" title="Bank details missing"></i> Bank ⚠️</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="small">
                                    <?php if ($officer['total_shifts'] > 0): ?>
                                        <div class="mb-1">
                                            <span class="badge bg-success me-1" style="font-size: 0.65em;" title="Confirmed Shifts">
                                                <i class="fas fa-check"></i> <?php echo $officer['confirmed_shifts']; ?>
                                            </span>
                                            <span class="badge bg-info me-1" style="font-size: 0.65em;" title="Completed Shifts">
                                                <i class="fas fa-check-double"></i> <?php echo $officer['completed_shifts']; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge bg-warning me-1" style="font-size: 0.65em;" title="Allocated (Unconfirmed)">
                                                <i class="fas fa-clock"></i> <?php echo $officer['allocated_shifts']; ?>
                                            </span>
                                            <span class="badge bg-danger me-1" style="font-size: 0.65em;" title="Declined/Rejected">
                                                <i class="fas fa-times"></i> <?php echo $officer['declined_shifts']; ?>
                                            </span>
                                        </div>
                                        <small class="text-muted mt-1 d-block">Total: <?php echo $officer['total_shifts']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">No shifts</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="officer_detail.php?id=<?php echo $officer['id']; ?>" 
                                       class="btn btn-outline-info" title="View Officer Details" style="font-size: 0.75em;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="officer_form.php?id=<?php echo $officer['id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit Officer" style="font-size: 0.75em;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-outline-info" 
                                            onclick="generatePIN(<?php echo $officer['id']; ?>)" title="Generate PIN" style="font-size: 0.75em;">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteOfficer(<?php echo $officer['id']; ?>)" title="Delete Officer" style="font-size: 0.75em;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($officers)): ?>
            <div class="card-body text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-3">No officers found.</p>
                <a href="officer_form.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add Your First Officer
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize DataTables for Officers
$(document).ready(function() {
    $('#officersTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[1, 'asc']], // Sort by name by default
        dom: '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4 text-center"B><"col-sm-12 col-md-4"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv"></i> CSV',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-primary btn-sm'
            }
        ],
        language: {
            search: "Search officers:",
            lengthMenu: "Show _MENU_ officers per page",
            info: "Showing _START_ to _END_ of _TOTAL_ officers",
            infoEmpty: "No officers found",
            infoFiltered: "(filtered from _MAX_ total officers)"
        },
        columnDefs: [
            { orderable: false, targets: 7 } // Actions column
        ]
    });
});

function generatePIN(id) {
    if (confirm('Generate a new PIN for this officer? This will invalidate their current PIN.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="generate_pin">
            <input type="hidden" name="officer_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteOfficer(id) {
    if (confirm('Are you sure you want to delete this officer? This action cannot be undone and will also delete their user account.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="officer_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
