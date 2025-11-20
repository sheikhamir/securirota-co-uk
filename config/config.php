<?php
// Session configuration - don't auto-start, let individual pages handle it

// Database configuration
define('DB_HOST', 'db5018877643.hosting-data.io');
define('DB_NAME', 'dbs14895565');
define('DB_USER', 'dbu4421330');
define('DB_PASS', '-MtMr]i!hs?5Y1A*');

// Application configuration
define('BASE_URL', 'http://securirota.co.uk/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ENVIRONMENT', 'production'); // or 'development' for debugging

// Security
define('ENCRYPTION_KEY', 'FTih2Ej66OUxzZKkN9EVtoTJI4RO94dm');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.ionos.co.uk');
define('SMTP_PORT', 587);
define('SMTP_USER', 'info@securirota.co.uk');
define('SMTP_PASS', '(S3cur!R0t@123!)');

// Timezone
date_default_timezone_set('Europe/London');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'database.php';

// Include helper functions
require_once __DIR__ . '/../includes/helpers.php';

// Authentication helper
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
    
    // Check session timeout using AuthMiddleware
    try {
        require_once dirname(__DIR__) . '/includes/AuthMiddleware.php';
        
        $db = new Database();
        $conn = $db->getConnection();
        $auth = new AuthMiddleware($conn);
        
        // Check only session timeout, not other middleware features
        if (!$auth->checkSessionTimeout()) {
            // Session timeout - redirect to login
            header('Location: ' . BASE_URL . 'login.php?timeout=1');
            exit();
        }
        
    } catch (Exception $e) {
        // Log error but continue - don't block access due to middleware issues
        error_log("Session validation failed in requireLogin: " . $e->getMessage());
    }
    
    // Check subscription status for logged in users (skip for super_admin and root users)
    if (isset($_SESSION['company_id']) && $_SESSION['company_id'] && 
        !in_array($_SESSION['user_role'] ?? '', ['super_admin', 'root'])) {
        
        try {
            require_once dirname(__DIR__) . '/includes/SubscriptionMiddleware.php';
            
            $db = new Database();
            $conn = $db->getConnection();
            $subscription_middleware = new SubscriptionMiddleware($conn);
            
            // Handle subscription check
            if (!$subscription_middleware->handle()) {
                // Subscription check failed - user has been redirected
                exit();
            }
            
        } catch (Exception $e) {
            // Log error but don't block access to prevent system lockout
            error_log("Subscription check failed in requireLogin: " . $e->getMessage());
        }
    }
}

function requireActiveSubscriptionAPI() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Check subscription status for API requests
    if (isset($_SESSION['company_id']) && $_SESSION['company_id'] && 
        !in_array($_SESSION['user_role'] ?? '', ['super_admin', 'root'])) {
        
        try {
            require_once dirname(__DIR__) . '/includes/SubscriptionMiddleware.php';
            
            $db = new Database();
            $conn = $db->getConnection();
            
            SubscriptionMiddleware::requireActiveSubscriptionAPI($conn);
            
        } catch (Exception $e) {
            // Log error but don't block access to prevent system lockout
            error_log("API subscription check failed: " . $e->getMessage());
        }
    }
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function isRootUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin' && 
           (!isset($_SESSION['company_id']) || $_SESSION['company_id'] === null);
}

function isOfficer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'officer';
}

function getCurrentCompanyId() {
    // Super admin has no company - they manage all companies
    if (isSuperAdmin()) {
        return null;
    }
    return $_SESSION['company_id'] ?? null;
}

function getCompanyFilterForQuery() {
    // Super admin sees all companies, others see only their company
    if (isSuperAdmin()) {
        return null; // No filter - see all companies
    }
    return getCurrentCompanyId();
}

function addCompanyFilter($query, $params = [], $tableAlias = '') {
    // Super admin sees all data, others only their company
    if (isSuperAdmin()) {
        return [$query, $params];
    }
    
    $company_id = getCurrentCompanyId();
    if ($company_id) {
        $company_column = $tableAlias ? "$tableAlias.company_id" : "company_id";
        
        // Check if the query already has a WHERE clause
        if (stripos($query, 'WHERE') !== false) {
            $query .= " AND $company_column = ?";
        } else {
            $query .= " WHERE $company_column = ?";
        }
        $params[] = $company_id;
    }
    
    return [$query, $params];
}

function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function requireCompanyAdmin() {
    if (!isCompanyAdmin() && !isSuperAdmin()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}
?>
