-- Fix character encoding issues in countries table
-- This migration fixes UTF-8 character encoding problems

-- Convert table to UTF-8
ALTER TABLE countries CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Fix any corrupted country names with special characters
UPDATE countries SET name = 'São Tomé and Príncipe' WHERE code = 'STP';
UPDATE countries SET name = 'Côte d\'Ivoire' WHERE code = 'CIV';

-- Verify the fix
SELECT 'Character encoding fix completed. Countries with special characters:' as message;
SELECT name, code FROM countries WHERE name LIKE '%ã%' OR name LIKE '%ô%' OR name LIKE '%é%' OR name LIKE '%í%' ORDER BY name;
