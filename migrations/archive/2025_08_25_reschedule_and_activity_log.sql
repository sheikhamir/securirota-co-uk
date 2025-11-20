-- Database Migration for Reschedule Enhancement and Activity Logging
-- Date: 2025-08-25
-- Author: GitHub Copilot

-- 1. Add reschedule fields to shifts table
ALTER TABLE shifts 
ADD COLUMN rescheduled BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN reschedule_reason TEXT NULL AFTER rescheduled;

-- 2. Create activity_log table for comprehensive audit trail
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type ENUM(
        'create_shift', 'update_shift', 'delete_shift', 'confirm_shift', 'reschedule_shift',
        'create_client', 'update_client', 'delete_client',
        'create_site', 'update_site', 'delete_site',
        'create_officer', 'update_officer', 'delete_officer',
        'create_user', 'update_user', 'delete_user',
        'generate_invoice', 'generate_report',
        'login', 'logout'
    ) NOT NULL,
    entity_type ENUM('shift', 'client', 'site', 'officer', 'user', 'invoice', 'report', 'system') NOT NULL,
    entity_id INT NULL, -- ID of the entity being acted upon
    description TEXT NOT NULL, -- Human readable description of the action
    metadata JSON NULL, -- Store additional data about the action (old values, new values, etc.)
    ip_address VARCHAR(45) NULL, -- IPv4 or IPv6 address
    user_agent TEXT NULL, -- Browser/device information
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_entity_type_id (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- 3. Create a view for easier activity log querying with user details
CREATE VIEW activity_log_view AS
SELECT 
    al.*,
    u.username,
    u.email,
    CONCAT(o.first_name, ' ', o.last_name) as full_name
FROM activity_log al
LEFT JOIN users u ON al.user_id = u.id
LEFT JOIN officers o ON u.id = o.user_id
ORDER BY al.created_at DESC;

-- 4. Create indexes for better performance
CREATE INDEX idx_shifts_rescheduled ON shifts(rescheduled);
CREATE INDEX idx_shifts_status_rescheduled ON shifts(status, rescheduled);

-- Insert initial test data to verify the migration
INSERT INTO activity_log (user_id, action_type, entity_type, entity_id, description, ip_address) 
VALUES (1, 'create_shift', 'shift', NULL, 'Database migration completed - added reschedule functionality and activity logging', '127.0.0.1');
