<?php
// src/Models/CargaHistorial.php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class CargaHistorial extends Model
{
    protected string $table = 'carga_historial';
    
    protected array $fillable = [
        'archivo_nombre',
        'usuario_id',
        'ruedas_procesadas',
        'registros_insertados',
        'estado',
        'mensaje'
    ];
    
    protected array $casts = [
        'usuario_id' => 'integer',
        'registros_insertados' => 'integer',
        'ruedas_procesadas' => 'json'
    ];
    
    public function usuario(): ?User
    {
        return User::find($this->usuario_id);
    }
    
    public static function recientes(int $limit = 20): array
    {
        $sql = "SELECT * FROM carga_historial ORDER BY created_at DESC LIMIT :limit";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(fn($row) => new self($row), $results);
    }
    
    public static function exitosos(): array
    {
        $sql = "SELECT * FROM carga_historial WHERE estado = 'exitoso' ORDER BY created_at DESC";
        $results = Database::fetchAll($sql);
        return array_map(fn($row) => new self($row), $results);
    }
    
    public static function fallidos(): array
    {
        $sql = "SELECT * FROM carga_historial WHERE estado = 'fallido' ORDER BY created_at DESC";
        $results = Database::fetchAll($sql);
        return array_map(fn($row) => new self($row), $results);
    }
}