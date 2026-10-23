<?php

declare(strict_types=1);

namespace App\Features\Pagos;

use App\Features\Shared\BaseController;
use App\Helpers\JsonResponse;
use PDO;

final class PagoController extends BaseController
{
    public function listPagos(): void
    {
        $statement = $this->pdo->query('SELECT * FROM pago ORDER BY fechapago DESC, id DESC');
        JsonResponse::ok(array_map($this->formatPago(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function getPagosPendientesPorFecha(string $fecha): void
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*, s.idcliente, c.nombre AS nombrecliente, c.curp AS clientecurp
             FROM pago p
             INNER JOIN solicitudcredito s ON s.id = p.idsolicitudcredito
             LEFT JOIN cliente c ON c.id = s.idcliente
             WHERE p.fechapago = ? AND p.pagado = 0 AND p.status = 1
             ORDER BY p.id DESC',
        );
        $statement->execute([$fecha]);
        JsonResponse::ok(array_map($this->formatPago(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function getPagosPorSolicitudCredito(int $idSolicitudCredito): void
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM pago WHERE idsolicitudcredito = ? AND status = 1 ORDER BY numeropago, fechapago, id',
        );
        $statement->execute([$idSolicitudCredito]);
        JsonResponse::ok(array_map($this->formatPago(...), $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function addPago(): void
    {
        $data = $this->readJson();
        $fields = $this->mapInput($data, [
            'numeropago' => ['numeroPago', 'numero', 'numeropago'],
            'fechapago' => ['fechaPago', 'fechapago'],
            'monto' => ['monto', 'cantidad'],
            'recargo' => ['recargo'],
            'pagado' => ['pagado'],
            'status' => ['status'],
            'idsolicitudcredito' => ['idSolicitudCredito', 'idsolicitudcredito'],
        ]);
        if (!array_key_exists('pagado', $fields)) {
            $fields['pagado'] = 0;
        }
        if (!array_key_exists('status', $fields)) {
            $fields['status'] = 1;
        }
        $this->normalizeBoolean($fields, 'pagado');
        $this->normalizeBoolean($fields, 'status');
        if (isset($fields['fechapago']) && is_string($fields['fechapago'])) {
            $fields['fechapago'] = substr($fields['fechapago'], 0, 10);
        }

        JsonResponse::ok($this->insertRecord('pago', $fields), 201);
    }

    public function markPagoDone(int $id): void
    {
        if (!$this->updateRecord('pago', $id, ['pagado' => 1])) {
            JsonResponse::notFound('Pago no encontrado');
        }
        $this->getPago($id);
    }

    public function applyExtraWeek(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE pago SET recargo = COALESCE(recargo, 0) + 100 WHERE id = ? AND status = 1',
        );
        $statement->execute([$id]);
        if ($statement->rowCount() === 0) {
            $exists = $this->pdo->prepare('SELECT 1 FROM pago WHERE id = ? AND status = 1 LIMIT 1');
            $exists->execute([$id]);
            if ($exists->fetchColumn() === false) {
                JsonResponse::notFound('Pago no encontrado');
            }
        }
        $this->getPago($id, 'Semana extra');
    }

    public function getProximoPago(int $idSolicitudCredito): void
    {
        $statement = $this->pdo->prepare(
            'SELECT numeropago, fechapago, monto, recargo FROM pago
             WHERE idsolicitudcredito = ? AND pagado = 0 AND status = 1
             ORDER BY fechapago, numeropago, id LIMIT 1',
        );
        $statement->execute([$idSolicitudCredito]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Pago próximo no encontrado');
        }

        $amount = (float) ($row['monto'] ?? 0) + (float) ($row['recargo'] ?? 0);
        JsonResponse::ok([
            'numero' => (int) ($row['numeropago'] ?? 0),
            'monto' => $amount,
            'saldoPendiente' => $amount,
            'fechaProximoPago' => $row['fechapago'],
        ]);
    }

    public function getDetallePagoPendiente(int $id): void
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM vw_detallepagopendiente WHERE idpago = ? LIMIT 1',
        );
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Detalle no encontrado');
        }
        JsonResponse::ok($row);
    }

    private function getPago(int $id, ?string $responseStatus = null): void
    {
        $statement = $this->pdo->prepare('SELECT * FROM pago WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            JsonResponse::notFound('Pago no encontrado');
        }
        $formatted = $this->formatPago($row);
        if ($responseStatus !== null) {
            $formatted['status'] = $responseStatus;
        }
        JsonResponse::ok($formatted);
    }

    private function formatPago(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'numeroPago' => $row['numeropago'] === null ? null : (int) $row['numeropago'],
            'idSolicitudCredito' => $row['idsolicitudcredito'] === null ? null : (int) $row['idsolicitudcredito'],
            'idCliente' => isset($row['idcliente']) ? (int) $row['idcliente'] : null,
            'nombreCliente' => $row['nombrecliente'] ?? null,
            'curp' => $row['clientecurp'] ?? null,
            'cantidad' => (float) ($row['monto'] ?? 0),
            'recargo' => (float) ($row['recargo'] ?? 0),
            'fechaPago' => $row['fechapago'],
            'pagado' => (bool) $row['pagado'],
            'status' => (int) $row['pagado'] === 1 ? 'Pagado' : 'Pendiente',
        ];
    }

    private function normalizeBoolean(array &$fields, string $column): void
    {
        if (array_key_exists($column, $fields)) {
            $fields[$column] = (int) (bool) $fields[$column];
        }
    }
}
