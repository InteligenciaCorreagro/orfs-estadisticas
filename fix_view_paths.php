<?php
// fix_view_paths.php

$controllers = [
    'src/Controllers/Admin/TraderController.php',
    'src/Controllers/Admin/UsuarioController.php',
    'src/Controllers/Admin/CargaArchivoController.php',
    'src/Controllers/Reportes/OrfsController.php',
    'src/Controllers/Reportes/MargenController.php',
    'src/Controllers/Reportes/RuedaController.php',
    'src/Controllers/Reportes/NegociadoDiarioController.php',
    'src/Controllers/Reportes/ConsolidadoController.php',
    'src/Controllers/Trader/MiEstadisticaController.php',
];

$replacements = [
    // Patrón antiguo -> nuevo
    "require __DIR__ . '/../../Views/admin/traders/index.php'" => "require view_path('admin.traders.index')",
    "require __DIR__ . '/../../Views/admin/traders/create.php'" => "require view_path('admin.traders.create')",
    "require __DIR__ . '/../../Views/admin/traders/edit.php'" => "require view_path('admin.traders.edit')",
    "require __DIR__ . '/../../Views/admin/usuarios/index.php'" => "require view_path('admin.usuarios.index')",
    "require __DIR__ . '/../../Views/admin/usuarios/create.php'" => "require view_path('admin.usuarios.create')",
    "require __DIR__ . '/../../Views/admin/carga-archivo.php'" => "require view_path('admin.carga-archivo')",
    "require __DIR__ . '/../../Views/admin/dashboard.php'" => "require view_path('admin.dashboard')",
    "require __DIR__ . '/../../Views/reportes/orfs.php'" => "require view_path('reportes.orfs')",
    "require __DIR__ . '/../../Views/reportes/margen.php'" => "require view_path('reportes.margen')",
    "require __DIR__ . '/../../Views/reportes/rueda.php'" => "require view_path('reportes.rueda')",
    "require __DIR__ . '/../../Views/reportes/negociado-diario.php'" => "require view_path('reportes.negociado-diario')",
    "require __DIR__ . '/../../Views/reportes/consolidado.php'" => "require view_path('reportes.consolidado')",
    "require __DIR__ . '/../../Views/trader/dashboard.php'" => "require view_path('trader.dashboard')",
    "require __DIR__ . '/../../Views/trader/mis-transacciones.php'" => "require view_path('trader.mis-transacciones')",
    "require __DIR__ . '/../Views/auth/login.php'" => "require view_path('auth.login')",
    "require __DIR__ . '/../Views/layouts/app.php'" => "require view_path('layouts.app')",
    "require __DIR__ . '/../Views/layouts/auth.php'" => "require view_path('layouts.auth')",
];

foreach ($controllers as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        foreach ($replacements as $old => $new) {
            $content = str_replace($old, $new, $content);
        }
        
        file_put_contents($file, $content);
        echo "✓ Actualizado: $file\n";
    } else {
        echo "✗ No encontrado: $file\n";
    }
}

echo "\n✓ Proceso completado!\n";