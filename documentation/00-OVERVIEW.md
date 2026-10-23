# 00-OVERVIEW.md

Resumen rápido
- Backend: `ApiCreditoAmbar` — .NET 8 Minimal Web API (`ApiCreditoAmbar/Program.cs`).
- Frontend actual (referencia funcional): `AppCreditoAmbar` — Blazor (componentes en `AppCreditoAmbar/Components`).
- Frontend objetivo: React (no encontrado en el repositorio; ver sección de estado).
- Comunicación: HTTP JSON; autenticación por JWT (ver `ApiCreditoAmbar/Program.cs`).

Ubicación de la documentación
- `documentation/01-SOLUTION-ARCHITECTURE.md`
- `documentation/02-BLAZOR-FUNCTIONAL-GUIDE.md`
- `documentation/03-BACKEND-API-REFERENCE.md`
- `documentation/04-REACT-FRONTEND-GUIDE.md`
- `documentation/05-BLAZOR-TO-REACT-PARITY.md`
- `documentation/06-REACT-MIGRATION-ROADMAP.md`
- `documentation/07-SETUP-AND-TROUBLESHOOTING.md`

Propósito de esta fase (Stage A)
- Inspección completa del repositorio.
- Inventario funcional del frontend Blazor.
- Catálogo de endpoints del backend (fuente: `ApiCreditoAmbar/Program.cs`).
- Estado actual del frontend React (si existe).
- Matriz de paridad y roadmap incremental para migración a React.