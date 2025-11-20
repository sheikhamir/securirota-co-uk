-- Core Database Schema
-- This creates the base tables for the security rota system
-- Run this first on a fresh database

-- Create database (optional - may already exist)
-- CREATE DATABASE IF NOT EXISTS rota_system;
-- USE rota_system;

-- Users table (for authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'officer') DEFAULT 'officer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Officers table (staff records)
CREATE TABLE IF NOT EXISTS officers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    employee_id VARCHAR(20) UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    phone VARCHAR(20),
    email VARCHAR(100),
    address_line1 VARCHAR(100),
    address_line2 VARCHAR(100),
    street VARCHAR(100),
    postcode VARCHAR(10),
    city VARCHAR(50),
    country VARCHAR(50) DEFAULT 'United Kingdom',
    
    -- Compliance/Legal
    sia_badge_number VARCHAR(50),
    sia_expiry_date DATE,
    visa_status VARCHAR(50),
    visa_expiry_date DATE,
    national_insurance VARCHAR(20),
    
    -- Employment
    role VARCHAR(50),
    department VARCHAR(50),
    start_date DATE,
    pay_rate DECIMAL(8,2),
    
    -- Bank Details
    bank_name VARCHAR(100),
    bank_address TEXT,
    account_number VARCHAR(20),
    sort_code VARCHAR(10),
    iban VARCHAR(50),
    
    -- Other
    profile_photo VARCHAR(255),
    notes TEXT,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Clients table
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    billing_rate DECIMAL(8,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sites table
CREATE TABLE IF NOT EXISTS sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    postcode VARCHAR(10),
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    site_requirements TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Roles table for dynamic role management
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shifts table (rota management)
CREATE TABLE IF NOT EXISTS shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    officer_id INT,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    role VARCHAR(50) DEFAULT 'Security',
    role_id INT NULL,
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('unallocated', 'allocated', 'confirmed', 'declined', 'completed', 'cancelled') DEFAULT 'unallocated',
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    rescheduled BOOLEAN DEFAULT FALSE,
    reschedule_reason TEXT NULL,
    
    -- Actual attendance
    actual_start_time TIME NULL,
    actual_end_time TIME NULL,
    
    -- Rates
    officer_rate DECIMAL(8,2),
    client_rate DECIMAL(8,2),
    
    notes TEXT,
    decline_reason TEXT,
    checkin_image VARCHAR(255),
    checkout_image VARCHAR(255),
    checkin_timestamp TIMESTAMP NULL,
    checkout_timestamp TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- Activity log table for comprehensive audit trail
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
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_entity_type_id (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- Email templates table
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

-- Email images table for template images
CREATE TABLE IF NOT EXISTS email_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_filename (filename),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Documents table (for uploaded files)
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

-- Site Rota Templates table
CREATE TABLE IF NOT EXISTS site_rotas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_id (site_id),
    INDEX idx_active (is_active)
);

-- Subcontractors table
CREATE TABLE IF NOT EXISTS subcontractors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    vat_number VARCHAR(50),
    registration_number VARCHAR(50),
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Holiday pay tracking
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

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_shifts_rescheduled ON shifts(rescheduled);
CREATE INDEX IF NOT EXISTS idx_shifts_status_rescheduled ON shifts(status, rescheduled);
CREATE INDEX IF NOT EXISTS idx_shifts_status ON shifts(status);
CREATE INDEX IF NOT EXISTS idx_shifts_cancelled_at ON shifts(cancelled_at);
CREATE INDEX IF NOT EXISTS idx_shifts_role_id ON shifts(role_id);
CREATE INDEX IF NOT EXISTS idx_roles_active_sort ON roles (is_active, sort_order);

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
