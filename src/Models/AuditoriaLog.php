<?php
// src/Models/AuditoriaLog.php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class AuditoriaLog extends Model
{
    protected static string $table = 'auditoria_logs';
    
    protected array $fillable = [
        'user_id',
        'accion',
        'modulo',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent'
    ];
    
    protected array $casts = [
        'user_id' => 'integer',
        'datos_anteriores' => 'json',
        'datos_nuevos' => 'json'
    ];
    
    public function usuario(): ?User
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }
    
    public static function registrar(
        ?int $userId,
        string $accion,
        string $modulo,
        string $descripcion,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): void {
        $data = [
            'user_id' => $userId,
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
            'datos_anteriores' => $datosAnteriores ? json_encode($datosAnteriores) : null,
            'datos_nuevos' => $datosNuevos ? json_encode($datosNuevos) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        Database::insert('auditoria_logs', $data);
    }
    
    public static function porUsuario(int $userId, int $limit = 50): array
    {
        $sql = "SELECT * FROM auditoria_logs WHERE user_id = :user_id 
                ORDER BY created_at DESC LIMIT :limit";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(fn($row) => new self($row), $results);
    }
    
    public static function recientes(int $limit = 100): array
    {
        $sql = "SELECT * FROM auditoria_logs ORDER BY created_at DESC LIMIT :limit";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(fn($row) => new self($row), $results);
    }
}