-- Site Rota Templates Migration
-- This will create tables to manage recurring shift patterns for each site

-- Site Rota Templates table - stores recurring patterns for each site
CREATE TABLE IF NOT EXISTS site_rota_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_id (site_id),
    INDEX idx_active (is_active)
);

-- Site Rota Template Shifts - defines individual shift patterns within a template
CREATE TABLE IF NOT EXISTS site_rota_template_shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    role VARCHAR(50) DEFAULT 'Security Officer',
    required_officers INT DEFAULT 1,
    priority INT DEFAULT 1 COMMENT 'Priority for shift filling (1=highest)',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES site_rota_templates(id) ON DELETE CASCADE,
    INDEX idx_template_day (template_id, day_of_week),
    INDEX idx_day_of_week (day_of_week)
);

-- Site Rota Generations - tracks when rotas are generated from templates
CREATE TABLE IF NOT EXISTS site_rota_generations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    template_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    week_end_date DATE NOT NULL,
    generated_by INT,
    shifts_created INT DEFAULT 0,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES site_rota_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_site_week (site_id, week_start_date),
    INDEX idx_site_date (site_id, week_start_date)
);

-- Insert some sample rota templates for existing sites
INSERT INTO site_rota_templates (site_id, template_name, description) 
SELECT id, CONCAT(site_name, ' - Standard Security'), 'Standard security coverage for this site'
FROM sites 
WHERE status = 'active'
ON DUPLICATE KEY UPDATE template_name = template_name;

-- Insert sample template shifts for the templates we just created
-- This creates a basic Mon-Fri 9-5 pattern for each site
INSERT INTO site_rota_template_shifts (template_id, day_of_week, start_time, end_time, role, required_officers)
SELECT 
    t.id,
    weekday,
    '09:00:00',
    '17:00:00', 
    'Security Officer',
    1
FROM site_rota_templates t
CROSS JOIN (
    SELECT 1 as weekday UNION ALL 
    SELECT 2 UNION ALL 
    SELECT 3 UNION ALL 
    SELECT 4 UNION ALL 
    SELECT 5
) days
WHERE t.template_name LIKE '%Standard Security%'
ON DUPLICATE KEY UPDATE start_time = start_time;

-- Insert weekend coverage (Saturday morning only)
INSERT INTO site_rota_template_shifts (template_id, day_of_week, start_time, end_time, role, required_officers)
SELECT 
    t.id,
    6, -- Saturday
    '10:00:00',
    '14:00:00', 
    'Security Officer',
    1
FROM site_rota_templates t
WHERE t.template_name LIKE '%Standard Security%'
ON DUPLICATE KEY UPDATE start_time = start_time;
