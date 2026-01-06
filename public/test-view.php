<?php
// Test directo de la vista
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Session;
use App\Models\CargaHistorial;

echo "<h1>Testing View Rendering</h1>";

try {
    echo "<p>Bootstrap loaded</p>";

    Session::start();
    Session::set('user_id', 1);
    Session::set('user_name', 'Test User');
    Session::set('user_email', 'test@test.com');
    Session::set('user_role', 'admin');

    echo "<p>Session configured</p>";

    $userName = Session::get('user_name');
    $userRole = Session::get('user_role');

    echo "<p>Getting historial...</p>";
    $historial = CargaHistorial::recientes(20);
    echo "<p>Got " . count($historial) . " records</p>";

    echo "<p>Starting output buffer...</p>";
    ob_start();
    require __DIR__ . '/../src/Views/admin/carga-archivo.php';
    $output = ob_get_clean();
    echo "<p>Output buffer captured: " . strlen($output) . " bytes</p>";

    echo "<hr>";
    echo "<h2>ACTUAL OUTPUT:</h2>";
    echo $output;

} catch (Throwable $e) {
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h2>ERROR:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>" . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
