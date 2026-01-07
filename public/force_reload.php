<?php
/**
 * Script para FORZAR la recarga de archivos PHP
 * Invalida el cache de archivos específicos
 */

$files_to_invalidate = [
    __DIR__ . '/../src/Services/Excel/RuedaProcessor.php',
    __DIR__ . '/../src/Core/Database.php',
];

echo "<h1>Forzando Recarga de Archivos PHP</h1>";

// Limpiar stat cache
clearstatcache(true);
echo "<p>✓ Stat cache limpiado</p>";

// Limpiar OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>✓ OPcache reseteado globalmente</p>";
}

// Invalidar archivos específicos en OPcache
if (function_exists('opcache_invalidate')) {
    foreach ($files_to_invalidate as $file) {
        if (file_exists($file)) {
            opcache_invalidate($file, true);
            $status = opcache_is_script_cached($file) ? 'TODAVÍA EN CACHE' : 'INVALIDADO';
            echo "<p>• " . basename($file) . ": <strong>{$status}</strong></p>";
        }
    }
}

echo "<hr>";
echo "<h2>Verificación de Archivos:</h2>";

foreach ($files_to_invalidate as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        $date = date('Y-m-d H:i:s', $mtime);
        echo "<p><strong>" . basename($file) . "</strong><br>";
        echo "Última modificación: {$date}<br>";
        echo "Path: {$file}</p>";
    }
}

// Tocar los archivos para forzar actualización
echo "<hr>";
echo "<h2>Forzando Actualización de Timestamps:</h2>";
foreach ($files_to_invalidate as $file) {
    if (file_exists($file)) {
        touch($file);
        echo "<p>✓ " . basename($file) . " timestamp actualizado</p>";
    }
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>✓ OPcache reseteado NUEVAMENTE después de tocar archivos</p>";
}

echo "<hr>";
echo "<h2 style='color: red;'>IMPORTANTE:</h2>";
echo "<ol>";
echo "<li><strong style='color: red;'>CIERRA TODAS LAS PESTAÑAS</strong> del sitio</li>";
echo "<li><strong style='color: red;'>ESPERA 5 SEGUNDOS</strong></li>";
echo "<li><strong style='color: red;'>ABRE UNA PESTAÑA NUEVA</strong> y ve a <a href='/admin/carga-archivo'>/admin/carga-archivo</a></li>";
echo "<li>Sube el archivo Excel</li>";
echo "<li>Revisa <a href='/debug_log.txt' target='_blank'>debug_log.txt</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p>Timestamp actual: " . date('Y-m-d H:i:s') . "</p>";
