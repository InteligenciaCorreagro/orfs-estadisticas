<?php
// src/Utils/FileUploader.php

namespace App\Utils;

use Exception;

class FileUploader
{
    private string $uploadDir;
    private array $allowedExtensions;
    private int $maxFileSize;
    
    public function __construct(
        string $uploadDir = null,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx'],
        int $maxFileSize = 10485760 // 10MB
    ) {
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../../storage/uploads/';
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;
        
        // Crear directorio si no existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function upload(array $file): array
    {
        // Validar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Validar tamaño
        if ($file['size'] > $this->maxFileSize) {
            $maxSize = $this->maxFileSize / 1048576; // Convertir a MB
            throw new Exception("El archivo excede el tamaño máximo permitido de {$maxSize}MB");
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception(
                "Extensión no permitida. Solo se permiten: " . 
                implode(', ', $this->allowedExtensions)
            );
        }
        
        // Generar nombre único
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Error al guardar el archivo');
        }
        
        return [
            'original_name' => $file['name'],
            'saved_name' => $filename,
            'path' => $filepath,
            'size' => $file['size'],
            'extension' => $extension,
            'mime_type' => $file['type']
        ];
    }
    
    public function delete(string $filename): bool
    {
        $filepath = $this->uploadDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo es demasiado grande';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se subió ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta el directorio temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo en disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Una extensión de PHP detuvo la subida';
            default:
                return 'Error desconocido al subir el archivo';
        }
    }
}