<?php
/**
 * Script de debugging detallado para encontrar el error en la carga
 */

require __DIR__ . '/bootstrap.php';

use App\Services\Excel\ExcelReader;
use App\Services\Excel\ExcelValidator;
use App\Core\Database;

echo "=== DEBUG DETALLADO DE CARGA DE EXCEL ===\n\n";

// Archivo de ejemplo (ajustar path según sea necesario)
$filePath = __DIR__ . '/Estadisticas_Diarias (44).xls';

if (!file_exists($filePath)) {
    echo "❌ ERROR: No se encuentra el archivo: {$filePath}\n";
    echo "   Por favor, coloca un archivo de prueba en la raíz del proyecto.\n";
    exit(1);
}

echo "✓ Archivo encontrado: {$filePath}\n\n";

// Paso 1: Leer Excel
echo "--- PASO 1: Leer Excel ---\n";
try {
    $reader = new ExcelReader($filePath);
    $data = $reader->toAssociativeArray();
    echo "✓ Datos leídos: " . count($data) . " filas\n";

    if (empty($data)) {
        echo "❌ ERROR: No hay datos en el archivo\n";
        exit(1);
    }

    echo "\n--- Primera fila de datos ---\n";
    print_r($data[0]);
    echo "\n";

} catch (Exception $e) {
    echo "❌ ERROR al leer Excel: " . $e->getMessage() . "\n";
    exit(1);
}

// Paso 2: Validar estructura
echo "\n--- PASO 2: Validar estructura ---\n";
$validator = new ExcelValidator();
$headers = array_keys($data[0]);
echo "Headers encontrados:\n";
foreach ($headers as $idx => $header) {
    echo "  [$idx] => '{$header}' (length: " . strlen($header) . ")\n";
}
echo "\n";

