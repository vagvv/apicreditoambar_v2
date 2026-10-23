<?php

declare(strict_types=1);

namespace App\Features\Clientes;

use App\Features\Shared\BaseController;
use App\Helpers\JsonResponse;
use PDO;

final class ClienteController extends BaseController
{
    private const CLIENTE_COLUMNS = [
        'nombre' => ['nombre', 'Nombre'],
        'domicilio' => ['domicilio', 'Domicilio'],
        'nacionalidad' => ['nacionalidad', 'Nacionalidad'],
        'lugarnacimiento' => ['lugarNacimiento', 'LugarNacimiento'],
        'fechanacimiento' => ['fechaNacimiento', 'FechaNacimiento'],
        'sexo' => ['sexo', 'Sexo'],
        'curp' => ['curp', 'Curp'],
        'estadocivil' => ['estadoCivil', 'estadocivil', 'EstadoCivil'],
        'email' => ['email', 'Email'],
        'telefono' => ['telefono', 'Telefono'],
        'celular' => ['celular', 'Celular'],
        'numerodependientes' => ['numeroDependientes', 'numerodependientes', 'NumeroDependientes'],
        'nombreempresa' => ['nombreEmpresa', 'nombreempresa', 'NombreEmpresa'],
        'puesto' => ['puesto', 'Puesto'],
        'sueldo' => ['sueldo', 'Sueldo'],
        'ingresomensual' => ['ingresoMensual', 'ingresomensual', 'IngresoMensual'],
        'antiguedad' => ['antiguedad', 'Antiguedad'],
        'domicilioempresa' => ['domicilioEmpresa', 'domicilioempresa', 'DomicilioEmpresa'],
        'mediocaptacion' => ['medioCaptacion', 'mediocaptacion', 'MedioCaptacion'],
        'esaval' => ['esAval', 'esaval', 'EsAval'],
        'idclienteavalado' => ['idClienteAvalado', 'idclienteavalado', 'IdClienteAvalado'],
        'status' => ['status', 'Status'],
    ];

    public function listClientes(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM cliente WHERE esaval = 0')->fetchColumn();
        $statement = $this->pdo->prepare('SELECT * FROM cliente WHERE esaval = 0 ORDER BY id DESC LIMIT :offset, :limit');
        $statement->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->execute();
        $items = array_map($this->formatCliente(...), $statement->fetchAll(PDO::FETCH_ASSOC));

        JsonResponse::ok([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $total === 0 ? 0 : (int) ceil($total / $pageSize),
        ]);
    }

    public function getCliente(int $id): void
    {
        $this->findCliente($id, 'Cliente no encontrado');
    }

    public function createCliente(): void
    {
        $fields = $this->normalizeClienteFields($this->mapInput($this->readJson(), self::CLIENTE_COLUMNS));
        JsonResponse::ok($this->insertRecord('cliente', $fields), 201);
    }

    public function updateCliente(int $id): void
    {
        $fields = $this->normalizeClienteFields($this->mapInput($this->readJson(), self::CLIENTE_COLUMNS));
        if (!$this->updateRecord('cliente', $id, $fields)) {
            JsonResponse::notFound('Cliente no encontrado');
        }
        JsonResponse::ok(['updated' => true, 'id' => $id]);
    }

    public function searchClientesByNombre(string $nombre): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM cliente WHERE esaval = 0 AND nombre LIKE ? ORDER BY id DESC');
        $statement->execute(['%' . $nombre . '%']);
        JsonResponse::ok(array_map($this->formatCliente(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function getClienteAval(int $id): void
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM cliente WHERE esaval = 1 AND idclienteavalado = ? ORDER BY id DESC LIMIT 1',
        );
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Aval no encontrado');
        }
        JsonResponse::ok($this->formatCliente($row));
    }

