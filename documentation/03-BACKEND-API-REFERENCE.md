# 03-BACKEND-API-REFERENCE.md

Catálogo principal de endpoints (fuente: `ApiCreditoAmbar/Program.cs`)

Notas:
- JWT: configurado en `Program.cs` con issuer/audience y `ClockSkew = 1 min`.
- Repositorios e interfaces están en `Infrastructure/Repositories` e `Domain/Repositories`.
- MediatR se utiliza para delegar en handlers (`Application/*`).

Ejemplos seleccionados (completo en `ApiCreditoAmbar/Program.cs`)
- POST `/auth/login`
  - Body: `{ User, Password }` (type: `ApiCreditoAmbar.Application.Authentication.LoginRequest`)
  - Anonymous.
  - 200: `{ token, role, userId }`
  - 401: credenciales invalidas.

- Clientes
  - GET `/clientes?page={page}&pageSize={pageSize}` — Roles: admin
  - GET `/clientes/{id}` — Roles: admin
  - POST `/clientes/add` — Roles: admin — Body: `CreateClienteCommand` — 201 Created `/clientes/{id}`
  - PUT `/clientes/{id}` y `POST /clientes/{id}/update` — actualizar.
  - POST `/clientes/upload-comprobantedomicilio` etc. — aceptan `IFormFile` y aplican `.WithFileValidation(...)` (5 MB).

- Conyugues, Garantias, Pagos, Referencias, SolicitudesCredito, Usuarios, Viviendas:
  - Ver `ApiCreditoAmbar/Program.cs` secciones `#region ...` para listas de rutas.
  - Roles usados: `admin` y `empleado`. Ej.: `pagos` endpoints requieren `admin,empleado`.

Observaciones sobre contratos y comportamiento
- Muchas rutas devuelven `Results.Ok(obj)` o `Results.Created(location, id)`.
- Algunos endpoints devuelven `Results.Problem("Error desconocido", null, 0)` en excepciones (ver `clientes/add`), revisar para normalizar status codes.
- Rutas de archivos devuelven `Results.File(bytes, "image/jpeg")`.
- Fecha en ruta: `GET /pagos/pendientes-por-fecha/{fecha}` parsea `DateTime` por la ruta. Blazor construye `fecha` como `YYYY-M-D`.

Referencias de implementación
- Program.cs: `ApiCreditoAmbar/Program.cs` (mapeo completo).
- Handlers: `ApiCreditoAmbar/Application/*`.
- Repositorios: `ApiCreditoAmbar/Infrastructure/Repositories/*`.