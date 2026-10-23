<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Config/Env.php';
require_once __DIR__ . '/../src/Helpers/JsonResponse.php';
require_once __DIR__ . '/../src/Helpers/Jwt.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Features/Shared/BaseController.php';
require_once __DIR__ . '/../src/Features/Auth/AuthController.php';
require_once __DIR__ . '/../src/Features/Clientes/ClienteController.php';
require_once __DIR__ . '/../src/Features/SolicitudesCredito/SolicitudCreditoController.php';
require_once __DIR__ . '/../src/Features/Pagos/PagoController.php';
require_once __DIR__ . '/../src/Features/Usuarios/UsuarioController.php';
require_once __DIR__ . '/../src/Routes/Router.php';

use App\Config\Env;
use App\Routes\Router;

Env::load(__DIR__ . '/..');
$router = new Router();
$router->dispatch();
