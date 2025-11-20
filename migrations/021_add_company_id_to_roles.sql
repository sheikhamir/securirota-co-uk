-- Add company_id column to roles table for multi-tenant support
-- Migration: 021_add_company_id_to_roles.sql

-- Add company_id column to roles table
ALTER TABLE roles 
ADD COLUMN company_id INT(11) NULL AFTER description,
ADD INDEX idx_roles_company_id (company_id);

-- Update existing roles to belong to company 1 (the main company)
UPDATE roles SET company_id = 1;

-- Make company_id NOT NULL after setting values
ALTER TABLE roles 
MODIFY COLUMN company_id INT(11) NOT NULL;

-- Add foreign key constraint (optional, but recommended)
-- ALTER TABLE roles 
-- ADD CONSTRAINT fk_roles_company_id 
-- FOREIGN KEY (company_id) REFERENCES clients(company_id) ON DELETE CASCADE;