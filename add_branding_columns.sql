-- Add branding and customization columns to companies table
ALTER TABLE companies 
ADD COLUMN brand_primary_color VARCHAR(7) DEFAULT '#0066cc',
ADD COLUMN brand_secondary_color VARCHAR(7) DEFAULT '#6c757d',
ADD COLUMN brand_logo_url VARCHAR(255) NULL,
ADD COLUMN brand_favicon_url VARCHAR(255) NULL,
ADD COLUMN display_company_name VARCHAR(100) NULL,
ADD COLUMN display_timezone VARCHAR(50) DEFAULT 'Europe/London',
ADD COLUMN display_date_format VARCHAR(20) DEFAULT 'Y-m-d',
ADD COLUMN display_time_format VARCHAR(20) DEFAULT 'H:i',
ADD COLUMN feature_settings JSON NULL,
ADD COLUMN custom_css TEXT NULL;
