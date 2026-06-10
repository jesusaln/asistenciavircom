# Plan quick wins 7 dias

## Objetivo
Aplicar cambios de bajo riesgo con alto impacto (P0/P1) sin tocar migraciones/DB.

## Dia 1
- Auditar rutas API y clasificar publicas vs privadas (`routes/api.php`).
- Definir lista de orígenes CORS por entorno.

## Dia 2
- Proteger rutas API sensibles con `auth:sanctum` y roles.
- Agregar rate limit a endpoints públicos (configurable por entorno).

## Dia 3
- Endurecer webhook WhatsApp: firma obligatoria y rechazo si falta.
- Reducir logging de payloads y headers a metadatos.

## Dia 4
- Ajustar endpoint de prueba WhatsApp: mover a rutas privadas o agregar auth.
- Revisar exposición de secretos en frontend y enmascarar valores.

## Dia 5
- Optimizar webhook para evitar N+1 con batch de mensajes.
- Revisar endpoints sin paginación y agregar paginado donde aplique.

## Dia 6
- Crear workflow de CI para tests + lint (sin deploy).
- Documentar ejecución local de tests/lint en `docs/`.

## Dia 7
- Revisión final de cambios y actualización de backlog.
- Preparar PR con commits pequeños y `docs/CHANGELOG-agente.md`.
