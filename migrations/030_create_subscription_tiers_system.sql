-- Create subscription tiers system
-- This migration adds subscription tiers management for the root user

-- Create subscription_tiers table
CREATE TABLE IF NOT EXISTS `subscription_tiers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `max_officers` int(11) NOT NULL DEFAULT 0,
    `max_sites` int(11) NOT NULL DEFAULT 0,
    `max_clients` int(11) NOT NULL DEFAULT 0,
    `monthly_price` decimal(10,2) DEFAULT 0.00,
    `yearly_price` decimal(10,2) DEFAULT 0.00,
    `is_custom` tinyint(1) DEFAULT 0 COMMENT 'Is this a custom contact-us tier',
    `is_active` tinyint(1) DEFAULT 1,
    `features` json DEFAULT NULL COMMENT 'Additional features included in tier',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tier_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add subscription_tier_id to companies table
ALTER TABLE `companies` 
ADD COLUMN IF NOT EXISTS `subscription_tier_id` int(11) DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `subscription_start_date` date DEFAULT NULL AFTER `subscription_tier_id`,
ADD COLUMN IF NOT EXISTS `subscription_end_date` date DEFAULT NULL AFTER `subscription_start_date`,
ADD COLUMN IF NOT EXISTS `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly' AFTER `subscription_end_date`,
ADD COLUMN IF NOT EXISTS `last_billing_date` date DEFAULT NULL AFTER `billing_cycle`,
ADD COLUMN IF NOT EXISTS `next_billing_date` date DEFAULT NULL AFTER `last_billing_date`,
ADD COLUMN IF NOT EXISTS `billing_status` enum('active','suspended','overdue','cancelled') DEFAULT 'active' AFTER `next_billing_date`;

-- Add foreign key constraint
ALTER TABLE `companies` 
ADD CONSTRAINT `fk_companies_subscription_tier` 
FOREIGN KEY (`subscription_tier_id`) REFERENCES `subscription_tiers`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Insert default subscription tiers
INSERT INTO `subscription_tiers` 
(`name`, `description`, `max_officers`, `max_sites`, `max_clients`, `monthly_price`, `yearly_price`, `is_custom`, `features`) 
VALUES 
('Basic', 'Perfect for small security companies getting started', 100, 25, 25, 99.99, 999.99, 0, 
 JSON_OBJECT(
    'shift_scheduling', true,
    'basic_reporting', true,
    'officer_management', true,
    'site_management', true,
    'client_management', true,
    'email_notifications', true,
    'mobile_app_access', false,
    'advanced_reporting', false,
    'custom_branding', false,
    'api_access', false,
    'priority_support', false
 )),
('Professional', 'Ideal for growing security companies', 250, 100, 100, 199.99, 1999.99, 0,
 JSON_OBJECT(
    'shift_scheduling', true,
    'basic_reporting', true,
    'officer_management', true,
    'site_management', true,
    'client_management', true,
    'email_notifications', true,
    'mobile_app_access', true,
    'advanced_reporting', true,
    'custom_branding', false,
    'api_access', false,
    'priority_support', true
 )),
('Enterprise', 'For large security operations', 500, 250, 500, 399.99, 3999.99, 0,
 JSON_OBJECT(
    'shift_scheduling', true,
    'basic_reporting', true,
    'officer_management', true,
    'site_management', true,
    'client_management', true,
    'email_notifications', true,
    'mobile_app_access', true,
    'advanced_reporting', true,
    'custom_branding', true,
    'api_access', true,
    'priority_support', true,
    'dedicated_account_manager', true,
    'custom_integrations', true
 )),
('Custom', 'Contact us for custom pricing and features', 999999, 999999, 999999, 0.00, 0.00, 1,
 JSON_OBJECT(
    'shift_scheduling', true,
    'basic_reporting', true,
    'officer_management', true,
    'site_management', true,
    'client_management', true,
    'email_notifications', true,
    'mobile_app_access', true,
    'advanced_reporting', true,
    'custom_branding', true,
    'api_access', true,
    'priority_support', true,
    'dedicated_account_manager', true,
    'custom_integrations', true,
    'custom_development', true,
    'on_premise_deployment', true
 ));

-- Create billing_history table for tracking billing events
CREATE TABLE IF NOT EXISTS `billing_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11) NOT NULL,
    `subscription_tier_id` int(11) NOT NULL,
    `billing_date` date NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `billing_cycle` enum('monthly','yearly') NOT NULL,
    `status` enum('pending','paid','failed','cancelled') DEFAULT 'pending',
    `invoice_number` varchar(50) DEFAULT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company_id` (`company_id`),
    KEY `idx_billing_date` (`billing_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_billing_history_company` 
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_billing_history_subscription_tier` 
        FOREIGN KEY (`subscription_tier_id`) REFERENCES `subscription_tiers`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subscription_usage_tracking table for monitoring company usage
CREATE TABLE IF NOT EXISTS `subscription_usage_tracking` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11) NOT NULL,
    `tracking_date` date NOT NULL,
    `officers_count` int(11) DEFAULT 0,
    `sites_count` int(11) DEFAULT 0,
    `clients_count` int(11) DEFAULT 0,
    `shifts_created` int(11) DEFAULT 0,
    `users_active` int(11) DEFAULT 0,
    `api_calls` int(11) DEFAULT 0,
    `storage_used_mb` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_date` (`company_id`, `tracking_date`),
    KEY `idx_tracking_date` (`tracking_date`),
    CONSTRAINT `fk_usage_tracking_company` 
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing companies to have basic tier by default
UPDATE `companies` 
SET `subscription_tier_id` = (SELECT id FROM subscription_tiers WHERE name = 'Basic' LIMIT 1),
    `subscription_start_date` = CURDATE(),
    `subscription_end_date` = DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
    `billing_cycle` = 'monthly',
    `next_billing_date` = DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
    `billing_status` = 'active'
WHERE `subscription_tier_id` IS NULL;