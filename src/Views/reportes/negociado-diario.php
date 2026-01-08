<?php
// src/Views/reportes/negociado-diario.php
ob_start();
$pageTitle = 'Negociado Diario';
?>

<div class="page-header mb-3">
    <h1><i class="fas fa-chart-line"></i> Negociado Diario</h1>
    <p class="text-muted">Vista resumida por trader con detalle matricial</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year"><i class="far fa-calendar"></i> Año:</label>
                <select name="year" id="year" class="form-select" style="width: 150px;" onchange="loadData()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top: 20px;">
                <button type="button" class="btn btn-primary" onclick="showMatricialView()" style="padding: 8px 20px;">
                    <i class="fas fa-eye"></i> Ver Detalle Completo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabla resumida -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Resumen por Trader</h3>
    </div>
    <div class="card-body">
        <div id="dataContainer">
            <div class="text-center">
                <div class="spinner"></div>
                <p class="text-muted mt-2"><i class="fas fa-sync fa-spin"></i> Cargando datos...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalle matricial -->
<div id="detalleModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 98%; max-height: 95vh;">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-th"></i> Negociado - Vista Matricial</h2>
            <button class="modal-close" onclick="closeDetailModal()">&times;</button>
        </div>

        <!-- Filtros dentro del modal -->
        <div class="modal-filters" style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
            <div class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
                <div>
                    <label for="filterMes"><i class="far fa-calendar-alt"></i> Mes:</label>
                    <select id="filterMes" class="form-select" style="width: 200px;" onchange="applyFilters()">
                        <option value="">Todos los meses</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>

                <div>
                    <label for="filterRueda"><i class="fas fa-circle-notch"></i> Rueda:</label>
                    <select id="filterRueda" class="form-select" style="width: 200px;" onchange="applyFilters()">
                        <option value="">Todas las ruedas</option>
                    </select>
                </div>

                <div>
                    <label for="filterCliente"><i class="fas fa-search"></i> Cliente:</label>
                    <input type="text" id="filterCliente" class="form-control" style="width: 300px;" placeholder="Buscar por nombre de cliente..." oninput="applyFilters()">
                </div>

                <div style="margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-body" style="overflow: auto; max-height: calc(95vh - 200px);">
            <div id="detalleContent">
                <div class="text-center">
                    <div class="spinner"></div>
                    <p class="text-muted mt-2">Cargando detalle...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
// Variables globales
let fullData = [];
let filteredData = [];
let allRuedas = [];

// Nombres de meses
const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

document.addEventListener('DOMContentLoaded', function() {
    loadData();

    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailModal();
        }
    });

    // Cerrar modal al hacer clic fuera
    document.getElementById('detalleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailModal();
        }
    });
});

