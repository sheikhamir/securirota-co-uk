<?php
/**
 * API Security Middleware
 * Provides enhanced security for API endpoints
 */

require_once __DIR__ . '/../includes/SecurityManager.php';
require_once __DIR__ . '/../includes/AuthMiddleware.php';

class APISecurityMiddleware {
    private $security;
    private $auth;
    private $config;
    
    public function __construct($pdo, $config = []) {
        $this->security = new SecurityManager($pdo, $config);
        $this->auth = new AuthMiddleware($pdo, $config);
        $this->config = array_merge([
            'api_rate_limit' => 1000,
            'require_api_key' => false,
            'allowed_origins' => ['*'],
            'max_request_size' => 10485760, // 10MB
            'require_https' => false
        ], $config);
    }
    
    /**
     * Handle API request security
     */
    public function handleAPIRequest() {
        try {
            // Set CORS headers
            $this->setCORSHeaders();
            
            // Handle preflight requests
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit();
            }
            
            // 1. HTTPS enforcement
            if ($this->config['require_https'] && !$this->isHTTPS()) {
                $this->apiError(426, 'HTTPS required');
                return false;
            }
            
            // 2. Request size check
            if (!$this->checkRequestSize()) {
                return false;
            }
            
            // 3. API rate limiting
            if (!$this->checkAPIRateLimit()) {
                return false;
            }
            
            // 4. API key validation (if required)
            if ($this->config['require_api_key'] && !$this->validateAPIKey()) {
                return false;
            }
            
            // 5. Input validation and sanitization
            if (!$this->validateAPIInput()) {
                return false;
            }
            
            // 6. Authentication check
            if (!$this->checkAPIAuthentication()) {
                return false;
            }
            
            // 7. Log API access
            $this->logAPIAccess();
            
            return true;
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('api_error', $_SESSION['user_id'] ?? 'anonymous', [
                'error' => $e->getMessage(),
                'endpoint' => $_SERVER['REQUEST_URI']
            ]);
            
            $this->apiError(500, 'Internal server error');
            return false;
        }
    }
    
    /**
     * Set CORS headers
     */
    private function setCORSHeaders() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $this->config['allowed_origins']) || in_array($origin, $this->config['allowed_origins'])) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
    
    /**
     * Check request size
     */
    private function checkRequestSize() {
        $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($content_length > $this->config['max_request_size']) {
            $this->apiError(413, 'Request too large');
            return false;
        }
        
        return true;
    }
    
    /**
     * API-specific rate limiting
     */
    private function checkAPIRateLimit() {
        $identifier = $this->getAPIIdentifier();
        $rate_check = $this->security->checkRateLimit($identifier, $this->config['api_rate_limit']);
        
        if (!$rate_check['allowed']) {
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($rate_check['reset_time'] ?? time() + 3600));
            
            $this->apiError(429, 'Rate limit exceeded', [
                'retry_after' => $rate_check['reset_time'] ?? time() + 3600
            ]);
            return false;
        }
        
        header('X-RateLimit-Remaining: ' . $rate_check['remaining']);
        return true;
    }
    
    /**
     * Validate API key
     */
    private function validateAPIKey() {
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
        
        if (!$api_key) {
            $this->apiError(401, 'API key required');
            return false;
        }
        
        // Hash the API key for database lookup
        $api_key_hash = hash('sha256', $api_key);
        
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT at.*, u.username, u.company_id 
            FROM api_tokens at
            JOIN users u ON at.user_id = u.id
            WHERE at.token_hash = ? 
            AND at.is_active = TRUE 
            AND (at.expires_at IS NULL OR at.expires_at > NOW())
        ");
        $stmt->execute([$api_key_hash]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$token) {
            $this->security->logSecurityEvent('invalid_api_key', 'anonymous', [
                'api_key_hash' => $api_key_hash,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            $this->apiError(401, 'Invalid API key');
            return false;
        }
        
        // Update last used timestamp
        $stmt = $pdo->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?");
        $stmt->execute([$token['id']]);
        
        // Set user context
        $_SESSION['api_user_id'] = $token['user_id'];
        $_SESSION['api_company_id'] = $token['company_id'];
        $_SESSION['api_permissions'] = json_decode($token['permissions'], true);
        
        return true;
    }
    
    /**
     * Validate API input
     */
    private function validateAPIInput() {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Validate JSON input
        if (strpos($content_type, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            
            if (!empty($input)) {
                $json = json_decode($input, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->apiError(400, 'Invalid JSON format');
                    return false;
                }
                
                // Store parsed JSON for later use
                $_POST['json_data'] = $json;
            }
        }
        
        // Validate required parameters based on endpoint
        $endpoint = $this->getEndpoint();
        $required_params = $this->getRequiredParameters($endpoint);
        
        foreach ($required_params as $param) {
            if (!isset($_POST[$param]) && !isset($_GET[$param]) && !isset($_POST['json_data'][$param])) {
                $this->apiError(400, "Missing required parameter: $param");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check API authentication
     */
    private function checkAPIAuthentication() {
        // API key authentication already handled above
        if ($this->config['require_api_key']) {
            return true;
        }
        
        // Session-based authentication
        if (!isset($_SESSION['user_id'])) {
            $this->apiError(401, 'Authentication required');
            return false;
        }
        
        return true;
    }
    
    /**
     * Log API access
     */
    private function logAPIAccess() {
        $this->security->logSecurityEvent('api_access', $_SESSION['user_id'] ?? $_SESSION['api_user_id'] ?? 'anonymous', [
            'endpoint' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'api_key_used' => isset($_SESSION['api_user_id'])
        ]);
    }
    
    /**
     * Get API identifier for rate limiting
     */
    private function getAPIIdentifier() {
        if (isset($_SESSION['api_user_id'])) {
            return 'api_user_' . $_SESSION['api_user_id'];
        } elseif (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        } else {
            return 'ip_' . $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Get endpoint name
     */
    private function getEndpoint() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return basename($path, '.php');
    }
    
    /**
     * Get required parameters for endpoint
     */
    private function getRequiredParameters($endpoint) {
        $requirements = [
            'create_shift' => ['site_id', 'officer_id', 'start_time', 'end_time'],
            'update_shift' => ['shift_id'],
            'users' => [],
            'get_shift' => ['shift_id'],
            // Add more endpoint requirements as needed
        ];
        
        return $requirements[$endpoint] ?? [];
    }
    
    /**
     * Check if HTTPS
     */
    private function isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Send API error response
     */
    private function apiError($code, $message, $data = []) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit();
    }
    
    /**
     * Check API permission
     */
    public function checkAPIPermission($permission) {
        if (isset($_SESSION['api_permissions'])) {
            return in_array($permission, $_SESSION['api_permissions']);
        }
        
        // Fall back to user permission check
        if (isset($_SESSION['user_id'])) {
            return $this->security->checkPermission($_SESSION['user_id'], $permission);
        }
        
        return false;
    }
}

// Initialize API security for all API requests
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    global $pdo;
    $api_security = new APISecurityMiddleware($pdo);
    
    if (!$api_security->handleAPIRequest()) {
        exit(); // Security check failed, response already sent
    }
}
?>
