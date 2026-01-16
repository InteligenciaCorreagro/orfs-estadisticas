<?php
// modules/benchmark/controllers/BenchmarkController.php

namespace App\Controllers\BusinessIntelligence;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\BusinessIntelligence\BMCApiClient;

class BenchmarkController
{
    private BMCApiClient $client;

    public function __construct()
    {
        $this->client = new BMCApiClient();
    }

    public function dashboard(): void
    {
        Session::start();
        $user = auth();

        $data = [
            'user' => $user,
            'availableYears' => getYearsArray(2021, currentYear()),
            'defaultYear' => currentYear()
        ];

        $this->render('dashboard', $data);
    }

    public function comparativa(): void
    {
        Session::start();
        $user = auth();

        $data = [
            'user' => $user,
            'availableYears' => getYearsArray(2021, currentYear()),
            'defaultYear' => currentYear()
        ];

        $this->render('comparativa', $data);
    }

    public function sectores(): void
    {
        Session::start();
        $user = auth();

        $data = [
            'user' => $user,
            'availableYears' => getYearsArray(2021, currentYear()),
            'defaultYear' => currentYear()
        ];

        $this->render('sectores', $data);
    }

    public function temporal(): void
    {
        Session::start();
        $user = auth();

        $data = [
            'user' => $user,
            'availableYears' => getYearsArray(2021, currentYear()),
            'defaultYear' => currentYear()
        ];

        $this->render('temporal', $data);
    }

    public function reportes(): void
    {
        Session::start();
        $user = auth();

        $data = [
            'user' => $user,
            'availableYears' => getYearsArray(2021, currentYear()),
            'defaultYear' => currentYear()
        ];

        $this->render('reportes', $data);
    }

    public function summary(Request $request): void
    {
        $result = $this->client->getSummary();
        $this->respond($result);
    }

    public function reports(Request $request): void
    {
        $limit = (int) $request->get('limit', 50);
        $year = $request->get('year');
        $yearValue = $year !== null ? (int) $year : null;

        $result = $this->client->getReports($limit, $yearValue);
        $this->respond($result);
    }

    public function report(Request $request): void
    {
        $id = (int) $request->get('id');
        if ($id <= 0) {
            (new Response())->error('ID requerido', [], 422);
            return;
        }

        $result = $this->client->getReport($id);
        $this->respond($result);
    }

    public function compare(Request $request): void
    {
        $idsParam = (string) $request->get('ids', '');
        $ids = array_values(array_filter(array_map('trim', explode(',', $idsParam))));

        if (empty($ids)) {
            (new Response())->error('Ids requeridos', [], 422);
            return;
        }

        $result = $this->client->compare($ids);
        $this->respond($result);
    }

    public function trendsScb(Request $request): void
    {
        $scb = (string) $request->get('scb', '');
        if ($scb === '') {
            (new Response())->error('SCB requerido', [], 422);
            return;
        }

        $result = $this->client->getTrendsScb($scb);
        $this->respond($result);
    }

    public function trendsSectores(Request $request): void
    {
        $result = $this->client->getTrendsSectores();
        $this->respond($result);
    }

    public function analyze(Request $request): void
    {
        if (!$request->hasFile('file')) {
            (new Response())->error('Archivo requerido', [], 422);
            return;
        }

        $file = $request->file('file');
        $usuario = (string) $request->post('usuario', '');

        $result = $this->client->analyzeFile($file['tmp_name'], $file['name'], $usuario);
        $this->respond($result);
    }

    public function exportCsv(Request $request): void
    {
        $year = $request->get('year');
        $yearValue = $year !== null ? (int) $year : null;

        $result = $this->client->getReports(200, $yearValue);
        if (!($result['success'] ?? false)) {
            (new Response())->error('No se pudo generar CSV', [], 502);
            return;
        }

        $csv = $this->buildCsvFromReports($result['data'] ?? []);
        $filename = 'benchmark_reportes_' . ($yearValue ?: 'todos') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit;
    }

    public function exportPdf(Request $request): void
    {
        (new Response())->error('Export PDF pendiente', [], 501);
    }

    public function exportExcel(Request $request): void
    {
        (new Response())->error('Export Excel pendiente', [], 501);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);

