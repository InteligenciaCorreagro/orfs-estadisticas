<?php
// src/Views/admin/dashboard.php
ob_start();
$pageTitle = 'Dashboard';
?>

<div class="page-header mb-3">
    <h1>Dashboard Administrativo</h1>
    <p class="text-muted">Bienvenido, <?= e($userName) ?></p>
</div>

<!-- Filtro de anio -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/dashboard" class="d-flex gap-2 align-center">
            <label for="year">Anio:</label>
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

<div class="dashboard-toolbar">
    <button type="button" class="btn btn-secondary" id="dashboardEditToggle">
        <i class="fas fa-sliders-h"></i> Editar dashboard
    </button>
    <button type="button" class="btn btn-primary" id="dashboardSave" style="display: none;">
        <i class="fas fa-save"></i> Guardar
    </button>
    <button type="button" class="btn btn-secondary" id="dashboardCancel" style="display: none;">
        <i class="fas fa-times"></i> Cancelar
    </button>
    <span class="text-muted" id="dashboardHint" style="display: none;">Arrastra para ordenar</span>
</div>

<div class="card mb-3" id="dashboardEditor" style="display: none;">
    <div class="card-header">
        <h3 class="card-title">Widgets disponibles</h3>
    </div>
    <div class="card-body">
        <div id="widgetOptions" class="dashboard-editor"></div>
    </div>
</div>

<div id="dashboardGrid" class="dashboard-grid"></div>

<?php
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

$dashboardJson = json_encode($dashboard, $jsonFlags);
if ($dashboardJson === false) {
    $dashboardJson = 'null';
}
$layoutJson = json_encode($layout, $jsonFlags);
if ($layoutJson === false) {
    $layoutJson = '[]';
}
$defaultLayoutJson = json_encode($defaultLayout, $jsonFlags);
if ($defaultLayoutJson === false) {
    $defaultLayoutJson = '[]';
}

$additionalJS = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const dashboardData = __DASHBOARD_JSON__;
const savedLayout = __LAYOUT_JSON__;
const defaultLayout = __DEFAULT_LAYOUT_JSON__;
const currentYear = __CURRENT_YEAR__;

const chartTypeLabels = {
    bar: 'Barras',
    doughnut: 'Donas',
    line: 'Flechas'
};
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

