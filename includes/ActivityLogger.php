<?php
/**
 * Activity Logger Class
 * Logs all user actions for audit trail and compliance
 */
class ActivityLogger {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Log an activity
     * @param int $user_id - ID of the user performing the action
     * @param string $action_type - Type of action being performed
     * @param string $entity_type - Type of entity being acted upon
     * @param int|null $entity_id - ID of the entity (optional)
     * @param string $description - Human readable description
     * @param array|null $metadata - Additional data (old values, new values, etc.)
     * @return bool - Success status
     */
    public function log($user_id, $action_type, $entity_type, $entity_id, $description, $metadata = null) {
        try {
            // Get user's company_id for multi-tenant logging
            $company_id = $this->getUserCompanyId($user_id);
            
            $stmt = $this->conn->prepare("
                INSERT INTO activity_log 
                (user_id, company_id, action_type, entity_type, entity_id, description, metadata, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ip_address = $this->getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadata_json = $metadata ? json_encode($metadata) : null;
            
            $result = $stmt->execute([
                $user_id,
                $company_id,
                $action_type,
                $entity_type,
                $entity_id,
                $description,
                $metadata_json,
                $ip_address,
                $user_agent
            ]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's company ID for logging context
     */
    private function getUserCompanyId($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT company_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['company_id'] : null;
        } catch (Exception $e) {
            error_log("Error getting user company ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log shift-related activities
     */
    public function logShiftAction($user_id, $action, $shift_id, $description, $metadata = null) {
        $action_types = [
            'create' => 'create_shift',
            'update' => 'update_shift',
            'delete' => 'delete_shift',
            'confirm' => 'confirm_shift',
            'reschedule' => 'reschedule_shift',
            'cancel' => 'cancel_shift'
        ];
        
        $action_type = $action_types[$action] ?? 'update_shift';
        return $this->log($user_id, $action_type, 'shift', $shift_id, $description, $metadata);
    }
    
    /**
     * Log client-related activities
     */
    public function logClientAction($user_id, $action, $client_id, $description, $metadata = null) {
        $action_types = [
            'create' => 'create_client',
            'update' => 'update_client',
            'delete' => 'delete_client'
        ];
        
        $action_type = $action_types[$action] ?? 'update_client';
        return $this->log($user_id, $action_type, 'client', $client_id, $description, $metadata);
    }
    
    /**
     * Log site-related activities
     */
    public function logSiteAction($user_id, $action, $site_id, $description, $metadata = null) {
        $action_types = [
            'create' => 'create_site',
            'update' => 'update_site',
            'delete' => 'delete_site'
        ];
        
        $action_type = $action_types[$action] ?? 'update_site';
        return $this->log($user_id, $action_type, 'site', $site_id, $description, $metadata);
    }
    
    /**
     * Log officer-related activities
     */
    public function logOfficerAction($user_id, $action, $officer_id, $description, $metadata = null) {
        $action_types = [
            'create' => 'create_officer',
            'update' => 'update_officer',
            'delete' => 'delete_officer'
        ];
        
        $action_type = $action_types[$action] ?? 'update_officer';
        return $this->log($user_id, $action_type, 'officer', $officer_id, $description, $metadata);
    }
    
    /**
     * Log user-related activities
     */
    public function logUserAction($user_id, $action, $target_user_id, $description, $metadata = null) {
        $action_types = [
            'create' => 'create_user',
            'update' => 'update_user',
            'delete' => 'delete_user'
        ];
        
        $action_type = $action_types[$action] ?? 'update_user';
        return $this->log($user_id, $action_type, 'user', $target_user_id, $description, $metadata);
    }
    
    /**
     * Log system activities (reports, invoices, etc.)
     */
    public function logSystemAction($user_id, $action, $description, $metadata = null) {
        $action_types = [
            'generate_invoice' => 'generate_invoice',
            'generate_report' => 'generate_report',
            'login' => 'login',
            'logout' => 'logout'
        ];
        
        $action_type = $action_types[$action] ?? $action;
        return $this->log($user_id, $action_type, 'system', null, $description, $metadata);
    }
    
    /**
     * Get recent activities for a user
     */
    public function getUserActivities($user_id, $limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM activity_log_view 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent activities for an entity
     */
    public function getEntityActivities($entity_type, $entity_id, $limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM activity_log_view 
                WHERE entity_type = ? AND entity_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$entity_type, $entity_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all recent activities (admin view) - with company filtering for security
     */
    public function getAllActivities($limit = 100, $action_type = null, $search = null, $date_from = null, $date_to = null) {
        try {
            $sql = "SELECT * FROM activity_log_view ";
            $params = [];
            $whereConditions = [];
            
            // Add company filtering based on user role
            $this->addCompanyFiltering($whereConditions, $params);
            
            if ($action_type) {
                $whereConditions[] = "action_type = ?";
                $params[] = $action_type;
            }
            
            if ($search) {
                $whereConditions[] = "(description LIKE ? OR username LIKE ? OR full_name LIKE ? OR metadata LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($date_from) {
                $whereConditions[] = "DATE(created_at) >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $whereConditions[] = "DATE(created_at) <= ?";
                $params[] = $date_to;
            }
            
            if (!empty($whereConditions)) {
                $sql .= "WHERE " . implode(" AND ", $whereConditions) . " ";
            }
            
            $sql .= "ORDER BY created_at DESC LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            
            // Bind parameters with correct types
            $paramIndex = 1;
            foreach ($params as $param) {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
            }
            $stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client's real IP address
     */
    private function getClientIP() {
        // Check for shared internet/proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for remote address through proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Check for remote address
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    action_type,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM activity_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action_type, DATE(created_at)
                ORDER BY created_at DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get paginated activities with sorting
     */
    public function getAllActivitiesPaginated($page = 1, $per_page = 20, $action_type = null, $search = null, $date_from = null, $date_to = null, $sort_by = 'created_at', $sort_order = 'desc') {
        try {
            // Validate sort parameters
            $allowed_sort_fields = ['created_at', 'action_type', 'username', 'full_name'];
            if (!in_array($sort_by, $allowed_sort_fields)) {
                $sort_by = 'created_at';
            }
            
            $sort_order = strtolower($sort_order);
            if (!in_array($sort_order, ['asc', 'desc'])) {
                $sort_order = 'desc';
            }
            
            // Calculate offset
            $offset = ($page - 1) * $per_page;
            
            // Build base query
            $sql = "SELECT * FROM activity_log_view ";
            $params = [];
            $whereConditions = [];
            
            // Add company filtering for security
            $this->addCompanyFiltering($whereConditions, $params);
            
            // Add filters
            if ($action_type) {
                $whereConditions[] = "action_type = ?";
                $params[] = $action_type;
            }
            
            if ($search) {
                $whereConditions[] = "(description LIKE ? OR username LIKE ? OR full_name LIKE ? OR metadata LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($date_from) {
                $whereConditions[] = "DATE(created_at) >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $whereConditions[] = "DATE(created_at) <= ?";
                $params[] = $date_to;
            }
            
            if (!empty($whereConditions)) {
                $sql .= "WHERE " . implode(" AND ", $whereConditions) . " ";
            }
            
            $sql .= "ORDER BY {$sort_by} {$sort_order} LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            
            // Bind parameters
            $paramIndex = 1;
            foreach ($params as $param) {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
            }
            $stmt->bindValue($paramIndex++, $per_page, PDO::PARAM_INT);
            $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of activities with filters
     */
    public function getActivitiesCount($action_type = null, $search = null, $date_from = null, $date_to = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM activity_log_view ";
            $params = [];
            $whereConditions = [];
            
            // Add company filtering for security
            $this->addCompanyFiltering($whereConditions, $params);
            
            // Add filters (same as pagination method)
            if ($action_type) {
                $whereConditions[] = "action_type = ?";
                $params[] = $action_type;
            }
            
            if ($search) {
                $whereConditions[] = "(description LIKE ? OR username LIKE ? OR full_name LIKE ? OR metadata LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($date_from) {
                $whereConditions[] = "DATE(created_at) >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $whereConditions[] = "DATE(created_at) <= ?";
                $params[] = $date_to;
            }
            
            if (!empty($whereConditions)) {
                $sql .= "WHERE " . implode(" AND ", $whereConditions);
            }
            
            $stmt = $this->conn->prepare($sql);
            
            // Bind parameters
            $paramIndex = 1;
            foreach ($params as $param) {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (Exception $e) {
            error_log("Activity Logger Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Add company filtering to queries based on user role
     */
    private function addCompanyFiltering(&$whereConditions, &$params) {
        // Check if user is logged in and get their role
        if (!isset($_SESSION['user_role'])) {
            // No session, show no activities
            $whereConditions[] = "1 = 0";
            return;
        }
        
        $user_role = $_SESSION['user_role'];
        
        // Super admin can see all activities
        if ($user_role === 'super_admin') {
            return; // No filtering for super admin
        }
        
        // Company admin and officers can only see their company's activities
        $company_id = $_SESSION['company_id'] ?? null;
        if ($company_id) {
            $whereConditions[] = "company_id = ?";
            $params[] = $company_id;
        } else {
            // If no company_id, show no activities
            $whereConditions[] = "1 = 0";
        }
    }
}
?>
