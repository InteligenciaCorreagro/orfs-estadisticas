<?php
// src/Services/Excel/RuedaProcessor.php

namespace App\Services\Excel;

use App\Core\Database;
use App\Models\OrfsTransaction;
use App\Models\Trader;
use DateTime;
use Exception;

class RuedaProcessor
{
    private ExcelValidator $validator;
    private array $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    public function __construct()
    {
        $this->validator = new ExcelValidator();
    }

    public function procesarArchivo(array $data): array
    {
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
                Database::beginTransaction();

                $registrosProcesados = $this->procesarRueda($data, $ruedaNo);

                Database::commit();

                $resultado['ruedas_procesadas'][] = [
                    'rueda' => $ruedaNo,
                    'fecha' => $fecha,
                    'registros' => $registrosProcesados
                ];
                $resultado['total_registros'] += $registrosProcesados;
            } catch (Exception $e) {
                Database::rollback();
                $resultado['errores'][] = [
                    'rueda' => $ruedaNo,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $resultado;
    }

    private function procesarRueda(array $data, int $ruedaNo): int
    {
        // Eliminar datos anteriores de esta rueda
        OrfsTransaction::eliminarPorRueda($ruedaNo);

        $registrosInsertados = 0;

        foreach ($data as $index => $row) {
            // Filtrar solo registros de esta rueda
            if ((int)($row['rueda_no'] ?? 0) !== $ruedaNo) {
                continue;
            }

            // Validar fila
            if (!$this->validator->validarFila($row, $index + 2)) {
                continue; // Saltar fila inválida
            }

            // Procesar y guardar registro
            try {
                $registro = $this->procesarRegistro($row);

                // INSERTAR en la base de datos
                // Database::insert() ya se encarga de validar y limpiar los datos
                $id = Database::insert('orfs_transactions', $registro);
                $registrosInsertados++;
            } catch (\PDOException $e) {
                // Log detallado del error
                error_log("Error PDO insertando registro de rueda {$ruedaNo}:");
                error_log("  - Mensaje: " . $e->getMessage());
                error_log("  - Código: " . $e->getCode());
                error_log("  - Datos del Excel: " . json_encode($row, JSON_UNESCAPED_UNICODE));

                // Re-lanzar con contexto
                throw new \Exception(
                    "Error insertando registro de rueda {$ruedaNo}: " . $e->getMessage()
                );
            } catch (\Exception $e) {
                // Log de otros errores (validación, tipos, etc.)
                error_log("Error al procesar registro de rueda {$ruedaNo}:");
                error_log("  - Mensaje: " . $e->getMessage());
                error_log("  - Datos: " . json_encode($row, JSON_UNESCAPED_UNICODE));

                throw new \Exception(
                    "Error procesando registro de rueda {$ruedaNo}: " . $e->getMessage()
                );
            }
        }

        return $registrosInsertados;
    }

    private function procesarRegistro(array $row): array
    {
        // Convertir todos los valores a string primero para evitar problemas con objetos RichText
        $row = array_map(function ($value) {
            if (is_null($value)) {
                return '';
            }
            if (is_object($value)) {
                if (method_exists($value, 'getPlainText')) {
                    return trim($value->getPlainText());
                }
                if (method_exists($value, '__toString')) {
                    return trim((string) $value);
                }
                return '';
            }
            return trim((string) $value);
        }, $row);

        $nit = $row['ncodigo'] ?? '';
        $nombreTrader = $row['nomtrader'] ?? '';
        $gtotal = (float) ($row['gtotal'] ?? 0);

        // Validar campos requeridos
        if (empty($nit) || empty($nombreTrader)) {
            throw new \Exception("NIT o Trader vacío en registro");
        }

        // Obtener porcentaje de comisión
        $porcentajeComision = $this->obtenerPorcentajeComision($nombreTrader, $nit);

        // Calcular comisión
        $comisionCorr = $gtotal * ($porcentajeComision / 100);

        // Parsear fecha
        $fecha = $this->parsearFecha($row['fecha'] ?? '');

        // Obtener nombre del mes
        $nombreMes = $this->meses[(int)$fecha->format('n')];

        // IMPORTANTE: Todos los valores deben ser tipos escalares (string, int, float, null)
        return [
            'reasig' => null,
            'nit' => (string) $nit,
            'nombre' => (string) ($row['nnombre'] ?? ''),
            'corredor' => (string) $nombreTrader,
            'comi_porcentual' => (float) ($row['comi_porce'] ?? 0),
            'ciudad' => (string) ($row['nomzona'] ?? ''),
            'fecha' => $fecha->format('Y-m-d'),
            'rueda_no' => (int) ($row['rueda_no'] ?? 0),
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

        // Si no encuentra, buscar por NIT
        $trader = Trader::buscarPorNombreONit($nit);

        if ($trader) {
            return (float) $trader->porcentaje_comision;
        }

        return 0.0;
    }

    private function parsearFecha($fecha): DateTime
    {
        try {
            // Si es número (serial date de Excel)
            if (is_numeric($fecha)) {
                $unixDate = ($fecha - 25569) * 86400;
                return new DateTime('@' . $unixDate);
            }

            // Convertir a string si es objeto
            if (is_object($fecha)) {
                if (method_exists($fecha, 'getPlainText')) {
                    $fecha = $fecha->getPlainText();
                } else if (method_exists($fecha, '__toString')) {
                    $fecha = (string) $fecha;
                }
            }

            $fecha = trim($fecha);

            // CORRECCIÓN: Detectar formato DD/MM/YYYY o D/MM/YYYY
            // Formato común en Colombia: día/mes/año
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha, $matches)) {
                $dia = (int) $matches[1];
                $mes = (int) $matches[2];
                $año = (int) $matches[3];

                // Validar que sean valores válidos
                if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12) {
                    // IMPORTANTE: En Colombia usamos formato DD/MM/YYYY
                    // Por lo tanto: primer número = día, segundo = mes
                    return DateTime::createFromFormat('d/m/Y', $fecha);
                }
            }

            // Si tiene formato con guiones: DD-MM-YYYY
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $fecha, $matches)) {
                $dia = (int) $matches[1];
                $mes = (int) $matches[2];
                $año = (int) $matches[3];

                if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12) {
                    return DateTime::createFromFormat('d-m-Y', $fecha);
                }
            }

            // Intentar formato ISO: YYYY-MM-DD
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fecha)) {
                return new DateTime($fecha);
            }

            // Último intento: otros formatos estándar
            $formatos = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'Y/m/d'];

            foreach ($formatos as $formato) {
                $date = DateTime::createFromFormat($formato, $fecha);
                if ($date !== false) {
                    return $date;
                }
            }

            // Intento genérico
            return new DateTime($fecha);
        } catch (Exception $e) {
            throw new Exception("Formato de fecha inválido: {$fecha}");
        }
    }
}
