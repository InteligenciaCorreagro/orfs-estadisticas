<?php
// src/Views/reportes/negociado-diario.php
ob_start();
$pageTitle = 'Negociado Diario';
?>

<div class="page-header mb-3">
    <h1><i class="fas fa-chart-line"></i> Negociado Diario</h1>
    <p class="text-muted">Vista resumida por trader con detalle mensual</p>
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

<!-- Modal de detalle mensual -->
<div id="detalleModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 95%; max-height: 90vh;">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-user-tie"></i> Detalle Mensual del Trader</h2>
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

                <div style="margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-body" style="overflow-y: auto; max-height: calc(90vh - 220px);">
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
let currentTrader = null;

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
                        <th class="text-center"><i class="fas fa-eye"></i> Acción</th>
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
            <tr class="clickable-row" style="cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e8f5e9'" onmouseout="this.style.background=''">
                <td><strong>${row.trader}</strong></td>
                <td class="text-right"><span class="badge" style="background: #27ae60; color: white; padding: 4px 10px; border-radius: 12px;">${row.total_clientes}</span></td>
                <td class="text-right">${row.total_ruedas}</td>
                <td class="text-right"><strong>${formatCurrency(totalTransado)}</strong></td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatCurrency(totalComision)}</td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatPercentage(margenPct)}</td>
                <td class="text-center">
                    <button class="btn btn-sm" style="background: #27ae60; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer;" onclick="showDetail('${row.trader}')">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </td>
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
            <td></td>
        </tr>
    `;

    html += `
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('dataContainer').innerHTML = html;
}

