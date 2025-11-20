-- Security Company Rota Management System Database Schema

CREATE DATABASE IF NOT EXISTS rota_system;
USE rota_system;

-- Users table (for authentication)
CREATE TABLE users (
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
CREATE TABLE officers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
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
CREATE TABLE clients (
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
CREATE TABLE sites (
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

-- Shifts table (rota management)
CREATE TABLE shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    officer_id INT,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    role VARCHAR(50) DEFAULT 'Security',
    status ENUM('unallocated', 'allocated', 'confirmed', 'declined', 'completed') DEFAULT 'unallocated',
    
    -- Actual attendance
    actual_start_time TIME NULL,
    actual_end_time TIME NULL,
    
    -- Rates
    officer_rate DECIMAL(8,2),
    client_rate DECIMAL(8,2),
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE SET NULL
);

-- Documents table (for uploaded files)
CREATE TABLE documents (
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
CREATE TABLE leave_requests (
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
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('shift_assigned', 'shift_reminder', 'document_expiry', 'general') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@security.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample clients
INSERT INTO clients (name, contact_person, email, phone, billing_rate) VALUES 
('Optimus Security', 'John Smith', 'john@optimus.com', '01234567890', 15.00),
('Pristine Services', 'Jane Doe', 'jane@pristine.com', '01234567891', 16.50);

-- Insert sample sites
INSERT INTO sites (client_id, name, address, postcode, contact_person, contact_phone) VALUES 
(1, 'Optimus Warehouse', '123 Industrial Estate, London', 'SW1A 1AA', 'Mike Johnson', '01234567892'),
(2, 'Pristine Office Complex', '456 Business Park, Manchester', 'M1 1AA', 'Sarah Wilson', '01234567893');
