<?php
// src/Views/business_intelligence/archivos_historicos.php
$pageTitle = 'Archivos Históricos';
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-history me-2"></i>
                Archivos Históricos
            </h1>
            <p class="text-muted">Gestión de archivos históricos anuales (2021-2025)</p>
        </div>
    </div>

    <!-- Mensajes flash -->
    <?php if (flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= e(flash('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (flash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= e(flash('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario de carga -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload me-2"></i>
                        Subir Archivo Histórico
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/bi/archivos-historicos/upload" method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="year" class="form-label">Año *</label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="">Seleccione un año</option>
                                    <?php foreach ($availableYears as $availableYear): ?>
                                        <option value="<?= $availableYear ?>"><?= $availableYear ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label for="file" class="form-label">Archivo *</label>
                                <input type="file" class="form-control" id="file" name="file"
                                       accept=".xlsx,.xls,.csv" required>
                                <small class="text-muted">
                                    Formatos permitidos: Excel (.xlsx, .xls) o CSV (.csv)
                                </small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="notes" class="form-label">Notas (opcional)</label>
                                <input type="text" class="form-control" id="notes" name="notes"
                                       placeholder="Descripción del archivo">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>
                                    Subir Archivo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas por año -->
    <?php if (!empty($yearStats)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Estadísticas por Año
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Año</th>
                                    <th>Total Archivos</th>
                                    <th>Tamaño Total</th>
                                    <th>Última Carga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearStats as $stat): ?>
                                <tr>
                                    <td><strong><?= $stat['year'] ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= $stat['total_uploads'] ?> archivo(s)
                                        </span>
                                    </td>
                                    <td><?= getReadableFileSize($stat['total_size']) ?></td>
                                    <td><?= formatDateTime($stat['last_upload']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lista de archivos históricos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Archivos Históricos Subidos
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($uploads)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay archivos históricos cargados. Suba el primer archivo usando el formulario anterior.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Año</th>
                                        <th>Nombre Original</th>
                                        <th>Tamaño</th>
                                        <th>Estado</th>
                                        <th>Registros</th>
                                        <th>Subido Por</th>
                                        <th>Fecha de Carga</th>
                                        <th>Notas</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uploads as $upload): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-dark fs-6">
                                                <?= $upload['year'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-file-excel text-success me-2"></i>
                                            <?= e($upload['original_filename']) ?>
                                        </td>
                                        <td><?= getReadableFileSize($upload['file_size']) ?></td>
                                        <td>
                                            <?php if ($upload['processed']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Procesado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock"></i> Pendiente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($upload['processed']): ?>
                                                <span class="badge bg-info">
                                                    <?= number_format($upload['records_count']) ?> registros
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($upload['uploaded_by_name']) ?></td>
                                        <td><?= formatDateTime($upload['upload_date']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= e($upload['notes'] ?: 'Sin notas') ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="/bi/archivos-historicos/download?id=<?= $upload['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmDelete(<?= $upload['id'] ?>)"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 18px;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                Confirmar Eliminación
            </h5>
            <button type="button" onclick="closeDeleteModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 20px;">
            <p style="margin-bottom: 10px;">¿Está seguro que desea eliminar este archivo histórico?</p>
            <p style="color: #dc3545; margin: 0;">
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
        </div>
        <div style="padding: 15px 20px; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
            <form id="deleteForm" method="POST" action="/bi/archivos-historicos/delete" style="display: inline; margin: 0;">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash" style="margin-right: 8px;"></i>
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
}

// Cerrar modal al hacer clic fuera de él
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
