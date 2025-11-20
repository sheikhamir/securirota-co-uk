<?php
/**
 * Helper Functions for SecuriRota System
 * Centralized location for common utility functions
 */

// =============================================================================
// HTML/Output Helper Functions
// =============================================================================

if (!function_exists('safe_html')) {
    /**
     * Safely escape HTML with null values
     * @param mixed $value The value to escape
     * @return string Empty string if null, otherwise escaped HTML
     */
    function safe_html($value) {
        return htmlspecialchars($value ?? '');
    }
}

if (!function_exists('safe_display')) {
    /**
     * Safely display values with fallback
     * @param mixed $value The value to display
     * @param string $fallback Fallback text if value is empty
     * @return string
     */
    function safe_display($value, $fallback = '-') {
        return !empty($value) ? htmlspecialchars($value) : $fallback;
    }
}

if (!function_exists('safe_number')) {
    /**
     * Safely format numbers with null handling
     * @param mixed $value The numeric value
     * @param int $decimals Number of decimal places
     * @return string
     */
    function safe_number($value, $decimals = 2) {
        return number_format($value ?? 0, $decimals);
    }
}

// =============================================================================
// Date/Time Helper Functions
// =============================================================================

if (!function_exists('safe_date')) {
    /**
     * Safely format dates with null handling
     * @param mixed $date The date value
     * @param string $format Date format
     * @return string
     */
    function safe_date($date, $format = 'd/m/Y') {
        return $date ? date($format, strtotime($date)) : '-';
    }
}

