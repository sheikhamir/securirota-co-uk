<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Site Management';

function getSiteManagementReturnUrl($url = null) {
    $url = trim((string)$url);

    if ($url === '') {
        return 'sites.php';
    }

    $parts = parse_url($url);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || strpos($url, '//') === 0 || preg_match('/[\r\n]/', $url)) {
        return 'sites.php';
    }

    return $url;
}

$return_url = getSiteManagementReturnUrl($_GET['return_url'] ?? $_POST['return_url'] ?? 'sites.php');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering
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
    
    // Handle form submissions
    if ($_POST) {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Build INSERT query with company_id for multi-tenant support
                    if ($use_company_filter && $company_id) {
                        $stmt = $conn->prepare("
                            INSERT INTO sites (client_id, site_name, address, contact_person, contact_phone, site_instructions, client_rate, company_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $_POST['client_id'], $_POST['site_name'], $_POST['address'],
                            $_POST['contact_person'], $_POST['contact_phone'], $_POST['site_instructions'],
                            !empty($_POST['client_rate']) ? $_POST['client_rate'] : null,
                            $company_id
                        ]);
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO sites (client_id, site_name, address, contact_person, contact_phone, site_instructions, client_rate) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $_POST['client_id'], $_POST['site_name'], $_POST['address'],
                            $_POST['contact_person'], $_POST['contact_phone'], $_POST['site_instructions'],
                            !empty($_POST['client_rate']) ? $_POST['client_rate'] : null
                        ]);
                    }
                    
                    $success = "Site added successfully!";
                    break;
                    
                case 'update':
                    // Build UPDATE query with security check for multi-tenant support
                    if ($use_company_filter && $company_id) {
                        $stmt = $conn->prepare("
                            UPDATE sites SET 
                                client_id = ?, site_name = ?, address = ?, contact_person = ?, 
                                contact_phone = ?, site_instructions = ?, status = ?, client_rate = ?
                            WHERE id = ? AND company_id = ?
                        ");
                        
                        $stmt->execute([
                            $_POST['client_id'], $_POST['site_name'], $_POST['address'],
                            $_POST['contact_person'], $_POST['contact_phone'], $_POST['site_instructions'], 
                            $_POST['status'], 
                            !empty($_POST['client_rate']) ? $_POST['client_rate'] : null,
                            $_POST['id'],
                            $company_id
                        ]);
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE sites SET 
                                client_id = ?, site_name = ?, address = ?, contact_person = ?, 
                                contact_phone = ?, site_instructions = ?, status = ?, client_rate = ?
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([
                            $_POST['client_id'], $_POST['site_name'], $_POST['address'],
                            $_POST['contact_person'], $_POST['contact_phone'], $_POST['site_instructions'], 
                            $_POST['status'], 
                            !empty($_POST['client_rate']) ? $_POST['client_rate'] : null,
                            $_POST['id']
                        ]);
                    }
                    
                    header('Location: ' . getSiteManagementReturnUrl($_POST['return_url'] ?? 'sites.php'));
                    exit();
            }
        }
    }
    
    // Get sites list with shift statistics
    $current_week_start = date('Y-m-d', strtotime('monday this week'));
    $current_week_end = date('Y-m-d', strtotime('sunday this week'));
    
    $sql = "
        SELECT s.*, c.company_name as client_name, 
               c.billing_rate as client_default_rate,
               COALESCE(s.client_rate, c.billing_rate, 0.00) as effective_client_rate,
               CASE 
                   WHEN s.client_rate IS NOT NULL THEN 'Site Override'
                   WHEN c.billing_rate IS NOT NULL THEN 'Client Default'
                   ELSE 'None Set'
               END as rate_source,
               COUNT(sh.id) as total_shifts_this_week,
               COUNT(CASE WHEN sh.officer_id IS NULL THEN 1 END) as unallocated_shifts_this_week,
               COUNT(CASE WHEN sh.status = 'confirmed' THEN 1 END) as confirmed_shifts_this_week
        FROM sites s
        JOIN clients c ON s.client_id = c.id
        LEFT JOIN shifts sh ON s.id = sh.site_id 
            AND sh.shift_date BETWEEN '$current_week_start' AND '$current_week_end'";
    
    if ($use_company_filter && $company_id) {
        $sql .= " WHERE s.company_id = ?";
    }
    
    $sql .= " GROUP BY s.id, s.site_name, c.company_name, c.billing_rate, s.client_rate
              ORDER BY c.company_name, s.site_name";
    
    $stmt = $conn->prepare($sql);
    
    if ($use_company_filter && $company_id) {
        $stmt->execute([$company_id]);
    } else {
        $stmt->execute();
    }
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate stats
    $total_sites = count($sites);
    $active_sites = count(array_filter($sites, function($s) { return $s['status'] === 'active'; }));
    $total_shifts_week = array_sum(array_column($sites, 'total_shifts_this_week'));
    $total_unallocated = array_sum(array_column($sites, 'unallocated_shifts_this_week'));
    
    // Get clients for dropdown in add/edit forms
    if ($use_company_filter && $company_id) {
        $clients_stmt = $conn->prepare("SELECT id, company_name as name FROM clients WHERE company_id = ? ORDER BY company_name");
        $clients_stmt->execute([$company_id]);
    } else {
        $clients_stmt = $conn->query("SELECT id, company_name as name FROM clients ORDER BY company_name");
    }
    $clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get single site for editing with company security check
    $edit_site = null;
    if (isset($_GET['edit'])) {
        if ($use_company_filter && $company_id) {
            $stmt = $conn->prepare("SELECT * FROM sites WHERE id = ? AND company_id = ?");
            $stmt->execute([$_GET['edit'], $company_id]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$_GET['edit']]);
        }
        $edit_site = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

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

