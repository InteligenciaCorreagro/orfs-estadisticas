<?php
// modules/benchmark/services/BMCApiClient.php

namespace App\Services\BusinessIntelligence;

/**
 * Cliente HTTP para el API de Benchmark.
 */
class BMCApiClient
{
    private string $baseUrl;
    private int $timeout;
    private int $connectTimeout;
    private int $analyzeTimeout;
    private int $analyzeConnectTimeout;

    /**
     * @param string|null $baseUrl
     * @param int $timeout
     * @param int $connectTimeout
     */
    public function __construct(
        ?string $baseUrl = null,
        int $timeout = 20,
        int $connectTimeout = 6,
        ?int $analyzeTimeout = null,
        ?int $analyzeConnectTimeout = null
    )
    {
        $this->baseUrl = $baseUrl ?? env('BMC_API_BASE', 'http://localhost:7000');
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->analyzeTimeout = $analyzeTimeout ?? (int) env('BMC_API_ANALYZE_TIMEOUT', 300);
        $this->analyzeConnectTimeout = $analyzeConnectTimeout ?? (int) env('BMC_API_ANALYZE_CONNECT_TIMEOUT', $this->connectTimeout);
    }

    /**
     * @param int $limit
     * @param int|null $year
     * @return array
     */
    public function getReports(int $limit = 50, ?int $year = null): array
    {
        $query = ['limit' => $limit];
        if ($year !== null) {
            $query['year'] = $year;
        }

        return $this->request('GET', '/api/v1/reports', $query);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getReport(int $id): array
    {
        return $this->request('GET', '/api/v1/reports/' . $id);
    }

    /**
     * @param array<int, string> $ids
     * @return array
     */
    public function compare(array $ids): array
    {
        return $this->request('GET', '/api/v1/compare', ['ids' => implode(',', $ids)]);
    }

    /**
     * @param string $comisionista
     * @return array
     */
    public function getTrendsScb(string $comisionista): array
    {
        return $this->request('GET', '/api/v1/trends/scb/' . rawurlencode($comisionista));
    }

    /**
     * @return array
     */
    public function getTrendsSectores(): array
    {
        return $this->request('GET', '/api/v1/trends/sectores');
    }

    /**
     * @return array
     */
    public function getSummary(): array
    {
        return $this->request('GET', '/api/v1/stats/summary');
    }

    /**
     * @param string $filePath
     * @param string $filename
     * @param string $usuario
     * @return array
     */
    public function analyzeFile(string $filePath, string $filename, string $usuario): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'Archivo no encontrado',
                'status' => 400
            ];
        }

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        $file = curl_file_create($filePath, $mime, $filename);

        $analyzeBase = env('BMC_ANALYZE_BASE', env('BMC_PDF_API_BASE', $this->baseUrl));
        $analyzePath = env('BMC_ANALYZE_PATH', '/api/v1/analyze');
        if (!is_string($analyzePath) || trim($analyzePath) === '') {
            $analyzePath = '/api/v1/analyze';
        }

        $result = $this->request('POST', $analyzePath, [], [
            'usuario' => $usuario
        ], [
            'file' => $file
        ], $this->analyzeTimeout, $this->analyzeConnectTimeout, $analyzeBase);

        if (($result['status'] ?? 0) === 404 && $analyzePath === '/api/v1/analyze') {
            $fallback = $this->request('POST', '/analyze', [], [
                'usuario' => $usuario
            ], [
                'file' => $file
            ], $this->analyzeTimeout, $this->analyzeConnectTimeout, $analyzeBase);

            if (($fallback['status'] ?? 0) !== 404) {
                return $fallback;
            }
        }

        return $result;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $query
     * @param array $body
     * @param array $files
     * @return array
     */
    private function request(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $files = [],
        ?int $timeout = null,
        ?int $connectTimeout = null,
        ?string $baseUrl = null
    ): array
    {
        $url = $this->buildUrl($path, $query, $baseUrl);
        $method = strtoupper($method);
        $timeout = $timeout ?? $this->timeout;
        $connectTimeout = $connectTimeout ?? $this->connectTimeout;

        $ch = curl_init();
        if ($ch === false) {
            return [
                'success' => false,
                'error' => 'No se pudo inicializar cURL',
                'status' => 500
            ];
        }

        $headers = [
            'Accept: application/json'
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_CUSTOMREQUEST => $method
        ];

        if (!empty($files)) {
            $options[CURLOPT_POSTFIELDS] = array_merge($body, $files);
        } elseif ($method !== 'GET') {
            $payload = json_encode($body);
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = $payload;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'success' => false,
                'error' => $error ?: 'Error de comunicacion',
                'status' => 0
            ];
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            $success = $status >= 200 && $status < 300;
            return [
                'success' => $success,
                'data' => ['raw' => $response],
                'error' => $success ? null : 'Respuesta no JSON',
                'status' => $status
            ];
        }

        if ($status < 200 || $status >= 300) {
            return [
                'success' => false,
                'error' => $decoded['message'] ?? 'Error HTTP',
                'data' => $decoded,
                'status' => $status
            ];
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            return [
                'success' => false,
                'error' => $decoded['message'] ?? 'Error API',
                'data' => $decoded,
                'status' => $status
            ];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'status' => $status
        ];
    }

    /**
     * @param string $path
     * @param array $query
     * @return string
     */
    private function buildUrl(string $path, array $query = [], ?string $baseUrl = null): string
    {
        $path = '/' . ltrim($path, '/');
        $base = rtrim($baseUrl ?? $this->baseUrl, '/');
        $queryString = $this->buildQuery($query);

        return $base . $path . $queryString;
    }

    /**
     * @param array $params
     * @return string
     */
    private function buildQuery(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $encodedKey = $this->encodeKey((string) $key);
            $encodedValue = rawurlencode((string) $value);
            $parts[] = $encodedKey . '=' . $encodedValue;
        }

        return empty($parts) ? '' : '?' . implode('&', $parts);
    }

    /**
     * @param string $key
     * @return string
     */
    private function encodeKey(string $key): string
    {
        if ($key === 'year' || $key === 'anio') {
            return 'a%C3%B1o';
        }

        return rawurlencode($key);
    }
}

