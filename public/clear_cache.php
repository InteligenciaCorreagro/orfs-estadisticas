<?php
/**
 * Script para limpiar OPcache desde el navegador
 * Acceder a: http://tu-dominio.local/clear_cache.php
 */

// Seguridad básica
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips)) {
    die('Acceso denegado');
}

echo "<h1>Limpieza de Cache PHP</h1>";

// Limpiar OPcache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green;'>✓ OPcache limpiado exitosamente</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al limpiar OPcache</p>";
    }

    // Mostrar estadísticas
    $status = opcache_get_status();
    echo "<h2>Estadísticas de OPcache:</h2>";
    echo "<pre>";
    echo "Habilitado: " . ($status['opcache_enabled'] ? 'Sí' : 'No') . "\n";
    echo "Cache lleno: " . ($status['cache_full'] ? 'Sí' : 'No') . "\n";
    echo "Scripts en cache: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "Hits: " . $status['opcache_statistics']['hits'] . "\n";
    echo "Misses: " . $status['opcache_statistics']['misses'] . "\n";
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠ OPcache no está habilitado</p>";
}

// Limpiar realpath cache
if (function_exists('clearstatcache')) {
    clearstatcache(true);
    echo "<p style='color: green;'>✓ Realpath cache limpiado</p>";
}

echo "<hr>";
echo "<h2>Siguiente paso:</h2>";
echo "<ol>";
echo "<li>Recarga completamente la página de carga de archivos (Ctrl+F5)</li>";
echo "<li>Sube el archivo Excel de nuevo</li>";
echo "<li>Revisa <a href='/debug_log.txt' target='_blank'>debug_log.txt</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='/admin/carga-archivo'>← Volver a carga de archivos</a></p>";
