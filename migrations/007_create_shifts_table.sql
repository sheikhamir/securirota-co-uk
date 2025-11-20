-- Create shifts table
-- Depends on: sites, officers, roles

CREATE TABLE IF NOT EXISTS shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    officer_id INT,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    role VARCHAR(50) DEFAULT 'Security Officer',
    role_id INT,
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('unallocated', 'allocated', 'confirmed', 'declined', 'completed', 'cancelled') DEFAULT 'unallocated',
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    rescheduled BOOLEAN DEFAULT FALSE,
    reschedule_reason TEXT,
    officer_rate DECIMAL(8,2),
    client_rate DECIMAL(8,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    decline_reason TEXT,
    checkin_image VARCHAR(255),
    checkout_image VARCHAR(255),
    checkin_timestamp TIMESTAMP NULL,
    checkout_timestamp TIMESTAMP NULL,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_cancelled_at (cancelled_at),
    INDEX idx_rescheduled (rescheduled),
    INDEX idx_status_rescheduled (status, rescheduled)
);
