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

                // VALIDAR que no haya claves vacías
                $registroLimpio = array_filter(
                    $registro,
                    function ($key) {
                        return is_string($key) && $key !== '' && !is_numeric($key);
                    },
                    ARRAY_FILTER_USE_KEY
                );

                // INSERTAR usando Database directamente con mejor control
                $id = Database::insert('orfs_transactions', $registroLimpio);
                $registrosInsertados++;
            } catch (\PDOException $e) {
                // Log detallado del error
                error_log("Error insertando registro de rueda {$ruedaNo}:");
                error_log("  - Mensaje: " . $e->getMessage());
                error_log("  - Datos: " . json_encode($row));

                // Re-lanzar con contexto
                throw new \Exception(
                    "Error insertando registro de rueda {$ruedaNo}: " . $e->getMessage()
                );
            }
        }

        return $registrosInsertados;
    }

    private function procesarRegistro(array $row): array
    {
        $nit = trim($row['ncodigo'] ?? '');
        $nombreTrader = trim($row['nomtrader'] ?? '');
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
        $fecha = $this->parsearFecha($row['fecha']);

        // Obtener nombre del mes
        $nombreMes = $this->meses[(int)$fecha->format('n')];

        // IMPORTANTE: NO incluir created_at ni updated_at
        // La tabla los maneja automáticamente con DEFAULT CURRENT_TIMESTAMP
        return [
            'reasig' => null,
            'nit' => $nit,
            'nombre' => trim($row['nnombre'] ?? ''),
            'corredor' => $nombreTrader,
            'comi_porcentual' => (float) ($row['comi_porce'] ?? 0),
            'ciudad' => trim($row['nomzona'] ?? ''),
            'fecha' => $fecha->format('Y-m-d'),
            'rueda_no' => (int) $row['rueda_no'],
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
