<?php
// modules/benchmark/views/comparativa.php

$pageTitle = 'Benchmark - Comparativa';
$pageSubtitle = 'Comparacion competitiva entre SCB';
$currentPage = 'comparativa';

$defaultYear = $defaultYear ?? currentYear();
$availableYears = $availableYears ?? [];
$user = $user ?? auth();

$benchmarkConfig = [
    'page' => 'comparativa',
    'apiBase' => '/api/bi/benchmark',
    'defaultYear' => $defaultYear,
    'correagroName' => 'Correagro S.A.',
    'userName' => $user['name'] ?? ''
];

require __DIR__ . '/partials/benchmark_assets.php';
ob_start();
?>

<div id="benchmark-app" class="bmc-page" data-benchmark-page="comparativa">
    <?php require __DIR__ . '/partials/benchmark_nav.php'; ?>

    <div class="card mb-4">
        <div class="card-body bmc-filters">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">SCB a comparar</label>
                    <select id="bmc-compare-scbs" class="form-select" multiple></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Periodo</label>
                    <select id="bmc-compare-period" class="form-select">
                        <option value="6">Ultimos 6 meses</option>
                        <option value="12" selected>Ultimos 12 meses</option>
                        <option value="24">Ultimos 24 meses</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ventana crecimiento</label>
                    <select id="bmc-compare-window" class="form-select">
                        <option value="3">3 meses</option>
                        <option value="6" selected>6 meses</option>
                        <option value="12">12 meses</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ano</label>
                    <select id="bmc-compare-year" class="form-select">
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                <?= e((string) $year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100 bmc-compare-run">
                        <i class="fas fa-play me-1"></i> Ejecutar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card position-relative mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Market Share</h5>
                </div>
                <div class="card-body">
                    <canvas id="bmc-compare-share" height="140"></canvas>
                </div>
            </div>

            <div class="card position-relative mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Volumen mensual</h5>
                </div>
                <div class="card-body">
                    <canvas id="bmc-compare-volume" height="140"></canvas>
                </div>
            </div>

            <div class="card position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Crecimiento</h5>
                </div>
                <div class="card-body">
                    <canvas id="bmc-compare-growth" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card position-relative mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Brechas competitivas</h5>
                </div>
                <div class="card-body">
                    <div class="bmc-gap-card">
                        <div class="text-muted small">Cuanto falta para superar</div>
                        <div class="h6 mb-0" id="bmc-gap-competitor">--</div>
                        <div class="h4" id="bmc-gap-amount">--</div>
                    </div>
                    <hr>
                    <div class="bmc-gap-card">
                        <div class="text-muted small">Meses para alcanzar</div>
                        <div class="h6 mb-0" id="bmc-gap-target">--</div>
                        <div class="h4" id="bmc-gap-months">--</div>
                    </div>
                </div>
            </div>

            <div class="card position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Notas</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small text-muted" id="bmc-compare-notes">
                        <li class="list-group-item">Seleccione SCB para ver comparativa.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../src/Views/layouts/app.php';
?>



