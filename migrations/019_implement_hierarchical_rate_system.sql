-- Migration 019: Implement Hierarchical Rate System
-- This migration sets up site-based rates that override client rates
-- Depends on: clients, sites, shifts

-- Add clear rate fields to sites table for different rate types
ALTER TABLE sites 
ADD COLUMN IF NOT EXISTS client_rate DECIMAL(8,2) NULL COMMENT 'Site-specific client rate (overrides client billing_rate if set)',
ADD COLUMN IF NOT EXISTS officer_rate DECIMAL(8,2) NULL COMMENT 'Site-specific officer rate (overrides default if set)',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update sites table to make rate columns more explicit
-- Rename hourly_rate to default_rate for clarity if it exists
SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_name = 'sites' 
        AND table_schema = DATABASE()
        AND column_name = 'hourly_rate'
    ),
    'ALTER TABLE sites CHANGE hourly_rate default_rate DECIMAL(8,2) DEFAULT 0.00 COMMENT "Default site rate (legacy)"',
    'SELECT "hourly_rate column does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add rate calculation helper view
CREATE OR REPLACE VIEW site_effective_rates AS
SELECT 
    s.id as site_id,
    s.site_name,
    s.client_id,
    c.company_name as client_name,
    
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
    END as officer_rate_source,
    
    s.client_rate as site_client_rate,
    c.billing_rate as client_billing_rate,
    s.officer_rate as site_officer_rate
    
FROM sites s
LEFT JOIN clients c ON s.client_id = c.id
WHERE s.status = 'active';

-- Update existing shifts to use the new rate calculation if they don't have rates set
UPDATE shifts sh
JOIN site_effective_rates ser ON sh.site_id = ser.site_id
SET 
    sh.client_rate = ser.effective_client_rate,
    sh.officer_rate = ser.effective_officer_rate
WHERE sh.client_rate IS NULL OR sh.client_rate = 0.00;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_sites_client_rate ON sites(client_rate);
CREATE INDEX IF NOT EXISTS idx_sites_officer_rate ON sites(officer_rate);
CREATE INDEX IF NOT EXISTS idx_shifts_rates ON shifts(client_rate, officer_rate);

-- Add comment explaining the rate hierarchy
ALTER TABLE sites COMMENT = 'Sites table with hierarchical rate system: site rates override client rates';