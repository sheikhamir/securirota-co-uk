-- Default Data Migration
-- Inserts default roles and email templates (CORRECTED STRUCTURE)

-- Insert default roles (using ACTUAL structure - no display_name or sort_order)
INSERT IGNORE INTO roles (name, description, is_active) VALUES 
('Security Officer', 'Standard security officer role', 1),
('Controller', 'Control room operator', 1),
('Supervisor', 'Shift supervisor', 1),
('Manager', 'Site manager', 1);

-- Insert default admin user
-- Password is 'admin123' - CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN
INSERT IGNORE INTO users (username, email, password, role, status) VALUES 
('admin', 'admin@security.com', '$2y$10$1K/44ZtfecnhytAwFjZc9e7VvX9cDVU.mysicv20IGB.2DSk4NBmO', 'admin', 'active');

-- Insert default email templates (using ACTUAL structure with is_html field)
INSERT IGNORE INTO email_templates (template_type, template_name, subject, body, is_html, variables) VALUES
('pin_generation', 'PIN Generation', 'Your Security PIN - {{company_name}}', 'Dear {{officer_name}},\n\nYour security PIN has been generated for accessing the officer portal.\n\nPIN: {{pin_code}}\n\nPlease keep this PIN secure and do not share it with anyone. You can use this PIN to:\n- Access your shift schedules\n- Check in/out of shifts\n- View your profile information\n\nIf you have any questions or need assistance, please contact us.\n\nBest regards,\n{{company_name}} Team', 0, '["{{officer_name}}", "{{pin_code}}", "{{company_name}}", "{{login_url}}"]'),

('shift_reminder', 'Shift Reminder', 'Shift Reminder - {{site_name}} on {{shift_date}}', 'Dear {{officer_name}},\n\nThis is a reminder about your upcoming shift:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\nDuration: {{shift_duration}} hours\n\nPlease ensure you arrive on time and bring all necessary equipment.\n\nBest regards,\n{{company_name}} Team', 0, '["{{officer_name}}", "{{site_name}}", "{{shift_date}}", "{{shift_start_time}}", "{{shift_end_time}}", "{{shift_duration}}", "{{company_name}}"]'),

('welcome_officer', 'Welcome Officer', 'Welcome to {{company_name}}', 'Dear {{officer_name}},\n\nWelcome to {{company_name}}! We\'re excited to have you join our security team.\n\nYour account has been created with the following details:\n- Officer ID: {{officer_id}}\n- Email: {{officer_email}}\n\nYour security PIN will be sent separately. Please keep it secure as you\'ll need it to access the officer portal.\n\nWe look forward to working with you!\n\nBest regards,\n{{company_name}} Team', 0, '["{{officer_name}}", "{{officer_id}}", "{{officer_email}}", "{{company_name}}"]'),

('password_reset', 'Password Reset', 'Password Reset Request - {{company_name}}', 'Dear {{user_name}},\n\nYou have requested a password reset for your account.\n\nClick the following link to reset your password:\n{{reset_link}}\n\nThis link will expire in 1 hour for security reasons.\n\nIf you did not request this password reset, please ignore this email.\n\nBest regards,\n{{company_name}} Team', 0, '["{{user_name}}", "{{reset_link}}", "{{company_name}}"]'),

('shift_assigned', 'Shift Assignment', 'New Shift Assignment - {{site_name}}', 'Dear {{officer_name}},\n\nYou have been assigned to a new shift:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\nRate: £{{hourly_rate}}/hour\n\nPlease confirm your availability and contact us if you have any questions.\n\nBest regards,\n{{company_name}} Team', 0, '["{{officer_name}}", "{{site_name}}", "{{shift_date}}", "{{shift_start_time}}", "{{shift_end_time}}", "{{hourly_rate}}", "{{company_name}}"]'),

('shift_cancelled', 'Shift Cancellation', 'Shift Cancellation - {{site_name}} on {{shift_date}}', 'Dear {{officer_name}},\n\nWe regret to inform you that your shift has been cancelled:\n\nSite: {{site_name}}\nDate: {{shift_date}}\nTime: {{shift_start_time}} - {{shift_end_time}}\n\nReason: {{cancellation_reason}}\n\nWe apologize for any inconvenience and will notify you of any replacement shifts.\n\nBest regards,\n{{company_name}} Team', 0, '["{{officer_name}}", "{{site_name}}", "{{shift_date}}", "{{shift_start_time}}", "{{shift_end_time}}", "{{cancellation_reason}}", "{{company_name}}"]');

-- Update existing shifts to map role names to role IDs if they haven't been mapped yet
UPDATE shifts s 
INNER JOIN roles r ON s.role = r.name 
SET s.role_id = r.id
WHERE s.role_id IS NULL;

-- For any shifts with roles that don't exist in the roles table, set to the default 'Security Officer' role
UPDATE shifts s 
SET s.role_id = (SELECT id FROM roles WHERE name = 'Security Officer' LIMIT 1)
WHERE s.role_id IS NULL AND s.role IS NOT NULL;
