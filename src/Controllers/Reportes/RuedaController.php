<?php
// src/Controllers/Reportes/RuedaController.php

namespace App\Controllers\Reportes;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\RuedaReporteService;
use App\Services\Excel\ExcelWriter;
use App\Models\Trader;

class RuedaController
{
    private RuedaReporteService $ruedaService;
    
    public function __construct()
    {
        $this->ruedaService = new RuedaReporteService();
    }
    
    /**
     * Mostrar página de reporte de ruedas
     */
    public function index(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        
        $year = $request->get('year', date('Y'));
        
        ob_start();
        require __DIR__ . '/../../Views/reportes/rueda.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener listado de ruedas del año
     */
    public function getRuedasDelAño(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->ruedaService->obtenerRuedasDelAño($year);
        
        $response = new Response();
        $response->success('Ruedas obtenidas', $data);
    }
    
    /**
     * API: Obtener detalle de una rueda específica
     */
    public function getDetalleRueda(Request $request, int $ruedaNo): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->ruedaService->obtenerDetalleRueda($ruedaNo, $year);
        
        $response = new Response();
        $response->success('Detalle de rueda obtenido', $data);
    }
    
    /**
     * API: Obtener resumen por ciudad de una rueda
     */
    public function getResumenPorCiudad(Request $request, int $ruedaNo): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->ruedaService->obtenerResumenPorCiudad($ruedaNo, $year);
        
        $response = new Response();
        $response->success('Resumen por ciudad obtenido', $data);
    }
    
    /**
     * API: Obtener resumen por corredor de una rueda
     */
    public function getResumenPorCorredor(Request $request, int $ruedaNo): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->ruedaService->obtenerResumenPorCorredor($ruedaNo, $year);
        
        $response = new Response();
        $response->success('Resumen por corredor obtenido', $data);
    }
    
    /**
     * API: Obtener estadísticas de una rueda
     */
    public function getEstadisticas(Request $request, int $ruedaNo): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->ruedaService->obtenerEstadisticasRueda($ruedaNo, $year);
        
        $response = new Response();
        $response->success('Estadísticas de rueda obtenidas', $data);
    }
    
    /**
     * API: Comparar múltiples ruedas
     */
    public function compararRuedas(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $ruedasStr = $request->get('ruedas', '');
        
        if (empty($ruedasStr)) {
            $response = new Response();
            $response->error('Debe especificar las ruedas a comparar', [], 400);
            return;
        }
        
        $ruedas = array_map('intval', explode(',', $ruedasStr));
        
        $data = $this->ruedaService->compararRuedas($ruedas, $year);
        
        $response = new Response();
        $response->success('Comparación de ruedas obtenida', $data);
    }
    
    /**
     * Exportar detalle de rueda a Excel
     */
    public function exportarRueda(Request $request, int $ruedaNo): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->ruedaService->obtenerDetalleRueda($ruedaNo, $year);
        $estadisticas = $this->ruedaService->obtenerEstadisticasRueda($ruedaNo, $year);
        
        // Preparar datos para Excel
        $headers = [
            'Ciudad', 'Corredor', 'Cliente', 'NIT', 'Transado', 'Comisión', 'Margen'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['ciudad'],
                $row['corredor'],
                $row['cliente'],
                $row['nit'],
                $row['transado'],
                $row['comision'],
                $row['margen']
            ];
        }
        
        $excel = new ExcelWriter();
        $excel->setHeaders($headers)
              ->setData($rows)
              ->autoSize()
              ->addBorders()
              ->formatCurrency('E2:G' . (count($rows) + 1));
        
        $filename = "Rueda_{$ruedaNo}_{$year}.xlsx";
        $excel->download($filename);
    }
}