<?php

namespace App\Controllers\Reportes;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Reportes\NegociadoDiarioService;
use App\Services\Excel\ExcelWriter;
use App\Models\Trader;

class NegociadoDiarioController
{
    private NegociadoDiarioService $negociadoService;
    
    public function __construct()
    {
        $this->negociadoService = new NegociadoDiarioService();
    }
    
    /**
     * Mostrar página de negociados diarios
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
        require __DIR__ . '/../../Views/reportes/negociado-diario.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * API: Obtener negociados por cliente con cada rueda
     */
    public function getData(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $userRole = Session::get('user_role');
        $traderName = Session::get('trader_name');
        
        $corredor = $userRole === 'trader' ? $traderName : $request->get('corredor');
        
        $data = $this->negociadoService->obtenerNegociadosPorCliente($year, $corredor);
        
        $response = new Response();
        $response->success('Negociados diarios obtenidos', $data);
    }
    
    /**
     * API: Obtener resumen de actividad diaria
     */
    public function getResumenDiario(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        
        $data = $this->negociadoService->obtenerResumenDiario($year);
        
        $response = new Response();
        $response->success('Resumen diario obtenido', $data);
    }
    
    /**
     * API: Obtener clientes más activos
     */
    public function getClientesMasActivos(Request $request): void
    {
        $year = (int) $request->get('year', date('Y'));
        $limit = (int) $request->get('limit', 20);
        
        $data = $this->negociadoService->obtenerClientesMasActivos($year, $limit);
        
        $response = new Response();
        $response->success('Clientes más activos obtenidos', $data);
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
        
        $resultado = $this->negociadoService->obtenerNegociadosPorCliente($year, $corredor);
        
        $ruedas = $resultado['ruedas'];
        $data = $resultado['data'];
        
        // Preparar headers dinámicos
        $headers = ['NIT', 'Cliente', 'Corredor'];
        foreach ($ruedas as $rueda) {
            $headers[] = "Rueda {$rueda['rueda_no']}";
        }
        $headers[] = 'Total';
        
        // Preparar datos
        $rows = [];
        foreach ($data as $row) {
            $rowData = [
                $row['nit'],
                $row['cliente'],
                $row['corredor']
            ];
            
            foreach ($ruedas as $rueda) {
                $key = 'rueda_' . $rueda['rueda_no'];
                $rowData[] = $row[$key] ?? 0;
            }
            
            $rowData[] = $row['total'];
            $rows[] = $rowData;
        }
        
        $excel = new ExcelWriter();
        $excel->setHeaders($headers)
              ->setData($rows)
              ->autoSize()
              ->addBorders()
              ->formatCurrency('D2:' . chr(67 + count($ruedas)) . (count($rows) + 1));
        
        $filename = "Negociados_Diarios_{$year}" . ($corredor ? "_{$corredor}" : "") . ".xlsx";
        $excel->download($filename);
    }
}