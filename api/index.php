<?php
// api/index.php

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Config\Routes;

Routes::web();
Routes::api();

$request = new Request();
Router::dispatch($request);
