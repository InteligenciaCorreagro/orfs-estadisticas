<?php
// src/Services/BusinessIntelligence/HistoricalDataProcessor.php

namespace App\Services\BusinessIntelligence;

use App\Core\Database;
use App\Services\Excel\ExcelReader;

class HistoricalDataProcessor
{
    private ExcelReader $excelReader;

    public function __construct()
    {
        $this->excelReader = new ExcelReader();
    }

    /**
     * Procesar archivo histórico e insertar en orfs_transactions
     */
    public function processHistoricalFile(string $filePath, int $year): array
    {
        try {
            // Leer archivo Excel/CSV
            $data = $this->excelReader->read($filePath);

            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'El archivo está vacío o no se pudo leer'
                ];
            }

            // Verificar que tenga las columnas esperadas
            $requiredColumns = ['REASIG', 'NIT', 'NOMBRE', 'CORREDOR', 'FECHA', 'RUEDA_NO', 'NEGOCIADO', 'MES'];
            $headers = array_keys($data[0]);

            $missingColumns = [];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $headers)) {
                    $missingColumns[] = $col;
                }
            }

            if (!empty($missingColumns)) {
                return [
                    'success' => false,
                    'message' => 'Faltan columnas requeridas: ' . implode(', ', $missingColumns)
                ];
            }

            // Iniciar transacción
            Database::beginTransaction();

            $insertedCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                try {
                    // Preparar datos para insertar
                    $transaction = [
                        'reasig' => $row['REASIG'] ?? null,
                        'nit' => $row['NIT'] ?? '',
                        'nombre' => $row['NOMBRE'] ?? '',
                        'corredor' => $row['CORREDOR'] ?? '',
                        'comi_porcentual' => floatval($row['COMI_PORCENTUAL'] ?? 0),
                        'ciudad' => $row['CIUDAD'] ?? null,
                        'fecha' => $this->parseDate($row['FECHA'] ?? ''),
                        'rueda_no' => intval($row['RUEDA_NO'] ?? 0),
                        'negociado' => floatval($row['NEGOCIADO'] ?? 0),
                        'comi_bna' => floatval($row['COMI_BNA'] ?? 0),
                        'campo_209' => floatval($row['246'] ?? 0),
                        'comi_corr' => floatval($row['COMI_CORR'] ?? 0),
                        'iva_bna' => floatval($row['IVA_BNA'] ?? 0),
                        'iva_comi' => floatval($row['IVA_COMI'] ?? 0),
                        'iva_cama' => floatval($row['IVA_CAMA'] ?? 0),
                        'facturado' => floatval($row['FACTURADO'] ?? 0),
                        'mes' => $row['MES'] ?? '',
                        'comi_corr_neto' => floatval($row['COMI_CORR_NETO'] ?? 0),
                        'year' => $year // El año viene del parámetro
                    ];

                    // Validar datos requeridos
                    if (empty($transaction['nit']) || empty($transaction['nombre']) ||
                        empty($transaction['corredor']) || empty($transaction['fecha'])) {
                        $errors[] = "Fila " . ($index + 2) . ": Datos requeridos faltantes";
                        continue;
                    }

                    // Insertar en la base de datos
                    $sql = "INSERT INTO orfs_transactions
                            (reasig, nit, nombre, corredor, comi_porcentual, ciudad, fecha,
                             rueda_no, negociado, comi_bna, campo_209, comi_corr, iva_bna,
                             iva_comi, iva_cama, facturado, mes, comi_corr_neto, year)
                            VALUES
                            (:reasig, :nit, :nombre, :corredor, :comi_porcentual, :ciudad, :fecha,
                             :rueda_no, :negociado, :comi_bna, :campo_209, :comi_corr, :iva_bna,
                             :iva_comi, :iva_cama, :facturado, :mes, :comi_corr_neto, :year)";

                    Database::query($sql, $transaction);
                    $insertedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            // Commit si todo salió bien
            Database::commit();

            $message = "Procesamiento completado. {$insertedCount} registros insertados";
            if (!empty($errors)) {
                $message .= ". " . count($errors) . " errores encontrados";
            }

            return [
                'success' => true,
                'message' => $message,
                'inserted' => $insertedCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            // Rollback en caso de error
            try {
                Database::rollback();
            } catch (\Exception $rollbackException) {
                // Ignorar errores de rollback
            }

            logError('Error processing historical file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parsear fecha desde el Excel
     */
    private function parseDate($dateValue): string
    {
        if (empty($dateValue)) {
            return date('Y-m-d');
        }

        // Si es un timestamp de Excel
        if (is_numeric($dateValue)) {
            $timestamp = ($dateValue - 25569) * 86400;
            return date('Y-m-d', $timestamp);
        }

        // Si es un string de fecha
        try {
            $date = new \DateTime($dateValue);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }
}
