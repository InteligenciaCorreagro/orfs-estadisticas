<?php
// src/Views/trader/mis-transacciones.php
ob_start();
$pageTitle = 'Mis Transacciones';
?>

<div class="page-header mb-3">
    <h1><i class="fas fa-chart-line"></i> Mis Transacciones</h1>
    <p class="text-muted">Vista detallada de tus negociaciones - <?= e($traderName) ?></p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year"><i class="far fa-calendar-alt"></i> Año:</label>
                <select id="year" class="form-select" style="width: 150px;" onchange="loadData()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top: 20px;">
                <button type="button" id="btnVerDetalle" class="btn btn-primary" style="padding: 8px 20px;">
                    <i class="fas fa-eye"></i> Ver Detalle Completo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Resumen -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table"></i> Resumen de Mis Transacciones</h3>
    </div>
    <div class="card-body">
        <div id="dataContainer">
            <div class="text-center">
                <div class="spinner"></div>
                <p class="text-muted mt-2">Cargando datos...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalle matricial -->
<div id="detalleModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-th"></i> Detalle de Mis Transacciones</h2>
            <button class="modal-close" id="closeModalBtn" type="button">&times;</button>
        </div>

        <!-- Filtros dentro del modal -->
        <div class="modal-filters">
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

        <div class="modal-body">
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
    console.log('[Mis Transacciones] DOM loaded');

    // Asegurar que el modal esté oculto al cargar
    const modal = document.getElementById('detalleModal');
    if (modal) {
        modal.classList.remove('modal-show');
        console.log('[Mis Transacciones] Modal hidden on load');
    }

    // Event listener para el botón Ver Detalle Completo
    const btnVerDetalle = document.getElementById('btnVerDetalle');
    if (btnVerDetalle) {
        btnVerDetalle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[Mis Transacciones] Ver Detalle button clicked');
            showMatricialView(e);
        });
    }

    // Event listener para el botón de cerrar
    const closeBtn = document.getElementById('closeModalBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('[Mis Transacciones] Close button clicked');
            closeDetailModal();
        });
    }

    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('detalleModal');
            if (modal && modal.classList.contains('modal-show')) {
                console.log('[Mis Transacciones] ESC pressed, closing modal');
                closeDetailModal();
            }
        }
    });

    // Cerrar al hacer clic fuera del modal (en el overlay)
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                console.log('[Mis Transacciones] Clicked outside modal, closing');
                closeDetailModal();
            }
        });
    }

    // Prevenir que clicks dentro del modal container cierren el modal
    const modalContainer = modal?.querySelector('.modal-container');
    if (modalContainer) {
        modalContainer.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Cargar datos iniciales
    loadData();
});

async function loadData() {
    const year = document.getElementById('year').value;

    try {
        const response = await fetch(`/api/trader/mis-clientes?year=${year}`, {
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

    // Calcular totales
    let totalClientes = data.length;
    let totalTransado = 0;
    let totalComision = 0;

    data.forEach(row => {
        totalTransado += parseFloat(row.total_negociado) || 0;
        totalComision += parseFloat(row.total_comision) || 0;
    });

    let html = `
        <div class="row mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Mis Clientes</h3>
                    <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">${totalClientes}</p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Negociado</h3>
                    <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">${formatCurrency(totalTransado)}</p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Comisión</h3>
                    <p style="margin: 10px 0 0 0; font-size: 28px; font-weight: bold;">${formatCurrency(totalComision)}</p>
                </div>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table table-striped">
                <thead style="background: linear-gradient(135deg, #2d3436 0%, #000000 100%); color: white;">
                    <tr>
                        <th><i class="fas fa-user"></i> Cliente</th>
                        <th class="text-right"><i class="fas fa-dollar-sign"></i> Total Negociado</th>
                        <th class="text-right"><i class="fas fa-coins"></i> Comisión</th>
                        <th class="text-right"><i class="fas fa-chart-line"></i> Margen %</th>
                    </tr>
                </thead>
                <tbody>
    `;

    data.forEach((row, index) => {
        const negociado = parseFloat(row.total_negociado) || 0;
        const comision = parseFloat(row.total_comision) || 0;
        const margen = parseFloat(row.total_margen) || 0;
        const margenPct = negociado > 0 ? (margen / negociado) : 0;

        html += `
            <tr>
                <td><strong>${row.nombre}</strong></td>
                <td class="text-right">${formatCurrency(negociado)}</td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatCurrency(comision)}</td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatPercentage(margenPct)}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('dataContainer').innerHTML = html;
}

async function showMatricialView(event) {
    console.log('[Mis Transacciones] showMatricialView called');

    // Prevenir propagación del evento
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    const year = document.getElementById('year').value;
    const modal = document.getElementById('detalleModal');

    console.log('[Mis Transacciones] Year:', year);

    // Mostrar loading
    document.getElementById('detalleContent').innerHTML = `
        <div class="text-center">
            <div class="spinner"></div>
            <p class="text-muted mt-2">Cargando vista matricial...</p>
        </div>
    `;

    // Mostrar modal
    if (modal) {
        modal.classList.add('modal-show');
        console.log('[Mis Transacciones] Modal displayed');
    }

    try {
        const url = `/api/reportes/negociado-diario/matricial?year=${year}`;
        console.log('[Mis Transacciones] Fetching:', url);

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        console.log('[Mis Transacciones] Response status:', response.status);

        const result = await response.json();
        console.log('[Mis Transacciones] Response data:', result);

        if (result.success) {
            allRuedas = result.data.ruedas || [];
            fullData = result.data.data || [];
            filteredData = [...fullData];

            console.log('[Mis Transacciones] Ruedas count:', allRuedas.length);
            console.log('[Mis Transacciones] Data count:', fullData.length);

            // Poblar filtro de ruedas
            populateRuedasFilter();

            // Renderizar vista matricial
            renderMatricialView();
        } else {
            console.error('[Mis Transacciones] API returned error:', result.message);
            document.getElementById('detalleContent').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar detalle</p>';
        }
    } catch (error) {
        console.error('[Mis Transacciones] Error loading matricial view:', error);
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
    console.log('[Mis Transacciones] renderMatricialView called, filteredData length:', filteredData.length);

    if (filteredData.length === 0) {
        console.warn('[Mis Transacciones] No filtered data available');
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
    console.log('[Mis Transacciones] closeDetailModal called');
    const modal = document.getElementById('detalleModal');
    if (modal) {
        modal.classList.remove('modal-show');
        console.log('[Mis Transacciones] Modal closed');
    }

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
