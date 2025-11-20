<?php
// Check authentication and permissions first, before any output
session_start();
require_once '../config/config.php';

// Check if user has permission to access this page
if (!hasRole('admin') && !hasRole('manager')) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=access_denied');
    exit();
}

$page_title = 'Client Management';
require_once '../includes/header.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM clients LIKE 'company_id'");
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
                    // Build INSERT query with or without company_id
                    if ($use_company_filter && $company_id) {
                        $stmt = $conn->prepare("
                            INSERT INTO clients (company_name, contact_person, email, phone, address, billing_rate, company_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $_POST['company_name'], $_POST['contact_person'], $_POST['email'], 
                            $_POST['phone'], $_POST['address'], $_POST['billing_rate'] ?: null, $company_id
                        ]);
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO clients (company_name, contact_person, email, phone, address, billing_rate) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $_POST['company_name'], $_POST['contact_person'], $_POST['email'], 
                            $_POST['phone'], $_POST['address'], $_POST['billing_rate'] ?: null
                        ]);
                    }
                    
                    $success = "Client added successfully!";
                    break;
                    
                case 'update':
                    // SECURITY: Only update clients that belong to the user's company
                    $update_sql = "
                        UPDATE clients SET 
                            company_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, billing_rate = ?
                        WHERE id = ?";
                    
                    $update_params = [
                        $_POST['company_name'], $_POST['contact_person'], $_POST['email'], 
                        $_POST['phone'], $_POST['address'], $_POST['billing_rate'] ?: null, $_POST['id']
                    ];
                    
                    if ($use_company_filter && $company_id) {
                        $update_sql .= " AND company_id = ?";
                        $update_params[] = $company_id;
                    }
                    
                    $stmt = $conn->prepare($update_sql);
                    $stmt->execute($update_params);
                    
                    $success = "Client updated successfully!";
                    break;
            }
        }
    }
    
    // Get clients list - WITH COMPANY FILTERING
    $sql = "SELECT * FROM clients";
    $params = [];
    
    if ($use_company_filter && $company_id) {
        $sql .= " WHERE company_id = ?";
        $params[] = $company_id;
    }
    
    $sql .= " ORDER BY company_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get single client for editing
    $edit_client = null;
    if (isset($_GET['edit'])) {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_client = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Client Form -->
<?php if (isset($_GET['action']) && $_GET['action'] === 'add' || isset($_GET['edit'])): ?>
<div class="card">
    <div class="card-header">
        <h3>
            <i class="fas fa-handshake"></i> 
            <?php echo isset($_GET['edit']) ? 'Edit Client' : 'Add New Client'; ?>
        </h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'update' : 'add'; ?>">
        <?php if (isset($_GET['edit'])): ?>
            <input type="hidden" name="id" value="<?php echo $_GET['edit']; ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div class="form-section">
                <h4 style="color: #667eea; margin-bottom: 15px;">Client Information</h4>
                
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" class="form-control" 
                           value="<?php echo htmlspecialchars($edit_client['company_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" 
                           value="<?php echo htmlspecialchars($edit_client['contact_person'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($edit_client['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($edit_client['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Billing Rate (£/hour)</label>
                    <input type="number" step="0.01" name="billing_rate" class="form-control" 
                           value="<?php echo $edit_client['billing_rate'] ?? ''; ?>" 
                           placeholder="e.g. 15.00">
                </div>
            </div>
            
            <div class="form-section">
                <h4 style="color: #667eea; margin-bottom: 15px;">Address</h4>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="5" 
                              placeholder="Full business address"><?php echo htmlspecialchars($edit_client['address'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-10 mt-20">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo isset($_GET['edit']) ? 'Update Client' : 'Add Client'; ?>
            </button>
            <a href="clients.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php else: ?>

<!-- Clients List -->
<div class="card">
    <div class="card-header d-flex justify-between align-center">
        <h3><i class="fas fa-handshake"></i> Clients Management</h3>
        <a href="clients.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Client
        </a>
    </div>
    
    <?php if (empty($clients)): ?>
        <div class="text-center p-20">
            <p class="text-muted">No clients found.</p>
            <a href="clients.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add First Client
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table id="clientsTable">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Contact Details</th>
                        <th>Billing Rate</th>
                        <th>Sites</th>
                        <th data-orderable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <?php
                        // Get site count for this client
                        $site_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM sites WHERE client_id = ?");
                        $site_count_stmt->execute([$client['id']]);
                        $site_count = $site_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($client['company_name']); ?></strong>
                                <?php if ($client['address']): ?>
                                    <br><small class="text-muted"><?php echo nl2br(htmlspecialchars(substr($client['address'], 0, 50))); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['contact_person']): ?>
                                    <?php echo htmlspecialchars($client['contact_person']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No contact person</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['phone']): ?>
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['phone']); ?></div>
                                <?php endif; ?>
                                <?php if ($client['email']): ?>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></div>
                                <?php endif; ?>
                                <?php if (!$client['phone'] && !$client['email']): ?>
                                    <span class="text-muted">No contact details</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['billing_rate']): ?>
                                    <span class="badge badge-success"><?php echo formatCurrency($client['billing_rate']); ?>/hr</span>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $site_count; ?> sites</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="client_detail.php?id=<?php echo $client['id']; ?>" 
                                       class="btn btn-outline-success" title="View Details" style="font-size: 0.75em;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="clients.php?edit=<?php echo $client['id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit Client" style="font-size: 0.75em;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="sites.php?client_id=<?php echo $client['id']; ?>" 
                                       class="btn btn-outline-info" title="View Sites" style="font-size: 0.75em;">
                                        <i class="fas fa-building"></i>
                                    </a>
                                    <a href="reports.php?client_id=<?php echo $client['id']; ?>" 
                                       class="btn btn-outline-secondary" title="View Reports" style="font-size: 0.75em;">
                                        <i class="fas fa-chart-bar"></i>
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

<script>
// Initialize DataTables for Clients
$(document).ready(function() {
    $('#clientsTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'asc']], // Sort by company name by default
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
            search: "Search clients:",
            lengthMenu: "Show _MENU_ clients per page",
            info: "Showing _START_ to _END_ of _TOTAL_ clients",
            infoEmpty: "No clients found",
            infoFiltered: "(filtered from _MAX_ total clients)"
        },
        columnDefs: [
            { orderable: false, targets: 5 } // Actions column
        ]
    });
});
</script>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
