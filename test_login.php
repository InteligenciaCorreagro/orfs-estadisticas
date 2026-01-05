<?php
// test_login.php

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Models\User;

echo "=== TEST DE LOGIN ===\n\n";

$email = 'admin@correagro.com';
$password = 'Admin123';

echo "Intentando login con:\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// 1. Buscar usuario
echo "1. Buscando usuario...\n";
$user = User::findByEmail($email);

if (!$user) {
    die("✗ Usuario no encontrado\n");
}

echo "✓ Usuario encontrado: {$user->name}\n\n";

// 2. Verificar hash de contraseña en BD
echo "2. Hash en base de datos:\n";
echo substr($user->password, 0, 60) . "...\n\n";

// 3. Verificar contraseña
echo "3. Verificando contraseña...\n";
$isValid = $user->verifyPassword($password);

if ($isValid) {
    echo "✓ Contraseña CORRECTA\n";
} else {
    echo "✗ Contraseña INCORRECTA\n";
    
    // Debug adicional
    echo "\nDebug:\n";
    echo "- Password ingresado: $password\n";
    echo "- Hash en BD: " . substr($user->password, 0, 20) . "...\n";
    echo "- Resultado de password_verify: " . var_export(password_verify($password, $user->password), true) . "\n";
}

echo "\n4. Verificando hash manualmente...\n";
$testHash = password_hash($password, PASSWORD_BCRYPT);
echo "Nuevo hash generado: " . substr($testHash, 0, 60) . "...\n";
echo "¿Verifica correctamente? " . (password_verify($password, $testHash) ? "✓ SÍ" : "✗ NO") . "\n";

echo "\n=== FIN DEL TEST ===\n";