    public function getClienteFoto(int $id): void
    {
        if ($id <= 0) {
            JsonResponse::error('El id del cliente no es válido', 400);
        }

        $directory = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'clientes';
        if (!is_dir($directory)) {
            JsonResponse::notFound('Imagen del cliente no encontrada');
        }

        $filenames = scandir($directory);
        if ($filenames === false) {
            JsonResponse::error('No se pudo consultar el directorio de imágenes', 500);
        }

        $pattern = '/^foto_' . $id . '_[a-f0-9]{8,32}(?:_[^\/]+)?\.(?:jpg|jpeg|png)$/i';
        foreach ($filenames as $filename) {
            if (preg_match($pattern, $filename)) {
                $this->sendUploadedFile('clientes', $filename);
            }
        }

        JsonResponse::notFound('Imagen del cliente no encontrada');
    }

    public function listClienteDocumentos(int $id): void
    {
        if ($id <= 0) {
            JsonResponse::error('El id del cliente no es válido', 400);
        }

        $documents = [];
        $groups = [
            ['folder' => 'identificacion', 'kind' => 'identificacion', 'label' => 'Identificación oficial'],
            ['folder' => 'clientes', 'kind' => 'comprobantedomicilio', 'label' => 'Comprobante de domicilio'],
            ['folder' => 'clientes', 'kind' => 'foto', 'label' => 'Fotografía del cliente'],
            ['folder' => 'vivienda', 'kind' => 'imagen', 'label' => 'Fotografía de vivienda'],
        ];

        foreach ($groups as $group) {
            $directory = dirname(__DIR__, 3)
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'uploads'
                . DIRECTORY_SEPARATOR . $group['folder'];
            if (!is_dir($directory)) {
                continue;
            }

            $filenames = scandir($directory);
            if ($filenames === false) {
                JsonResponse::error('No se pudo consultar el directorio de documentos', 500);
            }

            $pattern = '/^' . preg_quote($group['kind'] . '_' . $id . '_', '/') .
                '[a-f0-9]{8,32}(?:_[^\/]+)?\.(?:jpg|jpeg|png|pdf)$/i';
            foreach ($filenames as $filename) {
                if (!preg_match($pattern, $filename)) {
                    continue;
                }

                $path = $directory . DIRECTORY_SEPARATOR . $filename;
                if (!is_file($path)) {
                    continue;
                }

                $mimeType = mime_content_type($path);
                $modifiedAt = filemtime($path);
                $size = filesize($path);
                if ($mimeType === false || $modifiedAt === false || $size === false) {
                    JsonResponse::error('No se pudieron consultar los metadatos de un documento', 500);
                }

                $downloadPath = match ($group['kind']) {
                    'identificacion' => '/clientes/identificacion/',
                    'comprobantedomicilio' => '/clientes/comprobantedomicilio/',
                    'foto' => '/clientes/imagen/',
                    default => '/viviendas/imagen/',
                };
                $documents[] = [
                    'kind' => $group['kind'],
                    'label' => $group['label'],
                    'filename' => $filename,
                    'mimeType' => $mimeType,
                    'size' => $size,
                    'uploadedAt' => date(DATE_ATOM, $modifiedAt),
                    'downloadPath' => $downloadPath . rawurlencode($filename),
                ];
            }
        }

        usort(
            $documents,
            static fn(array $left, array $right): int => strcmp($right['uploadedAt'], $left['uploadedAt']),
        );
        JsonResponse::ok(['items' => $documents, 'total' => count($documents)]);
    }

    public function uploadClienteArchivo(string $kind, ?int $id = null): void
    {
        $config = [
            'foto' => ['folder' => 'clientes', 'mimes' => ['image/jpeg', 'image/png']],
            'comprobantedomicilio' => [
                'folder' => 'clientes',
                'mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
            ],
            'identificacion' => [
                'folder' => 'identificacion',
                'mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
            ],
        ];
        if (!isset($config[$kind])) {
            JsonResponse::error('Tipo de documento no válido', 400);
        }

        $this->storeUploadedFile($config[$kind]['folder'], $kind, $id, $config[$kind]['mimes']);
    }

