-- Migration: Update shifts table to use role_id instead of role name
-- Migration: 2025_09_10_update_shifts_to_use_role_id.sql

-- First, add the new role_id column
ALTER TABLE shifts ADD COLUMN role_id INT NULL AFTER role;

-- Add foreign key constraint to roles table
ALTER TABLE shifts ADD CONSTRAINT fk_shifts_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Update existing shifts to map role names to role IDs
UPDATE shifts s 
INNER JOIN roles r ON s.role = r.name 
SET s.role_id = r.id;

-- For any shifts with roles that don't exist in the roles table, set to the default 'Security' role
UPDATE shifts s 
SET s.role_id = (SELECT id FROM roles WHERE name = 'Security' LIMIT 1)
WHERE s.role_id IS NULL AND s.role IS NOT NULL;

-- Now we can drop the old role column (but let's keep it for now as backup)
-- ALTER TABLE shifts DROP COLUMN role;

-- Create index for performance
CREATE INDEX idx_shifts_role_id ON shifts (role_id);
