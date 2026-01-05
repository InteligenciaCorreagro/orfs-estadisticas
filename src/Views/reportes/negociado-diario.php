<?php
// src/Views/reportes/negociado-diario.php
ob_start();
$pageTitle = 'Negociado Diario';
?>

<div class="page-header mb-3">
    <h1>Negociado Diario</h1>
    <p class="text-muted">Vista de transacciones rueda por rueda</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/reportes/negociado-diario" class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
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
                <button type="button" class="btn btn-primary" onclick="loadData()">
                    🔍 Filtrar
                </button>
                <button type="button" class="btn btn-success" onclick="exportarExcel()">
                    📥 Exportar Excel
                </button>
            </div>
        </form>
    </div>
</div>

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
        
        const response = await fetch(`/api/reportes/negociado-diario?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            renderTable(result.data);
        } else {
            document.getElementById('dataContainer').innerHTML = 
                '<p class="text-center text-danger">Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML = 
            '<p class="text-center text-danger">Error de conexión</p>';
    }
}

function renderTable(response) {
    if (!response.data || response.data.length === 0) {
        document.getElementById('dataContainer').innerHTML = 
            '<p class="text-center text-muted">No hay datos disponibles</p>';
        return;
    }
    
    const ruedas = response.ruedas || [];
    const data = response.data || [];
    
    let html = `
        <div style="overflow-x: auto;">
            <table class="table table-striped" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th style="position: sticky; left: 0; background: #4472C4; color: white; z-index: 2;">NIT</th>
                        <th style="position: sticky; left: 100px; background: #4472C4; color: white; z-index: 2;">Cliente</th>
                        <th style="position: sticky; left: 300px; background: #4472C4; color: white; z-index: 2;">Corredor</th>
    `;
    
    ruedas.forEach(rueda => {
        html += `<th>Rueda ${rueda.rueda_no}</th>`;
    });
    
    html += `
                        <th style="background: #f0f0f0; font-weight: bold;">Total</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(row => {
        html += `
            <tr>
                <td style="position: sticky; left: 0; background: white; z-index: 1;">${row.nit}</td>
                <td style="position: sticky; left: 100px; background: white; z-index: 1;">${row.cliente}</td>
                <td style="position: sticky; left: 300px; background: white; z-index: 1;">${row.corredor}</td>
        `;
        
        ruedas.forEach(rueda => {
            const key = 'rueda_' + rueda.rueda_no;
            const valor = row[key] || 0;
            html += `<td style="text-align: right;">${valor > 0 ? formatCurrency(valor) : '-'}</td>`;
        });
        
        html += `
                <td style="text-align: right; background: #f0f0f0; font-weight: bold;">${formatCurrency(row.total)}</td>
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
    
    window.location.href = `/reportes/negociado-diario/exportar?${params}`;
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';