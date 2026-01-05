<?php
// src/Views/reportes/margen.php
ob_start();
$pageTitle = 'Reporte Margen';
?>

<div class="page-header mb-3">
    <h1>Reporte de Margen</h1>
    <p class="text-muted">Análisis de rentabilidad por corredor y cliente</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/reportes/margen" class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year">Año:</label>
                <select name="year" id="year" class="form-select" style="width: 150px;">
                    <?php foreach (getYearsArray(2020) as $y): ?>
                        <option value="<?= $y ?>" <?= $y == ($year ?? date('Y')) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($userRole === 'admin'): ?>
                <div>
                    <label for="corredor">Corredor:</label>
                    <select name="corredor" id="corredor" class="form-select" style="width: 250px;">
                        <option value="">Todos</option>
                        <?php foreach ($traders as $t): ?>
                            <option value="<?= e($t->nombre) ?>" <?= ($corredor ?? '') === $t->nombre ? 'selected' : '' ?>>
                                <?= e($t->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    🔍 Filtrar
                </button>
                <button type="button" class="btn btn-success" onclick="exportarExcel()">
                    📥 Exportar Excel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resumen -->
<div id="resumenContainer" class="mb-3"></div>

<!-- Tabla de datos -->
<div class="card">
    <div class="card-body">
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
document.addEventListener('DOMContentLoaded', function() {
    loadData();
});

async function loadData() {
    const year = document.getElementById('year').value;
    const corredor = document.getElementById('corredor') ? document.getElementById('corredor').value : '';
    
    try {
        const params = new URLSearchParams({ year });
        if (corredor) params.append('corredor', corredor);
        
        const response = await fetch(`/api/reportes/margen?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            renderTable(result.data);
            loadResumen(year, corredor);
        } else {
            document.getElementById('dataContainer').innerHTML = 
                '<p class="text-center text-danger">Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML = 
            '<p class="text-center text-danger">Error de conexión</p>';
    }
}

async function loadResumen(year, corredor) {
    try {
        const params = new URLSearchParams({ year });
        if (corredor) params.append('corredor', corredor);
        
        const response = await fetch(`/api/reportes/margen/top-corredores?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            const top = result.data[0];
            
            const html = `
                <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Transado</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${formatCurrency(top.total_transado)}
                            </p>
                        </div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Comisión</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${formatCurrency(top.total_comision)}
                            </p>
                        </div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Margen</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${formatCurrency(top.total_margen)}
                            </p>
                        </div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total Clientes</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${top.total_clientes}
                            </p>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('resumenContainer').innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading resumen:', error);
    }
}

function renderTable(data) {
    if (data.length === 0) {
        document.getElementById('dataContainer').innerHTML = 
            '<p class="text-center text-muted">No hay datos disponibles</p>';
        return;
    }
    
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                   'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    let html = `
        <div style="overflow-x: auto;">
            <table class="table table-striped" style="font-size: 11px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="vertical-align: middle;">Corredor</th>
                        <th rowspan="2" style="vertical-align: middle;">NIT</th>
                        <th rowspan="2" style="vertical-align: middle;">Cliente</th>
    `;
    
    meses.forEach(mes => {
        html += `
            <th colspan="3" class="text-center" style="background: #f0f0f0;">
                ${mes.charAt(0).toUpperCase() + mes.slice(1, 3)}
            </th>
        `;
    });
    
    html += `
                        <th colspan="3" class="text-center" style="background: #e0e0e0; font-weight: bold;">Total</th>
                    </tr>
                    <tr>
    `;
    
    for (let i = 0; i < 13; i++) {
        html += `
            <th style="font-size: 10px;">Trans</th>
            <th style="font-size: 10px;">Com</th>
            <th style="font-size: 10px;">Marg</th>
        `;
    }
    
    html += `
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(row => {
        html += `
            <tr>
                <td>${row.corredor}</td>
                <td>${row.nit}</td>
                <td>${row.cliente}</td>
        `;
        
        meses.forEach(mes => {
            html += `
                <td style="text-align: right;">${formatCurrency(row[mes + '_transado'])}</td>
                <td style="text-align: right;">${formatCurrency(row[mes + '_comision'])}</td>
                <td style="text-align: right;">${formatCurrency(row[mes + '_margen'])}</td>
            `;
        });
        
        html += `
                <td style="text-align: right; background: #f0f0f0; font-weight: bold;">${formatCurrency(row.total_transado)}</td>
                <td style="text-align: right; background: #f0f0f0; font-weight: bold;">${formatCurrency(row.total_comision)}</td>
                <td style="text-align: right; background: #f0f0f0; font-weight: bold;">${formatCurrency(row.total_margen)}</td>
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

function exportarExcel() {
    const year = document.getElementById('year').value;
    const corredor = document.getElementById('corredor') ? document.getElementById('corredor').value : '';
    
    const params = new URLSearchParams({ year });
    if (corredor) params.append('corredor', corredor);
    
    window.location.href = `/reportes/margen/exportar?${params}`;
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';