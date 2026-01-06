<?php
// src/Views/admin/carga-archivo.php
ob_start();
$pageTitle = 'Cargar Archivo';
?>

<div class="page-header mb-3">
    <h1><i class="fas fa-file-upload"></i> Carga de Archivo Excel</h1>
    <p class="text-muted">Procesar archivos diarios de transacciones ORFS</p>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cloud-upload-alt"></i> Subir Archivo</h3>
    </div>
    <div class="card-body">
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="archivo" class="form-label">
                    <i class="fas fa-file-excel"></i> Seleccionar archivo Excel (.xls o .xlsx)
                </label>
                <div style="position: relative;">
                    <input
                        type="file"
                        id="archivo"
                        name="archivo"
                        class="form-control"
                        accept=".xls,.xlsx"
                        required
                        style="padding-left: 45px;"
                    >
                    <i class="fas fa-paperclip" style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #95A5A6; font-size: 16px;"></i>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle"></i> Tamaño máximo: 10MB</small>
            </div>

            <button type="submit" class="btn btn-primary" id="btnUpload">
                <i class="fas fa-upload"></i> Cargar y Procesar
            </button>
        </form>

        <div id="uploadResult" style="margin-top: 20px; display: none;"></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Historial de Cargas</h3>
    </div>
    <div class="card-body">
        <div id="historialContainer">
            <div class="text-center">
                <div class="spinner"></div>
                <p class="text-muted mt-2">Cargando historial...</p>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const btnUpload = document.getElementById('btnUpload');
    const uploadResult = document.getElementById('uploadResult');
    
    loadHistorial();
    
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(uploadForm);
        
        btnUpload.disabled = true;
        btnUpload.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        uploadResult.style.display = 'none';
        
        try {
            const response = await fetch('/admin/carga-archivo/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                uploadResult.className = 'alert alert-success';
                uploadResult.innerHTML = `
                    <h4><i class="fas fa-check-circle"></i> ${data.message}</h4>
                    <ul style="margin-bottom: 0;">
                        <li><i class="fas fa-circle-notch"></i> <strong>Ruedas procesadas:</strong> ${data.data.resultado.ruedas_procesadas.length}</li>
                        <li><i class="fas fa-database"></i> <strong>Total registros:</strong> ${data.data.resultado.total_registros}</li>
                    </ul>
                    ${data.data.resultado.errores.length > 0 ? '<h5 style="margin-top: 15px;"><i class="fas fa-exclamation-triangle"></i> Errores encontrados:</h5><ul>' + data.data.resultado.errores.map(e => `<li><i class="fas fa-times-circle"></i> Rueda ${e.rueda}: ${e.error}</li>`).join('') + '</ul>' : ''}
                `;
                uploadForm.reset();
                loadHistorial();
            } else {
                uploadResult.className = 'alert alert-danger';
                uploadResult.innerHTML = `<i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ${data.message}`;
            }
            
            uploadResult.style.display = 'block';
            
        } catch (error) {
            uploadResult.className = 'alert alert-danger';
            uploadResult.innerHTML = `<i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ${error.message}`;
            uploadResult.style.display = 'block';
        } finally {
            btnUpload.disabled = false;
            btnUpload.innerHTML = '<i class="fas fa-upload"></i> Cargar y Procesar';
        }
    });
    
    async function loadHistorial() {
        try {
            const response = await fetch('/admin/carga-archivo/historial', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.data.length > 0) {
                const getEstadoIcon = (estado) => {
                    if (estado === 'exitoso') return '<i class="fas fa-check-circle"></i>';
                    if (estado === 'fallido') return '<i class="fas fa-times-circle"></i>';
                    return '<i class="fas fa-exclamation-triangle"></i>';
                };

                const html = `
                    <div class="table-wrapper">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><i class="far fa-calendar-alt"></i> Fecha</th>
                                    <th><i class="fas fa-file-excel"></i> Archivo</th>
                                    <th><i class="fas fa-user"></i> Usuario</th>
                                    <th class="text-right"><i class="fas fa-database"></i> Registros</th>
                                    <th class="text-center"><i class="fas fa-info-circle"></i> Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.map(item => `
                                    <tr>
                                        <td><i class="far fa-clock"></i> ${new Date(item.created_at).toLocaleString('es-CO')}</td>
                                        <td><i class="fas fa-file-alt"></i> ${item.archivo_nombre}</td>
                                        <td><i class="fas fa-user-circle"></i> ${item.usuario_nombre || 'N/A'}</td>
                                        <td class="text-right"><strong>${item.registros_insertados.toLocaleString('es-CO')}</strong></td>
                                        <td class="text-center">
                                            <span class="badge badge-${item.estado === 'exitoso' ? 'success' : item.estado === 'fallido' ? 'danger' : 'warning'}">
                                                ${getEstadoIcon(item.estado)} ${item.estado.toUpperCase()}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                document.getElementById('historialContainer').innerHTML = html;
            } else {
                document.getElementById('historialContainer').innerHTML = '<p class="text-center text-muted"><i class="fas fa-inbox"></i> No hay cargas registradas</p>';
            }
        } catch (error) {
            document.getElementById('historialContainer').innerHTML = '<p class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar historial</p>';
        }
    }
});
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
