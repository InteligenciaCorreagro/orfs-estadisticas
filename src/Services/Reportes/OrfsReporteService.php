<?php
// src/Services/Reportes/OrfsReporteService.php

namespace App\Services\Reportes;

use App\Core\Database;

class OrfsReporteService
{
    /**
     * Obtener reporte ORFS agrupado por corredor, cliente y mes
     */
    public function obtenerReporteOrfs(int $year, array|string|null $corredor = null): array
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
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        
        $sql .= " GROUP BY corredor, nit, nombre ORDER BY corredor, nombre";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener totales por corredor
     */
    public function obtenerTotalesPorCorredor(int $year, array|string|null $corredor = null): array
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
        ";

        $params = ['year' => $year];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        $sql .= " GROUP BY corredor
            ORDER BY total_negociado DESC";

        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener resumen por mes
     */
    public function obtenerResumenPorMes(int $year, array|string|null $corredor = null): array
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
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        
        $sql .= " GROUP BY mes ORDER BY 
            FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')
        ";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticasGenerales(int $year, array|string|null $corredor = null): array
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
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        
        $result = Database::fetch($sql, $params);
        
        return $result ?: [];
    }
    
    /**
     * Comparar año actual vs año anterior
     */
    public function compararConAñoAnterior(int $year, array|string|null $corredor = null): array
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

        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        
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

    /**
     * Top clientes por indicador
     */
    public function obtenerTopClientes(int $year, array|string|null $corredor = null, string $orden = 'negociado', int $limit = 5): array
    {
        $ordenesPermitidos = [
            'negociado' => 'total_negociado',
            'comision' => 'total_comision',
            'transacciones' => 'total_transacciones'
        ];
        $ordenColumna = $ordenesPermitidos[$orden] ?? $ordenesPermitidos['negociado'];

        $sql = "
            SELECT 
                nit,
                nombre AS cliente,
                corredor,
                COUNT(*) AS total_transacciones,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
        ";

        $params = ['year' => $year];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        $sql .= " GROUP BY nit, nombre, corredor
                  ORDER BY {$ordenColumna} DESC
                  LIMIT :limit";

        $stmt = Database::getInstance()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Top clientes del mes con conteo de transacciones
     */
    public function obtenerTopClientesPorMes(int $year, string $mes, array|string|null $corredor = null, int $limit = 10): array
    {
        $sql = "
            SELECT 
                nit,
                nombre AS cliente,
                corredor,
                COUNT(*) AS total_transacciones,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
              AND mes = :mes
        ";

        $params = [
            'year' => $year,
            'mes' => $mes
        ];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        $sql .= " GROUP BY nit, nombre, corredor
                  ORDER BY total_transacciones DESC, total_negociado DESC
                  LIMIT :limit";

        $stmt = Database::getInstance()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
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
