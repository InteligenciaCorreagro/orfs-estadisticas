<?php
// src/Services/Reportes/NegociadoDiarioService.php

namespace App\Services\Reportes;

use App\Core\Database;

class NegociadoDiarioService
{
    /**
     * Obtener negociados por cliente con cada rueda como columna
     */
    public function obtenerNegociadosPorCliente(int $year, ?string $corredor = null): array
    {
        // Primero obtener todas las ruedas del año
        $ruedas = $this->obtenerRuedasDelAño($year);
        
        if (empty($ruedas)) {
            return [];
        }
        
        // Construir query dinámico con CASE para cada rueda
        $caseClauses = [];
        foreach ($ruedas as $rueda) {
            $caseClauses[] = "SUM(CASE WHEN rueda_no = {$rueda['rueda_no']} THEN negociado ELSE 0 END) AS rueda_{$rueda['rueda_no']}";
        }
        
        $sql = "
            SELECT 
                nit,
                nombre AS cliente,
                corredor,
                " . implode(",\n                ", $caseClauses) . ",
                SUM(negociado) AS total
            FROM orfs_transactions
            WHERE year = :year
        ";
        
        $params = ['year' => $year];
        
        if ($corredor) {
            $sql .= " AND corredor = :corredor";
            $params['corredor'] = $corredor;
        }
        
        $sql .= " GROUP BY nit, nombre, corredor ORDER BY corredor, nombre";
        
        $results = Database::fetchAll($sql, $params);
        
        // Agregar información de ruedas para el frontend
        return [
            'ruedas' => $ruedas,
            'data' => $results
        ];
    }
    
    /**
     * Obtener ruedas del año con información básica
     */
    private function obtenerRuedasDelAño(int $year): array
    {
        $sql = "
            SELECT DISTINCT 
                rueda_no,
                DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha
            FROM orfs_transactions
            WHERE year = :year
            ORDER BY rueda_no ASC
        ";
        
        return Database::fetchAll($sql, ['year' => $year]);
    }
    
    /**
     * Obtener resumen de actividad diaria
     */
    public function obtenerResumenDiario(int $year): array
    {
        $sql = "
            SELECT 
                fecha,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT corredor) AS total_corredores,
                SUM(negociado) AS total_negociado,
                SUM(comi_corr) AS total_comision
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY fecha
            ORDER BY fecha ASC
        ";
        
        return Database::fetchAll($sql, ['year' => $year]);
    }
    
    /**
     * Obtener clientes más activos
     */
    public function obtenerClientesMasActivos(int $year, int $limit = 20): array
    {
        $sql = "
            SELECT 
                nit,
                nombre AS cliente,
                corredor,
                COUNT(*) AS total_transacciones,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_negociado,
                AVG(negociado) AS promedio_negociado
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY nit, nombre, corredor
            ORDER BY total_transacciones DESC
            LIMIT :limit
        ";
        
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':year', $year, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}