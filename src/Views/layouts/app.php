<?php
// src/Views/layouts/app.php
$user = auth();
$currentYear = date('Y');

if (!$user) {
    redirect('/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ORFS Estadisticas' ?> - CORREAGRO</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <?= $additionalCSS ?? '' ?>
</head>
<body>
    <header class="header">
        <div class="container-fluid">
            <div class="header-content">
                <div class="logo">
                    <a href="/dashboard" style="text-decoration: none; color: inherit;">
                        ORFS Estadisticas
                    </a>
                </div>
                
                <div class="user-menu">
                    <span class="user-name">
                        <?= e($user['name']) ?>
                        <?php if ($user['role'] === 'trader'): ?>
                            <small>(<?= e($user['trader_name']) ?>)</small>
                        <?php endif; ?>
                    </span>
                    <span class="user-role badge badge-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                    <a href="/logout" class="btn btn-sm btn-secondary">Cerrar Sesion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="/dashboard">Dashboard</a></li>
                    
                    <?php if ($user['role'] === 'admin'): ?>
                        <li class="menu-section"><span>ADMINISTRACION</span></li>
                        <li><a href="/admin/carga-archivo">Cargar Archivo</a></li>
                        <li><a href="/admin/traders">Traders</a></li>
                        <li><a href="/admin/usuarios">Usuarios</a></li>
                    <?php endif; ?>
                    
                    <li class="menu-section"><span>REPORTES</span></li>
                    <li><a href="/reportes/orfs">ORFS</a></li>
                    <li><a href="/reportes/margen">Margen</a></li>
                    <li><a href="/reportes/rueda">Ruedas</a></li>
                    <li><a href="/reportes/negociado-diario">Negociado Diario</a></li>
                    <li><a href="/reportes/consolidado">Consolidado</a></li>
                    
                    <?php if ($user['role'] === 'trader'): ?>
                        <li class="menu-section"><span>MI CUENTA</span></li>
                        <li><a href="/trader/dashboard">Mi Dashboard</a></li>
                        <li><a href="/trader/mis-transacciones">Mis Transacciones</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <?php if ($flashMessage = flash('message')): ?>
                <div class="alert alert-success">
                    <?= e($flashMessage) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($flashError = flash('error')): ?>
                <div class="alert alert-danger">
                    <?= e($flashError) ?>
                </div>
            <?php endif; ?>
            
            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <?= $additionalJS ?? '' ?>
</body>
</html>
