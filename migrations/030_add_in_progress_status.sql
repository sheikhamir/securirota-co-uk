-- Add 'in_progress' status to shifts table
-- This allows tracking shifts that have been checked in but not yet checked out

ALTER TABLE shifts 
MODIFY COLUMN status ENUM('unallocated', 'allocated', 'confirmed', 'declined', 'in_progress', 'completed', 'cancelled') DEFAULT 'unallocated';