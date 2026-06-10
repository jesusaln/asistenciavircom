# Plan de Mejoras - Módulo de Pólizas de Servicio

> **Fecha de Creación:** 19 de Enero 2026  
> **Última Actualización:** 19 de Enero 2026  
> **Estado:** Fase 4 Completada ✅ (Proyecto Finalizado)  
> **Total de Mejoras:** 20  

---

## 📋 Resumen Ejecutivo

Este documento presenta un plan de mejoras estructurado en 4 fases para optimizar el módulo de Pólizas de Servicio. Las fases están ordenadas por prioridad: primero se corrigen problemas críticos de lógica de negocio, luego se mejora la operación diaria, después la experiencia del cliente, y finalmente optimizaciones técnicas.

---

## ✅ FASE 1: Correcciones Críticas de Lógica de Negocio [COMPLETADA]
**Prioridad:** Alta | **Estimación:** 2-3 días | **Impacto:** Evita pérdida de ingresos y errores contables

### 1.1 Validación de Póliza Activa al Crear Ticket
**Problema:** El cliente puede crear tickets desde el portal aunque su póliza esté vencida, cancelada o pendiente de pago.

**Solución:**
- Agregar middleware/validación en `PortalTicketController@store`
- Mostrar mensaje claro: "Su póliza no está activa. Por favor, regularice su situación para solicitar soporte."
- Opción: Permitir crear ticket pero marcarlo como "pendiente de pago" automáticamente

**Archivos a modificar:**
- `app/Http/Controllers/Portal/PortalTicketController.php`
- `resources/js/Pages/Portal/Tickets/Create.vue`

---

### 1.2 Idempotencia en Consumo de Folios
**Problema:** Si el evento `saved` se dispara dos veces por error de red o retry, el mismo folio se descuenta dos veces.

**Solución:**
- Agregar campo `consumo_registrado_at` en tickets
- Verificar antes de descontar: si ya tiene fecha, no volver a descontar
- Usar transacción con lock para evitar race conditions

**Archivos a modificar:**
- `app/Models/Ticket.php` (método `registrarConsumoUnitarioEnPoliza`)
- Migración: agregar columna `consumo_registrado_at`

---

### 1.3 Cobro por Cada Unidad Excedente
**Problema:** Al exceder el límite, solo se genera UN cobro. Si el cliente usa 5 tickets extra, solo se cobra el primero.

**Solución:**
- Modificar `generarCobroExcedente()` para que se llame CADA vez que se excede
- Agregar lógica de acumulación: guardar excedentes pendientes y cobrar al final del período
- Alternativa: Cobrar en tiempo real cada excedente

**Archivos a modificar:**
- `app/Models/PolizaServicio.php` (método `verificarAlertasLimite`)
- `app/Models/PolizaServicio.php` (método `generarCobroExcedente`)

---

### 1.4 Periodo de Gracia para Pagos Atrasados
**Problema:** Si el cliente paga 1 día después del vencimiento, la póliza ya está marcada como "vencida" y pierde acceso.

**Solución:**
- Agregar campo `dias_gracia` en `polizas_servicio` (default: 5 días)
- Modificar la lógica de estado: marcar como "vencida" solo después de la gracia
- Nuevo estado intermedio: `vencida_en_gracia`

**Archivos a modificar:**
- Migración: agregar `dias_gracia`
- `app/Console/Commands/PolizaCheckExpirations.php`
- `app/Models/PolizaServicio.php`

---

### 1.5 Validación de Horas Antes de Cerrar Ticket
**Problema:** Un técnico puede registrar 100 horas en un ticket sin validación de si la póliza tiene capacidad.

**Solución:**
- Agregar validación en `TicketController@close`
- Si excede horas disponibles, mostrar advertencia y requerir confirmación
- Auto-calcular costo extra y mostrarlo antes de confirmar

**Archivos a modificar:**
- `app/Http/Controllers/Admin/TicketController.php`
- `resources/js/Pages/Soporte/Tickets/Show.vue` (modal de cierre)

---

## ✅ FASE 2: Mejoras Funcionales y Operativas [COMPLETADA]
**Prioridad:** Media-Alta | **Estimación:** 3-4 días | **Impacto:** Mejora flujo de trabajo diario

### 2.1 Historial de Consumos en Portal
**Problema:** El cliente solo ve el consumo del mes actual, no puede consultar meses anteriores.

**Solución:**
- Crear vista `Portal/Polizas/Historial.vue`
- Endpoint API: `GET /portal/polizas/{id}/historial?mes=2026-01`
- Tabla con: Fecha, Tipo (Ticket/Visita/Hora), Detalle, Ahorro

