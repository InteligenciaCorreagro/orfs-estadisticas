<?php
// public/index.php

// Bootstrap de la aplicación
require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Config\Routes;

// Registrar rutas web
Routes::web();

// Registrar rutas API (también accesibles desde /api/*)
Routes::api();

// Crear request
$request = new Request();

// Despachar ruta
Router::dispatch($request);