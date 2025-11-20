-- Enhanced Security Tables
-- Creates tables for security features, rate limiting, and audit logging

-- Rate limiting table (fallback when Redis is not available)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, created_at)
);

-- Failed login attempts tracking
CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, created_at)
);

-- Comprehensive security logging
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    identifier VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_identifier (identifier),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity)
);

-- User sessions tracking
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id INT,
    company_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);

-- API access tokens for enhanced API security
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_hash VARCHAR(255) UNIQUE NOT NULL,
    user_id INT,
    company_id INT,
    name VARCHAR(255),
    permissions JSON,
    rate_limit_override INT DEFAULT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);

-- Security settings per company
CREATE TABLE IF NOT EXISTS company_security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNIQUE NOT NULL,
    settings JSON NOT NULL DEFAULT '{}',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Insert default security settings for existing companies
INSERT IGNORE INTO company_security_settings (company_id, settings)
SELECT id, JSON_OBJECT(
    'password_policy', JSON_OBJECT(
        'min_length', 8,
        'require_uppercase', true,
        'require_lowercase', true,
        'require_numbers', true,
        'require_special_chars', true,
        'password_expiry_days', 90
    ),
    'session_settings', JSON_OBJECT(
        'timeout_minutes', 60,
        'max_concurrent_sessions', 3,
        'require_2fa', false
    ),
    'access_control', JSON_OBJECT(
        'allowed_ip_ranges', JSON_ARRAY(),
        'blocked_countries', JSON_ARRAY(),
        'require_device_registration', false
    ),
    'api_settings', JSON_OBJECT(
        'rate_limit_per_hour', 1000,
        'require_api_key', false,
        'allowed_origins', JSON_ARRAY()
    )
) as settings
FROM companies;

-- Two-factor authentication
CREATE TABLE IF NOT EXISTS user_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    backup_codes JSON,
    is_enabled BOOLEAN DEFAULT FALSE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Device registration for enhanced security
CREATE TABLE IF NOT EXISTS registered_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    device_name VARCHAR(255),
    device_type VARCHAR(50),
    browser_info TEXT,
    ip_address VARCHAR(45),
    is_trusted BOOLEAN DEFAULT FALSE,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_device (user_id, device_fingerprint),
    INDEX idx_user_id (user_id),
    INDEX idx_device_fingerprint (device_fingerprint)
);

-- Add additional indexes for performance
ALTER TABLE activity_log ADD INDEX idx_company_action_type (company_id, action_type);
ALTER TABLE activity_log ADD INDEX idx_user_action_type (user_id, action_type);
ALTER TABLE users ADD INDEX idx_company_role (company_id, role);
ALTER TABLE users ADD INDEX idx_status (status);

-- Clean up old data procedures
DELIMITER //

CREATE PROCEDURE CleanupSecurityLogs()
BEGIN
    -- Keep only last 90 days of security logs
    DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Keep only last 30 days of rate limit logs
    DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Keep only last 7 days of failed login attempts
    DELETE FROM failed_login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Clean up expired sessions
    DELETE FROM user_sessions WHERE expires_at < NOW() OR (is_active = FALSE AND last_activity < DATE_SUB(NOW(), INTERVAL 1 DAY));
END //

DELIMITER ;

-- Event to run cleanup daily
CREATE EVENT IF NOT EXISTS SecurityLogsCleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL CleanupSecurityLogs();
