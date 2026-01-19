<?php
// src/Views/reportes/negociado-diario.php
ob_start();
$pageTitle = 'Negociado Diario';
?>

<div class="page-header mb-3">
    <h1><i class="fas fa-chart-line"></i> Negociado Diario</h1>
    <p class="text-muted">Vista matricial por cliente y rueda</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year"><i class="far fa-calendar"></i> Anio:</label>
                <select name="year" id="year" class="form-select" style="width: 150px;" onchange="loadData()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

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
</div>

<!-- Detalle matricial -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-th"></i> Detalle matricial</h3>
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
});

async function loadData() {
    const year = document.getElementById('year').value;

    document.getElementById('dataContainer').innerHTML =
        '<div class="text-center"><div class="spinner"></div><p class="text-muted mt-2"><i class="fas fa-sync fa-spin"></i> Cargando datos...</p></div>';

    try {
        const response = await fetch(`/api/reportes/negociado-diario/matricial?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.success) {
            allRuedas = result.data.ruedas || [];
            fullData = result.data.data || [];
            filteredData = [...fullData];

            document.getElementById('filterMes').value = '';
            document.getElementById('filterRueda').value = '';
            document.getElementById('filterCliente').value = '';

            populateRuedasFilter();
            renderMatricialView();
        } else {
            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexion</p>';
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
        document.getElementById('dataContainer').innerHTML =
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

    document.getElementById('dataContainer').innerHTML = html;
}

</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';




