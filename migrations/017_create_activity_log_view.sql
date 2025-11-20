-- Create activity_log_view
-- Depends on: activity_log, users, officers

CREATE OR REPLACE VIEW activity_log_view AS
SELECT 
    al.*,
    u.username,
    u.email,
    CONCAT(o.first_name, ' ', o.last_name) as full_name
FROM activity_log al
LEFT JOIN users u ON al.user_id = u.id
LEFT JOIN officers o ON u.id = o.user_id
ORDER BY al.created_at DESC;
