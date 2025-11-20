<?php
/**
 * Enhanced Authentication Middleware
 * Provides comprehensive security checks for all requests
 */

require_once __DIR__ . '/SecurityManager.php';

class AuthMiddleware {
    private $security;
    private $config;
    private $pdo;
    
    public function __construct($pdo, $config = []) {
        $this->pdo = $pdo;
        $this->security = new SecurityManager($pdo, $config);
        
        // Merge SecurityManager config (which includes database settings) with AuthMiddleware config
        $securityConfig = $this->security->getConfig();
        $this->config = array_merge([
            'csrf_exempt_paths' => ['/api/login', '/api/register'],
            'rate_limit_exempt_roles' => ['super_admin'],
            'api_paths' => ['/api/'],
            'require_https' => false // Set to true in production
        ], $securityConfig, $config);
    }
    
    /**
     * Main middleware handler
     */
    public function handle($request_path = null) {
        $request_path = $request_path ?? $_SERVER['REQUEST_URI'];
        
        try {
            // 1. HTTPS enforcement (if enabled)
            if ($this->config['require_https'] && !$this->isHTTPS()) {
                $this->redirectToHTTPS();
                return false;
            }
            
            // 2. Rate limiting check
            if (!$this->checkRateLimit($request_path)) {
                return false;
            }
            
            // 3. CSRF protection
            if (!$this->checkCSRF($request_path)) {
                return false;
            }
            
            // 4. Session validation
            if (!$this->validateSession()) {
                return false;
            }
            
            // 5. Input sanitization
            $this->sanitizeInputs();
            
            // 6. Security logging
            $this->logRequest($request_path);
            
            return true;
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('middleware_error', $_SESSION['user_id'] ?? 'anonymous', [
                'error' => $e->getMessage(),
                'path' => $request_path
            ]);
            
            http_response_code(500);
            if ($this->isAPIRequest($request_path)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Security validation failed']);
            } else {
                header('Location: /error.php?type=security');
            }
            return false;
        }
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit($request_path) {
        // Skip rate limiting for exempt roles
        if (isset($_SESSION['role']) && in_array($_SESSION['role'], $this->config['rate_limit_exempt_roles'])) {
            return true;
        }
        
        // Determine identifier (user ID or IP)
        $identifier = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
        
        // Different limits for different types of requests
        $limit = $this->isAPIRequest($request_path) ? 1000 : 500; // Higher limit for API
        
        $rate_check = $this->security->checkRateLimit($identifier, $limit);
        
        if (!$rate_check['allowed']) {
            http_response_code(429);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($rate_check['reset_time'] ?? time() + 3600));
            
            if ($this->isAPIRequest($request_path)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $rate_check['reset_time'] ?? time() + 3600
                ]);
            } else {
                header('Location: /error.php?type=rate_limit');
            }
            return false;
        }
        
        // Add rate limit headers
        header('X-RateLimit-Remaining: ' . $rate_check['remaining']);
        
        return true;
    }
    
    /**
     * CSRF protection
     */
    private function checkCSRF($request_path) {
        // Skip CSRF for GET requests and exempt paths
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        foreach ($this->config['csrf_exempt_paths'] as $exempt_path) {
            if (strpos($request_path, $exempt_path) !== false) {
                return true;
            }
        }
        
        // Check for CSRF token
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !$this->security->validateCSRFToken($token)) {
            http_response_code(403);
            
            $this->security->logSecurityEvent('csrf_violation', $_SESSION['user_id'] ?? 'anonymous', [
                'path' => $request_path,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            if ($this->isAPIRequest($request_path)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CSRF token validation failed']);
            } else {
                header('Location: /error.php?type=csrf');
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate user session
     */
    private function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return true; // Not logged in, no session to validate
        }
        
        // Check session timeout
        $timeout = $this->config['session_timeout'] ?? 3600;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Optional: Validate session in database (only if user_sessions table is being used)
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_sessions 
                WHERE session_id = ? AND user_id = ? AND is_active = TRUE AND expires_at > NOW()
            ");
            $stmt->execute([session_id(), $_SESSION['user_id']]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If we found a session record, validate it. If no record exists, skip this check
            if ($session !== false) {
                // Update session activity if record exists
                $stmt = $this->pdo->prepare("
                    UPDATE user_sessions 
                    SET last_activity = NOW() 
                    WHERE session_id = ?
                ");
                $stmt->execute([session_id()]);
            }
            // If no session record exists, we just rely on PHP session timeout check above
            
        } catch (Exception $e) {
            // If there's a database error, don't fail the session - just log it
            error_log("Database session validation error: " . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Sanitize all inputs
     */
    private function sanitizeInputs() {
        // Sanitize GET parameters
        foreach ($_GET as $key => $value) {
            $_GET[$key] = $this->security->sanitizeInput($value);
        }
        
        // Sanitize POST parameters (preserve JSON for API)
        if (!$this->isAPIRequest($_SERVER['REQUEST_URI'])) {
            foreach ($_POST as $key => $value) {
                $_POST[$key] = is_array($value) ? 
                    array_map([$this->security, 'sanitizeInput'], $value) : 
                    $this->security->sanitizeInput($value);
            }
        }
    }
    
    /**
     * Log security-relevant requests
     */
    private function logRequest($request_path) {
        // Log sensitive operations
        $sensitive_paths = ['/api/users', '/api/companies', '/api/migration', '/admin'];
        
        foreach ($sensitive_paths as $sensitive_path) {
            if (strpos($request_path, $sensitive_path) !== false) {
                $this->security->logSecurityEvent('sensitive_access', $_SESSION['user_id'] ?? 'anonymous', [
                    'path' => $request_path,
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                break;
            }
        }
    }
    
    /**
     * Check if request is HTTPS
     */
    private function isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Redirect to HTTPS
     */
    private function redirectToHTTPS() {
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirectURL");
        exit();
    }
    
    /**
     * Check if request is for API endpoint
     */
    private function isAPIRequest($path) {
        foreach ($this->config['api_paths'] as $api_path) {
            if (strpos($path, $api_path) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get CSRF token for forms
     */
    public function getCSRFToken() {
        return $this->security->generateCSRFToken();
    }
    
    /**
     * Check user permission
     */
    public function checkPermission($permission, $company_id = null) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return $this->security->checkPermission($_SESSION['user_id'], $permission, $company_id);
    }
    
    /**
     * Get configuration for testing
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Public method to check session timeout only
     */
    public function checkSessionTimeout() {
        return $this->validateSession();
    }
}

// Global middleware instance
$auth_middleware = null;

function initializeAuthMiddleware($pdo, $config = []) {
    global $auth_middleware;
    $auth_middleware = new AuthMiddleware($pdo, $config);
    return $auth_middleware;
}

function requireAuth($permission = null, $company_id = null) {
    global $auth_middleware;
    
    if (!isset($_SESSION['user_id'])) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
        } else {
            header('Location: /login.php');
        }
        exit();
    }
    
    if ($permission && $auth_middleware && !$auth_middleware->checkPermission($permission, $company_id)) {
        http_response_code(403);
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Insufficient permissions']);
        } else {
            header('Location: /error.php?type=permission');
        }
        exit();
    }
}

function getCSRFToken() {
    global $auth_middleware;
    return $auth_middleware ? $auth_middleware->getCSRFToken() : '';
}
?>
