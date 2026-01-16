-- database/seeds/business_intelligence_user_seed.sql
-- Crear usuario de inteligencia de negocios

-- Password: BI123456 (bcrypt hash)
INSERT INTO users (name, email, password, role, activo, created_at, updated_at)
VALUES (
    'Inteligencia de Negocios',
    'bi@correagro.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: BI123456
    'business_intelligence',
    TRUE,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    role = 'business_intelligence',
    activo = TRUE;