async function loadData() {
    const year = document.getElementById('year').value;

    try {
        const response = await fetch(`/api/reportes/negociado-diario/traders?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.success) {
            renderSummaryTable(result.data);
        } else {
            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar datos</p>';
        }
    } catch (error) {
        console.error('Error loading data:', error);
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexión</p>';
    }
}

function renderSummaryTable(data) {
    if (data.length === 0) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-inbox"></i> No hay datos disponibles</p>';
        return;
    }

    let html = `
        <div class="table-wrapper">
            <table class="table table-striped">
                <thead style="background: linear-gradient(135deg, #2d3436 0%, #000000 100%); color: white;">
                    <tr>
                        <th><i class="fas fa-user-tie"></i> Trader</th>
                        <th class="text-right"><i class="fas fa-users"></i> Clientes</th>
                        <th class="text-right"><i class="fas fa-circle-notch"></i> Ruedas</th>
                        <th class="text-right"><i class="fas fa-dollar-sign"></i> Total Transado</th>
                        <th class="text-right"><i class="fas fa-coins"></i> Total Comisión</th>
                        <th class="text-right"><i class="fas fa-chart-line"></i> Margen %</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Calcular total general
    let totalGeneral = {
        clientes: 0,
        ruedas: 0,
        transado: 0,
        comision: 0,
        margen: 0
    };

    data.forEach((row, index) => {
        const totalTransado = parseFloat(row.total_transado) || 0;
        const totalComision = parseFloat(row.total_comision) || 0;
        const totalMargen = parseFloat(row.total_margen) || 0;
        const margenPct = totalTransado > 0 ? totalMargen / totalTransado : 0;

        totalGeneral.clientes += parseInt(row.total_clientes) || 0;
        totalGeneral.ruedas += parseInt(row.total_ruedas) || 0;
        totalGeneral.transado += totalTransado;
        totalGeneral.comision += totalComision;
        totalGeneral.margen += totalMargen;

        html += `
            <tr>
                <td><strong>${row.trader}</strong></td>
                <td class="text-right"><span class="badge" style="background: #27ae60; color: white; padding: 4px 10px; border-radius: 12px;">${row.total_clientes}</span></td>
                <td class="text-right">${row.total_ruedas}</td>
                <td class="text-right"><strong>${formatCurrency(totalTransado)}</strong></td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatCurrency(totalComision)}</td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatPercentage(margenPct)}</td>
            </tr>
        `;
    });

    // Fila de totales
    const margenGeneralPct = totalGeneral.transado > 0 ? totalGeneral.margen / totalGeneral.transado : 0;
    html += `
        <tr style="background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); color: white; font-weight: bold; font-size: 14px;">
            <td><i class="fas fa-calculator"></i> TOTAL GENERAL</td>
            <td class="text-right">${totalGeneral.clientes}</td>
            <td class="text-right">${totalGeneral.ruedas}</td>
            <td class="text-right">${formatCurrency(totalGeneral.transado)}</td>
            <td class="text-right">${formatCurrency(totalGeneral.comision)}</td>
            <td class="text-right">${formatPercentage(margenGeneralPct)}</td>
        </tr>
    `;

    html += `
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('dataContainer').innerHTML = html;
}

async function showMatricialView() {
    const year = document.getElementById('year').value;

    // Mostrar modal
    document.getElementById('detalleModal').style.display = 'flex';
    document.getElementById('detalleContent').innerHTML = `
        <div class="text-center">
            <div class="spinner"></div>
            <p class="text-muted mt-2">Cargando vista matricial...</p>
        </div>
    `;

    try {
        const response = await fetch(`/api/reportes/negociado-diario/matricial?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.success) {
            allRuedas = result.data.ruedas || [];
            fullData = result.data.data || [];
            filteredData = [...fullData];

            // Poblar filtro de ruedas
            populateRuedasFilter();

            // Renderizar vista matricial
            renderMatricialView();
        } else {
            document.getElementById('detalleContent').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar detalle</p>';
        }
    } catch (error) {
        console.error('Error loading matricial view:', error);
        document.getElementById('detalleContent').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexión</p>';
    }
}

function populateRuedasFilter() {
    let html = '<option value="">Todas las ruedas</option>';
    allRuedas.forEach(rueda => {
        html += `<option value="${rueda.rueda_no}">Rueda ${rueda.rueda_no}</option>`;
    });

    document.getElementById('filterRueda').innerHTML = html;
}

function applyFilters() {
    const mesFilter = document.getElementById('filterMes').value;
    const ruedaFilter = document.getElementById('filterRueda').value;
    const clienteFilter = document.getElementById('filterCliente').value.toLowerCase().trim();

    filteredData = fullData.filter(row => {
        if (mesFilter && row.mes_num != mesFilter) return false;
        if (ruedaFilter && row.rueda_no != ruedaFilter) return false;
        if (clienteFilter && !row.cliente.toLowerCase().includes(clienteFilter)) return false;
        return true;
    });

    renderMatricialView();
}

function clearFilters() {
    document.getElementById('filterMes').value = '';
    document.getElementById('filterRueda').value = '';
    document.getElementById('filterCliente').value = '';
    filteredData = [...fullData];
    renderMatricialView();
}

