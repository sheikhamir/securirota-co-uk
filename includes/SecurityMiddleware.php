<?php
/**
 * Security Middleware
 * Provides automatic security checks for API endpoints
 */

require_once __DIR__ . '/SecurityManager.php';

class SecurityMiddleware {
    private $security;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->security = new SecurityManager($pdo);
    }
    
    /**
     * Apply security checks to API requests
     */
    public function applySecurityChecks($options = []) {
        $defaults = [
            'rate_limit' => true,
            'csrf_protection' => true,
            'session_validation' => true,
            'permission_check' => null,
            'company_isolation' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        try {
            // Rate limiting
            if ($options['rate_limit']) {
                $this->checkRateLimit();
            }
            
            // Session validation
            if ($options['session_validation']) {
                $this->validateSession();
            }
            
            // CSRF protection for POST/PUT/DELETE requests
            if ($options['csrf_protection'] && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
                $this->validateCSRF();
            }
            
            // Permission checking
            if ($options['permission_check']) {
                $this->checkPermission($options['permission_check']);
            }
            
            // Company isolation
            if ($options['company_isolation']) {
                $this->enforceCompanyIsolation();
            }
            
            // Log the API access
            $this->logAPIAccess();
            
        } catch (SecurityException $e) {
            $this->handleSecurityViolation($e);
        }
    }
    
    private function checkRateLimit() {
        $identifier = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
        $result = $this->security->checkRateLimit($identifier);
        
        if (!$result['allowed']) {
            throw new SecurityException('Rate limit exceeded', 429);
        }
        
        // Add rate limit headers
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        if (isset($result['reset_time'])) {
            header('X-RateLimit-Reset: ' . $result['reset_time']);
        }
    }
    
    private function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            throw new SecurityException('Authentication required', 401);
        }
        
        // Check session security
        if (!$this->security->validateSession()) {
            throw new SecurityException('Session invalid', 401);
        }
        
        // Update session activity
        $this->updateSessionActivity();
    }
    
    private function validateCSRF() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!$this->security->validateCSRFToken($token)) {
            throw new SecurityException('CSRF token invalid', 403);
        }
    }
    
    private function checkPermission($permission) {
        if (!isset($_SESSION['user_id'])) {
            throw new SecurityException('Authentication required', 401);
        }
        
        if (!$this->security->checkPermission($_SESSION['user_id'], $permission, $_SESSION['company_id'] ?? null)) {
            throw new SecurityException('Permission denied', 403);
        }
    }
    
    private function enforceCompanyIsolation() {
        // Super admin can access everything
        if ($_SESSION['role'] === 'super_admin') {
            return;
        }
        
        // Ensure user can only access their company's data
        if (isset($_POST['company_id']) && $_POST['company_id'] != $_SESSION['company_id']) {
            throw new SecurityException('Company isolation violation', 403);
        }
        
        if (isset($_GET['company_id']) && $_GET['company_id'] != $_SESSION['company_id']) {
            throw new SecurityException('Company isolation violation', 403);
        }
    }
    
    private function logAPIAccess() {
        $stmt = $this->pdo->prepare("
            INSERT INTO api_access_log (
                user_id, ip_address, endpoint, method, user_agent, 
                company_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SESSION['company_id'] ?? null
        ]);
    }
    
    private function updateSessionActivity() {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare("
                UPDATE user_sessions 
                SET last_activity = NOW() 
                WHERE user_id = ? AND session_id = ? AND is_active = 1
            ");
            $stmt->execute([$_SESSION['user_id'], session_id()]);
        }
    }
    
    private function handleSecurityViolation($exception) {
        // Log the security event
        $this->security->logSecurityEvent('security_violation', $_SESSION['username'] ?? $_SERVER['REMOTE_ADDR'], [
            'violation_type' => $exception->getMessage(),
            'endpoint' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ]);
        
        // Return appropriate HTTP response
        http_response_code($exception->getCode());
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);
        
        exit();
    }
    
    /**
     * Generate CSRF token for forms
     */
    public function getCSRFToken() {
        return $this->security->generateCSRFToken();
    }
    
    /**
     * Get rate limit info for current user
     */
    public function getRateLimitInfo() {
        $identifier = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
        return $this->security->checkRateLimit($identifier);
    }
}

/**
 * Custom exception for security violations
 */
class SecurityException extends Exception {
    public function __construct($message = "", $code = 403, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Helper function to quickly apply security middleware
 */
function applySecurityMiddleware($pdo, $options = []) {
    $middleware = new SecurityMiddleware($pdo);
    $middleware->applySecurityChecks($options);
    return $middleware;
}
?>
