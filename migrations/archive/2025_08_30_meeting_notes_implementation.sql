-- Migration to implement changes from meeting notes
-- Date: 2025-08-30
-- Description: Comprehensive updates to Officers, Rota, and authentication system

USE rohabae1_rota;

-- ======================================
-- 1. OFFICERS TABLE UPDATES
-- ======================================

-- Add new fields to officers table
ALTER TABLE officers 
-- New Fields (NF)
ADD COLUMN staff_id VARCHAR(10) UNIQUE NOT NULL DEFAULT '',
ADD COLUMN date_of_birth DATE NULL,
ADD COLUMN national_insurance VARCHAR(15) NULL,
ADD COLUMN photo VARCHAR(255) NULL,
ADD COLUMN nationality VARCHAR(50) NULL,
ADD COLUMN suspend BOOLEAN DEFAULT FALSE,
ADD COLUMN right_to_work_reference VARCHAR(100) NULL,
ADD COLUMN date_started DATE NULL,
ADD COLUMN date_left DATE NULL,
ADD COLUMN subcontractor_id INT NULL,

-- Field Updates (FU) - Expand address fields
ADD COLUMN address_city VARCHAR(50) NULL,
ADD COLUMN address_postal_code VARCHAR(15) NULL,

-- Field Updates (FU) - Expand bank details
ADD COLUMN bank_account_name VARCHAR(100) NULL,
ADD COLUMN bank_roll_number VARCHAR(30) NULL,

-- Update existing visa_status to support new options
MODIFY COLUMN visa_status ENUM('Student Visa', 'Dependent Visa', 'Work Visa', 'Other') NULL;

-- Create index for staff_id for faster lookups
CREATE INDEX idx_officers_staff_id ON officers(staff_id);
CREATE INDEX idx_officers_suspend ON officers(suspend);

-- ======================================
-- 2. HOLIDAY PAY SYSTEM
-- ======================================

-- Create holiday_pay table for officers
CREATE TABLE officer_holiday_pay (
    id INT PRIMARY KEY AUTO_INCREMENT,
    officer_id INT NOT NULL,
    type ENUM('Ad-Hoc', 'Flexi', 'Normal', 'Head Office') NOT NULL,
    hourly_rate_holiday DECIMAL(8,2) NOT NULL,
    calculation_period_start DATE NOT NULL,
    preload_days VARCHAR(20) NULL,
    annual_entitlement VARCHAR(20) NULL,
    can_take_ahead_accrual BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE
);

-- ======================================
-- 3. SUBCONTRACTORS TABLE
-- ======================================

CREATE TABLE subcontractors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100) NULL,
    contact_phone VARCHAR(20) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add foreign key relationship
ALTER TABLE officers 
ADD CONSTRAINT fk_officers_subcontractor 
FOREIGN KEY (subcontractor_id) REFERENCES subcontractors(id) ON DELETE SET NULL;

-- ======================================
-- 4. AUTHENTICATION SYSTEM UPDATES
-- ======================================

-- Add PIN field to users table and mobile number
ALTER TABLE users 
ADD COLUMN mobile_number VARCHAR(20) UNIQUE NULL,
ADD COLUMN pin VARCHAR(6) NULL,
ADD COLUMN pin_generated_at TIMESTAMP NULL;

-- Create index for mobile number lookups
CREATE INDEX idx_users_mobile_number ON users(mobile_number);

-- ======================================
-- 5. SHIFTS TABLE UPDATES (ROTA)
-- ======================================

-- Add decline reason and image fields
ALTER TABLE shifts 
ADD COLUMN decline_reason TEXT NULL,
ADD COLUMN checkin_image VARCHAR(255) NULL,
ADD COLUMN checkout_image VARCHAR(255) NULL,
ADD COLUMN checkin_timestamp TIMESTAMP NULL,
ADD COLUMN checkout_timestamp TIMESTAMP NULL;

-- Update role enum to include Cleaner
ALTER TABLE shifts 
MODIFY COLUMN role ENUM('Security Officer', 'Controller', 'Manager', 'Supervisor', 'Cleaner') DEFAULT 'Security Officer';

-- ======================================
-- 6. SITE ROTAS TABLE
-- ======================================

CREATE TABLE site_rotas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_site_week (site_id, week_start_date)
);

-- Link shifts to site rotas
ALTER TABLE shifts 
ADD COLUMN site_rota_id INT NULL,
ADD CONSTRAINT fk_shifts_site_rota 
FOREIGN KEY (site_rota_id) REFERENCES site_rotas(id) ON DELETE SET NULL;

-- ======================================
-- 7. NOTIFICATIONS SYSTEM UPDATES
-- ======================================

