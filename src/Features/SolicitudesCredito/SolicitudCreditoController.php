<?php

declare(strict_types=1);

namespace App\Features\SolicitudesCredito;

use App\Features\Shared\BaseController;
use App\Helpers\JsonResponse;
use DateTimeImmutable;
use PDO;
use Throwable;

final class SolicitudCreditoController extends BaseController
{
    public function listSolicitudesCredito(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM solicitudcredito WHERE status = 1')->fetchColumn();
        $statement = $this->pdo->prepare(
            'SELECT s.*, c.nombre AS nombrecliente,
                    COALESCE(p.numeropagos, 0) AS numeropagos,
                    p.pagosemanal, p.fechaprimerpago
             FROM solicitudcredito s
             LEFT JOIN cliente c ON c.id = s.idcliente
             LEFT JOIN (
                 SELECT idsolicitudcredito, COUNT(*) AS numeropagos,
                        MIN(monto) AS pagosemanal, MIN(fechapago) AS fechaprimerpago
                 FROM pago WHERE status = 1 GROUP BY idsolicitudcredito
             ) p ON p.idsolicitudcredito = s.id
             WHERE s.status = 1
             ORDER BY s.id DESC LIMIT :offset, :limit',
        );
        $statement->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->execute();

        JsonResponse::ok([
            'items' => array_map($this->formatSolicitud(...), $statement->fetchAll(PDO::FETCH_ASSOC)),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $total === 0 ? 0 : (int) ceil($total / $pageSize),
        ]);
    }

