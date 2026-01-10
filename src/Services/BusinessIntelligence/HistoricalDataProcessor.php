<?php
// src/Services/BusinessIntelligence/HistoricalDataProcessor.php

namespace App\Services\BusinessIntelligence;

use App\Core\Database;

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

        // Optimizar configuración de MySQL para inserciones masivas
        try {
            Database::query("SET autocommit = 0");
            Database::query("SET unique_checks = 0");
            Database::query("SET foreign_key_checks = 0");
        } catch (\Exception $e) {
            // Continuar si no se pueden cambiar estas configuraciones
        }

        $insertedCount = 0;
        $allErrors = [];

        try {
            // Usar ChunkedExcelReader con chunks más grandes (500 filas)
            $chunkedReader = new ChunkedExcelReader($filePath, 500);

            // Preparar el statement una vez fuera del loop
            $sql = "INSERT INTO orfs_transactions
                    (reasig, nit, nombre, corredor, comi_porcentual, ciudad, fecha,
                     rueda_no, negociado, comi_bna, campo_209, comi_corr, iva_bna,
                     iva_comi, iva_cama, facturado, mes, comi_corr_neto, year)
                    VALUES
                    (:reasig, :nit, :nombre, :corredor, :comi_porcentual, :ciudad, :fecha,
                     :rueda_no, :negociado, :comi_bna, :campo_209, :comi_corr, :iva_bna,
                     :iva_comi, :iva_cama, :facturado, :mes, :comi_corr_neto, :year)";

            $pdo = Database::getInstance();
            $stmt = $pdo->prepare($sql);

            // Definir el callback para procesar cada chunk
            $results = $chunkedReader->processInChunks(function($chunkData, &$results) use ($year, &$insertedCount, &$allErrors, $stmt) {
                // Iniciar transacción para este chunk
                Database::beginTransaction();

                try {
                    foreach ($chunkData as $index => $row) {
                        try {
                            // Preparar datos para insertar (columnas en minúsculas)
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
                                'year' => $year
                            ];

                            // Validar datos requeridos
                            if (empty($transaction['nit']) || empty($transaction['nombre']) ||
                                empty($transaction['corredor']) || empty($transaction['fecha'])) {
                                $allErrors[] = "Fila {$index}: Datos requeridos faltantes";
                                continue;
                            }

                            // Ejecutar usando el prepared statement reutilizable
                            $stmt->execute($transaction);
                            $insertedCount++;
                            $results['processed']++;

                        } catch (\Exception $e) {
                            $allErrors[] = "Fila {$index}: " . $e->getMessage();
                        }
                    }

                    // Commit del chunk
                    Database::commit();

                } catch (\Exception $e) {
                    // Rollback del chunk en caso de error
                    try {
                        Database::rollback();
                    } catch (\Exception $rollbackException) {
                        // Ignorar errores de rollback
                    }

                    $allErrors[] = "Error en chunk: " . $e->getMessage();
                }
            });

            $message = "Procesamiento completado. {$insertedCount} registros insertados";
            if (!empty($allErrors)) {
                $message .= ". " . count($allErrors) . " errores encontrados";
            }

            if (!empty($results['errors'])) {
                $allErrors = array_merge($allErrors, $results['errors']);
            }

            return [
                'success' => true,
                'message' => $message,
                'inserted' => $insertedCount,
                'errors' => $allErrors
            ];

        } catch (\Exception $e) {
            logError('Error processing historical file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ];
        } finally {
            // Restaurar configuraciones de MySQL
            try {
                Database::query("SET autocommit = 1");
                Database::query("SET unique_checks = 1");
                Database::query("SET foreign_key_checks = 1");
            } catch (\Exception $e) {
                // Ignorar errores al restaurar
            }

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
