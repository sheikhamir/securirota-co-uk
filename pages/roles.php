<?php
// Start output buffering to handle any unexpected output
ob_start();

// Check if user is admin first (before any output)
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';

if (!isAdmin()) {
    header('Location: ../dashboard.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering for security
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM roles LIKE 'company_id'");
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $sql = "INSERT INTO roles (name, description, company_id) VALUES (?, ?, ?)";
                    $params = [$_POST['name'], $_POST['description']];
                    
                    // SECURITY: Add company filtering for new roles
                    if ($use_company_filter && $company_id) {
                        $params[] = $company_id;
                    } else {
                        $params[] = 1; // Default to company 1 if no filtering
                    }
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $_SESSION['success_message'] = 'Role created successfully!';
                    break;
                    
                case 'update':
                    $sql = "UPDATE roles SET name = ?, description = ?, is_active = ? WHERE id = ?";
                    $params = [$_POST['name'], $_POST['description'], isset($_POST['is_active']) ? 1 : 0, $_POST['role_id']];
                    
                    // SECURITY: Add company filtering to prevent cross-company updates
                    if ($use_company_filter && $company_id) {
                        $sql .= " AND company_id = ?";
                        $params[] = $company_id;
                    }
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $_SESSION['success_message'] = 'Role updated successfully!';
                    break;
                    
                case 'delete':
                    // Check if role is being used in shifts - WITH COMPANY FILTERING
                    $check_sql = "SELECT COUNT(*) as count FROM shifts WHERE role_id = ?";
                    $check_params = [$_POST['role_id']];
                    
                    if ($use_company_filter && $company_id) {
                        $check_sql .= " AND company_id = ?";
                        $check_params[] = $company_id;
                    }
                    
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->execute($check_params);
                    $usage_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($usage_count > 0) {
                        $_SESSION['error_message'] = "Cannot delete role: it is currently assigned to $usage_count shifts.";
                    } else {
                        $delete_sql = "DELETE FROM roles WHERE id = ?";
                        $delete_params = [$_POST['role_id']];
                        
                        // SECURITY: Add company filtering to prevent cross-company deletions
                        if ($use_company_filter && $company_id) {
                            $delete_sql .= " AND company_id = ?";
                            $delete_params[] = $company_id;
                        }
                        
                        $stmt = $conn->prepare($delete_sql);
                        $stmt->execute($delete_params);
                        $_SESSION['success_message'] = 'Role deleted successfully!';
                    }
                    break;
            }
            
            // Clean output buffer and redirect to prevent form resubmission
            ob_clean();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    // Get all roles - WITH COMPANY FILTERING
    $roles_sql = "SELECT * FROM roles";
    $roles_params = [];
    
    // SECURITY: Add company filtering to prevent viewing other companies' roles
    if ($use_company_filter && $company_id) {
        $roles_sql .= " WHERE company_id = ?";
        $roles_params[] = $company_id;
    }
    
    $roles_sql .= " ORDER BY name";
    $stmt = $conn->prepare($roles_sql);
    $stmt->execute($roles_params);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("ROLES PAGE ERROR: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while loading roles.';
    $roles = [];
}

// Clean any unwanted output and include the header
ob_end_clean();
$page_title = 'Role Management';
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-user-tag"></i> Role Management</h2>
                <button type="button" class="btn btn-primary" onclick="showCreateRoleModal()">
                    <i class="fas fa-plus"></i> Add New Role
                </button>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="rolesTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($role['name']); ?></td>
                                    <td><?php echo htmlspecialchars($role['description']); ?></td>
                                    <td>
                                        <?php if ($role['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
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

<!-- Create/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalTitle">Add New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="roleForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="roleAction" value="create">
                    <input type="hidden" name="role_id" id="roleId">
                    
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="roleName" name="name" required>
                        <div class="form-text">Display name for this role</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="roleDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="roleDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3" id="roleActiveGroup" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="roleActive" name="is_active" value="1">
                            <label class="form-check-label" for="roleActive">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="roleSubmitBtn">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the role "<span id="deleteRoleName"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="role_id" id="deleteRoleId">
                    <button type="submit" class="btn btn-danger">Delete Role</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showCreateRoleModal() {
    document.getElementById('roleModalTitle').textContent = 'Add New Role';
    document.getElementById('roleAction').value = 'create';
    document.getElementById('roleSubmitBtn').textContent = 'Create Role';
    document.getElementById('roleActiveGroup').style.display = 'none';
    
    // Clear form
    document.getElementById('roleForm').reset();
    document.getElementById('roleId').value = '';
    
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}

function editRole(role) {
    document.getElementById('roleModalTitle').textContent = 'Edit Role';
    document.getElementById('roleAction').value = 'update';
    document.getElementById('roleSubmitBtn').textContent = 'Update Role';
    document.getElementById('roleActiveGroup').style.display = 'block';
    
    // Populate form
    document.getElementById('roleId').value = role.id;
    document.getElementById('roleName').value = role.name;
    document.getElementById('roleDescription').value = role.description || '';
    document.getElementById('roleActive').checked = role.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}

function deleteRole(roleId, roleName) {
    document.getElementById('deleteRoleId').value = roleId;
    document.getElementById('deleteRoleName').textContent = roleName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Initialize DataTable
$(document).ready(function() {
    $('#rolesTable').DataTable({
        order: [[0, 'asc']], // Sort by name by default
        pageLength: 25,
        responsive: true
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
