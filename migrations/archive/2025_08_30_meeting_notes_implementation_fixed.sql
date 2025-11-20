-- Migration to implement changes from meeting notes (FIXED VERSION)
-- Date: 2025-08-30
-- Description: Comprehensive updates to Officers, Rota, and authentication system

USE rohabae1_rota;

-- ======================================
-- 1. OFFICERS TABLE UPDATES
-- ======================================

-- First, temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Add new fields to officers table (handle each column separately to avoid issues)
-- New Fields (NF)
ALTER TABLE officers ADD COLUMN staff_id VARCHAR(10) DEFAULT '';
ALTER TABLE officers ADD COLUMN date_of_birth DATE NULL;
ALTER TABLE officers ADD COLUMN national_insurance VARCHAR(15) NULL;
ALTER TABLE officers ADD COLUMN photo VARCHAR(255) NULL;
ALTER TABLE officers ADD COLUMN nationality VARCHAR(50) NULL;
ALTER TABLE officers ADD COLUMN suspend BOOLEAN DEFAULT FALSE;
ALTER TABLE officers ADD COLUMN right_to_work_reference VARCHAR(100) NULL;
ALTER TABLE officers ADD COLUMN date_started DATE NULL;
ALTER TABLE officers ADD COLUMN date_left DATE NULL;
ALTER TABLE officers ADD COLUMN subcontractor_id INT NULL;

-- Field Updates (FU) - Expand address fields
ALTER TABLE officers ADD COLUMN address_city VARCHAR(50) NULL;
ALTER TABLE officers ADD COLUMN address_postal_code VARCHAR(15) NULL;

-- Field Updates (FU) - Expand bank details
ALTER TABLE officers ADD COLUMN bank_account_name VARCHAR(100) NULL;
ALTER TABLE officers ADD COLUMN bank_roll_number VARCHAR(30) NULL;

-- Update existing visa_status to support new options
ALTER TABLE officers MODIFY COLUMN visa_status ENUM('Student Visa', 'Dependent Visa', 'Work Visa', 'British', 'EU', 'Visa', 'Other') DEFAULT 'British';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================
-- 2. HOLIDAY PAY SYSTEM
-- ======================================

-- Create holiday_pay table for officers
CREATE TABLE IF NOT EXISTS officer_holiday_pay (
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

CREATE TABLE IF NOT EXISTS subcontractors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100) NULL,
    contact_phone VARCHAR(20) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add foreign key relationship (check if column exists first)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='officers' 
     AND COLUMN_NAME='subcontractor_id') > 0,
    'SELECT "Column subcontractor_id already exists"',
    'ALTER TABLE officers ADD CONSTRAINT fk_officers_subcontractor FOREIGN KEY (subcontractor_id) REFERENCES subcontractors(id) ON DELETE SET NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================
-- 4. AUTHENTICATION SYSTEM UPDATES
-- ======================================

-- Add PIN field to users table and mobile number (check if they exist first)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='users' 
     AND COLUMN_NAME='mobile_number') > 0,
    'SELECT "Column mobile_number already exists"',
    'ALTER TABLE users ADD COLUMN mobile_number VARCHAR(20) UNIQUE NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='users' 
     AND COLUMN_NAME='pin') > 0,
    'SELECT "Column pin already exists"',
    'ALTER TABLE users ADD COLUMN pin VARCHAR(6) NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='users' 
     AND COLUMN_NAME='pin_generated_at') > 0,
    'SELECT "Column pin_generated_at already exists"',
    'ALTER TABLE users ADD COLUMN pin_generated_at TIMESTAMP NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================
-- 5. SHIFTS TABLE UPDATES (ROTA)
-- ======================================

-- Check if shifts table exists first
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='shifts') > 0,
    'SELECT "Shifts table exists, proceeding with updates"',
    'SELECT "Shifts table does not exist, skipping updates"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add decline reason and image fields (only if shifts table exists)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='shifts') > 0,
    'ALTER TABLE shifts ADD COLUMN decline_reason TEXT NULL, ADD COLUMN checkin_image VARCHAR(255) NULL, ADD COLUMN checkout_image VARCHAR(255) NULL, ADD COLUMN checkin_timestamp TIMESTAMP NULL, ADD COLUMN checkout_timestamp TIMESTAMP NULL',
    'SELECT "Shifts table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================
-- 6. SITE ROTAS TABLE
-- ======================================

CREATE TABLE IF NOT EXISTS site_rotas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_site_week (site_id, week_start_date)
);

-- ======================================
-- 7. NOTIFICATIONS SYSTEM UPDATES
-- ======================================

-- Check if notifications table exists and update if it does
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='notifications') > 0,
    'ALTER TABLE notifications MODIFY COLUMN type ENUM("shift_assigned", "shift_reminder", "shift_declined", "pin_reset", "document_expiry", "general") NOT NULL',
    'SELECT "Notifications table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================
-- 8. ACTIVITY LOG FOR SHIFT DECLINES
-- ======================================

CREATE TABLE IF NOT EXISTS shift_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    officer_id INT NOT NULL,
    activity_type ENUM('assigned', 'accepted', 'declined', 'completed', 'cancelled') NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================
-- 9. DOCUMENT MANAGEMENT UPDATES
-- ======================================

-- Check if documents table exists and update if it does
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='documents') > 0,
    'ALTER TABLE documents MODIFY COLUMN document_type ENUM("sia_license", "id_proof", "address_proof", "contract", "policy_document", "other") NOT NULL',
    'SELECT "Documents table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make officer_id optional for policy documents (only if table exists)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA='rohabae1_rota' 
     AND TABLE_NAME='documents') > 0,
    'ALTER TABLE documents MODIFY COLUMN officer_id INT NULL',
    'SELECT "Documents table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================
-- 10. DATA MIGRATION AND CLEANUP
-- ======================================

-- Generate staff IDs for existing officers (only if column exists and is empty)
SET @counter = 10000;
UPDATE officers 
SET staff_id = LPAD(@counter := @counter + 1, 5, '0')
WHERE staff_id = '' OR staff_id IS NULL;

-- Now make staff_id unique after populating it
ALTER TABLE officers ADD CONSTRAINT unique_staff_id UNIQUE (staff_id);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_officers_suspend ON officers(suspend);

-- ======================================
-- 11. SAMPLE DATA FOR TESTING
-- ======================================

-- Insert sample subcontractors
INSERT IGNORE INTO subcontractors (name, contact_email, contact_phone) VALUES 
('SecureGuard Ltd', 'info@secureguard.co.uk', '020 1234 5678'),
('ProSecurity Services', 'contact@prosecurity.co.uk', '0161 987 6543');

-- ======================================
-- 12. FUNCTIONS AND TRIGGERS
-- ======================================

-- Drop trigger if it exists, then create it
DROP TRIGGER IF EXISTS generate_staff_id;

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
DROP TRIGGER IF EXISTS format_sort_code;
DROP TRIGGER IF EXISTS format_sort_code_update;

DELIMITER //
CREATE TRIGGER format_sort_code 
BEFORE INSERT ON officers
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
-- FINAL STATUS
-- ======================================
SELECT 'Migration completed successfully!' AS Status;
