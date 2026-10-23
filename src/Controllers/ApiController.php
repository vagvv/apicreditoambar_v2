<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Config\Env;
use App\Helpers\JsonResponse;
use App\Helpers\Jwt;
use App\Helpers\Storage;
use App\Middleware\AuthMiddleware;
use PDO;

final class ApiController
{
    private Storage $storage;
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
        $this->storage = new Storage();
        $this->storage->ensureDefaults();
    }

    public function handle(string $method, string $path): void
    {
        $path = $this->normalizePath($path);

        if ($method === 'GET' && $path === '/') {
            JsonResponse::ok(['status' => 'ok', 'service' => 'ApiCreditoAmbar PHP']);
        }

        if ($method === 'POST' && $path === '/auth/login') {
            $this->login();
            return;
        }

        $auth = $this->requireAuthForRoutes($path, $method);
        if ($auth === false) {
            JsonResponse::unauthorized('Unauthorized');
        }

        $segments = array_values(array_filter(explode('/', $path), static fn(string $s) => $s !== ''));

        if ($method === 'GET' && $path === '/clientes') {
            $this->listClientes();
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/(\d+)$#', $path, $m)) {
            $this->getCliente((int) $m[1]);
            return;
        }

        if ($method === 'POST' && $path === '/clientes/add') {
            $this->createCliente();
            return;
        }

        if ($method === 'POST' && preg_match('#^/clientes/(\d+)/update$#', $path, $m)) {
            $this->updateCliente((int) $m[1]);
            return;
        }

        if ($method === 'PUT' && preg_match('#^/clientes/(\d+)$#', $path, $m)) {
            $this->updateCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/get-by/nombre/(.+)$#', $path, $m)) {
            $this->searchClientesByNombre(rawurldecode($m[1]));
            return;
        }

        if ($method === 'GET' && preg_match('#^/solicitudcredito/view$#', $path)) {
            $this->getSolicitudCreditoView();
            return;
        }

        if ($method === 'POST' && $path === '/solicitudescredito/add') {
            $this->createSolicitudCredito();
            return;
        }

        if ($method === 'POST' && $path === '/solicitudescredito/authorize') {
            $this->authorizeSolicitudCredito();
            return;
        }

        if ($method === 'POST' && preg_match('#^/solicitudescredito/(\d+)/reject$#', $path, $m)) {
            $this->rejectSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/solicitudescredito/(\d+)$#', $path, $m)) {
            $this->getSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/solicitudescredito/por-cliente/(\d+)$#', $path, $m)) {
            $this->getSolicitudesCreditoPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/pendientes-por-fecha/(.+)$#', $path, $m)) {
            $this->getPagosPendientesPorFecha(rawurldecode($m[1]));
            return;
        }

        if ($method === 'POST' && $path === '/pagos/add') {
            $this->addPago();
            return;
        }

        if ($method === 'POST' && preg_match('#^/pagos/(\d+)/done$#', $path, $m)) {
            $this->markPagoDone((int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/pagos/(\d+)/apply-extra-week$#', $path, $m)) {
            $this->applyExtraWeek((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/proximo/(\d+)$#', $path, $m)) {
            $this->getProximoPago((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/detalle-pago-pendiente/(\d+)$#', $path, $m)) {
            $this->getDetallePagoPendiente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/usuarios/search$#', $path)) {
            $this->searchUsuarios();
            return;
        }

        if ($method === 'GET' && $path === '/usuarios') {
            $this->listUsuarios();
            return;
        }

        if ($method === 'POST' && $path === '/usuarios/add') {
            $this->createUsuario();
            return;
        }

        if ($method === 'POST' && preg_match('#^/usuarios/(\d+)/change-password$#', $path, $m)) {
            $this->changePassword((int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/usuarios/(\d+)/delete$#', $path, $m)) {
            $this->deleteUser((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/usuarios/(\d+)$#', $path, $m)) {
            $this->getUsuario((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/viviendas') {
            $this->listViviendas();
            return;
        }

        if ($method === 'POST' && $path === '/viviendas/add') {
            $this->createVivienda();
            return;
        }

        if ($method === 'PUT' && preg_match('#^/viviendas/(\d+)$#', $path, $m)) {
            $this->updateVivienda((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/viviendas/por-cliente/(\d+)$#', $path, $m)) {
            $this->getViviendasPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/conyugues') {
            $this->listConyugues();
            return;
        }

        if ($method === 'POST' && $path === '/conyugues/add') {
            $this->createConyuge();
            return;
        }

        if ($method === 'GET' && preg_match('#^/conyugues/por-cliente/(\d+)$#', $path, $m)) {
            $this->getConyuguesPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/referencias') {
            $this->listReferencias();
            return;
        }

        if ($method === 'POST' && $path === '/referencias/add') {
            $this->createReferencia();
            return;
        }

        if ($method === 'GET' && preg_match('#^/referencias/por-cliente/(\d+)$#', $path, $m)) {
            $this->getReferenciasPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/garantias') {
            $this->listGarantias();
            return;
        }

        if ($method === 'POST' && $path === '/garantias/add') {
            $this->createGarantia();
            return;
        }

        if ($method === 'GET' && preg_match('#^/garantias/por-solicitud-credito/(\d+)$#', $path, $m)) {
            $this->getGarantiasPorSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/aval/(\d+)$#', $path, $m)) {
            $this->getClienteAval((int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/clientes/upload-(comprobantedomicilio|identificacion|foto)$#', $path, $m)) {
            $this->uploadClienteArchivo($m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/(comprobantedomicilio|identificacion|imagen)/(.+)$#', $path, $m)) {
            $this->getClienteArchivo($m[1], rawurldecode($m[2]));
            return;
        }

        if ($method === 'POST' && preg_match('#^/clientes/(\d+)/picture$#', $path, $m)) {
            $this->uploadClienteArchivo('foto', (int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/(conyugues|viviendas|referencias|garantias)/(\d+)/update$#', $path, $m)) {
            $resource = $m[1];
            $id = (int) $m[2];
            $this->updateGenericResource($resource, $id);
            return;
        }

        JsonResponse::notFound('Route not found');
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        if (str_contains($path, '/api')) {
            $path = preg_replace('#^/api#', '', $path) ?? '/';
        }
        return rtrim($path, '/') ?: '/';
    }

    private function requireAuthForRoutes(string $path, string $method): bool
    {
        $anonymous = [
            '/auth/login',
            '/',
        ];

        if (in_array($path, $anonymous, true)) {
            return true;
        }

        if ($method === 'OPTIONS') {
            return true;
        }

        if (AuthMiddleware::requireRoles([]) !== null) {
            return true;
        }

        return false;
    }

    private function readJson(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function login(): void
    {
        $body = $this->readJson();
        $user = (string) ($body['user'] ?? $_POST['user'] ?? '');
        $password = (string) ($body['password'] ?? $_POST['password'] ?? '');
        $defaultUser = (string) Env::get('DEFAULT_USER', 'admin');
        $defaultPassword = (string) Env::get('DEFAULT_PASSWORD', 'admin123');

        if ($user === '' || $password === '') {
            JsonResponse::error('Usuario y contraseña requeridos', 400);
        }

        $record = null;
        if ($this->pdo instanceof PDO) {
            $stmt = $this->pdo->prepare('SELECT * FROM usuario WHERE usuario = ? AND password = ? AND status = 1 LIMIT 1');
            $stmt->execute([$user, $password]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($dbUser)) {
                $record = [
                    'id' => (int) ($dbUser['id'] ?? 1),
                    'user' => (string) ($dbUser['usuario'] ?? $user),
                    'role' => (((int) ($dbUser['isadmin'] ?? 0)) === 1 ? 'admin' : 'empleado'),
                    'nombre' => (string) ($dbUser['nombre'] ?? ''),
                ];
            }
        }

        if ($record === null) {
            foreach ($this->storage->users() as $entry) {
                if ((string) ($entry['user'] ?? '') === $user && (string) ($entry['password'] ?? '') === $password) {
                    $record = $entry;
                    break;
                }
            }
        }

        if ($record === null && $user === $defaultUser && $password === $defaultPassword) {
            $record = ['id' => 1, 'user' => $defaultUser, 'role' => 'admin'];
        }

        if ($record === null) {
            JsonResponse::unauthorized('Credenciales inválidas');
        }

        $role = (string) ($record['role'] ?? 'empleado');
        $token = Jwt::sign([
            'sub' => (int) ($record['id'] ?? 1),
            'role' => $role,
            'user' => $user,
            'exp' => time() + (int) Env::get('JWT_TTL', 3600),
        ]);

        JsonResponse::ok([
            'token' => $token,
            'role' => $role,
            'userId' => (int) ($record['id'] ?? 1),
        ]);
    }

    private function listClientes(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));

        if ($this->pdo instanceof PDO) {
            $total = (int) $this->pdo->query('SELECT COUNT(*) FROM cliente')->fetchColumn();
            $stmt = $this->pdo->prepare('SELECT * FROM cliente ORDER BY id DESC LIMIT :offset, :limit');
            $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = array_map(fn(array $row): array => [
                'id' => (int) ($row['id'] ?? 0),
                'nombre' => $row['nombre'] ?? null,
                'domicilio' => $row['domicilio'] ?? null,
                'nacionalidad' => $row['nacionalidad'] ?? null,
                'lugarNacimiento' => $row['lugarnacimiento'] ?? null,
                'fechaNacimiento' => $row['fechanacimiento'] ?? null,
                'sexo' => $row['sexo'] ?? null,
                'curp' => $row['curp'] ?? null,
                'estadoCivil' => $row['estadocivil'] ?? null,
                'email' => $row['email'] ?? null,
                'telefono' => $row['telefono'] ?? null,
                'celular' => $row['celular'] ?? null,
                'numeroDependientes' => $row['numerodependientes'] ?? null,
                'nombreEmpresa' => $row['nombreempresa'] ?? null,
                'puesto' => $row['puesto'] ?? null,
                'sueldo' => $row['sueldo'] ?? null,
                'ingresoMensual' => $row['ingresomensual'] ?? null,
                'antiguedad' => $row['antiguedad'] ?? null,
                'domicilioEmpresa' => $row['domicilioempresa'] ?? null,
                'medioCaptacion' => $row['mediocaptacion'] ?? null,
                'esAval' => (bool) ($row['esaval'] ?? false),
                'idClienteAvalado' => $row['idclienteavalado'] ?? null,
                'status' => (bool) ($row['status'] ?? true),
            ], $records);
            JsonResponse::ok([
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => $total === 0 ? 0 : (int) ceil($total / $pageSize),
            ]);
        }

        $records = $this->storage->clientes();
        $offset = ($page - 1) * $pageSize;
        $items = array_slice($records, $offset, $pageSize);
        JsonResponse::ok([
            'items' => $items,
            'total' => count($records),
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => (int) ceil(count($records) / $pageSize),
        ]);
    }

    private function getCliente(int $id): void
    {
        $items = $this->storage->clientes();
        foreach ($items as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                JsonResponse::ok($row);
            }
        }
        JsonResponse::notFound('Cliente no encontrado');
    }

    private function createCliente(): void
    {
        $data = $this->readJson();
        $items = $this->storage->clientes();
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $items[] = $data;
        $this->storage->write('clientes', $items);
        JsonResponse::ok($id, 201);
    }

    private function updateCliente(int $id): void
    {
        $data = $this->readJson();
        $items = $this->storage->clientes();
        $updated = false;
        foreach ($items as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row = array_merge($row, $data);
                $updated = true;
            }
        }
        unset($row);
        if (!$updated) {
            JsonResponse::notFound('Cliente no encontrado');
        }
        $this->storage->write('clientes', $items);
        JsonResponse::ok(['updated' => true, 'id' => $id]);
    }

    private function searchClientesByNombre(string $nombre): void
    {
        $filtered = [];
        foreach ($this->storage->clientes() as $row) {
            if (stripos((string) ($row['nombre'] ?? ''), $nombre) !== false) {
                $filtered[] = $row;
            }
        }
        JsonResponse::ok($filtered);
    }

    private function getSolicitudCreditoView(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));

        if ($this->pdo instanceof PDO) {
            $total = (int) $this->pdo->query('SELECT COUNT(*) FROM vw_solicitudcredito')->fetchColumn();
            $stmt = $this->pdo->prepare('SELECT * FROM vw_solicitudcredito ORDER BY id DESC LIMIT :offset, :limit');
            $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = array_map(function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'nombreCliente' => $row['nombre'] ?? null,
                    'montoSolicitado' => $row['cantidadsolicitada'] ?? 0,
                    'cantidadAprobada' => $row['cantidadaprobada'] ?? 0,
                    'tipoAutorizacion' => $row['tipoautorizacion'] ?? 'Pendiente',
                    'fechaSolicitud' => $row['fechasolicitud'] ?? null,
                    'status' => $row['tipoautorizacion'] ?? 'Pendiente',
                ];
            }, $records);
            JsonResponse::ok($items);
        }

        $records = $this->storage->solicitudes();
        $offset = ($page - 1) * $pageSize;
        JsonResponse::ok(array_slice($records, $offset, $pageSize));
    }

    private function createSolicitudCredito(): void
    {
        $data = $this->readJson();
        $items = $this->storage->solicitudes();
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $data['tipoAutorizacion'] = 'Pendiente';
        $data['status'] = 'Pendiente';
        $items[] = $data;
        $this->storage->write('solicitudes', $items);
        JsonResponse::ok($id, 201);
    }

    private function authorizeSolicitudCredito(): void
    {
        $data = $this->readJson();
        $id = (int) ($data['IdSolicitudCredito'] ?? 0);
        $items = $this->storage->solicitudes();
        foreach ($items as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row['tipoAutorizacion'] = 'Aprobada';
                $row['status'] = 'Aprobada';
                $row['cantidadAprobada'] = (float) ($data['CantidadAprobada'] ?? $row['montoSolicitado'] ?? 0);
                $row['numeroPagos'] = (int) ($data['NumeroPagos'] ?? 0);
                $row['pagoSemanal'] = (float) ($data['PagoSemanal'] ?? 0);
                $row['fechaPrimerPago'] = $data['fechaPrimerPago'] ?? null;
                break;
            }
        }
        unset($row);
        $this->storage->write('solicitudes', $items);
        JsonResponse::ok(['approved' => $id]);
    }

    private function rejectSolicitudCredito(int $id): void
    {
        $items = $this->storage->solicitudes();
        foreach ($items as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row['tipoAutorizacion'] = 'Rechazada';
                $row['status'] = 'Rechazada';
                $this->storage->write('solicitudes', $items);
                JsonResponse::ok(['r' => $id]);
            }
        }
        unset($row);
        JsonResponse::notFound('Solicitud no encontrada');
    }

    private function getSolicitudCredito(int $id): void
    {
        foreach ($this->storage->solicitudes() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                JsonResponse::ok($row);
            }
        }
        JsonResponse::notFound('Solicitud no encontrada');
    }

    private function getSolicitudesCreditoPorCliente(int $idCliente): void
    {
        $items = [];
        foreach ($this->storage->solicitudes() as $row) {
            if ((int) ($row['idCliente'] ?? 0) === $idCliente) {
                $items[] = $row;
            }
        }
        JsonResponse::ok($items);
    }

    private function getPagosPendientesPorFecha(string $fecha): void
    {
        if ($this->pdo instanceof PDO) {
            $stmt = $this->pdo->prepare('SELECT * FROM pago WHERE fechapago = ? ORDER BY id DESC');
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = array_map(function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'idSolicitudCredito' => (int) ($row['idsolicitudcredito'] ?? 0),
                    'nombreCliente' => null,
                    'cantidad' => (float) ($row['monto'] ?? 0),
                    'fechaPago' => $row['fechapago'] ?? null,
                    'status' => ((int) ($row['pagado'] ?? 0) === 1 ? 'Pagado' : 'Pendiente'),
                ];
            }, $rows);
            JsonResponse::ok($items);
        }

        $items = [];
        foreach ($this->storage->pagos() as $row) {
            if ((string) ($row['fechaPago'] ?? '') === $fecha || str_starts_with((string) ($row['fechaPago'] ?? ''), $fecha)) {
                $items[] = $row;
            }
        }
        JsonResponse::ok($items);
    }

    private function addPago(): void
    {
        $data = $this->readJson();
        $items = $this->storage->pagos();
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $data['status'] = 'Pendiente';
        $items[] = $data;
        $this->storage->write('pagos', $items);
        JsonResponse::ok($id, 201);
    }

    private function markPagoDone(int $id): void
    {
        $items = $this->storage->pagos();
        foreach ($items as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row['status'] = 'Pagado';
                $this->storage->write('pagos', $items);
                JsonResponse::ok($row);
            }
        }
        unset($row);
        JsonResponse::notFound('Pago no encontrado');
    }

    private function applyExtraWeek(int $id): void
    {
        $items = $this->storage->pagos();
        foreach ($items as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row['status'] = 'Semana extra';
                $row['cantidad'] = (float) ($row['cantidad'] ?? 0) + 100;
                $this->storage->write('pagos', $items);
                JsonResponse::ok($row);
            }
        }
        unset($row);
        JsonResponse::notFound('Pago no encontrado');
    }

    private function getProximoPago(int $idSolicitudCredito): void
    {
        $items = $this->storage->pagos();
        foreach ($items as $row) {
            if ((int) ($row['idSolicitudCredito'] ?? 0) === $idSolicitudCredito) {
                JsonResponse::ok([
                    'numero' => (int) ($row['id'] ?? 1),
                    'monto' => (float) ($row['cantidad'] ?? 0),
                    'saldoPendiente' => (float) ($row['cantidad'] ?? 0),
                    'fechaProximoPago' => (string) ($row['fechaPago'] ?? date('Y-m-d')),
                ]);
            }
        }
        JsonResponse::notFound('Pago próximo no encontrado');
    }

    private function getDetallePagoPendiente(int $id): void
    {
        foreach ($this->storage->pagos() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                JsonResponse::ok($row);
            }
        }
        JsonResponse::notFound('Detalle no encontrado');
    }

    private function listUsuarios(): void
    {
        JsonResponse::ok($this->storage->users());
    }

    private function createUsuario(): void
    {
        $data = $this->readJson();
        $items = $this->storage->users();
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $items[] = $data;
        $this->storage->saveResource('users', $items);
        JsonResponse::ok($id, 201);
    }

    private function searchUsuarios(): void
    {
        $query = (string) ($_GET['q'] ?? '');
        if ($this->pdo instanceof PDO) {
            if ($query === '') {
                $stmt = $this->pdo->query('SELECT * FROM usuario WHERE status = 1 ORDER BY id DESC LIMIT 25');
            } else {
                $stmt = $this->pdo->prepare('SELECT * FROM usuario WHERE status = 1 AND (usuario LIKE ? OR nombre LIKE ?) ORDER BY id DESC LIMIT 25');
                $like = '%' . $query . '%';
                $stmt->execute([$like, $like]);
            }
            if ($query !== '') {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                JsonResponse::ok($results);
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            JsonResponse::ok($results);
        }

        $results = [];
        foreach ($this->storage->users() as $user) {
            if ($query === '' || stripos((string) ($user['user'] ?? ''), $query) !== false || stripos((string) ($user['nombre'] ?? ''), $query) !== false) {
                $results[] = $user;
            }
        }
        JsonResponse::ok($results);
    }

    private function changePassword(int $id): void
    {
        $data = $this->readJson();
        $items = $this->storage->users();
        foreach ($items as &$user) {
            if ((int) ($user['id'] ?? 0) === $id) {
                $user['password'] = (string) ($data['password'] ?? '');
                $this->storage->write('users', $items);
                JsonResponse::ok(['id' => $id]);
            }
        }
        unset($user);
        JsonResponse::notFound('Usuario no encontrado');
    }

    private function deleteUser(int $id): void
    {
        $items = $this->storage->users();
        $filtered = [];
        foreach ($items as $user) {
            if ((int) ($user['id'] ?? 0) !== $id) {
                $filtered[] = $user;
            }
        }
        if (count($filtered) === count($items)) {
            JsonResponse::notFound('Usuario no encontrado');
        }
        $this->storage->write('users', $filtered);
        JsonResponse::ok(['r' => $id]);
    }

    private function getUsuario(int $id): void
    {
        foreach ($this->storage->users() as $user) {
            if ((int) ($user['id'] ?? 0) === $id) {
                JsonResponse::ok($user);
            }
        }
        JsonResponse::notFound('Usuario no encontrado');
    }

    private function listViviendas(): void
    {
        JsonResponse::ok($this->storage->resource('viviendas'));
    }

    private function createVivienda(): void
    {
        $data = $this->readJson();
        $items = $this->storage->resource('viviendas');
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? $row['idVivienda'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $items[] = $data;
        $this->storage->saveResource('viviendas', $items);
        JsonResponse::ok($id, 201);
    }

    private function updateVivienda(int $id): void
    {
        $this->updateGenericResource('viviendas', $id);
    }

    private function getViviendasPorCliente(int $idCliente): void
    {
        $items = [];
        foreach ($this->storage->resource('viviendas') as $row) {
            if ((int) ($row['idCliente'] ?? 0) === $idCliente) {
                $items[] = $row;
            }
        }
        if ($items === []) {
            $items = [['idCliente' => $idCliente, 'domicilio' => 'Calle Principal 123', 'latitud' => 20.67, 'longitud' => -103.34]];
        }
        JsonResponse::ok($items);
    }

    private function listConyugues(): void
    {
        JsonResponse::ok($this->storage->resource('conyugues'));
    }

    private function createConyuge(): void
    {
        $data = $this->readJson();
        $items = $this->storage->resource('conyugues');
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $items[] = $data;
        $this->storage->saveResource('conyugues', $items);
        JsonResponse::ok($id, 201);
    }

    private function getConyuguesPorCliente(int $idCliente): void
    {
        $items = [];
        foreach ($this->storage->resource('conyugues') as $row) {
            if ((int) ($row['idCliente'] ?? 0) === $idCliente) {
                $items[] = $row;
            }
        }
        if ($items === []) {
            $items = [['idCliente' => $idCliente, 'nombre' => 'Cónyuge de prueba', 'celular' => '0000000000']];
        }
        JsonResponse::ok($items);
    }

    private function listReferencias(): void
    {
        JsonResponse::ok($this->storage->resource('referencias'));
    }

    private function createReferencia(): void
    {
        $data = $this->readJson();
        $items = $this->storage->resource('referencias');
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $items[] = $data;
        $this->storage->saveResource('referencias', $items);
        JsonResponse::ok($id, 201);
    }

    private function getReferenciasPorCliente(int $idCliente): void
    {
        $items = [];
        foreach ($this->storage->resource('referencias') as $row) {
            if ((int) ($row['idCliente'] ?? 0) === $idCliente) {
                $items[] = $row;
            }
        }
        if ($items === []) {
            $items = [['idCliente' => $idCliente, 'nombre' => 'Referencia de prueba', 'domicilio' => 'Calle Referencia 42']];
        }
        JsonResponse::ok($items);
    }

    private function listGarantias(): void
    {
        JsonResponse::ok($this->storage->resource('garantias'));
    }

    private function createGarantia(): void
    {
        $data = $this->readJson();
        $items = $this->storage->resource('garantias');
        $id = $items === [] ? 1 : max(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $items)) + 1;
        $data['id'] = $id;
        $items[] = $data;
        $this->storage->saveResource('garantias', $items);
        JsonResponse::ok($id, 201);
    }

    private function getGarantiasPorSolicitudCredito(int $idSolicitudCredito): void
    {
        $items = [];
        foreach ($this->storage->resource('garantias') as $row) {
            if ((int) ($row['idSolicitudCredito'] ?? 0) === $idSolicitudCredito) {
                $items[] = $row;
            }
        }
        if ($items === []) {
            $items = [['idSolicitudCredito' => $idSolicitudCredito, 'descripcion' => 'Garantía de prueba']];
        }
        JsonResponse::ok($items);
    }

    private function getClienteAval(int $id): void
    {
        $cliente = $this->storage->clientes();
        foreach ($cliente as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                JsonResponse::ok($row);
            }
        }
        JsonResponse::notFound('Aval no encontrado');
    }

    private function uploadClienteArchivo(string $kind, ?int $id = null): void
    {
        $targetDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            JsonResponse::error('No se recibió archivo', 400);
        }

        $originalName = basename((string) $_FILES['file']['name']);
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $kind . '_' . ($id ?? 'cliente') . '_' . bin2hex(random_bytes(4)) . '_' . $originalName;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            JsonResponse::error('No se pudo guardar el archivo', 500);
        }

        JsonResponse::ok(['stored' => basename($filePath), 'kind' => $kind, 'id' => $id], 201);
    }

    private function updateGenericResource(string $resource, int $id): void
    {
        $data = $this->readJson();
        $items = $this->storage->resource($resource);
        $updated = false;
        foreach ($items as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row = array_merge($row, $data);
                $updated = true;
            }
        }
        unset($row);
        if (!$updated) {
            JsonResponse::notFound(ucfirst($resource) . ' no encontrado');
        }
        $this->storage->saveResource($resource, $items);
        JsonResponse::ok(['updated' => true, 'id' => $id]);
    }

    private function getClienteArchivo(string $kind, string $filename): void
    {
        $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($file)) {
            JsonResponse::notFound('Archivo no encontrado');
        }
        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
}
