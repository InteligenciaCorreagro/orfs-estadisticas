<?php
// src/Views/reportes/rueda.php
ob_start();
$pageTitle = 'Reporte Ruedas';
?>

<div class="page-header mb-3">
    <h1><i class="fas fa-circle-notch"></i> Reporte de Ruedas</h1>
    <p class="text-muted">Detalle completo por rueda específica de negociación</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year"><i class="far fa-calendar"></i> Año:</label>
                <select name="year" id="year" class="form-select" style="width: 150px;" onchange="loadRuedas()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="rueda"><i class="fas fa-list"></i> Rueda:</label>
                <select name="rueda" id="rueda" class="form-select" style="width: 250px;" onchange="loadDetalle()">
                    <option value="">Seleccione una rueda</option>
                </select>
            </div>

            <div style="margin-top: 20px;">
                <button type="button" class="btn btn-success" onclick="exportarRueda()" id="btnExportar" disabled>
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas de la rueda -->
<div id="estadisticasContainer"></div>

<!-- Detalle de la rueda -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table"></i> Detalle de Transacciones</h3>
    </div>
    <div class="card-body">
        <div id="dataContainer">
            <p class="text-center text-muted"><i class="fas fa-arrow-up"></i> Seleccione una rueda para ver el detalle</p>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRuedas();
});

async function loadRuedas() {
    const year = document.getElementById('year').value;
    
    try {
        const response = await fetch(`/api/reportes/rueda/listado?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('rueda');
            select.innerHTML = '<option value="">Seleccione una rueda</option>';
            
            result.data.forEach(rueda => {
                const option = document.createElement('option');
                option.value = rueda.rueda_no;
                option.textContent = `Rueda ${rueda.rueda_no} - ${rueda.fecha} (${formatCurrency(rueda.total_negociado)})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading ruedas:', error);
    }
}

async function loadDetalle() {
    const year = document.getElementById('year').value;
    const rueda = document.getElementById('rueda').value;
    
    if (!rueda) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-arrow-up"></i> Seleccione una rueda para ver el detalle</p>';
        document.getElementById('estadisticasContainer').innerHTML = '';
        document.getElementById('btnExportar').disabled = true;
        return;
    }

    document.getElementById('dataContainer').innerHTML =
        '<div class="text-center"><div class="spinner"></div><p class="text-muted mt-2"><i class="fas fa-sync fa-spin"></i> Cargando datos...</p></div>';
    
    try {
        // Cargar estadísticas
        const statsResponse = await fetch(`/api/reportes/rueda/${rueda}/estadisticas?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const statsResult = await statsResponse.json();
        
        if (statsResult.success) {
            renderEstadisticas(statsResult.data);
        }
        
        // Cargar detalle
        const response = await fetch(`/api/reportes/rueda/${rueda}?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            renderDetalle(result.data);
            document.getElementById('btnExportar').disabled = false;
        } else {
            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexión</p>';
    }
}

function renderEstadisticas(stats) {
    const html = `
        <div class="row mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="far fa-calendar-alt"></i> Fecha
                    </h4>
                    <p style="margin: 10px 0 0 0; font-size: 20px; font-weight: bold;">
                        ${stats.fecha || 'N/A'}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-exchange-alt"></i> Transacciones
                    </h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${(stats.total_transacciones || 0).toLocaleString('es-CO')}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-dollar-sign"></i> Total Transado
                    </h4>
                    <p style="margin: 10px 0 0 0; font-size: 22px; font-weight: bold;">
                        ${formatCurrency(stats.total_transado)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border: none;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-percentage"></i> Total Comisión
                    </h4>
                    <p style="margin: 10px 0 0 0; font-size: 22px; font-weight: bold;">
                        ${formatCurrency(stats.total_comision)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border: none;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users"></i> Corredores
                    </h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${stats.total_corredores || 0}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; border: none;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-map-marker-alt"></i> Ciudades
                    </h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${stats.total_ciudades || 0}
                    </p>
                </div>
            </div>
        </div>
    `;

    document.getElementById('estadisticasContainer').innerHTML = html;
}

function renderDetalle(data) {
    if (data.length === 0) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-muted"><i class="fas fa-inbox"></i> No hay datos disponibles</p>';
        return;
    }

    let html = `
        <div class="table-wrapper">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><i class="fas fa-map-marker-alt"></i> Ciudad</th>
                        <th><i class="fas fa-user-tie"></i> Corredor</th>
                        <th><i class="fas fa-building"></i> Cliente</th>
                        <th><i class="fas fa-id-card"></i> NIT</th>
                        <th class="text-right"><i class="fas fa-dollar-sign"></i> Transado</th>
                        <th class="text-right"><i class="fas fa-percentage"></i> Comisión</th>
                        <th class="text-right"><i class="fas fa-chart-line"></i> Margen</th>
                    </tr>
                </thead>
                <tbody>
    `;

    data.forEach(row => {
        html += `
            <tr>
                <td><i class="fas fa-map-pin" style="color: #95A5A6; margin-right: 6px;"></i>${row.ciudad || 'N/A'}</td>
                <td><i class="fas fa-user" style="color: var(--primary-color); margin-right: 6px;"></i>${row.corredor}</td>
                <td>${row.cliente}</td>
                <td><span style="font-family: monospace; background: #F8F9FA; padding: 2px 8px; border-radius: 4px;">${row.nit}</span></td>
                <td class="text-right"><strong>${formatCurrency(row.transado)}</strong></td>
                <td class="text-right" style="color: var(--success-color);"><strong>${formatCurrency(row.comision)}</strong></td>
                <td class="text-right" style="color: var(--primary-color);"><strong>${formatCurrency(row.margen)}</strong></td>
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

function exportarRueda() {
    const year = document.getElementById('year').value;
    const rueda = document.getElementById('rueda').value;
    
    if (!rueda) return;
    
    window.location.href = `/reportes/rueda/${rueda}/exportar?year=${year}`;
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';