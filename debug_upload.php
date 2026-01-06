<?php
// debug_upload.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once './bootstrap.php';

use App\Services\Excel\ExcelReader;
use App\Services\Excel\ExcelValidator;
use App\Services\Excel\RuedaProcessor;
use App\Core\Database;

echo "=== DEBUG DE CARGA DE ARCHIVO ===\n\n";

$filepath = './storage/uploads/695d2789d0a37_Estadisticas_Diarias (44).xls';

try {
    // 1. Leer archivo
    echo "1. Leyendo archivo Excel...\n";
    $reader = new ExcelReader($filepath);
    $data = $reader->toAssociativeArray();
    echo "   ✓ Archivo leído: " . count($data) . " filas\n\n";
    
    // Mostrar primeras columnas
    if (count($data) > 0) {
        echo "   Columnas encontradas:\n";
        foreach (array_keys($data[0]) as $col) {
            echo "   - $col\n";
        }
        echo "\n";
        
        echo "   Primera fila de datos:\n";
        print_r($data[0]);
        echo "\n";
    }
    
    // 2. Validar estructura
    echo "2. Validando estructura...\n";
    $validator = new ExcelValidator();
    
    if ($validator->validarEstructura(array_keys($data[0] ?? []))) {
        echo "   ✓ Estructura válida\n\n";
    } else {
        echo "   ✗ Estructura inválida:\n";
        foreach ($validator->getErrores() as $error) {
            echo "   - $error\n";
        }
        die("\n");
    }
    
    // 3. Extraer ruedas
    echo "3. Extrayendo ruedas...\n";
    $ruedas = $validator->extraerRuedas($data);
    echo "   ✓ Ruedas encontradas: " . count($ruedas) . "\n";
    foreach ($ruedas as $ruedaNo => $fecha) {
        echo "   - Rueda $ruedaNo: $fecha\n";
    }
    echo "\n";
    
    // 4. Procesar primera rueda (solo la primera para test)
    $primeraRueda = array_key_first($ruedas);
    echo "4. Procesando rueda $primeraRueda para test...\n";
    
    // Filtrar solo registros de esta rueda
    $registrosRueda = array_filter($data, function($row) use ($primeraRueda) {
        return (int)($row['rueda_no'] ?? 0) === $primeraRueda;
    });
    
    echo "   Registros de la rueda: " . count($registrosRueda) . "\n\n";
    
    // 5. Procesar primer registro
    echo "5. Procesando primer registro...\n";
    $primerRegistro = reset($registrosRueda);
    
    echo "   Datos del registro:\n";
    print_r($primerRegistro);
    echo "\n";
    
    // Preparar datos para inserción
    $processor = new RuedaProcessor();
    
    // Como no podemos acceder al método privado, vamos a replicar la lógica aquí
    $nit = trim($primerRegistro['ncodigo']);
    $nombreTrader = trim($primerRegistro['nomtrader']);
    $gtotal = (float) ($primerRegistro['gtotal'] ?? 0);
    
    echo "   NIT: $nit\n";
    echo "   Trader: $nombreTrader\n";
    echo "   GTotal: $gtotal\n\n";
    
    // Parsear fecha
    $fechaRaw = $primerRegistro['fecha'];
    echo "   Fecha raw: $fechaRaw\n";
    
    if (is_numeric($fechaRaw)) {
        $unixDate = ($fechaRaw - 25569) * 86400;
        $fecha = new DateTime('@' . $unixDate);
    } else {
        $fecha = new DateTime($fechaRaw);
    }
    
    echo "   Fecha parseada: " . $fecha->format('Y-m-d') . "\n";
    echo "   Año: " . $fecha->format('Y') . "\n";
    echo "   Mes número: " . $fecha->format('n') . "\n\n";
    
    // 6. Preparar registro para inserción
    echo "6. Preparando registro para inserción...\n";
    
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $nombreMes = $meses[(int)$fecha->format('n')];
    
    $registro = [
        'reasig' => null,
        'nit' => $nit,
        'nombre' => trim($primerRegistro['nnombre'] ?? ''),
        'corredor' => $nombreTrader,
        'comi_porcentual' => (float) ($primerRegistro['comi_porce'] ?? 0),
        'ciudad' => trim($primerRegistro['nomzona'] ?? ''),
        'fecha' => $fecha->format('Y-m-d'),
        'rueda_no' => (int) $primerRegistro['rueda_no'],
        'negociado' => $gtotal,
        'comi_bna' => 0,
        'campo_209' => 0,
        'comi_corr' => $gtotal * 0.5, // Placeholder
        'iva_bna' => 0,
        'iva_comi' => 0,
        'iva_cama' => 0,
        'facturado' => 0,
        'mes' => $nombreMes,
        'comi_corr_neto' => $gtotal * 0.5,
        'year' => (int) $fecha->format('Y')
    ];
    
    echo "   Registro preparado:\n";
    foreach ($registro as $key => $value) {
        $displayValue = is_null($value) ? 'NULL' : $value;
        echo "   - $key: $displayValue\n";
    }
    echo "\n";
    
    // 7. Intentar inserción
    echo "7. Intentando inserción en BD...\n";
    
    try {
        $id = Database::insert('orfs_transactions', $registro);
        echo "   ✓ Registro insertado con ID: $id\n";
    } catch (Exception $e) {
        echo "   ✗ Error en inserción:\n";
        echo "   Mensaje: " . $e->getMessage() . "\n";
        echo "   Código: " . $e->getCode() . "\n";
        
        if ($e instanceof PDOException) {
            echo "   Info PDO:\n";
            print_r($e->errorInfo);
        }
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR GENERAL:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEL DEBUG ===\n";