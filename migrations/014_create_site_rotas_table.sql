-- Create site_rotas table
-- Depends on: sites

CREATE TABLE IF NOT EXISTS site_rotas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_id (site_id)
);