<!-- Site Form -->
<?php if (isset($_GET['action']) && $_GET['action'] === 'add' || isset($_GET['edit'])): ?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-building me-2"></i>
            <?php echo isset($_GET['edit']) ? 'Edit Site' : 'Add New Site'; ?>
        </h5>
    </div>
    
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'update' : 'add'; ?>">
            <?php if (isset($_GET['edit'])): ?>
                <input type="hidden" name="id" value="<?php echo $_GET['edit']; ?>">
            <?php endif; ?>
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($return_url); ?>">
            
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Site Information</h6>
                    
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Client *</label>
                        <select name="client_id" id="client_id" class="form-select" required>
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>" 
                                        <?php echo ($edit_site['client_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name *</label>
                        <input type="text" name="site_name" id="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_site['site_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3"><?php echo htmlspecialchars($edit_site['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if (isset($_GET['edit'])): ?>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active" <?php echo ($edit_site['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_site['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Contact Information</h6>
                    
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="contact_person" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_site['contact_person'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" name="contact_phone" id="contact_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_site['contact_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_instructions" class="form-label">Site Instructions</label>
                        <textarea name="site_instructions" id="site_instructions" class="form-control" rows="5" 
                                  placeholder="Special instructions, security requirements, access codes, etc."><?php echo htmlspecialchars($edit_site['site_instructions'] ?? ''); ?></textarea>
                    </div>
                    
                    <h6 class="text-primary mb-3 mt-4">Client Rate Settings</h6>
                    <div class="mb-3">
                        <label for="client_rate" class="form-label">Site Client Rate (£/hour)</label>
                        <input type="number" step="0.01" min="0" name="client_rate" id="client_rate" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_site['client_rate'] ?? ''); ?>" 
                               placeholder="Leave empty to use client default rate">
                        <small class="form-text text-muted">Override client's default billing rate for this site. Officer rates are managed individually in the Officers section.</small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i><?php echo isset($_GET['edit']) ? 'Update Site' : 'Add Site'; ?>
                </button>
                <a href="<?php echo htmlspecialchars($return_url); ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<!-- Sites List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-building me-2"></i>Sites List
        </h5>
        <a href="sites.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Site
        </a>
    </div>
    
    <!-- Quick Stats -->
    <?php if (!empty($sites)): ?>
        <?php 
        $total_sites = count($sites);
        $active_sites = count(array_filter($sites, function($s) { return $s['status'] === 'active'; }));
        $total_shifts_week = array_sum(array_column($sites, 'total_shifts_this_week'));
        $total_unallocated = array_sum(array_column($sites, 'unallocated_shifts_this_week'));
        ?>
        <div class="card-body border-bottom bg-light">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="h4 mb-0 text-primary"><?php echo $total_sites; ?></div>
                    <small class="text-muted">Total Sites</small>
                </div>
                <div class="col-md-3">
                    <div class="h4 mb-0 text-success"><?php echo $active_sites; ?></div>
                    <small class="text-muted">Active Sites</small>
                </div>
                <div class="col-md-3">
                    <div class="h4 mb-0 text-info"><?php echo $total_shifts_week; ?></div>
                    <small class="text-muted">Shifts This Week</small>
                </div>
                <div class="col-md-3">
                    <div class="h4 mb-0 text-warning"><?php echo $total_unallocated; ?></div>
                    <small class="text-muted">Unallocated</small>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card-body p-0">
        <?php if (empty($sites)): ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-building fa-3x text-muted"></i>
                </div>
                <p class="text-muted mb-3">No sites found.</p>
                <a href="sites.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add First Site
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="sitesTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Site Name</th>
                            <th>Client</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th width="160" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $site): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($site['site_name']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($site['client_name']); ?></div>
                                    <?php if ($site['effective_client_rate'] > 0): ?>
                                        <small class="text-muted">
                                            Rate: £<?php echo number_format($site['effective_client_rate'], 2); ?>/hr
                                            <span class="badge badge-sm bg-<?php echo $site['rate_source'] === 'Site Override' ? 'success' : ($site['rate_source'] === 'Client Default' ? 'info' : 'secondary'); ?>">
                                                <?php echo $site['rate_source']; ?>
                                            </span>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-warning">No rate set</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($site['address']): ?>
                                        <small><?php echo nl2br(htmlspecialchars($site['address'])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">No address</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($site['contact_person']): ?>
                                        <div class="small fw-semibold"><?php echo htmlspecialchars($site['contact_person']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($site['contact_phone']): ?>
                                        <div class="small text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($site['contact_phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $site['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($site['status']); ?>
                                    </span>
                                    
                                    <?php if ($site['status'] === 'active' && $site['total_shifts_this_week'] > 0): ?>
                                        <div class="mt-1">
                                            <small class="text-muted">This Week:</small><br>
                                            <span class="badge bg-primary"><?php echo $site['total_shifts_this_week']; ?> shifts</span>
                                            <?php if ($site['unallocated_shifts_this_week'] > 0): ?>
                                                <br/><span class="badge bg-warning"><?php echo $site['unallocated_shifts_this_week']; ?> unallocated</span>
                                            <?php endif; ?>
                                            <?php if ($site['confirmed_shifts_this_week'] > 0): ?>
                                                <br/><span class="badge bg-success"><?php echo $site['confirmed_shifts_this_week']; ?> confirmed</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($site['status'] === 'active'): ?>
                                        <div class="mt-1">
                                            <small class="text-muted">No shifts this week</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Site Actions">
                                        <a href="sites.php?edit=<?php echo $site['id']; ?>&return_url=<?php echo rawurlencode($_SERVER['REQUEST_URI'] ?? 'sites.php'); ?>"
                                           class="btn btn-warning btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Edit Site Details">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="site_detail.php?id=<?php echo $site['id']; ?>" 
                                           class="btn btn-info btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="View Site Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="site_rotas.php?site_id=<?php echo $site['id']; ?>" 
                                           class="btn btn-success btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="View Site Rota">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <a href="rota.php?site_filter=<?php echo $site['id']; ?>" 
                                           class="btn btn-primary btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="View Officers (Site Filtered)">
                                            <i class="fas fa-users"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

<script>
// Initialize DataTables for Sites
$(document).ready(function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    $('#sitesTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'asc']], // Sort by site name by default
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
            search: "Search sites:",
            lengthMenu: "Show _MENU_ sites per page",
            info: "Showing _START_ to _END_ of _TOTAL_ sites",
            infoEmpty: "No sites found",
            infoFiltered: "(filtered from _MAX_ total sites)"
        },
        columnDefs: [
            { orderable: false, targets: 5 } // Actions column
        ]
    });
});
</script>
