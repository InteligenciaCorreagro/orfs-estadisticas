<?php
// src/Services/BusinessIntelligence/HistoricalDataProcessor.php

namespace App\Services\BusinessIntelligence;

use App\Core\Database;
use App\Services\Excel\ExcelReader;

class HistoricalDataProcessor
{
    /**
     * Procesar archivo histórico e insertar en orfs_transactions
     */
    public function processHistoricalFile(string $filePath, int $year): array
    {
        // Aumentar límite de memoria temporalmente para archivos grandes
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            // Leer archivo Excel/CSV
            $excelReader = new ExcelReader($filePath);
            $data = $excelReader->load()->toAssociativeArray();

            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'El archivo está vacío o no se pudo leer'
                ];
            }

            // Verificar que tenga las columnas esperadas
            // Nota: ExcelReader convierte headers a minúsculas
            $requiredColumns = ['reasig', 'nit', 'nombre', 'corredor', 'fecha', 'rueda_no', 'negociado', 'mes'];
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

            // Procesar en lotes para mejor rendimiento de memoria
            $batchSize = 100;
            $insertedCount = 0;
            $errors = [];
            $totalRows = count($data);

            for ($offset = 0; $offset < $totalRows; $offset += $batchSize) {
                // Iniciar transacción para este lote
                Database::beginTransaction();

                try {
                    $batch = array_slice($data, $offset, $batchSize);

                    foreach ($batch as $index => $row) {
                        $actualIndex = $offset + $index;

                        try {
                            // Preparar datos para insertar (columnas en minúsculas porque ExcelReader las convierte)
                    $transaction = [
                        'reasig' => $row['reasig'] ?? null,
                        'nit' => $row['nit'] ?? '',
                        'nombre' => $row['nombre'] ?? '',
                        'corredor' => $row['corredor'] ?? '',
                        'comi_porcentual' => floatval($row['comi_porcentual'] ?? 0),
                        'ciudad' => $row['ciudad'] ?? null,
                        'fecha' => $this->parseDate($row['fecha'] ?? ''),
                        'rueda_no' => intval($row['rueda_no'] ?? 0),
                        'negociado' => floatval($row['negociado'] ?? 0),
                        'comi_bna' => floatval($row['comi_bna'] ?? 0),
                        'campo_209' => floatval($row['246'] ?? 0),
                        'comi_corr' => floatval($row['comi_corr'] ?? 0),
                        'iva_bna' => floatval($row['iva_bna'] ?? 0),
                        'iva_comi' => floatval($row['iva_comi'] ?? 0),
                        'iva_cama' => floatval($row['iva_cama'] ?? 0),
                        'facturado' => floatval($row['facturado'] ?? 0),
                        'mes' => $row['mes'] ?? '',
                        'comi_corr_neto' => floatval($row['comi_corr_neto'] ?? 0),
                        'year' => $year // El año viene del parámetro
                    ];

                            // Validar datos requeridos
                            if (empty($transaction['nit']) || empty($transaction['nombre']) ||
                                empty($transaction['corredor']) || empty($transaction['fecha'])) {
                                $errors[] = "Fila " . ($actualIndex + 2) . ": Datos requeridos faltantes";
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
                            $errors[] = "Fila " . ($actualIndex + 2) . ": " . $e->getMessage();
                        }
                    }

                    // Commit del lote si todo salió bien
                    Database::commit();

                    // Liberar memoria del lote procesado
                    unset($batch);

                } catch (\Exception $e) {
                    // Rollback del lote en caso de error
                    try {
                        Database::rollback();
                    } catch (\Exception $rollbackException) {
                        // Ignorar errores de rollback
                    }

                    $errors[] = "Error en lote (filas " . ($offset + 1) . " a " . ($offset + $batchSize) . "): " . $e->getMessage();
                }
            }

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
        } finally {
            // Restaurar límite de memoria original
            ini_set('memory_limit', $originalMemoryLimit);
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
