-- Migration 024: Add company_id to all tenant-specific tables
-- This migration adds company_id foreign keys to enable multi-tenancy
-- Depends on: 023_add_super_admin_and_company_to_users.sql

-- Add company_id to clients table
ALTER TABLE clients 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing clients to belong to default company
UPDATE clients 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);

-- Add company_id to officers table
ALTER TABLE officers 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing officers to belong to default company
UPDATE officers 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);

-- Add company_id to sites table (sites already have client_id, but we need direct company_id for performance)
ALTER TABLE sites 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing sites to belong to default company
UPDATE sites 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);

-- Add company_id to shifts table
ALTER TABLE shifts 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing shifts to belong to default company
UPDATE shifts 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);

-- Add company_id to documents table
ALTER TABLE documents 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing documents to belong to default company
UPDATE documents 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);

-- Add company_id to leave_requests table
ALTER TABLE leave_requests 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing leave_requests to belong to default company
UPDATE leave_requests 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);

-- Add company_id to notifications table
ALTER TABLE notifications 
ADD COLUMN company_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
ADD INDEX idx_company_id (company_id);

-- Update existing notifications to belong to default company
UPDATE notifications 
SET company_id = (SELECT id FROM companies WHERE slug = 'default-company' LIMIT 1);
