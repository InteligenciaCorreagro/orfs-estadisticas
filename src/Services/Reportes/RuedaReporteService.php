<?php
// src/Services/Reportes/RuedaReporteService.php

namespace App\Services\Reportes;

use App\Core\Database;

class RuedaReporteService
{
    /**
     * Obtener listado de ruedas del año
     */
    public function obtenerRuedasDelAño(int $year): array
    {
        $sql = "
            SELECT DISTINCT 
                rueda_no,
                fecha,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY rueda_no, fecha
            ORDER BY rueda_no ASC
        ";
        
        return Database::fetchAll($sql, ['year' => $year]);
    }
    
    /**
     * Obtener detalle de una rueda específica
     */
    public function obtenerDetalleRueda(int $ruedaNo, int $year): array
    {
        $sql = "
            SELECT 
                ciudad,
                corredor,
                nombre AS cliente,
                nit,
                negociado AS transado,
                comi_corr AS comision,
                (comi_corr - comi_bna) AS margen
            FROM orfs_transactions
            WHERE rueda_no = :rueda AND year = :year
            ORDER BY ciudad, corredor, nombre
        ";
        
        return Database::fetchAll($sql, [
            'rueda' => $ruedaNo,
            'year' => $year
        ]);
    }
    
    /**
     * Obtener resumen por ciudad de una rueda
     */
    public function obtenerResumenPorCiudad(int $ruedaNo, int $year): array
    {
        $sql = "
            SELECT 
                ciudad,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen
            FROM orfs_transactions
            WHERE rueda_no = :rueda AND year = :year
            GROUP BY ciudad
            ORDER BY total_transado DESC
        ";
        
        return Database::fetchAll($sql, [
            'rueda' => $ruedaNo,
            'year' => $year
        ]);
    }
    
    /**
     * Obtener resumen por corredor de una rueda
     */
    public function obtenerResumenPorCorredor(int $ruedaNo, int $year): array
    {
        $sql = "
            SELECT 
                corredor,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT nit) AS total_clientes,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen
            FROM orfs_transactions
            WHERE rueda_no = :rueda AND year = :year
            GROUP BY corredor
            ORDER BY total_transado DESC
        ";
        
        return Database::fetchAll($sql, [
            'rueda' => $ruedaNo,
            'year' => $year
        ]);
    }
    
    /**
     * Comparar múltiples ruedas
     */
    public function compararRuedas(array $ruedas, int $year): array
    {
        $placeholders = implode(',', array_fill(0, count($ruedas), '?'));
        
        $sql = "
            SELECT 
                rueda_no,
                fecha,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                COUNT(DISTINCT nit) AS total_clientes,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen,
                AVG(negociado) AS promedio_transaccion
            FROM orfs_transactions
            WHERE rueda_no IN ({$placeholders}) AND year = ?
            GROUP BY rueda_no, fecha
            ORDER BY rueda_no ASC
        ";
        
        $params = array_merge($ruedas, [$year]);
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener estadísticas de rueda
     */
    public function obtenerEstadisticasRueda(int $ruedaNo, int $year): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                COUNT(DISTINCT nit) AS total_clientes,
                COUNT(DISTINCT ciudad) AS total_ciudades,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen,
                AVG(negociado) AS promedio_transaccion,
                MAX(negociado) AS max_transaccion,
                MIN(negociado) AS min_transaccion,
                fecha
            FROM orfs_transactions
            WHERE rueda_no = :rueda AND year = :year
            GROUP BY fecha
        ";
        
        $result = Database::fetch($sql, [
            'rueda' => $ruedaNo,
            'year' => $year
        ]);
        
        return $result ?: [];
    }
}