---
description: Plan de Integración de RustDesk Management en Asistencia Vircom
---

# Plan de Integración: RustDesk + Asistencia Vircom

Este plan detalla la integración de capacidades de soporte remoto profesional dentro de la plataforma de asistencia, permitiendo gestionar clientes y conexiones de forma centralizada.

## Fase 1: Preparación de Base de Datos y Modelos ✅
- [x] Agregar campos `rustdesk_id` y `rustdesk_alias` a la tabla `users`.
- [x] Actualizar el modelo `User` en Laravel para permitir asignación masiva.
- [x] Agregar configuración global de RustDesk (ID Server, Key, API URL) a `empresa_configuracion`.
- [x] Crear script de inicialización para asignar alias predeterminados a todos los usuarios.
- [x] Implementar interfaz de edición de campos RustDesk en el expediente del empleado.

## Fase 2: Puente de Comunicación (API) ✅
- [x] Implementar `App\Services\RustDeskService` para interactuar con la API.
- [x] Configurar autenticación con el servidor RustDesk API.
- [x] Implementar métodos para:
    - Verificar estado del equipo (online/offline).
    - Obtener lista de dispositivos vinculados.
    - Sincronizar alias desde el portal de asistencia.
- [x] **Sincronización de Equipos**: Capacidad de listar qué equipos de mis clientes están "Online" directamente desde Asistencia Vircom.
- [x] **Login Unificado**: Permitir que los técnicos usen su cuenta de Asistencia Vircom para loguearse en la app de RustDesk.

### Entregables Técnicos Fase 2
- [ ] Crear `config/rustdesk.php` con:
    - `api_url`
    - `api_token` o credenciales de servicio
    - `id_server`
    - `key`
    - `timeout` y `retry`
- [ ] Implementar `App\Services\RustDeskService` con cliente HTTP desacoplado.
- [ ] Implementar `App\Contracts\RustDeskClientInterface` para facilitar testing/mocking.
- [ ] Agregar manejo de errores estandarizado:
    - Timeouts
    - `401/403` (credenciales inválidas)
    - `429` (rate limit)
    - `5xx` (fallback/reintento)
- [ ] Agregar logging estructurado en canal dedicado (`rustdesk`).
- [ ] Agregar job programado para sincronización periódica de estados (cada 1-5 min según carga).

### Criterios de Aceptación Fase 2
- [ ] Desde Asistencia Vircom se puede consultar el estado de un equipo por `rustdesk_id`.
- [ ] La lista de dispositivos vinculados responde en menos de 3 segundos para carga normal.
- [ ] Alias actualizado en Asistencia se refleja en RustDesk sin intervención manual.
- [ ] Existen pruebas unitarias para servicio y pruebas de integración para endpoints críticos.
- [ ] Si RustDesk no está disponible, la UI muestra estado degradado sin bloquear el sistema.

### Checklist Laravel Fase 2 (Implementación Inmediata)
1. Base de configuración
- [ ] Crear archivo `config/rustdesk.php`.
- [ ] Mapear llaves desde `.env`:
    - `RUSTDESK_API_URL`
    - `RUSTDESK_API_TOKEN`
    - `RUSTDESK_SERVER_ADDRESS`
    - `RUSTDESK_RELAY_SERVER`
    - `RUSTDESK_PUBLIC_KEY`
    - `RUSTDESK_TIMEOUT`
    - `RUSTDESK_RETRY_TIMES`
    - `RUSTDESK_RETRY_SLEEP_MS`
- [ ] Agregar ejemplo en `.env.example` con valores placeholder.

2. Contrato + servicio
- [ ] Crear contrato [app/Contracts/RustDeskClientInterface.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/app/Contracts/RustDeskClientInterface.php) con métodos:
    - `getDeviceStatus(string $rustdeskId): array`
    - `listDevices(?string $search = null): array`
    - `syncAlias(string $rustdeskId, string $alias): bool`
- [ ] Crear servicio [app/Services/RustDeskService.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/app/Services/RustDeskService.php) que implemente el contrato.
- [ ] Usar `Http::baseUrl(...)->timeout(...)->retry(...)` con headers de autenticación.
- [ ] Homologar respuesta en formato interno:
    - `ok` (bool)
    - `data` (array|null)
    - `error` (string|null)
    - `status` (int|null)

