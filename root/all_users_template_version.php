<?php
/**
 * Root User - All Users Management
 * Manage all users across all companies from root level
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'All Users Management';
$page_description = 'Manage all users across all companies from root level';
$active_page = 'all_users';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'User Management', 'url' => 'root_users.php'],
    ['title' => 'All Users']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Handle user actions
if ($_POST && isset($_POST['action'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        switch ($_POST['action']) {
            case 'add_user':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $company_id = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
                $status = $_POST['status'];
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Username and password are required.');
                }
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, role, company_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$username, $email, $hashed_password, $role, $company_id, $status]);
                
                $_SESSION['success'] = "User '{$username}' created successfully.";
                break;
                
            case 'update_user':
                $user_id = (int)$_POST['user_id'];
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $company_id = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?, company_id = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $role, $company_id, $status, $user_id]);
                
                $_SESSION['success'] = "User '{$username}' updated successfully.";
                break;
                
            case 'change_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = $_POST['new_password'];
                
                if (empty($new_password)) {
                    throw new Exception('New password is required.');
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $_SESSION['success'] = "Password changed successfully.";
                break;
                
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                
                // Don't allow deletion of root user
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user['username'] === 'root') {
                    throw new Exception('Cannot delete the root user.');
                }
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $_SESSION['success'] = "User deleted successfully.";
                break;
                
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $new_status = $_POST['new_status'];
                
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                
                $_SESSION['success'] = "User status updated successfully.";
                break;
        }
        
        header('Location: all_users.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$company_filter = $_GET['company_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get users with company information
    $where_conditions = [];
    $params = [];
    
    if (!empty($role_filter)) {
        $where_conditions[] = "u.role = ?";
        $params[] = $role_filter;
    }
    
    if (!empty($company_filter)) {
        $where_conditions[] = "u.company_id = ?";
        $params[] = $company_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR c.name LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            c.name as company_name,
            (SELECT COUNT(*) FROM activity_log WHERE user_id = u.id) as activity_count,
            DATE(u.last_login) as last_login_date
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        {$where_clause}
        ORDER BY u.created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get companies for dropdown
    $stmt = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user statistics
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admins,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
            SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as managers,
            SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users
        FROM users
    ");
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading users: " . $e->getMessage();
}

// Available user roles
$user_roles = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'manager' => 'Manager',
    'user' => 'User'
];

// Template configuration
$enable_live_search = true;
$search_config = [
    'input_id' => 'userSearch',
    'table_id' => 'usersTable',
    'columns' => [0, 1, 2, 3] // Search username, email, company, role
];

$enable_table_sorting = true;
$sortable_table_id = 'usersTable';

// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-users text-warning me-2"></i>
                All Users Management
            </h1>
            <p class="mb-0 text-white-50">Manage all users across all companies from root level</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-1"></i> Add User
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $user_stats['total_users'] ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $user_stats['active_users'] ?></div>
                    <div class="stats-label">Active Users</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $user_stats['super_admins'] + $user_stats['admins'] ?></div>
                    <div class="stats-label">Administrators</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= count($companies) ?></div>
                    <div class="stats-label">Companies</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="crown-card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3">
                <label for="search" class="form-label">Search Users</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="userSearch" name="search" 
                           value="<?= htmlspecialchars($search) ?>" placeholder="Username, email, company...">
                </div>
            </div>
            <div class="col-md-2">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <?php foreach ($user_roles as $role_key => $role_name): ?>
                        <option value="<?= $role_key ?>" <?= $role_filter === $role_key ? 'selected' : '' ?>>
                            <?= $role_name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="company_id" class="form-label">Company</label>
                <select class="form-select" name="company_id">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>" <?= $company_filter == $company['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-crown w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="crown-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            All Users (<?= count($users) ?>)
        </h5>
        <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Showing <?= count($users) ?> of <?= $user_stats['total_users'] ?> total users
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5>No Users Found</h5>
                <p class="text-muted">No users match your current filters.</p>
                <a href="all_users.php" class="btn btn-crown-outline">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table crown-table" id="usersTable">
                    <thead>
                        <tr>
                            <th data-sortable>Username</th>
                            <th data-sortable>Email</th>
                            <th data-sortable>Company</th>
                            <th data-sortable>Role</th>
                            <th data-sortable>Status</th>
                            <th data-sortable>Last Login</th>
                            <th data-sortable>Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
                                            <small class="text-muted">ID: <?= $user['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <?= htmlspecialchars($user['email'] ?: 'No email') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php if ($user['company_name']): ?>
                                            <span class="crown-badge crown-badge-light">
                                                <?= htmlspecialchars($user['company_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No company</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $role_class = [
                                        'super_admin' => 'danger',
                                        'admin' => 'warning',
                                        'manager' => 'info',
                                        'user' => 'secondary'
                                    ][$user['role']] ?? 'secondary';
                                    ?>
                                    <span class="crown-badge crown-badge-<?= $role_class ?>">
                                        <?= $user_roles[$user['role']] ?? ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="crown-badge crown-badge-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php if ($user['last_login_date']): ?>
                                            <?= date('M j, Y', strtotime($user['last_login_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="crown-badge crown-badge-light">
                                            <?= number_format($user['activity_count']) ?>
                                        </span>
                                        actions
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="toggleStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>', '<?= htmlspecialchars($user['username']) ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Toggle Status">
                                            <i class="fas fa-<?= $user['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                        </button>
                                        <?php if ($user['username'] !== 'root'): ?>
                                            <button type="button" class="btn btn-crown-outline btn-sm" 
                                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Delete User">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Add New User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <?php foreach ($user_roles as $role_key => $role_name): ?>
                                        <option value="<?= $role_key ?>"><?= $role_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_id" class="form-label">Company</label>
                                <select class="form-select" id="company_id" name="company_id">
                                    <option value="">No Company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= $company['id'] ?>">
                                            <?= htmlspecialchars($company['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-plus me-1"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_role" class="form-label">Role *</label>
                                <select class="form-select" id="edit_role" name="role" required>
                                    <?php foreach ($user_roles as $role_key => $role_name): ?>
                                        <option value="<?= $role_key ?>"><?= $role_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_company_id" class="form-label">Company</label>
                                <select class="form-select" id="edit_company_id" name="company_id">
                                    <option value="">No Company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= $company['id'] ?>">
                                            <?= htmlspecialchars($company['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status *</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-save me-1"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$inline_js = "
// Edit user function
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_company_id').value = user.company_id || '';
    document.getElementById('edit_status').value = user.status;
    
    RootCommon.showModal('editUserModal');
}

// Change password function
function changePassword(userId, username) {
    const newPassword = prompt('Enter new password for ' + username + ':');
    if (newPassword && newPassword.length >= 6) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='change_password'>
            <input type='hidden' name='user_id' value='\${userId}'>
            <input type='hidden' name='new_password' value='\${newPassword}'>
        `;
        document.body.appendChild(form);
        form.submit();
    } else if (newPassword) {
        RootCommon.showAlert('Password must be at least 6 characters long.', 'warning');
    }
}

// Toggle status function
function toggleStatus(userId, currentStatus, username) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    const message = 'Are you sure you want to ' + action + ' user \"' + username + '\"?';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='toggle_status'>
            <input type='hidden' name='user_id' value='\${userId}'>
            <input type='hidden' name='new_status' value='\${newStatus}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

// Delete user function
function deleteUser(userId, username) {
    const message = 'Are you sure you want to delete user \"' + username + '\"? This action cannot be undone.';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='delete_user'>
            <input type='hidden' name='user_id' value='\${userId}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });
}
";

include 'includes/footer.php';
?>