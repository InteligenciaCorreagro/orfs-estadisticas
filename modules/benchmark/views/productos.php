<?php
// modules/benchmark/views/productos.php

$pageTitle = 'Benchmark - Productos';
$pageSubtitle = 'Ranking por producto, mes a mes y general';
$currentPage = 'productos';

$defaultYear = $defaultYear ?? currentYear();
$availableYears = $availableYears ?? [];
$user = $user ?? auth();

$benchmarkConfig = [
    'page' => 'productos',
    'apiBase' => '/api/bi/benchmark',
    'defaultYear' => $defaultYear,
    'correagroName' => 'Correagro S.A.',
    'userName' => $user['name'] ?? ''
];

require __DIR__ . '/partials/benchmark_assets.php';
ob_start();
?>

<div id="benchmark-app" class="bmc-page" data-benchmark-page="productos">
    <?php require __DIR__ . '/partials/benchmark_nav.php'; ?>

    <div class="card mb-4">
        <div class="card-header bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Visualizaciones</h5>
                <span class="text-muted small">Comparativo rapido por producto</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo de grafica</label>
                    <select id="bmc-product-chart-type" class="form-select">
                        <option value="bar" selected>Barras</option>
                        <option value="line">Lineas</option>
                        <option value="doughnut">Dona</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Metrica</label>
                    <select id="bmc-product-chart-metric" class="form-select">
                        <option value="monto" selected>Volumen (MM)</option>
                        <option value="participacion">Participacion (%)</option>
                        <option value="variacion">Variacion (%)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fuente</label>
                    <select id="bmc-product-chart-source" class="form-select">
                        <option value="general" selected>Ranking general</option>
                        <option value="period">Periodo seleccionado</option>
                        <option value="monthly">Mes a mes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Top productos</label>
                    <select id="bmc-product-chart-top" class="form-select">
                        <option value="6">6</option>
                        <option value="8" selected>8</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                    </select>
                </div>
            </div>
            <div class="bmc-chart-card">
                <canvas id="bmc-products-chart" height="180"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body bmc-filters">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ano</label>
                    <select id="bmc-product-year" class="form-select">
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                <?= e((string) $year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Periodo (mes)</label>
                    <select id="bmc-product-period" class="form-select">
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Top</label>
                    <select id="bmc-product-limit" class="form-select">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="bmc-inline-help">
                        <span class="badge bg-light text-dark">Variacion con flechas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Ranking general por producto</h5>
                        <span class="text-muted small">Acumulado anual</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bmc-products-general" class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Sector</th>
                                    <th>Volumen (MM)</th>
                                    <th>Participacion</th>
                                    <th>Variacion</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Ranking por producto (mes)</h5>
                        <span class="text-muted small">Periodo seleccionado</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bmc-products-period" class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Sector</th>
                                    <th>Volumen (MM)</th>
                                    <th>Participacion</th>
                                    <th>Variacion</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card position-relative">
        <div class="card-header bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Ranking mes a mes</h5>
                <span class="text-muted small">Top por periodo</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="bmc-products-monthly" class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Producto</th>
                            <th>Sector</th>
                            <th>Volumen (MM)</th>
                            <th>Participacion</th>
                            <th>Variacion</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../src/Views/layouts/app.php';
?>