3. Provider e inyección
- [ ] Registrar binding en [app/Providers/AppServiceProvider.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/app/Providers/AppServiceProvider.php):
    - `RustDeskClientInterface::class => RustDeskService::class`
- [ ] Definir canal de log `rustdesk` en [config/logging.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/config/logging.php) (daily, nivel `info/error`).

4. Endpoint API interno para consumo UI
- [ ] Crear controlador [app/Http/Controllers/Api/RustDeskController.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/app/Http/Controllers/Api/RustDeskController.php):
    - `status(string $rustdeskId)`
    - `devices(Request $request)`
    - `syncAlias(Request $request)`
- [ ] Agregar rutas en [routes/api.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/routes/api.php) bajo prefijo `rustdesk` y middleware `auth:sanctum`.
- [ ] Validar permisos (ej. `remote_support.start` / `remote_support.audit`) antes de responder.

5. Sincronización automática (job + scheduler)
- [ ] Crear job [app/Jobs/SyncRustDeskDeviceStatusJob.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/app/Jobs/SyncRustDeskDeviceStatusJob.php).
- [ ] Implementar barrido por usuarios con `rustdesk_id` no nulo.
- [ ] Persistir estado online/offline en cache o tabla técnica (según diseño final).
- [ ] Programar ejecución en [routes/console.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/routes/console.php) cada 1-5 minutos.

6. Pruebas
- [ ] Crear unit tests del servicio en [tests/Unit/Services/RustDeskServiceTest.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/tests/Unit/Services/RustDeskServiceTest.php) usando `Http::fake()`.
- [ ] Crear feature tests API en [tests/Feature/Api/RustDeskApiTest.php](/home/vircom/.gemini/antigravity/scratch/asistenciavircom/tests/Feature/Api/RustDeskApiTest.php).
- [ ] Casos mínimos:
    - `200` en consulta de estado.
    - `401/403` por token inválido.
    - `429` y `5xx` con retry/fallback.
    - fallo de red con respuesta degradada controlada.

7. Comandos sugeridos (scaffolding)
```bash
php artisan make:controller Api/RustDeskController
php artisan make:job SyncRustDeskDeviceStatusJob
php artisan make:test --unit Services/RustDeskServiceTest
php artisan make:test Feature/Api/RustDeskApiTest
```

8. Definición de terminado (DoD) Fase 2
- [ ] `php artisan test --filter=RustDesk` en verde.
- [ ] Endpoints de RustDesk documentados en `docs/` (request/response y códigos de error).
- [ ] Log de integración visible en canal `rustdesk` sin exponer secretos.
- [ ] Feature flag opcional para habilitar RustDesk por empresa/cliente piloto.

## Fase 3: Soporte "Un Clic" (UI/UX)
- [x] **Botón de Soporte Rápido**: En cada cliente con ID registrado, añadir un botón "Iniciar Soporte Remoto".
- [x] **Protocolo RustDesk**: Al dar clic, abrir automáticamente la aplicación local de RustDesk conectándose al ID del cliente mediante el esquema `rustdesk://`.
- [x] **Portal del Cliente**: Añadir un botón para que el cliente descargue el ejecutable pre-configurado de Vircom.

### Entregables Técnicos Fase 3
- [ ] Agregar componente reusable `BotonSoporteRustDesk` en vistas de cliente.
- [ ] Validar que exista `rustdesk_id` antes de mostrar acciones de conexión.
- [ ] Generar enlace con esquema seguro:
    - `rustdesk://connect?id={rustdesk_id}`
    - opción para incluir contraseña temporal (si política lo permite)
- [ ] Incluir fallback UX:
    - Copiar ID al portapapeles si el protocolo no abre.
    - Mostrar instrucciones cortas para abrir RustDesk manualmente.
- [ ] Publicar sección "Descargar RustDesk Vircom" en portal del cliente con versión controlada.
- [ ] Registrar evento de intento de conexión (click tracking) para trazabilidad.

