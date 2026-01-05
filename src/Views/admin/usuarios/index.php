<?php
// src/Views/admin/usuarios/index.php
ob_start();
$pageTitle = 'Usuarios';
?>

<div class="page-header mb-3 d-flex justify-between align-center">
    <div>
        <h1>Gestión de Usuarios</h1>
        <p class="text-muted">Administrar usuarios del sistema</p>
    </div>
    <a href="/admin/usuarios/create" class="btn btn-primary">
        ➕ Nuevo Usuario
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
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Trader Asignado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user->id ?></td>
                                <td><?= e($user->name) ?></td>
                                <td><?= e($user->email) ?></td>
                                <td>
                                    <span class="badge badge-<?= $user->role === 'admin' ? 'primary' : ($user->role === 'trader' ? 'success' : 'secondary') ?>">
                                        <?= ucfirst($user->role) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user->role === 'trader'): ?>
                                        <small><?= e($user->trader_name ?? 'N/A') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $user->activo ? 'success' : 'secondary' ?>">
                                        <?= $user->activo ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        class="btn btn-sm btn-primary" 
                                        onclick="editUser(<?= $user->id ?>)">
                                        ✏️ Editar
                                    </button>
                                    <button 
                                        class="btn btn-sm btn-danger" 
                                        onclick="deleteUser(<?= $user->id ?>, '<?= e($user->name) ?>')">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay usuarios registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de edición -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; min-width: 500px; max-width: 90%;">
        <h3 style="margin-top: 0;">Editar Usuario</h3>
        <form id="editForm">
            <input type="hidden" id="edit_id">
            
            <div class="form-group">
                <label for="edit_name" class="form-label">Nombre *</label>
                <input type="text" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_email" class="form-label">Email *</label>
                <input type="email" id="edit_email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_password" class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                <input type="password" id="edit_password" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="edit_role" class="form-label">Rol *</label>
                <select id="edit_role" class="form-select" required onchange="toggleTraderField(true)">
                    <option value="admin">Admin</option>
                    <option value="trader">Trader</option>
                    <option value="guest">Guest</option>
                </select>
            </div>
            
            <div class="form-group" id="edit_trader_field" style="display: none;">
                <label for="edit_trader_name" class="form-label">Trader Asignado</label>
                <select id="edit_trader_name" class="form-select">
                    <option value="">Seleccione un trader</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_activo" class="form-label">Estado *</label>
                <select id="edit_activo" class="form-select" required>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">💾 Actualizar</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
let traders = [];

document.addEventListener('DOMContentLoaded', function() {
    loadTraders();
});

async function loadTraders() {
    try {
        const response = await fetch('/api/admin/traders/activos', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        
        if (result.success) {
            traders = result.data;
        }
    } catch (error) {
        console.error('Error loading traders:', error);
    }
}

function toggleTraderField(isEdit = false) {
    const roleSelect = isEdit ? document.getElementById('edit_role') : document.getElementById('role');
    const traderField = isEdit ? document.getElementById('edit_trader_field') : document.getElementById('trader_field');
    
    if (roleSelect.value === 'trader') {
        traderField.style.display = 'block';
    } else {
        traderField.style.display = 'none';
    }
}

async function editUser(id) {
    try {
        const response = await fetch(`/api/admin/usuarios/${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        
        if (result.success) {
            const user = result.data;
            
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_activo').value = user.activo ? '1' : '0';
            
            // Cargar traders en select
            const traderSelect = document.getElementById('edit_trader_name');
            traderSelect.innerHTML = '<option value="">Seleccione un trader</option>';
            traders.forEach(trader => {
                const option = document.createElement('option');
                option.value = trader.nombre;
                option.textContent = trader.nombre;
                if (user.trader_name === trader.nombre) {
                    option.selected = true;
                }
                traderSelect.appendChild(option);
            });
            
            toggleTraderField(true);
            
            document.getElementById('editModal').style.display = 'block';
        }
    } catch (error) {
        showNotification('Error al cargar usuario', 'danger');
    }
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editForm').reset();
}

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('edit_id').value;
    const data = {
        name: document.getElementById('edit_name').value,
        email: document.getElementById('edit_email').value,
        role: document.getElementById('edit_role').value,
        trader_name: document.getElementById('edit_role').value === 'trader' 
            ? document.getElementById('edit_trader_name').value 
            : null,
        activo: document.getElementById('edit_activo').value
    };
    
    const password = document.getElementById('edit_password').value;
    if (password) {
        data.password = password;
    }
    
    try {
        const response = await fetch(`/api/admin/usuarios/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'danger');
        }
    } catch (error) {
        showNotification('Error al actualizar usuario', 'danger');
    }
});

async function deleteUser(id, nombre) {
    if (!confirm(`¿Está seguro de eliminar el usuario "${nombre}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`/api/admin/usuarios/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'danger');
        }
    } catch (error) {
        showNotification('Error al eliminar usuario', 'danger');
    }
}
</script>
JS;

$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';