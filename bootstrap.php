<?php
// bootstrap.php - VERSIÓN CORREGIDA

// Configurar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', '0'); // CAMBIAR A 0 para no enviar output

// Zona horaria
date_default_timezone_set('America/Bogota');

// Autoload de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/config');
$dotenv->load();

// Cargar helpers
require_once __DIR__ . '/src/Helpers/functions.php';
require_once __DIR__ . '/src/Helpers/view_helper.php';

// Configurar manejo de errores personalizado - SIN OUTPUT
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $message = "Error [{$errno}]: {$errstr} en {$errfile}:{$errline}";
    error_log($message); // Solo log, NO echo
    
    // NO HACER ECHO - causaba el problema de headers
    // if ($_ENV['APP_DEBUG'] === 'true') {
    //     echo "<pre>$message</pre>";
    // }
    
    return true;
});

set_exception_handler(function($exception) {
    $message = "Exception: " . $exception->getMessage() . " en " . 
               $exception->getFile() . ":" . $exception->getLine();
    error_log($message);
    
    if ($_ENV['APP_DEBUG'] === 'true') {
        // Solo en debug y cuando no haya sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            echo "<pre>";
            echo $message . "\n\n";
            echo "Stack trace:\n";
            echo $exception->getTraceAsString();
            echo "</pre>";
        }
    } else {
        http_response_code(500);
        echo "Error interno del servidor";
    }
});

// NO inicializar sesión aquí - se hace en cada request

// Configuración de PHP
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');