<?php
// test_user_id.php

error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/bootstrap.php';

use App\Models\User;
use App\Core\Database;

echo "=== TEST USER ID ===\n\n";

// 1. Buscar en BD directamente
echo "1. Query directo a BD:\n";
$sql = "SELECT id, name, email FROM users WHERE email = 'admin@correagro.com'";
$result = Database::fetch($sql, []);
echo "   Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// 2. Usar modelo User
echo "2. Usando User::findByEmail():\n";
$user = User::findByEmail('admin@correagro.com');

if ($user) {
    echo "   ✓ Usuario encontrado\n";
    echo "   - ID: " . ($user->id ?? 'NULL') . "\n";
    echo "   - Name: " . ($user->name ?? 'NULL') . "\n";
    echo "   - Email: " . ($user->email ?? 'NULL') . "\n";
    echo "   - Role: " . ($user->role ?? 'NULL') . "\n\n";
    
    echo "3. toArray():\n";
    echo "   " . json_encode($user->toArray(), JSON_PRETTY_PRINT) . "\n\n";
    
    echo "4. Acceso directo a propiedades:\n";
    echo "   \$user->id = " . var_export($user->id, true) . "\n";
    echo "   isset(\$user->id) = " . var_export(isset($user->id), true) . "\n";
    echo "   empty(\$user->id) = " . var_export(empty($user->id), true) . "\n";
} else {
    echo "   ✗ Usuario NO encontrado\n";
}

echo "\n=== FIN DEL TEST ===\n";