-- Create activity_log table
-- Depends on: users

CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type ENUM(
        'create_shift', 'update_shift', 'delete_shift', 'confirm_shift', 'reschedule_shift', 'cancel_shift',
        'create_client', 'update_client', 'delete_client',
        'create_site', 'update_site', 'delete_site',
        'create_officer', 'update_officer', 'delete_officer',
        'create_user', 'update_user', 'delete_user',
        'generate_invoice', 'generate_report',
        'login', 'logout'
    ) NOT NULL,
    entity_type ENUM('shift', 'client', 'site', 'officer', 'user', 'invoice', 'report', 'system') NOT NULL,
    entity_id INT NULL,
    description TEXT NOT NULL,
    metadata LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_entity_type_id (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);