    public function getSolicitudCreditoView(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));
        $statement = $this->pdo->prepare(
            'SELECT s.*, c.nombre AS nombrecliente,
                    COALESCE(p.numeropagos, 0) AS numeropagos,
                    p.pagosemanal, p.fechaprimerpago
             FROM solicitudcredito s
             LEFT JOIN cliente c ON c.id = s.idcliente
             LEFT JOIN (
                 SELECT idsolicitudcredito, COUNT(*) AS numeropagos,
                        MIN(monto) AS pagosemanal, MIN(fechapago) AS fechaprimerpago
                 FROM pago WHERE status = 1 GROUP BY idsolicitudcredito
             ) p ON p.idsolicitudcredito = s.id
             WHERE s.status = 1
             ORDER BY s.id DESC LIMIT :offset, :limit',
        );
        $statement->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->execute();
        JsonResponse::ok(array_map($this->formatSolicitud(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function createSolicitudCredito(): void
    {
        $data = $this->readJson();
        $fields = $this->mapInput($data, [
            'fechasolicitud' => ['fechaSolicitud', 'fechasolicitud'],
            'cantidadsolicitada' => ['cantidadSolicitada', 'montoSolicitado', 'cantidadsolicitada'],
            'cantidadaprobada' => ['cantidadAprobada', 'cantidadaprobada'],
            'fechaautorizacion' => ['fechaAutorizacion', 'fechaautorizacion'],
            'tipoautorizacion' => ['tipoAutorizacion', 'tipoautorizacion'],
            'idcliente' => ['idCliente', 'idcliente'],
            'status' => ['status'],
        ]);
        $fields['tipoautorizacion'] = 'Pendiente';
        $fields['cantidadaprobada'] ??= 0;
        $fields['status'] = array_key_exists('status', $fields) ? (int) (bool) $fields['status'] : 1;
        $this->normalizeDate($fields, 'fechasolicitud');
        $this->normalizeDate($fields, 'fechaautorizacion');

        JsonResponse::ok($this->insertRecord('solicitudcredito', $fields), 201);
    }

    public function authorizeSolicitudCredito(): void
    {
        $data = $this->readJson();
        $id = (int) ($data['IdSolicitudCredito'] ?? $data['idSolicitudCredito'] ?? 0);
        $approvedAmount = (float) ($data['CantidadAprobada'] ?? $data['cantidadAprobada'] ?? 0);
        $numberOfPayments = (int) ($data['NumeroPagos'] ?? $data['numeroPagos'] ?? 0);
        $weeklyPayment = (float) ($data['PagoSemanal'] ?? $data['pagoSemanal'] ?? 0);
        $firstPaymentDate = (string) ($data['fechaPrimerPago'] ?? $data['FechaPrimerPago'] ?? '');

        if ($id <= 0 || $approvedAmount <= 0 || $numberOfPayments <= 0 || $weeklyPayment <= 0 || $firstPaymentDate === '') {
            JsonResponse::error('Se requieren solicitud, monto aprobado, número de pagos, pago semanal y fecha del primer pago', 400);
        }

        try {
            $firstPayment = new DateTimeImmutable(substr($firstPaymentDate, 0, 10));
        } catch (\Exception) {
            JsonResponse::error('La fecha del primer pago no es válida', 400);
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM solicitudcredito WHERE id = ? AND status = 1 LIMIT 1');
        $exists->execute([$id]);
        if ($exists->fetchColumn() === false) {
            JsonResponse::notFound('Solicitud no encontrada');
        }

        $existingPayments = $this->pdo->prepare(
            'SELECT COUNT(*) FROM pago WHERE idsolicitudcredito = ? AND status = 1',
        );
        $existingPayments->execute([$id]);
        if ((int) $existingPayments->fetchColumn() > 0) {
            JsonResponse::error('La solicitud ya tiene pagos registrados', 409);
        }

        $this->pdo->beginTransaction();
        try {
            $update = $this->pdo->prepare(
                "UPDATE solicitudcredito
                 SET tipoautorizacion = 'Aprobada', cantidadaprobada = ?, fechaautorizacion = CURDATE()
                 WHERE id = ? AND status = 1",
            );
            $update->execute([$approvedAmount, $id]);

            $insertPayment = $this->pdo->prepare(
                'INSERT INTO pago (numeropago, fechapago, monto, recargo, pagado, status, idsolicitudcredito)
                 VALUES (?, ?, ?, 0, 0, 1, ?)',
            );
            for ($paymentNumber = 1; $paymentNumber <= $numberOfPayments; $paymentNumber++) {
                $paymentDate = $firstPayment->modify('+' . (($paymentNumber - 1) * 7) . ' days')->format('Y-m-d');
                $insertPayment->execute([$paymentNumber, $paymentDate, $weeklyPayment, $id]);
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        JsonResponse::ok(['approved' => $id]);
    }

    public function rejectSolicitudCredito(int $id): void
    {
        if (!$this->updateRecord('solicitudcredito', $id, ['tipoautorizacion' => 'Rechazada'])) {
            JsonResponse::notFound('Solicitud no encontrada');
        }
        JsonResponse::ok(['r' => $id]);
    }

    public function getSolicitudCredito(int $id): void
    {
        $statement = $this->pdo->prepare(
            'SELECT s.*, c.nombre AS nombrecliente,
                    COUNT(p.id) AS numeropagos, MIN(p.monto) AS pagosemanal,
                    MIN(p.fechapago) AS fechaprimerpago
             FROM solicitudcredito s
             LEFT JOIN cliente c ON c.id = s.idcliente
             LEFT JOIN pago p ON p.idsolicitudcredito = s.id AND p.status = 1
             WHERE s.id = ?
             GROUP BY s.id',
        );
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Solicitud no encontrada');
        }
        JsonResponse::ok($this->formatSolicitud($row));
    }

    public function getSolicitudesCreditoPorCliente(int $idCliente): void
    {
        $statement = $this->pdo->prepare(
            'SELECT s.*, c.nombre AS nombrecliente,
                    COALESCE(p.numeropagos, 0) AS numeropagos,
                    p.pagosemanal, p.fechaprimerpago
             FROM solicitudcredito s
             LEFT JOIN cliente c ON c.id = s.idcliente
             LEFT JOIN (
                 SELECT idsolicitudcredito, COUNT(*) AS numeropagos,
                        MIN(monto) AS pagosemanal, MIN(fechapago) AS fechaprimerpago
                 FROM pago WHERE status = 1 GROUP BY idsolicitudcredito
             ) p ON p.idsolicitudcredito = s.id
             WHERE s.idcliente = ? AND s.status = 1
             ORDER BY s.id DESC',
        );
        $statement->execute([$idCliente]);
        JsonResponse::ok(array_map($this->formatSolicitud(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function getPrestamosActivos(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));
        $totalStatement = $this->pdo->query(
            "SELECT COUNT(*) FROM solicitudcredito
             WHERE status = 1 AND LOWER(tipoautorizacion) IN ('aprobada', 'approved')",
        );
        $total = (int) $totalStatement->fetchColumn();
        $statement = $this->pdo->prepare(
            "SELECT s.*, c.nombre AS nombrecliente,
                    COUNT(p.id) AS numeropagos, MIN(p.monto) AS pagosemanal
             FROM solicitudcredito s
             LEFT JOIN cliente c ON c.id = s.idcliente
             LEFT JOIN pago p ON p.idsolicitudcredito = s.id AND p.status = 1
             WHERE s.status = 1 AND LOWER(s.tipoautorizacion) IN ('aprobada', 'approved')
             GROUP BY s.id ORDER BY s.id DESC LIMIT :offset, :limit",
        );
        $statement->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->execute();
        $items = array_map($this->formatSolicitud(...), $statement->fetchAll(PDO::FETCH_ASSOC));

        JsonResponse::ok([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $total === 0 ? 0 : (int) ceil($total / $pageSize),
        ]);
    }

    public function getPrestamoPorSolicitudCredito(int $id): void
    {
        $statement = $this->pdo->prepare(
            "SELECT s.*, c.nombre AS nombrecliente,
                    COUNT(p.id) AS numeropagos, MIN(p.monto) AS pagosemanal,
                    MIN(p.fechapago) AS fechaprimerpago
             FROM solicitudcredito s
             LEFT JOIN cliente c ON c.id = s.idcliente
             LEFT JOIN pago p ON p.idsolicitudcredito = s.id AND p.status = 1
             WHERE s.id = ? AND LOWER(s.tipoautorizacion) IN ('aprobada', 'approved')
             GROUP BY s.id LIMIT 1",
        );
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Préstamo no encontrado');
        }
        JsonResponse::ok($this->formatSolicitud($row));
    }

    private function formatSolicitud(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'idCliente' => isset($row['idcliente']) ? (int) $row['idcliente'] : null,
            'nombreCliente' => $row['nombrecliente'] ?? $row['nombre'] ?? null,
            'montoSolicitado' => $row['cantidadsolicitada'] ?? 0,
            'cantidadSolicitada' => $row['cantidadsolicitada'] ?? 0,
            'cantidadAprobada' => $row['cantidadaprobada'] ?? 0,
            'tipoAutorizacion' => $row['tipoautorizacion'] ?? 'Pendiente',
            'fechaSolicitud' => $row['fechasolicitud'] ?? null,
            'fechaAutorizacion' => $row['fechaautorizacion'] ?? null,
            'status' => isset($row['status']) ? (bool) $row['status'] : ($row['tipoautorizacion'] ?? 'Pendiente'),
            'numeroPagos' => isset($row['numeropagos']) ? (int) $row['numeropagos'] : null,
            'pagoSemanal' => $row['pagosemanal'] ?? null,
            'fechaPrimerPago' => $row['fechaprimerpago'] ?? null,
        ];
    }

    private function normalizeDate(array &$fields, string $column): void
    {
        if (isset($fields[$column]) && is_string($fields[$column])) {
            $fields[$column] = substr($fields[$column], 0, 10);
        }
    }
}
