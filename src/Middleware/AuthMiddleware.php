<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Jwt;

final class AuthMiddleware
{
    /**
     * @param array<int, string> $roles
     */
    public static function requireRoles(array $roles): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches) ? trim($matches[1]) : null;
        $token ??= $_SERVER['HTTP_X_AUTH_TOKEN'] ?? null;

        if (!$token || !is_string($token)) {
            return null;
        }

        $claims = Jwt::verify($token);
        if (!$claims) {
            return null;
        }

        $role = (string) ($_SERVER['HTTP_X_AUTH_ROLE'] ?? $claims['role'] ?? '');
        if ($roles !== [] && $role !== '' && !in_array($role, $roles, true)) {
            return null;
        }

        if ($roles !== [] && !in_array((string) ($claims['role'] ?? ''), $roles, true)) {
            return null;
        }

        return $claims;
    }
}
