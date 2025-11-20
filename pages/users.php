<?php
$page_title = 'User Management';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize company filtering
    $use_company_filter = false;
    $company_id = null;
    
    // Check if we're in multi-tenant mode (post-migration)
    try {
        $column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'company_id'");
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
    if ($_POST && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Validate input
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('Username, email, and password are required.');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Please enter a valid email address.');
                }
                
                if (strlen($password) < 6) {
                    throw new Exception('Password must be at least 6 characters long.');
                }
                
                // Check if username or email already exists
                $check_sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
                $check_params = [$username, $email];
                
                if ($use_company_filter && $company_id) {
                    $check_sql .= " AND company_id = ?";
                    $check_params[] = $company_id;
                }
                
                $stmt = $conn->prepare($check_sql);
                $stmt->execute($check_params);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Username or email already exists.');
                }
                
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Prepare SQL based on company filtering
                if ($use_company_filter && $company_id) {
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password, role, status, company_id) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $email, $hashed_password, $role, $status, $company_id]);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password, role, status) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $email, $hashed_password, $role, $status]);
                }
                
                $success = "User '$username' created successfully.";
                break;
                
            case 'edit':
                $user_id = (int)$_POST['user_id'];
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                if (empty($username) || empty($email)) {
                    throw new Exception('Username and email are required.');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Please enter a valid email address.');
                }
                
                // Check if username or email already exists for other users
                $check_sql = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?";
                $check_params = [$username, $email, $user_id];
                
                if ($use_company_filter && $company_id) {
                    $check_sql .= " AND company_id = ?";
                    $check_params[] = $company_id;
                }
                
                $stmt = $conn->prepare($check_sql);
                $stmt->execute($check_params);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Username or email already exists.');
                }
                
                // Update user (ensure we only update users in our company)
                $update_sql = "UPDATE users 
                               SET username = ?, email = ?, role = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                               WHERE id = ?";
                $update_params = [$username, $email, $role, $status, $user_id];
                
                if ($use_company_filter && $company_id) {
                    $update_sql .= " AND company_id = ?";
                    $update_params[] = $company_id;
                }
                
                $stmt = $conn->prepare($update_sql);
                $stmt->execute($update_params);
                
                $success = "User '$username' updated successfully.";
                break;
                
            case 'change_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($new_password) || empty($confirm_password)) {
                    throw new Exception('Both password fields are required.');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('Passwords do not match.');
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception('Password must be at least 6 characters long.');
                }
                
                // Update password (ensure we only update users in our company)
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $password_params = [$hashed_password, $user_id];
                
                if ($use_company_filter && $company_id) {
                    $password_sql .= " AND company_id = ?";
                    $password_params[] = $company_id;
                }
                
                $stmt = $conn->prepare($password_sql);
                $stmt->execute($password_params);
                
                $success = "Password changed successfully.";
                break;
                
            case 'delete':
                $user_id = (int)$_POST['user_id'];
                
                // Prevent deleting the current user
                if ($user_id == $_SESSION['user_id']) {
                    throw new Exception('You cannot delete your own account.');
                }
                
                // Check if user has associated officer record
                $officer_check_sql = "SELECT COUNT(*) FROM officers WHERE user_id = ?";
                $officer_check_params = [$user_id];
                
                if ($use_company_filter && $company_id) {
                    $officer_check_sql .= " AND company_id = ?";
                    $officer_check_params[] = $company_id;
                }
                
                $stmt = $conn->prepare($officer_check_sql);
                $stmt->execute($officer_check_params);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cannot delete user with associated officer record. Please remove officer association first.');
                }
                
                // Delete user (ensure we only delete users in our company)
                $delete_sql = "DELETE FROM users WHERE id = ?";
                $delete_params = [$user_id];
                
                if ($use_company_filter && $company_id) {
                    $delete_sql .= " AND company_id = ?";
                    $delete_params[] = $company_id;
                }
                
                $stmt = $conn->prepare($delete_sql);
                $stmt->execute($delete_params);
                
                $success = "User deleted successfully.";
                break;
        }
    }
    
    // Initialize default stats
    $stats = [
        'total_users' => 0,
        'active_users' => 0, 
        'inactive_users' => 0,
        'admin_users' => 0,
        'officer_users' => 0
    ];
    
    // Get user statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
            SUM(CASE WHEN role = 'officer' THEN 1 ELSE 0 END) as officer_users
        FROM users";
    
    $stats_params = [];
    
    if ($use_company_filter && $company_id) {
        $stats_sql .= " WHERE company_id = ?";
        $stats_params[] = $company_id;
    }
    
    $stmt = $conn->prepare($stats_sql);
    $stmt->execute($stats_params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = $result;
    }
    
    // Get all users
    $users_sql = "
        SELECT u.*, 
               CASE 
                   WHEN o.id IS NOT NULL THEN CONCAT(o.first_name, ' ', o.last_name)
                   ELSE NULL 
               END as officer_name
        FROM users u 
        LEFT JOIN officers o ON u.id = o.user_id";
    
    $users_params = [];
    
    if ($use_company_filter && $company_id) {
        $users_sql .= " WHERE u.company_id = ?";
        $users_params[] = $company_id;
    }
    
    $users_sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($users_sql);
    $stmt->execute($users_params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> User Management</h2>
        <button class="btn btn-primary" onclick="showAddUserModal()">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo $stats['total_users'] ?? 0; ?></h5>
                    <p class="card-text">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?php echo $stats['active_users'] ?? 0; ?></h5>
                    <p class="card-text">Active Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?php echo $stats['inactive_users'] ?? 0; ?></h5>
                    <p class="card-text">Inactive Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?php echo $stats['admin_users'] ?? 0; ?></h5>
                    <p class="card-text">Admin Users</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> User Accounts</h5>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No users found</h5>
                    <p class="text-muted">Click "Add New User" to create your first user account.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Officer Link</th>
                                <th>Created</th>
                                <th>Last Updated</th>
                                <th data-orderable="false">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-info text-dark ms-1">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['officer_name']): ?>
                                            <span class="text-success">
                                                <i class="fas fa-link"></i> <?php echo htmlspecialchars($user['officer_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No officer linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="changePassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="officer">Officer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="officer">Officer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key"></i> Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="password_user_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Changing password for: <strong id="password_username"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddUserModal() {
    const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
    modal.show();
}

function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function changePassword(userId, username) {
    document.getElementById('password_user_id').value = userId;
    document.getElementById('password_username').textContent = username;
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize DataTables for Users
$(document).ready(function() {
    $('#usersTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'asc']], // Sort by username by default
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
            search: "Search users:",
            lengthMenu: "Show _MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users found",
            infoFiltered: "(filtered from _MAX_ total users)"
        },
        columnDefs: [
            { orderable: false, targets: 7 } // Actions column
        ]
    });
});

// Clear form data when modals are hidden
document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});

document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});

document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
});
</script>

<?php require_once '../includes/footer.php'; ?>
