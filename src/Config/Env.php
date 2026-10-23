<?php

declare(strict_types=1);

namespace App\Config;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;
        $file = $path . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($file)) {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', file_get_contents($file) ?: '');
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($key === '') {
                continue;
            }

            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
            $_SERVER[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}
