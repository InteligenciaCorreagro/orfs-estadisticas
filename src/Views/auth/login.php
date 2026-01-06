<?php
// src/Views/auth/login.php
ob_start();
?>

<div class="auth-logo">ORFS</div>
<h1 class="auth-title">ORFS Estadísticas</h1>

<?php if (isset($flashError)): ?>
    <div class="alert alert-danger mb-3">
        <?= e($flashError) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/login">
    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="correo@ejemplo.com"
            required
            autofocus
        >
    </div>
    
    <div class="form-group">
        <label for="password" class="form-label">Contraseña</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-control" 
            placeholder="••••••••"
            required
        >
    </div>
    
    <button type="submit" class="btn btn-primary btn-block" style="width: 100%;">
        Iniciar Sesión
    </button>
</form>

<div style="text-align: center; margin-top: 20px; color: #95a5a6; font-size: 12px;">
    <p>CORREAGRO S.A. © <?= date('Y') ?></p>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Login';
require __DIR__ . '/../layouts/auth.php';
