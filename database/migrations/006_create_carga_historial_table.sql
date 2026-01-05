-- database/migrations/006_create_carga_historial_table.sql

CREATE TABLE IF NOT EXISTS carga_historial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    archivo_nombre VARCHAR(255) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    ruedas_procesadas JSON NULL,
    registros_insertados INT DEFAULT 0,
    estado ENUM('exitoso', 'fallido', 'parcial') DEFAULT 'exitoso',
    mensaje TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;