**Archivos a crear/modificar:**
- `resources/js/Pages/Portal/Polizas/Historial.vue` (nuevo)
- `app/Http/Controllers/Portal/PortalPolizaController.php`
- `routes/web.php`

---

### 2.2 Notificación al Descontar Folio
**Problema:** El cliente no recibe notificación cuando se usa uno de sus servicios incluidos.

**Solución:**
- Crear `PolizaConsumoNotification`
- Enviar email/WhatsApp: "Se utilizó 1 de sus 5 tickets mensuales. Quedan 4."
- Configuración: permitir al cliente activar/desactivar estas alertas

**Archivos a crear/modificar:**
- `app/Notifications/PolizaConsumoNotification.php` (nuevo)
- `app/Models/PolizaServicio.php` (método `registrarTicketSoporte`)
- `resources/views/emails/poliza_consumo.blade.php` (nuevo)

---

### 2.3 Opción de Pausar Póliza
**Problema:** Si un cliente cierra temporalmente su negocio, no puede pausar el contrato.

**Solución:**
- Nuevo estado: `pausada`
- Agregar campos: `pausada_desde`, `pausada_hasta`, `motivo_pausa`
- Durante pausa: no generar cobros, no permitir tickets, extender fecha_fin equivalente

**Archivos a modificar:**
- Migración: agregar campos de pausa
- `app/Models/PolizaServicio.php`
- `app/Http/Controllers/PolizaServicioController.php` (acción pausar/reanudar)
- Vista admin para gestionar pausas

---

### 2.4 Cálculo de Ahorro con Precios Reales
**Problema:** El portal usa precio fijo de $650 para calcular ahorro. No refleja precios reales del catálogo.

**Solución:**
- Obtener precio real del servicio desde `Servicio` o `PlanPoliza`
- Si no existe precio específico, usar campo `costo_hora_excedente` de la póliza
- Mostrar desglose: "Ahorro basado en tarifa de $X por hora"

**Archivos a modificar:**
- `resources/js/Pages/Portal/Polizas/Show.vue` (función `ahorroMensual`)
- `app/Http/Controllers/Portal/PortalPolizaController.php` (enviar precios)

---

### 2.5 Reporte PDF de Consumo Mensual
**Problema:** El cliente no puede descargar un resumen de lo que usó en el mes.

**Solución:**
- Crear `PolizaReporteMensualPDF`
- Contenido: Tickets atendidos, horas usadas, visitas realizadas, ahorro total
- Botón en portal: "Descargar Reporte del Mes"

**Archivos a crear/modificar:**
- `resources/views/pdf/poliza-reporte-mensual.blade.php` (ya existe, verificar contenido)
- `app/Http/Controllers/PolizaServicioPDFController.php`
- `resources/js/Pages/Portal/Polizas/Show.vue` (agregar botón)

---

## ✅ FASE 3: Optimizaciones Técnicas [COMPLETADA]
**Prioridad:** Media | **Estimación:** 2-3 días | **Impacto:** Mejora performance y mantenibilidad

### 3.1 Optimizar N+1 Queries en Conteo de Tickets
**Problema:** El accessor `tickets_mes_actual_count` ejecuta una query por cada póliza listada.

**Solución:**
- Usar `withCount()` con constraint de fecha en el controlador
- Ejemplo: `->withCount(['tickets as tickets_mes_actual_count' => fn($q) => $q->whereMonth('created_at', now()->month)])`
- Eliminar lógica de caché estática en el accessor

**Archivos a modificar:**
- `app/Http/Controllers/PolizaServicioController.php`
- `app/Http/Controllers/Portal/PortalPolizaController.php`
- `app/Models/PolizaServicio.php`

---

### 3.2 Costos de Excedente Configurables
**Problema:** Los costos por ticket extra ($150), hora extra, etc. están hardcodeados.

**Solución:**
- Agregar campos en `plan_polizas`: `costo_ticket_extra`, `costo_visita_extra`
- Heredar estos valores a la póliza al contratarse
- Usar valores dinámicos en `generarCobroExcedente()`

**Archivos a modificar:**
- Migración: agregar campos a `plan_polizas` y `polizas_servicio`
- `app/Models/PolizaServicio.php`
- `app/Models/PlanPoliza.php`

---

### 3.3 Índices de Base de Datos
**Problema:** Consultas lentas al buscar tickets por póliza o consumos por fecha.

**Solución:**
- Agregar índice compuesto: `tickets(poliza_id, created_at)`
- Agregar índice: `poliza_consumos(poliza_id, fecha_consumo)`
- Agregar índice: `polizas_servicio(cliente_id, estado)`

**Archivos a crear:**
- Migración con índices

---

### 3.4 Auto-corrección de Reset Mensual
**Problema:** Si el cron falla el día del reset, los consumos no se limpian.

