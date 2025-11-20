-- Migration: Simplify roles table by removing name field and renaming display_name to name
-- Migration: 2025_09_10_simplify_roles_table_fixed.sql

-- First, let's update any existing shifts that might still reference the old role names
UPDATE shifts s 
INNER JOIN roles r ON s.role = r.name 
SET s.role_id = r.id 
WHERE s.role_id IS NULL AND s.role IS NOT NULL;

-- Drop the old name column first (the short identifier)
ALTER TABLE roles DROP COLUMN name;

-- Rename display_name to name (this will be our primary name field)
ALTER TABLE roles CHANGE COLUMN display_name name VARCHAR(100) NOT NULL;

-- Remove sort_order as we can order by name or id
ALTER TABLE roles DROP COLUMN sort_order;

-- Final structure: id, name (was display_name), description, is_active, created_at, updated_at
