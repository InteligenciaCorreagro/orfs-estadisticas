<?php
// modules/benchmark/views/reportes.php

$pageTitle = 'Benchmark - Reportes';
$pageSubtitle = 'Export ejecutivo y analisis de archivos';
$currentPage = 'reportes';

$defaultYear = $defaultYear ?? currentYear();
$availableYears = $availableYears ?? [];
$user = $user ?? auth();

$benchmarkConfig = [
    'page' => 'reportes',
    'apiBase' => '/api/bi/benchmark',
    'defaultYear' => $defaultYear,
    'correagroName' => 'Correagro S.A.',
    'userName' => $user['name'] ?? ''
];

require __DIR__ . '/partials/benchmark_assets.php';
ob_start();
?>

<div id="benchmark-app" class="bmc-page" data-benchmark-page="reportes">
    <?php require __DIR__ . '/partials/benchmark_nav.php'; ?>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card position-relative mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Exportar reportes</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Ano</label>
                            <select id="bmc-report-year" class="form-select">
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                        <?= e((string) $year) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-outline-primary" id="bmc-export-csv">
                                    <i class="fas fa-file-csv me-1"></i> CSV
                                </button>
                                <button class="btn btn-outline-secondary" id="bmc-export-pdf">
                                    <i class="fas fa-file-pdf me-1"></i> PDF (stub)
                                </button>
                                <button class="btn btn-outline-success" id="bmc-export-excel">
                                    <i class="fas fa-file-excel me-1"></i> Excel (stub)
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="text-muted small mb-2">Resumen ejecutivo</div>
                        <ul class="list-group list-group-flush" id="bmc-exec-summary">
                            <li class="list-group-item text-muted">Cargando resumen...</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card position-relative">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Analizar archivo</h5>
                </div>
                <div class="card-body">
                    <form id="bmc-analyze-form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Archivo</label>
                            <input type="file" class="form-control" name="file" id="bmc-analyze-file" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" name="usuario" id="bmc-analyze-user"
                                   value="<?= e($user['name'] ?? '') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-1"></i> Enviar a analisis
                        </button>
                        <div id="bmc-analyze-progress" class="bmc-upload-progress d-none" aria-live="polite">
                            <div class="bmc-progress-meta">
                                <span class="bmc-progress-label">Subiendo archivo...</span>
                                <span class="bmc-progress-value">0%</span>
                            </div>
                            <div class="bmc-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div class="bmc-progress-bar"></div>
                            </div>
                        </div>
                    </form>
                    <div class="mt-3">
                        <pre class="bmc-pre" id="bmc-analyze-result">Resultado pendiente...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-12">
        <div class="card position-relative">
            <div class="card-header bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Analitica PDF (acumulado vs mes)</h5>
                    <span class="text-muted small">Datos desde el microservicio de analisis</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Ano</label>
                        <select id="bmc-analysis-year" class="form-select">
                            <?php foreach ($availableYears as $year): ?>
                                <option value="<?= e((string) $year) ?>" <?= (int) $year === (int) $defaultYear ? 'selected' : '' ?>>
                                    <?= e((string) $year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Periodo (reporte)</label>
                        <select id="bmc-analysis-report" class="form-select">
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">SCB clave (comparacion)</label>
                        <select id="bmc-analysis-scbs" class="form-select" multiple></select>
                    </div>
                </div>
                <div class="bmc-inline-help mb-3">
                    <span class="badge bg-light text-dark">Acumulado vs Mes</span>
                    <span class="badge bg-light text-dark">Crecimiento y competencia</span>
                </div>

                <div class="row g-4">
                    <div class="col-xl-4">
                        <div class="bmc-chart-card">
                            <div class="text-muted small mb-2">Top 5 SCB acumulado</div>
                            <canvas id="bmc-chart-scb-acum" height="160"></canvas>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="bmc-chart-card">
                            <div class="text-muted small mb-2">Top 5 SCB mes</div>
                            <canvas id="bmc-chart-scb-mes" height="160"></canvas>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="bmc-chart-card">
                            <div class="text-muted small mb-2">Top 5 sectores acumulado</div>
                            <canvas id="bmc-chart-sector-acum" height="160"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-xl-6">
                        <div class="bmc-chart-card">
                            <div class="text-muted small mb-2">Participacion top 5 SCB</div>
                            <canvas id="bmc-chart-scb-share" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="bmc-chart-card">
                            <div class="text-muted small mb-2">Comparativo SCB clave</div>
                            <canvas id="bmc-chart-scb-compare" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-xl-12">
                        <div class="bmc-chart-card">
                            <div class="text-muted small mb-2">Top 5 sectores mes</div>
                            <canvas id="bmc-chart-sector-mes" height="160"></canvas>
                        </div>
                    </div>
                </div>

                <div class="bmc-divider"></div>

                <div class="row g-4">
                    <div class="col-xl-6">
                        <h6 class="bmc-section-title">Crecimiento SCB (acumulado)</h6>
                        <div class="table-responsive">
                            <table id="bmc-growth-scb-acum" class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>SCB</th>
                                        <th>Actual (MM)</th>
                                        <th>Anterior (MM)</th>
                                        <th>Variacion %</th>
                                        <th>Variacion abs</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <h6 class="bmc-section-title">Crecimiento Sector (acumulado)</h6>
                        <div class="table-responsive">
                            <table id="bmc-growth-sector-acum" class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Sector</th>
                                        <th>Actual (MM)</th>
                                        <th>Anterior (MM)</th>
                                        <th>Variacion %</th>
                                        <th>Variacion abs</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-xl-6">
                        <h6 class="bmc-section-title">Crecimiento SCB (mes)</h6>
                        <div class="table-responsive">
                            <table id="bmc-growth-scb-mes" class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>SCB</th>
                                        <th>Actual (MM)</th>
                                        <th>Anterior (MM)</th>
                                        <th>Variacion %</th>
                                        <th>Variacion abs</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <h6 class="bmc-section-title">Crecimiento Sector (mes)</h6>
                        <div class="table-responsive">
                            <table id="bmc-growth-sector-mes" class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Sector</th>
                                        <th>Actual (MM)</th>
                                        <th>Anterior (MM)</th>
                                        <th>Variacion %</th>
                                        <th>Variacion abs</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bmc-divider"></div>

                <details class="bmc-collapse" open>
                    <summary>Rankings SCB (acumulado y mes)</summary>
                    <div class="row g-4 mt-1">
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-scb-acum-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>SCB</th>
                                            <th>Actual (MM)</th>
                                            <th>Participacion</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-scb-mes-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>SCB</th>
                                            <th>Actual (MM)</th>
                                            <th>Participacion</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </details>

                <details class="bmc-collapse">
                    <summary>Ranking sector total (acumulado y mes)</summary>
                    <div class="row g-4 mt-1">
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-sector-total-acum-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Sector</th>
                                            <th>Actual (MM)</th>
                                            <th>Participacion</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-sector-total-mes-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Sector</th>
                                            <th>Actual (MM)</th>
                                            <th>Participacion</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </details>

                <details class="bmc-collapse">
                    <summary>Ranking sector SCB (acumulado y mes)</summary>
                    <div class="row g-4 mt-1">
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-sector-scb-acum-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Sector</th>
                                            <th>SCB</th>
                                            <th>Actual (MM)</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-sector-scb-mes-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Sector</th>
                                            <th>SCB</th>
                                            <th>Actual (MM)</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </details>

                <details class="bmc-collapse">
                    <summary>Productos por sector</summary>
                    <div class="table-responsive mt-2">
                        <table id="bmc-products-sector-table" class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Sector</th>
                                    <th>Producto</th>
                                    <th>Actual (MM)</th>
                                    <th>Participacion</th>
                                    <th>Variacion %</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </details>

                <details class="bmc-collapse">
                    <summary>Repos CDM subyacente (acumulado y mes)</summary>
                    <div class="row g-4 mt-1">
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-repos-subyacente-acum-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Subyacente</th>
                                            <th>Actual (MM)</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-repos-subyacente-mes-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Subyacente</th>
                                            <th>Actual (MM)</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </details>

                <details class="bmc-collapse">
                    <summary>Repos CDM SCB (acumulado y mes)</summary>
                    <div class="row g-4 mt-1">
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-repos-scb-acum-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>SCB</th>
                                            <th>Actual (MM)</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="table-responsive">
                                <table id="bmc-repos-scb-mes-table" class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>SCB</th>
                                            <th>Actual (MM)</th>
                                            <th>Variacion %</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../src/Views/layouts/app.php';
?>



