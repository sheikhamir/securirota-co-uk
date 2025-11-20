-- Create shift_activities table
-- Depends on: shifts

CREATE TABLE IF NOT EXISTS shift_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    activity_type ENUM('checkin', 'checkout', 'break_start', 'break_end', 'incident') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location_data JSON NULL,
    notes TEXT,
    image_path VARCHAR(255),
    
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    INDEX idx_shift_id (shift_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_timestamp (timestamp)
);
