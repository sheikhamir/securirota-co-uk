-- Migration 025: Add company_id to remaining tables and create company management views
-- This migration completes the multi-tenancy setup for all remaining tables
-- Depends on: 024_add_company_id_to_tenant_tables.sql

-- Check if other tables exist and add company_id if they do

-- Add company_id to activity_log table if it exists
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.TABLES 
        WHERE table_name = 'activity_log' 
        AND table_schema = DATABASE()
    ),
    'ALTER TABLE activity_log 
     ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id,
     ADD INDEX IF NOT EXISTS idx_company_id (company_id)',
    'SELECT "activity_log table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update activity_log records to belong to default company
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.TABLES 
        WHERE table_name = 'activity_log' 
        AND table_schema = DATABASE()
    ),
    'UPDATE activity_log 
     SET company_id = (SELECT id FROM companies WHERE slug = "default-company" LIMIT 1)
     WHERE company_id IS NULL',
    'SELECT "activity_log table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add company_id to email_templates table if it exists
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.TABLES 
        WHERE table_name = 'email_templates' 
        AND table_schema = DATABASE()
    ),
    'ALTER TABLE email_templates 
     ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id,
     ADD INDEX IF NOT EXISTS idx_company_id (company_id)',
    'SELECT "email_templates table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update email_templates records
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.TABLES 
        WHERE table_name = 'email_templates' 
        AND table_schema = DATABASE()
    ),
    'UPDATE email_templates 
     SET company_id = (SELECT id FROM companies WHERE slug = "default-company" LIMIT 1)
     WHERE company_id IS NULL',
    'SELECT "email_templates table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add company_id to site_rotas table if it exists
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.TABLES 
        WHERE table_name = 'site_rotas' 
        AND table_schema = DATABASE()
    ),
    'ALTER TABLE site_rotas 
     ADD COLUMN IF NOT EXISTS company_id INT NOT NULL AFTER id,
     ADD INDEX IF NOT EXISTS idx_company_id (company_id)',
    'SELECT "site_rotas table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update site_rotas records
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.TABLES 
        WHERE table_name = 'site_rotas' 
        AND table_schema = DATABASE()
    ),
    'UPDATE site_rotas 
     SET company_id = (SELECT id FROM companies WHERE slug = "default-company" LIMIT 1)
     WHERE company_id IS NULL OR company_id = 0',
    'SELECT "site_rotas table does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create view for company statistics
CREATE OR REPLACE VIEW company_stats AS
SELECT 
    c.id as company_id,
    c.name as company_name,
    c.slug,
    c.subscription_plan,
    c.subscription_status,
    c.max_officers,
    c.max_sites,
    COUNT(DISTINCT u.id) as total_users,
    COUNT(DISTINCT o.id) as total_officers,
    COUNT(DISTINCT cl.id) as total_clients,
    COUNT(DISTINCT s.id) as total_sites,
    COUNT(DISTINCT sh.id) as total_shifts,
    COUNT(DISTINCT CASE WHEN sh.shift_date >= CURDATE() - INTERVAL 30 DAY THEN sh.id END) as shifts_last_30_days,
    c.created_at,
    c.status
FROM companies c
LEFT JOIN users u ON c.id = u.company_id AND u.status = 'active'
LEFT JOIN officers o ON c.id = o.company_id AND o.status = 'active'
LEFT JOIN clients cl ON c.id = cl.company_id
LEFT JOIN sites s ON c.id = s.company_id AND s.status = 'active'
LEFT JOIN shifts sh ON c.id = sh.company_id
GROUP BY c.id;

-- Create view for multi-tenant site rates (update existing view)
CREATE OR REPLACE VIEW site_effective_rates AS
SELECT 
    s.id as site_id,
    s.name as site_name,
    s.client_id,
    s.company_id,
    c.name as client_name,
    comp.name as company_name,
    
    -- Effective client rate: site_rate takes precedence over client rate
    COALESCE(s.client_rate, c.billing_rate, 0.00) as effective_client_rate,
    
    -- Effective officer rate: site officer rate or default
    COALESCE(s.officer_rate, 0.00) as effective_officer_rate,
    
    -- Rate source indicators
    CASE 
        WHEN s.client_rate IS NOT NULL THEN 'site'
        WHEN c.billing_rate IS NOT NULL THEN 'client'
        ELSE 'default'
    END as client_rate_source,
    
    CASE 
        WHEN s.officer_rate IS NOT NULL THEN 'site'
        ELSE 'default'
    END as officer_rate_source
    
FROM sites s
LEFT JOIN clients c ON s.client_id = c.id
LEFT JOIN companies comp ON s.company_id = comp.id
WHERE s.status = 'active' AND comp.status = 'active';
