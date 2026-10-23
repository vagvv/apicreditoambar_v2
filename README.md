# ApiCreditoAmbar PHP API

This project reconstructs the original `ApiCreditoAmbar` backend in pure PHP without frameworks, preserving the documented HTTP JSON contracts and the JWT-style authorization flow expected by the React frontend.

## Quick start

1. Copy `.env.example` to `.env` and adjust the values.
2. Start the API:
   ```bash
   cd C:/xampp/htdocs/apicreditoambar
   php -S localhost:5000 -t public
   ```
3. Point the frontend to:
   ```env
   VITE_API_URL=http://localhost:5000/api
   ```

## Notes

- All structured API data is read from and written to MySQL through PDO. The API does not fall back to JSON files; it returns `503` when the database connection is unavailable.
- Client photos and address proofs are stored under `storage/uploads/clientes`, identification documents under `storage/uploads/identificacion`, housing photos under `storage/uploads/vivienda`, and responsibility videos under `storage/uploads/videoresponsabilidad/{id}.{extension}`. Upload endpoints validate the detected MIME type; files are not database-backed.
- Guarantee photos are stored under `storage/uploads/garantias/{idClienteOaval}`; this endpoint accepts JPEG and PNG images.
- Guarantee uploads include the guarantee record id so the expediente can show each image under its guarantee; the listing endpoint also returns older images without an associated guarantee id.
- All endpoints are implemented under `/api`.

## Main endpoints

- `POST /api/auth/login`
- `GET /api/clientes`
- `GET /api/clientes/{id}`
- `POST /api/clientes/add`
- `POST /api/clientes/{id}/update`
- `PUT /api/clientes/{id}`
- `POST /api/clientes/upload-foto?id={idCliente}`
- `POST /api/clientes/upload-identificacion?id={idCliente}`
- `POST /api/clientes/upload-comprobantedomicilio?id={idCliente}`
- `POST /api/cargar/videoresponsabilidad?id={idClienteOaval}`
- `POST /api/cargar/garantias/{idClienteOaval}`
- `GET /api/ver/garantias/{idClienteOaval}/`
- `GET /api/ver/garantias/{idClienteOaval}/{filename}`
- `GET /api/clientes/{id}/foto`
- `GET /api/clientes/{id}/documentos`
- `GET /api/clientes/imagen/{filename}`
- `GET /api/clientes/identificacion/{filename}`
- `GET /api/clientes/comprobantedomicilio/{filename}`
- `GET /api/solicitudescredito?page={page}&pageSize={pageSize}`
- `GET /api/solicitudcredito/view`
- `POST /api/solicitudescredito/add`
- `POST /api/solicitudescredito/authorize`
- `POST /api/solicitudescredito/{id}/reject`
- `GET /api/prestamos/activos?page={page}&pageSize={pageSize}`
- `GET /api/prestamos/por-solicitud-credito/{id}`
- `GET /api/pagos`
- `GET /api/pagos/pendientes-por-fecha/{fecha}`
- `GET /api/pagos/por-solicitud-credito/{id}`
- `POST /api/pagos/add`
- `POST /api/pagos/{id}/done`
- `POST /api/pagos/{id}/apply-extra-week`
- `GET /api/usuarios/search`
- `GET /api/usuarios`
- `POST /api/usuarios/add`
- `GET /api/conyugues`
- `POST /api/conyugues/add`
- `GET /api/viviendas`
- `POST /api/viviendas/add`
- `POST /api/viviendas/upload-imagen?id={idCliente}`
- `GET /api/viviendas/imagen/{filename}`
- `GET /api/referencias`
- `POST /api/referencias/add`
- `GET /api/garantias`
- `POST /api/garantias/add`

## Security

- JWT-style tokens are generated and validated with a shared secret.
- Authorization headers are accepted as `Authorization: Bearer <token>` and the role is also read from `X-Auth-Role`.
