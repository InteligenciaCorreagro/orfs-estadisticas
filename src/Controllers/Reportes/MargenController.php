<?php
// src/Controllers/Reportes/MargenController.php

namespace App\Controllers\Reportes;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\MargenReporteService;
use App\Services\Excel\ExcelWriter;
use App\Models\Trader;

class MargenController
{
    private MargenReporteService $margenService;
    
    public function __construct()
    {
        $this->margenService = new MargenReporteService();
    }
    
    /**
     * Mostrar página de reporte de margen
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
        require __DIR__ . '/../../Views/reportes/margen.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener datos del reporte de margen
     */
    public function getData(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        
        $data = $this->margenService->obtenerReporteMargen($year, $corredor);
        
        $response = new Response();
        $response->success('Reporte de margen obtenido', $data);
    }
    
    /**
     * API: Obtener top corredores por margen
     */
    public function getTopCorredores(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $limit = (int) $request->get('limit', 10);
        
        $data = $this->margenService->obtenerTopCorredoresPorMargen($year, $limit);
        
        $response = new Response();
        $response->success('Top corredores obtenido', $data);
    }
    
    /**
     * API: Obtener rentabilidad por cliente
     */
    public function getRentabilidadPorCliente(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        
        $data = $this->margenService->obtenerRentabilidadPorCliente($year, $corredor);
        
        $response = new Response();
        $response->success('Rentabilidad por cliente obtenida', $data);
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
        
        $data = $this->margenService->obtenerReporteMargen($year, $corredor);
        
        // Preparar datos para Excel
        $headers = [
            'Corredor', 'NIT', 'Cliente',
            'Ene-Trans', 'Ene-Com', 'Ene-Marg',
            'Feb-Trans', 'Feb-Com', 'Feb-Marg',
            'Mar-Trans', 'Mar-Com', 'Mar-Marg',
            'Abr-Trans', 'Abr-Com', 'Abr-Marg',
            'May-Trans', 'May-Com', 'May-Marg',
            'Jun-Trans', 'Jun-Com', 'Jun-Marg',
            'Jul-Trans', 'Jul-Com', 'Jul-Marg',
            'Ago-Trans', 'Ago-Com', 'Ago-Marg',
            'Sep-Trans', 'Sep-Com', 'Sep-Marg',
            'Oct-Trans', 'Oct-Com', 'Oct-Marg',
            'Nov-Trans', 'Nov-Com', 'Nov-Marg',
            'Dic-Trans', 'Dic-Com', 'Dic-Marg',
            'Total-Trans', 'Total-Com', 'Total-Marg'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['corredor'], $row['nit'], $row['cliente'],
                $row['enero_transado'], $row['enero_comision'], $row['enero_margen'],
                $row['febrero_transado'], $row['febrero_comision'], $row['febrero_margen'],
                $row['marzo_transado'], $row['marzo_comision'], $row['marzo_margen'],
                $row['abril_transado'], $row['abril_comision'], $row['abril_margen'],
                $row['mayo_transado'], $row['mayo_comision'], $row['mayo_margen'],
                $row['junio_transado'], $row['junio_comision'], $row['junio_margen'],
                $row['julio_transado'], $row['julio_comision'], $row['julio_margen'],
                $row['agosto_transado'], $row['agosto_comision'], $row['agosto_margen'],
                $row['septiembre_transado'], $row['septiembre_comision'], $row['septiembre_margen'],
                $row['octubre_transado'], $row['octubre_comision'], $row['octubre_margen'],
                $row['noviembre_transado'], $row['noviembre_comision'], $row['noviembre_margen'],
                $row['diciembre_transado'], $row['diciembre_comision'], $row['diciembre_margen'],
                $row['total_transado'], $row['total_comision'], $row['total_margen']
            ];
        }
        
        $excel = new ExcelWriter();
        $excel->setHeaders($headers)
              ->setData($rows)
              ->autoSize()
              ->addBorders()
              ->formatCurrency('D2:AL' . (count($rows) + 1));
        
        $filename = "Margen_{$year}" . ($corredor ? "_{$corredor}" : "") . ".xlsx";
        $excel->download($filename);
    }
}