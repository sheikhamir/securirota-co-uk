-- Add roles table for dynamic role management
-- Migration: 2025_09_10_create_roles_table.sql

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (name, display_name, description, sort_order) VALUES 
('Security', 'Security Officer', 'Standard security officer role', 1),
('Controller', 'Controller', 'Control room operator', 2),
('Supervisor', 'Supervisor', 'Shift supervisor', 3),
('Manager', 'Manager', 'Site manager', 4);

-- Create index for performance
CREATE INDEX idx_roles_active_sort ON roles (is_active, sort_order);
