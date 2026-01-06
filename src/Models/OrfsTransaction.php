<?php
// src/Models/OrfsTransaction.php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class OrfsTransaction extends Model
{
    protected string $table = 'orfs_transactions';
    
    protected array $fillable = [
        'reasig',
        'nit',
        'nombre',
        'corredor',
        'comi_porcentual',
        'ciudad',
        'fecha',
        'rueda_no',
        'negociado',
        'comi_bna',
        'campo_209',
        'comi_corr',
        'iva_bna',
        'iva_comi',
        'iva_cama',
        'facturado',
        'mes',
        'comi_corr_neto',
        'year'
    ];
    
    protected array $casts = [
        'comi_porcentual' => 'float',
        'negociado' => 'float',
        'comi_bna' => 'float',
        'campo_209' => 'float',
        'comi_corr' => 'float',
        'iva_bna' => 'float',
        'iva_comi' => 'float',
        'iva_cama' => 'float',
        'facturado' => 'float',
        'comi_corr_neto' => 'float',
        'rueda_no' => 'integer',
        'year' => 'integer'
    ];
    
    // Relaciones
    
    public function trader(): ?Trader
    {
        return Trader::where('nombre', $this->corredor)[0] ?? null;
    }
    
    // Métodos de cálculo
    
    public function getMargen(): float
    {
        return $this->comi_corr - $this->comi_bna;
    }
    
    // Scopes estáticos
    
    public static function porYear(int $year): array
    {
        $sql = "SELECT * FROM orfs_transactions WHERE year = :year";
        $results = Database::fetchAll($sql, ['year' => $year]);
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }

        return $models;
    }
    
    public static function porCorredor(string $corredor, ?int $year = null): array
    {
        $sql = "SELECT * FROM orfs_transactions WHERE corredor = :corredor";
        $params = ['corredor' => $corredor];
        
        if ($year) {
            $sql .= " AND year = :year";
            $params['year'] = $year;
        }
        
        $results = Database::fetchAll($sql, $params);
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }

        return $models;
    }
    
    public static function porRueda(int $rueda, ?int $year = null): array
    {
        $sql = "SELECT * FROM orfs_transactions WHERE rueda_no = :rueda";
        $params = ['rueda' => $rueda];
        
        if ($year) {
            $sql .= " AND year = :year";
            $params['year'] = $year;
        }
        
        $results = Database::fetchAll($sql, $params);
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }

        return $models;
    }
    
    public static function porMes(string $mes, int $year): array
    {
        $sql = "SELECT * FROM orfs_transactions WHERE mes = :mes AND year = :year";
        $results = Database::fetchAll($sql, ['mes' => $mes, 'year' => $year]);
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }

        return $models;
    }
    
    public static function eliminarPorRueda(int $rueda): int
    {
        return Database::delete('orfs_transactions', 'rueda_no = :rueda', ['rueda' => $rueda]);
    }
}