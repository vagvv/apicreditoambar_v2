# 02-BLAZOR-FUNCTIONAL-GUIDE.md

Propósito
- Guía exhaustiva de la aplicación Blazor (referencia funcional). Incluye rutas, componentes clave, servicios, validaciones y flujos que deben conservarse en React.

Estructura de proyecto (resumen)
- `AppCreditoAmbar/Program.cs` — registro de servicios y HttpClient base (BaseAddress: `http://creditoambarapi:8080/` en `Program.cs`).
- `AppCreditoAmbar/Components/` — componentes, layouts y páginas.
  - `Components/Pages/*` — páginas principales (ej.: `Auth/Login.razor`, `Credito/*`, `Prestamo/*`).
  - `Components/Layout/` — layouts (`LoginLayout.razor`).
  - `Components/Forms/` — formularios reutilizables (`FrmCliente.razor`).
  - `Components/Pages/Pagination.razor` — componente de paginación.
- Servicios:
  - `AppCreditoAmbar/Services/ApiService.cs` — cliente HTTP centralizado (añade token desde localStorage salvo llamadas anónimas).
  - `AppCreditoAmbar/Services/ClientFormStateService.cs` — estado compartido del formulario cliente.
  - `AppCreditoAmbar/Services/LoadingService.cs` — indicador global de carga.

Entradas y navegación
- Router principal: `AppCreditoAmbar/Components/Routes.razor`.
- Login:
  - `AppCreditoAmbar/Components/Pages/Auth/Login.razor`
  - POST `/auth/login` (anonymous). Guarda `auth_token` y `auth_role` en localStorage.
- Formularios cliente:
  - Flujos multi-step en `Components/Pages/ClienteFormSteps.razor` y componentes `Step*.razor` (ej. `Step6.razor` — vivienda).
  - Estado centralizado en `ClientFormStateService`.

Páginas clave (inventario resumido — ejemplos)
- `GET /` (Home) — `Home.razor`.
- Auth:
  - `Login.razor` — POST `/auth/login`. Guardado token -> redirige `/dashboard`.
- Clientes:
  - CRUD y uploads manejados por API (`/clientes`, `/clientes/add`, `/clientes/{id}`, `/clientes/upload-*`).
  - Visualización de imágenes: `/clientes/imagen/{nombre}`, `/clientes/identificacion/{nombre}`.
  - Formulario cliente: `Components/Forms/FrmCliente.razor` y `ClienteFormSteps.razor` (multi-step).
- Crédito y Solicitudes:
  - `CreditoDashboard.razor` -> consume `/solicitudcredito/view`.
  - `TablaSolicitudesCredito.razor` — muestra acciones Approve/Reject y comunica con `/solicitudescredito/{id}/reject` y `/solicitudescredito/authorize`.
- Pagos:
  - `PagosPendientes.razor` — GET `pagos/pendientes-por-fecha/{fecha}`, operaciones: `/pagos/{id}/done`, `/pagos/{id}/apply-extra-week`, detalle `/pagos/detalle-pago-pendiente/{id}`.

Formularios, validaciones y archivos
- Validaciones de cliente: validaciones en UI (required fields), y reglas server-side en handlers.
- File upload: Blazor usa `InputFile`, prepara bytes en memoria y guarda en `ClientFormStateService` hasta enviar; backend endpoints aceptan `IFormFile`.
- Localización y mapas: `Step6.razor` integra Google Maps JS API y usa `JSInvokable` para transportar lat/long a `ClientFormStateService`.

Estado compartido
- `ClientFormStateService.FormData` contiene `ClientFormData` con propiedades (ver `AppCreditoAmbar/Models/ClientFormData.cs`) y campos JsonIgnored para bytes y archivos.
- `LoadingService` controla indicador global de carga en layouts.

Comunicaciones con backend desde Blazor
- `ApiService`:
  - Método `Get<T>(string url, bool anonymous = false)` y `Post<T>`, `Put<T>`, `Delete`.
  - `AddAuthHeadersIfNeededAsync` lee `auth_token` y `auth_role` desde `localStorage` mediante `IJSRuntime` y añade header `Authorization: Bearer` y `X-Auth-Role`.