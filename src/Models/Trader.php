<?php
// src/Models/Trader.php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Trader extends Model
{
    protected static string $table = 'traders';
    
    protected array $fillable = [
        'nombre',
        'nit',
        'porcentaje_comision',
        'activo'
    ];
    
    protected array $casts = [
        'porcentaje_comision' => 'float',
        'activo' => 'boolean'
    ];
    
    // Relaciones
    
    public function adicionales(): array
    {
        $sql = "SELECT * FROM trader_adicionales WHERE trader_id = :trader_id";
        $results = Database::fetchAll($sql, ['trader_id' => $this->id]);
        return array_map(fn($row) => new TraderAdicional($row), $results);
    }
    
    public function transacciones(?int $year = null): array
    {
        $sql = "SELECT * FROM orfs_transactions WHERE corredor = :corredor";
        $params = ['corredor' => $this->nombre];
        
        if ($year) {
            $sql .= " AND year = :year";
            $params['year'] = $year;
        }
        
        $results = Database::fetchAll($sql, $params);
        return array_map(fn($row) => new OrfsTransaction($row), $results);
    }
    
    public function presupuestos(?int $year = null): array
    {
        $sql = "SELECT * FROM presupuestos WHERE corredor = :corredor";
        $params = ['corredor' => $this->nombre];
        
        if ($year) {
            $sql .= " AND year = :year";
            $params['year'] = $year;
        }
        
        $results = Database::fetchAll($sql, $params);
        return array_map(fn($row) => new Presupuesto($row), $results);
    }
    
    // Métodos útiles
    
    public function getNombresCompletos(): array
    {
        $nombres = [$this->nombre];
        
        foreach ($this->adicionales() as $adicional) {
            $nombres[] = $adicional->nombre_adicional;
        }
        
        return $nombres;
    }
    
    public static function activos(): array
    {
        $sql = "SELECT * FROM traders WHERE activo = 1 ORDER BY nombre";
        $results = Database::fetchAll($sql);
        return array_map(fn($row) => new self($row), $results);
    }
    
    public static function buscarPorNombreONit(string $termino): ?self
    {
        $sql = "SELECT * FROM traders WHERE nombre = :termino OR nit = :termino LIMIT 1";
        $result = Database::fetch($sql, ['termino' => $termino]);
        
        if ($result) {
            return new self($result);
        }
        
        $sql = "SELECT t.* FROM traders t 
                INNER JOIN trader_adicionales ta ON t.id = ta.trader_id 
                WHERE ta.nombre_adicional = :termino LIMIT 1";
        $result = Database::fetch($sql, ['termino' => $termino]);
        
        return $result ? new self($result) : null;
    }
}