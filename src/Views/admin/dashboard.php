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
$additionalJS = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const dashboardData = <?= json_encode($dashboard, JSON_UNESCAPED_UNICODE) ?>;
const savedLayout = <?= json_encode($layout, JSON_UNESCAPED_UNICODE) ?>;
const defaultLayout = <?= json_encode($defaultLayout, JSON_UNESCAPED_UNICODE) ?>;
const currentYear = <?= (int) $year ?>;

const widgetRegistry = {
    kpi_total_transacciones: {
        title: 'Total Transacciones',
        group: 'KPIs',
        size: 'small',
        className: 'kpi-card kpi-purple',
        html: () => `
            <div class="card-body">
                <div class="kpi-title">Total Transacciones</div>
                <div class="kpi-value">${formatNumberValue(dashboardData.kpis?.total_transacciones)}</div>
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
            </div>
        `
    },
    chart_negociado_mes: {
        title: 'Negociado por Mes',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_negociado_mes', 'Negociado por Mes'),
        onMount: (card) => renderLineChart(card, 'chart_negociado_mes', 'Negociado', 'total_negociado', '#16a34a')
    },
    chart_comision_mes: {
        title: 'Comision por Mes',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_comision_mes', 'Comision por Mes'),
        onMount: (card) => renderLineChart(card, 'chart_comision_mes', 'Comision', 'total_comision', '#0ea5e9')
    },
    chart_transacciones_mes: {
        title: 'Transacciones por Mes',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_transacciones_mes', 'Transacciones por Mes'),
        onMount: (card) => renderBarChart(card, 'chart_transacciones_mes', 'Transacciones', 'total_transacciones', '#8b5cf6')
    },
    chart_top_corredores: {
        title: 'Top Corredores por Volumen',
        group: 'Graficos',
        size: 'full',
        html: () => chartCardTemplate('chart_top_corredores', 'Top Corredores por Volumen'),
        onMount: (card) => renderTopCorredoresChart(card)
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

function chartCardTemplate(id, title) {
    return `
        <div class="card-header">
            <h3 class="card-title">${title}</h3>
        </div>
        <div class="card-body">
            <div class="chart-wrapper">
                <canvas data-chart-id="${id}"></canvas>
            </div>
        </div>
    `;
}

function renderLineChart(card, widgetId, label, key, color) {
    const rows = dashboardData.por_mes || [];
    const labels = rows.map(row => row.mes);
    const values = rows.map(row => parseFloat(row[key] || 0));

    if (!labels.length || typeof Chart === 'undefined') {
        showEmptyChart(card, 'No hay datos para graficar.');
        return;
    }

    const canvas = card.querySelector('canvas');
    const ctx = canvas.getContext('2d');
    chartInstances[widgetId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data: values,
                borderColor: color,
                backgroundColor: color + '22',
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    ticks: {
                        callback: value => formatCompact(value)
                    }
                }
            }
        }
    });
}

function renderBarChart(card, widgetId, label, key, color) {
    const rows = dashboardData.por_mes || [];
    const labels = rows.map(row => row.mes);
    const values = rows.map(row => parseFloat(row[key] || 0));

    if (!labels.length || typeof Chart === 'undefined') {
        showEmptyChart(card, 'No hay datos para graficar.');
        return;
    }

    const canvas = card.querySelector('canvas');
    const ctx = canvas.getContext('2d');
    chartInstances[widgetId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data: values,
                backgroundColor: color
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    ticks: {
                        callback: value => formatCompact(value)
                    }
                }
            }
        }
    });
}

function renderTopCorredoresChart(card) {
    const rows = (dashboardData.por_corredor || []).slice(0, 10);
    const labels = rows.map(row => row.corredor);
    const values = rows.map(row => parseFloat(row.total_negociado || 0));

    if (!labels.length || typeof Chart === 'undefined') {
        showEmptyChart(card, 'No hay datos para graficar.');
        return;
    }

    const canvas = card.querySelector('canvas');
    const ctx = canvas.getContext('2d');
    chartInstances.chart_top_corredores = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Negociado',
                data: values,
                backgroundColor: '#16a34a'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    ticks: {
                        callback: value => formatCompact(value)
                    }
                }
            }
        }
    });
}

function showEmptyChart(card, message) {
    const wrapper = card.querySelector('.chart-wrapper');
    if (wrapper) {
        wrapper.innerHTML = `<div class="widget-empty">${message}</div>`;
    }
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

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
