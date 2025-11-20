<?php
/**
 * All Users Management - Root Access Only
 * Comprehensive user management across all companies
 */
session_start();
require_once '../config/config.php';

// Check if user is the root superuser
if (!isRootUser()) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

require_once '../config/database.php';

$page_title = 'All Users Management';
$success_message = '';
$error_message = '';

// Handle user actions
if ($_POST) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_user':
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $role = $_POST['role'];
                    $company_id = $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null;
                    $status = $_POST['status'];
                    $mobile_number = trim($_POST['mobile_number']) ?: null;
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, company_id, status, mobile_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $password, $role, $company_id, $status, $mobile_number]);
                    $success_message = "User created successfully!";
                    break;
                    
                case 'update_user':
                    $user_id = (int)$_POST['user_id'];
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $role = $_POST['role'];
                    $company_id = $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null;
                    $status = $_POST['status'];
                    $mobile_number = trim($_POST['mobile_number']) ?: null;
                    
                    $sql = "UPDATE users SET username = ?, email = ?, role = ?, company_id = ?, status = ?, mobile_number = ?";
                    $params = [$username, $email, $role, $company_id, $status, $mobile_number];
                    
                    if (!empty($_POST['new_password'])) {
                        $sql .= ", password = ?, require_password_change = 1";
                        $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $user_id;
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $success_message = "User updated successfully!";
                    break;
                    
                case 'delete_user':
                    $user_id = (int)$_POST['user_id'];
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
                    $stmt->execute([$user_id]);
                    if ($stmt->rowCount() > 0) {
                        $success_message = "User deleted successfully!";
                    } else {
                        $error_message = "Cannot delete super admin users or user not found.";
                    }
                    break;
                    
                case 'reset_lockout':
                    $user_id = (int)$_POST['user_id'];
                    $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "User lockout reset successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$company_filter = isset($_GET['company']) ? $_GET['company'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Build the WHERE clause for filtering
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.mobile_number LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($role_filter)) {
        $where_conditions[] = "u.role = ?";
        $params[] = $role_filter;
    }
    
    if (!empty($company_filter)) {
        if ($company_filter === 'null') {
            $where_conditions[] = "u.company_id IS NULL";
        } else {
            $where_conditions[] = "u.company_id = ?";
            $params[] = $company_filter;
        }
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM users u $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $per_page);
    
    // Get users with company information
    $sql = "SELECT u.*, c.name as company_name, c.slug as company_slug 
            FROM users u 
            LEFT JOIN companies c ON u.company_id = c.id 
            $where_clause 
            ORDER BY u.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get companies for dropdowns
    $companies_stmt = $conn->query("SELECT id, name FROM companies ORDER BY name");
    $companies = $companies_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stats_stmt = $conn->query("SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'super_admin' THEN 1 END) as super_admins,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
        COUNT(CASE WHEN role = 'officer' THEN 1 END) as officers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_users,
        COUNT(CASE WHEN locked_until > NOW() THEN 1 END) as locked_users
        FROM users");
    $user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("All Users Error: " . $e->getMessage());
    $error_message = "Failed to load users: " . $e->getMessage();
    $users = [];
    $companies = [];
    $user_stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Root Control Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .user-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .user-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #5a5c69;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.8rem;
            border-radius: 50px;
        }
        
        .role-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .search-filters {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            margin: 0 0.1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .locked-indicator {
            color: #e74a3b;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Include root navigation -->
        <?php include 'components/navigation.php'; ?>
        
        <div id="content">
            <div class="container-fluid">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 text-dark mb-0">
                                    <i class="fas fa-users text-primary"></i> All Users Management
                                </h1>
                                <p class="text-muted mb-0">Manage all users across all companies</p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                    <i class="fas fa-plus"></i> Create New User
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h4 class="mb-0"><?php echo $user_stats['total_users'] ?? 0; ?></h4>
                                <small>Total Users</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-crown fa-2x mb-2"></i>
                                <h4 class="mb-0"><?php echo $user_stats['super_admins'] ?? 0; ?></h4>
                                <small>Super Admins</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie fa-2x mb-2"></i>
                                <h4 class="mb-0"><?php echo $user_stats['admins'] ?? 0; ?></h4>
                                <small>Admins</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                <h4 class="mb-0"><?php echo $user_stats['officers'] ?? 0; ?></h4>
                                <small>Officers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h4 class="mb-0"><?php echo $user_stats['active_users'] ?? 0; ?></h4>
                                <small>Active</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-lock fa-2x mb-2"></i>
                                <h4 class="mb-0"><?php echo $user_stats['locked_users'] ?? 0; ?></h4>
                                <small>Locked</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Users</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Username, email, or phone...">
                        </div>
                        <div class="col-md-2">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="officer" <?php echo $role_filter === 'officer' ? 'selected' : ''; ?>>Officer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="company" class="form-label">Company</label>
                            <select class="form-select" id="company" name="company">
                                <option value="">All Companies</option>
                                <option value="null" <?php echo $company_filter === 'null' ? 'selected' : ''; ?>>No Company (System Users)</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>" 
                                            <?php echo $company_filter == $company['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="all_users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <?php 
                                        $isLocked = !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
                                        $initials = strtoupper(substr($user['username'], 0, 2));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php echo $initials; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?php echo htmlspecialchars($user['username']); ?>
                                                            <?php if ($isLocked): ?>
                                                                <i class="fas fa-lock locked-indicator ms-1" title="Account Locked"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-muted small">
                                                            <?php echo htmlspecialchars($user['email'] ?? 'No email'); ?>
                                                        </div>
                                                        <?php if (!empty($user['mobile_number'])): ?>
                                                            <div class="text-muted small">
                                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['mobile_number']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $role_colors = [
                                                    'super_admin' => 'bg-danger',
                                                    'admin' => 'bg-warning text-dark',
                                                    'officer' => 'bg-primary'
                                                ];
                                                $role_color = $role_colors[$user['role']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge role-badge <?php echo $role_color; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['company_name']): ?>
                                                    <span class="text-primary">
                                                        <?php echo htmlspecialchars($user['company_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="fas fa-globe"></i> System User
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge status-badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge status-badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($isLocked): ?>
                                                    <br><span class="badge status-badge bg-danger mt-1">Locked</span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($user['failed_login_attempts']) && $user['failed_login_attempts'] > 0): ?>
                                                    <br><small class="text-warning">
                                                        <?php echo $user['failed_login_attempts']; ?> failed attempts
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($user['last_login_at'])): ?>
                                                    <div class="small">
                                                        <?php echo date('M j, Y', strtotime($user['last_login_at'])); ?><br>
                                                        <?php echo date('H:i', strtotime($user['last_login_at'])); ?>
                                                    </div>
                                                    <?php if (!empty($user['last_login_ip'])): ?>
                                                        <div class="text-muted small">
                                                            <?php echo htmlspecialchars($user['last_login_ip']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted small">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-primary btn-action" 
                                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                            title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <?php if ($isLocked): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="reset_lockout">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-warning btn-action" 
                                                                    title="Reset Lockout">
                                                                <i class="fas fa-unlock"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['role'] !== 'super_admin'): ?>
                                                        <button type="button" class="btn btn-outline-danger btn-action" 
                                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                                title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h6>No Users Found</h6>
                                            <p class="text-muted">No users match your search criteria.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Users pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&company=<?php echo urlencode($company_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): 
                                    ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&company=<?php echo urlencode($company_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&company=<?php echo urlencode($company_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_users); ?> 
                                    of <?php echo $total_users; ?> users
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Create New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_user">
                        
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                    <input type="tel" class="form-control" id="mobile_number" name="mobile_number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="officer">Officer</option>
                                        <option value="admin">Admin</option>
                                        <option value="super_admin">Super Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Company</label>
                                    <select class="form-select" id="company_id" name="company_id">
                                        <option value="">No Company (System User)</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>">
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
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
                                    <label for="edit_new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="edit_new_password" name="new_password">
                                    <div class="form-text">Leave empty to keep current password</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_mobile_number" class="form-label">Mobile Number</label>
                                    <input type="tel" class="form-control" id="edit_mobile_number" name="mobile_number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Role *</label>
                                    <select class="form-select" id="edit_role" name="role" required>
                                        <option value="officer">Officer</option>
                                        <option value="admin">Admin</option>
                                        <option value="super_admin">Super Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_company_id" class="form-label">Company</label>
                                    <select class="form-select" id="edit_company_id" name="company_id">
                                        <option value="">No Company (System User)</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>">
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status *</label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Delete User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning"></i> This action cannot be undone and will permanently remove all user data.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userData) {
            // Populate the edit modal with user data
            document.getElementById('edit_user_id').value = userData.id;
            document.getElementById('edit_username').value = userData.username;
            document.getElementById('edit_email').value = userData.email || '';
            document.getElementById('edit_mobile_number').value = userData.mobile_number || '';
            document.getElementById('edit_role').value = userData.role;
            document.getElementById('edit_company_id').value = userData.company_id || '';
            document.getElementById('edit_status').value = userData.status;
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function deleteUser(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = username;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
        
        // Role change handler for company assignment
        document.getElementById('role').addEventListener('change', function() {
            const companySelect = document.getElementById('company_id');
            if (this.value === 'super_admin') {
                companySelect.value = '';
                companySelect.disabled = true;
            } else {
                companySelect.disabled = false;
            }
        });
        
        document.getElementById('edit_role').addEventListener('change', function() {
            const companySelect = document.getElementById('edit_company_id');
            if (this.value === 'super_admin') {
                companySelect.value = '';
                companySelect.disabled = true;
            } else {
                companySelect.disabled = false;
            }
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('show')) {
                    alert.classList.remove('show');
                    alert.classList.add('fade');
                    setTimeout(function() {
                        alert.remove();
                    }, 150);
                }
            });
        }, 5000);
    </script>
</body>
</html>
