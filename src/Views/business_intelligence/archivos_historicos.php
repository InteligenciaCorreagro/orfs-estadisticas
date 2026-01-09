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
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar este archivo histórico?</p>
                <p class="text-danger mb-0">
                    <strong>Esta acción no se puede deshacer.</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" action="/bi/archivos-historicos/delete" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
