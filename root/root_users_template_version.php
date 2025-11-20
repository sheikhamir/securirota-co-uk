<?php
/**
 * Root User - Root Users Management
 * Manage root users and billing calculator users
 */

// Define page constants for template system
define('ROOT_ACCESS', true);

$page_title = 'Root Users Management';
$page_description = 'Manage root users and billing calculator users';
$active_page = 'root_users';
$show_breadcrumb = true;
$breadcrumb_items = [
    ['title' => 'User Management', 'url' => 'all_users.php'],
    ['title' => 'Root Users']
];

session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

// Handle root user actions
if ($_POST && isset($_POST['action'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        switch ($_POST['action']) {
            case 'add_root_user':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $user_type = $_POST['user_type']; // 'root' or 'billing_calculator'
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Username and password are required.');
                }
                
                // Check if username already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already exists.');
                }
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Root users are super_admin with no company
                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, role, company_id, status, created_at) 
                    VALUES (?, ?, ?, 'super_admin', NULL, 'active', NOW())
                ");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $user_id = $conn->lastInsertId();
                
                // Add special metadata for user type
                if ($user_type === 'billing_calculator') {
                    // You could add additional metadata or permissions here
                    // For now, we'll use a simple comment system
                    $stmt = $conn->prepare("
                        UPDATE users SET email = CONCAT(?, ' - Billing Calculator User') WHERE id = ?
                    ");
                    $stmt->execute([$email, $user_id]);
                }
                
                $_SESSION['success'] = "Root user '{$username}' created successfully.";
                break;
                
            case 'update_root_user':
                $user_id = (int)$_POST['user_id'];
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                
                // Don't allow changing the main root user
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current_user['username'] === 'root' && $username !== 'root') {
                    throw new Exception('Cannot change the main root username.');
                }
                
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?
                    WHERE id = ? AND role = 'super_admin' AND company_id IS NULL
                ");
                $stmt->execute([$username, $email, $user_id]);
                
                $_SESSION['success'] = "Root user '{$username}' updated successfully.";
                break;
                
            case 'change_root_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = $_POST['new_password'];
                
                if (empty($new_password)) {
                    throw new Exception('New password is required.');
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("
                    UPDATE users SET password = ? 
                    WHERE id = ? AND role = 'super_admin' AND company_id IS NULL
                ");
                $stmt->execute([$hashed_password, $user_id]);
                
                $_SESSION['success'] = "Password changed successfully.";
                break;
                
            case 'delete_root_user':
                $user_id = (int)$_POST['user_id'];
                
                // Don't allow deletion of main root user
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user['username'] === 'root') {
                    throw new Exception('Cannot delete the main root user.');
                }
                
                $stmt = $conn->prepare("
                    DELETE FROM users 
                    WHERE id = ? AND role = 'super_admin' AND company_id IS NULL
                ");
                $stmt->execute([$user_id]);
                
                $_SESSION['success'] = "Root user deleted successfully.";
                break;
        }
        
        header('Location: root_users.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get root users
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all root users (super_admin with no company)
    $stmt = $conn->query("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM activity_log WHERE user_id = u.id) as activity_count,
            DATE(u.last_login) as last_login_date
        FROM users u
        WHERE u.role = 'super_admin' AND u.company_id IS NULL
        ORDER BY u.username ASC
    ");
    $root_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = [
        'total_root_users' => count($root_users),
        'active_root_users' => count(array_filter($root_users, function($u) { return $u['status'] === 'active'; })),
        'billing_calculator_users' => count(array_filter($root_users, function($u) { return strpos($u['email'], 'Billing Calculator') !== false; })),
        'recent_logins' => count(array_filter($root_users, function($u) { return $u['last_login_date'] && strtotime($u['last_login_date']) > strtotime('-7 days'); }))
    ];
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading root users: " . $e->getMessage();
}

// Template configuration
$enable_live_search = true;
$search_config = [
    'input_id' => 'rootUserSearch',
    'table_id' => 'rootUsersTable',
    'columns' => [0, 1] // Search username and email
];

$enable_table_sorting = true;
$sortable_table_id = 'rootUsersTable';

// Include header template
include 'includes/header.php';
?>

<!-- Header Section -->
<div class="crown-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-crown text-warning me-2"></i>
                Root Users Management
            </h1>
            <p class="mb-0 text-white-50">Manage root users and billing calculator users</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-crown-outline" data-bs-toggle="modal" data-bs-target="#addRootUserModal">
                <i class="fas fa-plus me-1"></i> Add Root User
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
                    <div class="stats-number"><?= $stats['total_root_users'] ?></div>
                    <div class="stats-label">Total Root Users</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-crown"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $stats['active_root_users'] ?></div>
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
                    <div class="stats-number"><?= $stats['billing_calculator_users'] ?></div>
                    <div class="stats-label">Billing Calculator</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-calculator"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-number"><?= $stats['recent_logins'] ?></div>
                    <div class="stats-label">Recent Logins</div>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="crown-card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="rootUserSearch" placeholder="Search root users...">
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($root_users) ?> root users total
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Root Users Table -->
<div class="crown-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Root Users
        </h5>
        <div class="text-muted small">
            <i class="fas fa-shield-alt me-1"></i>
            Super Administrator Access
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($root_users)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-crown fa-3x mb-3"></i>
                <h5>No Root Users Found</h5>
                <p class="text-muted">Start by creating your first root user.</p>
                <button type="button" class="btn btn-crown" data-bs-toggle="modal" data-bs-target="#addRootUserModal">
                    <i class="fas fa-plus me-1"></i> Add Root User
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table crown-table" id="rootUsersTable">
                    <thead>
                        <tr>
                            <th data-sortable>Username</th>
                            <th data-sortable>Email</th>
                            <th data-sortable>Type</th>
                            <th data-sortable>Status</th>
                            <th data-sortable>Last Login</th>
                            <th data-sortable>Activity</th>
                            <th data-sortable>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($root_users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2 bg-warning">
                                            <i class="fas fa-crown text-white"></i>
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
                                    <?php if (strpos($user['email'], 'Billing Calculator') !== false): ?>
                                        <span class="crown-badge crown-badge-info">Billing Calculator</span>
                                    <?php elseif ($user['username'] === 'root'): ?>
                                        <span class="crown-badge crown-badge-danger">Main Root</span>
                                    <?php else: ?>
                                        <span class="crown-badge crown-badge-warning">Root User</span>
                                    <?php endif; ?>
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
                                    <div class="small">
                                        <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="editRootUser(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-crown-outline btn-sm" 
                                                onclick="changeRootPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($user['username'] !== 'root'): ?>
                                            <button type="button" class="btn btn-crown-outline btn-sm" 
                                                    onclick="deleteRootUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
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

<!-- Add Root User Modal -->
<div class="modal fade" id="addRootUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Add Root User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_root_user">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Root users have full system access. Only create trusted accounts.
                    </div>
                    
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
                    
                    <div class="mb-3">
                        <label for="user_type" class="form-label">User Type *</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="root">Root User</option>
                            <option value="billing_calculator">Billing Calculator User</option>
                        </select>
                        <div class="form-text">
                            Billing Calculator users have specialized access for billing calculations.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crown">
                        <i class="fas fa-plus me-1"></i> Create Root User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Root User Modal -->
<div class="modal fade" id="editRootUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content crown-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Edit Root User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editRootUserForm" data-loading>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_root_user">
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
// Edit root user function
function editRootUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email || '';
    
    RootCommon.showModal('editRootUserModal');
}

// Change root password function
function changeRootPassword(userId, username) {
    const newPassword = prompt('Enter new password for ' + username + ':');
    if (newPassword && newPassword.length >= 8) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='change_root_password'>
            <input type='hidden' name='user_id' value='\${userId}'>
            <input type='hidden' name='new_password' value='\${newPassword}'>
        `;
        document.body.appendChild(form);
        form.submit();
    } else if (newPassword) {
        RootCommon.showAlert('Password must be at least 8 characters long.', 'warning');
    }
}

// Delete root user function
function deleteRootUser(userId, username) {
    const message = 'Are you sure you want to delete root user \"' + username + '\"? This action cannot be undone and will permanently remove their access.';
    
    RootCommon.confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type='hidden' name='action' value='delete_root_user'>
            <input type='hidden' name='user_id' value='\${userId}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });
}
";

include 'includes/footer.php';
?>