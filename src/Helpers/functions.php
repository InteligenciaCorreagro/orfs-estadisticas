<?php
// src/Helpers/functions.php

use App\Core\Session;

/**
 * Escapar HTML
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Obtener valor de variable de entorno
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

/**
 * Formatear número como moneda
 */
function formatCurrency(?float $amount, string $currency = 'COP'): string
{
    if ($amount === null) {
        return '$0';
    }
    
    $formatted = number_format($amount, 0, ',', '.');
    
    switch ($currency) {
        case 'COP':
            return '$' . $formatted;
        case 'USD':
            return 'US$' . $formatted;
        default:
            return $formatted;
    }
}

/**
 * Formatear número con decimales
 */
function formatNumber(?float $number, int $decimals = 2): string
{
    if ($number === null) {
        return '0';
    }
    
    return number_format($number, $decimals, ',', '.');
}

/**
 * Formatear porcentaje
 */
function formatPercent(?float $number, int $decimals = 2): string
{
    if ($number === null) {
        return '0%';
    }
    
    return number_format($number, $decimals, ',', '.') . '%';
}

/**
 * Formatear fecha
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) {
        return '';
    }
    
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Formatear fecha y hora
 */
function formatDateTime(?string $dateTime, string $format = 'd/m/Y H:i'): string
{
    return formatDate($dateTime, $format);
}

/**
 * Obtener URL base
 */
function baseUrl(string $path = ''): string
{
    $baseUrl = env('APP_URL', 'http://localhost');
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Obtener URL de asset
 */
function asset(string $path): string
{
    // Servir assets directamente desde la carpeta public/assets respetando APP_URL
    return baseUrl('assets/' . ltrim($path, '/'));
}
/**
 * Redirigir
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Obtener usuario actual
 */
function auth(): ?array
{
    Session::start();
    
    if (!Session::has('user_id')) {
        return null;
    }
    
    return [
        'id' => Session::get('user_id'),
        'name' => Session::get('user_name'),
        'email' => Session::get('user_email'),
        'role' => Session::get('user_role'),
        'trader_name' => Session::get('trader_name')
    ];
}

/**
 * Verificar si está autenticado
 */
function isAuth(): bool
{
    return auth() !== null;
}

/**
 * Verificar rol
 */
function hasRole(string $role): bool
{
    $user = auth();
    return $user && $user['role'] === $role;
}

/**
 * Verificar si es admin
 */
function isAdmin(): bool
{
    return hasRole('admin');
}

/**
 * Verificar si es trader
 */
function isTrader(): bool
{
    return hasRole('trader');
}

/**
 * Verificar si es inteligencia de negocios
 */
function isBusinessIntelligence(): bool
{
    return hasRole('business_intelligence');
}

/**
 * Generar token CSRF
 */
function csrfToken(): string
{
    Session::start();
    
    if (!Session::has('csrf_token')) {
        Session::set('csrf_token', bin2hex(random_bytes(32)));
    }
    
    return Session::get('csrf_token');
}

/**
 * Verificar token CSRF
 */
function verifyCsrfToken(string $token): bool
{
    Session::start();
    return hash_equals(Session::get('csrf_token', ''), $token);
}

/**
 * Generar campo hidden de CSRF
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Obtener mensaje flash
 */
function flash(string $key): ?string
{
    return Session::getFlash($key);
}

/**
 * Establecer mensaje flash
 */
function setFlash(string $key, string $message): void
{
    Session::flash($key, $message);
}

/**
 * Dump and die (debug)
 */
function dd(...$vars): void
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Log de error
 */
function logError(string $message, array $context = []): void
{
    $logPath = __DIR__ . '/../../storage/logs/';
    
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }
    
    $logFile = $logPath . 'error-' . date('Y-m-d') . '.log';
    
    $logMessage = sprintf(
        "[%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log de información
 */
function logInfo(string $message, array $context = []): void
{
    $logPath = __DIR__ . '/../../storage/logs/';
    
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }
    
    $logFile = $logPath . 'info-' . date('Y-m-d') . '.log';
    
    $logMessage = sprintf(
        "[%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Obtener extensión de archivo
 */
function getFileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generar nombre de archivo único
 */
function generateUniqueFilename(string $originalName): string
{
    $extension = getFileExtension($originalName);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Validar extensión de archivo
 */
function isAllowedExtension(string $filename, array $allowed): bool
{
    $extension = getFileExtension($filename);
    return in_array($extension, $allowed);
}

/**
 * Obtener tamaño de archivo legible
 */
function getReadableFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Truncar texto
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Slug de texto
 */
function slug(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    
    // Reemplazar caracteres especiales
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
        $text
    );
    
    // Reemplazar espacios y caracteres no alfanuméricos
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Eliminar guiones al inicio y final
    return trim($text, '-');
}

/**
 * Verificar si la petición es AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Obtener IP del cliente
 */
function getClientIp(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Obtener año actual
 */
function currentYear(): int
{
    return (int) date('Y');
}

/**
 * Obtener mes actual en español
 */
function currentMonth(): string
{
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    return $meses[(int) date('n')];
}

/**
 * Obtener array de años para select
 */
function getYearsArray(int $startYear = 2020, ?int $endYear = null): array
{
    if ($endYear === null) {
        $endYear = (int) date('Y');
    }
    
    $years = [];
    for ($year = $endYear; $year >= $startYear; $year--) {
        $years[] = $year;
    }
    
    return $years;
}

/**
 * Obtener array de meses
 */
function getMonthsArray(): array
{
    return [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
}
