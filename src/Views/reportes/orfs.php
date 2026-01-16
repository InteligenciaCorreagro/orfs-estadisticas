<?php
// src/Views/reportes/orfs.php
ob_start();
$pageTitle = 'Reporte ORFS';
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
    background: linear-gradient(135deg, #4472C4 0%, #2E5090 100%);
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
    background: linear-gradient(135deg, #5a8ad8 0%, #3d65a8 100%);
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
    gap: 20px;
    font-size: 13px;
}

.corredor-header .stat-item {
    background: rgba(255,255,255,0.2);
    padding: 4px 10px;
    border-radius: 12px;
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
    font-size: 12px;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.corredor-content th,
.corredor-content td {
    white-space: nowrap;
    padding: 8px 10px;
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
    z-index: 99999;
    border-right: 2px solid #ddd;
}

.corredor-content td:nth-child(2),
.corredor-content th:nth-child(2) {
    position: sticky;
    left: 80px;
    background: white;
    z-index: 99999;
    border-right: 2px solid #ddd;
}

.corredor-content th:first-child,
.corredor-content th:nth-child(2) {
    background: #f8f9fa;
    z-index: 99999;
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
.filter-card {
    position: relative;
    z-index: 200000;
    overflow: visible;
}

.filter-card .card-body {
    overflow: visible;
}

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
    position: relative;
    z-index: 99999;
}

.filter-group label {
    font-weight: 600;
    font-size: 13px;
    color: #333;
}

.multi-select-container {
    position: relative;
    z-index: 99999;
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
    border-color: #4472C4;
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
    z-index: 200001;
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
    padding: 8px 10px;
    text-align: right;
    border-bottom: 1px solid #eee;
}

.data-table th:first-child,
.data-table td:first-child {
    text-align: left;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 99999;
}

.data-table th:nth-child(2),
.data-table td:nth-child(2) {
    text-align: left;
    position: sticky;
    left: 80px;
    background: #fff;
    z-index: 99998;
}

.data-table thead th:first-child,
.data-table thead th:nth-child(2) {
    background: #f8f9fa;
    z-index: 100000;
}

.data-table tbody tr:hover {
    background: #f0f7ff;
}

.data-table .total-column {
    background: #f0f0f0;
    font-weight: bold;
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
    <h1><i class="fas fa-chart-bar"></i> Reporte ORFS</h1>
    <p class="text-muted">Vista mensual por corredor y cliente</p>
</div>

<!-- Filtros -->
<div class="card mb-3 filter-card" style="overflow: visible;">
    <div class="card-body" style="overflow: visible;">
        <div class="filter-row" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 20px;">
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
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('meses', event)">
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
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('corredores', event)">
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
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('clientes', event)">
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
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
let lastVisibleClientes = [];

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

function toggleDropdown(type, event) {
    if (event) {
        event.stopPropagation();
    }
    const dropdown = document.getElementById(type + 'Dropdown');
    const isOpen = dropdown.classList.contains('show');

    // Cerrar todos
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

    document.getElementById('corredoresSelectAll').checked =
        allCorredores.length > 0 && selectedCorredores.length === allCorredores.length;

    // Actualizar lista de clientes según corredores seleccionados
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

    document.getElementById('clientesSelectAll').checked =
        visibleClientes.length > 0 && selectedClientes.length === visibleClientes.length;
}

function getVisibleClientes() {
    if (selectedCorredores.length === 0) {
        return [];
    }
    if (selectedCorredores.length === allCorredores.length) {
        return allClientes;
    }
    return [...new Set(allData.filter(d => selectedCorredores.includes(d.corredor)).map(d => d.nit + '|' + d.cliente))];
}

function updateClientesOptions() {
    const visibleClientes = getVisibleClientes();
    const container = document.getElementById('clientesOptions');

    const previousSelected = [...selectedClientes];
    const previousVisible = [...lastVisibleClientes];
    const hadAllVisibleSelected = previousVisible.length > 0 &&
        previousSelected.length === previousVisible.length;
    const shouldSelectAll = visibleClientes.length > 0 &&
        (previousVisible.length === 0 || previousSelected.length === allClientes.length || hadAllVisibleSelected);
    const allowSelectNew = previousSelected.length > 0 || previousVisible.length === 0;

    container.innerHTML = visibleClientes.map(c => {
        const [nit, nombre] = c.split('|');
        const wasSelected = previousSelected.includes(c);
        const isNewVisible = !previousVisible.includes(c);
        const isChecked = (shouldSelectAll || wasSelected || (allowSelectNew && isNewVisible)) ? 'checked' : '';
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
    lastVisibleClientes = [...visibleClientes];

    updateClientesSelection();
}

async function loadData() {
    const year = document.getElementById('year').value;

    try {
        const params = new URLSearchParams({ year });

        const response = await fetch(`/api/reportes/orfs?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.success) {
            allData = result.data;

            // Extraer corredores y clientes únicos
            allCorredores = [...new Set(allData.map(d => d.corredor))].sort();
            allClientes = [...new Set(allData.map(d => d.nit + '|' + d.cliente))].sort((a, b) => {
                const nameA = a.split('|')[1];
                const nameB = b.split('|')[1];
                return nameA.localeCompare(nameB);
            });

            selectedCorredores = [...allCorredores];
            selectedClientes = [...allClientes];

            // Inicializar opciones de filtros
            initCorredoresOptions();
            initClientesOptions();

            applyFilters();
        } else {
            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-danger">Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger">Error de conexión</p>';
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
    // Filtrar datos
    filteredData = allData.filter(row => {
        const clienteKey = row.nit + '|' + row.cliente;
        return selectedCorredores.includes(row.corredor) && selectedClientes.includes(clienteKey);
    });

    renderGroupedTable(filteredData);
}

function getRowTotal(row) {
    return selectedMeses.reduce((sum, mes) => sum + parseFloat(row[mes] || 0), 0);
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
        corredorTotals[corredor] = {
            clientes: grouped[corredor].length,
            total: grouped[corredor].reduce((sum, r) => sum + getRowTotal(r), 0)
        };
    });

    // Ordenar corredores por total
    const sortedCorredores = Object.keys(grouped).sort((a, b) =>
        corredorTotals[b].total - corredorTotals[a].total
    );

    // Meses filtrados
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
                        <span class="stat-item"><i class="fas fa-dollar-sign"></i> ${formatCurrency(stats.total)}</span>
                    </div>
                </div>
                <div class="corredor-content">
                    <div class="table-scroll-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width: 80px;">NIT</th>
                                <th style="min-width: 200px;">Cliente</th>
                                ${visibleMeses.map(m => `<th>${m.label}</th>`).join('')}
                                <th class="total-column">Total</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        rows.forEach(row => {
            const rowTotal = getRowTotal(row);
            html += `
                <tr>
                    <td>${row.nit}</td>
                    <td>${row.cliente}</td>
                    ${visibleMeses.map(m => `<td>${formatCurrency(row[m.key])}</td>`).join('')}
                    <td class="total-column">${formatCurrency(rowTotal)}</td>
                </tr>
            `;
        });

        // Fila de totales del corredor
        html += `
                <tr style="background: #e8f4e8; font-weight: bold;">
                    <td colspan="2">Total ${corredor}</td>
                    ${visibleMeses.map(m => {
                        const mesTotal = rows.reduce((sum, r) => sum + parseFloat(r[m.key] || 0), 0);
                        return `<td>${formatCurrency(mesTotal)}</td>`;
                    }).join('')}
                    <td class="total-column">${formatCurrency(stats.total)}</td>
                </tr>
            </tbody>
                    </table>
                    </div>
                </div>
            </div>
        `;
    });

    document.getElementById('dataContainer').innerHTML = html;

    // Actualizar info total
    const totalGeneral = Object.values(corredorTotals).reduce((sum, s) => sum + s.total, 0);
    const totalClientes = data.length;
    document.getElementById('totalInfo').innerHTML =
        `<i class="fas fa-chart-line"></i> ${sortedCorredores.length} corredores | ${totalClientes} clientes | Total: <strong>${formatCurrency(totalGeneral)}</strong>`;
}

function toggleGroup(header) {
    header.classList.toggle('expanded');
    const content = header.nextElementSibling;
    content.classList.toggle('show');
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
    window.location.href = `/reportes/orfs/exportar?${params}`;
}

// Recargar al cambiar año
document.getElementById('year').addEventListener('change', function() {
    loadData();
});
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
