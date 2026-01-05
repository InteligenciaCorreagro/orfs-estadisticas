<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $content;
    
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }
    
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    public function json(array $data, int $status = 200): void
    {
        $this->status($status);
        $this->header('Content-Type', 'application/json');
        $this->content = json_encode($data);
        $this->send();
    }
    
    public function success(string $message, array $data = [], int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
    
    public function error(string $message, array $errors = [], int $status = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
    
    public function html(string $html, int $status = 200): void
    {
        $this->status($status);
        $this->header('Content-Type', 'text/html; charset=UTF-8');
        $this->content = $html;
        $this->send();
    }
    
    public function redirect(string $url, int $status = 302): void
    {
        $this->status($status);
        $this->header('Location', $url);
        $this->send();
    }
    
    public function download(string $filepath, string $filename = null): void
    {
        if (!file_exists($filepath)) {
            $this->error('Archivo no encontrado', [], 404);
            return;
        }
        
        $filename = $filename ?? basename($filepath);
        
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->header('Content-Length', (string) filesize($filepath));
        $this->content = file_get_contents($filepath);
        $this->send();
    }
    
    private function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        echo $this->content;
        exit;
    }
}