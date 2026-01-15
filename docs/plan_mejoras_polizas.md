# Plan de Mejora Continua: Módulo de Pólizas de Servicio

Este documento detalla las fases para convertir el sistema de pólizas de Asistencia Vircom en una herramienta proactiva de alto valor para el cliente.

## 📋 Resumen de Fases

| Fase | Título | Objetivo | Estado |
| :--- | :--- | :--- | :--- |
| **1** | **Optimización de Mantenimiento** | Refinar la generación automática y notificaciones. | 🔄 En Progreso |
| **2** | **Dashboard de Transparencia** | Visibilidad total para el cliente en su portal. | ⏳ Pendiente |
| **3** | **Reporting Automatizado** | Entrega de valor tangible vía reportes PDF mensuales. | ⏳ Pendiente |
| **4** | **Operaciones y Seguridad** | Integración total con Bóveda de Credenciales y SLAs. | ⏳ Pendiente |
| **5** | **Ciclo de Vida y Cobranza** | Automatización de renovaciones y bloqueos por impago. | ⏳ Pendiente |

---

## 🛠️ Detalle de Implementación

### Fase 1: Optimización de Mantenimiento (Proactividad)
*   **Ajuste de Antelación:** Permitir configurar cuántos días antes se genera el ticket (actualmente es inmediato al vencer).
*   **Integración WhatsApp:** Enviar el aviso de mantenimiento programado vía WhatsApp (usando el módulo existente).
*   **Registro de Firma:** Facilitar que el técnico recolecte la firma digital del cliente desde la cita de mantenimiento.

### Fase 2: Dashboard de Transparencia (Portal del Cliente)
*   **Barra de Consumo:** Visualización gráfica de horas/tickets consumidos vs. disponibles.
*   **Inventario Protegido:** Sección donde el cliente ve exactamente qué equipos están cubiertos (Número de serie, ubicación, última vez atendido).
*   **Acceso a Credenciales:** (Opcional) Permitir al cliente ver sus propias contraseñas resguardadas de forma segura.

### Fase 3: Reporting Automatizado (Valor Tangible)
*   **Reporte Mensual PDF:** Generación automática el primer día de cada mes.
*   **KPIs de Servicio:** Tiempo promedio de respuesta (SLA) y cantidad de incidencias resueltas.
*   **Recomendaciones Técnicas:** Sección de observaciones del técnico para mejoras en la infraestructura del cliente.

### Fase 4: Operaciones y Seguridad
*   **Vista de Técnico Priorizada:** Los tickets vinculados a pólizas activas aparecen con un indicador visual de "VIP" o "Contrato".
*   **Exigencia de Horas:** Obligar al técnico a registrar horas trabajadas antes de cerrar un ticket de póliza para asegurar el tracking preciso.

### Fase 5: Ciclo de Vida y Cobranza
*   **Renovación con un Clic:** El sistema envía un enlace de pago/renovación 15 días antes del vencimiento.
*   **Suspensión de Soporte:** Si la cuenta por cobrar de la póliza está vencida, el sistema bloquea la creación de tickets en el portal hasta que se registre el pago.