async function showDetail(trader) {
    currentTrader = trader;
    const year = document.getElementById('year').value;

    // Mostrar modal
    document.getElementById('detalleModal').style.display = 'flex';
    document.getElementById('modalTitle').innerHTML = `<i class="fas fa-user-tie"></i> Detalle Mensual - ${trader}`;
    document.getElementById('detalleContent').innerHTML = `
        <div class="text-center">
            <div class="spinner"></div>
            <p class="text-muted mt-2">Cargando detalle...</p>
        </div>
    `;

    try {
        const response = await fetch(`/api/reportes/negociado-diario/trader/${encodeURIComponent(trader)}/detalle?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.success) {
            fullData = result.data;
            filteredData = [...fullData];

            // Poblar filtro de ruedas
            populateRuedasFilter();

            // Renderizar detalle
            renderDetail();
        } else {
            document.getElementById('detalleContent').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar detalle</p>';
        }
    } catch (error) {
        console.error('Error loading detail:', error);
        document.getElementById('detalleContent').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexión</p>';
    }
}

function populateRuedasFilter() {
    const ruedas = [...new Set(fullData.map(row => row.rueda_no))].sort((a, b) => a - b);

    let html = '<option value="">Todas las ruedas</option>';
    ruedas.forEach(rueda => {
        html += `<option value="${rueda}">Rueda ${rueda}</option>`;
    });

    document.getElementById('filterRueda').innerHTML = html;
}

function applyFilters() {
    const mesFilter = document.getElementById('filterMes').value;
    const ruedaFilter = document.getElementById('filterRueda').value;

    filteredData = fullData.filter(row => {
        if (mesFilter && row.mes_num != mesFilter) return false;
        if (ruedaFilter && row.rueda_no != ruedaFilter) return false;
        return true;
    });

    renderDetail();
}

function clearFilters() {
    document.getElementById('filterMes').value = '';
    document.getElementById('filterRueda').value = '';
    filteredData = [...fullData];
    renderDetail();
}

function renderDetail() {
    if (filteredData.length === 0) {
        document.getElementById('detalleContent').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-filter"></i> No hay datos con los filtros seleccionados</p>';
        return;
    }

    // Agrupar por cliente y mes
    const grouped = {};

    filteredData.forEach(row => {
        const key = `${row.nit}|${row.cliente}`;
        if (!grouped[key]) {
            grouped[key] = {
                nit: row.nit,
                cliente: row.cliente,
                meses: {}
            };
        }

        const mesKey = row.mes_num;
        if (!grouped[key].meses[mesKey]) {
            grouped[key].meses[mesKey] = {
                transado: 0,
                comision: 0,
                margen: 0,
                ruedas: []
            };
        }

        grouped[key].meses[mesKey].transado += parseFloat(row.transado) || 0;
        grouped[key].meses[mesKey].comision += parseFloat(row.comision) || 0;
        grouped[key].meses[mesKey].margen += parseFloat(row.margen) || 0;
        grouped[key].meses[mesKey].ruedas.push(row.rueda_no);
    });

    let html = `
        <div class="table-wrapper">
            <table class="table table-striped" style="font-size: 12px;">
                <thead style="background: #27ae60; color: white; position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th style="position: sticky; left: 0; background: #27ae60; z-index: 2;">Cliente</th>
                        <th style="position: sticky; left: 250px; background: #27ae60; z-index: 2;">NIT</th>
                        <th>Mes</th>
                        <th class="text-right">Transado</th>
                        <th class="text-right">Comisión $</th>
                        <th class="text-right">Margen %</th>
                        <th>Ruedas</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Totales generales
    let totalGeneral = {
        transado: 0,
        comision: 0,
        margen: 0
    };

    Object.values(grouped).forEach(clienteData => {
        let clienteTotalTransado = 0;
        let clienteTotalComision = 0;
        let clienteTotalMargen = 0;

        // Ordenar meses
        const mesesOrdenados = Object.keys(clienteData.meses).sort((a, b) => parseInt(a) - parseInt(b));

        mesesOrdenados.forEach((mes, index) => {
            const mesData = clienteData.meses[mes];
            const margenPct = mesData.transado > 0 ? mesData.margen / mesData.transado : 0;

            clienteTotalTransado += mesData.transado;
            clienteTotalComision += mesData.comision;
            clienteTotalMargen += mesData.margen;

            html += `
                <tr>
                    ${index === 0 ? `<td rowspan="${mesesOrdenados.length}" style="position: sticky; left: 0; background: white; z-index: 1; vertical-align: top; font-weight: bold;">${clienteData.cliente}</td>` : ''}
                    ${index === 0 ? `<td rowspan="${mesesOrdenados.length}" style="position: sticky; left: 250px; background: white; z-index: 1; vertical-align: top; font-family: monospace; font-size: 11px;">${clienteData.nit}</td>` : ''}
                    <td><i class="far fa-calendar-alt"></i> ${MESES[parseInt(mes)]}</td>
                    <td class="text-right">${formatCurrency(mesData.transado)}</td>
                    <td class="text-right" style="color: #27ae60; font-weight: 600;">${formatCurrency(mesData.comision)}</td>
                    <td class="text-right" style="color: #27ae60; font-weight: 600;">${formatPercentage(margenPct)}</td>
                    <td><span style="font-size: 10px; color: #7f8c8d;">${[...new Set(mesData.ruedas)].sort((a,b) => a-b).join(', ')}</span></td>
                </tr>
            `;
        });

        // Subtotal por cliente
        const clienteMargenPct = clienteTotalTransado > 0 ? clienteTotalMargen / clienteTotalTransado : 0;
        html += `
            <tr style="background: #e8f5e9; font-weight: bold;">
                <td colspan="3" class="text-right"><i class="fas fa-calculator"></i> Subtotal ${clienteData.cliente}</td>
                <td class="text-right">${formatCurrency(clienteTotalTransado)}</td>
                <td class="text-right" style="color: #27ae60;">${formatCurrency(clienteTotalComision)}</td>
                <td class="text-right" style="color: #27ae60;">${formatPercentage(clienteMargenPct)}</td>
                <td></td>
            </tr>
        `;

        totalGeneral.transado += clienteTotalTransado;
        totalGeneral.comision += clienteTotalComision;
        totalGeneral.margen += clienteTotalMargen;
    });

    // Total general
    const totalMargenPct = totalGeneral.transado > 0 ? totalGeneral.margen / totalGeneral.transado : 0;
    html += `
        <tr style="background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); color: white; font-weight: bold; font-size: 13px;">
            <td colspan="3" class="text-right"><i class="fas fa-calculator"></i> TOTAL GENERAL</td>
            <td class="text-right">${formatCurrency(totalGeneral.transado)}</td>
            <td class="text-right">${formatCurrency(totalGeneral.comision)}</td>
            <td class="text-right">${formatPercentage(totalMargenPct)}</td>
            <td></td>
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
    currentTrader = null;
    fullData = [];
    filteredData = [];

    // Limpiar filtros
    document.getElementById('filterMes').value = '';
    document.getElementById('filterRueda').value = '';
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
