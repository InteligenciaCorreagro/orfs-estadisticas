<?php
// src/Services/Excel/RuedaProcessorV2.php
// VERSIÓN 2 - ARCHIVO NUEVO PARA EVITAR CACHE

namespace App\Services\Excel;

use App\Core\Database;
use App\Models\OrfsTransaction;
use App\Models\Trader;
use DateTime;
use Exception;

class RuedaProcessorV2
{
    private ExcelValidator $validator;
    private array $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    public function __construct()
    {
        $this->validator = new ExcelValidator();
    }

    public function procesarArchivo(array $data): array
    {
        $debugFile = __DIR__ . '/../../../public/debug_v2.txt';

        file_put_contents($debugFile,
            "=== PROCESADOR V2 INICIADO ===\n" .
            "Fecha: " . date('Y-m-d H:i:s') . "\n" .
            "Total filas: " . count($data) . "\n\n",
            FILE_APPEND
        );

        // Validar estructura
        if (!$this->validator->validarEstructura(array_keys($data[0] ?? []))) {
            throw new Exception(
                "Estructura de archivo inválida:\n" .
                $this->validator->getErroresTexto()
            );
        }

        // Validar datos
        if (!$this->validator->validarDatos($data)) {
            throw new Exception(
                "Datos inválidos:\n" .
                $this->validator->getErroresTexto()
            );
        }

        // Extraer ruedas
        $ruedas = $this->validator->extraerRuedas($data);

        if (empty($ruedas)) {
            throw new Exception("No se encontraron ruedas para procesar");
        }

        file_put_contents($debugFile,
            "Ruedas encontradas: " . implode(', ', array_keys($ruedas)) . "\n\n",
            FILE_APPEND
        );

        // Validar fechas por rueda
        if (!$this->validator->validarFechasPorRueda($data, $ruedas)) {
            throw new Exception(
                "Error en validación de fechas:\n" .
                $this->validator->getErroresTexto()
            );
        }

        $resultado = [
            'ruedas_procesadas' => [],
            'total_registros' => 0,
            'errores' => []
        ];

        // Procesar cada rueda
        foreach ($ruedas as $ruedaNo => $fecha) {
            try {
                file_put_contents($debugFile,
                    "\n========================================\n" .
                    "PROCESANDO RUEDA {$ruedaNo}\n" .
                    "========================================\n",
                    FILE_APPEND
                );

                Database::beginTransaction();

                $registrosProcesados = $this->procesarRueda($data, $ruedaNo, $debugFile);

                Database::commit();

                $resultado['ruedas_procesadas'][] = [
                    'rueda' => $ruedaNo,
                    'fecha' => $fecha,
                    'registros' => $registrosProcesados
                ];
                $resultado['total_registros'] += $registrosProcesados;

                file_put_contents($debugFile,
                    "✓ Rueda {$ruedaNo} procesada: {$registrosProcesados} registros\n",
                    FILE_APPEND
                );

            } catch (Exception $e) {
                Database::rollback();

                file_put_contents($debugFile,
                    "✗ ERROR en rueda {$ruedaNo}: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );

                $resultado['errores'][] = [
                    'rueda' => $ruedaNo,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $resultado;
    }

    private function procesarRueda(array $data, int $ruedaNo, string $debugFile): int
    {
        file_put_contents($debugFile,
            "Eliminando datos anteriores de rueda {$ruedaNo}...\n",
            FILE_APPEND
        );

        OrfsTransaction::eliminarPorRueda($ruedaNo);

        file_put_contents($debugFile,
            "Iniciando inserción de registros...\n",
            FILE_APPEND
        );

        $registrosInsertados = 0;

        foreach ($data as $index => $row) {
            // Filtrar solo registros de esta rueda
            if ((int)($row['rueda_no'] ?? 0) !== $ruedaNo) {
                continue;
            }

            // Validar fila
            if (!$this->validator->validarFila($row, $index + 2)) {
                file_put_contents($debugFile,
                    "Fila {$index} no válida, saltando...\n",
                    FILE_APPEND
                );
                continue;
            }

            // Procesar y guardar registro
            try {
                file_put_contents($debugFile,
                    "  Procesando fila {$index}...\n",
                    FILE_APPEND
                );

                $registro = $this->procesarRegistro($row, $debugFile);

                file_put_contents($debugFile,
                    "  Intentando INSERT en BD...\n",
                    FILE_APPEND
                );

                $id = Database::insert('orfs_transactions', $registro);

                file_put_contents($debugFile,
                    "  ✓ ID: {$id}\n",
                    FILE_APPEND
                );

                $registrosInsertados++;

            } catch (\PDOException $e) {
                file_put_contents($debugFile,
                    "  ✗ ERROR PDO: " . $e->getMessage() . "\n" .
                    "     Datos: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n",
                    FILE_APPEND
                );

                throw new \Exception(
                    "Error insertando registro de rueda {$ruedaNo}: " . $e->getMessage()
                );
            } catch (\Exception $e) {
                file_put_contents($debugFile,
                    "  ✗ ERROR: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );

                throw new \Exception(
                    "Error procesando registro de rueda {$ruedaNo}: " . $e->getMessage()
                );
            }
        }

        return $registrosInsertados;
    }

    private function procesarRegistro(array $row, string $debugFile): array
    {
        // Convertir todos los valores a string, PRESERVANDO LAS CLAVES
        $cleanRow = [];
        foreach ($row as $key => $value) {
            if (is_null($value)) {
                $cleanRow[$key] = '';
            } elseif (is_object($value)) {
                if (method_exists($value, 'getPlainText')) {
                    $cleanRow[$key] = trim($value->getPlainText());
                } elseif (method_exists($value, '__toString')) {
                    $cleanRow[$key] = trim((string) $value);
                } else {
                    $cleanRow[$key] = '';
                }
            } else {
                $cleanRow[$key] = trim((string) $value);
            }
        }

        $nit = $cleanRow['ncodigo'] ?? '';
        $nombreTrader = $cleanRow['nomtrader'] ?? '';
        $gtotal = (float) ($cleanRow['gtotal'] ?? 0);

        // Validar campos requeridos
        if (empty($nit) || empty($nombreTrader)) {
            throw new \Exception("NIT o Trader vacío en registro");
        }

        // Obtener porcentaje de comisión
        $porcentajeComision = $this->obtenerPorcentajeComision($nombreTrader, $nit);

        // Calcular comisión
        $comisionCorr = $gtotal * ($porcentajeComision / 100);

        // Parsear fecha
        $fecha = $this->parsearFecha($cleanRow['fecha'] ?? '');

        // Obtener nombre del mes
        $nombreMes = $this->meses[(int)$fecha->format('n')];

        // IMPORTANTE: Todos los valores deben ser tipos escalares
        return [
            'reasig' => null,
            'nit' => (string) $nit,
            'nombre' => (string) ($cleanRow['nnombre'] ?? ''),
            'corredor' => (string) $nombreTrader,
            'comi_porcentual' => (float) ($cleanRow['comi_porce'] ?? 0),
            'ciudad' => (string) ($cleanRow['nomzona'] ?? ''),
            'fecha' => $fecha->format('Y-m-d'),
            'rueda_no' => (int) ($cleanRow['rueda_no'] ?? 0),
            'negociado' => (float) $gtotal,
            'comi_bna' => 0.0,
            'campo_209' => 0.0,
            'comi_corr' => (float) $comisionCorr,
            'iva_bna' => 0.0,
            'iva_comi' => 0.0,
            'iva_cama' => 0.0,
            'facturado' => 0.0,
            'mes' => (string) $nombreMes,
            'comi_corr_neto' => (float) $comisionCorr,
            'year' => (int) $fecha->format('Y')
        ];
    }

    private function obtenerPorcentajeComision(string $nombreTrader, string $nit): float
    {
        $trader = Trader::buscarPorNombreONit($nombreTrader);

        if ($trader) {
            return (float) $trader->porcentaje_comision;
        }

        $trader = Trader::buscarPorNombreONit($nit);

        if ($trader) {
            return (float) $trader->porcentaje_comision;
        }

        return 0.0;
    }

    private function parsearFecha($fecha): DateTime
    {
        try {
            if (is_numeric($fecha)) {
                $unixDate = ($fecha - 25569) * 86400;
                return new DateTime('@' . $unixDate);
            }

            if (is_object($fecha)) {
                if (method_exists($fecha, 'getPlainText')) {
                    $fecha = $fecha->getPlainText();
                } else if (method_exists($fecha, '__toString')) {
                    $fecha = (string) $fecha;
                }
            }

            $fecha = trim($fecha);

            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha, $matches)) {
                $dia = (int) $matches[1];
                $mes = (int) $matches[2];
                $año = (int) $matches[3];

                if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12) {
                    return DateTime::createFromFormat('d/m/Y', $fecha);
                }
            }

            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $fecha, $matches)) {
                $dia = (int) $matches[1];
                $mes = (int) $matches[2];
                $año = (int) $matches[3];

                if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12) {
                    return DateTime::createFromFormat('d-m-Y', $fecha);
                }
            }

            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fecha)) {
                return new DateTime($fecha);
            }

            $formatos = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'Y/m/d'];

            foreach ($formatos as $formato) {
                $date = DateTime::createFromFormat($formato, $fecha);
                if ($date !== false) {
                    return $date;
                }
            }

            return new DateTime($fecha);
        } catch (Exception $e) {
            throw new Exception("Formato de fecha inválido: {$fecha}");
        }
    }
}
