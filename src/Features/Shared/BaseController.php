<?php

declare(strict_types=1);

namespace App\Features\Shared;

use App\Config\Database;
use App\Helpers\JsonResponse;
use PDO;

abstract class BaseController
{
    protected PDO $pdo;

    public function __construct()
    {
        $pdo = Database::getPdo();
        if (!$pdo instanceof PDO) {
            JsonResponse::error('No fue posible conectar con la base de datos', 503);
        }
        $this->pdo = $pdo;
    }

    protected function readJson(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function insertRecord(string $table, array $fields): int
    {
        if ($fields === []) {
            throw new \InvalidArgumentException('No hay campos para insertar.');
        }

        $columns = array_keys($fields);
        $quotedColumns = array_map(static fn(string $column): string => '`' . $column . '`', $columns);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($fields);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function updateRecord(string $table, int $id, array $fields): bool
    {
        if ($fields === []) {
            return false;
        }

        $assignments = array_map(static fn(string $column): string => '`' . $column . '` = :' . $column, array_keys($fields));
        $statement = $this->pdo->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $assignments) . ' WHERE `id` = :record_id');
        $statement->execute([...$fields, 'record_id' => $id]);

        if ($statement->rowCount() > 0) {
            return true;
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM `' . $table . '` WHERE `id` = ? LIMIT 1');
        $exists->execute([$id]);
        return $exists->fetchColumn() !== false;
    }

    /**
     * @param array<string, string|array<int, string>> $columns
     * @return array<string, mixed>
     */
    protected function mapInput(array $input, array $columns): array
    {
        $fields = [];
        foreach ($columns as $column => $keys) {
            foreach ((array) $keys as $key) {
                if (array_key_exists($key, $input)) {
                    $fields[$column] = $input[$key];
                    break;
                }
            }
        }

        return $fields;
    }
}
