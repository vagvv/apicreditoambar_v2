# Stage A — Resumen ejecutivo

1) Arquitectura general
- Backend: `.NET 8` Minimal API (`ApiCreditoAmbar/Program.cs`).
- Frontend de referencia: Blazor (`AppCreditoAmbar/*`).
- React: no presente en repositorio; debe crearse.

2) Workflows Blazor más importantes
- Login (`AppCreditoAmbar/Components/Pages/Auth/Login.razor`) -> `/auth/login`.
- Solicitudes de crédito (`CreditoDashboard.razor`, `TablaSolicitudesCredito.razor`) -> `/solicitudcredito/view`, approve/reject.
- Pagos pendientes (`PagosPendientes.razor`) -> `/pagos/pendientes-por-fecha/{fecha}`, `/pagos/{id}/done`.
- Multi-step cliente (formularios `ClienteFormSteps.razor`, `Step6.razor`) con uploads y Google Maps.

3) Endpoint count & ubicación
- Mapeo completo en `ApiCreditoAmbar/Program.cs` (decenas de rutas; secciones: clientes, conyugues, garantias, pagos, referencias, solicitudescredito, usuarios, viviendas, auth).

4) Estado React
- No existe. Resultado: React Status = Missing para la mayoría de funcionalidades.

5) Missing funcional y gaps de mayor prioridad
- Login y token handling (necesario para todas las llamadas autorizadas).
- Solicitudes y pagos (vitales para operaciones diarias).
- Formulario cliente multi-step y uploads.
- Map integration.

6) Riesgos y issues
- Algunos endpoints devuelven `Results.Problem(..., status:0)` o `Ok(null)` — normalizar.
- Uploads marcadas como `AllowAnonymous` (revisar seguridad).
- ClockSkew 1 minuto — posible expiración en entornos con relojes desincronizados.

7) Orden de implementación recomendado (alto nivel)
- 1) Fundamentos React + auth (login).
- 2) Vertical slice: Solicitudes (lista + acciones).
- 3) Pagos pendientes.
- 4) Formulario cliente y uploads.
- 5) Restantes páginas y polish.

8) Documentos añadidos
- `documentation/00-07.md` set (ver arriba).
- `.editorconfig` y `CONTRIBUTING.md` (preparados).

9) Builds/tests ejecutados
- No ejecuté builds ni tests; análisis estático del repo. (Si quieres, puedo ejecutar `dotnet build` y relatar resultados.)

10) Preguntas abiertas
- ¿Existe un repositorio React externo o el frontend React debe crearse desde 0 aquí?
- ¿Deseáis mantener almacenamiento del token en `localStorage` o preferís cookie http-only para mayor seguridad?
- ¿Cuál es la prioridad de features (por negocio) si hay que escalonar entregas?