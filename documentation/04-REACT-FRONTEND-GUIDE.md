# 04-REACT-FRONTEND-GUIDE.md

Estado actual
- No se encontró una implementación React en el repositorio (no hay `package.json` ni carpeta `src/`).
- Por tanto: no hay archivos React para comparar o parchear. El proceso de migración debe iniciarse creando la base del proyecto React o enlazando un repositorio externo que contenga la aplicación en progreso.

Recomendaciones para la implementación React (mínimo para paridad)
- Estructura:
  - `src/` con `pages/`, `components/`, `services/`, `hooks/`, `types/`, `styles/`.
  - Rutas con `react-router`.
- API client:
  - `services/api.ts` exportando funciones `get`, `post`, `put`, `delete` con axios or fetch.
  - Base URL configurable con `REACT_APP_API_BASE_URL` o `VITE_...`.
  - Agregar interceptor para añadir `Authorization: Bearer <token>` desde `localStorage` y `X-Auth-Role`.
- Tipos/DTOs:
  - Crear `types/` TS con contratos reflejando `ClientFormData`, `PagoPendienteDTO`, modelos usados por Blazor.
- State:
  - Local: formulario multi-step -> Context o Zustand/Redux según complejidad.
  - Global: loading/error -> Context.
- Forms:
  - Usar `react-hook-form` con `yup` para validaciones (si se acepta nueva dependencia); se puede usar validación manual para evitar nuevas libs.
- File uploads:
  - Implementar multipart/form-data para endpoints que aceptan `IFormFile`.
- Mapas:
  - Integrar Google Maps JS (misma API Key o variable de entorno).
- Security:
  - Guardar token en `localStorage` (paralelo a Blazor), añadir lógica de expiración y logout cuando 401.
- Tests:
  - Unit tests con Jest + React Testing Library.
  - E2E: Playwright o Cypress para pruebas de aceptación comparadas con Blazor.

Acciones inmediatas
1. Crear proyecto React con tipo y tooling preferido (Vite + React + TypeScript recomendado).
2. Implementar `services/api.ts` que refleje `AppCreditoAmbar/Services/ApiService.cs`:
   - Métodos: get<T>, post<T>, put<T>, delete.
   - Añadir función `uploadFile(file: File, id: number, url: string)`.
3. Implementar vertical slice: Login page -> token storage -> ProtectedRoute -> Dashboard mínimo (consumo `/solicitudcredito/view`).