-- Migration 023: Add super_admin role and company_id to users
-- This migration updates the users table to support multi-tenancy
-- Depends on: 022_create_companies_table.sql

-- Add super_admin role to users table
ALTER TABLE users 
MODIFY COLUMN role ENUM('super_admin', 'admin', 'officer') DEFAULT 'officer';

-- Add company_id foreign key to users table
ALTER TABLE users 
ADD COLUMN company_id INT NULL AFTER role,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT;

-- Create index for better performance
ALTER TABLE users ADD INDEX idx_company_id (company_id);

-- Update existing users to belong to the default company
-- Super admin users should have company_id = NULL
UPDATE users 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1)
WHERE role IN ('admin', 'officer');

-- Create first super admin user
INSERT INTO users (username, email, password, role, company_id, status) VALUES 
('superadmin', 'superadmin@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 'active')
ON DUPLICATE KEY UPDATE role = 'super_admin', company_id = NULL;