-- Update notification types to include new ones from meeting notes
ALTER TABLE notifications 
MODIFY COLUMN type ENUM(
    'shift_assigned', 
    'shift_reminder', 
    'shift_declined', 
    'pin_reset', 
    'document_expiry', 
    'general'
) NOT NULL;

-- ======================================
-- 8. ACTIVITY LOG FOR SHIFT DECLINES
-- ======================================

CREATE TABLE shift_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    officer_id INT NOT NULL,
    activity_type ENUM('assigned', 'accepted', 'declined', 'completed', 'cancelled') NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE
);

-- ======================================
-- 9. DOCUMENT MANAGEMENT UPDATES
-- ======================================

-- Update document types to include policy documents
ALTER TABLE documents 
MODIFY COLUMN document_type ENUM(
    'sia_license', 
    'id_proof', 
    'address_proof', 
    'contract', 
    'policy_document',
    'other'
) NOT NULL;

-- Make officer_id optional for policy documents
ALTER TABLE documents 
MODIFY COLUMN officer_id INT NULL;

-- ======================================
-- 10. DATA MIGRATION AND CLEANUP
-- ======================================

-- Generate staff IDs for existing officers
SET @counter = 10000;
UPDATE officers 
SET staff_id = LPAD(@counter := @counter + 1, 5, '0')
WHERE staff_id = '' OR staff_id IS NULL;

-- Remove 'Casual' from employment status for existing records
-- (This will be handled in the application layer)

-- Create unique constraint on SIA license number
ALTER TABLE officers 
ADD CONSTRAINT unique_sia_badge UNIQUE (sia_badge_number);

-- ======================================
-- 11. SAMPLE DATA FOR TESTING
-- ======================================

-- Insert sample subcontractors
INSERT INTO subcontractors (name, contact_email, contact_phone) VALUES 
('SecureGuard Ltd', 'info@secureguard.co.uk', '020 1234 5678'),
('ProSecurity Services', 'contact@prosecurity.co.uk', '0161 987 6543');

-- ======================================
-- 12. FUNCTIONS AND TRIGGERS
-- ======================================

-- Trigger to auto-generate staff ID
DELIMITER //

CREATE TRIGGER generate_staff_id 
BEFORE INSERT ON officers
FOR EACH ROW
BEGIN
    IF NEW.staff_id = '' OR NEW.staff_id IS NULL THEN
        SET NEW.staff_id = LPAD((
            SELECT COALESCE(MAX(CAST(staff_id AS UNSIGNED)), 9999) + 1 
            FROM officers 
            WHERE staff_id REGEXP '^[0-9]+$'
        ), 5, '0');
    END IF;
END//

DELIMITER ;

-- Trigger to auto-format sort code with dashes
DELIMITER //

CREATE TRIGGER format_sort_code 
BEFORE INSERT ON officers
FOR EACH ROW
BEGIN
    IF NEW.sort_code IS NOT NULL AND LENGTH(NEW.sort_code) = 6 THEN
        SET NEW.sort_code = CONCAT(
            SUBSTRING(NEW.sort_code, 1, 2), '-',
            SUBSTRING(NEW.sort_code, 3, 2), '-',
            SUBSTRING(NEW.sort_code, 5, 2)
        );
    END IF;
END//

CREATE TRIGGER format_sort_code_update
BEFORE UPDATE ON officers
FOR EACH ROW
BEGIN
    IF NEW.sort_code IS NOT NULL AND LENGTH(NEW.sort_code) = 6 AND NEW.sort_code NOT LIKE '%-%' THEN
        SET NEW.sort_code = CONCAT(
            SUBSTRING(NEW.sort_code, 1, 2), '-',
            SUBSTRING(NEW.sort_code, 3, 2), '-',
            SUBSTRING(NEW.sort_code, 5, 2)
        );
    END IF;
END//

DELIMITER ;

-- ======================================
-- FINAL NOTES
-- ======================================
-- This migration implements all the changes from the meeting notes:
-- 1. Officer profile enhancements (visa options, banking, personal details)
-- 2. Authentication system changes (mobile + PIN)
-- 3. Holiday pay system
-- 4. Shift decline tracking with reasons
-- 5. Photo verification for check-in/out
-- 6. Site rota management
-- 7. Enhanced notifications
-- 8. Document management improvements
-- 9. Staff ID auto-generation
-- 10. Subcontractor management

-- Remember to update the application code to:
-- 1. Remove 'Casual' employment status from dropdowns
-- 2. Update login system to use mobile + PIN
-- 3. Implement photo upload for check-in/out
-- 4. Add email notifications for new officers
-- 5. Implement officer portal with limited access
