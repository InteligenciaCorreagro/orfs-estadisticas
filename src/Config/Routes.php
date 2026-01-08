<?php

namespace App\Config;

use App\Core\Router;
use App\Core\Session;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

// Controladores
use App\Controllers\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DebugController;
use App\Controllers\Admin\CargaArchivoController;
use App\Controllers\Admin\TraderController;
use App\Controllers\Admin\UsuarioController;
use App\Controllers\Reportes\OrfsController;
use App\Controllers\Reportes\MargenController;
use App\Controllers\Reportes\RuedaController;
use App\Controllers\Reportes\NegociadoDiarioController;
use App\Controllers\Reportes\ConsolidadoController;
use App\Controllers\Trader\MiEstadisticaController;

class Routes
{
    public static function web(): void
    {
        // ==================== RUTAS PÚBLICAS ====================
        
        Router::get('/', function() {
            redirect('/login');
        });
        
        Router::get('/login', [AuthController::class, 'showLogin']);
        Router::post('/login', [AuthController::class, 'login']);
        Router::get('/logout', [AuthController::class, 'logout']);
        Router::post('/logout', [AuthController::class, 'logout']);
        
        // ==================== RUTAS AUTENTICADAS ====================
        
        // Dashboard
        Router::get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

        // RUTA DE TEST
        Router::get('/test', function() {
            Session::start();
            ob_start();
            require __DIR__ . '/../Views/admin/test.php';
            $content = ob_get_clean();
            (new Response())->html($content);
        }, [AuthMiddleware::class]);

        // RUTA DE DEBUG
        Router::get('/debug', [DebugController::class, 'test'], [AuthMiddleware::class]);

        // ==================== RUTAS ADMIN ====================
        
        $adminMiddleware = [AuthMiddleware::class, new RoleMiddleware(['admin'])];
        
        // Carga de archivos
        Router::get('/admin/carga-archivo', [CargaArchivoController::class, 'index'], $adminMiddleware);
        Router::post('/admin/carga-archivo/upload', [CargaArchivoController::class, 'upload'], $adminMiddleware);
        Router::get('/admin/carga-archivo/historial', [CargaArchivoController::class, 'historial'], $adminMiddleware);

        // Traders
        Router::get('/admin/traders', [TraderController::class, 'index'], $adminMiddleware);
        Router::get('/admin/traders/create', [TraderController::class, 'create'], $adminMiddleware);
        Router::post('/admin/traders', [TraderController::class, 'store'], $adminMiddleware);
        Router::get('/admin/traders/:id', [TraderController::class, 'show'], $adminMiddleware);
        Router::get('/admin/traders/:id/edit', [TraderController::class, 'edit'], $adminMiddleware);
        Router::post('/admin/traders/:id', [TraderController::class, 'update'], $adminMiddleware);
        Router::delete('/admin/traders/:id', [TraderController::class, 'delete'], $adminMiddleware);
        
        // Usuarios
        Router::get('/admin/usuarios', [UsuarioController::class, 'index'], $adminMiddleware);
        Router::get('/admin/usuarios/create', [UsuarioController::class, 'create'], $adminMiddleware);
        Router::post('/admin/usuarios', [UsuarioController::class, 'store'], $adminMiddleware);
        Router::post('/admin/usuarios/:id', [UsuarioController::class, 'update'], $adminMiddleware);
        Router::delete('/admin/usuarios/:id', [UsuarioController::class, 'delete'], $adminMiddleware);
        
        // ==================== REPORTES ====================
        
        $reportesMiddleware = [AuthMiddleware::class];
        
        // ORFS
        Router::get('/reportes/orfs', [OrfsController::class, 'index'], $reportesMiddleware);
        Router::get('/reportes/orfs/exportar', [OrfsController::class, 'exportarExcel'], $reportesMiddleware);
        
        // Margen
        Router::get('/reportes/margen', [MargenController::class, 'index'], $reportesMiddleware);
        Router::get('/reportes/margen/exportar', [MargenController::class, 'exportarExcel'], $reportesMiddleware);
        
        // Rueda
        Router::get('/reportes/rueda', [RuedaController::class, 'index'], $reportesMiddleware);
        Router::get('/reportes/rueda/:ruedaNo/exportar', [RuedaController::class, 'exportarRueda'], $reportesMiddleware);
        
        // Negociado Diario
        Router::get('/reportes/negociado-diario', [NegociadoDiarioController::class, 'index'], $reportesMiddleware);
        Router::get('/reportes/negociado-diario/exportar', [NegociadoDiarioController::class, 'exportarExcel'], $reportesMiddleware);
        
        // Consolidado
        Router::get('/reportes/consolidado', [ConsolidadoController::class, 'index'], $reportesMiddleware);
        
        // ==================== TRADER ====================
        
        $traderMiddleware = [AuthMiddleware::class, new RoleMiddleware(['trader'])];
        
        Router::get('/trader/dashboard', [MiEstadisticaController::class, 'dashboard'], $traderMiddleware);
        Router::get('/trader/mis-transacciones', [MiEstadisticaController::class, 'misTransacciones'], $traderMiddleware);
        
        // ==================== PÁGINA NO AUTORIZADA ====================
        
        Router::get('/unauthorized', function() {
            http_response_code(403);
            echo '<h1>403 - No Autorizado</h1>';
            echo '<p>No tienes permisos para acceder a este recurso.</p>';
            echo '<a href="/dashboard">Volver al Dashboard</a>';
        });
    }
    
