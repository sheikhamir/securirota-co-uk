<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/ActivityLogger.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? '';
    if (isRootUser()) {
        header('Location: ' . BASE_URL . 'root/root_dashboard.php');
    } else if ($role === 'super_admin') {
        header('Location: ' . BASE_URL . 'root/index.php');
    } else if ($role === 'officer') {
        header('Location: ' . BASE_URL . 'pages/officer_portal.php');
    } else {
        header('Location: ' . BASE_URL . 'dashboard.php');
    }
    exit();
}

$error = '';

if ($_POST) {
    $login_identifier = trim($_POST['login_identifier']); // Can be mobile number or username
    
    // Remove spaces if this looks like a mobile number (contains digits)
    if (preg_match('/\d/', $login_identifier)) {
        $login_identifier = str_replace(' ', '', $login_identifier);
    }
    
    $password_or_pin = $_POST['password_or_pin']; // Can be password or PIN
    
    if (empty($login_identifier) || empty($password_or_pin)) {
        $error = 'Please enter both login details.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $logger = new ActivityLogger($conn);
            
            // Check if user is suspended first
            $suspended_check = $conn->prepare("
                SELECT u.id, u.username, u.password, u.role, u.mobile_number, u.pin, o.suspend 
                FROM users u 
                LEFT JOIN officers o ON u.id = o.user_id 
                WHERE (u.username = ? OR u.mobile_number = ?) AND u.status = 'active'
            ");
            $suspended_check->execute([$login_identifier, $login_identifier]);
            $user = $suspended_check->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['suspend'] == 1) {
                $error = 'Your account has been suspended. Please contact the administrator.';
            } else if ($user) {
                $login_successful = false;
                
                // Check if login is via PIN (officers) or password (admin/users)
                if (!empty($user['pin']) && $password_or_pin === $user['pin']) {
                    // PIN login for officers
                    $login_successful = true;
                } else if (password_verify($password_or_pin, $user['password'])) {
                    // Password login for admin or fallback
                    $login_successful = true;
                }
                
                if ($login_successful) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Get company info if companies table exists (after migration)
                    $company_id = null;
                    $company_name = null;
                    try {
                        $company_check = $conn->prepare("SELECT company_id FROM users WHERE id = ? LIMIT 1");
                        $company_check->execute([$user['id']]);
                        $company_data = $company_check->fetch(PDO::FETCH_ASSOC);
                        if ($company_data && isset($company_data['company_id'])) {
                            $company_id = $company_data['company_id'];
                            
                            // Get company name if companies table exists
                            $company_name_check = $conn->prepare("SELECT name FROM companies WHERE id = ? LIMIT 1");
                            $company_name_check->execute([$company_id]);
                            $company_info = $company_name_check->fetch(PDO::FETCH_ASSOC);
                            $company_name = $company_info['name'] ?? null;
                        }
                    } catch (Exception $e) {
                        // Companies table doesn't exist yet, that's fine
                    }
                    
                    $_SESSION['company_id'] = $company_id;
                    $_SESSION['company_name'] = $company_name;
                    
                    // Prepare metadata for logging
                    $metadata = [
                        'username' => $user['username'],
                        'mobile_number' => $user['mobile_number'],
                        'role' => $user['role'],
                        'company_id' => $company_id,
                        'login_time' => date('Y-m-d H:i:s'),
                        'login_method' => !empty($user['pin']) && $password_or_pin === $user['pin'] ? 'PIN' : 'Password'
                    ];
                    
                    // Check subscription status for companies (skip for super_admin and root users)
                    if ($company_id && !in_array($user['role'], ['super_admin', 'root'])) {
                        try {
                            require_once 'includes/SubscriptionMiddleware.php';
                            $subscription_middleware = new SubscriptionMiddleware($conn);
                            
                            $subscription_status = SubscriptionMiddleware::getSubscriptionStatus($conn, $company_id);
                            if ($subscription_status && !$subscription_status['is_active']) {
                                // Store subscription status for the expired page
                                $_SESSION['company_subscription_status'] = $subscription_status;
                                $_SESSION['subscription_expired'] = true;
                                
                                // Log the expired subscription login attempt
                                $expired_description = "Login attempted with expired subscription";
                                $expired_metadata = array_merge($metadata ?? [], [
                                    'subscription_expired_on' => $subscription_status['subscription_expires_at'],
                                    'company_name' => $subscription_status['name']
                                ]);
                                $logger->logSystemAction($user['id'], 'expired_subscription_login', $expired_description, $expired_metadata);
                                
                                // Redirect to subscription expired page
                                header('Location: ' . BASE_URL . 'subscription_expired.php');
                                exit();
                            }
                        } catch (Exception $e) {
                            // If subscription check fails, log error but allow login to prevent lockout
                            error_log("Subscription check failed during login: " . $e->getMessage());
                        }
                    }
                    
                    // Log the login activity
                    $description = "User logged in: " . ($user['mobile_number'] ?: $user['username']);
                    $logger->logSystemAction($user['id'], 'login', $description, $metadata);
                    
                    // Redirect based on role
                    if (isRootUser()) {
                        header('Location: ' . BASE_URL . 'root/root_dashboard.php');
                    } else if ($user['role'] === 'super_admin') {
                        header('Location: ' . BASE_URL . 'root/index.php');
                    } else if ($user['role'] === 'officer') {
                        header('Location: ' . BASE_URL . 'pages/officer_portal.php');
                    } else {
                        header('Location: ' . BASE_URL . 'dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid login credentials.';
                }
            } else {
                $error = 'Invalid login credentials.';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Security Rota Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
	    box-sizing: border-box;
            flex-direction: column;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            margin: 0;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
        .form-floating {
            margin-bottom: 1.5rem;
        }
        .form-floating .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-floating .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .login-footer {
            background-color: #f8f9fa;
            padding: 1.5rem 2rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .input-group-text {
            border: 2px solid #e9ecef;
            border-right: none;
            background: transparent;
            border-radius: 10px 0 0 10px;
        }
        .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-shield-alt fa-3x mb-3"></i>
            <h1>Security Rota System</h1>
            <p>Please login to continue</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-floating mb-3">
                    <input type="text" 
                           class="form-control" 
                           id="login_identifier" 
                           name="login_identifier" 
                           placeholder="Mobile number or username"
                           required
                           autocomplete="username">
                    <label for="login_identifier">
                        <i class="fas fa-mobile-alt me-2"></i>Mobile Number / Username
                    </label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" 
                           class="form-control" 
                           id="password_or_pin" 
                           name="password_or_pin" 
                           placeholder="PIN or Password"
                           required
                           autocomplete="current-password">
                    <label for="password_or_pin">
                        <i class="fas fa-key me-2"></i>PIN / Password
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="mt-4 text-center">
                <div class="row">
                    <div class="col-6">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-title text-primary"><i class="fas fa-user-shield"></i> Officers</h6>
                                <small class="text-muted">Use your mobile number and PIN</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-title text-success"><i class="fas fa-cog"></i> Admin</h6>
                                <small class="text-muted">Use username and password</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="login-footer">
            <i class="fas fa-lock me-2"></i>
            Secure login powered by SecuriRota System
            <br>
            <small class="text-muted">Version 2.0 - Enhanced Authentication</small>
        </div>
    </div>
    <?php if( isset($_GET) && ( isset($_GET['creator']) || isset($_GET['owner']) || isset($_GET['author']) ) ): ?>
        <style>
            .owner {margin-top: 1rem;text-align: center;color: #ffffff;}
            .owner a {color: #ffffff;text-decoration: underline;}
        </style>
        <div class="owner"><small>Developed by <a href="https://technopede.com/" target="_blank">Technopede</a></small></div>
    <?php endif; ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
