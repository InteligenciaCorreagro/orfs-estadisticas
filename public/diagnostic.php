<?php
// Diagnóstico de errores - MODO AGRESIVO
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Diagnostic Test</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

try {
    echo "<h2>1. Loading Bootstrap</h2>";
    require_once __DIR__ . '/../bootstrap.php';
    echo "<p style='color:green'>✓ Bootstrap loaded</p>";

    echo "<h2>2. Testing Helpers</h2>";
    echo "<p>e() function exists: " . (function_exists('e') ? 'YES' : 'NO') . "</p>";
    echo "<p>formatNumber() function exists: " . (function_exists('formatNumber') ? 'YES' : 'NO') . "</p>";
    echo "<p>auth() function exists: " . (function_exists('auth') ? 'YES' : 'NO') . "</p>";

    echo "<h2>3. Testing Database</h2>";
    use App\Core\Database;
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Database connection OK</p>";

    echo "<h2>4. Testing Session</h2>";
    use App\Core\Session;
    Session::start();
    echo "<p style='color:green'>✓ Session started</p>";

    echo "<h2>5. Testing Models</h2>";
    use App\Models\User;
    use App\Models\Trader;
    use App\Models\CargaHistorial;

    echo "<p>Testing User::all()...</p>";
    $users = User::all();
    echo "<p style='color:green'>✓ User::all() returned " . count($users) . " users</p>";

    echo "<p>Testing Trader::all()...</p>";
    $traders = Trader::all();
    echo "<p style='color:green'>✓ Trader::all() returned " . count($traders) . " traders</p>";

    if (count($traders) > 0) {
        echo "<p>Testing Trader->adicionales()...</p>";
        $trader = $traders[0];
        echo "<p>First trader ID: " . $trader->id . "</p>";
        echo "<p>First trader nombre: " . $trader->nombre . "</p>";
        $adicionales = $trader->adicionales();
        echo "<p style='color:green'>✓ Trader->adicionales() returned " . count($adicionales) . " adicionales</p>";
    }

    echo "<p>Testing CargaHistorial::recientes()...</p>";
    $historial = CargaHistorial::recientes(5);
    echo "<p style='color:green'>✓ CargaHistorial::recientes() returned " . count($historial) . " records</p>";

    echo "<h2>6. Testing View Loading</h2>";
    echo "<p>Testing e() function: " . e("<script>test</script>") . "</p>";
    echo "<p>Testing formatNumber(): " . formatNumber(1234.5678, 2) . "</p>";

    echo "<hr>";
    echo "<h2 style='color:green'>ALL TESTS PASSED!</h2>";
    echo "<p>If you see this, the system is working correctly.</p>";

} catch (Throwable $e) {
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h2>ERROR CAUGHT:</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
