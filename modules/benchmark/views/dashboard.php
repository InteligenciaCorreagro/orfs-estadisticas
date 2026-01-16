<?php
// modules/benchmark/views/dashboard.php

$pageTitle = 'Benchmark - Dashboard';
$pageSubtitle = 'Ranking competitivo y foco en Correagro S.A.';
$currentPage = 'dashboard';

$defaultYear = $defaultYear ?? currentYear();
$availableYears = $availableYears ?? [];

$user = $user ?? auth();

$benchmarkConfig = [
    'page' => 'dashboard',
    'apiBase' => '/api/bi/benchmark',
    'defaultYear' => $defaultYear,
    'correagroName' => 'Correagro S.A.',
    'userName' => $user['name'] ?? ''
];

require __DIR__ . '/partials/benchmark_assets.php';
ob_start();
?>

<div id="benchmark-app" class="bmc-page" data-benchmark-page="dashboard">
    <?php require __DIR__ . '/partials/benchmark_nav.php'; ?>

    <div class="card mb-4">
        <div class="card-body bmc-filters">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ano</label>
                    <select id="bmc-year" class="form-select">
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                <?= e((string) $year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Periodo (meses)</label>
                    <select id="bmc-period" class="form-select">
                        <option value="3">3 meses</option>
                        <option value="6" selected>6 meses</option>
                        <option value="12">12 meses</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Top ranking</label>
                    <select id="bmc-limit" class="form-select">
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="bmc-inline-help">
                        <span class="badge bg-light text-dark">Comision siempre en COP</span>
                        <span class="badge bg-light text-dark">Margen = comision / negociado</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bmc-kpi">
                <div class="card-body">
                    <div class="text-muted small">Volumen total (MM)</div>
                    <div class="h4 mb-0" data-kpi="total-volume">--</div>
                    <div class="small text-muted">Millones COP</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bmc-kpi">
                <div class="card-body">
                    <div class="text-muted small">Comision total</div>
                    <div class="h4 mb-0" data-kpi="total-commission">--</div>
                    <div class="small text-muted">COP</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bmc-kpi">
                <div class="card-body">
                    <div class="text-muted small">Margen promedio</div>
                    <div class="h4 mb-0" data-kpi="avg-margin">--</div>
                    <div class="small text-muted">%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bmc-kpi">
                <div class="card-body">
                    <div class="text-muted small">SCB activos</div>
                    <div class="h4 mb-0" data-kpi="total-scbs">--</div>
                    <div class="small text-muted">Participantes</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Ranking SCB</h5>
                        <span class="text-muted small">Tendencia 3/6/12 con sparklines</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bmc-ranking-table" class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th>SCB</th>
                                    <th>Cambio</th>
                                    <th>Participacion</th>
                                    <th>Volumen (MM)</th>
                                    <th>Crecimiento</th>
                                    <th>Tendencia</th>
                                    <th>Semaforo</th>
                                    <th>Comision</th>
                                    <th>Margen</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card bmc-correagro position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0 bmc-highlight">Correagro S.A.</h5>
                </div>
                <div class="card-body">
                    <div class="bmc-kpi-stack">
                        <div>
                            <div class="text-muted small">Posicion actual</div>
                            <div class="h3 mb-0" id="bmc-correagro-position">--</div>
                        </div>
                        <div>
                            <div class="text-muted small">Cuota</div>
                            <div class="h5 mb-0" id="bmc-correagro-share">--</div>
                        </div>
                    </div>

                    <div class="bmc-gap mt-3">
                        <div class="bmc-gap-item">
                            <span class="text-muted small">Gap vs #1</span>
                            <strong id="bmc-gap-1">--</strong>
                        </div>
                        <div class="bmc-gap-item">
                            <span class="text-muted small">Gap vs #2</span>
                            <strong id="bmc-gap-2">--</strong>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="text-muted small">Proyeccion para subir puesto</div>
                        <div class="h6 mb-0" id="bmc-correagro-projection">--</div>
                    </div>

                    <div class="mt-4">
                        <div class="text-muted small mb-2">Evolucion reciente</div>
                        <canvas id="bmc-correagro-trend" height="120"></canvas>
                    </div>

                    <div class="mt-4">
                        <div class="text-muted small mb-2">Sectores mas fuertes</div>
                        <ul class="list-group list-group-flush" id="bmc-correagro-sectors">
                            <li class="list-group-item text-muted">Sin datos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../src/Views/layouts/app.php';
?>



