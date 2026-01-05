-- database/seeds/users_seed.sql

-- Usuario admin por defecto
-- Email: admin@correagro.com
-- Password: Admin123

INSERT INTO users (name, email, password, role, activo) VALUES
('Administrador', 'admin@correagro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Usuario trader de ejemplo
-- Email: trader@correagro.com
-- Password: Trader123

INSERT INTO users (name, email, password, role, trader_name, activo) VALUES
('Trader Demo', 'trader@correagro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trader', 'LUIS FERNANDO VELEZ VELEZ', 1);