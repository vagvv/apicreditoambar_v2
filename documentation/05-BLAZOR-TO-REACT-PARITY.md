# 05-BLAZOR-TO-REACT-PARITY.md

Matriz de paridad (resumen; ver lista completa en tasks del roadmap)

| Módulo | Blazor Functionality | Backend Dependencies | React Status | Missing Work | Priority |
|---|---:|---|---|---|---|
| Login | `Login.razor` POST `/auth/login`, guarda `auth_token`, `auth_role` en localStorage | `/auth/login` | Missing | Implementar login y token storage | High |
| Solicitudes de crédito | `CreditoDashboard.razor`, `TablaSolicitudesCredito.razor` consume `/solicitudcredito/view`, approve/reject | `/solicitudcredito/*` | Missing | Implementar tabla, paginación y acciones | High |
| Pagos pendientes | `PagosPendientes.razor` consume `/pagos/pendientes-por-fecha/{fecha}`, acciones `/pagos/{id}/done` y `/pagos/{id}/apply-extra-week` | `/pagos/*` | Missing | Tabla, modal de pago, confirmaciones | High |
| Formulario cliente (multi-step) | `ClienteFormSteps.razor`, Steps 1..N, estado en `ClientFormStateService` | `/clientes/*` + uploads | Missing | Implementar multi-step + state + uploads | High |
| File uploads | `Step6.razor` y uploads clientes | `/clientes/upload-*` | Missing | Multipart upload con bytes | Medium |
| Map / geolocation | `Step6.razor` Google Maps integration | Google Maps API | Missing | Integrar Google Maps, draggable marker | Medium |

Observaciones
- Estado React: no presente. Todas las filas marcadas como Missing.
- Prioridad inicial: Login y un vertical slice (Login -> Solicitudes view -> acciones) para validar API y auth.