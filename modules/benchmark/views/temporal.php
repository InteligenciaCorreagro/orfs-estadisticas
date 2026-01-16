<?php
// modules/benchmark/views/temporal.php

$pageTitle = 'Benchmark - Analisis Temporal';
$pageSubtitle = 'Heatmap, volatilidad y prediccion simple';
$currentPage = 'temporal';

$defaultYear = $defaultYear ?? currentYear();
$availableYears = $availableYears ?? [];
$user = $user ?? auth();

$benchmarkConfig = [
    'page' => 'temporal',
    'apiBase' => '/api/bi/benchmark',
    'defaultYear' => $defaultYear,
    'correagroName' => 'Correagro S.A.',
    'userName' => $user['name'] ?? ''
];

require __DIR__ . '/partials/benchmark_assets.php';
ob_start();
?>

<div id="benchmark-app" class="bmc-page" data-benchmark-page="temporal">
    <?php require __DIR__ . '/partials/benchmark_nav.php'; ?>

    <div class="card mb-4">
        <div class="card-body bmc-filters">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ano</label>
                    <select id="bmc-temporal-year" class="form-select">
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                <?= e((string) $year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">SCB foco</label>
                    <select id="bmc-temporal-scb" class="form-select"></select>
                </div>
                <div class="col-md-5 text-end">
                    <span class="badge bg-light text-dark">Posiciones ultimos 12 meses</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card position-relative mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Heatmap de posiciones</h5>
        </div>
        <div class="card-body">
            <div id="bmc-heatmap" class="bmc-heatmap"></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Volatilidad</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bmc-volatility-table" class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>SCB</th>
                                    <th>Volatilidad</th>
                                    <th>Tendencia</th>
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
                    <h5 class="mb-0">Estacionalidad</h5>
                </div>
                <div class="card-body">
                    <canvas id="bmc-seasonality" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card position-relative mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Prediccion simple</h5>
        </div>
        <div class="card-body">
            <canvas id="bmc-forecast" height="140"></canvas>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../src/Views/layouts/app.php';
?>



