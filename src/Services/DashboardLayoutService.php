<?php
// src/Services/DashboardLayoutService.php

namespace App\Services;

use App\Core\Database;

class DashboardLayoutService
{
    private const TABLE = 'dashboard_widgets';

    public function getDefaultLayout(): array
    {
        return [
            ['id' => 'kpi_total_transacciones', 'enabled' => true],
            ['id' => 'kpi_total_negociado', 'enabled' => true],
            ['id' => 'kpi_total_comision', 'enabled' => true],
            ['id' => 'kpi_total_ruedas', 'enabled' => true],
            ['id' => 'kpi_total_clientes', 'enabled' => false],
            ['id' => 'kpi_total_margen', 'enabled' => false],
            ['id' => 'chart_negociado_mes', 'enabled' => true],
            ['id' => 'chart_top_corredores', 'enabled' => true],
            ['id' => 'chart_transacciones_mes', 'enabled' => false],
            ['id' => 'chart_comision_mes', 'enabled' => false],
            ['id' => 'table_por_mes', 'enabled' => true],
            ['id' => 'table_ultimas_ruedas', 'enabled' => true],
            ['id' => 'table_top_clientes', 'enabled' => false]
        ];
    }

    public function getLayout(int $userId): array
    {
        $this->ensureTable();

        $row = Database::fetch(
            'SELECT layout_json FROM ' . self::TABLE . ' WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        if (!$row || empty($row['layout_json'])) {
            return $this->getDefaultLayout();
        }

        $decoded = json_decode($row['layout_json'], true);
        if (!is_array($decoded)) {
            return $this->getDefaultLayout();
        }

        return $this->normalizeLayout($decoded);
    }

    public function saveLayout(int $userId, array $layout): void
    {
        $this->ensureTable();

        $normalized = $this->normalizeLayout($layout);
        $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE);

        $exists = Database::fetch(
            'SELECT user_id FROM ' . self::TABLE . ' WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        if ($exists) {
            Database::update(self::TABLE, ['layout_json' => $payload], 'user_id = :user_id', [
                'user_id' => $userId
            ]);
            return;
        }

        Database::insert(self::TABLE, [
            'user_id' => $userId,
            'layout_json' => $payload
        ]);
    }

    private function normalizeLayout(array $layout): array
    {
        $defaults = $this->getDefaultLayout();
        $defaultMap = [];
        foreach ($defaults as $item) {
            $defaultMap[$item['id']] = $item;
        }

        $result = [];
        foreach ($layout as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }
            $id = $item['id'];
            if (!isset($defaultMap[$id])) {
                continue;
            }
            $merged = $defaultMap[$id];
            if (array_key_exists('enabled', $item)) {
                $merged['enabled'] = (bool) $item['enabled'];
            }
            $result[] = $merged;
            unset($defaultMap[$id]);
        }

        foreach ($defaultMap as $item) {
            $result[] = $item;
        }

        return $result;
    }

    private function ensureTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS " . self::TABLE . " (
                user_id INT NOT NULL PRIMARY KEY,
                layout_json TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        Database::query($sql);
    }
}
