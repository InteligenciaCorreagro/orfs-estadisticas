<?php
// modules/benchmark/views/sectores.php

$pageTitle = 'Benchmark - Sectores';
$pageSubtitle = 'Correagro vs competidores por sector';
$currentPage = 'sectores';

$defaultYear = $defaultYear ?? currentYear();
$availableYears = $availableYears ?? [];
$user = $user ?? auth();

$benchmarkConfig = [
    'page' => 'sectores',
    'apiBase' => '/api/bi/benchmark',
    'defaultYear' => $defaultYear,
    'correagroName' => 'Correagro S.A.',
    'userName' => $user['name'] ?? ''
];

require __DIR__ . '/partials/benchmark_assets.php';
ob_start();
?>

<div id="benchmark-app" class="bmc-page" data-benchmark-page="sectores">
    <?php require __DIR__ . '/partials/benchmark_nav.php'; ?>

    <div class="card mb-4">
        <div class="card-body bmc-filters">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ano</label>
                    <select id="bmc-sector-year" class="form-select">
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                <?= e((string) $year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filtro</label>
                    <select id="bmc-sector-filter" class="form-select">
                        <option value="all" selected>Todos</option>
                        <option value="lider">Lider</option>
                        <option value="oportunidad">Oportunidad</option>
                        <option value="rezago">Rezago</option>
                    </select>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-light text-dark">Matriz BCG: Share vs Crecimiento</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Mapa BCG por sector</h5>
                </div>
                <div class="card-body">
                    <canvas id="bmc-bcg-chart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Resumen de oportunidades</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="bmc-mini-kpi">
                                <div class="text-muted small">Lider</div>
                                <div class="h5 mb-0" id="bmc-sector-leader">--</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bmc-mini-kpi">
                                <div class="text-muted small">Oportunidad</div>
                                <div class="h5 mb-0" id="bmc-sector-opportunity">--</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bmc-mini-kpi">
                                <div class="text-muted small">Rezago</div>
                                <div class="h5 mb-0" id="bmc-sector-lag">--</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <ul class="list-group list-group-flush small" id="bmc-sector-recommendations">
                            <li class="list-group-item text-muted">Sin datos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card position-relative mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Detalle por sector</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="bmc-sectores-table" class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Sector</th>
                            <th>Correagro Share</th>
                            <th>Top competidor</th>
                            <th>Estado</th>
                            <th>Recomendacion</th>
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



