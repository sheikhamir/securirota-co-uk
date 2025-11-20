-- Add share code field to officers table
-- This field will store the UK government Share Code for right to work verification

ALTER TABLE officers 
ADD COLUMN share_code VARCHAR(20) AFTER right_to_work_reference;