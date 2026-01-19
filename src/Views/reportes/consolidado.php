<?php
// src/Views/reportes/consolidado.php
ob_start();
$pageTitle = 'Reporte Consolidado';
?>

<div class="page-header mb-3">
    <h1>Reporte Consolidado</h1>
    <p class="text-muted">Vista ejecutiva general</p>
</div>

<!-- Filtro de año -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex gap-2 align-center">
            <label for="year">Año:</label>
            <select name="year" id="year" class="form-select" style="width: 150px;" onchange="loadDashboard()">
                <?php foreach (getYearsArray(2020) as $y): ?>
                    <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div id="dashboardContainer">
    <div class="text-center">
        <div class="spinner"></div>
        <p>Cargando dashboard...</p>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
});

async function loadDashboard() {
    const year = document.getElementById('year').value;
    
    try {
        const response = await fetch(`/api/reportes/consolidado?year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            renderDashboard(result.data);
        } else {
            document.getElementById('dashboardContainer').innerHTML = 
                '<p class="text-center text-danger">Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dashboardContainer').innerHTML = 
            '<p class="text-center text-danger">Error de conexión</p>';
    }
}

function renderDashboard(data) {
    const kpis = data.kpis || {};
    const porMes = data.por_mes || [];
    const porCorredor = data.por_corredor || [];
    const comparacion = data.comparacion_anual || {};
    const topClientes = data.top_clientes || [];
    
    let html = `
        <!-- KPIs Principales -->
        <div class="row mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Registrado</h3>
                    <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                        ${formatNumber(kpis.total_transacciones || 0, 0)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Negociado</h3>
                    <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                        ${formatCurrency(kpis.total_negociado || 0)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Comisión</h3>
                    <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                        ${formatCurrency(kpis.total_comision || 0)}
                    </p>
                </div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <div class="card-body">
                    <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Margen</h3>
                    <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;">
                        ${formatCurrency(kpis.total_margen || 0)}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Comparación Anual -->
    `;
    
    if (comparacion.año_actual && comparacion.año_anterior) {
        html += `
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Comparación Año Actual vs Anterior</h3>
            </div>
            <div class="card-body">
                <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <h4>Negociado</h4>
                        <p style="font-size: 24px; font-weight: bold;">
                            ${formatCurrency(comparacion.año_actual.total_negociado)}
                        </p>
                        <p style="color: ${comparacion.crecimiento.negociado.positivo ? '#43e97b' : '#f5576c'};">
                            ${comparacion.crecimiento.negociado.positivo ? '▲' : '▼'} 
                            ${formatPercent(Math.abs(comparacion.crecimiento.negociado.porcentaje))}
                        </p>
                    </div>
                    <div>
                        <h4>Comisión</h4>
                        <p style="font-size: 24px; font-weight: bold;">
                            ${formatCurrency(comparacion.año_actual.total_comision)}
                        </p>
                        <p style="color: ${comparacion.crecimiento.comision.positivo ? '#43e97b' : '#f5576c'};">
                            ${comparacion.crecimiento.comision.positivo ? '▲' : '▼'} 
                            ${formatPercent(Math.abs(comparacion.crecimiento.comision.porcentaje))}
                        </p>
                    </div>
                    <div>
                        <h4>Transacciones</h4>
                        <p style="font-size: 24px; font-weight: bold;">
                            ${formatNumber(comparacion.año_actual.total_transacciones, 0)}
                        </p>
                        <p style="color: ${comparacion.crecimiento.transacciones.positivo ? '#43e97b' : '#f5576c'};">
                            ${comparacion.crecimiento.transacciones.positivo ? '▲' : '▼'} 
                            ${formatNumber(Math.abs(comparacion.crecimiento.transacciones.valor), 0)}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        `;
    }
    
    // Evolución Mensual
    html += `
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Evolución Mensual</h3>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Transacciones</th>
                                <th>Ruedas</th>
                                <th>Negociado</th>
                                <th>Comisión</th>
                                <th>Margen</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    porMes.forEach(mes => {
        html += `
            <tr>
                <td>${mes.mes}</td>
                <td>${formatNumber(mes.total_transacciones, 0)}</td>
                <td>${formatNumber(mes.total_ruedas, 0)}</td>
                <td>${formatCurrency(mes.total_negociado)}</td>
                <td>${formatCurrency(mes.total_comision)}</td>
                <td>${formatCurrency(mes.total_margen)}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Top Clientes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top 10 Clientes por Volumen</h3>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Corredor</th>
                                <th>Transacciones</th>
                                <th>Negociado</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    topClientes.forEach((cliente, index) => {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${cliente.cliente}</td>
                <td>${cliente.corredor}</td>
                <td>${formatNumber(cliente.total_transacciones, 0)}</td>
                <td>${formatCurrency(cliente.total_negociado)}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dashboardContainer').innerHTML = html;
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';