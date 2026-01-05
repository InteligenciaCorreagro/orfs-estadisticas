<?php
// database/migrate.php

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

echo "=== Sistema de Migraciones ORFS ===\n\n";

// Conectar a la base de datos
try {
    $pdo = Database::getInstance();
    echo "✓ Conexión a base de datos establecida\n\n";
} catch (Exception $e) {
    die("✗ Error de conexión: " . $e->getMessage() . "\n");
}

// Directorio de migraciones
$migrationsDir = __DIR__ . '/migrations/';
$migrations = glob($migrationsDir . '*.sql');

sort($migrations);

echo "Ejecutando migraciones...\n\n";

foreach ($migrations as $migration) {
    $filename = basename($migration);
    echo "Ejecutando: $filename ... ";
    
    try {
        $sql = file_get_contents($migration);
        $pdo->exec($sql);
        echo "✓\n";
    } catch (PDOException $e) {
        echo "✗\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Seeds ===\n\n";

// Ejecutar seeds
$seedsDir = __DIR__ . '/seeds/';
$seeds = glob($seedsDir . '*.sql');

sort($seeds);

echo "Ejecutando seeds...\n\n";

foreach ($seeds as $seed) {
    $filename = basename($seed);
    echo "Ejecutando: $filename ... ";
    
    try {
        $sql = file_get_contents($seed);
        $pdo->exec($sql);
        echo "✓\n";
    } catch (PDOException $e) {
        echo "✗\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Migraciones completadas!\n\n";
echo "Credenciales de acceso:\n";
echo "Admin: admin@correagro.com / Admin123\n";
echo "Trader: trader@correagro.com / Trader123\n\n";