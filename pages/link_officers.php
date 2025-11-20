<?php
$page_title = 'Link Officers to Users';

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
    
    // Handle linking request
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'link') {
        $officer_id = (int)$_POST['officer_id'];
        $user_id = (int)$_POST['user_id'];
        
        // Update the officer record with the user_id
        $stmt = $conn->prepare("UPDATE officers SET user_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $officer_id]);
        
        $success = "Officer linked to user account successfully!";
    }
    
    // Handle create user for officer
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_user') {
        $officer_id = (int)$_POST['officer_id'];
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Get officer details
        $stmt = $conn->prepare("SELECT * FROM officers WHERE id = ?");
        $stmt->execute([$officer_id]);
        $officer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($officer) {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists.');
            }
            
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $current_company_id = getCurrentCompanyId();
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role, status, company_id) 
                VALUES (?, ?, ?, 'officer', 'active', ?)
            ");
            $stmt->execute([$username, $officer['email'], $hashed_password, $current_company_id]);
            $user_id = $conn->lastInsertId();
            
            // Link officer to user
            $stmt = $conn->prepare("UPDATE officers SET user_id = ? WHERE id = ?");
            $stmt->execute([$user_id, $officer_id]);
            
            $success = "User account created and linked to officer successfully!";
        }
    }
    
    // Get unlinked officers
    $stmt = $conn->prepare("
        SELECT o.* 
        FROM officers o 
        WHERE o.user_id IS NULL 
        ORDER BY o.first_name, o.last_name
    ");
    $stmt->execute();
    $unlinked_officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get users without linked officers
    $stmt = $conn->prepare("
        SELECT u.* 
        FROM users u 
        LEFT JOIN officers o ON u.id = o.user_id 
        WHERE o.user_id IS NULL AND u.role = 'officer'
        ORDER BY u.username
    ");
    $stmt->execute();
    $unlinked_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-link"></i> Link Officers to User Accounts</h2>
        <a href="officers.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Officers
        </a>
    </div>

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
        <!-- Unlinked Officers -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-user-times"></i> Officers Without User Accounts (<?php echo count($unlinked_officers); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($unlinked_officers)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-success">All officers have user accounts!</h6>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unlinked_officers as $officer): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Email: <?php echo htmlspecialchars($officer['email']); ?><br>
                                            Officer ID: <?php echo $officer['id']; ?>
                                        </small>
                                    </p>
                                    
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-success" onclick="createUser(<?php echo $officer['id']; ?>, '<?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>')">
                                            <i class="fas fa-plus"></i> Create User Account
                                        </button>
                                        <?php if (!empty($unlinked_users)): ?>
                                            <button class="btn btn-primary" onclick="linkToExisting(<?php echo $officer['id']; ?>, '<?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>')">
                                                <i class="fas fa-link"></i> Link to Existing
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Unlinked Users -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Users Without Officer Records (<?php echo count($unlinked_users); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($unlinked_users)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-success">All officer users are linked!</h6>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unlinked_users as $user): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Email: <?php echo htmlspecialchars($user['email']); ?><br>
                                            Role: <?php echo ucfirst($user['role']); ?><br>
                                            Status: <?php echo ucfirst($user['status']); ?>
                                        </small>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Create User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_user">
                    <input type="hidden" name="officer_id" id="createUserId">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Creating user account for: <strong id="createUserOfficerName"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="createUsername" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="createUsername" name="username" required>
                        <small class="text-muted">This will be used to login to the system</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="createPassword" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="createPassword" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Create User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Link to Existing Modal -->
<div class="modal fade" id="linkExistingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-link"></i> Link to Existing User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="link">
                    <input type="hidden" name="officer_id" id="linkOfficerId">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Linking officer: <strong id="linkOfficerName"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="linkUserId" class="form-label">Select User Account</label>
                        <select class="form-select" name="user_id" id="linkUserId" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($unlinked_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-link me-2"></i>Link Officer to User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function createUser(officerId, officerName) {
    document.getElementById('createUserId').value = officerId;
    document.getElementById('createUserOfficerName').textContent = officerName;
    
    // Generate suggested username from officer name
    const nameParts = officerName.toLowerCase().split(' ');
    let suggestedUsername = nameParts.join('_');
    if (suggestedUsername.length > 20) {
        suggestedUsername = nameParts[0] + '_' + nameParts[nameParts.length - 1];
    }
    document.getElementById('createUsername').value = suggestedUsername;
    
    const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
    modal.show();
}

function linkToExisting(officerId, officerName) {
    document.getElementById('linkOfficerId').value = officerId;
    document.getElementById('linkOfficerName').textContent = officerName;
    
    const modal = new bootstrap.Modal(document.getElementById('linkExistingModal'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
