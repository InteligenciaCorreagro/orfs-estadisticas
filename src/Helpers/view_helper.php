<?php
// src/Helpers/view_helper.php

/**
 * Obtener la ruta completa de una vista
 */
function view_path(string $view): string
{
    // Convertir punto a barra: 'auth.login' -> 'auth/login'
    $path = str_replace('.', '/', $view);
    
    // Ruta base de vistas
    $basePath = __DIR__ . '/../Views/';
    
    // Agregar extensión .php si no la tiene
    if (!str_ends_with($path, '.php')) {
        $path .= '.php';
    }
    
    $fullPath = $basePath . $path;
    
    if (!file_exists($fullPath)) {
        throw new Exception("Vista no encontrada: {$view} (buscada en: {$fullPath})");
    }
    
    return $fullPath;
}

/**
 * Renderizar una vista
 */
function view(string $view, array $data = []): string
{
    $viewPath = view_path($view);
    
    // Extraer variables al scope
    extract($data);
    
    ob_start();
    require $viewPath;
    return ob_get_clean();
}