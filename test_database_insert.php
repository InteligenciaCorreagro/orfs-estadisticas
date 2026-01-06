<?php
/**
 * Test del método Database::insert()
 */

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

echo "=== TEST DATABASE INSERT ===\n\n";

// Datos de prueba exactamente como los genera RuedaProcessor
$registro = [
    'reasig' => null,
    'nit' => '123456789',
    'nombre' => 'Cliente de Prueba',
    'corredor' => 'Trader Test',
    'comi_porcentual' => 2.5,
    'ciudad' => 'Bogotá',
    'fecha' => '2026-01-06',
    'rueda_no' => 1,
    'negociado' => 1000000.00,
    'comi_bna' => 0.0,
    'campo_209' => 0.0,
    'comi_corr' => 25000.00,
    'iva_bna' => 0.0,
    'iva_comi' => 0.0,
    'iva_cama' => 0.0,
    'facturado' => 0.0,
    'mes' => 'Enero',
    'comi_corr_neto' => 25000.00,
    'year' => 2026
];

echo "--- Datos de prueba ---\n";
print_r($registro);
echo "\n";

echo "--- Validando claves ---\n";
foreach ($registro as $key => $value) {
    $esString = is_string($key);
    $esVacio = $key === '';
    $esNumerico = is_numeric($key);
    $esValida = $esString && !$esVacio && !$esNumerico;

    echo "'{$key}' => is_string: " . ($esString ? 'true' : 'false') .
         ", is_empty: " . ($esVacio ? 'true' : 'false') .
         ", is_numeric: " . ($esNumerico ? 'true' : 'false') .
         ", VALIDA: " . ($esValida ? 'true' : 'false') . "\n";
}
echo "\n";

echo "--- Construyendo SQL ---\n";
$columns = implode(', ', array_keys($registro));
$placeholders = ':' . implode(', :', array_keys($registro));

echo "Columns: {$columns}\n\n";
echo "Placeholders: {$placeholders}\n\n";

$sql = "INSERT INTO orfs_transactions ({$columns}) VALUES ({$placeholders})";
echo "SQL completo:\n{$sql}\n\n";

echo "--- Preparando parámetros para PDO ---\n";
echo "Número de parámetros: " . count($registro) . "\n";
foreach ($registro as $key => $value) {
    $tipo = gettype($value);
    $valorStr = is_null($value) ? 'NULL' : (is_string($value) ? "'{$value}'" : $value);
    echo "  :{$key} => {$valorStr} ({$tipo})\n";
}
echo "\n";

echo "--- Intentando inserción ---\n";
try {
    Database::beginTransaction();

    $id = Database::insert('orfs_transactions', $registro);

    echo "✓ ÉXITO! Registro insertado con ID: {$id}\n";

    // Verificar que se insertó
    $inserted = Database::fetch(
        "SELECT * FROM orfs_transactions WHERE id = ?",
        [$id]
    );

    echo "\n--- Registro insertado ---\n";
    print_r($inserted);

    Database::rollback();
    echo "\n✓ Rollback ejecutado (no se guardó permanentemente)\n";

} catch (PDOException $e) {
    Database::rollback();
    echo "❌ ERROR PDO:\n";
    echo "  Código: {$e->getCode()}\n";
    echo "  Mensaje: {$e->getMessage()}\n";
    echo "  SQL State: {$e->errorInfo[0]}\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    if (Database::getInstance()->inTransaction()) {
        Database::rollback();
    }
    echo "❌ ERROR:\n";
    echo "  {$e->getMessage()}\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIN TEST ===\n";
