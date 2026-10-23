# 01-SOLUTION-ARCHITECTURE.md

Arquitectura general
- Backend: `ApiCreditoAmbar` (Minimal API .NET 8).
  - Configuración principal: `ApiCreditoAmbar/Program.cs`.
  - EF Core: `DbCreditoAmbarContext` (registrado en `Program.cs`).
  - Autenticación: JWT Bearer (clave y parámetros definidos en `Program.cs`).
  - Jobs programados: Hangfire (configurado en `Program.cs`).
- Frontend de referencia: `AppCreditoAmbar` (Blazor).
  - Entry: `AppCreditoAmbar/Components/App.razor` y `AppCreditoAmbar/Program.cs`.
  - Servicios: `AppCreditoAmbar/Services/ApiService.cs`, `ClientFormStateService.cs`, `LoadingService.cs`.
  - Componentes UI: `AppCreditoAmbar/Components/*` (páginas, layouts, formularios).
- Frontend objetivo: React
  - Estado: No encontrado (no hay `package.json`, ni `src/*` en la raíz). Requiere inicialización o importación del proyecto en curso.

Flujo de petición
1. Cliente (Blazor/React) realiza petición HTTP al backend en `http://<api-host>:8080/...`.
2. `ApiCreditoAmbar` procesa la petición (endpoints mapeados en `Program.cs`), usa MediatR para delegar a handlers, repositorios para persistencia (implementaciones en `Infrastructure/Repositories`).
3. Respuesta JSON devuelta con status apropiado (`Ok`, `Created`, `NotFound`, `BadRequest`, `Unauthorized`).

Dependencias externas
- PostgreSQL (connection string `ConexionPostgres`).
- Hangfire con storage PostgreSQL.
- Google Maps JS API (usada desde Blazor para geolocalización en `Step6.razor`).

Ruta a archivos clave
- Backend: `ApiCreditoAmbar/Program.cs`
- Frontend Blazor: `AppCreditoAmbar/Components/`, `AppCreditoAmbar/Services/ApiService.cs`
- Modelos de formulario cliente: `AppCreditoAmbar/Models/ClientFormData.cs`