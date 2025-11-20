-- Migration to add 'cancelled' status to shifts table
-- Date: 2025-09-09
-- Purpose: Add cancellation functionality while keeping cancelled shifts visible

ALTER TABLE shifts 
MODIFY COLUMN status ENUM('unallocated', 'allocated', 'confirmed', 'declined', 'completed', 'cancelled') DEFAULT 'unallocated';

-- Add cancellation fields
ALTER TABLE shifts 
ADD COLUMN cancelled_at TIMESTAMP NULL AFTER status,
ADD COLUMN cancellation_reason TEXT NULL AFTER cancelled_at;
