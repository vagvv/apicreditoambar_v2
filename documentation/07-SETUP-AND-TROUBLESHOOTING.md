# 07-SETUP-AND-TROUBLESHOOTING.md

Prerequisitos
- .NET 8 SDK instalado.
- PostgreSQL para desarrollo (connection string `ConexionPostgres`).
- Node.js + npm / yarn (si se crea React frontend).

Comandos locales
- Backend:
  - `dotnet restore`
  - `dotnet build ./ApiCreditoAmbar`
  - `dotnet run --project ApiCreditoAmbar`
- Blazor frontend:
  - Abrir `AppCreditoAmbar` en Visual Studio o `dotnet run --project AppCreditoAmbar`.
- React frontend (propuesto):
  - `npm install` (en `app-react`)
  - `npm run dev` o `npm run build`

Problemas comunes
- Token 401: revisar reloj de host (ClockSkew 1 minuto en `Program.cs`).
- CORS: `ApiCreditoAmbar/Program.cs` define política `FrontendDev`. Añadir origen de React en dev si es necesario.
- Uploads fallan: revisar límites y `WithFileValidation` en backend; los streams en Blazor usan `5 * 1024 * 1024`.

Cómo comprobar paridad
- Ejecutar Blazor localmente y replicar cada flujo en React usando los Acceptance Tests definidos (ver apartado tests).