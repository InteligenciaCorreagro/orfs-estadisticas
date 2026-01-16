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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <?= $additionalCSS ?? '' ?>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-top">
                <button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'business_intelligence'): ?>
                        <li><a href="/dashboard"><i class="fas fa-home"></i> Dashboard</a></li>

                        <li class="menu-section"><span><i class="fas fa-cog"></i> ADMINISTRACION</span></li>
                        <li><a href="/admin/carga-archivo"><i class="fas fa-file-upload"></i> Cargar Archivo</a></li>
                        <li><a href="/admin/traders"><i class="fas fa-users"></i> Traders</a></li>
                        <li><a href="/admin/usuarios"><i class="fas fa-user-shield"></i> Usuarios</a></li>
                        <?php if ($user['role'] === 'business_intelligence' || $user['role'] === 'admin'): ?>
                            <li><a href="/bi/archivos-historicos"><i class="fas fa-file-archive"></i> Archivos Historicos</a></li>
                            <li class="menu-section menu-accordion">
                                <button type="button" class="menu-accordion-toggle" aria-expanded="false">
                                    <span><i class="fas fa-chart-line"></i> Benchmark</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <ul class="menu-submenu">
                                    <li><a href="/bi/benchmark"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                                    <li><a href="/bi/benchmark/comparativa"><i class="fas fa-users"></i> Comparativa</a></li>
                                    <li><a href="/bi/benchmark/sectores"><i class="fas fa-layer-group"></i> Sectores</a></li>
                                    <li><a href="/bi/benchmark/temporal"><i class="fas fa-calendar-alt"></i> Analisis Temporal</a></li>
                                    <li><a href="/bi/benchmark/reportes"><i class="fas fa-file-export"></i> Reportes</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                            <li class="menu-section menu-accordion">
                                <button type="button" class="menu-accordion-toggle" aria-expanded="false">
                                    <span><i class="fas fa-chart-bar"></i> Reportes</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <ul class="menu-submenu">
                                    <li><a href="/reportes/orfs"><i class="fas fa-table"></i> ORFS</a></li>
                                    <li><a href="/reportes/margen"><i class="fas fa-percentage"></i> Margen</a></li>
                                    <li><a href="/reportes/rueda"><i class="fas fa-circle-notch"></i> Ruedas</a></li>
                                    <li><a href="/reportes/negociado-diario"><i class="fas fa-calendar-day"></i> Negociado Diario</a></li>
                                    <li><a href="/reportes/consolidado"><i class="fas fa-file-contract"></i> Consolidado</a></li>
                                </ul>
                            </li>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'trader'): ?>
                        <li class="menu-section menu-accordion">
                            <button type="button" class="menu-accordion-toggle" aria-expanded="false">
                                <span><i class="fas fa-chart-bar"></i> Reportes</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="menu-submenu">
                                <li><a href="/reportes/orfs"><i class="fas fa-table"></i> ORFS</a></li>
                                <li><a href="/reportes/margen"><i class="fas fa-percentage"></i> Margen</a></li>
                                <li><a href="/reportes/rueda"><i class="fas fa-circle-notch"></i> Ruedas</a></li>
                                <li><a href="/reportes/negociado-diario"><i class="fas fa-calendar-day"></i> Negociado Diario</a></li>
                                <li><a href="/reportes/consolidado"><i class="fas fa-file-contract"></i> Consolidado</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'trader'): ?>
                        <li class="menu-section"><span><i class="fas fa-user"></i> MI CUENTA</span></li>
                        <li><a href="/trader/dashboard"><i class="fas fa-chart-line"></i> Mi Dashboard</a></li>
                        <li><a href="/trader/mis-transacciones"><i class="fas fa-exchange-alt"></i> Mis Transacciones</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-profile">
                <div class="sidebar-profile-row">
                    <div class="sidebar-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="sidebar-profile-meta">
                        <div class="sidebar-name"><?= e($user['name']) ?></div>
                        <div class="sidebar-role">
                            <?php
                            if ($user['role'] === 'business_intelligence') {
                                echo 'Inteligencia de Negocios';
                            } else {
                                echo ucfirst($user['role']);
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <a href="/logout" class="sidebar-logout">
                    <i class="fas fa-right-from-bracket"></i> Cerrar Sesion
                </a>
            </div>
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