if (!function_exists('formatTime')) {
    /**
     * Format time to H:i format
     * @param string $time Time string
     * @return string Formatted time or empty string if invalid
     */
    function formatTime($time) {
        if (!$time) return '';
        return date('H:i', strtotime($time));
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format date to d/m/Y format
     * @param string $date Date string
     * @return string Formatted date or empty string if invalid
     */
    function formatDate($date) {
        if (!$date) return '';
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Format datetime to d/m/Y H:i format
     * @param string $datetime DateTime string
     * @return string Formatted datetime or empty string if invalid
     */
    function formatDateTime($datetime) {
        if (!$datetime) return '';
        return date('d/m/Y H:i', strtotime($datetime));
    }
}

if (!function_exists('formatTime24')) {
    /**
     * Format time to 24-hour format (for JavaScript compatibility)
     * @param string $time Time string
     * @return string Formatted time
     */
    function formatTime24($time) {
        if (!$time) return '';
        return date('H:i', strtotime($time));
    }
}

// =============================================================================
// User Authentication & Authorization Helper Functions
// =============================================================================

if (!function_exists('getCurrentUserId')) {
    /**
     * Get current user ID from session
     * @return int|null User ID or null if not logged in
     */
    function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('getCurrentUsername')) {
    /**
     * Get current username from session
     * @return string Username or 'Guest' if not logged in
     */
    function getCurrentUsername() {
        return $_SESSION['username'] ?? 'Guest';
    }
}

if (!function_exists('getCurrentUserRole')) {
    /**
     * Get current user role from session
     * @return string User role or 'guest' if not logged in
     */
    function getCurrentUserRole() {
        return $_SESSION['user_role'] ?? 'guest';
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Check if current user is admin (company admin)
     * @return bool True if admin, false otherwise
     */
    function isAdmin() {
        return (getCurrentUserRole() === 'admin');
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check if current user is super admin
     * @return bool True if super admin, false otherwise
     */
    function isSuperAdmin() {
        return (getCurrentUserRole() === 'super_admin');
    }
}

if (!function_exists('isCompanyAdmin')) {
    /**
     * Check if current user is company admin
     * @return bool True if company admin, false otherwise
     */
    function isCompanyAdmin() {
        return (getCurrentUserRole() === 'admin');
    }
}

if (!function_exists('isManager')) {
    /**
     * Check if current user is manager or admin
     * @return bool True if manager or admin, false otherwise
     */
    function isManager() {
        $role = getCurrentUserRole();
        return in_array($role, ['admin', 'manager']);
    }
}

// =============================================================================
// Navigation & Page Helper Functions
// =============================================================================

if (!function_exists('getCurrentPageName')) {
    /**
     * Get current page filename
     * @return string Current page filename
     */
    function getCurrentPageName() {
        return basename($_SERVER['PHP_SELF']);
    }
}

if (!function_exists('isCurrentPage')) {
    /**
     * Check if given page is the current page
     * @param string $page Page filename to check
     * @return bool True if current page, false otherwise
     */
    function isCurrentPage($page) {
        return getCurrentPageName() === $page;
    }
}

if (!function_exists('getNavActiveClass')) {
    /**
     * Get CSS class for active navigation item
     * @param string $page Page filename to check
     * @param string $activeClass CSS class for active state
     * @return string CSS class or empty string
     */
    function getNavActiveClass($page, $activeClass = 'active') {
        return isCurrentPage($page) ? $activeClass : '';
    }
}

// =============================================================================
// URL & Asset Helper Functions
// =============================================================================

if (!function_exists('baseUrl')) {
    /**
     * Generate base URL for assets and links
     * @param string $path Optional path to append
     * @return string Complete URL
     */
    function baseUrl($path = '') {
        return BASE_URL . ltrim($path, '/');
    }
}

if (!function_exists('assetUrl')) {
    /**
     * Generate asset URL with cache busting
     * @param string $assetPath Path to asset file
     * @param bool $cacheBust Whether to add cache busting parameter
     * @return string Asset URL
     */
    function assetUrl($assetPath, $cacheBust = true) {
        $url = baseUrl($assetPath);
        if ($cacheBust) {
            $url .= '?v=' . time();
        }
        return $url;
    }
}

// =============================================================================
// Form & Validation Helper Functions
// =============================================================================

if (!function_exists('old')) {
    /**
     * Get old input value (useful for form validation)
     * @param string $key Input name
     * @param mixed $default Default value
     * @return mixed Old value or default
     */
    function old($key, $default = '') {
        return $_SESSION['old_input'][$key] ?? $default;
    }
}

if (!function_exists('hasError')) {
    /**
     * Check if field has validation error
     * @param string $field Field name
     * @return bool True if has error, false otherwise
     */
    function hasError($field) {
        return isset($_SESSION['errors'][$field]);
    }
}

if (!function_exists('getError')) {
    /**
     * Get validation error message for field
     * @param string $field Field name
     * @return string Error message or empty string
     */
    function getError($field) {
        return $_SESSION['errors'][$field] ?? '';
    }
}

// =============================================================================
// Debug & Logging Helper Functions
// =============================================================================

if (!function_exists('debugLog')) {
    /**
     * Log debug information
     * @param mixed $data Data to log
     * @param string $prefix Optional prefix for log message
     */
    function debugLog($data, $prefix = 'DEBUG') {
        $message = $prefix . ': ' . (is_array($data) || is_object($data) ? json_encode($data) : $data);
        error_log($message);
    }
}

if (!function_exists('dumpVar')) {
    /**
     * Dump variable for debugging (only in development)
     * @param mixed $var Variable to dump
     * @param bool $die Whether to die after dumping
     */
    function dumpVar($var, $die = false) {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
            if ($die) die();
        }
    }
}

// =============================================================================
// Role Management Helper Functions
// =============================================================================

if (!function_exists('getRoles')) {
    /**
     * Get all active roles from database with company filtering
     * @return array Array of role objects
     */
    function getRoles() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Initialize company filtering
            $use_company_filter = false;
            $company_id = null;
            
            // Check if we're in multi-tenant mode (post-migration)
            try {
                $column_check = $conn->query("SHOW COLUMNS FROM roles LIKE 'company_id'");
                if ($column_check->rowCount() > 0) {
                    // Multi-tenant mode active
                    $use_company_filter = true;
                    $is_super_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
                    if (!$is_super_admin) {
                        $company_id = $_SESSION['company_id'] ?? null;
                    }
                }
            } catch (Exception $e) {
                // Pre-migration mode, no company filtering
                $use_company_filter = false;
            }
            
            $sql = "SELECT id, name FROM roles WHERE is_active = 1";
            $params = [];
            
            // SECURITY: Add company filtering to prevent accessing other companies' roles
            if ($use_company_filter && $company_id) {
                $sql .= " AND company_id = ?";
                $params[] = $company_id;
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("GET_ROLES_HELPER ERROR: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getRoleOptions')) {
    /**
     * Get role options as HTML option elements
     * @param string $selected_value Currently selected role ID
     * @return string HTML option elements
     */
    function getRoleOptions($selected_value = '') {
        $roles = getRoles();
        $options = '';
        
        foreach ($roles as $role) {
            $selected = ($role['id'] == $selected_value) ? 'selected' : '';
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($role['id']),
                $selected,
                htmlspecialchars($role['name'])
            );
        }
        
        return $options;
    }
}

// =============================================================================
// Rate Calculation Helper Functions
// =============================================================================

if (!function_exists('getEffectiveClientRate')) {
    /**
     * Get effective client rate for a site (site rate overrides client rate)
     * @param int $site_id Site ID
     * @param PDO $conn Database connection (optional)
     * @return float Effective client rate
     */
    function getEffectiveClientRate($site_id, $conn = null) {
        try {
            if (!$conn) {
                $db = new Database();
                $conn = $db->getConnection();
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    s.client_rate as site_client_rate,
                    c.billing_rate as client_billing_rate,
                    COALESCE(s.client_rate, c.billing_rate, 0.00) as effective_rate
                FROM sites s
                LEFT JOIN clients c ON s.client_id = c.id
                WHERE s.id = ?
            ");
            $stmt->execute([$site_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (float)$result['effective_rate'] : 0.00;
        } catch (Exception $e) {
            error_log("GET_EFFECTIVE_CLIENT_RATE ERROR: " . $e->getMessage());
            return 0.00;
        }
    }
}

if (!function_exists('getOfficerRate')) {
    /**
     * Get officer hourly rate from officers table
     * @param int $officer_id Officer ID
     * @param PDO $conn Database connection (optional)
     * @return float Officer hourly rate
     */
    function getOfficerRate($officer_id, $conn = null) {
        try {
            if (!$conn) {
                $db = new Database();
                $conn = $db->getConnection();
            }
            
            $stmt = $conn->prepare("
                SELECT hourly_rate
                FROM officers 
                WHERE id = ?
            ");
            $stmt->execute([$officer_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (float)$result['hourly_rate'] : 0.00;
        } catch (Exception $e) {
            error_log("GET_OFFICER_RATE ERROR: " . $e->getMessage());
            return 0.00;
        }
    }
}

if (!function_exists('getEffectiveOfficerRate')) {
    /**
     * Get effective officer rate for a specific shift
     * Checks for custom_officer_rate first, then falls back to officer's default rate
     * @param int $shift_id Shift ID (if updating existing shift)
     * @param int $officer_id Officer ID
     * @param float $custom_rate Custom rate override (optional)
     * @param PDO $conn Database connection (optional)
     * @return array Rate information with source
     */
    function getEffectiveOfficerRate($shift_id = null, $officer_id = null, $custom_rate = null, $conn = null) {
        try {
            if (!$conn) {
                $db = new Database();
                $conn = $db->getConnection();
            }
            
            // If custom rate is provided directly, use it
            if ($custom_rate !== null && $custom_rate > 0) {
                return [
                    'rate' => (float)$custom_rate,
                    'source' => 'custom',
                    'description' => 'Custom rate for this shift'
                ];
            }
            
            // If shift_id provided, check for existing custom rate
            if ($shift_id) {
                $stmt = $conn->prepare("
                    SELECT custom_officer_rate, officer_id
                    FROM shifts 
                    WHERE id = ?
                ");
                $stmt->execute([$shift_id]);
                $shift = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($shift && $shift['custom_officer_rate'] !== null) {
                    return [
                        'rate' => (float)$shift['custom_officer_rate'],
                        'source' => 'custom',
                        'description' => 'Custom rate for this shift'
                    ];
                }
                
                // Use officer_id from shift if not provided
                if (!$officer_id && $shift) {
                    $officer_id = $shift['officer_id'];
                }
            }
            
            // Fall back to officer's default rate
            if ($officer_id) {
                $default_rate = getOfficerRate($officer_id, $conn);
                return [
                    'rate' => $default_rate,
                    'source' => 'officer_default',
                    'description' => 'Officer default hourly rate'
                ];
            }
            
            // No rate available
            return [
                'rate' => 0.00,
                'source' => 'none',
                'description' => 'No rate available'
            ];
            
        } catch (Exception $e) {
            error_log("GET_EFFECTIVE_OFFICER_RATE ERROR: " . $e->getMessage());
            return [
                'rate' => 0.00,
                'source' => 'error',
                'description' => 'Error retrieving rate'
            ];
        }
    }
}

if (!function_exists('getShiftOfficerRate')) {
    /**
     * Get effective officer rate for a shift (simple version returning just the rate)
     * @param int $shift_id Shift ID (if updating existing shift)
     * @param int $officer_id Officer ID
     * @param float $custom_rate Custom rate override (optional)
     * @param PDO $conn Database connection (optional)
     * @return float Effective officer rate
     */
    function getShiftOfficerRate($shift_id = null, $officer_id = null, $custom_rate = null, $conn = null) {
        $rate_info = getEffectiveOfficerRate($shift_id, $officer_id, $custom_rate, $conn);
        return $rate_info['rate'];
    }
}

if (!function_exists('getSiteRatesWithSource')) {
    /**
     * Get site client rate information (officers have individual rates)
     * @param int $site_id Site ID
     * @param PDO $conn Database connection (optional)
     * @return array Rate information with sources
     */
    function getSiteRatesWithSource($site_id, $conn = null) {
        try {
            if (!$conn) {
                $db = new Database();
                $conn = $db->getConnection();
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    s.site_name,
                    s.client_rate as site_client_rate,
                    c.billing_rate as client_billing_rate,
                    c.company_name as client_name,
                    COALESCE(s.client_rate, c.billing_rate, 0.00) as effective_client_rate,
                    CASE 
                        WHEN s.client_rate IS NOT NULL THEN 'site'
                        WHEN c.billing_rate IS NOT NULL THEN 'client'
                        ELSE 'default'
                    END as client_rate_source
                FROM sites s
                LEFT JOIN clients c ON s.client_id = c.id
                WHERE s.id = ?
            ");
            $stmt->execute([$site_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("GET_SITE_RATES_WITH_SOURCE ERROR: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('calculateShiftEarnings')) {
    /**
     * Calculate earnings for a shift based on duration and rates
     * @param string $start_time Start time (HH:MM format)
     * @param string $end_time End time (HH:MM format)
     * @param float $client_rate Client hourly rate
     * @param float $officer_rate Officer hourly rate
     * @return array Array with client_amount, officer_amount, duration_hours
     */
    function calculateShiftEarnings($start_time, $end_time, $client_rate, $officer_rate) {
        try {
            $start = new DateTime($start_time);
            $end = new DateTime($end_time);
            
            // Handle overnight shifts
            if ($end < $start) {
                $end->modify('+1 day');
            }
            
            $interval = $start->diff($end);
            $duration_hours = $interval->h + ($interval->i / 60);
            
            return [
                'duration_hours' => round($duration_hours, 2),
                'client_amount' => round($duration_hours * $client_rate, 2),
                'officer_amount' => round($duration_hours * $officer_rate, 2),
                'profit_margin' => round($duration_hours * ($client_rate - $officer_rate), 2)
            ];
        } catch (Exception $e) {
            error_log("CALCULATE_SHIFT_EARNINGS ERROR: " . $e->getMessage());
            return [
                'duration_hours' => 0,
                'client_amount' => 0.00,
                'officer_amount' => 0.00,
                'profit_margin' => 0.00
            ];
        }
    }
}

if (!function_exists('updateShiftRates')) {
    /**
     * Update shift rates based on current site rates and officer rate
     * @param int $shift_id Shift ID
     * @param PDO $conn Database connection (optional)
     * @return bool Success status
     */
    function updateShiftRates($shift_id, $conn = null) {
        try {
            if (!$conn) {
                $db = new Database();
                $conn = $db->getConnection();
            }
            
            // Get site ID and officer ID for this shift
            $stmt = $conn->prepare("SELECT site_id, officer_id FROM shifts WHERE id = ?");
            $stmt->execute([$shift_id]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shift) {
                return false;
            }
            
            $client_rate = getEffectiveClientRate($shift['site_id'], $conn);
            $officer_rate = $shift['officer_id'] ? getOfficerRate($shift['officer_id'], $conn) : 0.00;
            
            // Update shift with current rates
            $stmt = $conn->prepare("
                UPDATE shifts 
                SET client_rate = ?, officer_rate = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            return $stmt->execute([$client_rate, $officer_rate, $shift_id]);
        } catch (Exception $e) {
            error_log("UPDATE_SHIFT_RATES ERROR: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format currency with proper symbol and decimal places
     * @param float $amount Amount to format
     * @param string $currency Currency symbol
     * @return string Formatted currency string
     */
    function formatCurrency($amount, $currency = '£') {
        return $currency . number_format($amount, 2);
    }
}

if (!function_exists('generateNextStaffId')) {
    /**
     * Generate next company-specific numeric staff ID
     * Every company starts from 10001 and increments within their own sequence
     * @param PDO $conn Database connection
     * @param int $company_id Company ID
     * @return string Next staff ID for the company
     */
    function generateNextStaffId($conn, $company_id) {
        try {
            // Get the highest existing staff_id for this specific company
            $stmt = $conn->prepare("
                SELECT MAX(CAST(staff_id AS UNSIGNED)) as max_staff_id 
                FROM officers 
                WHERE company_id = ? 
                AND staff_id REGEXP '^[0-9]+$'
            ");
            
            $stmt->execute([$company_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['max_staff_id']) {
                // Increment from the highest existing ID for this company
                $next_staff_id = $result['max_staff_id'] + 1;
            } else {
                // Start from 10001 if no existing IDs found for this company
                $next_staff_id = 10001;
            }
            
            return (string)$next_staff_id;
            
        } catch (Exception $e) {
            error_log("Error generating staff ID for company $company_id: " . $e->getMessage());
            // Fallback: return 10001
            return '10001';
        }
    }
}
?>
