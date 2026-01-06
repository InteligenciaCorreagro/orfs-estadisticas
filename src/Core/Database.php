<?php
// src/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                    $_ENV['DB_HOST'],
                    $_ENV['DB_PORT'],
                    $_ENV['DB_NAME']
                );

                self::$instance = new PDO(
                    $dsn,
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASS'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                die("Error de conexión a la base de datos");
            }
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        // Filtrar y limpiar datos
        $cleanData = [];
        foreach ($data as $key => $value) {
            // Solo incluir claves válidas (no vacías, no numéricas)
            if (!is_string($key) || $key === '' || is_numeric($key)) {
                continue;
            }

            // Validar que el valor sea un tipo escalar o null
            if (!is_scalar($value) && !is_null($value)) {
                throw new \Exception(
                    "Valor inválido para la columna '{$key}' en tabla {$table}: " .
                    "se esperaba escalar o null, se recibió " . gettype($value)
                );
            }

            // Sanitizar nombre de columna (solo alfanuméricos y guion bajo)
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if ($sanitizedKey !== $key) {
                error_log("Columna renombrada: '{$key}' => '{$sanitizedKey}'");
            }

            $cleanData[$sanitizedKey] = $value;
        }

        if (empty($cleanData)) {
            throw new \Exception("No hay datos válidos para insertar en la tabla {$table}");
        }

        $columns = implode(', ', array_keys($cleanData));
        $placeholders = ':' . implode(', :', array_keys($cleanData));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        // Debug mejorado
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log("SQL INSERT: " . $sql);
            error_log("PARAMS: " . json_encode($cleanData, JSON_UNESCAPED_UNICODE));
        }

        try {
            $stmt = self::query($sql, $cleanData);
            return (int) self::getInstance()->lastInsertId();
        } catch (\PDOException $e) {
            // Log detallado del error
            error_log("ERROR EN INSERT:");
            error_log("  SQL: " . $sql);
            error_log("  Params: " . json_encode($cleanData, JSON_UNESCAPED_UNICODE));
            error_log("  PDO Error: " . $e->getMessage());
            error_log("  Error Code: " . $e->getCode());
            throw $e;
        }
    }
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $params = array_merge($data, $whereParams);
        $stmt = self::query($sql, $params);

        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }
}
