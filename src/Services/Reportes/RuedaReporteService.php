<?php
// src/Services/Reportes/RuedaReporteService.php

namespace App\Services\Reportes;

use App\Core\Database;

class RuedaReporteService
{
    /**
     * Obtener listado de ruedas del año
     */
    public function obtenerRuedasDelAño(int $year, array|string|null $corredor = null): array
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
        ";

        $params = ['year' => $year];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);

        $sql .= " GROUP BY rueda_no, fecha
                  ORDER BY rueda_no ASC";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener detalle de una rueda específica
     */
    public function obtenerDetalleRueda(int $ruedaNo, int $year, array|string|null $corredor = null): array
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
        ";

        $params = [
            'rueda' => $ruedaNo,
            'year' => $year
        ];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);

        $sql .= " ORDER BY ciudad, corredor, nombre";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener resumen por ciudad de una rueda
     */
    public function obtenerResumenPorCiudad(int $ruedaNo, int $year, array|string|null $corredor = null): array
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
        ";

        $params = [
            'rueda' => $ruedaNo,
            'year' => $year
        ];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);

        $sql .= " GROUP BY ciudad
                  ORDER BY total_transado DESC";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener resumen por corredor de una rueda
     */
    public function obtenerResumenPorCorredor(int $ruedaNo, int $year, array|string|null $corredor = null): array
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
        ";

        $params = [
            'rueda' => $ruedaNo,
            'year' => $year
        ];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);

        $sql .= " GROUP BY corredor
                  ORDER BY total_transado DESC";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Comparar múltiples ruedas
     */
    public function compararRuedas(array $ruedas, int $year, array|string|null $corredor = null): array
    {
        $placeholders = [];
        $params = ['year' => $year];
        foreach ($ruedas as $index => $rueda) {
            $key = 'rueda' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $rueda;
        }

        $conditions = [
            'rueda_no IN (' . implode(',', $placeholders) . ')',
            'year = :year'
        ];

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
            WHERE " . implode(' AND ', $conditions) . "
        ";

        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        $sql .= " GROUP BY rueda_no, fecha
            ORDER BY rueda_no ASC";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener estadísticas de rueda
     */
    public function obtenerEstadisticasRueda(int $ruedaNo, int $year, array|string|null $corredor = null): array
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
        ";

        $params = [
            'rueda' => $ruedaNo,
            'year' => $year
        ];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);

        $sql .= " GROUP BY fecha";
        
        $result = Database::fetch($sql, $params);
        
        return $result ?: [];
    }

    private function appendCorredorFilter(string $sql, array|string|null $corredor, array &$params): string
    {
        if (!$corredor) {
            return $sql;
        }

        if (is_array($corredor)) {
            $placeholders = [];
            $index = 0;
            foreach ($corredor as $nombre) {
                $nombre = trim((string) $nombre);
                if ($nombre === '') {
                    continue;
                }
                $key = 'corredor' . $index;
                $placeholders[] = 'LOWER(TRIM(:' . $key . '))';
                $params[$key] = $nombre;
                $index++;
            }

            if (!$placeholders) {
                return $sql;
            }

            return $sql . ' AND LOWER(TRIM(corredor)) IN (' . implode(',', $placeholders) . ')';
        }

        $params['corredor'] = trim((string) $corredor);
        return $sql . ' AND LOWER(TRIM(corredor)) = LOWER(TRIM(:corredor))';
    }
}
