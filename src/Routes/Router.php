<?php

declare(strict_types=1);

namespace App\Routes;

use App\Config\Env;
use App\Features\Auth\AuthController;
use App\Features\Clientes\ClienteController;
use App\Features\Pagos\PagoController;
use App\Features\SolicitudesCredito\SolicitudCreditoController;
use App\Features\Usuarios\UsuarioController;
use App\Helpers\JsonResponse;
use App\Middleware\AuthMiddleware;

final class Router
{
    private AuthController $authController;
    private ClienteController $clienteController;
    private SolicitudCreditoController $solicitudCreditoController;
    private PagoController $pagoController;
    private UsuarioController $usuarioController;

    public function __construct()
    {
        Env::load(dirname(__DIR__, 2));
        $this->authController = new AuthController();
        $this->clienteController = new ClienteController();
        $this->solicitudCreditoController = new SolicitudCreditoController();
        $this->pagoController = new PagoController();
        $this->usuarioController = new UsuarioController();
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->normalizePath($_SERVER['REQUEST_URI'] ?? '/');

        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Auth-Role, X-Auth-Token');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            exit(0);
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Auth-Role, X-Auth-Token');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

        if ($method === 'GET' && $path === '/') {
            JsonResponse::ok(['status' => 'ok', 'service' => 'ApiCreditoAmbar PHP']);
        }

        if ($method === 'POST' && $path === '/auth/login') {
            $this->authController->login();
            return;
        }

        if (!$this->isAllowedWithoutAuthentication($path, $method)) {
            if (AuthMiddleware::requireRoles([]) === null) {
                JsonResponse::unauthorized('Unauthorized');
            }
        }

        if ($method === 'GET' && $path === '/clientes') {
            $this->clienteController->listClientes();
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/(\d+)$#', $path, $m)) {
            $this->clienteController->getCliente((int) $m[1]);
            return;
        }

        if ($method === 'POST' && $path === '/clientes/add') {
            $this->clienteController->createCliente();
            return;
        }

        if ($method === 'POST' && preg_match('#^/clientes/(\d+)/update$#', $path, $m)) {
            $this->clienteController->updateCliente((int) $m[1]);
            return;
        }

        if (($method === 'POST' || $method === 'PUT') && preg_match('#^/clientes/(\d+)$#', $path, $m)) {
            $this->clienteController->updateCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/get-by/nombre/(.+)$#', $path, $m)) {
            $this->clienteController->searchClientesByNombre(rawurldecode($m[1]));
            return;
        }

        if ($method === 'GET' && preg_match('#^/solicitudcredito/view$#', $path)) {
            $this->solicitudCreditoController->getSolicitudCreditoView();
            return;
        }

        if ($method === 'GET' && $path === '/solicitudescredito') {
            $this->solicitudCreditoController->listSolicitudesCredito();
            return;
        }

        if ($method === 'POST' && $path === '/solicitudescredito/add') {
            $this->solicitudCreditoController->createSolicitudCredito();
            return;
        }

        if ($method === 'POST' && $path === '/solicitudescredito/authorize') {
            $this->solicitudCreditoController->authorizeSolicitudCredito();
            return;
        }

        if ($method === 'POST' && preg_match('#^/solicitudescredito/(\d+)/reject$#', $path, $m)) {
            $this->solicitudCreditoController->rejectSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/solicitudescredito/(\d+)$#', $path, $m)) {
            $this->solicitudCreditoController->getSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/solicitudescredito/por-cliente/(\d+)$#', $path, $m)) {
            $this->solicitudCreditoController->getSolicitudesCreditoPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/prestamos/activos$#', $path)) {
            $this->solicitudCreditoController->getPrestamosActivos();
            return;
        }

        if ($method === 'GET' && preg_match('#^/prestamos/por-solicitud-credito/(\d+)$#', $path, $m)) {
            $this->solicitudCreditoController->getPrestamoPorSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/pagos') {
            $this->pagoController->listPagos();
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/pendientes-por-fecha/(.+)$#', $path, $m)) {
            $this->pagoController->getPagosPendientesPorFecha(rawurldecode($m[1]));
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/por-solicitud-credito/(\d+)$#', $path, $m)) {
            $this->pagoController->getPagosPorSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'POST' && $path === '/pagos/add') {
            $this->pagoController->addPago();
            return;
        }

        if ($method === 'POST' && preg_match('#^/pagos/(\d+)/done$#', $path, $m)) {
            $this->pagoController->markPagoDone((int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/pagos/(\d+)/apply-extra-week$#', $path, $m)) {
            $this->pagoController->applyExtraWeek((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/proximo/(\d+)$#', $path, $m)) {
            $this->pagoController->getProximoPago((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/pagos/detalle-pago-pendiente/(\d+)$#', $path, $m)) {
            $this->pagoController->getDetallePagoPendiente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/usuarios/search$#', $path)) {
            $this->usuarioController->searchUsuarios();
            return;
        }

        if ($method === 'GET' && $path === '/usuarios') {
            $this->usuarioController->listUsuarios();
            return;
        }

        if ($method === 'POST' && $path === '/usuarios/add') {
            $this->usuarioController->createUsuario();
            return;
        }

        if ($method === 'POST' && preg_match('#^/usuarios/(\d+)/change-password$#', $path, $m)) {
            $this->usuarioController->changePassword((int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/usuarios/(\d+)/delete$#', $path, $m)) {
            $this->usuarioController->deleteUser((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/usuarios/(\d+)$#', $path, $m)) {
            $this->usuarioController->getUsuario((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/viviendas') {
            $this->clienteController->listViviendas();
            return;
        }

        if ($method === 'POST' && $path === '/viviendas/add') {
            $this->clienteController->createVivienda();
            return;
        }

        if ($method === 'PUT' && preg_match('#^/viviendas/(\d+)$#', $path, $m)) {
            $this->clienteController->updateVivienda((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/viviendas/por-cliente/(\d+)$#', $path, $m)) {
            $this->clienteController->getViviendasPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'POST' && $path === '/viviendas/upload-imagen') {
            $this->clienteController->uploadViviendaArchivo(isset($_GET['id']) ? (int) $_GET['id'] : null);
            return;
        }

        if ($method === 'POST' && $path === '/cargar/videoresponsabilidad') {
            $this->clienteController->uploadVideoResponsabilidad((int) ($_GET['id'] ?? 0));
            return;
        }

        if ($method === 'POST' && preg_match('#^/cargar/garantias/(\d+)$#', $path, $m)) {
            $this->clienteController->uploadGarantia((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/ver/garantias/(\d+)/([^/]+)$#', $path, $m)) {
            $this->clienteController->getGarantiaImage((int) $m[1], rawurldecode($m[2]));
            return;
        }

        if ($method === 'GET' && preg_match('#^/ver/garantias/(\d+)$#', $path, $m)) {
            $this->clienteController->listGarantiaImages((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/viviendas/imagen/(.+)$#', $path, $m)) {
            $this->clienteController->getViviendaArchivo(rawurldecode($m[1]));
            return;
        }

        if ($method === 'GET' && $path === '/conyugues') {
            $this->clienteController->listConyugues();
            return;
        }

        if ($method === 'POST' && $path === '/conyugues/add') {
            $this->clienteController->createConyuge();
            return;
        }

        if ($method === 'GET' && preg_match('#^/conyugues/por-cliente/(\d+)$#', $path, $m)) {
            $this->clienteController->getConyuguesPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/referencias') {
            $this->clienteController->listReferencias();
            return;
        }

        if ($method === 'POST' && $path === '/referencias/add') {
            $this->clienteController->createReferencia();
            return;
        }

        if ($method === 'GET' && preg_match('#^/referencias/por-cliente/(\d+)$#', $path, $m)) {
            $this->clienteController->getReferenciasPorCliente((int) $m[1]);
            return;
        }

        if ($method === 'GET' && $path === '/garantias') {
            $this->clienteController->listGarantias();
            return;
        }

        if ($method === 'POST' && $path === '/garantias/add') {
            $this->clienteController->createGarantia();
            return;
        }

        if ($method === 'GET' && preg_match('#^/garantias/por-solicitud-credito/(\d+)$#', $path, $m)) {
            $this->clienteController->getGarantiasPorSolicitudCredito((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/aval/(\d+)$#', $path, $m)) {
            $this->clienteController->getClienteAval((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/(\d+)/foto$#', $path, $m)) {
            $this->clienteController->getClienteFoto((int) $m[1]);
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/(\d+)/documentos$#', $path, $m)) {
            $this->clienteController->listClienteDocumentos((int) $m[1]);
            return;
        }

        if ($method === 'POST' && preg_match('#^/clientes/upload-(comprobantedomicilio|identificacion|foto)$#', $path, $m)) {
            $this->clienteController->uploadClienteArchivo(
                $m[1],
                isset($_GET['id']) ? (int) $_GET['id'] : null,
            );
            return;
        }

        if ($method === 'GET' && preg_match('#^/clientes/(comprobantedomicilio|identificacion|imagen)/(.+)$#', $path, $m)) {
            $this->clienteController->getClienteArchivo($m[1], rawurldecode($m[2]));
            return;
        }

        if ($method === 'POST' && preg_match('#^/clientes/(\d+)/picture$#', $path, $m)) {
            $this->clienteController->uploadClienteArchivo('foto', (int) $m[1]);
            return;
        }

        if (($method === 'POST' || $method === 'PUT') && preg_match('#^/(conyugues|viviendas|referencias|garantias)/(\d+)/update$#', $path, $m)) {
            $this->clienteController->updateGenericResource($m[1], (int) $m[2]);
            return;
        }

        if ($method === 'PUT' && preg_match('#^/(conyugues|referencias|garantias)/(\d+)$#', $path, $m)) {
            $this->clienteController->updateGenericResource($m[1], (int) $m[2]);
            return;
        }

        JsonResponse::notFound('Route not found');
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        if (preg_match('#(?:^|/)api(?=/|$)(.*)$#', $path, $matches)) {
            $path = $matches[1] !== '' ? $matches[1] : '/';
        }
        return rtrim($path, '/') ?: '/';
    }

    private function isAllowedWithoutAuthentication(string $path, string $method): bool
    {
        $anonymous = ['/auth/login', '/'];
        if (in_array($path, $anonymous, true)) {
            return true;
        }
        if ($method === 'OPTIONS') {
            return true;
        }
        return false;
    }
}
