-- database/migrations/009_create_historical_uploads_table.sql
-- Table to store historical file uploads by year

CREATE TABLE IF NOT EXISTS historical_uploads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP NULL,
    records_count INT DEFAULT 0,
    notes TEXT NULL,

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_year (year),
    INDEX idx_uploaded_by (uploaded_by),
    UNIQUE KEY unique_year_file (year, filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
