<?php
// src/Views/reportes/rueda.php
ob_start();
$pageTitle = 'Reporte Ruedas';
?>

<style>
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

.corredor-group {
    margin-bottom: 8px;
}

.corredor-header {
    background: linear-gradient(135deg, #4472C4 0%, #2E5090 100%);
    color: white;
    padding: 12px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 6px;
    transition: all 0.3s ease;
    user-select: none;
}

.corredor-header:hover {
    background: linear-gradient(135deg, #5a8ad8 0%, #3d65a8 100%);
}

.corredor-header.expanded {
    border-radius: 6px 6px 0 0;
}

.corredor-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.corredor-city {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    opacity: 0.85;
}

.corredor-stats {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.stat-item {
    background: rgba(255,255,255,0.2);
    padding: 4px 10px;
    border-radius: 12px;
}

.toggle-icon {
    transition: transform 0.3s ease;
    font-size: 12px;
}

.corredor-header.expanded .toggle-icon {
    transform: rotate(90deg);
}

.corredor-content {
    display: none;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 6px 6px;
}

.corredor-content.show {
    display: block;
}

.table-scroll-wrapper {
    overflow-x: auto;
    width: 100%;
    display: block;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.data-table th,
.data-table td {
    white-space: nowrap;
    padding: 8px 10px;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f8f9fa;
}

.data-table tbody tr:hover {
    background: #f0f7ff;
}

.data-table .total-row {
    background: #e8f4e8;
    font-weight: bold;
}

.control-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
    flex-wrap: wrap;
    align-items: center;
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

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }

    .corredor-stats {
        display: none;
    }
}
</style>

<div class="page-header mb-3">
    <h1><i class="fas fa-circle-notch"></i> Reporte de Ruedas</h1>
    <p class="text-muted">Detalle completo por rueda específica de negociación</p>
</div>

<!-- Filtros -->
<div class="card mb-3 filter-card" style="overflow: visible;">
    <div class="card-body" style="overflow: visible;">
        <div class="filter-row">
            <div class="filter-group">
                <label for="year"><i class="far fa-calendar"></i> Año:</label>
                <select name="year" id="year" class="form-select" style="width: 120px;" onchange="loadRuedas()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fas fa-list"></i> Ruedas:</label>
                <div class="multi-select-container" id="ruedasContainer">
                    <button type="button" class="multi-select-btn" onclick="toggleDropdown('ruedas', event)">
                        <span id="ruedasLabel">Ninguna</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="multi-select-dropdown" id="ruedasDropdown">
                        <div class="select-all">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="ruedasSelectAll" onchange="toggleAllRuedas()">
                                <strong>Seleccionar todas</strong>
                            </label>
                        </div>
                        <div id="ruedasOptions"></div>
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <label>&nbsp;</label>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarRueda()" id="btnExportar" disabled>
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de la rueda -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table"></i> Detalle de Transacciones</h3>
    </div>
    <div class="card-body">
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
            <p class="text-center text-muted"><i class="fas fa-arrow-up"></i> Seleccione una o varias ruedas para ver el detalle</p>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
let allRuedas = [];
let selectedRuedas = [];
let ruedasMap = new Map();

document.addEventListener('DOMContentLoaded', function() {
    loadRuedas();

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.multi-select-container')) {
            document.querySelectorAll('.multi-select-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
});

function toggleDropdown(type, event) {
    if (event) {
        event.stopPropagation();
    }
    const dropdown = document.getElementById(type + 'Dropdown');
    const isOpen = dropdown.classList.contains('show');

    document.querySelectorAll('.multi-select-dropdown').forEach(d => d.classList.remove('show'));

    if (!isOpen) {
        dropdown.classList.add('show');
    }
}

function toggleAllRuedas() {
    const checked = document.getElementById('ruedasSelectAll').checked;
    document.querySelectorAll('#ruedasOptions input').forEach(cb => cb.checked = checked);
    updateRuedasSelection();
}

function updateRuedasSelection(shouldLoad = false) {
    const checkboxes = document.querySelectorAll('#ruedasOptions input:checked');
    selectedRuedas = Array.from(checkboxes).map(cb => cb.value);

    const label = document.getElementById('ruedasLabel');
    if (selectedRuedas.length === 0) {
        label.textContent = 'Ninguna';
    } else if (allRuedas.length > 0 && selectedRuedas.length === allRuedas.length) {
        label.textContent = 'Todas las ruedas';
    } else {
        label.textContent = selectedRuedas.length === 1
            ? '1 rueda'
            : `${selectedRuedas.length} ruedas`;
    }

    document.getElementById('ruedasSelectAll').checked =
        allRuedas.length > 0 && selectedRuedas.length === allRuedas.length;
    document.getElementById('btnExportar').disabled = selectedRuedas.length !== 1;

    if (shouldLoad) {
        loadDetalle();
    }
}

function initRuedasOptions() {
    const container = document.getElementById('ruedasOptions');
    container.innerHTML = allRuedas.map(rueda => {
        const key = String(rueda.rueda_no);
        const label = `Rueda ${rueda.rueda_no} - ${rueda.fecha}`;
        const checked = selectedRuedas.includes(key) ? 'checked' : '';
        return `
            <label class="option">
                <input type="checkbox" value="${key}" ${checked} onchange="updateRuedasSelection()">
                ${label}
            </label>
        `;
    }).join('');
    updateRuedasSelection();
}

function applyFilters() {
    loadDetalle();
}

async function loadRuedas() {
    const year = document.getElementById('year').value;
    
    try {
        const response = await fetch(`/api/reportes/rueda/listado?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            allRuedas = result.data || [];
            ruedasMap = new Map(allRuedas.map(rueda => [String(rueda.rueda_no), rueda]));
            selectedRuedas = [];
            initRuedasOptions();

            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-muted"><i class="fas fa-arrow-up"></i> Seleccione una o varias ruedas para ver el detalle</p>';
            document.getElementById('totalInfo').textContent = '';
        }
    } catch (error) {
        console.error('Error loading ruedas:', error);
    }
}

async function fetchDetalleRueda(ruedaNo, year) {
    const response = await fetch(`/api/reportes/rueda/${ruedaNo}?year=${year}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error('Error al cargar datos');
    }

    const meta = ruedasMap.get(String(ruedaNo)) || {};

    return (result.data || []).map(row => ({
        ...row,
        rueda_no: ruedaNo,
        fecha: meta.fecha || ''
    }));
}

async function loadDetalle() {
    const year = document.getElementById('year').value;
    const ruedas = [...selectedRuedas];
    
    if (ruedas.length === 0) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-arrow-up"></i> Seleccione una o varias ruedas para ver el detalle</p>';
        document.getElementById('btnExportar').disabled = true;
        return;
    }

    document.getElementById('btnExportar').disabled = ruedas.length !== 1;
    document.getElementById('dataContainer').innerHTML =
        '<div class="text-center"><div class="spinner"></div><p class="text-muted mt-2"><i class="fas fa-sync fa-spin"></i> Cargando datos...</p></div>';
    
    try {
        const detalleData = await Promise.all(ruedas.map(ruedaNo => fetchDetalleRueda(ruedaNo, year)));
        const merged = detalleData.flat();
        renderDetalle(merged);
    } catch (error) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexion</p>';
    }
}

function renderDetalle(data) {
    if (data.length === 0) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-inbox"></i> No hay datos disponibles</p>';
        const totalInfo = document.getElementById('totalInfo');
        if (totalInfo) {
            totalInfo.textContent = '';
        }
        return;
    }

    const ruedasUnicas = new Set(data.map(row => row.rueda_no)).size;
    const showRueda = ruedasUnicas > 1;
    const labelColspan = showRueda ? 4 : 2;

    const grouped = {};
    data.forEach(row => {
        const ciudad = row.ciudad || 'N/A';
        const key = `${row.corredor}|${ciudad}`;
        if (!grouped[key]) {
            grouped[key] = {
                corredor: row.corredor,
                ciudad,
                clientes: [],
                totalTransado: 0,
                totalComision: 0,
                totalMargen: 0
            };
        }
        grouped[key].clientes.push(row);
        grouped[key].totalTransado += parseFloat(row.transado) || 0;
        grouped[key].totalComision += parseFloat(row.comision) || 0;
        grouped[key].totalMargen += parseFloat(row.margen) || 0;
    });

    const grupos = Object.values(grouped).sort((a, b) => b.totalTransado - a.totalTransado);

    let html = '';
    grupos.forEach(group => {
        const avgComision = group.totalTransado ? (group.totalComision / group.totalTransado) : 0;
        const avgMargen = group.totalTransado ? (group.totalMargen / group.totalTransado) : 0;
        group.clientes.sort((a, b) => {
            if (showRueda) {
                const ruedaDiff = Number(a.rueda_no) - Number(b.rueda_no);
                if (ruedaDiff !== 0) {
                    return ruedaDiff;
                }
            }
            return a.cliente.localeCompare(b.cliente);
        });

        html += `
            <div class="corredor-group">
                <div class="corredor-header" onclick="toggleGroup(this)">
                    <div class="corredor-info">
                        <i class="fas fa-chevron-right toggle-icon"></i>
                        <strong>${group.corredor}</strong>
                        <span class="corredor-city"><i class="fas fa-map-pin"></i>${group.ciudad}</span>
                    </div>
                    <div class="corredor-stats">
                        <span class="stat-item"><i class="fas fa-building"></i> ${group.clientes.length} clientes</span>
                        <span class="stat-item"><i class="fas fa-dollar-sign"></i> ${formatCurrency(group.totalTransado)}</span>
                        <span class="stat-item"><i class="fas fa-percentage"></i> ${formatPercentage(avgComision)}</span>
                    </div>
                </div>
                <div class="corredor-content">
                    <div class="table-scroll-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    ${showRueda ? '<th>Rueda</th><th>Fecha</th>' : ''}
                                    <th>Cliente</th>
                                    <th>NIT</th>
                                    <th class="text-right">Transado</th>
                                    <th class="text-right">Comision</th>
                                    <th class="text-right">Margen</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        group.clientes.forEach(cliente => {
            html += `
                <tr>
                    ${showRueda ? `<td>${cliente.rueda_no}</td><td>${cliente.fecha || ''}</td>` : ''}
                    <td>${cliente.cliente}</td>
                    <td>${cliente.nit}</td>
                    <td class="text-right">${formatCurrency(cliente.transado)}</td>
                    <td class="text-right">${formatCurrency(cliente.comision)}</td>
                    <td class="text-right">${formatCurrency(cliente.margen)}</td>
                </tr>
            `;
        });

        html += `
                            <tr class="total-row">
                                <td colspan="${labelColspan}">Total ${group.corredor}</td>
                                <td class="text-right">${formatCurrency(group.totalTransado)}</td>
                                <td class="text-right">${formatCurrency(group.totalComision)}</td>
                                <td class="text-right">${formatCurrency(group.totalMargen)}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    });

    document.getElementById('dataContainer').innerHTML = html;

    const totalTransado = grupos.reduce((sum, g) => sum + g.totalTransado, 0);
    const totalMargen = grupos.reduce((sum, g) => sum + g.totalMargen, 0);
    const totalClientes = new Set(data.map(row => `${row.nit}|${row.cliente}`)).size;
    const totalInfo = document.getElementById('totalInfo');
    if (totalInfo) {
        const margenPct = totalTransado ? formatPercentage(totalMargen / totalTransado) : '0.00000%';
        const ruedasInfo = showRueda ? `${ruedasUnicas} ruedas | ` : '';
        totalInfo.innerHTML = `<i class="fas fa-chart-line"></i> ${ruedasInfo}${grupos.length} corredores | ${totalClientes} clientes | Margen: <strong>${margenPct}</strong>`;
    }
}

function toggleGroup(header) {
    header.classList.toggle('expanded');
    const content = header.nextElementSibling;
    content.classList.toggle('show');
}

function expandAll() {
    document.querySelectorAll('.corredor-header').forEach(header => {
        header.classList.add('expanded');
        header.nextElementSibling.classList.add('show');
    });
}

function collapseAll() {
    document.querySelectorAll('.corredor-header').forEach(header => {
        header.classList.remove('expanded');
        header.nextElementSibling.classList.remove('show');
    });
}

function exportarRueda() {
    const year = document.getElementById('year').value;
    const ruedas = [...selectedRuedas];
    
    if (ruedas.length !== 1) return;
    
    window.location.href = `/reportes/rueda/${ruedas[0]}/exportar?year=${year}`;
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';