const widgetRegistry = {
    kpi_total_transacciones: {
        title: 'Total Registros',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-purple',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Registros</div>
                <div class="kpi-value">${formatNumberValue(dashboardData.kpis?.total_transacciones)}</div>
                ${buildKpiTrend('total_transacciones')}
            </div>
        `
    },
    kpi_total_negociado: {
        title: 'Total Negociado',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-pink',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Negociado</div>
                <div class="kpi-value">${formatMoney(dashboardData.kpis?.total_negociado)}</div>
                ${buildKpiTrend('total_negociado')}
            </div>
        `
    },
    kpi_total_comision: {
        title: 'Total Comision',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-blue',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Comision</div>
                <div class="kpi-value">${formatMoney(dashboardData.kpis?.total_comision)}</div>
                ${buildKpiTrend('total_comision')}
            </div>
        `
    },
    kpi_total_ruedas: {
        title: 'Total Ruedas',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-green',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Ruedas</div>
                <div class="kpi-value">${formatNumberValue(dashboardData.kpis?.total_ruedas)}</div>
                ${buildKpiTrend('total_ruedas')}
            </div>
        `
    },
    kpi_total_clientes: {
        title: 'Total Clientes',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-teal',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Clientes</div>
                <div class="kpi-value">${formatNumberValue(dashboardData.kpis?.total_clientes)}</div>
                ${buildKpiTrend('total_clientes')}
            </div>
        `
    },
    kpi_total_margen: {
        title: 'Total Margen',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-orange',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Margen</div>
                <div class="kpi-value">${formatMoney(dashboardData.kpis?.total_margen)}</div>
                ${buildKpiTrend('total_margen')}
            </div>
        `
    },
    chart_negociado_mes: {
        title: 'Negociado por Mes',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_negociado_mes', 'Negociado por Mes', { defaultType: 'line' }),
        onMount: (card) => renderMetricChart(card, 'chart_negociado_mes', 'Negociado', 'total_negociado', '#16a34a', 'line')
    },
    chart_comision_mes: {
        title: 'Comision por Mes',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_comision_mes', 'Comision por Mes', { defaultType: 'line' }),
        onMount: (card) => renderMetricChart(card, 'chart_comision_mes', 'Comision', 'total_comision', '#0ea5e9', 'line')
    },
    chart_transacciones_mes: {
        title: 'Transacciones por Mes',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_transacciones_mes', 'Transacciones por Mes', { defaultType: 'bar' }),
        onMount: (card) => renderMetricChart(card, 'chart_transacciones_mes', 'Transacciones', 'total_transacciones', '#8b5cf6', 'bar')
    },
    chart_top_corredores: {
        title: 'Mejores Traders por Margen',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_top_corredores', 'Mejores Traders por Margen', { defaultType: 'bar', showTrend: false }),
        onMount: (card) => renderTopCorredoresChart(card)
    },
    chart_top_clientes_negociado: {
        title: 'Clientes que mas transan',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_top_clientes_negociado', 'Clientes que mas transan', { defaultType: 'bar', showTrend: false }),
        onMount: (card) => renderTopClientesChart(card, 'chart_top_clientes_negociado', dashboardData.top_clientes || [], 'Negociado', 'total_negociado', '#f97316')
    },
    chart_top_clientes_comision: {
        title: 'Ranking de clientes por comision generada',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_top_clientes_comision', 'Ranking de clientes por comision generada', { defaultType: 'bar', showTrend: false }),
        onMount: (card) => renderTopClientesChart(card, 'chart_top_clientes_comision', dashboardData.top_clientes_comision || [], 'Comision', 'total_comision', '#ef4444')
    },
    insights_rankings: {
        title: 'Rankings clave',
        group: 'Insights',
        size: 'full',
        html: () => buildRankingInsights()
    },
    table_por_mes: {
        title: 'Resumen Mensual',
        group: 'Tablas',
        size: 'full',
        html: () => buildResumenMensualTable()
    },
    table_ultimas_ruedas: {
        title: 'Ultimas Ruedas Procesadas',
        group: 'Tablas',
        size: 'full',
        html: () => buildUltimasRuedasTable()
    },
    table_top_clientes: {
        title: 'Top Clientes por Volumen',
        group: 'Tablas',
        size: 'full',
        html: () => buildTopClientesTable()
    }
};

const dashboardGrid = document.getElementById('dashboardGrid');
const editorCard = document.getElementById('dashboardEditor');
const widgetOptions = document.getElementById('widgetOptions');
const editToggle = document.getElementById('dashboardEditToggle');
const saveButton = document.getElementById('dashboardSave');
const cancelButton = document.getElementById('dashboardCancel');
const dashboardHint = document.getElementById('dashboardHint');

let layout = normalizeLayout(savedLayout);
let lastSavedLayout = JSON.parse(JSON.stringify(layout));
let editMode = false;
const chartInstances = {};

function normalizeLayout(rawLayout) {
    const defaults = Array.isArray(defaultLayout) ? defaultLayout : [];
    const defaultMap = new Map(defaults.map(item => [item.id, { ...item }]));
    const result = [];

    if (Array.isArray(rawLayout)) {
        rawLayout.forEach(item => {
            if (!item || !item.id || !defaultMap.has(item.id)) return;
            const base = defaultMap.get(item.id);
            base.enabled = item.enabled !== false;
            result.push(base);
            defaultMap.delete(item.id);
        });
    }

    defaultMap.forEach(item => result.push(item));
    return result;
}

