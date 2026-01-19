<?php
// src/Views/trader/dashboard.php
ob_start();
$pageTitle = 'Mi Dashboard';
?>

<div class="trader-dashboard">
<div class="page-header mb-3">
    <h1>Mi Dashboard</h1>
    <p class="text-muted">Bienvenido, <?= e($userName) ?> (<?= e($traderName) ?>)</p>
</div>

<!-- Filtro de anio -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/trader/dashboard">
            <div class="d-flex gap-2 align-center trader-filter-row">
                <label for="year">Anio:</label>
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
<div class="dashboard-grid dashboard-grid-kpi mb-3">
    <div class="card dashboard-widget kpi-card kpi-purple">
        <div class="card-body">
            <div class="kpi-title">Mis Transacciones</div>
            <div class="kpi-value"><?= number_format($estadisticas['total_transacciones'] ?? 0) ?></div>
            <div class="kpi-trend" data-kpi-trend="total_transacciones"></div>
        </div>
    </div>
    <div class="card dashboard-widget kpi-card kpi-pink">
        <div class="card-body">
            <div class="kpi-title">Total Negociado</div>
            <div class="kpi-value"><?= formatCurrency($estadisticas['total_negociado'] ?? 0) ?></div>
            <div class="kpi-trend" data-kpi-trend="total_negociado"></div>
        </div>
    </div>
    <div class="card dashboard-widget kpi-card kpi-blue">
        <div class="card-body">
            <div class="kpi-title">Total Comision</div>
            <div class="kpi-value"><?= formatCurrency($estadisticas['total_comision'] ?? 0) ?></div>
            <div class="kpi-trend" data-kpi-trend="total_comision"></div>
        </div>
    </div>
    <div class="card dashboard-widget kpi-card kpi-green">
        <div class="card-body">
            <div class="kpi-title">Mis Ruedas</div>
            <div class="kpi-value"><?= number_format($estadisticas['total_ruedas'] ?? 0) ?></div>
            <div class="kpi-trend" data-kpi-trend="total_ruedas"></div>
        </div>
    </div>
    <div class="card dashboard-widget kpi-card kpi-teal">
        <div class="card-body">
            <div class="kpi-title">Mis Clientes</div>
            <div class="kpi-value"><?= number_format($estadisticas['total_clientes'] ?? 0) ?></div>
        </div>
    </div>
</div>

<?php if (!empty($comparacion['aヵo_actual']) && !empty($comparacion['aヵo_anterior'])): ?>
<div class="card dashboard-widget mb-3">
    <div class="card-header">
        <h3 class="card-title">Mi rendimiento vs anio anterior</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-grid">
            <div>
                <h4>Negociado</h4>
                <p style="font-size: 20px; font-weight: bold;">
                    <?= formatCurrency($comparacion['aヵo_actual']['total_negociado']) ?>
                </p>
                <p style="color: <?= $comparacion['porcentaje_variacion_negociado'] >= 0 ? '#16a34a' : '#ef4444' ?>;">
                    <i class="fas <?= $comparacion['porcentaje_variacion_negociado'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                    <?= formatPercent(abs($comparacion['porcentaje_variacion_negociado'])) ?>
                </p>
            </div>
            <div>
                <h4>Comision</h4>
                <p style="font-size: 20px; font-weight: bold;">
                    <?= formatCurrency($comparacion['aヵo_actual']['total_comision']) ?>
                </p>
                <p style="color: <?= $comparacion['porcentaje_variacion_comision'] >= 0 ? '#16a34a' : '#ef4444' ?>;">
                    <i class="fas <?= $comparacion['porcentaje_variacion_comision'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                    <?= formatPercent(abs($comparacion['porcentaje_variacion_comision'])) ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Graficos -->
