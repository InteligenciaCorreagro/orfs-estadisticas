<?php
// src/Controllers/Admin/DashboardController.php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\DashboardLayoutService;
use App\Services\Reportes\ConsolidadoService;

class DashboardController
{
    private ConsolidadoService $consolidadoService;
    private DashboardLayoutService $layoutService;
    
    public function __construct()
    {
        $this->consolidadoService = new ConsolidadoService();
        $this->layoutService = new DashboardLayoutService();
    }
    
    /**
     * Mostrar dashboard principal
     */
    public function index(Request $request): void
    {
        Session::start();

        $year = $request->get('year', date('Y'));

        $dashboard = $this->consolidadoService->obtenerDashboardConsolidado($year);
        $userId = (int) Session::get('user_id');
        $layout = $this->layoutService->getLayout($userId);
        $defaultLayout = $this->layoutService->getDefaultLayout();

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

    /**
     * API: Obtener layout del dashboard
     */
    public function getLayout(Request $request): void
    {
        Session::start();
        $userId = (int) Session::get('user_id');
        if ($userId <= 0) {
            (new Response())->error('No autorizado', [], 401);
        }

        $layout = $this->layoutService->getLayout($userId);
        (new Response())->success('Layout obtenido correctamente', $layout);
    }

    /**
     * API: Guardar layout del dashboard
     */
    public function saveLayout(Request $request): void
    {
        Session::start();
        $userId = (int) Session::get('user_id');
        if ($userId <= 0) {
            (new Response())->error('No autorizado', [], 401);
        }

        $layout = $request->post('layout', []);
        if (!is_array($layout)) {
            (new Response())->error('Layout invalido', [], 422);
        }

        $this->layoutService->saveLayout($userId, $layout);
        (new Response())->success('Layout guardado correctamente');
    }
}
