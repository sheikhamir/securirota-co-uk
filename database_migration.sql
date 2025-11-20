-- Database Migration Script for SecurityRota Users Module
-- This script updates the database structure to support the Users module

-- Add email column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER username;

-- Add user_id column to officers table if it doesn't exist  
ALTER TABLE officers ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER id;

-- Add foreign key constraint for user_id in officers table
-- Note: This will fail silently if the constraint already exists
ALTER TABLE officers ADD CONSTRAINT fk_officers_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing users with email addresses if they don't have them
UPDATE users SET email = CONCAT(username, '@security.com') WHERE email IS NULL OR email = '';

-- Make email column NOT NULL after updating existing records
ALTER TABLE users MODIFY COLUMN email VARCHAR(100) NOT NULL;

-- Add unique constraint on email if it doesn't exist
ALTER TABLE users ADD CONSTRAINT UNIQUE(email);

-- Update admin user email if it's the default
UPDATE users SET email = 'admin@security.com' WHERE username = 'admin' AND email = 'admin@security.com';

-- Show final structure
SELECT 'Users table structure:' as info;
DESCRIBE users;

SELECT 'Officers table structure (first 10 columns):' as info;
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'officers' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION 
LIMIT 10;

SELECT 'Current users:' as info;
SELECT id, username, email, role, status FROM users ORDER BY id;
