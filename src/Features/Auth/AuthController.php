<?php

declare(strict_types=1);

namespace App\Features\Auth;

use App\Features\Shared\BaseController;
use App\Helpers\JsonResponse;
use App\Helpers\Jwt;
use App\Config\Env;
use PDO;

final class AuthController extends BaseController
{
    public function login(): void
    {
        $body = $this->readJson();
        $user = (string) ($body['user'] ?? $_POST['user'] ?? '');
        $password = (string) ($body['password'] ?? $_POST['password'] ?? '');
        if ($user === '' || $password === '') {
            JsonResponse::error('Usuario y contraseña requeridos', 400);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, usuario, isadmin FROM usuario WHERE usuario = ? AND password = ? AND status = 1 LIMIT 1',
        );
        $stmt->execute([$user, $password]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($record)) {
            JsonResponse::unauthorized('Credenciales inválidas');
        }

        $role = (int) ($record['isadmin'] ?? 0) === 1 ? 'admin' : 'empleado';
        $token = Jwt::sign([
            'sub' => (int) $record['id'],
            'role' => $role,
            'user' => $user,
            'exp' => time() + (int) Env::get('JWT_TTL', 3600),
        ]);

        JsonResponse::ok([
            'token' => $token,
            'role' => $role,
            'userId' => (int) $record['id'],
        ]);
    }
}
