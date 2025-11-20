-- Enhanced Security System Migration
-- Adds comprehensive security tables and features

-- API Access Log Table
CREATE TABLE IF NOT EXISTS api_access_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    ip_address VARCHAR(45),
    endpoint VARCHAR(255),
    method VARCHAR(10),
    user_agent TEXT,
    company_id INT,
    response_code INT DEFAULT 200,
    response_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_time (user_id, created_at),
    INDEX idx_endpoint (endpoint),
    INDEX idx_ip_time (ip_address, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- Enhanced Rate Limits Table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL,
    company_id INT,
    endpoint VARCHAR(255),
    count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_window (identifier, window_start),
    INDEX idx_company_time (company_id, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- Failed Login Attempts Table
CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL,
    company_id INT,
    username VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, created_at),
    INDEX idx_ip_time (ip_address, created_at),
    INDEX idx_username_time (username, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- Security Logs Table  
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(100) NOT NULL,
    identifier VARCHAR(255),
    company_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_identifier_time (identifier, created_at),
    INDEX idx_severity (severity),
    INDEX idx_company_time (company_id, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- Security Settings Table
CREATE TABLE IF NOT EXISTS security_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- User Sessions Table (Enhanced)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    company_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    login_method ENUM('password', '2fa', 'sso') DEFAULT 'password',
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_session_id (session_id),
    INDEX idx_company_time (company_id, created_at),
    INDEX idx_active_sessions (is_active, expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- Two-Factor Authentication Table
CREATE TABLE IF NOT EXISTS user_2fa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    secret_key VARCHAR(255) NOT NULL,
    backup_codes JSON,
    enabled BOOLEAN DEFAULT FALSE,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password History Table (for password reuse prevention)
CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_time (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default security settings
INSERT IGNORE INTO security_settings (setting_key, setting_value, description) VALUES
('rate_limit_per_hour', '1000', 'Maximum API requests per hour per user'),
('max_login_attempts', '5', 'Maximum failed login attempts before lockout'),
('lockout_duration_minutes', '15', 'Account lockout duration in minutes'),
('session_timeout_minutes', '60', 'Session timeout in minutes'),
('require_2fa', 'false', 'Require two-factor authentication for all users'),
('password_complexity', 'true', 'Require complex passwords'),
('password_min_length', '8', 'Minimum password length'),
('password_history_count', '5', 'Number of previous passwords to remember'),
('failed_login_notification', 'true', 'Send notifications for failed login attempts'),
('suspicious_activity_detection', 'true', 'Enable suspicious activity detection');

-- Add security-related columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45),
ADD COLUMN IF NOT EXISTS require_password_change BOOLEAN DEFAULT FALSE;

-- Add indexes for performance
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_failed_attempts (failed_login_attempts);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_locked_until (locked_until);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_last_login (last_login_at);
