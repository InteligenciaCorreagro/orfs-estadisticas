<?php
// src/Views/admin/dashboard.php
ob_start();
$pageTitle = 'Dashboard';
?>

<div class="page-header mb-3">
    <h1>Dashboard Administrativo</h1>
    <p class="text-muted">Bienvenido, <?= e($userName) ?></p>
</div>

<!-- Filtro de año -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/dashboard" class="d-flex gap-2 align-center">
            <label for="year">Año:</label>
            <select name="year" id="year" class="form-select" style="width: 150px;" onchange="this.form.submit()">
                <?php foreach (getYearsArray(2020) as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <div class="card kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Transacciones</h3>
            <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                <?= number_format($dashboard['kpis']['total_transacciones'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <div class="card kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Negociado</h3>
            <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                <?= formatCurrency($dashboard['kpis']['total_negociado'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <div class="card kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Comisión</h3>
            <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                <?= formatCurrency($dashboard['kpis']['total_comision'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <div class="card kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Ruedas</h3>
            <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                <?= number_format($dashboard['kpis']['total_ruedas'] ?? 0) ?>
            </p>
        </div>
    </div>
</div>

<!-- Resumen mensual -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Evolución Mensual <?= $year ?></h3>
    </div>
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Ruedas</th>
                        <th>Transacciones</th>
                        <th>Negociado</th>
                        <th>Comisión</th>
                        <th>Margen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dashboard['por_mes'])): ?>
                        <?php foreach ($dashboard['por_mes'] as $mes): ?>
                            <tr>
                                <td><?= e($mes['mes']) ?></td>
                                <td><?= number_format($mes['total_ruedas']) ?></td>
                                <td><?= number_format($mes['total_transacciones']) ?></td>
                                <td><?= formatCurrency($mes['total_negociado']) ?></td>
                                <td><?= formatCurrency($mes['total_comision']) ?></td>
                                <td><?= formatCurrency($mes['total_margen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay datos disponibles</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Corredores -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Top 10 Corredores por Volumen</h3>
    </div>
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Corredor</th>
                        <th>Clientes</th>
                        <th>Transacciones</th>
                        <th>Negociado</th>
                        <th>Margen %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dashboard['por_corredor'])): ?>
                        <?php foreach (array_slice($dashboard['por_corredor'], 0, 10) as $index => $corredor): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($corredor['corredor']) ?></td>
                                <td><?= number_format($corredor['total_clientes']) ?></td>
                                <td><?= number_format($corredor['total_transacciones']) ?></td>
                                <td><?= formatCurrency($corredor['total_negociado']) ?></td>
                                <td><?= formatPercent($corredor['porcentaje_margen'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay datos disponibles</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Últimas ruedas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Últimas Ruedas Procesadas</h3>
    </div>
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rueda</th>
                        <th>Fecha</th>
                        <th>Transacciones</th>
                        <th>Negociado</th>
                        <th>Comisión</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dashboard['ultimas_ruedas'])): ?>
                        <?php foreach ($dashboard['ultimas_ruedas'] as $rueda): ?>
                            <tr>
                                <td>
                                    <a href="/reportes/rueda?rueda=<?= $rueda['rueda_no'] ?>&year=<?= $year ?>">
                                        Rueda <?= $rueda['rueda_no'] ?>
                                    </a>
                                </td>
                                <td><?= formatDate($rueda['fecha']) ?></td>
                                <td><?= number_format($rueda['total_transacciones']) ?></td>
                                <td><?= formatCurrency($rueda['total_negociado']) ?></td>
                                <td><?= formatCurrency($rueda['total_comision']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay datos disponibles</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';