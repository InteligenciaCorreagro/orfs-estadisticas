<?php
// modules/benchmark/benchmark.routes.php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\BusinessIntelligence\BenchmarkController;

if (!class_exists(App\Controllers\BusinessIntelligence\BenchmarkController::class)) {
    require_once __DIR__ . '/controllers/BenchmarkController.php';
}

if (!class_exists(App\Services\BusinessIntelligence\BMCApiClient::class)) {
    require_once __DIR__ . '/services/BMCApiClient.php';
}

$biMiddleware = [AuthMiddleware::class, new RoleMiddleware(['business_intelligence', 'admin'])];

Router::get('/bi/benchmark', [BenchmarkController::class, 'dashboard'], $biMiddleware);
Router::get('/bi/benchmark/comparativa', [BenchmarkController::class, 'comparativa'], $biMiddleware);
Router::get('/bi/benchmark/sectores', [BenchmarkController::class, 'sectores'], $biMiddleware);
Router::get('/bi/benchmark/temporal', [BenchmarkController::class, 'temporal'], $biMiddleware);
Router::get('/bi/benchmark/reportes', [BenchmarkController::class, 'reportes'], $biMiddleware);

// Internal API proxy
Router::get('/api/bi/benchmark/summary', [BenchmarkController::class, 'summary'], $biMiddleware);
Router::get('/api/bi/benchmark/reports', [BenchmarkController::class, 'reports'], $biMiddleware);
Router::get('/api/bi/benchmark/report', [BenchmarkController::class, 'report'], $biMiddleware);
Router::get('/api/bi/benchmark/compare', [BenchmarkController::class, 'compare'], $biMiddleware);
Router::get('/api/bi/benchmark/trends/scb', [BenchmarkController::class, 'trendsScb'], $biMiddleware);
Router::get('/api/bi/benchmark/trends/sectores', [BenchmarkController::class, 'trendsSectores'], $biMiddleware);
Router::post('/api/bi/benchmark/analyze', [BenchmarkController::class, 'analyze'], $biMiddleware);

Router::get('/api/bi/benchmark/export/csv', [BenchmarkController::class, 'exportCsv'], $biMiddleware);
Router::get('/api/bi/benchmark/export/pdf', [BenchmarkController::class, 'exportPdf'], $biMiddleware);
Router::get('/api/bi/benchmark/export/excel', [BenchmarkController::class, 'exportExcel'], $biMiddleware);
