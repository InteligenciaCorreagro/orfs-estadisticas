<?php
// src/Services/BusinessIntelligence/HistoricalUploadService.php

namespace App\Services\BusinessIntelligence;

use App\Core\Database;

class HistoricalUploadService
{
    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../../storage/uploads/historical/';

        // Crear directorio si no existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Subir archivo histórico
     */
    public function uploadHistoricalFile(array $file, int $year, int $userId, string $notes = ''): array
    {
        try {
            // Generar nombre único para el archivo
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'historical_' . $year . '_' . time() . '.' . $extension;
            $filePath = $this->uploadDir . $filename;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'message' => 'Error al mover el archivo al servidor'
                ];
            }

            // Guardar en base de datos
            $sql = "INSERT INTO historical_uploads
                    (year, filename, original_filename, file_path, file_size, uploaded_by, notes, upload_date)
                    VALUES
                    (:year, :filename, :original_filename, :file_path, :file_size, :uploaded_by, :notes, NOW())";

            $params = [
                'year' => $year,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'uploaded_by' => $userId,
                'notes' => $notes
            ];

            try {
                Database::query($sql, $params);

                // Obtener el ID del registro insertado
                $uploadId = Database::getInstance()->lastInsertId();

                // Procesar el archivo e insertar datos en orfs_transactions
                $processor = new HistoricalDataProcessor();
                $processingResult = $processor->processHistoricalFile($filePath, $year);

                if ($processingResult['success']) {
                    // Marcar el archivo como procesado
                    $updateSql = "UPDATE historical_uploads
                                  SET processed = TRUE,
                                      processed_at = NOW(),
                                      records_count = :records_count
                                  WHERE id = :id";

                    Database::query($updateSql, [
                        'records_count' => $processingResult['inserted'],
                        'id' => $uploadId
                    ]);

                    $message = 'Archivo histórico de ' . $year . ' subido y procesado exitosamente. ';
                    $message .= $processingResult['inserted'] . ' registros insertados en la base de datos.';

                    if (!empty($processingResult['errors'])) {
                        $message .= ' Se encontraron ' . count($processingResult['errors']) . ' errores.';
                    }

                    return [
                        'success' => true,
                        'message' => $message,
                        'details' => $processingResult
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Archivo subido pero no se pudo procesar: ' . $processingResult['message']
                    ];
                }

            } catch (\PDOException $e) {
                // Si hay error en la BD, eliminar el archivo físico
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                logError('Error al guardar en BD: ' . $e->getMessage());
                throw new \Exception('Error al guardar en base de datos: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            logError('Error in uploadHistoricalFile: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener todos los archivos subidos
     */
    public function getAllUploads(): array
    {
        $sql = "SELECT
                    h.*,
                    u.name as uploaded_by_name
                FROM historical_uploads h
                LEFT JOIN users u ON h.uploaded_by = u.id
                ORDER BY h.year DESC, h.upload_date DESC";

        $results = Database::fetchAll($sql);
        return $results ?: [];
    }

    /**
     * Obtener archivo por ID
     */
    public function getUploadById(int $id): ?array
    {
        $sql = "SELECT * FROM historical_uploads WHERE id = :id LIMIT 1";
        return Database::fetch($sql, ['id' => $id]);
    }

    /**
     * Eliminar archivo
     */
    public function deleteUpload(int $id): array
    {
        try {
            // Obtener información del archivo
            $upload = $this->getUploadById($id);

            if (!$upload) {
                return [
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ];
            }

            // Eliminar archivo físico
            if (file_exists($upload['file_path'])) {
                unlink($upload['file_path']);
            }

            // Eliminar de base de datos
            $sql = "DELETE FROM historical_uploads WHERE id = :id";
            Database::query($sql, ['id' => $id]);

            return [
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ];

        } catch (\Exception $e) {
            logError('Error in deleteUpload: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar el archivo'
            ];
        }
    }

    /**
     * Obtener estadísticas por año
     */
    public function getYearlyStats(): array
    {
        $sql = "SELECT
                    year,
                    COUNT(*) as total_uploads,
                    SUM(file_size) as total_size,
                    MAX(upload_date) as last_upload
                FROM historical_uploads
                GROUP BY year
                ORDER BY year DESC";

        $results = Database::fetchAll($sql);
        return $results ?: [];
    }

    /**
     * Obtener estadísticas para un año específico
     */
    public function getStatsForYear(?int $year = null): array
    {
        if ($year === null) {
            return [
                'total_uploads' => 0,
                'total_size' => 0,
                'uploads' => []
            ];
        }

        $sql = "SELECT
                    COUNT(*) as total_uploads,
                    SUM(file_size) as total_size
                FROM historical_uploads
                WHERE year = :year";

        $stats = Database::fetch($sql, ['year' => $year]);

        $uploadsSql = "SELECT
                        h.*,
                        u.name as uploaded_by_name
                    FROM historical_uploads h
                    LEFT JOIN users u ON h.uploaded_by = u.id
                    WHERE h.year = :year
                    ORDER BY h.upload_date DESC";

        $uploads = Database::fetchAll($uploadsSql, ['year' => $year]);

        return [
            'total_uploads' => $stats['total_uploads'] ?? 0,
            'total_size' => $stats['total_size'] ?? 0,
            'uploads' => $uploads ?: []
        ];
    }

    /**
     * Verificar si existe archivo para un año
     */
    public function hasUploadForYear(int $year): bool
    {
        $sql = "SELECT COUNT(*) as count FROM historical_uploads WHERE year = :year";
        $result = Database::fetch($sql, ['year' => $year]);
        return ($result['count'] ?? 0) > 0;
    }
}