### Criterios de Aceptación Fase 3
- [ ] El técnico inicia soporte con un clic desde expediente del cliente.
- [ ] Si la app local no está instalada, se muestra guía de instalación sin error técnico.
- [ ] La acción no aparece para usuarios sin permisos de soporte remoto.
- [ ] Compatible en navegadores de escritorio principales usados por soporte.

## Fase 4: Auditoría y Facturación
- [x] **Registro de Sesiones**: Guardar historial de cuándo y quién se conectó a qué equipo.
- [x] **Cómputo de Horas**: Si el soporte es bajo contrato, sumar el tiempo de conexión remota a la bitácora de servicios automáticamente.

### Entregables Técnicos Fase 4
- [x] Crear tabla `remote_support_sessions` con:
    - `id`
    - `user_id` (técnico)
    - `cliente_id`
    - `rustdesk_id`
    - `started_at`
    - `ended_at`
    - `duration_minutes`
    - `status` (started/completed/failed)
    - `source` (manual/api/webhook)
- [x] Exponer servicio para iniciar/cerrar sesión de soporte.
- [x] Integrar con módulo de contratos para afectar bolsa de horas.
- [ ] Definir reglas de redondeo de tiempo (ej. bloques de 5 o 15 min).
- [ ] Generar reporte mensual por cliente/técnico con exportación CSV/XLSX.

### Criterios de Aceptación Fase 4
- [x] Toda sesión queda registrada con técnico, cliente y duración.
- [x] Contratos con horas incluidas descuentan consumo automáticamente.
- [ ] Sesiones fallidas quedan marcadas y no descuentan horas facturables.
- [ ] Existe reporte auditable para facturación y revisión interna.

## Fase 5: Seguridad, Operación y Despliegue
- [ ] **Seguridad de Credenciales**:
    - Guardar secretos en `.env`/secret manager.
    - Rotación periódica de token/API key.
    - Nunca exponer llaves en frontend.
- [ ] **Control de Accesos**:
    - Permiso granular `remote_support.start`, `remote_support.audit`, `remote_support.billing`.
    - Restricción por rol (técnico, supervisor, admin).
- [ ] **Observabilidad**:
    - Métricas de disponibilidad RustDesk API.
    - Alarmas por tasa de error y tiempo de respuesta.
- [ ] **Estrategia de Release**:
    - Feature flags por módulo (API, botón, auditoría).
    - Habilitación progresiva por grupo piloto.
- [ ] **Plan de Rollback**:
    - Desactivar flag sin rollback de DB.
    - Mantener operación manual como contingencia.

### Criterios de Aceptación Fase 5
- [ ] Auditoría de seguridad aprobada para credenciales y permisos.
- [ ] Monitoreo activo con alertas en canal operativo.
- [ ] Despliegue progresivo completado sin incidentes críticos.

## Backlog Recomendado (Post-MVP)
- [ ] Webhooks de RustDesk para estado en tiempo real (sin polling frecuente).
- [ ] Asignación automática técnico-cliente según reglas de disponibilidad.
- [ ] Dashboard de SLA: tiempo de primera respuesta, duración promedio, reincidencias.
- [ ] Grabación o evidencia de sesión (si política legal y de privacidad lo permite).
- [ ] Integración con sistema de tickets para abrir/cerrar incidencias automáticamente.

## Orden Sugerido de Ejecución
1. Completar Fase 2 (servicio API + sincronización básica).
2. Liberar Fase 3 para técnicos internos (grupo piloto).
3. Activar Fase 4 solo cuando el registro de sesiones sea confiable.
4. Endurecer Fase 5 antes de despliegue total a todos los clientes.

---
**Nota Técnica**: La API actual de RustDesk está operativa en el puerto 21114 del VPS. El acceso se realizará de forma segura mediante comunicación interna entre contenedores.

**Supuestos Operativos**:
- El servidor RustDesk Management mantiene endpoints estables y autenticables por token.
- El entorno de producción cuenta con cola de jobs y scheduler activos.
- Las políticas de privacidad del cliente permiten trazabilidad de sesiones de soporte.
