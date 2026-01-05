<?php
// src/Views/admin/usuarios/create.php
ob_start();
$pageTitle = 'Crear Usuario';
?>

<div class="page-header mb-3">
    <h1>Crear Nuevo Usuario</h1>
    <p class="text-muted">Registrar nuevo usuario en el sistema</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/usuarios" id="userForm">
            <div class="form-group">
                <label for="name" class="form-label">Nombre *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Contraseña *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    minlength="6"
                    required
                >
                <small class="text-muted">Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Rol *</label>
                <select id="role" name="role" class="form-select" required onchange="toggleTraderField()">
                    <option value="">Seleccione un rol</option>
                    <option value="admin">Admin</option>
                    <option value="trader">Trader</option>
                    <option value="guest">Guest</option>
                </select>
            </div>
            
            <div class="form-group" id="trader_field" style="display: none;">
                <label for="trader_name" class="form-label">Trader Asignado</label>
                <select id="trader_name" name="trader_name" class="form-select">
                    <option value="">Seleccione un trader</option>
                    <?php foreach ($traders as $trader): ?>
                        <option value="<?= e($trader->nombre) ?>">
                            <?= e($trader->nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="activo" class="form-label">Estado *</label>
                <select id="activo" name="activo" class="form-select" required>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    💾 Guardar
                </button>
                <a href="/admin/usuarios" class="btn btn-secondary">
                    ↩️ Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
function toggleTraderField() {
    const role = document.getElementById('role').value;
    const traderField = document.getElementById('trader_field');
    
    if (role === 'trader') {
        traderField.style.display = 'block';
    } else {
        traderField.style.display = 'none';
    }
}

document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('/api/admin/usuarios', {
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
                window.location.href = '/admin/usuarios';
            }, 1000);
        } else {
            showNotification(result.message, 'danger');
        }
    } catch (error) {
        showNotification('Error al crear usuario', 'danger');
    }
});
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';