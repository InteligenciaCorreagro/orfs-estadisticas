<?php
// src/Views/reportes/margen.php
ob_start();
$pageTitle = 'Reporte Margen';
?>

<style>
/* Contenedor principal de datos */
#dataContainer {
    width: 100%;
    overflow: hidden;
}

.corredor-group {
    margin-bottom: 2px;
}

.corredor-header {
    background: linear-gradient(135deg, #2d3436 0%, #000000 100%);
    color: white;
    padding: 12px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 4px;
    transition: all 0.3s ease;
    user-select: none;
}

.corredor-header:hover {
    background: linear-gradient(135deg, #4a4a4a 0%, #1a1a1a 100%);
}

.corredor-header.expanded {
    border-radius: 4px 4px 0 0;
}

.corredor-header .corredor-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.corredor-header .toggle-icon {
    transition: transform 0.3s ease;
    font-size: 14px;
}

.corredor-header.expanded .toggle-icon {
    transform: rotate(90deg);
}

.corredor-header .corredor-stats {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.corredor-header .stat-item {
    background: rgba(255,255,255,0.15);
    padding: 4px 10px;
    border-radius: 12px;
}

.corredor-header .stat-item.margen {
    background: rgba(39, 174, 96, 0.4);
}

.corredor-content {
    display: none;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
}

.corredor-content.show {
    display: block;
}

.table-scroll-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    width: 100%;
    display: block;
}

.corredor-content table {
    margin: 0;
    font-size: 11px;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.corredor-content th,
.corredor-content td {
    white-space: nowrap;
    padding: 6px 8px;
    border-bottom: 1px solid #eee;
}

.corredor-content th {
    background: #f8f9fa;
}

/* Columnas sticky - NIT y Cliente */
.corredor-content td:first-child,
.corredor-content th:first-child {
    position: sticky;
    left: 0;
    background: white;
    z-index: 10;
    border-right: 2px solid #ddd;
}

.corredor-content td:nth-child(2),
.corredor-content th:nth-child(2) {
    position: sticky;
    left: 80px;
    background: white;
    z-index: 10;
    border-right: 2px solid #ddd;
}

.corredor-content th:first-child,
.corredor-content th:nth-child(2) {
    background: #f8f9fa;
    z-index: 11;
}

.corredor-content tr:hover td:first-child,
.corredor-content tr:hover td:nth-child(2) {
    background: #f0f7ff;
}

/* Fila de totales sticky */
.corredor-content tr[style*="background: #e8f4e8"] td:first-child,
.corredor-content tr[style*="background: #e8f4e8"] td:nth-child(2) {
    background: #e8f4e8;
}

/* Filtros mejorados */
.filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 20px;
    padding-bottom: 10px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 150px;
}

.filter-group label {
    font-weight: 600;
    font-size: 13px;
    color: #333;
}

.multi-select-container {
    position: relative;
}

.multi-select-btn {
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.multi-select-btn:hover {
    border-color: #2d3436;
}

.multi-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 250px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    z-index: 99999;
    max-height: 300px;
    overflow-y: auto;
    display: none;
}

.multi-select-dropdown.show {
    display: block;
}

.multi-select-dropdown .select-all {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    position: sticky;
    top: 0;
}

.multi-select-dropdown .option {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.multi-select-dropdown .option:hover {
    background: #f0f7ff;
}

.multi-select-dropdown .option input {
    margin: 0;
}

/* Botones de control */
.control-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.control-buttons button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
}

.btn-expand-all {
    background: #27AE60;
    color: white;
}

.btn-expand-all:hover {
    background: #219a52;
}

.btn-collapse-all {
    background: #E74C3C;
    color: white;
}

.btn-collapse-all:hover {
    background: #c0392b;
}

/* Tabla mejorada */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 6px 8px;
    text-align: right;
    border-bottom: 1px solid #eee;
}

.data-table th:first-child,
.data-table td:first-child {
    text-align: left;
    position: sticky;
    left: 0;
    background: inherit;
    min-width: 80px;
}

.data-table th:nth-child(2),
.data-table td:nth-child(2) {
    text-align: left;
    min-width: 180px;
}

.data-table tbody tr:hover {
    background: #f0f7ff;
}

.data-table .total-column {
    background: #f0f0f0;
    font-weight: bold;
}

/* Colores de columnas */
.col-transado {
    background: rgba(102, 126, 234, 0.1);
}

.col-comision {
    background: rgba(39, 174, 96, 0.1);
}

.col-margen {
    background: rgba(243, 156, 18, 0.15);
    font-weight: 600;
}

/* Header de mes */
.mes-header {
    background: linear-gradient(135deg, #2d3436 0%, #000000 100%) !important;
    color: white !important;
    text-align: center !important;
    font-weight: bold;
    border-left: 2px solid #444;
}

.mes-subheader {
    font-size: 10px;
    text-align: center !important;
}

/* Resumen cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.summary-card {
    padding: 15px;
    border-radius: 10px;
    color: white;
}

.summary-card h4 {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}

.summary-card .value {
    font-size: 22px;
    font-weight: bold;
    margin-top: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .corredor-header .corredor-stats {
        display: none;
    }

    .filter-row {
        flex-direction: column;
    }
}
</style>

<div class="page-header mb-3">
    <h1><i class="fas fa-chart-line"></i> Reporte de Margen</h1>
    <p class="text-muted">Analisis de rentabilidad por corredor y cliente</p>
</div>

<!-- Filtros -->
<div class="card mb-3" style="overflow: visible;">
    <div class="card-body" style="overflow: visible;">
        <div class="filter-row">
            <!-- Año -->
            <div class="filter-group">
                <label for="year"><i class="fas fa-calendar"></i> Año:</label>
                <select name="year" id="year" class="form-select" style="width: 120px;">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Meses (Multi-select) -->
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> Meses:</label>
                <div class="multi-select-container" id="mesesContainer">
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('meses')">
                        <span id="mesesLabel">Todos los meses</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="multi-select-dropdown" id="mesesDropdown">
                        <div class="select-all">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="mesesSelectAll" checked onchange="toggleAllMeses()">
                                <strong>Seleccionar todos</strong>
                            </label>
                        </div>
                        <div id="mesesOptions"></div>
                    </div>
                </div>
            </div>

            <!-- Corredores (Multi-select) -->
            <div class="filter-group">
                <label><i class="fas fa-user-tie"></i> Corredores:</label>
                <div class="multi-select-container" id="corredoresContainer">
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('corredores')">
                        <span id="corredoresLabel">Todos los corredores</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="multi-select-dropdown" id="corredoresDropdown">
                        <div class="select-all">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="corredoresSelectAll" checked onchange="toggleAllCorredores()">
                                <strong>Seleccionar todos</strong>
                            </label>
                        </div>
                        <div id="corredoresOptions"></div>
                    </div>
                </div>
            </div>

            <!-- Clientes (Multi-select) -->
            <div class="filter-group">
                <label><i class="fas fa-users"></i> Clientes:</label>
                <div class="multi-select-container" id="clientesContainer">
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('clientes')">
                        <span id="clientesLabel">Todos los clientes</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="multi-select-dropdown" id="clientesDropdown">
                        <div class="select-all">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="clientesSelectAll" checked onchange="toggleAllClientes()">
                                <strong>Seleccionar todos</strong>
                            </label>
                        </div>
                        <div id="clientesOptions"></div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="filter-group">
                <label>&nbsp;</label>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen -->
<div id="summaryContainer"></div>

<!-- Tabla de datos -->
<div class="card">
    <div class="card-body">
        <!-- Botones de control -->
        <div class="control-buttons">
            <button class="btn-expand-all" onclick="expandAll()">
                <i class="fas fa-expand-alt"></i> Abrir Todos
            </button>
            <button class="btn-collapse-all" onclick="collapseAll()">
                <i class="fas fa-compress-alt"></i> Cerrar Todos
            </button>
            <span id="totalInfo" style="margin-left: auto; color: #666; font-size: 14px;"></span>
        </div>

        <div id="dataContainer">
            <div class="text-center">
                <div class="spinner"></div>
                <p>Cargando datos...</p>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
// Variables globales
let allData = [];
let filteredData = [];
const meses = [
    {key: 'enero', label: 'Ene', full: 'Enero'},
    {key: 'febrero', label: 'Feb', full: 'Febrero'},
    {key: 'marzo', label: 'Mar', full: 'Marzo'},
    {key: 'abril', label: 'Abr', full: 'Abril'},
    {key: 'mayo', label: 'May', full: 'Mayo'},
    {key: 'junio', label: 'Jun', full: 'Junio'},
    {key: 'julio', label: 'Jul', full: 'Julio'},
    {key: 'agosto', label: 'Ago', full: 'Agosto'},
    {key: 'septiembre', label: 'Sep', full: 'Septiembre'},
    {key: 'octubre', label: 'Oct', full: 'Octubre'},
    {key: 'noviembre', label: 'Nov', full: 'Noviembre'},
    {key: 'diciembre', label: 'Dic', full: 'Diciembre'}
];

let selectedMeses = meses.map(m => m.key);
let selectedCorredores = [];
let selectedClientes = [];
let allCorredores = [];
let allClientes = [];

// Formatear pesos sin decimales
function formatPesos(value) {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
}

// Formatear margen con 5 decimales
function formatMargen(comision, transado) {
    const com = parseFloat(comision) || 0;
    const trans = parseFloat(transado) || 0;
    if (trans === 0) return '0.00000%';
    const margen = (com / trans) * 100;
    return margen.toFixed(5) + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    initMesesOptions();
    loadData();

    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.multi-select-container')) {
            document.querySelectorAll('.multi-select-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
});

function initMesesOptions() {
    const container = document.getElementById('mesesOptions');
    container.innerHTML = meses.map(m => `
        <label class="option">
            <input type="checkbox" value="${m.key}" checked onchange="updateMesesSelection()">
            ${m.full}
        </label>
    `).join('');
}

function toggleDropdown(type) {
    event.stopPropagation();
    const dropdown = document.getElementById(type + 'Dropdown');
    const isOpen = dropdown.classList.contains('show');

    document.querySelectorAll('.multi-select-dropdown').forEach(d => d.classList.remove('show'));

    if (!isOpen) {
        dropdown.classList.add('show');
    }
}

function toggleAllMeses() {
    const checked = document.getElementById('mesesSelectAll').checked;
    document.querySelectorAll('#mesesOptions input').forEach(cb => cb.checked = checked);
    updateMesesSelection();
}

function toggleAllCorredores() {
    const checked = document.getElementById('corredoresSelectAll').checked;
    document.querySelectorAll('#corredoresOptions input').forEach(cb => cb.checked = checked);
    updateCorredoresSelection();
}

function toggleAllClientes() {
    const checked = document.getElementById('clientesSelectAll').checked;
    document.querySelectorAll('#clientesOptions input').forEach(cb => cb.checked = checked);
    updateClientesSelection();
}

function updateMesesSelection() {
    const checkboxes = document.querySelectorAll('#mesesOptions input:checked');
    selectedMeses = Array.from(checkboxes).map(cb => cb.value);

    const label = document.getElementById('mesesLabel');
    if (selectedMeses.length === 0) {
        label.textContent = 'Ninguno';
    } else if (selectedMeses.length === meses.length) {
        label.textContent = 'Todos los meses';
    } else {
        label.textContent = `${selectedMeses.length} meses`;
    }

    document.getElementById('mesesSelectAll').checked = selectedMeses.length === meses.length;
}

function updateCorredoresSelection() {
    const checkboxes = document.querySelectorAll('#corredoresOptions input:checked');
    selectedCorredores = Array.from(checkboxes).map(cb => cb.value);

    const label = document.getElementById('corredoresLabel');
    if (selectedCorredores.length === 0) {
        label.textContent = 'Ninguno';
    } else if (selectedCorredores.length === allCorredores.length) {
        label.textContent = 'Todos los corredores';
    } else {
        label.textContent = `${selectedCorredores.length} corredores`;
    }

    document.getElementById('corredoresSelectAll').checked = selectedCorredores.length === allCorredores.length;
    updateClientesOptions();
}

function updateClientesSelection() {
    const checkboxes = document.querySelectorAll('#clientesOptions input:checked');
    selectedClientes = Array.from(checkboxes).map(cb => cb.value);

    const label = document.getElementById('clientesLabel');
    const visibleClientes = getVisibleClientes();
    if (selectedClientes.length === 0) {
        label.textContent = 'Ninguno';
    } else if (selectedClientes.length === visibleClientes.length) {
        label.textContent = 'Todos los clientes';
    } else {
        label.textContent = `${selectedClientes.length} clientes`;
    }

    document.getElementById('clientesSelectAll').checked = selectedClientes.length === visibleClientes.length;
}

function getVisibleClientes() {
    if (selectedCorredores.length === 0 || selectedCorredores.length === allCorredores.length) {
        return allClientes;
    }
    return [...new Set(allData.filter(d => selectedCorredores.includes(d.corredor)).map(d => d.nit + '|' + d.cliente))];
}

function updateClientesOptions() {
    const visibleClientes = getVisibleClientes();
    const container = document.getElementById('clientesOptions');

    // Mantener seleccionados los que ya estaban y agregar los nuevos visibles
    const previousSelected = [...selectedClientes];

    container.innerHTML = visibleClientes.map(c => {
        const [nit, nombre] = c.split('|');
        // Si estaba seleccionado antes o es nuevo (todos los nuevos seleccionados por defecto)
        const wasSelected = previousSelected.includes(c);
        const isNew = !allClientes.includes(c) || previousSelected.length === 0;
        const isChecked = (wasSelected || isNew || previousSelected.length === allClientes.length) ? 'checked' : '';
        return `
            <label class="option">
                <input type="checkbox" value="${c}" ${isChecked} onchange="updateClientesSelection()">
                ${nombre} (${nit})
            </label>
        `;
    }).join('');

    // Actualizar selectedClientes basado en los checkboxes actuales
    const checkboxes = document.querySelectorAll('#clientesOptions input:checked');
    selectedClientes = Array.from(checkboxes).map(cb => cb.value);

    updateClientesSelection();
}

async function loadData() {
    const year = document.getElementById('year').value;

    try {
        const params = new URLSearchParams({ year });

        const response = await fetch(`/api/reportes/margen?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.success) {
            allData = result.data;

            allCorredores = [...new Set(allData.map(d => d.corredor))].sort();
            allClientes = [...new Set(allData.map(d => d.nit + '|' + d.cliente))].sort((a, b) => {
                const nameA = a.split('|')[1];
                const nameB = b.split('|')[1];
                return nameA.localeCompare(nameB);
            });

            selectedCorredores = [...allCorredores];
            selectedClientes = [...allClientes];

            initCorredoresOptions();
            initClientesOptions();

            applyFilters();
        } else {
            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-danger">Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger">Error de conexion</p>';
    }
}

function initCorredoresOptions() {
    const container = document.getElementById('corredoresOptions');
    container.innerHTML = allCorredores.map(c => `
        <label class="option">
            <input type="checkbox" value="${c}" checked onchange="updateCorredoresSelection()">
            ${c}
        </label>
    `).join('');
    updateCorredoresSelection();
}

function initClientesOptions() {
    const container = document.getElementById('clientesOptions');
    container.innerHTML = allClientes.map(c => {
        const [nit, nombre] = c.split('|');
        return `
            <label class="option">
                <input type="checkbox" value="${c}" checked onchange="updateClientesSelection()">
                ${nombre} (${nit})
            </label>
        `;
    }).join('');
    updateClientesSelection();
}

function applyFilters() {
    filteredData = allData.filter(row => {
        const clienteKey = row.nit + '|' + row.cliente;
        return selectedCorredores.includes(row.corredor) && selectedClientes.includes(clienteKey);
    });

    renderGroupedTable(filteredData);
    renderSummary(filteredData);
}

function renderSummary(data) {
    let totalTransado = 0;
    let totalComision = 0;

    data.forEach(row => {
        selectedMeses.forEach(mes => {
            totalTransado += parseFloat(row[mes + '_transado']) || 0;
            totalComision += parseFloat(row[mes + '_comision']) || 0;
        });
    });

    const margenPct = totalTransado > 0 ? ((totalComision / totalTransado) * 100).toFixed(5) : '0.00000';

    const html = `
        <div class="summary-cards">
            <div class="summary-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h4><i class="fas fa-dollar-sign"></i> Total Transado</h4>
                <div class="value">${formatPesos(totalTransado)}</div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h4><i class="fas fa-hand-holding-usd"></i> Total Comision</h4>
                <div class="value">${formatPesos(totalComision)}</div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h4><i class="fas fa-percentage"></i> Margen Promedio</h4>
                <div class="value">${margenPct}%</div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h4><i class="fas fa-users"></i> Total Clientes</h4>
                <div class="value">${data.length}</div>
            </div>
        </div>
    `;

    document.getElementById('summaryContainer').innerHTML = html;
}

function renderGroupedTable(data) {
    if (data.length === 0) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-inbox"></i> No hay datos disponibles</p>';
        document.getElementById('totalInfo').textContent = '';
        return;
    }

    // Agrupar por corredor
    const grouped = {};
    data.forEach(row => {
        if (!grouped[row.corredor]) {
            grouped[row.corredor] = [];
        }
        grouped[row.corredor].push(row);
    });

    // Calcular totales por corredor
    const corredorTotals = {};
    Object.keys(grouped).forEach(corredor => {
        let transado = 0, comision = 0;
        grouped[corredor].forEach(row => {
            selectedMeses.forEach(mes => {
                transado += parseFloat(row[mes + '_transado']) || 0;
                comision += parseFloat(row[mes + '_comision']) || 0;
            });
        });
        corredorTotals[corredor] = {
            clientes: grouped[corredor].length,
            transado,
            comision,
            margen: transado > 0 ? ((comision / transado) * 100).toFixed(5) : '0.00000'
        };
    });

    // Ordenar corredores por transado
    const sortedCorredores = Object.keys(grouped).sort((a, b) =>
        corredorTotals[b].transado - corredorTotals[a].transado
    );

    const visibleMeses = meses.filter(m => selectedMeses.includes(m.key));

    let html = '';

    sortedCorredores.forEach(corredor => {
        const rows = grouped[corredor];
        const stats = corredorTotals[corredor];

        html += `
            <div class="corredor-group">
                <div class="corredor-header" onclick="toggleGroup(this)">
                    <div class="corredor-info">
                        <i class="fas fa-chevron-right toggle-icon"></i>
                        <strong>${corredor}</strong>
                    </div>
                    <div class="corredor-stats">
                        <span class="stat-item"><i class="fas fa-users"></i> ${stats.clientes} clientes</span>
                        <span class="stat-item"><i class="fas fa-dollar-sign"></i> ${formatPesos(stats.transado)}</span>
                        <span class="stat-item"><i class="fas fa-hand-holding-usd"></i> ${formatPesos(stats.comision)}</span>
                        <span class="stat-item margen"><i class="fas fa-percentage"></i> ${stats.margen}%</span>
                    </div>
                </div>
                <div class="corredor-content">
                    <div class="table-scroll-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="vertical-align: middle;">NIT</th>
                                <th rowspan="2" style="vertical-align: middle;">Cliente</th>
                                ${visibleMeses.map(m => `<th colspan="3" class="mes-header">${m.label}</th>`).join('')}
                                <th colspan="3" class="mes-header" style="background: linear-gradient(135deg, #27ae60 0%, #16a085 100%) !important;">TOTAL</th>
                            </tr>
                            <tr>
                                ${visibleMeses.map(() => `
                                    <th class="mes-subheader col-transado">Trans.</th>
                                    <th class="mes-subheader col-comision">Com.</th>
                                    <th class="mes-subheader col-margen">Margen</th>
                                `).join('')}
                                <th class="mes-subheader col-transado">Trans.</th>
                                <th class="mes-subheader col-comision">Com.</th>
                                <th class="mes-subheader col-margen">Margen</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        rows.forEach(row => {
            let rowTransado = 0, rowComision = 0;

            html += `<tr><td>${row.nit}</td><td>${row.cliente}</td>`;

            visibleMeses.forEach(m => {
                const trans = parseFloat(row[m.key + '_transado']) || 0;
                const com = parseFloat(row[m.key + '_comision']) || 0;
                rowTransado += trans;
                rowComision += com;

                html += `
                    <td class="col-transado">${trans > 0 ? formatPesos(trans) : '-'}</td>
                    <td class="col-comision">${trans > 0 ? formatPesos(com) : '-'}</td>
                    <td class="col-margen">${trans > 0 ? formatMargen(com, trans) : '-'}</td>
                `;
            });

            // Totales de la fila
            html += `
                <td class="col-transado total-column">${formatPesos(rowTransado)}</td>
                <td class="col-comision total-column">${formatPesos(rowComision)}</td>
                <td class="col-margen total-column">${formatMargen(rowComision, rowTransado)}</td>
            </tr>`;
        });

        // Fila de totales del corredor
        let corredorTransado = 0, corredorComision = 0;
        const mesesTotals = {};
        visibleMeses.forEach(m => { mesesTotals[m.key] = { transado: 0, comision: 0 }; });

        rows.forEach(row => {
            visibleMeses.forEach(m => {
                const trans = parseFloat(row[m.key + '_transado']) || 0;
                const com = parseFloat(row[m.key + '_comision']) || 0;
                mesesTotals[m.key].transado += trans;
                mesesTotals[m.key].comision += com;
                corredorTransado += trans;
                corredorComision += com;
            });
        });

        html += `<tr style="background: #e8f4e8; font-weight: bold;">
            <td colspan="2">Total ${corredor}</td>`;

        visibleMeses.forEach(m => {
            const t = mesesTotals[m.key];
            html += `
                <td class="col-transado">${formatPesos(t.transado)}</td>
                <td class="col-comision">${formatPesos(t.comision)}</td>
                <td class="col-margen">${formatMargen(t.comision, t.transado)}</td>
            `;
        });

        html += `
            <td class="col-transado total-column">${formatPesos(corredorTransado)}</td>
            <td class="col-comision total-column">${formatPesos(corredorComision)}</td>
            <td class="col-margen total-column">${formatMargen(corredorComision, corredorTransado)}</td>
        </tr></tbody></table></div></div></div>`;
    });

    document.getElementById('dataContainer').innerHTML = html;

    // Info total
    let grandTransado = 0, grandComision = 0;
    Object.values(corredorTotals).forEach(s => {
        grandTransado += s.transado;
        grandComision += s.comision;
    });

    document.getElementById('totalInfo').innerHTML =
        `<i class="fas fa-chart-line"></i> ${sortedCorredores.length} corredores | ${data.length} clientes | Margen: <strong>${formatMargen(grandComision, grandTransado)}</strong>`;
}

function toggleGroup(header) {
    header.classList.toggle('expanded');
    header.nextElementSibling.classList.toggle('show');
}

function expandAll() {
    document.querySelectorAll('.corredor-header').forEach(h => {
        h.classList.add('expanded');
        h.nextElementSibling.classList.add('show');
    });
}

function collapseAll() {
    document.querySelectorAll('.corredor-header').forEach(h => {
        h.classList.remove('expanded');
        h.nextElementSibling.classList.remove('show');
    });
}

function exportarExcel() {
    const year = document.getElementById('year').value;
    const params = new URLSearchParams({ year });
    window.location.href = `/reportes/margen/exportar?${params}`;
}

document.getElementById('year').addEventListener('change', function() {
    loadData();
});
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