    public static function api(): void
    {
        // ==================== AUTH API ====================
        
        Router::post('/api/auth/login', [AuthController::class, 'login']);
        Router::post('/api/auth/logout', [AuthController::class, 'logout']);
        Router::get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
        Router::post('/api/auth/change-password', [AuthController::class, 'changePassword'], [AuthMiddleware::class]);
        
        // ==================== DASHBOARD API ====================
        
        Router::get('/api/dashboard', [DashboardController::class, 'getDashboardData'], [AuthMiddleware::class]);
        
        // ==================== ADMIN API ====================
        
        $adminMiddleware = [AuthMiddleware::class, new RoleMiddleware(['admin'])];
        
        // Traders
        Router::get('/api/admin/traders', [TraderController::class, 'index'], $adminMiddleware);
        Router::get('/api/admin/traders/activos', [TraderController::class, 'activos'], $adminMiddleware);
        Router::post('/api/admin/traders', [TraderController::class, 'store'], $adminMiddleware);
        Router::get('/api/admin/traders/:id', [TraderController::class, 'show'], $adminMiddleware);
        Router::put('/api/admin/traders/:id', [TraderController::class, 'update'], $adminMiddleware);
        Router::delete('/api/admin/traders/:id', [TraderController::class, 'delete'], $adminMiddleware);
        
        // Usuarios
        Router::get('/api/admin/usuarios', [UsuarioController::class, 'index'], $adminMiddleware);
        Router::post('/api/admin/usuarios', [UsuarioController::class, 'store'], $adminMiddleware);
        Router::put('/api/admin/usuarios/:id', [UsuarioController::class, 'update'], $adminMiddleware);
        Router::delete('/api/admin/usuarios/:id', [UsuarioController::class, 'delete'], $adminMiddleware);
        
        // Carga de archivos
        Router::post('/api/admin/carga-archivo', [CargaArchivoController::class, 'upload'], $adminMiddleware);
        Router::get('/api/admin/carga-archivo/historial', [CargaArchivoController::class, 'historial'], $adminMiddleware);
        
        // ==================== REPORTES API ====================
        
        $reportesMiddleware = [AuthMiddleware::class];
        
        // ORFS
        Router::get('/api/reportes/orfs', [OrfsController::class, 'getData'], $reportesMiddleware);
        Router::get('/api/reportes/orfs/totales-corredor', [OrfsController::class, 'getTotalesPorCorredor'], $reportesMiddleware);
        Router::get('/api/reportes/orfs/resumen-mes', [OrfsController::class, 'getResumenPorMes'], $reportesMiddleware);
        Router::get('/api/reportes/orfs/estadisticas', [OrfsController::class, 'getEstadisticas'], $reportesMiddleware);
        Router::get('/api/reportes/orfs/comparar-año', [OrfsController::class, 'compararAñoAnterior'], $reportesMiddleware);
        
        // Margen
        Router::get('/api/reportes/margen', [MargenController::class, 'getData'], $reportesMiddleware);
        Router::get('/api/reportes/margen/top-corredores', [MargenController::class, 'getTopCorredores'], $reportesMiddleware);
        Router::get('/api/reportes/margen/rentabilidad', [MargenController::class, 'getRentabilidadPorCliente'], $reportesMiddleware);
        
        // Rueda
        Router::get('/api/reportes/rueda/listado', [RuedaController::class, 'getRuedasDelAño'], $reportesMiddleware);
        Router::get('/api/reportes/rueda/:ruedaNo', [RuedaController::class, 'getDetalleRueda'], $reportesMiddleware);
        Router::get('/api/reportes/rueda/:ruedaNo/ciudad', [RuedaController::class, 'getResumenPorCiudad'], $reportesMiddleware);
        Router::get('/api/reportes/rueda/:ruedaNo/corredor', [RuedaController::class, 'getResumenPorCorredor'], $reportesMiddleware);
        Router::get('/api/reportes/rueda/:ruedaNo/estadisticas', [RuedaController::class, 'getEstadisticas'], $reportesMiddleware);
        Router::get('/api/reportes/rueda/comparar', [RuedaController::class, 'compararRuedas'], $reportesMiddleware);
        
        // Negociado Diario
        Router::get('/api/reportes/negociado-diario', [NegociadoDiarioController::class, 'getData'], $reportesMiddleware);
        Router::get('/api/reportes/negociado-diario/resumen', [NegociadoDiarioController::class, 'getResumenDiario'], $reportesMiddleware);
        Router::get('/api/reportes/negociado-diario/clientes-activos', [NegociadoDiarioController::class, 'getClientesMasActivos'], $reportesMiddleware);
        Router::get('/api/reportes/negociado-diario/traders', [NegociadoDiarioController::class, 'getResumenPorTrader'], $reportesMiddleware);
        Router::get('/api/reportes/negociado-diario/trader/:trader/detalle', [NegociadoDiarioController::class, 'getDetalleMensualTrader'], $reportesMiddleware);
        Router::get('/api/reportes/negociado-diario/matricial', [NegociadoDiarioController::class, 'getVistaMatricial'], $reportesMiddleware);

        // Consolidado
        Router::get('/api/reportes/consolidado', [ConsolidadoController::class, 'getDashboard'], $reportesMiddleware);
        Router::get('/api/reportes/consolidado/resumen-ejecutivo', [ConsolidadoController::class, 'getResumenEjecutivo'], $reportesMiddleware);
        
        // ==================== TRADER API ====================
        
        $traderMiddleware = [AuthMiddleware::class, new RoleMiddleware(['trader'])];
        
        Router::get('/api/trader/estadisticas', [MiEstadisticaController::class, 'getEstadisticas'], $traderMiddleware);
        Router::get('/api/trader/clientes', [MiEstadisticaController::class, 'getMisClientes'], $traderMiddleware);
        Router::get('/api/trader/rentabilidad', [MiEstadisticaController::class, 'getMiRentabilidad'], $traderMiddleware);
    }
}