if (!$validator->validarEstructura($headers)) {
    echo "❌ ERROR: Estructura inválida\n";
    foreach ($validator->getErrores() as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}
echo "✓ Estructura válida\n";

// Paso 3: Extraer y validar ruedas
echo "\n--- PASO 3: Extraer ruedas ---\n";
$ruedas = $validator->extraerRuedas($data);
echo "Ruedas encontradas: " . count($ruedas) . "\n";
foreach ($ruedas as $ruedaNo => $fecha) {
    echo "  - Rueda {$ruedaNo}: {$fecha}\n";
}

if (!$validator->validarFechasPorRueda($data, $ruedas)) {
    echo "❌ ERROR: Fechas inconsistentes\n";
    foreach ($validator->getErrores() as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}
echo "✓ Fechas validadas\n";

// Paso 4: Procesar primera rueda (con debugging)
echo "\n--- PASO 4: Procesar primera rueda ---\n";
$primeraRueda = array_key_first($ruedas);
echo "Procesando Rueda {$primeraRueda}...\n\n";

// Filtrar datos de la primera rueda
$datosRueda = array_filter($data, function($row) use ($primeraRueda) {
    return (int)($row['rueda_no'] ?? 0) === (int)$primeraRueda;
});

echo "Registros en la rueda: " . count($datosRueda) . "\n\n";

if (empty($datosRueda)) {
    echo "❌ ERROR: No hay datos para esta rueda\n";
    exit(1);
}

// Tomar el primer registro y procesarlo
$primerRegistro = reset($datosRueda);
echo "--- Primer registro crudo ---\n";
print_r($primerRegistro);
echo "\n";

// Procesar el registro (simular RuedaProcessor::procesarRegistro)
echo "--- Procesando registro ---\n";

$nit = trim($primerRegistro['ncodigo'] ?? '');
$nombreTrader = trim($primerRegistro['nomtrader'] ?? '');
$gtotal = (float) ($primerRegistro['gtotal'] ?? 0);
$fecha = $primerRegistro['fecha'] ?? '';

echo "NIT: '{$nit}'\n";
echo "Trader: '{$nombreTrader}'\n";
echo "Total: {$gtotal}\n";
echo "Fecha: '{$fecha}'\n\n";

// Simular parseo de fecha
try {
    if (is_numeric($fecha)) {
        $unixDate = ($fecha - 25569) * 86400;
        $fechaObj = new DateTime('@' . $unixDate);
    } else {
        $fechaObj = new DateTime(trim($fecha));
    }
    echo "✓ Fecha parseada: " . $fechaObj->format('Y-m-d') . "\n\n";
} catch (Exception $e) {
    echo "❌ ERROR al parsear fecha: " . $e->getMessage() . "\n";
    $fechaObj = new DateTime();
}

// Construir registro para insertar
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$nombreMes = $meses[(int)$fechaObj->format('n')];
$porcentajeComision = (float) ($primerRegistro['comi_porce'] ?? 0);
$comisionCorr = $gtotal * ($porcentajeComision / 100);

$registro = [
    'reasig' => null,
    'nit' => $nit,
    'nombre' => trim($primerRegistro['nnombre'] ?? ''),
    'corredor' => $nombreTrader,
    'comi_porcentual' => $porcentajeComision,
    'ciudad' => trim($primerRegistro['nomzona'] ?? ''),
    'fecha' => $fechaObj->format('Y-m-d'),
    'rueda_no' => (int) $primerRegistro['rueda_no'],
    'negociado' => $gtotal,
    'comi_bna' => 0.0,
    'campo_209' => 0.0,
    'comi_corr' => $comisionCorr,
    'iva_bna' => 0.0,
    'iva_comi' => 0.0,
    'iva_cama' => 0.0,
    'facturado' => 0.0,
    'mes' => $nombreMes,
    'comi_corr_neto' => $comisionCorr,
    'year' => (int) $fechaObj->format('Y')
];

echo "--- Registro a insertar ---\n";
foreach ($registro as $key => $value) {
    $tipo = gettype($value);
    $valorMostrar = is_null($value) ? 'NULL' : (is_string($value) ? "'{$value}'" : $value);
    echo "  {$key} ({$tipo}) => {$valorMostrar}\n";
}
echo "\n";

// Limpiar registro (simular el filtro)
$registroLimpio = array_filter(
    $registro,
    function ($key) {
        $valido = is_string($key) && $key !== '' && !is_numeric($key);
        if (!$valido) {
            echo "  ⚠ Clave rechazada: '{$key}' (is_string: " . (is_string($key) ? 'true' : 'false') . ", is_numeric: " . (is_numeric($key) ? 'true' : 'false') . ")\n";
        }
        return $valido;
    },
    ARRAY_FILTER_USE_KEY
);

echo "--- Registro limpio ---\n";
echo "Campos antes de limpiar: " . count($registro) . "\n";
echo "Campos después de limpiar: " . count($registroLimpio) . "\n\n";

if (count($registro) !== count($registroLimpio)) {
    echo "⚠ Se eliminaron campos durante la limpieza\n\n";
}

// Construir SQL
$columns = implode(', ', array_keys($registroLimpio));
$placeholders = ':' . implode(', :', array_keys($registroLimpio));

echo "--- SQL Generado ---\n";
echo "INSERT INTO orfs_transactions ({$columns}) VALUES ({$placeholders})\n\n";

echo "--- Parámetros ---\n";
foreach ($registroLimpio as $key => $value) {
    $valorMostrar = is_null($value) ? 'NULL' : (is_string($value) ? "'{$value}'" : $value);
    echo "  :{$key} => {$valorMostrar}\n";
}
echo "\n";

// Intentar inserción real
echo "--- PASO 5: Intentar inserción en BD ---\n";
try {
    Database::beginTransaction();

    $id = Database::insert('orfs_transactions', $registroLimpio);

    echo "✓ Inserción exitosa! ID: {$id}\n";

    Database::rollback(); // Rollback para no afectar la BD
    echo "✓ Rollback ejecutado (no se guardó en BD)\n";

} catch (PDOException $e) {
    Database::rollback();
    echo "❌ ERROR en inserción PDO:\n";
    echo "   Código: " . $e->getCode() . "\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   SQL State: " . $e->errorInfo[0] . "\n";

    if (isset($e->errorInfo[2])) {
        echo "   Detalle: " . $e->errorInfo[2] . "\n";
    }
} catch (Exception $e) {
    Database::rollback();
    echo "❌ ERROR genérico:\n";
    echo "   " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
