<?php
// src/Controllers/Admin/DashboardController.php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\ConsolidadoService;

class DashboardController
{
    private ConsolidadoService $consolidadoService;
    
    public function __construct()
    {
        $this->consolidadoService = new ConsolidadoService();
    }
    
    /**
     * Mostrar dashboard principal
     */
    public function index(Request $request): void
    {
        Session::start();
        
        $year = $request->get('year', date('Y'));
        
        $dashboard = $this->consolidadoService->obtenerDashboardConsolidado($year);
        
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        
        ob_start();
        require __DIR__ . '/../../Views/admin/dashboard.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener datos del dashboard
     */
    public function getDashboardData(Request $request): void
    {
        $year = $request->get('year', date('Y'));
        
        $dashboard = $this->consolidadoService->obtenerDashboardConsolidado($year);
        
        $response = new Response();
        $response->success('Dashboard obtenido correctamente', $dashboard);
    }
}