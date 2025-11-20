<?php
/**
 * Subscription Middleware
 * Checks company subscription status and handles expired subscriptions
 */

class SubscriptionMiddleware {
    private $pdo;
    private $config;
    
    public function __construct($pdo, $config = []) {
        $this->pdo = $pdo;
        $this->config = array_merge([
            'exempt_paths' => [
                '/login.php',
                '/logout.php',
                '/subscription_expired.php',
                '/api/login',
                '/api/logout',
                '/error.php',
                '/favicon.ico',
                '/assets/',
                '/api/system_stats.php' // Allow system stats for monitoring
            ],
            'exempt_roles' => ['super_admin', 'root'], // These roles can access even with expired subscription
            'grace_period_warning_days' => 7 // Show warning when within this many days of expiry
        ], $config);
    }
    
    /**
     * Main subscription check handler
     */
    public function handle($request_path = null) {
        $request_path = $request_path ?? $_SERVER['REQUEST_URI'];
        
        // Skip check for exempt paths
        if ($this->isExemptPath($request_path)) {
            return true;
        }
        
        // Skip check if no user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            return true;
        }
        
        // Skip check for exempt roles
        if ($this->isExemptRole()) {
            return true;
        }
        
        // Get company subscription status
        $subscription_status = $this->getCompanySubscriptionStatus($_SESSION['company_id']);
        
        if (!$subscription_status) {
            return $this->handleError('Unable to verify subscription status');
        }
        
        // Handle expired subscription
        if (!$subscription_status['is_active']) {
            return $this->handleExpiredSubscription($subscription_status, $request_path);
        }
        
        // Show warning if subscription is expiring soon
        if ($subscription_status['expires_soon']) {
            $this->addExpiryWarning($subscription_status);
        }
        
