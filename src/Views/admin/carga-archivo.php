<?php
// src/Views/admin/carga-archivo.php
ob_start();
$pageTitle = 'Cargar Archivo';
?>

<style>
/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.upload-modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 800px;
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

.modal-header {
    background: linear-gradient(135deg, #27AE60 0%, #16A085 100%);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 30px;
    max-height: calc(90vh - 140px);
    overflow-y: auto;
}

/* Drop Zone */
.drop-zone {
    border: 3px dashed #BDC3C7;
    border-radius: 12px;
    padding: 50px 20px;
    text-align: center;
    background: #F8F9FA;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.drop-zone:hover {
    border-color: #27AE60;
    background: #E8F8F5;
    transform: scale(1.02);
}

.drop-zone.drag-over {
    border-color: #27AE60;
    background: #D5F4E6;
    border-style: solid;
}

.drop-zone-icon {
    font-size: 64px;
    color: #27AE60;
    margin-bottom: 20px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.drop-zone-text {
    font-size: 18px;
    color: #2C3E50;
    margin-bottom: 10px;
    font-weight: 600;
}

.drop-zone-hint {
    color: #7F8C8D;
    font-size: 14px;
}

/* File List */
.file-list {
    margin-top: 30px;
}

.file-item {
    background: white;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.file-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.file-icon {
    font-size: 32px;
    color: #27AE60;
    flex-shrink: 0;
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-weight: 600;
    color: #2C3E50;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-size {
    color: #7F8C8D;
    font-size: 13px;
}

.file-status {
    flex-shrink: 0;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.file-status.pending {
    background: #FFF3CD;
    color: #856404;
}

.file-status.uploading {
    background: #D1ECF1;
    color: #0C5460;
}

.file-status.success {
    background: #D4EDDA;
    color: #155724;
}

.file-status.error {
    background: #F8D7DA;
    color: #721C24;
}

.file-remove {
    background: #E74C3C;
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-remove:hover {
    background: #C0392B;
    transform: scale(1.1);
}

.file-progress {
    width: 100%;
    height: 4px;
    background: #ECF0F1;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 8px;
}

.file-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #27AE60, #2ECC71);
    transition: width 0.3s ease;
    border-radius: 2px;
}

/* Modal Footer */
.modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #E0E0E0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.upload-summary {
    color: #7F8C8D;
    font-size: 14px;
}

.btn-upload-all {
    background: linear-gradient(135deg, #27AE60, #16A085);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-upload-all:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
}

.btn-upload-all:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Open Modal Button */
.btn-open-modal {
    background: linear-gradient(135deg, #27AE60, #16A085);
    color: white;
    border: none;
    padding: 20px 40px;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
}

.btn-open-modal:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
}

.btn-open-modal i {
    font-size: 24px;
}
</style>

<div class="page-header mb-3">
    <h1><i class="fas fa-file-upload"></i> Carga de Archivos Excel</h1>
    <p class="text-muted">Procesar archivos diarios de transacciones ORFS (hasta 10 archivos simultáneos)</p>
</div>

<div class="card mb-3">
    <div class="card-body" style="text-align: center; padding: 60px 20px;">
        <div style="margin-bottom: 30px;">
            <i class="fas fa-cloud-upload-alt" style="font-size: 80px; color: #27AE60; opacity: 0.8;"></i>
        </div>
        <h2 style="color: #2C3E50; margin-bottom: 15px;">Subir Archivos Excel</h2>
        <p style="color: #7F8C8D; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
            Arrastra y suelta tus archivos o haz clic en el botón para seleccionarlos.
            Puedes cargar hasta 10 archivos a la vez.
        </p>
        <button class="btn-open-modal" id="btnOpenModal">
            <i class="fas fa-plus-circle"></i>
            Seleccionar Archivos
        </button>
    </div>
</div>

<!-- Modal de Upload -->
<div class="modal-overlay" id="uploadModal">
    <div class="upload-modal">
        <div class="modal-header">
            <h2><i class="fas fa-file-upload"></i> Cargar Archivos Excel</h2>
            <button class="modal-close" id="btnCloseModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="drop-zone" id="dropZone">
                <input type="file" id="fileInput" accept=".xls,.xlsx" multiple style="display: none;">
                <div class="drop-zone-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="drop-zone-text">Arrastra archivos aquí</div>
                <div class="drop-zone-hint">o haz clic para seleccionar (máximo 10 archivos, 10MB cada uno)</div>
            </div>

            <div class="file-list" id="fileList"></div>
        </div>
        <div class="modal-footer">
            <div class="upload-summary">
                <span id="fileCount">0 archivos seleccionados</span>
            </div>
            <button class="btn-upload-all" id="btnUploadAll" disabled>
                <i class="fas fa-upload"></i>
                Procesar Archivos
            </button>
        </div>
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
    const uploadModal = document.getElementById('uploadModal');
    const btnOpenModal = document.getElementById('btnOpenModal');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const fileCount = document.getElementById('fileCount');
    const btnUploadAll = document.getElementById('btnUploadAll');

    let selectedFiles = [];
    const MAX_FILES = 10;
    const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    loadHistorial();

    // Abrir modal
    btnOpenModal.addEventListener('click', () => {
        uploadModal.classList.add('active');
    });

    // Cerrar modal
    btnCloseModal.addEventListener('click', (e) => {
        e.stopPropagation();
        closeModal();
    });
    uploadModal.addEventListener('click', (e) => {
        if (e.target === uploadModal) closeModal();
    });

    // Prevenir que clicks dentro del modal cierren el modal
    const modalContainer = uploadModal.querySelector('.upload-modal');
    if (modalContainer) {
        modalContainer.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    function closeModal() {
        uploadModal.classList.remove('active');
    }

    // Click en drop zone
    dropZone.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.click();
    });

    // Drag & Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
    });

    // Selección de archivos
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        const newFiles = Array.from(files);

        // Validar límite de archivos
        if (selectedFiles.length + newFiles.length > MAX_FILES) {
            alert(`Solo puedes subir un máximo de ${MAX_FILES} archivos a la vez`);
            return;
        }

        // Validar y agregar archivos
        newFiles.forEach(file => {
            if (file.size > MAX_SIZE) {
                alert(`El archivo "${file.name}" supera el tamaño máximo de 10MB`);
                return;
            }

            if (!file.name.match(/\.(xls|xlsx)$/i)) {
                alert(`El archivo "${file.name}" no es un archivo Excel válido`);
                return;
            }

            selectedFiles.push({
                file: file,
                id: Date.now() + Math.random(),
                status: 'pending',
                progress: 0
            });
        });

        renderFileList();
        updateUI();
    }

    function renderFileList() {
        if (selectedFiles.length === 0) {
            fileList.innerHTML = '';
            return;
        }

        const html = selectedFiles.map((item, index) => `
            <div class="file-item" data-id="${item.id}">
                <div class="file-icon">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${item.file.name}</div>
                    <div class="file-size">${formatFileSize(item.file.size)}</div>
                    ${item.status === 'uploading' || item.status === 'success' ? `
                        <div class="file-progress">
                            <div class="file-progress-bar" style="width: ${item.progress}%"></div>
                        </div>
                    ` : ''}
                </div>
                <div class="file-status ${item.status}">
                    ${getStatusText(item.status)}
                </div>
                ${item.status === 'pending' ? `
                    <button class="file-remove" onclick="removeFile('${item.id}')">
                        <i class="fas fa-times"></i>
                    </button>
                ` : ''}
            </div>
        `).join('');

        fileList.innerHTML = html;
    }

    function getStatusText(status) {
        const icons = {
            pending: '<i class="fas fa-clock"></i> Pendiente',
            uploading: '<i class="fas fa-spinner fa-spin"></i> Procesando',
            success: '<i class="fas fa-check"></i> Completado',
            error: '<i class="fas fa-times"></i> Error'
        };
        return icons[status] || status;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function updateUI() {
        const pendingCount = selectedFiles.filter(f => f.status === 'pending').length;
        fileCount.textContent = `${selectedFiles.length} archivo${selectedFiles.length !== 1 ? 's' : ''} seleccionado${selectedFiles.length !== 1 ? 's' : ''}`;
        btnUploadAll.disabled = pendingCount === 0;
    }

    window.removeFile = function(id) {
        selectedFiles = selectedFiles.filter(f => f.id !== id);
        renderFileList();
        updateUI();
    }

    // Procesar archivos
    btnUploadAll.addEventListener('click', async () => {
        btnUploadAll.disabled = true;

        const pendingFiles = selectedFiles.filter(f => f.status === 'pending');

        for (const fileItem of pendingFiles) {
            await uploadFile(fileItem);
        }

        // Recargar historial
        await loadHistorial();

        // Limpiar después de 3 segundos
        setTimeout(() => {
            selectedFiles = [];
            renderFileList();
            updateUI();
            closeModal();
        }, 3000);
    });

    async function uploadFile(fileItem) {
        fileItem.status = 'uploading';
        fileItem.progress = 0;
        renderFileList();

        const formData = new FormData();
        formData.append('archivo', fileItem.file);

        try {
            const response = await fetch('/admin/carga-archivo/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Simular progreso
            fileItem.progress = 50;
            renderFileList();

            const data = await response.json();

            if (data.success) {
                fileItem.status = 'success';
                fileItem.progress = 100;
            } else {
                fileItem.status = 'error';
            }
        } catch (error) {
            fileItem.status = 'error';
        }

        renderFileList();
    }

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
                            <thead style="background: linear-gradient(135deg, #2d3436 0%, #000000 100%); color: white;">
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
