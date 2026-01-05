<?php
// src/Services/Reportes/MargenReporteService.php

namespace App\Services\Reportes;

use App\Core\Database;

class MargenReporteService
{
    /**
     * Obtener reporte de margen por corredor y cliente con datos mensuales
     */
    public function obtenerReporteMargen(int $year, ?string $corredor = null): array
    {
        $sql = "
            SELECT 
                corredor,
                nit,
                nombre AS cliente,
                -- Enero
                SUM(CASE WHEN mes = 'Enero' THEN negociado ELSE 0 END) AS enero_transado,
                SUM(CASE WHEN mes = 'Enero' THEN comi_corr ELSE 0 END) AS enero_comision,
                SUM(CASE WHEN mes = 'Enero' THEN (comi_corr - comi_bna) ELSE 0 END) AS enero_margen,
                -- Febrero
                SUM(CASE WHEN mes = 'Febrero' THEN negociado ELSE 0 END) AS febrero_transado,
                SUM(CASE WHEN mes = 'Febrero' THEN comi_corr ELSE 0 END) AS febrero_comision,
                SUM(CASE WHEN mes = 'Febrero' THEN (comi_corr - comi_bna) ELSE 0 END) AS febrero_margen,
                -- Marzo
                SUM(CASE WHEN mes = 'Marzo' THEN negociado ELSE 0 END) AS marzo_transado,
                SUM(CASE WHEN mes = 'Marzo' THEN comi_corr ELSE 0 END) AS marzo_comision,
                SUM(CASE WHEN mes = 'Marzo' THEN (comi_corr - comi_bna) ELSE 0 END) AS marzo_margen,
                -- Abril
                SUM(CASE WHEN mes = 'Abril' THEN negociado ELSE 0 END) AS abril_transado,
                SUM(CASE WHEN mes = 'Abril' THEN comi_corr ELSE 0 END) AS abril_comision,
                SUM(CASE WHEN mes = 'Abril' THEN (comi_corr - comi_bna) ELSE 0 END) AS abril_margen,
                -- Mayo
                SUM(CASE WHEN mes = 'Mayo' THEN negociado ELSE 0 END) AS mayo_transado,
                SUM(CASE WHEN mes = 'Mayo' THEN comi_corr ELSE 0 END) AS mayo_comision,
                SUM(CASE WHEN mes = 'Mayo' THEN (comi_corr - comi_bna) ELSE 0 END) AS mayo_margen,
                -- Junio
                SUM(CASE WHEN mes = 'Junio' THEN negociado ELSE 0 END) AS junio_transado,
                SUM(CASE WHEN mes = 'Junio' THEN comi_corr ELSE 0 END) AS junio_comision,
                SUM(CASE WHEN mes = 'Junio' THEN (comi_corr - comi_bna) ELSE 0 END) AS junio_margen,
                -- Julio
                SUM(CASE WHEN mes = 'Julio' THEN negociado ELSE 0 END) AS julio_transado,
                SUM(CASE WHEN mes = 'Julio' THEN comi_corr ELSE 0 END) AS julio_comision,
                SUM(CASE WHEN mes = 'Julio' THEN (comi_corr - comi_bna) ELSE 0 END) AS julio_margen,
                -- Agosto
                SUM(CASE WHEN mes = 'Agosto' THEN negociado ELSE 0 END) AS agosto_transado,
                SUM(CASE WHEN mes = 'Agosto' THEN comi_corr ELSE 0 END) AS agosto_comision,
                SUM(CASE WHEN mes = 'Agosto' THEN (comi_corr - comi_bna) ELSE 0 END) AS agosto_margen,
                -- Septiembre
                SUM(CASE WHEN mes = 'Septiembre' THEN negociado ELSE 0 END) AS septiembre_transado,
                SUM(CASE WHEN mes = 'Septiembre' THEN comi_corr ELSE 0 END) AS septiembre_comision,
                SUM(CASE WHEN mes = 'Septiembre' THEN (comi_corr - comi_bna) ELSE 0 END) AS septiembre_margen,
                -- Octubre
                SUM(CASE WHEN mes = 'Octubre' THEN negociado ELSE 0 END) AS octubre_transado,
                SUM(CASE WHEN mes = 'Octubre' THEN comi_corr ELSE 0 END) AS octubre_comision,
                SUM(CASE WHEN mes = 'Octubre' THEN (comi_corr - comi_bna) ELSE 0 END) AS octubre_margen,
                -- Noviembre
                SUM(CASE WHEN mes = 'Noviembre' THEN negociado ELSE 0 END) AS noviembre_transado,
                SUM(CASE WHEN mes = 'Noviembre' THEN comi_corr ELSE 0 END) AS noviembre_comision,
                SUM(CASE WHEN mes = 'Noviembre' THEN (comi_corr - comi_bna) ELSE 0 END) AS noviembre_margen,
                -- Diciembre
                SUM(CASE WHEN mes = 'Diciembre' THEN negociado ELSE 0 END) AS diciembre_transado,
                SUM(CASE WHEN mes = 'Diciembre' THEN comi_corr ELSE 0 END) AS diciembre_comision,
                SUM(CASE WHEN mes = 'Diciembre' THEN (comi_corr - comi_bna) ELSE 0 END) AS diciembre_margen,
                -- Totales
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen
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
     * Obtener top corredores por margen
     */
    public function obtenerTopCorredoresPorMargen(int $year, int $limit = 10): array
    {
        $sql = "
            SELECT 
                corredor,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen,
                COUNT(DISTINCT nit) AS total_clientes,
                AVG(comi_corr - comi_bna) AS margen_promedio
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY corredor
            ORDER BY total_margen DESC
            LIMIT :limit
        ";
        
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener análisis de rentabilidad por cliente
     */
    public function obtenerRentabilidadPorCliente(int $year, ?string $corredor = null): array
    {
        $sql = "
            SELECT 
                corredor,
                nit,
                nombre AS cliente,
                COUNT(*) AS total_transacciones,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen,
                AVG(negociado) AS promedio_transaccion,
                (SUM(comi_corr - comi_bna) / SUM(negociado) * 100) AS porcentaje_margen
            FROM orfs_transactions
            WHERE year = :year
        ";
        
        $params = ['year' => $year];
        
        if ($corredor) {
            $sql .= " AND corredor = :corredor";
            $params['corredor'] = $corredor;
        }
        
        $sql .= " GROUP BY corredor, nit, nombre 
                  HAVING total_margen > 0
                  ORDER BY total_margen DESC";
        
        return Database::fetchAll($sql, $params);
    }
}