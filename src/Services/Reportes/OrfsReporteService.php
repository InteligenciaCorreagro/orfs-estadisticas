<?php
// src/Services/Reportes/OrfsReporteService.php

namespace App\Services\Reportes;

use App\Core\Database;

class OrfsReporteService
{
    /**
     * Obtener reporte ORFS agrupado por corredor, cliente y mes
     */
    public function obtenerReporteOrfs(int $year, ?string $corredor = null): array
    {
        $sql = "
            SELECT 
                corredor,
                nit,
                nombre AS cliente,
                SUM(CASE WHEN mes = 'Enero' THEN negociado ELSE 0 END) AS enero,
                SUM(CASE WHEN mes = 'Febrero' THEN negociado ELSE 0 END) AS febrero,
                SUM(CASE WHEN mes = 'Marzo' THEN negociado ELSE 0 END) AS marzo,
                SUM(CASE WHEN mes = 'Abril' THEN negociado ELSE 0 END) AS abril,
                SUM(CASE WHEN mes = 'Mayo' THEN negociado ELSE 0 END) AS mayo,
                SUM(CASE WHEN mes = 'Junio' THEN negociado ELSE 0 END) AS junio,
                SUM(CASE WHEN mes = 'Julio' THEN negociado ELSE 0 END) AS julio,
                SUM(CASE WHEN mes = 'Agosto' THEN negociado ELSE 0 END) AS agosto,
                SUM(CASE WHEN mes = 'Septiembre' THEN negociado ELSE 0 END) AS septiembre,
                SUM(CASE WHEN mes = 'Octubre' THEN negociado ELSE 0 END) AS octubre,
                SUM(CASE WHEN mes = 'Noviembre' THEN negociado ELSE 0 END) AS noviembre,
                SUM(CASE WHEN mes = 'Diciembre' THEN negociado ELSE 0 END) AS diciembre,
                SUM(negociado) AS total
            FROM orfs_transactions
            WHERE year = :year
        ";
        
        $params = ['year' => $year];
        
        if ($corredor) {
            $sql .= " AND corredor = :corredor";
            $params['corredor'] = $corredor;
        }
        
        $sql .= " GROUP BY corredor, nit, nombre ORDER BY corredor, nombre";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener totales por corredor
     */
    public function obtenerTotalesPorCorredor(int $year): array
    {
        $sql = "
            SELECT 
                corredor,
                COUNT(DISTINCT nit) AS total_clientes,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY corredor
            ORDER BY total_negociado DESC
        ";
        
        return Database::fetchAll($sql, ['year' => $year]);
    }
    
    /**
     * Obtener resumen por mes
     */
    public function obtenerResumenPorMes(int $year, ?string $corredor = null): array
    {
        $sql = "
            SELECT 
                mes,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                COUNT(*) AS total_transacciones,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
        ";
        
        $params = ['year' => $year];
        
        if ($corredor) {
            $sql .= " AND corredor = :corredor";
            $params['corredor'] = $corredor;
        }
        
        $sql .= " GROUP BY mes ORDER BY 
            FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')
        ";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticasGenerales(int $year, ?string $corredor = null): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                COUNT(DISTINCT nit) AS total_clientes,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                AVG(negociado) AS promedio_negociado,
                MAX(negociado) AS max_negociado,
                MIN(negociado) AS min_negociado
            FROM orfs_transactions
            WHERE year = :year
        ";
        
        $params = ['year' => $year];
        
        if ($corredor) {
            $sql .= " AND corredor = :corredor";
            $params['corredor'] = $corredor;
        }
        
        $result = Database::fetch($sql, $params);
        
        return $result ?: [];
    }
    
    /**
     * Comparar año actual vs año anterior
     */
    public function compararConAñoAnterior(int $year, ?string $corredor = null): array
    {
        $sql = "
            SELECT 
                year,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                COUNT(*) AS total_transacciones
            FROM orfs_transactions
            WHERE year IN (:year_actual, :year_anterior)
        ";
        
        $params = [
            'year_actual' => $year,
            'year_anterior' => $year - 1
        ];
        
        if ($corredor) {
            $sql .= " AND corredor = :corredor";
            $params['corredor'] = $corredor;
        }
        
        $sql .= " GROUP BY year ORDER BY year DESC";
        
        $results = Database::fetchAll($sql, $params);
        
        $comparacion = [
            'año_actual' => null,
            'año_anterior' => null,
            'variacion_negociado' => 0,
            'variacion_comision' => 0,
            'variacion_transacciones' => 0,
            'porcentaje_variacion_negociado' => 0,
            'porcentaje_variacion_comision' => 0
        ];
        
        foreach ($results as $result) {
            if ($result['year'] == $year) {
                $comparacion['año_actual'] = $result;
            } else {
                $comparacion['año_anterior'] = $result;
            }
        }
        
        if ($comparacion['año_actual'] && $comparacion['año_anterior']) {
            $actual = $comparacion['año_actual'];
            $anterior = $comparacion['año_anterior'];
            
            $comparacion['variacion_negociado'] = $actual['total_negociado'] - $anterior['total_negociado'];
            $comparacion['variacion_comision'] = $actual['total_comision'] - $anterior['total_comision'];
            $comparacion['variacion_transacciones'] = $actual['total_transacciones'] - $anterior['total_transacciones'];
            
            if ($anterior['total_negociado'] > 0) {
                $comparacion['porcentaje_variacion_negociado'] = 
                    ($comparacion['variacion_negociado'] / $anterior['total_negociado']) * 100;
            }
            
            if ($anterior['total_comision'] > 0) {
                $comparacion['porcentaje_variacion_comision'] = 
                    ($comparacion['variacion_comision'] / $anterior['total_comision']) * 100;
            }
        }
        
        return $comparacion;
    }
}