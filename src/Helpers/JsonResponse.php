<?php

declare(strict_types=1);

namespace App\Helpers;

final class JsonResponse
{
    public static function send(mixed $payload, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        foreach ($headers as $header => $value) {
            header($header . ': ' . $value);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(mixed $payload = null, int $status = 200): void
    {
        self::send($payload, $status);
    }

    public static function error(string $message, int $status = 400, array $details = []): void
    {
        $payload = ['message' => $message];
        if ($details !== []) {
            $payload['details'] = $details;
        }
        self::send($payload, $status);
    }

    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }
}
