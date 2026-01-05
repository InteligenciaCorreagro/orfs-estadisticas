<?php
// src/Models/Presupuesto.php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Presupuesto extends Model
{
    protected static string $table = 'presupuestos';
    
    protected array $fillable = [
        'nit',
        'corredor',
        'mes',
        'year',
        'transado_presupuesto',
        'comision_presupuesto'
    ];
    
    protected array $casts = [
        'mes' => 'integer',
        'year' => 'integer',
        'transado_presupuesto' => 'float',
        'comision_presupuesto' => 'float'
    ];
    
    public function trader(): ?Trader
    {
        return Trader::where('nombre', $this->corredor)[0] ?? null;
    }
    
    public static function porYear(int $year): array
    {
        $sql = "SELECT * FROM presupuestos WHERE year = :year";
        $results = Database::fetchAll($sql, ['year' => $year]);
        return array_map(fn($row) => new self($row), $results);
    }
    
    public static function porCorredor(string $corredor, ?int $year = null): array
    {
        $sql = "SELECT * FROM presupuestos WHERE corredor = :corredor";
        $params = ['corredor' => $corredor];
        
        if ($year) {
            $sql .= " AND year = :year";
            $params['year'] = $year;
        }
        
        $results = Database::fetchAll($sql, $params);
        return array_map(fn($row) => new self($row), $results);
    }
    
    public static function obtener(string $corredor, string $nit, int $mes, int $year): ?self
    {
        $sql = "SELECT * FROM presupuestos 
                WHERE corredor = :corredor AND nit = :nit AND mes = :mes AND year = :year 
                LIMIT 1";
        $result = Database::fetch($sql, [
            'corredor' => $corredor,
            'nit' => $nit,
            'mes' => $mes,
            'year' => $year
        ]);
        
        return $result ? new self($result) : null;
    }
}