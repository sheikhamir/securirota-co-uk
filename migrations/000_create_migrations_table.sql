-- Migration tracking system
-- This table tracks which migrations have been applied

CREATE TABLE IF NOT EXISTS migrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT DEFAULT 0,
    INDEX idx_filename (filename),
    INDEX idx_executed_at (executed_at)
);