function renderDashboard() {
    destroyCharts();
    dashboardGrid.innerHTML = '';

    const enabled = layout.filter(item => item.enabled && widgetRegistry[item.id]);
    if (!enabled.length) {
        dashboardGrid.innerHTML = '<div class="dashboard-empty">No hay widgets activos. Usa "Editar dashboard" para agregar.</div>';
        return;
    }

    enabled.forEach(item => {
        const def = widgetRegistry[item.id];
        const card = document.createElement('div');
        card.className = `card dashboard-widget ${def.className || ''} ${def.size === 'full' ? 'widget-size-full' : ''}`;
        card.dataset.widgetId = item.id;
        card.innerHTML = def.html();

        const handle = document.createElement('div');
        handle.className = 'widget-handle';
        handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
        card.appendChild(handle);

        if (editMode) {
            card.setAttribute('draggable', 'true');
        } else {
            card.removeAttribute('draggable');
        }

        dashboardGrid.appendChild(card);
        if (typeof def.onMount === 'function') {
            def.onMount(card);
        }
    });

    applyDragAndDrop();
}

function renderWidgetOptions() {
    widgetOptions.innerHTML = '';
    const groups = {};

    layout.forEach(item => {
        const def = widgetRegistry[item.id];
        if (!def) return;
        if (!groups[def.group]) groups[def.group] = [];
        groups[def.group].push({ ...item, title: def.title });
    });

    Object.entries(groups).forEach(([groupName, items]) => {
        const group = document.createElement('div');
        group.className = 'widget-group';
        group.innerHTML = `<div class="widget-group-title">${groupName}</div>`;

        items.forEach(item => {
            const label = document.createElement('label');
            label.className = 'widget-option';
            label.innerHTML = `
                <input type="checkbox" data-widget-id="${item.id}" ${item.enabled ? 'checked' : ''}>
                <span>${item.title}</span>
            `;
            group.appendChild(label);
        });

        widgetOptions.appendChild(group);
    });

    widgetOptions.querySelectorAll('input[type="checkbox"]').forEach(input => {
        input.addEventListener('change', () => {
            const widgetId = input.getAttribute('data-widget-id');
            const target = layout.find(item => item.id === widgetId);
            if (!target) return;
            target.enabled = input.checked;
            renderDashboard();
        });
    });
}

function setEditMode(enabled) {
    editMode = enabled;
    dashboardGrid.classList.toggle('dashboard-edit-mode', editMode);
    editorCard.style.display = editMode ? 'block' : 'none';
    saveButton.style.display = editMode ? 'inline-flex' : 'none';
    cancelButton.style.display = editMode ? 'inline-flex' : 'none';
    dashboardHint.style.display = editMode ? 'inline-flex' : 'none';
    editToggle.style.display = editMode ? 'none' : 'inline-flex';

    if (editMode) {
        renderWidgetOptions();
    }

    renderDashboard();
}

function saveLayout() {
    fetch('/api/dashboard/layout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ layout })
    })
    .then(response => response.json())
    .then(result => {
        if (!result.success) {
            throw new Error(result.message || 'Error al guardar');
        }
        lastSavedLayout = JSON.parse(JSON.stringify(layout));
        setEditMode(false);
        if (typeof showNotification === 'function') {
            showNotification('Dashboard guardado correctamente', 'success');
        }
    })
    .catch(error => {
        if (typeof showNotification === 'function') {
            showNotification(error.message || 'Error al guardar', 'danger');
        }
    });
}

function cancelChanges() {
    layout = JSON.parse(JSON.stringify(lastSavedLayout));
    setEditMode(false);
}

function applyDragAndDrop() {
    if (!editMode) return;

    const draggableItems = dashboardGrid.querySelectorAll('.dashboard-widget');
    draggableItems.forEach(item => {
        item.addEventListener('dragstart', () => {
            item.classList.add('dragging');
        });
        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
            syncLayoutFromDOM();
        });
    });

    dashboardGrid.addEventListener('dragover', event => {
        event.preventDefault();
        const afterElement = getDragAfterElement(dashboardGrid, event.clientY);
        const dragging = dashboardGrid.querySelector('.dragging');
        if (!dragging) return;
        if (afterElement == null) {
            dashboardGrid.appendChild(dragging);
        } else {
            dashboardGrid.insertBefore(dragging, afterElement);
        }
    });
}