    public function uploadViviendaArchivo(?int $id = null): void
    {
        $this->storeUploadedFile('vivienda', 'imagen', $id, ['image/jpeg', 'image/png']);
    }

    public function uploadGarantia(int $idCliente): void
    {
        if ($idCliente <= 0) {
            JsonResponse::error('El id del cliente o aval no es válido', 400);
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM cliente WHERE id = ? LIMIT 1');
        $exists->execute([$idCliente]);
        if ($exists->fetchColumn() === false) {
            JsonResponse::notFound('Cliente o aval no encontrado');
        }

        if (!isset($_FILES['file'])) {
            JsonResponse::error('No se recibió archivo', 400);
        }
        if ($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE) {
            JsonResponse::error('La imagen excede el tamaño máximo permitido por el servidor', 413);
        }
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            JsonResponse::error('No se pudo recibir la imagen de garantía', 400);
        }
        if ((int) $_FILES['file']['size'] > 5 * 1024 * 1024) {
            JsonResponse::error('La imagen de garantía no puede exceder 5 MB', 413);
        }

        $temporaryFile = $_FILES['file']['tmp_name'];
        if (!is_uploaded_file($temporaryFile)) {
            JsonResponse::error('El archivo recibido no es válido', 400);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!is_string($mime) || !isset($extensions[$mime])) {
            JsonResponse::error('Formato de imagen no permitido', 415);
        }

        $idGarantia = isset($_POST['idGarantia']) ? (int) $_POST['idGarantia'] : null;
        if ($idGarantia !== null && $idGarantia <= 0) {
            JsonResponse::error('El id de la garantía no es válido', 400);
        }

        $targetDir = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'garantias'
            . DIRECTORY_SEPARATOR . $idCliente;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            JsonResponse::error('No se pudo crear el directorio de imágenes de garantías', 500);
        }

