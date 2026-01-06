<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static array $middlewares = [];
    
    public static function get(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }
    
    public static function post(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }
    
    public static function put(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('PUT', $path, $handler, $middlewares);
    }
    
    public static function delete(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    private static function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'pattern' => self::pathToPattern($path)
        ];
    }
    
    private static function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    public static function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();
        
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                foreach ($route['middlewares'] as $middleware) {
                    // Si ya es una instancia, usarla directamente
                    // Si es un string (nombre de clase), instanciarla
                    $middlewareInstance = is_object($middleware) ? $middleware : new $middleware();
                    $middlewareInstance->handle($request);
                }
                
                $handler = $route['handler'];
                
                if (is_array($handler)) {
                    [$controller, $method] = $handler;
                    $controllerInstance = new $controller();
                    $controllerInstance->$method($request, ...$params);
                } elseif (is_callable($handler)) {
                    $handler($request, ...$params);
                }
                
                return;
            }
        }
        
        $response = new Response();
        $response->error('Ruta no encontrada', [], 404);
    }
}