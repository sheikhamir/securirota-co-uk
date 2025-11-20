-- Migration to add 'cancel_shift' action type to activity_log table
-- Date: 2025-09-09
-- Purpose: Support logging of shift cancellation activities

ALTER TABLE activity_log 
MODIFY COLUMN action_type ENUM(
    'create_shift', 'update_shift', 'delete_shift', 'confirm_shift', 'reschedule_shift', 'cancel_shift',
    'create_client', 'update_client', 'delete_client',
    'create_site', 'update_site', 'delete_site',
    'create_officer', 'update_officer', 'delete_officer',
    'create_user', 'update_user', 'delete_user',
    'generate_invoice', 'generate_report',
    'login', 'logout'
) NOT NULL;
