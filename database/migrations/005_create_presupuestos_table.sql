-- database/migrations/005_create_presupuestos_table.sql

CREATE TABLE IF NOT EXISTS presupuestos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nit VARCHAR(20) NOT NULL,
    corredor VARCHAR(255) NOT NULL,
    mes INT NOT NULL,
    year INT NOT NULL,
    transado_presupuesto DECIMAL(15,2) DEFAULT 0.00,
    comision_presupuesto DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_ppto (nit, corredor, mes, year),
    INDEX idx_corredor_year (corredor, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;