<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\Env;

final class Jwt
{
    public static function sign(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $secret = (string) Env::get('JWT_SECRET', 'local-secret');
        $base64Header = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $base64Payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
        return $base64Header . '.' . $base64Payload . '.' . self::base64UrlEncode($signature);
    }

    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $secret = (string) Env::get('JWT_SECRET', 'local-secret');
        $expected = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
        if (!hash_equals(self::base64UrlEncode($expected), $signature)) {
            return null;
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);
        if (!is_array($decoded)) {
            return null;
        }

        $exp = (int) ($decoded['exp'] ?? 0);
        if ($exp > 0 && $exp < time()) {
            return null;
        }

        return $decoded;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
