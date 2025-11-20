<?php
/**
 * Security Manager Class
 * Handles rate limiting, security validation, and access controls
 */

class SecurityManager {
    private $pdo;
    private $redis;
    private $config;
    
    public function __construct($pdo, $config = []) {
        $this->pdo = $pdo;
        
        // Load security settings from database
        $dbSettings = $this->loadSecuritySettings();
        
        $this->config = array_merge([
            'rate_limit_window' => 3600, // 1 hour
            'max_requests_per_hour' => $dbSettings['rate_limit_per_hour'] ?? 1000,
            'max_login_attempts' => $dbSettings['max_login_attempts'] ?? 5,
            'lockout_duration' => ($dbSettings['lockout_duration_minutes'] ?? 15) * 60, // Convert to seconds
            'session_timeout' => ($dbSettings['session_timeout_minutes'] ?? 60) * 60, // Convert to seconds
            'csrf_token_lifetime' => 3600,
            'password_min_length' => $dbSettings['password_min_length'] ?? 8,
            'require_password_complexity' => $dbSettings['password_complexity'] === 'true'
        ], $config);
        
        // Try to initialize Redis for better performance
        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
            }
        } catch (Exception $e) {
            $this->redis = null; // Fallback to database storage
        }
    }
    
    /**
     * Load security settings from database
     */
    private function loadSecuritySettings() {
        try {
            $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM security_settings");
            $stmt->execute();
            $settings = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (Exception $e) {
            // If there's an error loading settings, return empty array to use defaults
            return [];
        }
    }
    
    /**
     * Check API rate limiting
     */
    public function checkRateLimit($identifier, $limit = null, $window = null) {
        $limit = $limit ?? $this->config['max_requests_per_hour'];
        $window = $window ?? $this->config['rate_limit_window'];
        
        $key = "rate_limit:" . $identifier;
        $current_time = time();
        
        if ($this->redis) {
            // Use Redis for rate limiting
            $current_count = $this->redis->get($key);
            if ($current_count === false) {
                $this->redis->setex($key, $window, 1);
                return ['allowed' => true, 'remaining' => $limit - 1];
            } else {
                if ($current_count >= $limit) {
                    return ['allowed' => false, 'remaining' => 0, 'reset_time' => $current_time + $this->redis->ttl($key)];
                }
                $this->redis->incr($key);
                return ['allowed' => true, 'remaining' => $limit - ($current_count + 1)];
            }
        } else {
            // Fallback to database
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as request_count 
                FROM rate_limits 
                WHERE identifier = ? AND created_at > ?
            ");
            $stmt->execute([$identifier, date('Y-m-d H:i:s', $current_time - $window)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['request_count'] >= $limit) {
                return ['allowed' => false, 'remaining' => 0];
            }
            
            // Log this request
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (identifier, created_at) VALUES (?, NOW())
            ");
            $stmt->execute([$identifier]);
            
            return ['allowed' => true, 'remaining' => $limit - ($result['request_count'] + 1)];
        }
    }
    
    /**
     * Check login attempts and lockout
     */
    public function checkLoginAttempts($identifier) {
        $key = "login_attempts:" . $identifier;
        $lockout_key = "lockout:" . $identifier;
        
        if ($this->redis) {
            $lockout = $this->redis->get($lockout_key);
            if ($lockout) {
                return ['locked' => true, 'unlock_time' => time() + $this->redis->ttl($lockout_key)];
            }
            
            $attempts = $this->redis->get($key) ?: 0;
            return ['locked' => false, 'attempts' => $attempts, 'remaining' => $this->config['max_login_attempts'] - $attempts];
        } else {
            // Database fallback
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM failed_login_attempts 
                WHERE identifier = ? AND created_at > ?
            ");
            $stmt->execute([$identifier, date('Y-m-d H:i:s', time() - $this->config['lockout_duration'])]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['attempts'] >= $this->config['max_login_attempts']) {
                return ['locked' => true];
            }
            
            return ['locked' => false, 'attempts' => $result['attempts'], 'remaining' => $this->config['max_login_attempts'] - $result['attempts']];
        }
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($identifier, $details = []) {
        $key = "login_attempts:" . $identifier;
        $lockout_key = "lockout:" . $identifier;
        
        if ($this->redis) {
            $attempts = $this->redis->incr($key);
            $this->redis->expire($key, $this->config['lockout_duration']);
            
            if ($attempts >= $this->config['max_login_attempts']) {
                $this->redis->setex($lockout_key, $this->config['lockout_duration'], time());
            }
        } else {
            // Database fallback
            $stmt = $this->pdo->prepare("
                INSERT INTO failed_login_attempts (identifier, ip_address, user_agent, details, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $identifier,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($details)
            ]);
        }
        
        // Log security event
        $this->logSecurityEvent('failed_login', $identifier, $details);
    }
    
    /**
     * Generate and validate CSRF tokens
     */
    public function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + $this->config['csrf_token_lifetime'];
        
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $_SESSION['csrf_tokens'][$token] = $expiry;
        
        // Clean expired tokens
        foreach ($_SESSION['csrf_tokens'] as $t => $exp) {
            if ($exp < time()) {
                unset($_SESSION['csrf_tokens'][$t]);
            }
        }
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }
        
        if ($_SESSION['csrf_tokens'][$token] < time()) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }
        
        // Token is valid, remove it (one-time use)
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < $this->config['password_min_length']) {
            $errors[] = "Password must be at least {$this->config['password_min_length']} characters long";
        }
        
        if ($this->config['require_password_complexity']) {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Password must contain at least one uppercase letter";
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = "Password must contain at least one lowercase letter";
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Password must contain at least one number";
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = "Password must contain at least one special character";
            }
        }
        
        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate input data
     */
    public function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule_set) {
            $value = $data[$field] ?? null;
            
            foreach ($rule_set as $rule => $param) {
                switch ($rule) {
                    case 'required':
                        if ($param && empty($value)) {
                            $errors[$field][] = ucfirst($field) . " is required";
                        }
                        break;
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = ucfirst($field) . " must be a valid email address";
                        }
                        break;
                    case 'min_length':
                        if ($value && strlen($value) < $param) {
                            $errors[$field][] = ucfirst($field) . " must be at least {$param} characters long";
                        }
                        break;
                    case 'max_length':
                        if ($value && strlen($value) > $param) {
                            $errors[$field][] = ucfirst($field) . " must not exceed {$param} characters";
                        }
                        break;
                    case 'numeric':
                        if ($value && !is_numeric($value)) {
                            $errors[$field][] = ucfirst($field) . " must be a number";
                        }
                        break;
                    case 'in':
                        if ($value && !in_array($value, $param)) {
                            $errors[$field][] = ucfirst($field) . " must be one of: " . implode(', ', $param);
                        }
                        break;
                }
            }
        }
        
        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
    
    /**
     * Check user permissions
     */
    public function checkPermission($user_id, $permission, $company_id = null) {
        // Get user role and company
        $stmt = $this->pdo->prepare("SELECT role, company_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Super admin has all permissions
        if ($user['role'] === 'super_admin') {
            return true;
        }
        
        // Check company context
        if ($company_id && $user['company_id'] != $company_id) {
            return false;
        }
        
        // Define role permissions
        $permissions = [
            'admin' => [
                'view_dashboard', 'manage_users', 'manage_officers', 'manage_sites', 
                'manage_shifts', 'view_reports', 'manage_company_settings', 'view_activity_logs'
            ],
            'manager' => [
                'view_dashboard', 'manage_officers', 'manage_sites', 'manage_shifts', 'view_reports'
            ],
            'officer' => [
                'view_dashboard', 'view_shifts', 'checkin_checkout'
            ]
        ];
        
        return in_array($permission, $permissions[$user['role']] ?? []);
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($event_type, $identifier, $details = []) {
        $stmt = $this->pdo->prepare("
            INSERT INTO security_logs (event_type, identifier, ip_address, user_agent, details, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $event_type,
            $identifier,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($details)
        ]);
    }
    
    /**
     * Check for suspicious activity
     */
    public function detectSuspiciousActivity($user_id) {
        $suspicious_indicators = [];
        
        // Check for multiple failed logins
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as failed_attempts 
            FROM failed_login_attempts 
            WHERE identifier = ? AND created_at > ?
        ");
        $stmt->execute([$user_id, date('Y-m-d H:i:s', time() - 3600)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['failed_attempts'] > 3) {
            $suspicious_indicators[] = 'multiple_failed_logins';
        }
        
        // Check for unusual access patterns
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT ip_address 
            FROM security_logs 
            WHERE identifier = ? AND created_at > ?
        ");
        $stmt->execute([$user_id, date('Y-m-d H:i:s', time() - 86400)]);
        $ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($ips) > 5) {
            $suspicious_indicators[] = 'multiple_ip_addresses';
        }
        
        return $suspicious_indicators;
    }
    
    /**
     * Get security configuration
     */
    public function getConfig() {
        return $this->config;
    }
}
?>
