<?php
// src/Controllers/Trader/MiEstadisticaController.php

namespace App\Controllers\Trader;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\OrfsReporteService;
use App\Services\Reportes\MargenReporteService;
use App\Services\Reportes\RuedaReporteService;
use App\Services\Reportes\NegociadoDiarioService;

class MiEstadisticaController
{
    private OrfsReporteService $orfsService;
    private MargenReporteService $margenService;
    private RuedaReporteService $ruedaService;
    private NegociadoDiarioService $negociadoService;
    
    public function __construct()
    {
        $this->orfsService = new OrfsReporteService();
        $this->margenService = new MargenReporteService();
        $this->ruedaService = new RuedaReporteService();
        $this->negociadoService = new NegociadoDiarioService();
    }
    
    /**
     * Dashboard personal del trader
     */
    public function dashboard(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $traderName = Session::get('trader_name');
        
        $year = $request->get('year', date('Y'));
        
        // Obtener estadísticas personales
        $estadisticas = $this->orfsService->obtenerEstadisticasGenerales($year, $traderName);
        $resumenMensual = $this->orfsService->obtenerResumenPorMes($year, $traderName);
        $comparacion = $this->orfsService->compararConAñoAnterior($year, $traderName);
        
        ob_start();
        require __DIR__ . '/../../Views/trader/dashboard.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * Mis transacciones
     */
    public function misTransacciones(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $traderName = Session::get('trader_name');
        
        $year = $request->get('year', date('Y'));
        
        ob_start();
        require __DIR__ . '/../../Views/trader/mis-transacciones.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener mis estadísticas
     */
    public function getEstadisticas(Request $request): void
    {
        $traderName = Session::get('trader_name');
        $year = (int) $request->get('year', date('Y'));
        
        $estadisticas = $this->orfsService->obtenerEstadisticasGenerales($year, $traderName);
        $resumenMensual = $this->orfsService->obtenerResumenPorMes($year, $traderName);
        $comparacion = $this->orfsService->compararConAñoAnterior($year, $traderName);
        
        $data = [
            'estadisticas' => $estadisticas,
            'resumen_mensual' => $resumenMensual,
            'comparacion_anual' => $comparacion
        ];
        
        $response = new Response();
        $response->success('Estadísticas obtenidas', $data);
    }
    
    /**
     * API: Obtener mis clientes
     */
    public function getMisClientes(Request $request): void
    {
        $traderName = Session::get('trader_name');
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->orfsService->obtenerReporteOrfs($year, $traderName);
        
        $response = new Response();
        $response->success('Clientes obtenidos', $data);
    }
    
    /**
     * API: Obtener mi rentabilidad
     */
    public function getMiRentabilidad(Request $request): void
    {
        $traderName = Session::get('trader_name');
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->margenService->obtenerRentabilidadPorCliente($year, $traderName);
        
        $response = new Response();
        $response->success('Rentabilidad obtenida', $data);
    }
}