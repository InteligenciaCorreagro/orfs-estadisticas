<?php
// src/Views/trader/dashboard.php
ob_start();
$pageTitle = 'Mi Dashboard';
?>

<div class="page-header mb-3">
    <h1>Mi Dashboard</h1>
    <p class="text-muted">Bienvenido, <?= e($userName) ?> (<?= e($traderName) ?>)</p>
</div>

<!-- Filtro de año -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/trader/dashboard">
            <div class="d-flex gap-2 align-center">
                <label for="year">Año:</label>
                <select name="year" id="year" class="form-select" style="width: 150px;" onchange="this.form.submit()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Mis Transacciones</h3>
            <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">
                <?= number_format($estadisticas['total_transacciones'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Negociado</h3>
            <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">
                <?= formatCurrency($estadisticas['total_negociado'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Comisión</h3>
            <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">
                <?= formatCurrency($estadisticas['total_comision'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <div class="card-body">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Mis Clientes</h3>
            <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">
                <?= number_format($estadisticas['total_clientes'] ?? 0) ?>
            </p>
        </div>
    </div>
</div>

<!-- Comparación -->
<?php if (!empty($comparacion['año_actual']) && !empty($comparacion['año_anterior'])): ?>
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Mi Rendimiento vs Año Anterior</h3>
    </div>
    <div class="card-body">
        <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <h4>Negociado</h4>
                <p style="font-size: 20px; font-weight: bold;">
                    <?= formatCurrency($comparacion['año_actual']['total_negociado']) ?>
                </p>
                <p style="color: <?= $comparacion['porcentaje_variacion_negociado'] >= 0 ? '#43e97b' : '#f5576c' ?>;">
                    <?= $comparacion['porcentaje_variacion_negociado'] >= 0 ? '▲' : '▼' ?> 
                    <?= formatPercent(abs($comparacion['porcentaje_variacion_negociado'])) ?>
                </p>
            </div>
            <div>
                <h4>Comisión</h4>
                <p style="font-size: 20px; font-weight: bold;">
                    <?= formatCurrency($comparacion['año_actual']['total_comision']) ?>
                </p>
                <p style="color: <?= $comparacion['porcentaje_variacion_comision'] >= 0 ? '#43e97b' : '#f5576c' ?>;">
                    <?= $comparacion['porcentaje_variacion_comision'] >= 0 ? '▲' : '▼' ?> 
                    <?= formatPercent(abs($comparacion['porcentaje_variacion_comision'])) ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Resumen Mensual -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Mi Evolución Mensual <?= $year ?></h3>
    </div>
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Transacciones</th>
                        <th>Ruedas</th>
                        <th>Negociado</th>
                        <th>Comisión</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($resumenMensual)): ?>
                        <?php foreach ($resumenMensual as $mes): ?>
                            <tr>
                                <td><?= e($mes['mes']) ?></td>
                                <td><?= number_format($mes['total_transacciones']) ?></td>
                                <td><?= number_format($mes['total_ruedas']) ?></td>
                                <td><?= formatCurrency($mes['total_negociado']) ?></td>
                                <td><?= formatCurrency($mes['total_comision']) ?></td>
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