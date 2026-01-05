<?php
// src/Controllers/Reportes/ConsolidadoController.php

namespace App\Controllers\Reportes;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\ConsolidadoService;

class ConsolidadoController
{
    private ConsolidadoService $consolidadoService;
    
    public function __construct()
    {
        $this->consolidadoService = new ConsolidadoService();
    }
    
    /**
     * Mostrar página de reporte consolidado
     */
    public function index(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        
        $year = $request->get('year', date('Y'));
        
        ob_start();
        require __DIR__ . '/../../Views/reportes/consolidado.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener dashboard consolidado
     */
    public function getDashboard(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->consolidadoService->obtenerDashboardConsolidado($year);
        
        $response = new Response();
        $response->success('Dashboard consolidado obtenido', $data);
    }
    
    /**
     * API: Obtener resumen ejecutivo
     */
    public function getResumenEjecutivo(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->consolidadoService->obtenerResumenEjecutivo($year);
        
        $response = new Response();
        $response->success('Resumen ejecutivo obtenido', $data);
    }
}