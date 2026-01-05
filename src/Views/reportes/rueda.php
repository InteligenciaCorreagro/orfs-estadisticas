<?php
// src/Views/reportes/rueda.php
ob_start();
$pageTitle = 'Reporte Ruedas';
?>

<div class="page-header mb-3">
    <h1>Reporte de Ruedas</h1>
    <p class="text-muted">Detalle por rueda específica</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year">Año:</label>
                <select name="year" id="year" class="form-select" style="width: 150px;" onchange="loadRuedas()">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="rueda">Rueda:</label>
                <select name="rueda" id="rueda" class="form-select" style="width: 200px;" onchange="loadDetalle()">
                    <option value="">Seleccione una rueda</option>
                </select>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="button" class="btn btn-success" onclick="exportarRueda()" id="btnExportar" disabled>
                    📥 Exportar Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas de la rueda -->
<div id="estadisticasContainer"></div>

<!-- Detalle de la rueda -->
<div class="card">
    <div class="card-body">
        <div id="dataContainer">
            <p class="text-center text-muted">Seleccione una rueda para ver el detalle</p>
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
            '<p class="text-center text-muted">Seleccione una rueda para ver el detalle</p>';
        document.getElementById('estadisticasContainer').innerHTML = '';
        document.getElementById('btnExportar').disabled = true;
        return;
    }
    
    document.getElementById('dataContainer').innerHTML = 
        '<div class="text-center"><div class="spinner"></div></div>';
    
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
                '<p class="text-center text-danger">Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML = 
            '<p class="text-center text-danger">Error de conexión</p>';
    }
}

function renderEstadisticas(stats) {
    const html = `
        <div class="row mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Fecha</h4>
                    <p style="margin: 10px 0 0 0; font-size: 20px; font-weight: bold;">
                        ${stats.fecha || 'N/A'}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Transacciones</h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${stats.total_transacciones || 0}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Transado</h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${formatCurrency(stats.total_transado)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Comisión</h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${formatCurrency(stats.total_comision)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Corredores</h4>
                    <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                        ${stats.total_corredores || 0}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white;">
                <div class="card-body">
                    <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Ciudades</h4>
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
            '<p class="text-center text-muted">No hay datos disponibles</p>';
        return;
    }
    
    let html = `
        <div style="overflow-x: auto;">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Ciudad</th>
                        <th>Corredor</th>
                        <th>Cliente</th>
                        <th>NIT</th>
                        <th>Transado</th>
                        <th>Comisión</th>
                        <th>Margen</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(row => {
        html += `
            <tr>
                <td>${row.ciudad || 'N/A'}</td>
                <td>${row.corredor}</td>
                <td>${row.cliente}</td>
                <td>${row.nit}</td>
                <td style="text-align: right;">${formatCurrency(row.transado)}</td>
                <td style="text-align: right;">${formatCurrency(row.comision)}</td>
                <td style="text-align: right;">${formatCurrency(row.margen)}</td>
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