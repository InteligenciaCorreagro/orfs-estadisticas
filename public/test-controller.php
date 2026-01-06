<?php
// Test del controlador completo
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Session;
use App\Controllers\Admin\CargaArchivoController;

echo "<!-- DEBUG: Starting test -->\n";

try {
    // Simular sesión de usuario admin
    Session::start();
    Session::set('user_id', 1);
    Session::set('user_name', 'Admin Test');
    Session::set('user_email', 'admin@test.com');
    Session::set('user_role', 'admin');

    echo "<!-- DEBUG: Session configured -->\n";

    // Crear request mock
    $request = new Request();

    echo "<!-- DEBUG: Request created -->\n";

    // Crear controlador
    $controller = new CargaArchivoController();

    echo "<!-- DEBUG: Controller instantiated -->\n";

    // Llamar al método index
    $controller->index($request);

    echo "<!-- DEBUG: This should never print because controller exits -->\n";

} catch (Throwable $e) {
    echo "<!-- DEBUG: Exception caught -->\n";
    echo "<html><body>";
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h2>ERROR IN CONTROLLER:</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    echo "</body></html>";
}
