-- ACCURATE Core Database Schema
-- This reflects the ACTUAL database structure as it exists
-- Generated from database audit on 2025-09-11

-- Users table (for authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'officer') DEFAULT 'officer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    mobile_number VARCHAR(20) UNIQUE,
    pin VARCHAR(6),
    pin_generated_at TIMESTAMP NULL
);

-- Officers table (staff records) - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS officers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    sia_badge_number VARCHAR(50),
    sia_expiry_date DATE,
    visa_status ENUM('Student Visa','Dependent Visa','Work Visa','British','EU','Visa','Other') DEFAULT 'British',
    visa_expiry_date DATE,
    employment_status ENUM('Full-time','Part-time','Casual','Inactive') DEFAULT 'Part-time',
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    bank_account VARCHAR(20),
    sort_code VARCHAR(10),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    staff_id VARCHAR(10) UNIQUE,
    date_of_birth DATE,
    national_insurance VARCHAR(15),
    photo VARCHAR(255),
    nationality VARCHAR(50),
    suspend BOOLEAN DEFAULT FALSE,
    right_to_work_reference VARCHAR(100),
    date_started DATE,
    date_left DATE,
    subcontractor_id INT,
    address_city VARCHAR(50),
    address_postal_code VARCHAR(15),
    bank_account_name VARCHAR(100),
    bank_roll_number VARCHAR(30),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_suspend (suspend)
);

-- Clients table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    billing_rate DECIMAL(8,2) DEFAULT 0.00,
    payment_terms VARCHAR(50) DEFAULT 'Net 30',
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sites table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    site_name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    site_instructions TEXT,
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Roles table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_is_active (is_active)
);

-- Shifts table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    officer_id INT,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    role VARCHAR(50) DEFAULT 'Security Officer',
    role_id INT,
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('unallocated', 'allocated', 'confirmed', 'declined', 'completed', 'cancelled') DEFAULT 'unallocated',
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    rescheduled BOOLEAN DEFAULT FALSE,
    reschedule_reason TEXT,
    officer_rate DECIMAL(8,2),
    client_rate DECIMAL(8,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    decline_reason TEXT,
    checkin_image VARCHAR(255),
    checkout_image VARCHAR(255),
    checkin_timestamp TIMESTAMP NULL,
    checkout_timestamp TIMESTAMP NULL,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_cancelled_at (cancelled_at),
    INDEX idx_rescheduled (rescheduled),
    INDEX idx_status_rescheduled (status, rescheduled)
);

-- Activity log table - ACTUAL STRUCTURE
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

-- Email templates table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_type VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_html BOOLEAN DEFAULT FALSE,
    variables LONGTEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Email images table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS email_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    alt_text VARCHAR(500),
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_filename (filename),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Documents table
CREATE TABLE IF NOT EXISTS documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    officer_id INT NOT NULL,
    document_type ENUM('sia_license', 'id_proof', 'address_proof', 'contract', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE
);

-- Leave requests table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    officer_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('shift_assigned', 'shift_reminder', 'document_expiry', 'general') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Site Rotas table - ACTUAL STRUCTURE  
CREATE TABLE IF NOT EXISTS site_rotas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_id (site_id)
);

-- Subcontractors table - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS subcontractors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Holiday pay tracking - ACTUAL STRUCTURE
CREATE TABLE IF NOT EXISTS officer_holiday_pay (
    id INT PRIMARY KEY AUTO_INCREMENT,
    officer_id INT NOT NULL,
    accrual_year YEAR NOT NULL,
    hours_worked DECIMAL(8,2) DEFAULT 0,
    holiday_hours_accrued DECIMAL(8,2) DEFAULT 0,
    holiday_hours_taken DECIMAL(8,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_officer_year (officer_id, accrual_year)
);

-- Shift activities tracking
CREATE TABLE IF NOT EXISTS shift_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    activity_type ENUM('checkin', 'checkout', 'break_start', 'break_end', 'incident') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location_data JSON NULL,
    notes TEXT,
    image_path VARCHAR(255),
    
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    INDEX idx_shift_id (shift_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_timestamp (timestamp)
);

-- Create view for easier activity log querying
CREATE OR REPLACE VIEW activity_log_view AS
SELECT 
    al.*,
    u.username,
    u.email,
    CONCAT(o.first_name, ' ', o.last_name) as full_name
FROM activity_log al
LEFT JOIN users u ON al.user_id = u.id
LEFT JOIN officers o ON u.id = o.user_id
ORDER BY al.created_at DESC;
