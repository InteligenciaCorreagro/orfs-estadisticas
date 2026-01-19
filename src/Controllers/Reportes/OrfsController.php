<?php
// src/Controllers/Reportes/OrfsController.php

namespace App\Controllers\Reportes;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\OrfsReporteService;
use App\Services\Excel\ExcelWriter;
use App\Models\Trader;

class OrfsController
{
    private OrfsReporteService $orfsService;
    
    public function __construct()
    {
        $this->orfsService = new OrfsReporteService();
    }
    
    /**
     * Mostrar página de reporte ORFS
     */
    public function index(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $year = $request->get('year', date('Y'));
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        
        // Obtener traders para filtro (solo admin)
        $traders = [];
        if ($userRole === 'admin') {
            $traders = Trader::activos();
        }
        
        ob_start();
        require __DIR__ . '/../../Views/reportes/orfs.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener datos del reporte ORFS
     */
    public function getData(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        $corredorFiltro = $userRole === 'trader' ? getTraderCorredoresFromSession() : $corredor;
        
        $data = $this->orfsService->obtenerReporteOrfs($year, $corredorFiltro);
        
        $response = new Response();
        $response->success('Reporte ORFS obtenido', $data);
    }
    
    /**
     * API: Obtener totales por corredor
     */
    public function getTotalesPorCorredor(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $corredorFiltro = $userRole === 'trader' ? getTraderCorredoresFromSession() : null;
        
        $data = $this->orfsService->obtenerTotalesPorCorredor($year, $corredorFiltro);
        
        $response = new Response();
        $response->success('Totales por corredor obtenidos', $data);
    }
    
    /**
     * API: Obtener resumen por mes
     */
    public function getResumenPorMes(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        $corredorFiltro = $userRole === 'trader' ? getTraderCorredoresFromSession() : $corredor;
        
        $data = $this->orfsService->obtenerResumenPorMes($year, $corredorFiltro);
        
        $response = new Response();
        $response->success('Resumen por mes obtenido', $data);
    }
    
    /**
     * API: Obtener estadísticas generales
     */
    public function getEstadisticas(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        $corredorFiltro = $userRole === 'trader' ? getTraderCorredoresFromSession() : $corredor;
        
        $data = $this->orfsService->obtenerEstadisticasGenerales($year, $corredorFiltro);
        
        $response = new Response();
        $response->success('Estadísticas obtenidas', $data);
    }
    
    /**
     * API: Comparar con año anterior
     */
    public function compararAñoAnterior(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        $corredorFiltro = $userRole === 'trader' ? getTraderCorredoresFromSession() : $corredor;
        
        $data = $this->orfsService->compararConAñoAnterior($year, $corredorFiltro);
        
        $response = new Response();
        $response->success('Comparación obtenida', $data);
    }
    
    /**
     * Exportar a Excel
     */
    public function exportarExcel(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        $corredorFiltro = $userRole === 'trader' ? getTraderCorredoresFromSession() : $corredor;
        
        $data = $this->orfsService->obtenerReporteOrfs($year, $corredorFiltro);
        
        // Preparar datos para Excel
        $headers = [
            'Corredor', 'NIT', 'Cliente',
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
            'Total'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['corredor'],
                $row['nit'],
                $row['cliente'],
                $row['enero'],
                $row['febrero'],
                $row['marzo'],
                $row['abril'],
                $row['mayo'],
                $row['junio'],
                $row['julio'],
                $row['agosto'],
                $row['septiembre'],
                $row['octubre'],
                $row['noviembre'],
                $row['diciembre'],
                $row['total']
            ];
        }
        
        $excel = new ExcelWriter();
        $excel->setHeaders($headers)
              ->setData($rows)
              ->autoSize()
              ->addBorders()
              ->formatCurrency('D2:P' . (count($rows) + 1));
        
        $filename = "ORFS_{$year}" . ($corredor ? "_{$corredor}" : "") . ".xlsx";
        $excel->download($filename);
    }
}
