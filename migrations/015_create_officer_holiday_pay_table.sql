-- Create officer_holiday_pay table
-- Depends on: officers

CREATE TABLE IF NOT EXISTS officer_holiday_pay (
    id INT PRIMARY KEY AUTO_INCREMENT,
    officer_id INT NOT NULL,
    accrual_year YEAR NOT NULL,
    hours_worked DECIMAL(8,2) DEFAULT 0,
    holiday_hours_accrued DECIMAL(8,2) DEFAULT 0,
    holiday_hours_taken DECIMAL(8,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_officer_year (officer_id, accrual_year)
);