function getDragAfterElement(container, y) {
    const elements = [...container.querySelectorAll('.dashboard-widget:not(.dragging)')];
    return elements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset, element: child };
        }
        return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function syncLayoutFromDOM() {
    const orderedIds = [...dashboardGrid.querySelectorAll('.dashboard-widget')].map(el => el.dataset.widgetId);
    const enabledSet = new Set(orderedIds);
    const reordered = [];

    orderedIds.forEach(id => {
        const item = layout.find(entry => entry.id === id);
        if (item) reordered.push(item);
    });

    layout.forEach(item => {
        if (!enabledSet.has(item.id)) {
            reordered.push(item);
        }
    });

    layout = reordered;
}

function destroyCharts() {
    Object.values(chartInstances).forEach(instance => instance.destroy());
    Object.keys(chartInstances).forEach(key => delete chartInstances[key]);
}

function chartCardTemplate(id, title, config = {}) {
    const types = config.types || ['bar', 'doughnut', 'line'];
    const defaultType = config.defaultType || types[0];
    const showTrend = config.showTrend !== false;
    const options = types.map(type => `
        <option value="${type}" ${type === defaultType ? 'selected' : ''}>
            ${chartTypeLabels[type] || type}
        </option>
    `).join('');
    const trendBadge = showTrend ? `<span class="trend-badge trend-flat" data-trend-id="${id}"></span>` : '';

    return `
        <div class="card-header chart-card-header">
            <div class="chart-card-title">
                <h3 class="card-title">${title}</h3>
                ${trendBadge}
            </div>
            <select class="chart-type-select" data-chart-select="${id}">
                ${options}
            </select>
        </div>
        <div class="card-body">
            <div class="chart-wrapper">
                <canvas data-chart-id="${id}"></canvas>
            </div>
        </div>
    `;
}

function buildKpiTrend(key) {
    const trend = calculateTrend(getMonthlySeries(key));
    if (!trend) {
        return '';
    }
    const icon = trend.direction === 'up' ? 'fa-arrow-up' : trend.direction === 'down' ? 'fa-arrow-down' : 'fa-minus';
    const pct = Math.abs(trend.pct).toFixed(1);
    return `
        <div class="kpi-trend">
            <span class="trend-badge trend-${trend.direction}">
                <i class="fas ${icon}"></i>${pct}% mes
            </span>
        </div>
    `;
}

