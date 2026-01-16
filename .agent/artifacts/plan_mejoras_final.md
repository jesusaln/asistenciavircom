# Plan de Mejoras Tickets y Reportes

## Estado Actual
- **Fase 1 Completada**: Reportes PDF básicos, Filtros, Botones de acción.
- **Fase 2 Completada**:
    - Campos `servicio_inicio_at` y `servicio_fin_at` en BD y Modelo.
    - Lógica de registro de horas (backend/frontend) con cálculo automático en Admin.
    - Modal de advertencia para prioridad "Urgente" en Portal Clientes.
    - Permisos extendidos de borrado (Admin + SuperAdmin).

## Próximos Pasos (Pendientes)

### Fase 3: Mejoras en Reportes y Portal Cliente
1.  **Actualizar PDFs**: Mostrar las columnas "Inicio" y "Fin" en los reportes generados.
2.  **Portal Cliente - Dashboard**:
    - Mostrar detalles de tiempo (inicio/fin/duración) en la vista de tickets del cliente.
    - Agregar resumen visual de consumo de póliza (barra de progreso).
    - Botón para que el cliente descargue su propio reporte de consumo.

### Fase 4: Notificaciones y Automatización (Futuro)
- Enviar correo al cliente cuando se cierra un ticket con el resumen de horas.
- Alerta al admin cuando una póliza llega al 80% o 100% de consumo.