        return true;
    }
    
    /**
     * Get company subscription status
     */
    private function getCompanySubscriptionStatus($company_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.subscription_status,
                    c.subscription_expires_at,
                    c.subscription_grace_period_days,
                    c.last_subscription_check,
                    CASE 
                        WHEN c.subscription_expires_at IS NULL THEN 1
                        WHEN c.subscription_expires_at > NOW() THEN 1
                        WHEN c.subscription_grace_period_days > 0 AND 
                             DATE_ADD(c.subscription_expires_at, INTERVAL c.subscription_grace_period_days DAY) > NOW() THEN 1
                        ELSE 0
                    END as is_active,
                    CASE 
                        WHEN c.subscription_expires_at IS NULL THEN 0
                        WHEN DATEDIFF(c.subscription_expires_at, NOW()) <= ? THEN 1
                        ELSE 0
                    END as expires_soon,
                    GREATEST(0, DATEDIFF(c.subscription_expires_at, NOW())) as days_until_expiry
                FROM companies c 
                WHERE c.id = ? AND c.status = 'active'
            ");
            
            $stmt->execute([$this->config['grace_period_warning_days'], $company_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update last subscription check
                $this->updateLastSubscriptionCheck($company_id);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Subscription check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last subscription check timestamp
     */
    private function updateLastSubscriptionCheck($company_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE companies 
                SET last_subscription_check = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$company_id]);
        } catch (Exception $e) {
            error_log("Failed to update subscription check: " . $e->getMessage());
        }
    }
    
    /**
     * Handle expired subscription
     */
    private function handleExpiredSubscription($subscription_status, $request_path) {
        // Log the expired subscription access attempt
        $this->logExpiredSubscriptionAttempt($subscription_status, $request_path);
        
        // Clear sensitive session data but keep user info for the expired page
        $_SESSION['subscription_expired'] = true;
        $_SESSION['company_subscription_status'] = $subscription_status;
        
        // Redirect based on request type
        if ($this->isAPIRequest($request_path)) {
            http_response_code(402); // Payment Required
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'subscription_expired',
                'message' => 'Company subscription has expired',
                'company_name' => $subscription_status['name'],
                'expired_on' => $subscription_status['subscription_expires_at'],
                'redirect_url' => '/rota/subscription_expired.php'
            ]);
        } else {
            header('Location: /rota/subscription_expired.php');
        }
        
        return false;
    }
    
    /**
     * Add expiry warning to session
     */
    private function addExpiryWarning($subscription_status) {
        $_SESSION['subscription_warning'] = [
            'days_remaining' => $subscription_status['days_until_expiry'],
            'expires_at' => $subscription_status['subscription_expires_at'],
            'company_name' => $subscription_status['name']
        ];
    }
    
    /**
     * Log expired subscription access attempt
     */
    private function logExpiredSubscriptionAttempt($subscription_status, $request_path) {
        try {
            require_once __DIR__ . '/ActivityLogger.php';
            $logger = new ActivityLogger($this->pdo);
            
            $description = "Access attempted with expired subscription";
            $metadata = [
                'company_id' => $subscription_status['id'],
                'company_name' => $subscription_status['name'],
                'expired_on' => $subscription_status['subscription_expires_at'],
                'requested_path' => $request_path,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            
            $logger->logSystemAction($_SESSION['user_id'], 'subscription_expired_access', $description, $metadata);
            
        } catch (Exception $e) {
            error_log("Failed to log expired subscription attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Check if path is exempt from subscription checks
     */
    private function isExemptPath($request_path) {
        foreach ($this->config['exempt_paths'] as $exempt_path) {
            if (strpos($request_path, $exempt_path) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if current user role is exempt from subscription checks
     */
    private function isExemptRole() {
        $user_role = $_SESSION['user_role'] ?? '';
        return in_array($user_role, $this->config['exempt_roles']);
    }
    
    /**
     * Check if request is an API request
     */
    private function isAPIRequest($request_path) {
        return strpos($request_path, '/api/') === 0 || 
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    /**
     * Handle subscription check errors
     */
    private function handleError($message) {
        error_log("Subscription middleware error: " . $message);
        
        // In case of error, allow access but log the issue
        // This prevents the system from being completely locked out due to database issues
        return true;
    }
    
    /**
     * Get subscription status for a specific company (utility method)
     */
    public static function getSubscriptionStatus($pdo, $company_id) {
        $middleware = new self($pdo);
        return $middleware->getCompanySubscriptionStatus($company_id);
    }
    
    /**
     * Check if company subscription is active (utility method)
     */
    public static function isSubscriptionActive($pdo, $company_id) {
        $status = self::getSubscriptionStatus($pdo, $company_id);
        return $status ? $status['is_active'] : false;
    }
    
    /**
     * API-specific subscription check that returns JSON response
     */
    public static function requireActiveSubscriptionAPI($pdo) {
        // Skip check if no user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            return true;
        }
        
        // Skip check for exempt roles
        $user_role = $_SESSION['user_role'] ?? '';
        if (in_array($user_role, ['super_admin', 'root'])) {
            return true;
        }
        
        $middleware = new self($pdo);
        $subscription_status = $middleware->getCompanySubscriptionStatus($_SESSION['company_id']);
        
        if (!$subscription_status || !$subscription_status['is_active']) {
            http_response_code(402); // Payment Required
            echo json_encode([
                'success' => false,
                'error' => 'subscription_expired',
                'message' => 'Company subscription has expired',
                'company_name' => $subscription_status['name'] ?? 'Unknown',
                'expired_on' => $subscription_status['subscription_expires_at'] ?? null,
                'redirect_url' => '/rota/subscription_expired.php'
            ]);
            
            // Log the API access attempt with expired subscription
            if ($subscription_status) {
                try {
                    require_once __DIR__ . '/ActivityLogger.php';
                    $logger = new ActivityLogger($pdo);
                    
                    $description = "API access attempted with expired subscription";
                    $metadata = [
                        'company_id' => $subscription_status['id'],
                        'company_name' => $subscription_status['name'],
                        'expired_on' => $subscription_status['subscription_expires_at'],
                        'requested_path' => $_SERVER['REQUEST_URI'] ?? '',
                        'api_endpoint' => true,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                    ];
                    
                    $logger->logSystemAction($_SESSION['user_id'], 'api_subscription_expired_access', $description, $metadata);
                    
                } catch (Exception $e) {
                    error_log("Failed to log expired subscription API attempt: " . $e->getMessage());
                }
            }
            
            exit();
        }
        
        return true;
    }
}
?>