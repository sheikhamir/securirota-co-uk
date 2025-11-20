<?php
$page_title = 'My Profile';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get current user data
    $stmt = $conn->prepare("
        SELECT u.*, 
               CASE 
                   WHEN o.id IS NOT NULL THEN CONCAT(o.first_name, ' ', o.last_name)
                   ELSE NULL 
               END as officer_name,
               o.id as officer_id
        FROM users u 
        LEFT JOIN officers o ON u.id = o.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found.');
    }
    
    // Handle form submissions
    if ($_POST && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                
                if (empty($username) || empty($email)) {
                    throw new Exception('Username and email are required.');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Please enter a valid email address.');
                }
                
                // Check if username or email already exists for other users
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Username or email already exists.');
                }
                
                // Update user profile
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
                
                // Update session data
                $_SESSION['username'] = $username;
                
                $success = "Profile updated successfully.";
                
                // Refresh user data
                $stmt = $conn->prepare("
                    SELECT u.*, 
                           CASE 
                               WHEN o.id IS NOT NULL THEN CONCAT(o.first_name, ' ', o.last_name)
                               ELSE NULL 
                           END as officer_name,
                           o.id as officer_id
                    FROM users u 
                    LEFT JOIN officers o ON u.id = o.user_id 
                    WHERE u.id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception('All password fields are required.');
                }
                
                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception('Current password is incorrect.');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('New passwords do not match.');
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception('New password must be at least 6 characters long.');
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                $success = "Password changed successfully.";
                break;
        }
    }
    
    // Get user activity statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logins,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_logins,
            created_at as last_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
        <div class="text-muted">
            <small>Last updated: <?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></small>
        </div>
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

    <div class="row">
        <!-- Profile Overview -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Profile Overview</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="row text-center mt-4">
                        <div class="col">
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'secondary'; ?> fs-6">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        <div class="col">
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?> fs-6">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($user['officer_name']): ?>
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">Linked Officer Profile:</small><br>
                            <strong><?php echo htmlspecialchars($user['officer_name']); ?></strong>
                            <a href="<?php echo BASE_URL; ?>pages/officers.php" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="fas fa-external-link-alt"></i> View Profile
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">No officer profile linked</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Account Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?php echo $activity['today_logins']; ?></h4>
                            <small class="text-muted">Today</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info"><?php echo $activity['week_logins']; ?></h4>
                            <small class="text-muted">This Week</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">
                            Account created: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Management -->
        <div class="col-md-8">
            <!-- Update Profile Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Update Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    <div class="form-text">Contact an administrator to change your role.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Account Status</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['status']); ?>" readonly>
                                    <div class="form-text">Contact an administrator if your account is inactive.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-shield-alt"></i> Security Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Last Login:</small><br>
                            <strong><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Account Created:</small><br>
                            <strong><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></strong>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Security Tips:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Use a strong, unique password</li>
                                <li>Never share your login credentials</li>
                                <li>Log out when finished using the system</li>
                                <li>Report any suspicious account activity</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword) {
        if (newPassword === confirmPassword) {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.setCustomValidity('Passwords do not match');
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    }
});

// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthIndicator = document.getElementById('password-strength');
    
    // You can add password strength validation here
    if (password.length >= 6) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
