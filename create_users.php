<?php
// create_users.php

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;

echo "=== CREAR USUARIOS CON CONTRASEÑAS CORRECTAS ===\n\n";

try {
    $pdo = Database::getInstance();
    
    // Eliminar usuarios existentes
    $pdo->exec("DELETE FROM users");
    echo "✓ Usuarios anteriores eliminados\n\n";
    
    // Crear usuarios con contraseñas hasheadas correctamente
    $users = [
        [
            'name' => 'Administrador',
            'email' => 'admin@correagro.com',
            'password' => 'Admin123',
            'role' => 'admin',
            'trader_name' => null
        ],
        [
            'name' => 'Trader Demo',
            'email' => 'trader@correagro.com',
            'password' => 'Trader123',
            'role' => 'trader',
            'trader_name' => 'LUIS FERNANDO VELEZ VELEZ'
        ],
        [
            'name' => 'Usuario Invitado',
            'email' => 'guest@correagro.com',
            'password' => 'Guest123',
            'role' => 'guest',
            'trader_name' => null
        ]
    ];
    
    foreach ($users as $userData) {
        // Hashear contraseña correctamente
        $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (name, email, password, role, trader_name, activo) 
                VALUES (:name, :email, :password, :role, :trader_name, 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => $hashedPassword,
            'role' => $userData['role'],
            'trader_name' => $userData['trader_name']
        ]);
        
        echo "✓ Usuario creado: {$userData['email']} / {$userData['password']}\n";
    }
    
    echo "\n=== USUARIOS CREADOS EXITOSAMENTE ===\n\n";
    echo "Credenciales:\n";
    echo "Admin:  admin@correagro.com  / Admin123\n";
    echo "Trader: trader@correagro.com / Trader123\n";
    echo "Guest:  guest@correagro.com  / Guest123\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}