-- Migration 020: Fix Hierarchical Rate System - Remove Site Officer Rate
-- Remove officer_rate from sites table as it should come from individual officers
-- Site rates are for client billing only, officer rates come from officer records

-- Remove the officer_rate column from sites table
ALTER TABLE sites DROP COLUMN IF EXISTS officer_rate;

-- Update the view to use individual officer rates
DROP VIEW IF EXISTS site_effective_rates;

CREATE VIEW site_effective_rates AS
SELECT 
    s.id as site_id,
    s.site_name,
    s.client_id,
    c.company_name as client_name,
    
    -- Effective client rate: site_rate takes precedence over client rate
    COALESCE(s.client_rate, c.billing_rate, 0.00) as effective_client_rate,
    
    -- Rate source indicators
    CASE 
        WHEN s.client_rate IS NOT NULL THEN 'site'
        WHEN c.billing_rate IS NOT NULL THEN 'client'
        ELSE 'default'
    END as client_rate_source,
    
    s.client_rate as site_client_rate,
    c.billing_rate as client_billing_rate
    
FROM sites s
LEFT JOIN clients c ON s.client_id = c.id
WHERE s.status = 'active';

-- Remove officer_rate index if it exists
DROP INDEX IF EXISTS idx_sites_officer_rate ON sites;

-- Update shifts table comment to clarify rate sources
ALTER TABLE shifts COMMENT = 'Shifts table: client_rate from site hierarchy, officer_rate from individual officer rates';

-- Update sites table comment
ALTER TABLE sites COMMENT = 'Sites table with client rate hierarchy: site client_rate overrides client billing_rate';