# 06-REACT-MIGRATION-ROADMAP.md

Fases y tareas (prioritizadas)

Fase 0 — Preparación
- Objetivo: crear los documentos y establecer normas (.editorconfig, CONTRIBUTING).
- Archivos a modificar: raíz del repo.
- Criterio de aceptación: docs añadidos y aprobados.

Fase 1 — Fundamentos React
- Objetivo: crear proyecto React + TypeScript y base de rutas.
- Archivos: `frontend/` o `app-react/` (nuevo).
- Pasos:
  1. `npx create-vite@latest app-react --template react-ts`
  2. Añadir `services/api.ts` basado en `AppCreditoAmbar/Services/ApiService.cs`.
  3. Configurar `REACT_APP_API_BASE_URL` -> `http://localhost:8080/`.
- Aceptación: Login page consigue token y redirige a /dashboard; requests autenticados funcionan.

Fase 2 — Vertical slice crítico
- Objetivo: migrar Solicitudes de crédito (lista y acciones).
- Pasos:
  1. Implementar `pages/CreditoDashboard.tsx` y `components/TablaSolicitudes.tsx`.
  2. Consumir `/solicitudcredito/view`.
  3. Implementar Approve/Reject con llamadas a endpoints correspondientes.
- Aceptación: Paridad de comportamiento con Blazor para solicitudes.

Fase 3 — Pagos y collection periods
- Objetivo: migrar `PagosPendientes`.
- Pasos:
  1. Implementar tabla, modal de pago y confirmaciones.
  2. Integrar llamadas `/pagos/{id}/done` y `/pagos/{id}/apply-extra-week`.
- Aceptación: Realizar pago y marcar vencido reflejado en UI.

Fase 4 — Formulario cliente (multi-step)
- Objetivo: migrar `ClienteFormSteps` y `Step*.razor`.
- Pasos:
  1. Implementar state container (Context + localStorage).
  2. Implementar file uploads (multipart/form-data).
  3. Mapas y geolocalización en Step6.
- Aceptación: Completar flujo de creación cliente con archivos subidos.

Fase 5 — Tests y QA
- Objetivo: pruebas unitarias y E2E comparando React vs Blazor.
- Implementar test-cases definidos en `documentation/07-SETUP-AND-TROUBLESHOOTING.md`.

Riesgos
- Diferencias en manejo de formatos de fecha (rutas con DateTime).
- Endpoints que devuelven null o status poco explícitos (refactor requerido en backend si se quiere estandarizar).