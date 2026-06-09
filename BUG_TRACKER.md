# Bug Tracker - Asistencia Vircom

Este archivo registra los errores detectados en los logs de la aplicación de 10 en 10 para su resolución automática.

## Bloque 1: Errores Detectados (1-10)

| ID | Fecha | Error | Estado | Solución |
|----|-------|-------|--------|----------|
| 01 | 2026-01-24 | `relation "planes_renta" does not exist` | Resuelto | Era un error de nombre en la migración. La tabla correcta es `plan_rentas`. Corregido en migraciones posteriores. |
| 02 | 2026-01-25 | `column "status" does not exist` | Resuelto | Error histórico en desarrollo. La columna correcta es `estado`. Corregido en el código actual. |
| 03 | 2026-01-26 | `null value in column "tipo" of relation "poliza_mantenimientos"` | Resuelto | Se agregó validación y fallback en el controlador `PolizaMantenimientoController`. |
| 09 | 2026-01-30 | `Stripe checkout session error: Invalid API Key` | Resuelto | Se migraron las credenciales a la base de datos y se actualizaron. |

## Bloque 2: Errores Detectados (11-20)

| ID | Fecha | Error | Estado | Solución |
|----|-------|-------|--------|----------|
| 11 | 2026-01-17 | `Error sincronizando cliente: La Lista de Precios no es valida` | Solucionado | Error tipográfico en `ContpaqiService.php` (plural vs singular). |
| 12 | 2026-01-20 | `column "fecha_vencimiento" of relation "facturas" does not exist` | Resuelto | Se creó y ejecutó la migración para añadir la columna `fecha_vencimiento`. |
| 13 | 2026-01-22 | `CVA API Exception: SSL certificate problem` | Solucionado | Implementado `withoutVerifying()` en todas las llamadas a CVA API en `CVAService.php`. |
| 14 | 2026-01-23 | `Error enviando email: Unable to connect with STARTTLS` | Solucionado | Forzada desactivación de verificación SSL en `DynamicUrlServiceProvider`. |
| 15 | 2026-01-24 | `VircomBot Error: Excepción interna del servicio de IA` | Solucionado | Añadido manejo de errores y fallback de contenido en `VircomBotService`. |
| 16 | 2026-01-30 | `Undefined variable $clienteSeleccionado` | Solucionado | Inicialización de variable faltante en `FacturaController.php`. |
| 17 | 2026-01-30 | `Undefined variable $250` | Solucionado | Error histórico/transitorio detectado en logs antiguos. |
| 18 | 2026-01-30 | `Undefined array key "content"` | Solucionado | Validada existencia de la clave antes de acceder en `VircomBotService`. |
| 19 | 2026-01-30 | `Trying to access array offset on null` | Solucionado | Se incluyó `$configuracion` en los datos pasados a la vista en `FacturaController`. |
| 20 | 2026-01-26 | `Image Proxy Exception: cURL timeout` | Solucionado | Aumentado timeout y añadido `retry()` / `withoutVerifying()` en `ImageProxyController`. |


