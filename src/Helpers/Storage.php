<?php

declare(strict_types=1);

namespace App\Helpers;

final class Storage
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    public function read(string $name): array
    {
        $file = $this->storagePath . DIRECTORY_SEPARATOR . $name . '.json';
        if (!is_file($file)) {
            return [];
        }

        $json = file_get_contents($file);
        $data = json_decode($json ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    public function write(string $name, array $data): void
    {
        $file = $this->storagePath . DIRECTORY_SEPARATOR . $name . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function users(): array
    {
        return $this->read('users');
    }

    public function clientes(): array
    {
        return $this->read('clientes');
    }

    public function solicitudes(): array
    {
        return $this->read('solicitudes');
    }

    public function pagos(): array
    {
        return $this->read('pagos');
    }

    public function resource(string $name): array
    {
        return $this->read($name);
    }

    public function saveResource(string $name, array $items): void
    {
        $this->write($name, $items);
    }

    public function ensureDefaults(): void
    {
        $defaultUsers = [
            [
                'id' => 1,
                'user' => 'admin',
                'password' => 'admin123',
                'role' => 'admin',
                'nombre' => 'Administrador',
            ],
            [
                'id' => 2,
                'user' => 'empleado',
                'password' => 'empleado123',
                'role' => 'empleado',
                'nombre' => 'Empleado',
            ],
        ];

        if ($this->read('users') === []) {
            $this->write('users', $defaultUsers);
        }

        if ($this->read('clientes') === []) {
            $this->write('clientes', [[
                'id' => 1,
                'nombre' => 'Juan Pérez',
                'domicilio' => 'Calle Reforma 123',
                'nacionalidad' => 'Mexicana',
                'lugarNacimiento' => 'Guadalajara',
                'fechaNacimiento' => '1993-04-18',
                'sexo' => 1,
                'curp' => 'PEJJ930418HDFRNC09',
                'estadoCivil' => 'Soltero',
                'email' => 'juan.perez@example.com',
                'telefono' => '3331234567',
                'celular' => '3331234567',
                'numeroDependientes' => 0,
                'nombreEmpresa' => 'Crédito Ámbar',
                'puesto' => 'Gerente',
                'sueldo' => 25000,
                'ingresoMensual' => 25000,
                'antiguedad' => '2 años',
                'domicilioEmpresa' => 'Av. México 45',
                'medioCaptacion' => 'Internet',
                'esAval' => false,
                'idClienteAvalado' => 0,
                'status' => true,
            ]]);
        }

        if ($this->read('solicitudes') === []) {
            $this->write('solicitudes', [[
                'id' => 1,
                'idCliente' => 1,
                'nombreCliente' => 'Juan Pérez',
                'montoSolicitado' => 15000,
                'tipoAutorizacion' => 'Pendiente',
                'status' => 'Pendiente',
                'fechaSolicitud' => '2025-01-08',
            ]]);
        }

        if ($this->read('pagos') === []) {
            $this->write('pagos', [[
                'id' => 1,
                'idSolicitudCredito' => 1,
                'nombreCliente' => 'Juan Pérez',
                'cantidad' => 1350,
                'fechaPago' => '2025-01-15',
                'status' => 'Pendiente',
            ]]);
        }
    }
}
