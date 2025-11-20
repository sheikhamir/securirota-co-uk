-- Update countries table documentation
-- Since we're now using alphabetical ordering everywhere, update the sort_order field comment

ALTER TABLE countries MODIFY COLUMN sort_order INT DEFAULT 999 COMMENT 'Legacy field - countries now ordered alphabetically by name';

-- Show confirmation
SELECT 'Countries table updated to use alphabetical ordering. sort_order field is now legacy.' as message;
