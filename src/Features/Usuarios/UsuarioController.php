<?php

declare(strict_types=1);

namespace App\Features\Usuarios;

use App\Features\Shared\BaseController;
use App\Helpers\JsonResponse;
use PDO;

final class UsuarioController extends BaseController
{
    public function listUsuarios(): void
    {
        $statement = $this->pdo->query(
            'SELECT id, nombre, celular, domicilio, usuario, isadmin, status
             FROM usuario WHERE status = 1 ORDER BY id DESC',
        );
        JsonResponse::ok(array_map($this->formatUsuario(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function createUsuario(): void
    {
        $data = $this->readJson();
        $fields = $this->mapInput($data, [
            'nombre' => ['nombre'],
            'celular' => ['celular'],
            'domicilio' => ['domicilio'],
            'usuario' => ['user', 'usuario'],
            'password' => ['password'],
            'isadmin' => ['isadmin', 'isAdmin'],
            'status' => ['status'],
        ]);
        if (!array_key_exists('status', $fields)) {
            $fields['status'] = 1;
        }
        if (!array_key_exists('isadmin', $fields)) {
            $fields['isadmin'] = 0;
        }
        $fields['status'] = (int) (bool) $fields['status'];
        $fields['isadmin'] = (int) (bool) $fields['isadmin'];

        JsonResponse::ok($this->insertRecord('usuario', $fields), 201);
    }

    public function searchUsuarios(): void
    {
        $query = (string) ($_GET['q'] ?? '');
        if ($query === '') {
            $statement = $this->pdo->query(
                'SELECT id, nombre, celular, domicilio, usuario, isadmin, status
                 FROM usuario WHERE status = 1 ORDER BY id DESC LIMIT 25',
            );
        } else {
            $statement = $this->pdo->prepare(
                'SELECT id, nombre, celular, domicilio, usuario, isadmin, status
                 FROM usuario WHERE status = 1 AND (usuario LIKE ? OR nombre LIKE ?)
                 ORDER BY id DESC LIMIT 25',
            );
            $like = '%' . $query . '%';
            $statement->execute([$like, $like]);
        }
        JsonResponse::ok(array_map($this->formatUsuario(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function changePassword(int $id): void
    {
        $data = $this->readJson();
        $password = (string) ($data['password'] ?? '');
        if ($password === '') {
            JsonResponse::error('La contraseña es requerida', 400);
        }
        if (!$this->updateRecord('usuario', $id, ['password' => $password])) {
            JsonResponse::notFound('Usuario no encontrado');
        }
        JsonResponse::ok(['id' => $id]);
    }

    public function deleteUser(int $id): void
    {
        if (!$this->updateRecord('usuario', $id, ['status' => 0])) {
            JsonResponse::notFound('Usuario no encontrado');
        }
        JsonResponse::ok(['r' => $id]);
    }

    public function getUsuario(int $id): void
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nombre, celular, domicilio, usuario, isadmin, status
             FROM usuario WHERE id = ? AND status = 1 LIMIT 1',
        );
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Usuario no encontrado');
        }
        JsonResponse::ok($this->formatUsuario($row));
    }

    private function formatUsuario(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'celular' => $row['celular'],
            'domicilio' => $row['domicilio'],
            'user' => $row['usuario'],
            'role' => (int) $row['isadmin'] === 1 ? 'admin' : 'empleado',
            'status' => (bool) $row['status'],
        ];
    }
}