**Solución:**
- Agregar lógica en `PolizaServicio::boot()` o accessor
- Al cargar póliza, verificar si `ultimo_reset_consumo_at` es de un mes anterior
- Si es así, ejecutar reset automáticamente

**Archivos a modificar:**
- `app/Models/PolizaServicio.php` (método boot o accessor)

---

### 3.5 Logs Estructurados de Auditoría
**Problema:** Los logs actuales son texto plano, difíciles de consultar y auditar.

**Solución:**
- Usar tabla `poliza_audit_logs` con campos JSON
- Registrar: acción, usuario, datos_antes, datos_después, ip, timestamp
- Interfaz admin para consultar auditoría

**Archivos a crear/modificar:**
- Migración: crear `poliza_audit_logs`
- `app/Models/PolizaAuditLog.php` (nuevo)
- `app/Models/PolizaServicio.php` (registrar eventos)

---

## ✅ FASE 4: Mejoras de Experiencia del Cliente [COMPLETADA]
**Prioridad:** Baja-Media | **Estimación:** 3-4 días | **Impacto:** Diferenciación competitiva

### 4.1 Gráfica de Histórico de Consumo
**Problema:** El cliente no puede ver tendencias de uso a lo largo del tiempo.

**Solución:**
- Agregar Chart.js o similar al portal
- Mostrar gráfica de líneas: últimos 6 meses de consumo
- Comparativa: límite vs usado por mes

**Archivos a crear/modificar:**
- `resources/js/Pages/Portal/Polizas/Show.vue`
- `app/Http/Controllers/Portal/PortalPolizaController.php` (endpoint datos históricos)

---

### 4.2 Alertas Visuales de Límite
**Problema:** No hay advertencia visual cuando el cliente está cerca de su límite.

**Solución:**
- Banner en portal cuando consumo >= 80%: "⚠️ Has usado 4 de 5 tickets este mes"
- Color amarillo al 80%, rojo al 100%
- Sugerencia de upgrade de plan

**Archivos a modificar:**
- `resources/js/Pages/Portal/Polizas/Show.vue`
- `resources/js/Pages/Portal/Layout/ClientLayout.vue` (banner global)

---

### 4.3 Botón de Renovación Anticipada
**Problema:** El cliente no puede renovar proactivamente antes de que venza.

**Solución:**
- Botón "Renovar Ahora" visible 30 días antes del vencimiento
- Redirigir al checkout con descuento por fidelidad (opcional)
- Extender fecha_fin automáticamente al pagar

**Archivos a crear/modificar:**
- `resources/js/Pages/Portal/Polizas/Show.vue`
- `app/Http/Controllers/PolizaPaymentController.php` (método renovar)
- `routes/web.php`

---

### 4.4 Detalle de Tickets Consumidos
**Problema:** El cliente ve "3/5 tickets usados" pero no sabe cuáles fueron.

**Solución:**
- Agregar sección colapsable: "Ver tickets de este mes"
- Listar: Folio, Fecha, Título, Estado
- Link para ver detalle de cada ticket

**Archivos a modificar:**
- `resources/js/Pages/Portal/Polizas/Show.vue`
- `app/Http/Controllers/Portal/PortalPolizaController.php` (incluir tickets del mes)

---

### 4.5 Exportar a Calendario
**Problema:** Las fechas importantes no se pueden agregar al calendario del cliente.

**Solución:**
- Botón "Agregar a Calendario" para:
  - Próximo cobro
  - Fecha de vencimiento
  - Mantenimientos programados
- Generar archivo .ics compatible con Google Calendar/Outlook

**Archivos a crear/modificar:**
- `app/Http/Controllers/Portal/PortalPolizaController.php` (método exportCalendar)
- `resources/js/Pages/Portal/Polizas/Show.vue`
- Helper para generar .ics

---

## 📊 Matriz de Priorización

| Fase | Mejoras | Esfuerzo | Impacto | Recomendación |
|------|---------|----------|---------|---------------|
| 1 | 1.1 - 1.5 | Alto | Crítico | **Implementar inmediatamente** |
| 2 | 2.1 - 2.5 | Medio | Alto | Implementar en siguiente sprint |
| 3 | 3.1 - 3.5 | Medio | Medio | Implementar cuando haya capacidad |
| 4 | 4.1 - 4.5 | Medio | Bajo-Medio | Implementar como diferenciador |

---

## ✅ Siguiente Paso Recomendado

Comenzar con **Fase 1.1: Validación de Póliza Activa** ya que es la corrección más urgente para evitar que clientes sin póliza activa usen servicios sin costo.

---

*Documento generado para AsistenciaVircom - Sistema de Gestión de Servicios*