        $guaranteePrefix = $idGarantia === null ? '' : $idGarantia . '_';
        $filename = 'garantia_' . $guaranteePrefix . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($temporaryFile, $targetDir . DIRECTORY_SEPARATOR . $filename)) {
            JsonResponse::error('No se pudo guardar la imagen de garantía', 500);
        }

        JsonResponse::ok([
            'stored' => $filename,
            'idCliente' => $idCliente,
            'idGarantia' => $idGarantia,
            'kind' => 'garantia',
        ], 201);
    }

    public function listGarantiaImages(int $idCliente): void
    {
        if ($idCliente <= 0) {
            JsonResponse::error('El id del cliente o aval no es válido', 400);
        }
        $this->assertClienteExists($idCliente);

        $directory = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'garantias'
            . DIRECTORY_SEPARATOR . $idCliente;
        if (!is_dir($directory)) {
            JsonResponse::ok(['items' => [], 'total' => 0]);
        }

        $filenames = scandir($directory);
        if ($filenames === false) {
            JsonResponse::error('No se pudo consultar el directorio de imágenes de garantías', 500);
        }

        $items = [];
        foreach ($filenames as $filename) {
            if (!preg_match('/^garantia_(?:(\d+)_)?[a-f0-9]{32}\.(?:jpg|png)$/i', $filename, $matches)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($path)) {
                continue;
            }
            $mimeType = mime_content_type($path);
            $modifiedAt = filemtime($path);
            $size = filesize($path);
            if ($mimeType === false || $modifiedAt === false || $size === false) {
                JsonResponse::error('No se pudieron consultar los metadatos de una imagen de garantía', 500);
            }

            $items[] = [
                'filename' => $filename,
                'idGarantia' => isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : null,
                'mimeType' => $mimeType,
                'size' => $size,
                'uploadedAt' => date(DATE_ATOM, $modifiedAt),
                'downloadPath' => '/ver/garantias/' . $idCliente . '/' . rawurlencode($filename),
            ];
        }

        usort(
            $items,
            static fn(array $left, array $right): int => strcmp($right['uploadedAt'], $left['uploadedAt']),
        );
        JsonResponse::ok(['items' => $items, 'total' => count($items)]);
    }

    public function getGarantiaImage(int $idCliente, string $filename): void
    {
        if ($idCliente <= 0 || !preg_match('/^garantia_(?:\d+_)?[a-f0-9]{32}\.(?:jpg|png)$/i', $filename)) {
            JsonResponse::notFound('Imagen de garantía no encontrada');
        }
        $this->assertClienteExists($idCliente);

        $file = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'garantias'
            . DIRECTORY_SEPARATOR . $idCliente
            . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($file)) {
            JsonResponse::notFound('Imagen de garantía no encontrada');
        }

        header('Content-Type: ' . (mime_content_type($file) ?: 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }

    public function uploadVideoResponsabilidad(int $id): void
    {
        if ($id <= 0) {
            JsonResponse::error('El id del cliente o aval no es válido', 400);
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM cliente WHERE id = ? LIMIT 1');
        $exists->execute([$id]);
        if ($exists->fetchColumn() === false) {
            JsonResponse::notFound('Cliente o aval no encontrado');
        }

        if (!isset($_FILES['file'])) {
            JsonResponse::error('No se recibió archivo', 400);
        }
        if ($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE) {
            JsonResponse::error('El video excede el tamaño máximo permitido por el servidor', 413);
        }
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            JsonResponse::error('No se pudo recibir el video', 400);
        }

        $temporaryFile = $_FILES['file']['tmp_name'];
        if (!is_uploaded_file($temporaryFile)) {
            JsonResponse::error('El archivo recibido no es válido', 400);
        }

        $videoTypes = [
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'video/x-msvideo' => 'avi',
            'video/mpeg' => 'mpeg',
            'video/3gpp' => '3gp',
        ];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
        if (!is_string($mime) || !isset($videoTypes[$mime])) {
            JsonResponse::error('Formato de video no permitido', 415);
        }

        $targetDir = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'videoresponsabilidad';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            JsonResponse::error('No se pudo crear el directorio de videos', 500);
        }

        $extension = $videoTypes[$mime];
        $filename = $id . '.' . $extension;
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryFile, $filePath)) {
            JsonResponse::error('No se pudo guardar el video', 500);
        }

        foreach (array_values($videoTypes) as $previousExtension) {
            if ($previousExtension === $extension) {
                continue;
            }
            $previousFile = $targetDir . DIRECTORY_SEPARATOR . $id . '.' . $previousExtension;
            if (is_file($previousFile) && !unlink($previousFile)) {
                JsonResponse::error('El video se guardó, pero no se pudo retirar una versión anterior', 500);
            }
        }

        JsonResponse::ok([
            'stored' => $filename,
            'id' => $id,
            'mimeType' => $mime,
            'size' => filesize($filePath),
        ], 201);
    }

    public function getClienteArchivo(string $kind, string $filename): void
    {
        $folders = [
            'comprobantedomicilio' => 'clientes',
            'identificacion' => 'identificacion',
            'imagen' => 'clientes',
        ];
        if (!isset($folders[$kind])) {
            JsonResponse::notFound('Archivo no encontrado');
        }
        $this->sendUploadedFile($folders[$kind], $filename);
    }

    public function getViviendaArchivo(string $filename): void
    {
        $this->sendUploadedFile('vivienda', $filename);
    }

    private function storeUploadedFile(string $folder, string $kind, ?int $id, array $allowedMimes): void
    {
        if (!isset($_FILES['file'])) {
            JsonResponse::error('No se recibió archivo', 400);
        }
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            JsonResponse::error('No se pudo recibir el archivo', 400);
        }

        $temporaryFile = $_FILES['file']['tmp_name'];
        if (!is_uploaded_file($temporaryFile)) {
            JsonResponse::error('El archivo recibido no es válido', 400);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ];
        if (!is_string($mime) || !in_array($mime, $allowedMimes, true)) {
            JsonResponse::error('Formato de archivo no permitido', 415);
        }

        $targetDir = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            JsonResponse::error('No se pudo crear el directorio de archivos', 500);
        }

        $filename = $kind . '_' . ($id ?? 'cliente') . '_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryFile, $filePath)) {
            JsonResponse::error('No se pudo guardar el archivo', 500);
        }

        JsonResponse::ok(['stored' => $filename, 'kind' => $kind, 'id' => $id], 201);
    }

    private function sendUploadedFile(string $folder, string $filename): void
    {
        $safeFilename = basename($filename);
        $file = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $folder
            . DIRECTORY_SEPARATOR . $safeFilename;
        if ($safeFilename !== $filename || !is_file($file)) {
            JsonResponse::notFound('Archivo no encontrado');
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }

    public function listViviendas(): void
    {
        $statement = $this->pdo->query('SELECT * FROM vivienda WHERE status = 1 ORDER BY id DESC');
        JsonResponse::ok(array_map($this->formatVivienda(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function createVivienda(): void
    {
        $data = $this->readJson();
        $fields = $this->mapInput($data, [
            'fechaingreso' => ['fechaIngreso', 'fechaingreso'],
            'tipovivienda' => ['tipoVivienda', 'tipovivienda', 'tipo'],
            'valor' => ['valor'],
            'numerohabitantes' => ['numeroHabitantes', 'numerohabitantes'],
            'idcliente' => ['idCliente', 'idcliente'],
            'status' => ['status'],
            'latitud' => ['latitud'],
            'longitud' => ['longitud'],
        ]);
        $this->normalizeBoolean($fields, 'status');
        $id = $this->insertRecord('vivienda', $fields);
        JsonResponse::ok($id, 201);
    }

    public function updateVivienda(int $id): void
    {
        $this->updateRelatedResource('vivienda', $id, [
            'fechaingreso' => ['fechaIngreso', 'fechaingreso'],
            'tipovivienda' => ['tipoVivienda', 'tipovivienda', 'tipo'],
            'valor' => ['valor'],
            'numerohabitantes' => ['numeroHabitantes', 'numerohabitantes'],
            'idcliente' => ['idCliente', 'idcliente'],
            'status' => ['status'],
            'latitud' => ['latitud'],
            'longitud' => ['longitud'],
        ]);
    }

    public function getViviendasPorCliente(int $idCliente): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM vivienda WHERE idcliente = ? AND status = 1 ORDER BY id DESC');
        $statement->execute([$idCliente]);
        JsonResponse::ok(array_map($this->formatVivienda(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function listConyugues(): void
    {
        $statement = $this->pdo->query('SELECT * FROM conyugue WHERE status = 1 ORDER BY id DESC');
        JsonResponse::ok(array_map($this->formatConyugue(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function createConyuge(): void
    {
        $fields = $this->mapInput($this->readJson(), [
            'nombre' => ['nombre'],
            'celular' => ['celular'],
            'empresa' => ['empresa', 'nombreEmpresa', 'nombreempresa'],
            'domicilio' => ['domicilio', 'domicilioEmpresa', 'domicilioempresa'],
            'puesto' => ['puesto'],
            'antiguedad' => ['antiguedad'],
            'sueldo' => ['sueldo'],
            'idcliente' => ['idCliente', 'idcliente'],
            'status' => ['status'],
        ]);
        $this->normalizeBoolean($fields, 'status');
        JsonResponse::ok($this->insertRecord('conyugue', $fields), 201);
    }

    public function getConyuguesPorCliente(int $idCliente): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM conyugue WHERE idcliente = ? AND status = 1 ORDER BY id DESC');
        $statement->execute([$idCliente]);
        JsonResponse::ok(array_map($this->formatConyugue(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function listReferencias(): void
    {
        $statement = $this->pdo->query('SELECT * FROM referencia WHERE status = 1 ORDER BY id DESC');
        JsonResponse::ok(array_map($this->formatReferencia(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function createReferencia(): void
    {
        $fields = $this->mapInput($this->readJson(), [
            'nombre' => ['nombre'],
            'edad' => ['edad'],
            'celular' => ['celular'],
            'domicilio' => ['domicilio'],
            'parentesco' => ['parentesco'],
            'tiempoconocer' => ['tiempoConocer', 'tiempoconocer'],
            'status' => ['status'],
            'idcliente' => ['idCliente', 'idcliente'],
        ]);
        $this->normalizeBoolean($fields, 'status');
        JsonResponse::ok($this->insertRecord('referencia', $fields), 201);
    }

    public function getReferenciasPorCliente(int $idCliente): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM referencia WHERE idcliente = ? AND status = 1 ORDER BY id DESC');
        $statement->execute([$idCliente]);
        JsonResponse::ok(array_map($this->formatReferencia(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function listGarantias(): void
    {
        $statement = $this->pdo->query('SELECT * FROM garantia WHERE status = 1 ORDER BY id DESC');
        JsonResponse::ok(array_map($this->formatGarantia(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function createGarantia(): void
    {
        $fields = $this->mapInput($this->readJson(), [
            'descripcion' => ['descripcion'],
            'status' => ['status'],
            'idsolicitudcredito' => ['idSolicitudCredito', 'idsolicitudcredito'],
        ]);
        $this->normalizeBoolean($fields, 'status');
        JsonResponse::ok($this->insertRecord('garantia', $fields), 201);
    }

    public function getGarantiasPorSolicitudCredito(int $idSolicitudCredito): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM garantia WHERE idsolicitudcredito = ? AND status = 1 ORDER BY id DESC');
        $statement->execute([$idSolicitudCredito]);
        JsonResponse::ok(array_map($this->formatGarantia(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function updateGenericResource(string $resource, int $id): void
    {
        $config = [
            'conyugues' => ['table' => 'conyugue', 'columns' => [
                'nombre' => ['nombre'], 'celular' => ['celular'], 'empresa' => ['empresa', 'nombreEmpresa'],
                'domicilio' => ['domicilio', 'domicilioEmpresa'], 'puesto' => ['puesto'], 'antiguedad' => ['antiguedad'],
                'sueldo' => ['sueldo'], 'idcliente' => ['idCliente'], 'status' => ['status'],
            ]],
            'viviendas' => ['table' => 'vivienda', 'columns' => [
                'fechaingreso' => ['fechaIngreso'], 'tipovivienda' => ['tipoVivienda', 'tipo'], 'valor' => ['valor'],
                'numerohabitantes' => ['numeroHabitantes'], 'idcliente' => ['idCliente'], 'status' => ['status'],
                'latitud' => ['latitud'], 'longitud' => ['longitud'],
            ]],
            'referencias' => ['table' => 'referencia', 'columns' => [
                'nombre' => ['nombre'], 'edad' => ['edad'], 'celular' => ['celular'], 'domicilio' => ['domicilio'],
                'parentesco' => ['parentesco'], 'tiempoconocer' => ['tiempoConocer'], 'status' => ['status'],
                'idcliente' => ['idCliente'],
            ]],
            'garantias' => ['table' => 'garantia', 'columns' => [
                'descripcion' => ['descripcion'], 'status' => ['status'], 'idsolicitudcredito' => ['idSolicitudCredito'],
            ]],
        ];
        if (!isset($config[$resource])) {
            JsonResponse::notFound('Recurso no encontrado');
        }

        $this->updateRelatedResource($config[$resource]['table'], $id, $config[$resource]['columns']);
    }

    private function updateRelatedResource(string $table, int $id, array $columns): void
    {
        $fields = $this->mapInput($this->readJson(), $columns);
        $this->normalizeBoolean($fields, 'status');
        if (!$this->updateRecord($table, $id, $fields)) {
            JsonResponse::notFound(ucfirst($table) . ' no encontrado');
        }
        JsonResponse::ok(['updated' => true, 'id' => $id]);
    }

    private function findCliente(int $id, string $notFoundMessage): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM cliente WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound($notFoundMessage);
        }
        JsonResponse::ok($this->formatCliente($row));
    }

    private function assertClienteExists(int $id): void
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM cliente WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        if ($statement->fetchColumn() === false) {
            JsonResponse::notFound('Cliente o aval no encontrado');
        }
    }

    private function formatCliente(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'domicilio' => $row['domicilio'],
            'nacionalidad' => $row['nacionalidad'],
            'lugarNacimiento' => $row['lugarnacimiento'],
            'fechaNacimiento' => $row['fechanacimiento'],
            'sexo' => $row['sexo'] === null ? null : (int) $row['sexo'],
            'curp' => $row['curp'],
            'estadoCivil' => $row['estadocivil'],
            'email' => $row['email'],
            'telefono' => $row['telefono'],
            'celular' => $row['celular'],
            'numeroDependientes' => $row['numerodependientes'] === null ? null : (int) $row['numerodependientes'],
            'nombreEmpresa' => $row['nombreempresa'],
            'puesto' => $row['puesto'],
            'sueldo' => $row['sueldo'],
            'ingresoMensual' => $row['ingresomensual'],
            'antiguedad' => $row['antiguedad'],
            'domicilioEmpresa' => $row['domicilioempresa'],
            'medioCaptacion' => $row['mediocaptacion'],
            'esAval' => (bool) $row['esaval'],
            'idClienteAvalado' => $row['idclienteavalado'] === null ? null : (int) $row['idclienteavalado'],
            'status' => (bool) $row['status'],
        ];
    }

    private function normalizeClienteFields(array $fields): array
    {
        if (isset($fields['fechanacimiento']) && is_string($fields['fechanacimiento'])) {
            $fields['fechanacimiento'] = substr($fields['fechanacimiento'], 0, 10);
        }
        $this->normalizeBoolean($fields, 'esaval');
        $this->normalizeBoolean($fields, 'status');
        return $fields;
    }

    private function normalizeBoolean(array &$fields, string $column): void
    {
        if (array_key_exists($column, $fields)) {
            $fields[$column] = (int) (bool) $fields[$column];
        }
    }

    private function formatVivienda(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'fechaIngreso' => $row['fechaingreso'],
            'tipoVivienda' => $row['tipovivienda'],
            'valor' => $row['valor'],
            'numeroHabitantes' => $row['numerohabitantes'] === null ? null : (int) $row['numerohabitantes'],
            'idCliente' => $row['idcliente'] === null ? null : (int) $row['idcliente'],
            'status' => (bool) $row['status'],
            'latitud' => $row['latitud'],
            'longitud' => $row['longitud'],
        ];
    }

    private function formatConyugue(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'celular' => $row['celular'],
            'nombreEmpresa' => $row['empresa'],
            'domicilioEmpresa' => $row['domicilio'],
            'puesto' => $row['puesto'],
            'antiguedad' => $row['antiguedad'],
            'sueldo' => $row['sueldo'],
            'status' => (bool) $row['status'],
            'idCliente' => $row['idcliente'] === null ? null : (int) $row['idcliente'],
        ];
    }

    private function formatReferencia(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'edad' => $row['edad'] === null ? null : (int) $row['edad'],
            'celular' => $row['celular'],
            'domicilio' => $row['domicilio'],
            'parentesco' => $row['parentesco'],
            'tiempoConocer' => $row['tiempoconocer'] === null ? null : (int) $row['tiempoconocer'],
            'status' => (bool) $row['status'],
            'idCliente' => $row['idcliente'] === null ? null : (int) $row['idcliente'],
        ];
    }

    private function formatGarantia(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'descripcion' => $row['descripcion'],
            'status' => (bool) $row['status'],
            'idSolicitudCredito' => $row['idsolicitudcredito'] === null ? null : (int) $row['idsolicitudcredito'],
        ];
    }
}
