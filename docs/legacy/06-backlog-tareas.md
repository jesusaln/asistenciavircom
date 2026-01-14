# Backlog de tareas (priorizado)

## Asegurar rutas API sensibles
Tipo: Seguridad
Prioridad: P0
Impacto: Alto
Esfuerzo: M
Dónde: `routes/api.php`
Qué hacer: Identificar rutas que deben ser privadas y aplicar `auth:sanctum` + middleware de roles; mantener lista explícita de endpoints públicos.
Criterio de terminado (DoD): Todas las rutas sensibles requieren autenticación y hay tests básicos de acceso/denegación.

## Requerir firma en webhook de WhatsApp
Tipo: Seguridad
Prioridad: P0
Impacto: Alto
Esfuerzo: S
Dónde: `app/Http/Controllers/WhatsAppWebhookController.php`
Qué hacer: Rechazar requests sin `X-Hub-Signature-256` y validar firma obligatoria.
Criterio de terminado (DoD): Webhook devuelve 403 si falta firma; logs muestran intentos rechazados.

## Quitar password por defecto de DB en config
Tipo: Seguridad
Prioridad: P0
Impacto: Alto
Esfuerzo: S
Dónde: `config/database.php`
Qué hacer: Eliminar el default sensible y documentar variable requerida en `.env`.
Criterio de terminado (DoD): No hay credenciales por defecto en config; despliegue falla si no hay `.env`.

## Proteger endpoint publico de prueba WhatsApp
Tipo: Seguridad
Prioridad: P0
Impacto: Alto
Esfuerzo: S
Dónde: `routes/api.php`, `app/Http/Controllers/EmpresaWhatsAppController.php`
Qué hacer: Mover a rutas autenticadas o aplicar `auth:sanctum` + rate limit; reducir logs a metadatos no sensibles.
Criterio de terminado (DoD): Endpoint requiere auth y ya no loguea headers/body completos.

## Resolver empresa por tenant/usuario (no usar first)
Tipo: Bug
Prioridad: P1
Impacto: Alto
Esfuerzo: M
Dónde: `app/Http/Controllers/EmpresaWhatsAppController.php`
Qué hacer: Obtener la empresa desde el usuario autenticado o el contexto actual.
Criterio de terminado (DoD): Configuración solo afecta a la empresa activa y hay test de aislamiento.

## Corregir CORS por entorno
Tipo: Seguridad
Prioridad: P1
Impacto: Alto
Esfuerzo: M
Dónde: `app/Http/Middleware/CorsMiddleware.php`
Qué hacer: Reemplazar el CORS manual por configuración por entorno (lista blanca de orígenes).
Criterio de terminado (DoD): Solo orígenes permitidos reciben CORS con credenciales.

## Optimizar webhook para evitar N+1
Tipo: Performance
Prioridad: P1
Impacto: Medio
Esfuerzo: M
Dónde: `app/Http/Controllers/WhatsAppWebhookController.php`
Qué hacer: Cargar mensajes por lote usando `whereIn` y mapear en memoria.
Criterio de terminado (DoD): Una consulta por lote de IDs y métricas de respuesta mejoradas.

## Paginacion/aggregates en cobranzas
Tipo: Performance
Prioridad: P1
Impacto: Medio
Esfuerzo: M
Dónde: `app/Http/Controllers/Api/CobranzaApiController.php`
Qué hacer: Paginar resultados y mover stats a consultas agregadas.
Criterio de terminado (DoD): Endpoints soportan paginación y mantienen respuesta rápida.

## Agregar CI de tests y lint
Tipo: DX
Prioridad: P2
Impacto: Medio
Esfuerzo: M
Dónde: `.github/workflows/`
Qué hacer: Workflow separado para `php artisan test`, `./vendor/bin/pint` y build de assets.
Criterio de terminado (DoD): CI bloquea merge si falla calidad.

## Unificar documentacion de version de Laravel
Tipo: Refactor
Prioridad: P2
Impacto: Bajo
Esfuerzo: S
Dónde: `README.md`, `docs/INVENTARIO_MODULOS.md`
Qué hacer: Alinear version real del framework.
Criterio de terminado (DoD): Documentacion consistente y validada.

## Convertir scripts sueltos a comandos Artisan
Tipo: Refactor
Prioridad: P2
Impacto: Bajo
Esfuerzo: M
Dónde: `repair_*.php`, `fill_payment_supplements.php`, `sync_stock.php`
Qué hacer: Migrar scripts a comandos con validaciones y logs.
Criterio de terminado (DoD): Scripts removidos de la raíz y comandos documentados.
