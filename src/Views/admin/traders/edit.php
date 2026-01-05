<?php
// src/Views/admin/traders/edit.php
ob_start();
$pageTitle = 'Editar Trader';
?>

<div class="page-header mb-3">
    <h1>Editar Trader</h1>
    <p class="text-muted">Modificar información del corredor</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/traders/<?= $trader->id ?>" id="traderForm">
            <div class="form-group">
                <label for="nombre" class="form-label">Nombre *</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="form-control" 
                    value="<?= e($trader->nombre) ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="nit" class="form-label">NIT</label>
                <input 
                    type="text" 
                    id="nit" 
                    name="nit" 
                    class="form-control"
                    value="<?= e($trader->nit ?? '') ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="porcentaje_comision" class="form-label">Porcentaje Comisión (%) *</label>
                <input 
                    type="number" 
                    id="porcentaje_comision" 
                    name="porcentaje_comision" 
                    class="form-control" 
                    step="0.0001"
                    min="0"
                    max="100"
                    value="<?= $trader->porcentaje_comision ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="activo" class="form-label">Estado *</label>
                <select id="activo" name="activo" class="form-select" required>
                    <option value="1" <?= $trader->activo ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= !$trader->activo ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nombres Adicionales</label>
                <div id="adicionalesContainer">
                    <?php foreach ($adicionales as $adicional): ?>
                        <div class="d-flex gap-1 mb-2">
                            <input 
                                type="text" 
                                name="adicionales[]" 
                                class="form-control" 
                                placeholder="Nombre adicional"
                                value="<?= e($adicional->nombre_adicional) ?>"
                            >
                            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
                                ✕
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addAdicional()">
                    ➕ Agregar Adicional
                </button>
            </div>
            
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    💾 Actualizar
                </button>
                <a href="/admin/traders" class="btn btn-secondary">
                    ↩️ Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
function addAdicional() {
    const container = document.getElementById('adicionalesContainer');
    const div = document.createElement('div');
    div.className = 'd-flex gap-1 mb-2';
    div.innerHTML = `
        <input 
            type="text" 
            name="adicionales[]" 
            class="form-control" 
            placeholder="Nombre adicional"
        >
        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
            ✕
        </button>
    `;
    container.appendChild(div);
}

document.getElementById('traderForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Obtener adicionales como array
    const adicionales = [];
    formData.getAll('adicionales[]').forEach(val => {
        if (val.trim()) adicionales.push(val.trim());
    });
    data.adicionales = adicionales;
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => {
                window.location.href = '/admin/traders';
            }, 1000);
        } else {
            showNotification(result.message, 'danger');
        }
    } catch (error) {
        showNotification('Error al actualizar trader', 'danger');
    }
});
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';