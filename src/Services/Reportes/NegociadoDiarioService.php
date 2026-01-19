<?php
// src/Services/Reportes/NegociadoDiarioService.php

namespace App\Services\Reportes;

use App\Core\Database;

class NegociadoDiarioService
{
    /**
     * Obtener negociados por cliente con cada rueda como columna
     */
    public function obtenerNegociadosPorCliente(int $year, array|string|null $corredor = null): array
    {
        // Primero obtener todas las ruedas del año
        $ruedas = $this->obtenerRuedasDelAño($year, $corredor);
        
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
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        
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
    private function obtenerRuedasDelAño(int $year, array|string|null $corredor = null): array
    {
        $sql = "
            SELECT DISTINCT 
                rueda_no,
                DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha
            FROM orfs_transactions
            WHERE year = :year
        ";

        $params = ['year' => $year];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);
        $sql .= " ORDER BY rueda_no ASC";

        return Database::fetchAll($sql, $params);
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

    /**
     * Obtener resumen agrupado por trader para vista de negociado diario
     */
    public function obtenerResumenPorTrader(int $year, array|string|null $corredor = null): array
    {
        $sql = "
            SELECT
                corredor AS trader,
                COUNT(DISTINCT nit) AS total_clientes,
                COUNT(DISTINCT rueda_no) AS total_ruedas,
                SUM(negociado) AS total_transado,
                SUM(comi_corr) AS total_comision,
                SUM(comi_corr - comi_bna) AS total_margen
            FROM orfs_transactions
            WHERE year = :year
        ";

        $params = ['year' => $year];
        $sql = $this->appendCorredorFilter($sql, $corredor, $params);

        $sql .= " GROUP BY corredor ORDER BY corredor ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Obtener detalle mensual de un trader específico con todos sus clientes
     */
    public function obtenerDetalleMensualPorTrader(int $year, array|string|null $trader): array
    {
        $sql = "
            SELECT
                nit,
                nombre AS cliente,
                corredor AS trader,
                rueda_no,
                DATE_FORMAT(fecha, '%Y-%m') AS mes,
                MONTH(fecha) AS mes_num,
                SUM(negociado) AS transado,
                SUM(comi_corr) AS comision,
                SUM(comi_corr - comi_bna) AS margen
            FROM orfs_transactions
            WHERE year = :year
            GROUP BY nit, nombre, corredor, rueda_no, mes, mes_num
            ORDER BY mes_num ASC, nombre ASC, rueda_no ASC
        ";

        $params = ['year' => $year];
        $sql = $this->appendCorredorFilter($sql, $trader, $params);

        return Database::fetchAll($sql, $params);
    }

    /**
     * Obtener vista matricial de negociados: Cliente x Rueda
     * Para mostrar todos los clientes con sus transacciones por rueda
     */
    public function obtenerVistaMatricialNegociados(int $year, array|string|null $corredor = null): array
    {
        // Primero obtener todas las ruedas del año ordenadas
        $sqlRuedas = "
            SELECT DISTINCT rueda_no, fecha, mes, MONTH(fecha) AS mes_num
            FROM orfs_transactions
            WHERE year = :year
        ";

        $paramsRuedas = ['year' => $year];

        $sqlRuedas = $this->appendCorredorFilter($sqlRuedas, $corredor, $paramsRuedas);

        $sqlRuedas .= " ORDER BY rueda_no ASC";

        $ruedas = Database::fetchAll($sqlRuedas, $paramsRuedas);

        // Obtener todos los datos agrupados por cliente y rueda
        $sqlData = "
            SELECT
                nit,
                nombre AS cliente,
                corredor,
                rueda_no,
                mes,
                MONTH(fecha) AS mes_num,
                SUM(negociado) AS transado
            FROM orfs_transactions
            WHERE year = :year
        ";

        $paramsData = ['year' => $year];

        $sqlData = $this->appendCorredorFilter($sqlData, $corredor, $paramsData);

        $sqlData .= " GROUP BY nit, nombre, corredor, rueda_no, mes, mes_num ORDER BY nombre ASC, rueda_no ASC";

        $data = Database::fetchAll($sqlData, $paramsData);

        return [
            'ruedas' => $ruedas,
            'data' => $data
        ];
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
