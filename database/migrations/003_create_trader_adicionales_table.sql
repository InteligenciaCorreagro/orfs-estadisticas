-- database/migrations/003_create_trader_adicionales_table.sql

CREATE TABLE IF NOT EXISTS trader_adicionales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trader_id BIGINT UNSIGNED NOT NULL,
    nombre_adicional VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (trader_id) REFERENCES traders(id) ON DELETE CASCADE,
    INDEX idx_trader_id (trader_id),
    INDEX idx_nombre_adicional (nombre_adicional)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;