<div class="dashboard-grid dashboard-grid-charts mb-3">
    <div class="card dashboard-widget">
        <div class="card-header chart-card-header">
            <div class="chart-card-title">
                <h3 class="card-title">Negociado por Mes</h3>
                <span class="trend-badge trend-flat" data-trend-id="chart_trader_negociado"></span>
            </div>
            <select class="chart-type-select" data-chart-select="chart_trader_negociado">
                <option value="bar">Barras</option>
                <option value="doughnut">Donas</option>
                <option value="line" selected>Flechas</option>
            </select>
        </div>
        <div class="card-body">
            <div class="chart-wrapper">
                <canvas data-chart-id="chart_trader_negociado"></canvas>
            </div>
        </div>
    </div>
    <div class="card dashboard-widget">
        <div class="card-header chart-card-header">
            <div class="chart-card-title">
                <h3 class="card-title">Comision por Mes</h3>
                <span class="trend-badge trend-flat" data-trend-id="chart_trader_comision"></span>
            </div>
            <select class="chart-type-select" data-chart-select="chart_trader_comision">
                <option value="bar">Barras</option>
                <option value="doughnut">Donas</option>
                <option value="line" selected>Flechas</option>
            </select>
        </div>
        <div class="card-body">
            <div class="chart-wrapper">
                <canvas data-chart-id="chart_trader_comision"></canvas>
            </div>
        </div>
    </div>
    <div class="card dashboard-widget">
        <div class="card-header chart-card-header">
            <div class="chart-card-title">
                <h3 class="card-title">Transacciones por Mes</h3>
                <span class="trend-badge trend-flat" data-trend-id="chart_trader_transacciones"></span>
            </div>
            <select class="chart-type-select" data-chart-select="chart_trader_transacciones">
                <option value="bar" selected>Barras</option>
                <option value="doughnut">Donas</option>
                <option value="line">Flechas</option>
            </select>
        </div>
        <div class="card-body">
            <div class="chart-wrapper">
                <canvas data-chart-id="chart_trader_transacciones"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Rankings -->
