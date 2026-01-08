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
        $debugFile = __DIR__ . '/../../public/debug_log.txt';

        // DEBUG: Log datos de entrada
        file_put_contents($debugFile,
            "\n\n=== DATABASE INSERT CALLED ===\n" .
            "Tabla: {$table}\n" .
            "Datos originales (keys): " . implode(', ', array_keys($data)) . "\n" .
            "Total campos: " . count($data) . "\n",
            FILE_APPEND
        );

        // Filtrar y limpiar datos
        $cleanData = [];
        $rejected = [];
        foreach ($data as $key => $value) {
            // Solo incluir claves válidas (no vacías, no numéricas)
            if (!is_string($key) || $key === '' || is_numeric($key)) {
                $rejected[] = "Clave rechazada: '{$key}' (is_string: " . (is_string($key) ? 'true' : 'false') .
                             ", is_numeric: " . (is_numeric($key) ? 'true' : 'false') . ")";
                continue;
            }

            // Validar que el valor sea un tipo escalar o null
            if (!is_scalar($value) && !is_null($value)) {
                $rejected[] = "Valor no escalar en '{$key}': " . gettype($value);
                throw new \Exception(
                    "Valor inválido para la columna '{$key}' en tabla {$table}: " .
                    "se esperaba escalar o null, se recibió " . gettype($value)
                );
            }

            // Sanitizar nombre de columna (solo alfanuméricos y guion bajo)
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if ($sanitizedKey !== $key) {
                file_put_contents($debugFile,
                    "Columna renombrada: '{$key}' => '{$sanitizedKey}'\n",
                    FILE_APPEND
                );
            }

            $cleanData[$sanitizedKey] = $value;
        }

        // DEBUG: Log datos rechazados
        if (!empty($rejected)) {
            file_put_contents($debugFile,
                "Campos rechazados:\n" . implode("\n", $rejected) . "\n",
                FILE_APPEND
            );
        }

        if (empty($cleanData)) {
            file_put_contents($debugFile, "ERROR: No hay datos válidos\n", FILE_APPEND);
            throw new \Exception("No hay datos válidos para insertar en la tabla {$table}");
        }

        $columns = implode(', ', array_keys($cleanData));
        $placeholders = ':' . implode(', :', array_keys($cleanData));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        // DEBUG: Log SQL y parámetros
        file_put_contents($debugFile,
            "SQL: {$sql}\n" .
            "Campos limpios (" . count($cleanData) . "): " . implode(', ', array_keys($cleanData)) . "\n" .
            "Placeholders: {$placeholders}\n" .
            "Valores:\n" . json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND
        );

        try {
            $stmt = self::query($sql, $cleanData);
            $id = (int) self::getInstance()->lastInsertId();

            file_put_contents($debugFile, "✓ INSERT EXITOSO! ID: {$id}\n", FILE_APPEND);
            return $id;
        } catch (\PDOException $e) {
            // Log detallado del error
            file_put_contents($debugFile,
                "❌ ERROR PDO:\n" .
                "  Mensaje: " . $e->getMessage() . "\n" .
                "  Código: " . $e->getCode() . "\n" .
                "  SQL State: " . ($e->errorInfo[0] ?? 'N/A') . "\n" .
                "  SQL: {$sql}\n",
                FILE_APPEND
            );
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
        $debugFile = __DIR__ . '/../../public/debug_log.txt';

        file_put_contents($debugFile,
            "\n\n=== DATABASE DELETE CALLED ===\n" .
            "Tabla: {$table}\n" .
            "WHERE: {$where}\n" .
            "Params: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND
        );

        $sql = "DELETE FROM {$table} WHERE {$where}";

        file_put_contents($debugFile,
            "SQL: {$sql}\n",
            FILE_APPEND
        );

        try {
            $stmt = self::query($sql, $params);
            $rowCount = $stmt->rowCount();

            file_put_contents($debugFile,
                "✓ DELETE EXITOSO! Filas afectadas: {$rowCount}\n",
                FILE_APPEND
            );

            return $rowCount;
        } catch (\PDOException $e) {
            file_put_contents($debugFile,
                "❌ ERROR PDO en DELETE:\n" .
                "  Mensaje: " . $e->getMessage() . "\n" .
                "  Código: " . $e->getCode() . "\n" .
                "  SQL: {$sql}\n" .
                "  Params: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n",
                FILE_APPEND
            );
            throw $e;
        }
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