function getMonthlySeries(key) {
    return (dashboardData.por_mes || []).map(row => {
        if (row[key] === undefined || row[key] === null) {
            return Number.NaN;
        }
        const value = parseFloat(row[key]);
        return Number.isFinite(value) ? value : Number.NaN;
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

function updateTrendBadge(card, widgetId, values) {
    const badge = card.querySelector(`[data-trend-id="${widgetId}"]`);
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

function buildPalette(count) {
    const colors = [];
    for (let i = 0; i < count; i += 1) {
        colors.push(chartPalette[i % chartPalette.length]);
    }
    return colors;
}

function renderSelectableChart(card, widgetId, config) {
    const labels = config.labels || [];
    const values = (config.values || []).map(value => (Number.isFinite(value) ? value : 0));
    const color = config.color || '#16a34a';
    const defaultType = config.defaultType || 'bar';
    const label = config.label || '';
    const indexAxis = config.indexAxis || null;
    const multiColor = config.multiColor || false;

    if (!labels.length || typeof Chart === 'undefined') {
        showEmptyChart(card, 'No hay datos para graficar.');
        return;
    }

    const canvas = card.querySelector('canvas');
    const ctx = canvas.getContext('2d');
    const select = card.querySelector(`[data-chart-select="${widgetId}"]`);

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
            dataset.backgroundColor = multiColor ? buildPalette(values.length) : color;
        }

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        };

        if (type !== 'doughnut') {
            const useHorizontal = type === 'bar' && indexAxis === 'y';
            options.scales = useHorizontal
                ? {
                    x: {
                        ticks: {
                            callback: value => formatCompact(value)
                        }
                    }
                }
                : {
                    y: {
                        ticks: {
                            callback: value => formatCompact(value)
                        }
                    }
                };
            if (type === 'bar' && indexAxis) {
                options.indexAxis = indexAxis;
            }
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
    updateTrendBadge(card, widgetId, values);

    if (select) {
        select.addEventListener('change', () => renderChart(select.value));
    }
}

function renderMetricChart(card, widgetId, label, key, color, defaultType) {
    const rows = dashboardData.por_mes || [];
    const labels = rows.map(row => row.mes);
    const values = rows.map(row => parseFloat(row[key] || 0));

    renderSelectableChart(card, widgetId, {
        labels,
        values,
        label,
        color,
        defaultType
    });
}

function renderTopCorredoresChart(card) {
    const rows = (dashboardData.por_corredor || []).slice(0, 10);
    const labels = rows.map(row => row.corredor);
    const values = rows.map(row => parseFloat(row.total_margen || 0));

    renderSelectableChart(card, 'chart_top_corredores', {
        labels,
        values,
        label: 'Margen',
        color: '#16a34a',
        defaultType: 'bar',
        indexAxis: 'y',
        multiColor: true
    });
}

function renderTopClientesChart(card, widgetId, rows, label, valueKey, color) {
    const labels = rows.slice(0, 10).map(row => row.cliente);
    const values = rows.slice(0, 10).map(row => parseFloat(row[valueKey] || 0));

    renderSelectableChart(card, widgetId, {
        labels,
        values,
        label,
        color,
        defaultType: 'bar',
        indexAxis: 'y',
        multiColor: true
    });
}

function showEmptyChart(card, message) {
    const wrapper = card.querySelector('.chart-wrapper');
    if (wrapper) {
        wrapper.innerHTML = `<div class="widget-empty">${message}</div>`;
    }
}

function buildRankingInsights() {
    const topNegociado = (dashboardData.top_clientes || []).slice(0, 5);
    const topComision = (dashboardData.top_clientes_comision || []).slice(0, 5);
    const topTraders = (dashboardData.por_corredor || []).slice(0, 5);

    return `
        <div class="card-header">
            <h3 class="card-title">Rankings clave</h3>
        </div>
        <div class="card-body">
            <div class="insight-grid">
                ${buildRankingCard(
                    'Clientes que mas transan',
                    'fa-chart-line',
                    topNegociado,
                    row => formatMoney(row.total_negociado),
                    row => `${escapeHtml(row.nit)} · ${escapeHtml(row.corredor)}`,
                    'insight-blue'
                )}
                ${buildRankingCard(
                    'Ranking de clientes por comision generada',
                    'fa-coins',
                    topComision,
                    row => formatMoney(row.total_comision),
                    row => `${escapeHtml(row.nit)} · ${escapeHtml(row.corredor)}`,
                    'insight-red'
                )}
                ${buildRankingCard(
                    'Mejores traders',
                    'fa-user-tie',
                    topTraders,
                    row => formatMoney(row.total_margen),
                    row => `${formatNumberValue(row.total_clientes)} clientes`,
                    'insight-green'
                )}
            </div>
        </div>
    `;
}

function buildRankingCard(title, icon, rows, valueFormatter, metaFormatter, className) {
    const body = rows.length ? rows.map((row, index) => `
        <div class="insight-item">
            <div>
                <div class="insight-name">
                    <span class="insight-rank">${index + 1}</span>
                    ${escapeHtml(row.cliente || row.corredor)}
                </div>
                <div class="insight-meta">${metaFormatter(row)}</div>
            </div>
            <div class="insight-value">${valueFormatter(row)}</div>
        </div>
    `).join('') : `
        <div class="insight-empty">No hay datos disponibles</div>
    `;

    return `
        <div class="insight-card ${className}">
            <div class="insight-header">
                <i class="fas ${icon}"></i>
                <span>${title}</span>
            </div>
            <div class="insight-list">${body}</div>
        </div>
    `;
}

function buildResumenMensualTable() {
    const rows = dashboardData.por_mes || [];
    const body = rows.length ? rows.map(row => `
        <tr>
            <td>${escapeHtml(row.mes)}</td>
            <td>${formatNumberValue(row.total_ruedas)}</td>
            <td>${formatNumberValue(row.total_transacciones)}</td>
            <td>${formatMoney(row.total_negociado)}</td>
            <td>${formatMoney(row.total_comision)}</td>
            <td>${formatMoney(row.total_margen)}</td>
        </tr>
    `).join('') : `
        <tr>
            <td colspan="6" class="text-center">No hay datos disponibles</td>
        </tr>
    `;

    return `
        <div class="card-header">
            <h3 class="card-title">Resumen Mensual ${currentYear}</h3>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Ruedas</th>
                            <th>Transacciones</th>
                            <th>Negociado</th>
                            <th>Comision</th>
                            <th>Margen</th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        </div>
    `;
}

function buildUltimasRuedasTable() {
    const rows = dashboardData.ultimas_ruedas || [];
    const body = rows.length ? rows.map(row => `
        <tr>
            <td><a href="/reportes/rueda?rueda=${encodeURIComponent(row.rueda_no)}&year=${currentYear}">Rueda ${row.rueda_no}</a></td>
            <td>${formatDateValue(row.fecha)}</td>
            <td>${formatNumberValue(row.total_transacciones)}</td>
            <td>${formatMoney(row.total_negociado)}</td>
            <td>${formatMoney(row.total_comision)}</td>
        </tr>
    `).join('') : `
        <tr>
            <td colspan="5" class="text-center">No hay datos disponibles</td>
        </tr>
    `;

    return `
        <div class="card-header">
            <h3 class="card-title">Ultimas Ruedas Procesadas</h3>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rueda</th>
                            <th>Fecha</th>
                            <th>Transacciones</th>
                            <th>Negociado</th>
                            <th>Comision</th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        </div>
    `;
}

function buildTopClientesTable() {
    const rows = dashboardData.top_clientes || [];
    const body = rows.length ? rows.map(row => `
        <tr>
            <td>${escapeHtml(row.nit)}</td>
            <td>${escapeHtml(row.cliente)}</td>
            <td>${escapeHtml(row.corredor)}</td>
            <td>${formatMoney(row.total_negociado)}</td>
            <td>${formatMoney(row.total_comision)}</td>
            <td>${formatNumberValue(row.total_transacciones)}</td>
        </tr>
    `).join('') : `
        <tr>
            <td colspan="6" class="text-center">No hay datos disponibles</td>
        </tr>
    `;

    return `
        <div class="card-header">
            <h3 class="card-title">Top Clientes por Volumen</h3>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NIT</th>
                            <th>Cliente</th>
                            <th>Corredor</th>
                            <th>Negociado</th>
                            <th>Comision</th>
                            <th>Transacciones</th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        </div>
    `;
}

function formatMoney(value) {
    if (typeof formatCurrency === 'function') {
        return formatCurrency(parseFloat(value) || 0);
    }
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(parseFloat(value) || 0);
}

function formatNumberValue(value) {
    return new Intl.NumberFormat('es-CO').format(parseFloat(value) || 0);
}

function formatDateValue(value) {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleDateString('es-CO');
}

function formatCompact(value) {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('es-CO', {
        notation: 'compact',
        maximumFractionDigits: 1
    }).format(num);
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

editToggle.addEventListener('click', () => setEditMode(true));
saveButton.addEventListener('click', saveLayout);
cancelButton.addEventListener('click', cancelChanges);

document.addEventListener('DOMContentLoaded', () => {
    renderDashboard();
});
</script>
JS;

$additionalJS = str_replace(
    ['__DASHBOARD_JSON__', '__LAYOUT_JSON__', '__DEFAULT_LAYOUT_JSON__', '__CURRENT_YEAR__'],
    [$dashboardJson, $layoutJson, $defaultLayoutJson, (string) (int) $year],
    $additionalJS
);

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
