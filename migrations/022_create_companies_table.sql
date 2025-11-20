-- Migration 022: Create companies table for multi-tenant support
-- This migration creates the companies table to support multiple organizations
-- No dependencies - this is a new base table

CREATE TABLE IF NOT EXISTS companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL COMMENT 'URL-friendly identifier',
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    postcode VARCHAR(20),
    country VARCHAR(100) DEFAULT 'United Kingdom',
    
    -- Business details
    registration_number VARCHAR(100),
    vat_number VARCHAR(50),
    
    -- Subscription details
    subscription_plan ENUM('basic', 'professional', 'enterprise') DEFAULT 'basic',
    subscription_status ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
    subscription_start_date DATE,
    subscription_end_date DATE,
    max_officers INT DEFAULT 50 COMMENT 'Maximum number of officers allowed',
    max_sites INT DEFAULT 20 COMMENT 'Maximum number of sites allowed',
    
    -- Features
    features JSON COMMENT 'Enabled features for this company',
    
    -- Billing
    billing_contact_name VARCHAR(255),
    billing_email VARCHAR(255),
    billing_address TEXT,
    
    -- Settings
    settings JSON COMMENT 'Company-specific settings and preferences',
    
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_subscription_status (subscription_status)
);

-- Insert a default company for existing data migration
INSERT INTO companies (
    name, 
    slug, 
    email, 
    subscription_plan, 
    subscription_status,
    subscription_start_date,
    max_officers,
    max_sites,
    status
) VALUES (
    'Default Security Company',
    'default-company',
    'admin@security.com',
    'enterprise',
    'active',
    CURDATE(),
    999,
    999,
    'active'
);
