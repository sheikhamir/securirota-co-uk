-- Add custom officer rate field to shifts table
-- This allows setting a specific rate for a shift that overrides the officer's default rate

-- Add custom_officer_rate column (nullable - only set when custom rate is needed)
ALTER TABLE shifts 
ADD COLUMN custom_officer_rate DECIMAL(8,2) NULL COMMENT 'Custom rate for this specific shift - overrides officer default rate when set' 
AFTER officer_rate;

-- Add index for performance when checking custom rates
CREATE INDEX idx_custom_officer_rate ON shifts(custom_officer_rate);

-- Update shifts table comment to reflect new custom rate functionality
ALTER TABLE shifts COMMENT = 'Shifts table: client_rate from site hierarchy, officer_rate from individual officer rates or custom_officer_rate override';