<?php
// src/Views/admin/carga-archivo.php
ob_start();
$pageTitle = 'Cargar Archivo';
?>

<div class="page-header mb-3">
    <h1>Carga de Archivo Excel</h1>
    <p class="text-muted">Procesar archivos diarios de transacciones</p>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Subir Archivo</h3>
    </div>
    <div class="card-body">
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="archivo" class="form-label">
                    Seleccionar archivo Excel (.xls o .xlsx)
                </label>
                <input 
                    type="file" 
                    id="archivo" 
                    name="archivo" 
                    class="form-control" 
                    accept=".xls,.xlsx"
                    required
                >
                <small class="text-muted">Tamaño máximo: 10MB</small>
            </div>
            
            <button type="submit" class="btn btn-primary" id="btnUpload">
                📤 Cargar y Procesar
            </button>
        </form>
        
        <div id="uploadResult" style="margin-top: 20px; display: none;"></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Historial de Cargas</h3>
    </div>
    <div class="card-body">
        <div id="historialContainer">
            <div class="text-center">
                <div class="spinner"></div>
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
    
    // Cargar historial
    loadHistorial();
    
    // Manejar upload
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(uploadForm);
        
        btnUpload.disabled = true;
        btnUpload.innerHTML = '⏳ Procesando...';
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
                    <h4>✓ ${data.message}</h4>
                    <ul>
                        <li>Ruedas procesadas: ${data.data.resultado.ruedas_procesadas.length}</li>
                        <li>Total registros: ${data.data.resultado.total_registros}</li>
                    </ul>
                    ${data.data.resultado.errores.length > 0 ? '<h5>Errores:</h5><ul>' + data.data.resultado.errores.map(e => `<li>Rueda ${e.rueda}: ${e.error}</li>`).join('') + '</ul>' : ''}
                `;
                uploadForm.reset();
                loadHistorial();
            } else {
                uploadResult.className = 'alert alert-danger';
                uploadResult.innerHTML = `<strong>✗ Error:</strong> ${data.message}`;
            }
            
            uploadResult.style.display = 'block';
            
        } catch (error) {
            uploadResult.className = 'alert alert-danger';
            uploadResult.innerHTML = `<strong>✗ Error:</strong> ${error.message}`;
            uploadResult.style.display = 'block';
        } finally {
            btnUpload.disabled = false;
            btnUpload.innerHTML = '📤 Cargar y Procesar';
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
                const html = `
                    <div style="overflow-x: auto;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th>Usuario</th>
                                    <th>Registros</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.map(item => `
                                    <tr>
                                        <td>${new Date(item.created_at).toLocaleString('es-CO')}</td>
                                        <td>${item.archivo_nombre}</td>
                                        <td>${item.usuario_nombre || 'N/A'}</td>
                                        <td>${item.registros_insertados}</td>
                                        <td>
                                            <span class="badge badge-${item.estado === 'exitoso' ? 'success' : item.estado === 'fallido' ? 'danger' : 'warning'}">
                                                ${item.estado}
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
                document.getElementById('historialContainer').innerHTML = '<p class="text-center text-muted">No hay cargas registradas</p>';
            }
        } catch (error) {
            document.getElementById('historialContainer').innerHTML = '<p class="text-center text-danger">Error al cargar historial</p>';
        }
    }
});
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';