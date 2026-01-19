<?php
// src/Services/Reportes/ConsolidadoService.php

namespace App\Services\Reportes;

use App\Core\Database;

class ConsolidadoService
{
    /**
     * Obtener dashboard consolidado con KPIs principales
     */
    public function obtenerDashboardConsolidado(int $year): array
    {
        return [
            'kpis' => $this->obtenerKPIs($year),
            'por_mes' => $this->obtenerDatosPorMes($year),
            'por_corredor' => $this->obtenerDatosPorCorredor($year),
            'comparacion_anual' => $this->obtenerComparacionAnual($year),
            'top_clientes' => $this->obtenerTopClientes($year, 10),
            'top_clientes_comision' => $this->obtenerTopClientesPorComision($year, 10),
            'ultimas_ruedas' => $this->obtenerUltimasRuedas($year, 5)
        ];
    }
    
    /**
     * KPIs principales
     */
    private function obtenerKPIs(int $year): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                COUNT(DISTINCT nit) AS total_clientes,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen,
                AVG(negociado) AS promedio_transaccion
            FROM orfs_transactions
            WHERE year = :year
        ";
        
        $result = Database::fetch($sql, ['year' => $year]);
        
        return $result ?: [];
    }
    
    /**
     * Datos agrupados por mes
     */
    private function obtenerDatosPorMes(int $year): array
    {
        $sql = "
            SELECT 
                mes,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY mes
            ORDER BY FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')
        ";
        
        return Database::fetchAll($sql, ['year' => $year]);
    }
    
    /**
     * Datos agrupados por corredor
     */
    private function obtenerDatosPorCorredor(int $year): array
    {
        $sql = "
            SELECT 
                corredor,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT nit) AS total_clientes,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen,
                (SUM(comi_corr - comi_bna) / SUM(negociado) * 100) AS porcentaje_margen
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY corredor
            ORDER BY total_negociado DESC
        ";
        
        return Database::fetchAll($sql, ['year' => $year]);
    }
    
    /**
     * Comparación año actual vs anterior
     */
    private function obtenerComparacionAnual(int $year): array
    {
        $sql = "
            SELECT 
                year,
                COUNT(*) AS total_transacciones,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen
            FROM orfs_transactions
            WHERE year IN (:year_actual, :year_anterior)
            GROUP BY year
            ORDER BY year DESC
        ";
        
        $results = Database::fetchAll($sql, [
            'year_actual' => $year,
            'year_anterior' => $year - 1
        ]);
        
        $comparacion = [
            'año_actual' => null,
            'año_anterior' => null,
            'crecimiento' => []
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
            
            $comparacion['crecimiento'] = [
                'transacciones' => $this->calcularCrecimiento(
                    $actual['total_transacciones'],
                    $anterior['total_transacciones']
                ),
                'negociado' => $this->calcularCrecimiento(
                    $actual['total_negociado'],
                    $anterior['total_negociado']
                ),
                'comision' => $this->calcularCrecimiento(
                    $actual['total_comision'],
                    $anterior['total_comision']
                ),
                'margen' => $this->calcularCrecimiento(
                    $actual['total_margen'],
                    $anterior['total_margen']
                )
            ];
        }
        
        return $comparacion;
    }
    
    /**
     * Top clientes por volumen
     */
    private function obtenerTopClientes(int $year, int $limit = 10): array
    {
        $sql = "
            SELECT 
                nit,
                nombre AS cliente,
                corredor,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                COUNT(*) AS total_transacciones
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY nit, nombre, corredor
            ORDER BY total_negociado DESC
            LIMIT :limit
        ";
        
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Top clientes por comision
     */
    private function obtenerTopClientesPorComision(int $year, int $limit = 10): array
    {
        $sql = "
            SELECT 
                nit,
                nombre AS cliente,
                corredor,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision,
                COUNT(*) AS total_transacciones
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY nit, nombre, corredor
            ORDER BY total_comision DESC
            LIMIT :limit
        ";
        
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Últimas ruedas realizadas
     */
    private function obtenerUltimasRuedas(int $year, int $limit = 5): array
    {
        $sql = "
            SELECT 
                rueda_no,
                fecha,
                COUNT(*) AS total_transacciones,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY rueda_no, fecha
            ORDER BY rueda_no DESC
            LIMIT :limit
        ";
        
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Calcular porcentaje de crecimiento
     */
    private function calcularCrecimiento(?float $actual, ?float $anterior): array
    {
        if (!$anterior || $anterior == 0) {
            return [
                'valor' => $actual ?? 0,
                'porcentaje' => 0,
                'positivo' => true
            ];
        }
        
        $diferencia = $actual - $anterior;
        $porcentaje = ($diferencia / $anterior) * 100;
        
        return [
            'valor' => $diferencia,
            'porcentaje' => round($porcentaje, 2),
            'positivo' => $diferencia >= 0
        ];
    }
    
    /**
     * Obtener resumen ejecutivo para impresión
     */
    public function obtenerResumenEjecutivo(int $year): array
    {
        $kpis = $this->obtenerKPIs($year);
        $porMes = $this->obtenerDatosPorMes($year);
        $porCorredor = $this->obtenerDatosPorCorredor($year);
        $comparacion = $this->obtenerComparacionAnual($year);
        
        return [
            'año' => $year,
            'fecha_generacion' => date('d/m/Y H:i:s'),
            'resumen_general' => $kpis,
            'evolucion_mensual' => $porMes,
            'ranking_corredores' => $porCorredor,
            'comparacion_anual' => $comparacion
        ];
    }
}
