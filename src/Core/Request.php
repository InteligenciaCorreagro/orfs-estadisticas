<?php

namespace App\Core;

class Request
{
    private array $query;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private ?array $json = null;
    
    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        
        if ($this->isJson()) {
            $this->json = json_decode(file_get_contents('php://input'), true);
        }
    }
    
    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
    
    public function post(string $key, $default = null)
    {
        if ($this->isJson() && $this->json !== null) {
            return $this->json[$key] ?? $default;
        }
        return $this->post[$key] ?? $default;
    }
    
    public function input(string $key, $default = null)
    {
        return $this->post($key) ?? $this->get($key) ?? $default;
    }
    
    public function all(): array
    {
        if ($this->isJson() && $this->json !== null) {
            return $this->json;
        }
        return array_merge($this->query, $this->post);
    }
    
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }
    
    public function except(array $keys): array
    {
        $all = $this->all();
        return array_diff_key($all, array_flip($keys));
    }
    
    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }
    
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }
    
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }
    
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }
    
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }
    
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }
    
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }
    
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }
    
    public function isAjax(): bool
    {
        return !empty($this->server['HTTP_X_REQUESTED_WITH']) &&
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function isJson(): bool
    {
        return strpos($this->server['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }
    
    public function uri(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }
    
    public function url(): string
    {
        $protocol = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . ($this->server['HTTP_HOST'] ?? 'localhost');
    }
    
    public function fullUrl(): string
    {
        return $this->url() . ($this->server['REQUEST_URI'] ?? '/');
    }
    
    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
}