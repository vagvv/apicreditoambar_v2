<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function getPdo(): ?PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $db = Env::get('DB_NAME', 'bdcreditoambar');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASSWORD', '');

        if ($host === '' || $db === '') {
            return null;
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

        try {
            self::$pdo = new PDO($dsn, (string) $user, (string) $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException) {
            return null;
        }

        return self::$pdo;
    }
}
