-- Create sites table
-- Depends on: clients

CREATE TABLE IF NOT EXISTS sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    site_name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    site_instructions TEXT,
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
