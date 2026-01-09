<?php
// src/Views/reportes/margen.php
ob_start();
$pageTitle = 'Reporte Margen';
?>

<style>
/* Modal de detalle */
.detail-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease;
}

.detail-modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.detail-modal {
    background: white;
    border-radius: 16px;
    width: 95%;
    max-width: 1400px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.detail-modal-header {
    background: linear-gradient(135deg, #2d3436 0%, #000000 100%);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.detail-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
}

.detail-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.detail-modal-body {
    padding: 20px;
    max-height: calc(90vh - 80px);
    overflow-y: auto;
}

.clickable-row {
    cursor: pointer;
    transition: all 0.3s ease;
}

.clickable-row:hover {
    background: #E8F8F5 !important;
    transform: translateX(5px);
}
</style>

<div class="page-header mb-3">
    <h1><i class="fas fa-chart-line"></i> Reporte de Margen</h1>
    <p class="text-muted">Análisis de rentabilidad por corredor y cliente (haz clic en una fila para ver detalle mensual)</p>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/reportes/margen" class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
            <div>
                <label for="year"><i class="far fa-calendar"></i> Año:</label>
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
                    <label for="corredor"><i class="fas fa-user-tie"></i> Corredor:</label>
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
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <button type="button" class="btn btn-success" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resumen -->
<div id="resumenContainer" class="mb-3"></div>

<!-- Tabla Resumida -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table"></i> Resumen por Cliente</h3>
        <small class="text-muted">Haz clic en una fila para ver detalle mensual completo</small>
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

<!-- Modal de Detalle -->
<div class="detail-modal-overlay" id="detailModal">
    <div class="detail-modal">
        <div class="detail-modal-header">
            <h3 id="modalTitle"><i class="fas fa-chart-bar"></i> Detalle Mensual</h3>
            <button class="detail-modal-close" onclick="closeDetailModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="detail-modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
let fullData = [];

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
            fullData = result.data;
            renderSummaryTable(result.data);
            loadResumen(year, corredor);
        } else {
            document.getElementById('dataContainer').innerHTML =
                '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar datos</p>';
        }
    } catch (error) {
        document.getElementById('dataContainer').innerHTML =
            '<p class="text-center text-danger"><i class="fas fa-wifi" style="text-decoration: line-through;"></i> Error de conexión</p>';
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
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-dollar-sign"></i> Total Transado</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${formatCurrency(top.total_transado)}
                            </p>
                        </div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-percentage"></i> Total Comisión</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${formatCurrency(top.total_comision)}
                            </p>
                        </div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-chart-line"></i> Total Margen</h4>
                            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">
                                ${formatCurrency(top.total_margen)}
                            </p>
                        </div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                        <div class="card-body">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-users"></i> Total Clientes</h4>
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
                        <th><i class="fas fa-user-tie"></i> Corredor</th>
                        <th><i class="fas fa-id-card"></i> NIT</th>
                        <th><i class="fas fa-building"></i> Cliente</th>
                        <th class="text-right"><i class="fas fa-dollar-sign"></i> Total Transado</th>
                        <th class="text-right"><i class="fas fa-dollar-sign"></i> Comisión COP</th>
                        <th class="text-right"><i class="fas fa-chart-line"></i> Margen COP</th>
                        <th class="text-center"><i class="fas fa-expand-alt"></i> Ver Detalle</th>
                    </tr>
                </thead>
                <tbody>
    `;

    data.forEach((row, index) => {
        const totalTransado = parseFloat(row.total_transado) || 0;
        const totalComision = parseFloat(row.total_comision) || 0;
        const totalMargen = parseFloat(row.total_margen) || 0;

        html += `
            <tr class="clickable-row" onclick="showDetail(${index})">
                <td><i class="fas fa-user" style="color: #27ae60; margin-right: 6px;"></i>${row.corredor}</td>
                <td><span style="font-family: monospace; background: #F8F9FA; padding: 2px 6px; border-radius: 4px; font-size: 11px;">${row.nit}</span></td>
                <td><strong>${row.cliente}</strong></td>
                <td class="text-right"><strong>${formatCurrency(totalTransado)}</strong></td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatCurrency(totalComision)}</td>
                <td class="text-right" style="color: #27ae60; font-weight: bold;">${formatCurrency(totalMargen)}</td>
                <td class="text-center">
                    <button class="btn btn-sm" style="background: #27ae60; color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </td>
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

function showDetail(index) {
    const row = fullData[index];
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                   'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    document.getElementById('modalTitle').innerHTML = `
        <i class="fas fa-chart-bar"></i> Detalle Mensual: <strong>${row.cliente}</strong> (${row.nit})
    `;

    let html = `
        <div style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #E8F8F5, #D5F4E6); border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #2d3436;"><i class="fas fa-info-circle"></i> Información General</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong><i class="fas fa-user-tie"></i> Corredor:</strong><br>
                    ${row.corredor}
                </div>
                <div>
                    <strong><i class="fas fa-building"></i> Cliente:</strong><br>
                    ${row.cliente}
                </div>
                <div>
                    <strong><i class="fas fa-id-card"></i> NIT:</strong><br>
                    <code>${row.nit}</code>
                </div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="table table-striped" style="font-size: 12px;">
                <thead style="background: linear-gradient(135deg, #2d3436 0%, #000000 100%); color: white;">
                    <tr>
                        <th rowspan="2" style="vertical-align: middle; border-right: 2px solid #27ae60;">Mes</th>
    `;

    // Headers
    html += `
                        <th colspan="3" class="text-center">Valores</th>
                    </tr>
                    <tr>
                        <th>Transado</th>
                        <th style="background: rgba(39, 174, 96, 0.2);">Comisión COP</th>
                        <th style="background: rgba(39, 174, 96, 0.3);">Margen COP</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Rows por mes
    meses.forEach(mes => {
        const transado = parseFloat(row[mes + '_transado']) || 0;
        const comision = parseFloat(row[mes + '_comision']) || 0;
        const margen = parseFloat(row[mes + '_margen']) || 0;

        html += `
            <tr>
                <td style="font-weight: 600; background: #f8f9fa; border-right: 2px solid #27ae60;">
                    <i class="far fa-calendar"></i> ${mes.charAt(0).toUpperCase() + mes.slice(1)}
                </td>
                <td class="text-right">${transado > 0 ? formatCurrency(transado) : '-'}</td>
                <td class="text-right" style="color: #27ae60; font-weight: 600;">${transado > 0 ? formatCurrency(comision) : '-'}</td>
                <td class="text-right" style="color: #27ae60; font-weight: 600;">${transado > 0 ? formatCurrency(margen) : '-'}</td>
            </tr>
        `;
    });

    // Total row
    const totalTransado = parseFloat(row.total_transado) || 0;
    const totalComision = parseFloat(row.total_comision) || 0;
    const totalMargen = parseFloat(row.total_margen) || 0;

    html += `
                <tr style="background: #27ae60; color: white; font-weight: bold;">
                    <td style="border-right: 2px solid white;"><i class="fas fa-calculator"></i> TOTAL</td>
                    <td class="text-right">${formatCurrency(totalTransado)}</td>
                    <td class="text-right">${formatCurrency(totalComision)}</td>
                    <td class="text-right">${formatCurrency(totalMargen)}</td>
                </tr>
            </tbody>
        </table>
        </div>
    `;

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
    }
});

// Cerrar modal haciendo clic fuera
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

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