<div class="dashboard-grid dashboard-grid-rankings mb-3">
    <div class="card dashboard-widget">
        <div class="card-header">
            <h3 class="card-title">Clientes que mas transan</h3>
        </div>
        <div class="card-body">
            <div class="ranking-list">
                <?php if (!empty($topClientesNegociado)): ?>
                    <?php foreach ($topClientesNegociado as $index => $cliente): ?>
                        <div class="ranking-item">
                            <div>
                                <div class="ranking-name">
                                    <span class="ranking-rank"><?= $index + 1 ?></span>
                                    <?= e($cliente['cliente']) ?>
                                </div>
                                <div class="ranking-meta">
                                    NIT <?= e($cliente['nit']) ?> · <?= number_format($cliente['total_transacciones']) ?> transacciones
                                </div>
                            </div>
                            <div class="ranking-value"><?= formatCurrency($cliente['total_negociado']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">No hay datos disponibles</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card dashboard-widget">
        <div class="card-header">
            <h3 class="card-title">Ranking de clientes por comision generada</h3>
        </div>
        <div class="card-body">
            <div class="ranking-list">
                <?php if (!empty($topClientesComision)): ?>
                    <?php foreach ($topClientesComision as $index => $cliente): ?>
                        <div class="ranking-item">
                            <div>
                                <div class="ranking-name">
                                    <span class="ranking-rank"><?= $index + 1 ?></span>
                                    <?= e($cliente['cliente']) ?>
                                </div>
                                <div class="ranking-meta">
                                    NIT <?= e($cliente['nit']) ?> · <?= number_format($cliente['total_transacciones']) ?> transacciones
                                </div>
                            </div>
                            <div class="ranking-value"><?= formatCurrency($cliente['total_comision']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">No hay datos disponibles</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top clientes del mes -->
<div class="card dashboard-widget mb-3">
    <div class="card-header">
        <h3 class="card-title">
            Top clientes del mes <?= $mesTopClientes ? e($mesTopClientes) : '' ?>
        </h3>
    </div>
    <div class="card-body">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIT</th>
                        <th>Cliente</th>
                        <th>Transacciones mes</th>
                        <th>Negociado</th>
                        <th>Comision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($topClientesMes)): ?>
                        <?php foreach ($topClientesMes as $cliente): ?>
                            <tr>
                                <td><?= e($cliente['nit']) ?></td>
                                <td><?= e($cliente['cliente']) ?></td>
                                <td><?= number_format($cliente['total_transacciones']) ?></td>
                                <td><?= formatCurrency($cliente['total_negociado']) ?></td>
                                <td><?= formatCurrency($cliente['total_comision']) ?></td>
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

</div>

<?php
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

$resumenMensualJson = json_encode($resumenMensual, $jsonFlags);
if ($resumenMensualJson === false) {
    $resumenMensualJson = '[]';
}

$additionalJS = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const resumenMensual = __RESUMEN_MENSUAL_JSON__;
const chartPalette = [
    '#16a34a',
    '#0ea5e9',
    '#f97316',
    '#8b5cf6',
    '#ef4444',
    '#14b8a6',
    '#eab308',
    '#6366f1',
    '#10b981',
    '#f59e0b'
];
const chartInstances = {};

function getMonthlySeries(key) {
    return (resumenMensual || []).map(row => {
        const value = parseFloat(row[key] || 0);
        return Number.isFinite(value) ? value : 0;
    });
}

function calculateTrend(values) {
    const clean = values.filter(value => Number.isFinite(value));
    if (clean.length < 2) {
        return null;
    }
    const last = clean[clean.length - 1];
    const prev = clean[clean.length - 2];
    const delta = last - prev;
    const pct = prev ? (delta / prev) * 100 : 0;
    const direction = delta > 0 ? 'up' : delta < 0 ? 'down' : 'flat';
    return { direction, pct };
}

function buildPalette(count) {
    const colors = [];
    for (let i = 0; i < count; i += 1) {
        colors.push(chartPalette[i % chartPalette.length]);
    }
    return colors;
}

function formatCompact(value) {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('es-CO', {
        notation: 'compact',
        maximumFractionDigits: 1
    }).format(num);
}

function renderTrendBadge(widgetId, values) {
    const badge = document.querySelector(`[data-trend-id="${widgetId}"]`);
    if (!badge) {
        return;
    }
    const trend = calculateTrend(values);
    if (!trend) {
        badge.textContent = 'Sin datos';
        badge.className = 'trend-badge trend-flat';
        return;
    }
    const icon = trend.direction === 'up' ? 'fa-arrow-up' : trend.direction === 'down' ? 'fa-arrow-down' : 'fa-minus';
    const pct = Math.abs(trend.pct).toFixed(1);
    badge.className = `trend-badge trend-${trend.direction}`;
    badge.innerHTML = `<i class="fas ${icon}"></i>${pct}% mes`;
}

function renderKpiTrends() {
    document.querySelectorAll('[data-kpi-trend]').forEach(el => {
        const key = el.getAttribute('data-kpi-trend');
        const values = getMonthlySeries(key);
        const trend = calculateTrend(values);
        if (!trend) {
            el.innerHTML = '';
            return;
        }
        const icon = trend.direction === 'up' ? 'fa-arrow-up' : trend.direction === 'down' ? 'fa-arrow-down' : 'fa-minus';
        const pct = Math.abs(trend.pct).toFixed(1);
        el.innerHTML = `
            <span class="trend-badge trend-${trend.direction}">
                <i class="fas ${icon}"></i>${pct}% mes
            </span>
        `;
    });
}

function showEmptyChart(widgetId, message) {
    const canvas = document.querySelector(`[data-chart-id="${widgetId}"]`);
    const wrapper = canvas?.closest('.chart-wrapper');
    if (wrapper) {
        wrapper.innerHTML = `<div class="widget-empty">${message}</div>`;
    }
}

function renderSelectableChart(widgetId, label, values, color, defaultType) {
    const labels = (resumenMensual || []).map(row => row.mes);
    if (!labels.length || typeof Chart === 'undefined') {
        showEmptyChart(widgetId, 'No hay datos para graficar.');
        return;
    }

    const canvas = document.querySelector(`[data-chart-id="${widgetId}"]`);
    const ctx = canvas.getContext('2d');
    const select = document.querySelector(`[data-chart-select="${widgetId}"]`);

    const renderChart = (type) => {
        if (chartInstances[widgetId]) {
            chartInstances[widgetId].destroy();
        }

        const dataset = {
            label,
            data: values
        };

        if (type === 'line') {
            dataset.borderColor = color;
            dataset.backgroundColor = color + '22';
            dataset.fill = true;
            dataset.tension = 0.35;
        } else if (type === 'doughnut') {
            dataset.backgroundColor = buildPalette(values.length);
            dataset.borderWidth = 0;
        } else {
            dataset.backgroundColor = color;
        }

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        };

        if (type !== 'doughnut') {
            options.scales = {
                y: {
                    ticks: {
                        callback: value => formatCompact(value)
                    }
                }
            };
        }

        chartInstances[widgetId] = new Chart(ctx, {
            type,
            data: {
                labels,
                datasets: [dataset]
            },
            options
        });
    };

    const initialType = select ? (select.value || defaultType) : defaultType;
    renderChart(initialType);
    renderTrendBadge(widgetId, values);

    if (select) {
        select.addEventListener('change', () => renderChart(select.value));
    }
}

function renderCharts() {
    renderSelectableChart(
        'chart_trader_negociado',
        'Negociado',
        getMonthlySeries('total_negociado'),
        '#16a34a',
        'line'
    );
    renderSelectableChart(
        'chart_trader_comision',
        'Comision',
        getMonthlySeries('total_comision'),
        '#0ea5e9',
        'line'
    );
    renderSelectableChart(
        'chart_trader_transacciones',
        'Transacciones',
        getMonthlySeries('total_transacciones'),
        '#8b5cf6',
        'bar'
    );
}

document.addEventListener('DOMContentLoaded', () => {
    renderKpiTrends();
    renderCharts();
});
</script>
JS;

$additionalJS = str_replace(
    ['__RESUMEN_MENSUAL_JSON__'],
    [$resumenMensualJson],
    $additionalJS
);

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
