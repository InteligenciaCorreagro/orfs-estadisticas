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
                    </form>
                    <div class="mt-3">
                        <pre class="bmc-pre" id="bmc-analyze-result">Resultado pendiente...</pre>
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



