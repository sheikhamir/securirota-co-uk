-- Migration 029: Add subscription expiry system
-- This migration enhances the companies table with subscription expiry functionality
-- Dependencies: 022_create_companies_table.sql

-- Add subscription_expires_at column for more explicit expiry tracking
ALTER TABLE companies 
ADD COLUMN subscription_expires_at DATETIME NULL COMMENT 'Explicit subscription expiry date and time';

-- Add index for efficient expiry checks
ALTER TABLE companies 
ADD INDEX idx_subscription_expires_at (subscription_expires_at);

-- Add subscription grace period (optional - for companies with payment delays)
ALTER TABLE companies 
ADD COLUMN subscription_grace_period_days INT DEFAULT 0 COMMENT 'Grace period in days after expiry';

-- Add last subscription check timestamp to avoid repeated checks
ALTER TABLE companies 
ADD COLUMN last_subscription_check TIMESTAMP NULL COMMENT 'Last time subscription was checked';

-- Update the existing company to have subscription that expires at end of September 2025
UPDATE companies 
SET subscription_expires_at = '2025-09-30 23:59:59',
    subscription_end_date = '2025-09-30',
    last_subscription_check = NOW()
WHERE slug = 'default-company' OR id = 1;

-- Create a view for active companies (non-expired subscriptions)
CREATE OR REPLACE VIEW active_companies AS
SELECT c.*,
    CASE 
        WHEN c.subscription_expires_at IS NULL THEN 1
        WHEN c.subscription_expires_at > NOW() THEN 1
        WHEN c.subscription_grace_period_days > 0 AND 
             DATE_ADD(c.subscription_expires_at, INTERVAL c.subscription_grace_period_days DAY) > NOW() THEN 1
        ELSE 0
    END as is_subscription_active
FROM companies c
WHERE c.status = 'active' 
AND (
    c.subscription_expires_at IS NULL OR 
    c.subscription_expires_at > NOW() OR
    (c.subscription_grace_period_days > 0 AND DATE_ADD(c.subscription_expires_at, INTERVAL c.subscription_grace_period_days DAY) > NOW())
);

-- Create function to check if a company's subscription is active
DELIMITER //
CREATE OR REPLACE FUNCTION is_company_subscription_active(company_id_param INT) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE expires_at DATETIME;
    DECLARE grace_days INT;
    DECLARE is_active BOOLEAN DEFAULT FALSE;
    
    SELECT subscription_expires_at, subscription_grace_period_days
    INTO expires_at, grace_days
    FROM companies 
    WHERE id = company_id_param AND status = 'active';
    
    -- If no expiry date set, consider active
    IF expires_at IS NULL THEN
        SET is_active = TRUE;
    -- Check if still within subscription period
    ELSEIF expires_at > NOW() THEN
        SET is_active = TRUE;
    -- Check if within grace period
    ELSEIF grace_days > 0 AND DATE_ADD(expires_at, INTERVAL grace_days DAY) > NOW() THEN
        SET is_active = TRUE;
    ELSE
        SET is_active = FALSE;
    END IF;
    
    RETURN is_active;
END//
DELIMITER ;