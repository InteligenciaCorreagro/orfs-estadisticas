<?php
// debug_login_flow.php

require_once __DIR__ . '/bootstrap.php';

use App\Core\Session;
use App\Services\Auth\AuthService;

echo "=== DEBUG LOGIN FLOW ===\n\n";

// 1. Iniciar sesión
Session::start();
echo "1. Sesión iniciada\n";
echo "   Session ID: " . session_id() . "\n\n";

// 2. Intentar login
$authService = new AuthService();
$email = 'admin@correagro.com';
$password = 'Admin123';

echo "2. Intentando login con:\n";
echo "   Email: $email\n";
echo "   Password: $password\n\n";

$result = $authService->login($email, $password);

echo "3. Resultado del login:\n";
echo "   Success: " . ($result['success'] ? 'SÍ' : 'NO') . "\n";
echo "   Message: " . $result['message'] . "\n\n";

// 3. Verificar datos en sesión
echo "4. Datos guardados en sesión:\n";
echo "   user_id: " . Session::get('user_id', 'NO GUARDADO') . "\n";
echo "   user_name: " . Session::get('user_name', 'NO GUARDADO') . "\n";
echo "   user_email: " . Session::get('user_email', 'NO GUARDADO') . "\n";
echo "   user_role: " . Session::get('user_role', 'NO GUARDADO') . "\n";
echo "   trader_name: " . Session::get('trader_name', 'NO GUARDADO') . "\n\n";

// 4. Verificar autenticación
echo "5. Verificando autenticación:\n";
echo "   ¿Está autenticado? " . ($authService->check() ? 'SÍ' : 'NO') . "\n";
echo "   ¿Es admin? " . ($authService->isAdmin() ? 'SÍ' : 'NO') . "\n\n";

// 5. Dump de toda la sesión
echo "6. Dump completo de \$_SESSION:\n";
print_r($_SESSION);

echo "\n=== FIN DEL DEBUG ===\n";