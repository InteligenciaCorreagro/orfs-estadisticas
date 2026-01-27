<?php
// modules/benchmark/views/partials/benchmark_nav.php

$currentPage = $currentPage ?? '';
$pageTitle = $pageTitle ?? 'Benchmark';
$pageSubtitle = $pageSubtitle ?? 'Benchmark competitivo';
?>

<div class="bmc-header card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div>
                <h1 class="h4 mb-1"><?= e($pageTitle) ?></h1>
                <div class="text-muted small"><?= e($pageSubtitle) ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary bmc-refresh">
                    <i class="fas fa-rotate me-1"></i> Actualizar
                </button>
                <span class="small text-muted bmc-last-updated">Actualizado: --</span>
            </div>
        </div>

        <ul class="nav nav-pills bmc-nav mt-3">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/bi/benchmark">
                    <i class="fas fa-chart-line me-1"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'comparativa' ? 'active' : '' ?>" href="/bi/benchmark/comparativa">
                    <i class="fas fa-users me-1"></i> Comparativa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'sectores' ? 'active' : '' ?>" href="/bi/benchmark/sectores">
                    <i class="fas fa-layer-group me-1"></i> Sectores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'temporal' ? 'active' : '' ?>" href="/bi/benchmark/temporal">
                    <i class="fas fa-calendar-alt me-1"></i> Analisis Temporal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'productos' ? 'active' : '' ?>" href="/bi/benchmark/productos">
                    <i class="fas fa-boxes-stacked me-1"></i> Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'reportes' ? 'active' : '' ?>" href="/bi/benchmark/reportes">
                    <i class="fas fa-file-export me-1"></i> Reportes
                </a>
            </li>
        </ul>
    </div>
</div>