        ob_start();
        require __DIR__ . '/../../../modules/benchmark/views/' . $view . '.php';
        $content = ob_get_clean();

        (new Response())->html($content);
    }

    private function respond(array $result): void
    {
        $response = new Response();
        $success = (bool) ($result['success'] ?? false);

        if (!$success) {
            $response->json([
                'success' => false,
                'message' => $result['error'] ?? 'Error al consultar API',
                'data' => $result['data'] ?? null
            ], (int) ($result['status'] ?? 502));
            return;
        }

        $response->json([
            'success' => true,
            'data' => $result['data'] ?? [],
            'message' => $result['message'] ?? null
        ]);
    }

    /**
     * Construye CSV desde la respuesta del API de reportes.
     *
     * @param array $payload
     * @return string
     */
    private function buildCsvFromReports(array $payload): string
    {
        $rows = $this->extractReportRows($payload);

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        $header = [
            'Posicion',
            'SCB',
            'Participacion',
            'Volumen_Millones',
            'Crecimiento',
            'Comision_COP',
            'Negociado',
            'Margen_Porcentaje'
        ];
        fputcsv($handle, $header);

        foreach ($rows as $row) {
            $normalized = $this->normalizeReportRow($row);
            fputcsv($handle, [
                $normalized['position'],
                $normalized['scb'],
                number_format($normalized['share'], 6, '.', ''),
                number_format($normalized['volume_millions'], 4, '.', ''),
                number_format($normalized['growth'], 6, '.', ''),
                number_format($normalized['commission'], 2, '.', ''),
                number_format($normalized['negotiated'], 2, '.', ''),
                number_format($normalized['margin'] * 100, 5, '.', '')
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /**
     * @param array $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractReportRows(array $payload): array
    {
        if (isset($payload[0]) && is_array($payload[0])) {
            return $payload;
        }

        foreach (['reports', 'data', 'items', 'rows', 'result'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach (['reports', 'items', 'rows'] as $key) {
                if (isset($payload['data'][$key]) && is_array($payload['data'][$key])) {
                    return $payload['data'][$key];
                }
            }
        }

        return [];
    }

    /**
     * Normaliza un registro de reporte.
     *
     * @param array $row
     * @return array<string, mixed>
     */
    private function normalizeReportRow(array $row): array
    {
        $name = $row['scb'] ?? $row['comisionista'] ?? $row['nombre'] ?? $row['name'] ?? $row['razon_social'] ?? 'N/D';
        $position = (int) ($row['position'] ?? $row['posicion'] ?? $row['rank'] ?? $row['ranking'] ?? 0);
        $share = (float) ($row['participacion'] ?? $row['market_share'] ?? $row['share'] ?? 0);
        $growth = (float) ($row['crecimiento'] ?? $row['growth'] ?? $row['growth_pct'] ?? 0);

        $volumeRaw = $row['volumen_millones'] ?? $row['volume_millions'] ?? $row['volumen'] ?? $row['volume'] ?? 0;
        $volumeMillions = $this->toMillions((float) $volumeRaw, isset($row['volumen_millones']) || isset($row['volume_millions']));

        $commission = (float) ($row['comision'] ?? $row['commission'] ?? $row['comision_cop'] ?? 0);
        $negotiated = (float) ($row['negociado'] ?? $row['traded'] ?? $row['trading_volume'] ?? $row['volume'] ?? $row['volumen'] ?? 0);
        $margin = $negotiated > 0 ? $commission / $negotiated : 0.0;

        return [
            'position' => $position,
            'scb' => $name,
            'share' => $share,
            'volume_millions' => $volumeMillions,
            'growth' => $growth,
            'commission' => $commission,
            'negotiated' => $negotiated,
            'margin' => $margin
        ];
    }

    /**
     * Convierte a millones si el valor viene en unidades completas.
     *
     * @param float $value
     * @param bool $alreadyMillions
     * @return float
     */
    private function toMillions(float $value, bool $alreadyMillions): float
    {
        if ($alreadyMillions) {
            return $value;
        }

        if ($value > 0 && $value < 10000) {
            return $value;
        }

        return $value / 1000000;
    }
}

