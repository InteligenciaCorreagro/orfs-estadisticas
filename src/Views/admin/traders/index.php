<?php
// src/Views/admin/traders/index.php
ob_start();
$pageTitle = 'Traders';
?>

<div class="page-header mb-3 d-flex justify-between align-center">
    <div>
        <h1>Gestión de Traders</h1>
        <p class="text-muted">Administrar corredores y comisiones</p>
    </div>
    <a href="/admin/traders/create" class="btn btn-primary">
        ➕ Nuevo Trader
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>NIT</th>
                        <th>Comisión %</th>
                        <th>Adicionales</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($traders)): ?>
                        <?php foreach ($traders as $trader): ?>
                            <?php $adicionales = $trader->adicionales(); ?>
                            <tr>
                                <td><?= $trader->id ?></td>
                                <td><?= e($trader->nombre) ?></td>
                                <td><?= e($trader->nit ?? 'N/A') ?></td>
                                <td><?= formatNumber($trader->porcentaje_comision, 4) ?>%</td>
                                <td>
                                    <?php if (count($adicionales) > 0): ?>
                                        <small>
                                            <?= count($adicionales) ?> adicional(es)
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Ninguno</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $trader->activo ? 'success' : 'secondary' ?>">
                                        <?= $trader->activo ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/traders/<?= $trader->id ?>/edit" class="btn btn-sm btn-primary">
                                        ✏️ Editar
                                    </a>
                                    <button 
                                        class="btn btn-sm btn-danger" 
                                        onclick="deleteTrader(<?= $trader->id ?>, '<?= e($trader->nombre) ?>')">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay traders registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
async function deleteTrader(id, nombre) {
    if (!confirm(`¿Está seguro de eliminar el trader "${nombre}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/traders/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'danger');
        }
    } catch (error) {
        showNotification('Error al eliminar trader', 'danger');
    }
}
</script>
JS;

$content = ob_get_clean();
// Subir dos niveles para llegar a src/Views/layouts/app.php
require __DIR__ . '/../../layouts/app.php';