function renderMatricialView() {
    if (filteredData.length === 0) {
        document.getElementById('detalleContent').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-filter"></i> No hay datos con los filtros seleccionados</p>';
        return;
    }

    // Determinar qué ruedas mostrar basado en los filtros
    const mesFilter = document.getElementById('filterMes').value;
    const ruedaFilter = document.getElementById('filterRueda').value;

    let ruedasAMostrar = allRuedas;
    if (mesFilter) {
        ruedasAMostrar = allRuedas.filter(r => r.mes_num == mesFilter);
    }
    if (ruedaFilter) {
        ruedasAMostrar = allRuedas.filter(r => r.rueda_no == ruedaFilter);
    }

    // Agrupar datos por cliente
    const clientesMap = {};
    filteredData.forEach(row => {
        const key = `${row.nit}`;
        if (!clientesMap[key]) {
            clientesMap[key] = {
                nit: row.nit,
                nombre: row.cliente,
                corredor: row.corredor,
                ruedas: {}
            };
        }

        clientesMap[key].ruedas[row.rueda_no] = parseFloat(row.transado) || 0;
    });

    const clientes = Object.values(clientesMap);

    // Calcular título del mes si hay filtro
    let mesTitulo = '';
    if (mesFilter) {
        mesTitulo = `Total ${MESES[parseInt(mesFilter)]}`;
    } else {
        mesTitulo = 'Total general';
    }

    let html = `
        <div style="overflow-x: auto;">
            <table class="table table-striped" style="font-size: 11px; white-space: nowrap;">
                <thead style="background: #4472C4; color: white; position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="position: sticky; left: 0; background: #4472C4; z-index: 11; min-width: 250px;">NOMBRE</th>
    `;

    // Headers de ruedas
    ruedasAMostrar.forEach(rueda => {
        html += `<th class="text-center" style="min-width: 120px;">${rueda.rueda_no}</th>`;
    });

    html += `
                        <th class="text-right" style="background: #f0f0f0; color: #2d3436; font-weight: bold; min-width: 150px;">${mesTitulo}</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Totales por mes/general
    let totalMes = 0;
    let totalesPorRueda = {};

    clientes.forEach((cliente, idx) => {
        let totalCliente = 0;

        html += `
            <tr style="${idx % 2 === 0 ? 'background: #f8f9fa;' : ''}">
                <td style="position: sticky; left: 0; background: ${idx % 2 === 0 ? '#f8f9fa' : 'white'}; z-index: 1; font-weight: 500;">
                    ${cliente.nombre}
                </td>
        `;

        ruedasAMostrar.forEach(rueda => {
            const valor = cliente.ruedas[rueda.rueda_no] || 0;
            totalCliente += valor;

            if (!totalesPorRueda[rueda.rueda_no]) {
                totalesPorRueda[rueda.rueda_no] = 0;
            }
            totalesPorRueda[rueda.rueda_no] += valor;

            html += `
                <td class="text-right">
                    ${valor > 0 ? formatCurrency(valor) : '-'}
                </td>
            `;
        });

        totalMes += totalCliente;

        html += `
                <td class="text-right" style="background: #f0f0f0; font-weight: bold;">
                    ${formatCurrency(totalCliente)}
                </td>
            </tr>
        `;
    });

    // Fila de totales por rueda
    html += `
        <tr style="background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); color: white; font-weight: bold; position: sticky; bottom: 0;">
            <td style="position: sticky; left: 0; background: #27ae60; z-index: 11;">
                <i class="fas fa-calculator"></i> TOTAL
            </td>
    `;

    ruedasAMostrar.forEach(rueda => {
        html += `
            <td class="text-right">
                ${formatCurrency(totalesPorRueda[rueda.rueda_no] || 0)}
            </td>
        `;
    });

    html += `
            <td class="text-right" style="font-size: 13px;">
                ${formatCurrency(totalMes)}
            </td>
        </tr>
    `;

    html += `
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('detalleContent').innerHTML = html;
}

function closeDetailModal() {
    document.getElementById('detalleModal').style.display = 'none';
    fullData = [];
    filteredData = [];
    allRuedas = [];

    // Limpiar filtros
    document.getElementById('filterMes').value = '';
    document.getElementById('filterRueda').value = '';
    document.getElementById('filterCliente').value = '';
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
