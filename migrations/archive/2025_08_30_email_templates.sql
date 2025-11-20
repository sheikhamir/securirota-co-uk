-- Migration for Email Templates
-- Created: 2025-08-30

-- Create email_templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_type VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables JSON DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default email templates
INSERT INTO email_templates (template_type, template_name, subject, body, variables) VALUES
('pin_generation', 'PIN Generation', 'Your Security PIN - {{company_name}}', 'Dear {{officer_name}},\n\nYour security PIN has been generated for accessing the officer portal.\n\nPIN: {{pin_code}}\n\nPlease keep this PIN secure and do not share it with anyone. You can use this PIN to:\n- Access your shift schedules\n- Check in/out of shifts\n- View your profile information\n\nIf you have any questions or need assistance, please contact us.\n\nBest regards,\n{{company_name}} Team', '["{{officer_name}}", "{{pin_code}}", "{{company_name}}", "{{login_url}}"]'),

('shift_reminder', 'Shift Reminder', 'Shift Reminder - {{site_name}} on {{shift_date}}', 'Dear {{officer_name}},\n\nThis is a reminder about your upcoming shift:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\nDuration: {{shift_duration}} hours\n\nPlease ensure you arrive on time and bring all necessary equipment.\n\nBest regards,\n{{company_name}} Team', '["{{officer_name}}", "{{site_name}}", "{{shift_date}}", "{{shift_start_time}}", "{{shift_end_time}}", "{{shift_duration}}", "{{company_name}}"]'),

('welcome_officer', 'Welcome Officer', 'Welcome to {{company_name}}', 'Dear {{officer_name}},\n\nWelcome to {{company_name}}! We\'re excited to have you join our security team.\n\nYour account has been created with the following details:\n- Officer ID: {{officer_id}}\n- Email: {{officer_email}}\n\nYour security PIN will be sent separately. Please keep it secure as you\'ll need it to access the officer portal.\n\nWe look forward to working with you!\n\nBest regards,\n{{company_name}} Team', '["{{officer_name}}", "{{officer_id}}", "{{officer_email}}", "{{company_name}}"]'),

('password_reset', 'Password Reset', 'Password Reset Request - {{company_name}}', 'Dear {{user_name}},\n\nYou have requested a password reset for your account.\n\nClick the following link to reset your password:\n{{reset_link}}\n\nThis link will expire in 1 hour for security reasons.\n\nIf you did not request this password reset, please ignore this email.\n\nBest regards,\n{{company_name}} Team', '["{{user_name}}", "{{reset_link}}", "{{company_name}}"]'),

('shift_assigned', 'Shift Assignment', 'New Shift Assignment - {{site_name}}', 'Dear {{officer_name}},\n\nYou have been assigned to a new shift:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\nRate: £{{hourly_rate}}/hour\n\nPlease confirm your availability and contact us if you have any questions.\n\nBest regards,\n{{company_name}} Team', '["{{officer_name}}", "{{site_name}}", "{{shift_date}}", "{{shift_start_time}}", "{{shift_end_time}}", "{{hourly_rate}}", "{{company_name}}"]'),

('shift_cancelled', 'Shift Cancellation', 'Shift Cancellation - {{site_name}} on {{shift_date}}', 'Dear {{officer_name}},\n\nWe regret to inform you that your shift has been cancelled:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\n\nReason: {{cancellation_reason}}\n\nWe apologize for any inconvenience and will notify you of any replacement shifts.\n\nBest regards,\n{{company_name}} Team', '["{{officer_name}}", "{{site_name}}", "{{shift_date}}", "{{shift_start_time}}", "{{shift_end_time}}", "{{cancellation_reason}}", "{{company_name}}"]');
