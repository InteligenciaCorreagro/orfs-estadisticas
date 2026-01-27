<?php
// modules/benchmark/controllers/BenchmarkController.php

namespace App\Controllers\BusinessIntelligence;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
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

    public function productos(): void
    {
        Session::start();
        $user = auth();

        $data = [
            'user' => $user,
            'availableYears' => getYearsArray(2021, currentYear()),
            'defaultYear' => currentYear()
        ];

        $this->render('productos', $data);
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
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ini_set')) {
            @ini_set('max_execution_time', '0');
        }

        if (!$request->hasFile('file')) {
            $file = $request->file('file');
            if (is_array($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
                $message = match ($file['error']) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Archivo demasiado grande',
                    UPLOAD_ERR_PARTIAL => 'Archivo subido parcialmente',
                    UPLOAD_ERR_NO_FILE => 'Archivo requerido',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo',
                    UPLOAD_ERR_EXTENSION => 'Carga detenida por extension',
                    default => 'Error de carga de archivo'
                };
                (new Response())->error($message, [], 422);
                return;
            }

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

    public function productPeriods(Request $request): void
    {
        $year = (int) $request->get('year', currentYear());

        $rows = Database::fetchAll(
            'SELECT id, periodo, fecha_carga FROM bmc_reportes WHERE (periodo LIKE :pattern OR YEAR(fecha_carga) = :year) ORDER BY fecha_carga ASC',
            ['pattern' => '%' . $year . '%', 'year' => $year]
        );

        (new Response())->json([
            'success' => true,
            'data' => $rows
        ]);
    }

    public function products(Request $request): void
    {
        $year = (int) $request->get('year', currentYear());
        $limit = max(1, (int) $request->get('limit', 50));
        $reportId = (int) $request->get('report_id', 0);
        $periodo = trim((string) $request->get('periodo', ''));

        $periodLabel = null;

        if ($reportId <= 0 && $periodo !== '') {
            $reportRow = Database::fetch(
                'SELECT id, periodo FROM bmc_reportes WHERE periodo = :periodo AND (periodo LIKE :pattern OR YEAR(fecha_carga) = :year) ORDER BY fecha_carga DESC LIMIT 1',
                ['periodo' => $periodo, 'pattern' => '%' . $year . '%', 'year' => $year]
            );
            if ($reportRow) {
                $reportId = (int) $reportRow['id'];
                $periodLabel = $reportRow['periodo'] ?? null;
            }
        }

        if ($reportId > 0) {
            $reportRow = Database::fetch(
                'SELECT periodo FROM bmc_reportes WHERE id = :id',
                ['id' => $reportId]
            );
            if ($reportRow) {
                $periodLabel = $reportRow['periodo'] ?? $periodLabel;
            }

            $rows = Database::fetchAll(
                'SELECT * FROM bmc_productos_sector WHERE reporte_id = :id',
                ['id' => $reportId]
            );

            $items = array_map(fn(array $row) => $this->normalizeProductoRow($row), $rows);
            usort($items, fn(array $a, array $b) => ($b['monto_millones'] <=> $a['monto_millones']));
            $items = array_slice($items, 0, $limit);

            (new Response())->json([
                'success' => true,
                'data' => [
                    'scope' => 'period',
                    'periodo' => $periodLabel,
                    'items' => $items
                ]
            ]);
            return;
        }

        $rows = Database::fetchAll(
            'SELECT p.*, r.periodo, r.fecha_carga FROM bmc_productos_sector p ' .
            'JOIN bmc_reportes r ON r.id = p.reporte_id ' .
            'WHERE (r.periodo LIKE :pattern OR YEAR(r.fecha_carga) = :year)',
            ['pattern' => '%' . $year . '%', 'year' => $year]
        );

        if (!$rows) {
            (new Response())->json([
                'success' => true,
                'data' => [
                    'scope' => 'general',
                    'periodo' => null,
                    'items' => []
                ]
            ]);
            return;
        }

        $aggregated = [];
        foreach ($rows as $row) {
            $item = $this->normalizeProductoRow($row);
            $key = strtolower(trim($item['producto'])) . '|' . strtolower(trim($item['sector']));

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'producto' => $item['producto'],
                    'sector' => $item['sector'],
                    'monto_millones' => 0.0,
                    'participacion_pct' => 0.0,
                    'variacion_pct' => 0.0,
                    '_count' => 0
                ];
            }

            $aggregated[$key]['monto_millones'] += (float) $item['monto_millones'];
            $aggregated[$key]['participacion_pct'] += (float) $item['participacion_pct'];
            $aggregated[$key]['variacion_pct'] += (float) $item['variacion_pct'];
            $aggregated[$key]['_count']++;
        }

        $items = array_map(function (array $item) {
            $count = max(1, (int) $item['_count']);
            unset($item['_count']);
            $item['participacion_pct'] = $item['participacion_pct'] / $count;
            $item['variacion_pct'] = $item['variacion_pct'] / $count;
            return $item;
        }, array_values($aggregated));

        usort($items, fn(array $a, array $b) => ($b['monto_millones'] <=> $a['monto_millones']));
        $items = array_slice($items, 0, $limit);

        (new Response())->json([
            'success' => true,
            'data' => [
                'scope' => 'general',
                'periodo' => null,
                'items' => $items
            ]
        ]);
    }

    public function productsMonthly(Request $request): void
    {
        $year = (int) $request->get('year', currentYear());
        $limit = max(1, (int) $request->get('limit', 20));

        $rows = Database::fetchAll(
            'SELECT p.*, r.periodo, r.fecha_carga FROM bmc_productos_sector p ' .
            'JOIN bmc_reportes r ON r.id = p.reporte_id ' .
            'WHERE (r.periodo LIKE :pattern OR YEAR(r.fecha_carga) = :year)',
            ['pattern' => '%' . $year . '%', 'year' => $year]
        );

        if (!$rows) {
            (new Response())->json([
                'success' => true,
                'data' => []
            ]);
            return;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $periodo = $row['periodo'] ?? 'Sin periodo';
            if (!isset($grouped[$periodo])) {
                $grouped[$periodo] = [
                    'fecha' => $row['fecha_carga'] ?? null,
                    'items' => []
                ];
            }

            $item = $this->normalizeProductoRow($row);
            $item['periodo'] = $periodo;
            $grouped[$periodo]['items'][] = $item;
        }

        $periods = array_keys($grouped);
        usort($periods, function ($a, $b) use ($grouped) {
            $dateA = isset($grouped[$a]['fecha']) ? strtotime((string) $grouped[$a]['fecha']) : 0;
            $dateB = isset($grouped[$b]['fecha']) ? strtotime((string) $grouped[$b]['fecha']) : 0;
            return $dateA <=> $dateB;
        });

        $flat = [];
        foreach ($periods as $periodo) {
            $items = $grouped[$periodo]['items'];
            usort($items, fn(array $a, array $b) => ($b['monto_millones'] <=> $a['monto_millones']));
            $items = array_slice($items, 0, $limit);
            foreach ($items as $item) {
                $flat[] = $item;
            }
        }

        (new Response())->json([
            'success' => true,
            'data' => $flat
        ]);
    }

    public function analysisPeriods(Request $request): void
    {
        $year = (int) $request->get('year', currentYear());

        $rows = Database::fetchAll(
            'SELECT id, periodo, fecha_carga FROM bmc_reportes WHERE (periodo LIKE :pattern OR YEAR(fecha_carga) = :year) ORDER BY fecha_carga ASC',
            ['pattern' => '%' . $year . '%', 'year' => $year]
        );

        (new Response())->json([
            'success' => true,
            'data' => $rows
        ]);
    }

    public function analysis(Request $request): void
    {
        $reportId = (int) $request->get('report_id', 0);
        $year = (int) $request->get('year', currentYear());

        if ($reportId <= 0) {
            $reportRow = Database::fetch(
                'SELECT id, periodo, fecha_carga, datos_json FROM bmc_reportes WHERE (periodo LIKE :pattern OR YEAR(fecha_carga) = :year) ORDER BY fecha_carga DESC LIMIT 1',
                ['pattern' => '%' . $year . '%', 'year' => $year]
            );
        } else {
            $reportRow = Database::fetch(
                'SELECT id, periodo, fecha_carga, datos_json FROM bmc_reportes WHERE id = :id',
                ['id' => $reportId]
            );
        }

        if (!$reportRow || empty($reportRow['datos_json'])) {
            (new Response())->error('Sin datos para el reporte seleccionado', [], 404);
            return;
        }

        $decoded = json_decode($reportRow['datos_json'], true);
        if (!is_array($decoded)) {
            (new Response())->error('JSON de reporte invalido', [], 500);
            return;
        }

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $decoded = $decoded['data'];
        }

        $sections = $this->extractAnalysisSections($decoded);

        $tables = [
            'ranking_scb_acumulado' => $this->normalizeRankingList($sections['ranking_scb_acumulado'], ['comisionista', 'scb', 'nombre']),
            'ranking_scb_mes' => $this->normalizeRankingList($sections['ranking_scb_mes'], ['comisionista', 'scb', 'nombre']),
            'ranking_sector_total_acumulado' => $this->normalizeRankingList($sections['ranking_sector_total_acumulado'], ['sector', 'nombre']),
            'ranking_sector_total_mes' => $this->normalizeRankingList($sections['ranking_sector_total_mes'], ['sector', 'nombre']),
            'ranking_sector_scb_acumulado' => $this->normalizeRankingList($sections['ranking_sector_scb_acumulado'], ['sector', 'comisionista', 'scb', 'nombre']),
            'ranking_sector_scb_mes' => $this->normalizeRankingList($sections['ranking_sector_scb_mes'], ['sector', 'comisionista', 'scb', 'nombre']),
            'repos_cdm_subyacente_acumulado' => $this->normalizeRankingList($sections['repos_cdm_subyacente_acumulado'], ['subyacente', 'nombre', 'producto']),
            'repos_cdm_subyacente_mes' => $this->normalizeRankingList($sections['repos_cdm_subyacente_mes'], ['subyacente', 'nombre', 'producto']),
            'repos_cdm_scb_acumulado' => $this->normalizeRankingList($sections['repos_cdm_scb_acumulado'], ['comisionista', 'scb', 'nombre']),
            'repos_cdm_scb_mes' => $this->normalizeRankingList($sections['repos_cdm_scb_mes'], ['comisionista', 'scb', 'nombre'])
        ];

        $products = $this->flattenProductosPorSector($sections['productos_por_sector']);

        $growth = [
            'scb_acumulado' => $this->calculateGrowth($tables['ranking_scb_acumulado']),
            'scb_mes' => $this->calculateGrowth($tables['ranking_scb_mes']),
            'sector_acumulado' => $this->calculateGrowth($tables['ranking_sector_total_acumulado']),
            'sector_mes' => $this->calculateGrowth($tables['ranking_sector_total_mes'])
        ];

        $competition = $this->buildCompetition($tables['ranking_scb_acumulado'], $tables['ranking_scb_mes'], (string) $request->get('scb', ''));

        $charts = [
            'top_scb_acumulado' => $this->prepareChartDataset($tables['ranking_scb_acumulado'], 5),
            'top_scb_mes' => $this->prepareChartDataset($tables['ranking_scb_mes'], 5),
            'top_sector_acumulado' => $this->prepareChartDataset($tables['ranking_sector_total_acumulado'], 5),
            'top_sector_mes' => $this->prepareChartDataset($tables['ranking_sector_total_mes'], 5)
        ];

        (new Response())->json([
            'success' => true,
            'data' => [
                'report' => [
                    'id' => (int) $reportRow['id'],
                    'periodo' => $reportRow['periodo'] ?? null
                ],
                'sections' => $sections,
                'tables' => $tables,
                'products' => $products,
                'growth' => $growth,
                'competition' => $competition,
                'charts' => $charts
            ]
        ]);
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

        foreach (['reports', 'reportes', 'data', 'items', 'rows', 'result', 'ranking', 'ranking_scb', 'scb', 'records', 'list'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach (['reports', 'reportes', 'items', 'rows', 'ranking', 'ranking_scb', 'scb', 'records', 'list'] as $key) {
                if (isset($payload['data'][$key]) && is_array($payload['data'][$key])) {
                    return $payload['data'][$key];
                }
            }
        }

        if (isset($payload['ranking_scb']) && is_array($payload['ranking_scb'])) {
            foreach (['items', 'rows', 'data', 'results'] as $key) {
                if (isset($payload['ranking_scb'][$key]) && is_array($payload['ranking_scb'][$key])) {
                    return $payload['ranking_scb'][$key];
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
        $share = (float) ($row['participacion'] ?? $row['participacion_pct'] ?? $row['participacion_actual_pct'] ?? $row['market_share'] ?? $row['share'] ?? 0);
        $growth = (float) ($row['variacion_pct'] ?? $row['crecimiento'] ?? $row['growth'] ?? $row['growth_pct'] ?? 0);

        $volumeRaw = $row['monto_millones'] ?? $row['volumen_millones'] ?? $row['volume_millions'] ?? $row['volumen'] ?? $row['volume'] ?? 0;
        $volumeMillions = $this->toMillions(
            (float) $volumeRaw,
            isset($row['monto_millones']) || isset($row['volumen_millones']) || isset($row['volume_millions'])
        );

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

    /**
     * @param array $row
     * @return array<string, mixed>
     */
    private function normalizeProductoRow(array $row): array
    {
        $montoActual = $this->getRowValueBySuffix($row, '_actual_millones');
        $montoAnterior = $this->getRowValueBySuffix($row, '_anterior_millones');

        return [
            'producto' => $row['producto'] ?? 'N/D',
            'sector' => $row['sector'] ?? 'N/D',
            'monto_millones' => (float) $montoActual,
            'monto_anterior_millones' => (float) $montoAnterior,
            'participacion_pct' => (float) ($row['participacion_pct'] ?? 0),
            'variacion_pct' => (float) ($row['variacion_pct'] ?? 0)
        ];
    }

    private function getRowValueBySuffix(array $row, string $suffix): float
    {
        foreach ($row as $key => $value) {
            if (is_string($key) && str_ends_with($key, $suffix)) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAnalysisSections(array $data): array
    {
        $legacyScb = $this->ensureList($data['ranking_scb'] ?? []);
        $legacySector = $this->ensureList($data['ranking_sectores'] ?? []);
        $legacySubyacente = $this->ensureList($data['repos_cdm_subyacente'] ?? []);
        $legacyScbRepos = $this->ensureList($data['repos_cdm_scb'] ?? []);

        return [
            'ranking_scb_acumulado' => $this->ensureList($data['ranking_scb_acumulado'] ?? $legacyScb),
            'ranking_scb_mes' => $this->ensureList($data['ranking_scb_mes'] ?? []),
            'ranking_sector_total_acumulado' => $this->ensureList($data['ranking_sector_total_acumulado'] ?? $legacySector),
            'ranking_sector_total_mes' => $this->ensureList($data['ranking_sector_total_mes'] ?? []),
            'ranking_sector_scb_acumulado' => $this->ensureList($data['ranking_sector_scb_acumulado'] ?? []),
            'ranking_sector_scb_mes' => $this->ensureList($data['ranking_sector_scb_mes'] ?? []),
            'repos_cdm_subyacente_acumulado' => $this->ensureList($data['repos_cdm_subyacente_acumulado'] ?? $legacySubyacente),
            'repos_cdm_subyacente_mes' => $this->ensureList($data['repos_cdm_subyacente_mes'] ?? []),
            'repos_cdm_scb_acumulado' => $this->ensureList($data['repos_cdm_scb_acumulado'] ?? $legacyScbRepos),
            'repos_cdm_scb_mes' => $this->ensureList($data['repos_cdm_scb_mes'] ?? []),
            'productos_por_sector' => $data['productos_por_sector'] ?? []
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ensureList($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $labelKeys
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRankingList(array $rows, array $labelKeys): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = $this->extractLabel($row, $labelKeys);
            $sector = $row['sector'] ?? $row['sector_nombre'] ?? null;
            $scb = $row['scb'] ?? $row['comisionista'] ?? $row['nombre'] ?? null;
            $actual = $this->extractActualValue($row);
            $previous = $this->extractPreviousValue($row);
            $share = $this->extractShareValue($row);
            $varAbs = isset($row['variacion_absoluta_millones'])
                ? (float) $row['variacion_absoluta_millones']
                : $actual - $previous;
            $varPct = isset($row['variacion_pct'])
                ? (float) $row['variacion_pct']
                : $this->calculatePct($previous, $actual);

            $normalized[] = [
                'name' => $label,
                'sector' => $sector,
                'scb' => $scb,
                'actual' => $actual,
                'previous' => $previous,
                'share' => $share,
                'var_abs' => $varAbs,
                'var_pct' => $varPct
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function calculateGrowth(array $rows): array
    {
        return array_map(function (array $row) {
            return [
                'name' => $row['name'] ?? 'N/D',
                'actual' => (float) ($row['actual'] ?? 0),
                'previous' => (float) ($row['previous'] ?? 0),
                'var_abs' => (float) ($row['var_abs'] ?? 0),
                'var_pct' => (float) ($row['var_pct'] ?? 0)
            ];
        }, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function prepareChartDataset(array $rows, int $top = 5): array
    {
        if (empty($rows)) {
            return ['labels' => [], 'values' => []];
        }

        usort($rows, fn($a, $b) => ($b['actual'] ?? 0) <=> ($a['actual'] ?? 0));
        $rows = array_slice($rows, 0, $top);

        return [
            'labels' => array_map(fn($row) => $row['name'] ?? 'N/D', $rows),
            'values' => array_map(fn($row) => (float) ($row['actual'] ?? 0), $rows)
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $acumulado
     * @param array<int, array<string, mixed>> $mes
     * @return array<string, mixed>
     */
    private function buildCompetition(array $acumulado, array $mes, string $scbParam): array
    {
        $topAcum = $this->topN($acumulado, 5);
        $topMes = $this->topN($mes, 5);

        $targets = array_values(array_filter(array_map('trim', explode(',', $scbParam))));
        if (empty($targets)) {
            $targets = array_slice(array_column($topAcum, 'name'), 0, 3);
        }

        $comparison = $this->filterByNames($acumulado, $targets);
        $participation = array_map(function ($row) {
            return [
                'name' => $row['name'] ?? 'N/D',
                'share' => (float) ($row['share'] ?? 0),
                'actual' => (float) ($row['actual'] ?? 0)
            ];
        }, $topAcum);

        return [
            'top5_acumulado' => $topAcum,
            'top5_mes' => $topMes,
            'comparison' => $comparison,
            'participation' => $participation
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $names
     * @return array<int, array<string, mixed>>
     */
    private function filterByNames(array $rows, array $names): array
    {
        $map = [];
        foreach ($rows as $row) {
            if (!isset($row['name'])) {
                continue;
            }
            $map[strtolower((string) $row['name'])] = $row;
        }

        $result = [];
        foreach ($names as $name) {
            $key = strtolower($name);
            if (isset($map[$key])) {
                $result[] = $map[$key];
            }
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function topN(array $rows, int $limit): array
    {
        usort($rows, fn($a, $b) => ($b['actual'] ?? 0) <=> ($a['actual'] ?? 0));
        return array_slice($rows, 0, $limit);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $keys
     */
    private function extractLabel(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (!empty($row[$key])) {
                return (string) $row[$key];
            }
        }

        foreach ($row as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return 'N/D';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractActualValue(array $row): float
    {
        $exactKeys = [
            'año_actual_millones',
            'anio_actual_millones',
            'a¤o_actual_millones',
            'monto_millones',
            'total_actual_millones',
            'actual_millones',
            'valor_actual',
            'valor'
        ];

        return $this->getRowValue($row, $exactKeys, ['_actual_millones']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractPreviousValue(array $row): float
    {
        $exactKeys = [
            'año_anterior_millones',
            'anio_anterior_millones',
            'a¤o_anterior_millones',
            'total_anterior_millones',
            'anterior_millones',
            'valor_anterior'
        ];

        return $this->getRowValue($row, $exactKeys, ['_anterior_millones']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractShareValue(array $row): float
    {
        $keys = [
            'participacion_actual_pct',
            'participacion_pct',
            'participacion',
            'share',
            'market_share'
        ];

        return $this->getRowValue($row, $keys, []);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $keys
     * @param array<int, string> $suffixes
     */
    private function getRowValue(array $row, array $keys, array $suffixes): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return (float) $row[$key];
            }
        }

        foreach ($suffixes as $suffix) {
            $value = $this->getRowValueBySuffix($row, $suffix);
            if ($value !== 0.0) {
                return $value;
            }
        }

        return 0.0;
    }

    private function calculatePct(float $previous, float $current): float
    {
        if ($previous == 0.0) {
            return 0.0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * @param array<string, mixed> $productos
     * @return array<int, array<string, mixed>>
     */
    private function flattenProductosPorSector($productos): array
    {
        $rows = [];
        if (!is_array($productos)) {
            return $rows;
        }

        if (isset($productos[0]) && is_array($productos[0])) {
            foreach ($productos as $row) {
                if (is_array($row)) {
                    $rows[] = $this->normalizeProductoRow($row);
                }
            }
            return $rows;
        }

        foreach ($productos as $sector => $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $row['sector'] = $row['sector'] ?? $sector;
                $rows[] = $this->normalizeProductoRow($row);
            }
        }

        return $rows;
    }
}

