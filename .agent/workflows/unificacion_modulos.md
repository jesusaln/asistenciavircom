---
description: Historial y plan de unificación de módulos (Tickets, Citas, Ventas)
---

# Plan de Unificación de Módulos (Service Cycle)

Este documento detalla las fases para integrar los módulos de Tickets, Citas y Ventas en un flujo de trabajo continuo y eficiente.

## 🚀 Fase 1: Enlace Ticket -> Cita
**Objetivo:** Permitir que el personal de soporte agende visitas técnicas directamente desde un ticket abierto.

- [x] **Base de Datos:**
    - [x] Crear migración para añadir `ticket_id` a la tabla `citas`.
- [x] **Modelos:**
    - [x] Añadir relación `ticket()` en el modelo `Cita`.
    - [x] Añadir relación `citas()` en el modelo `Ticket`.
- [x] **Backend:**
    - [x] Modificar `CitaController@create` para aceptar `ticket_id` y pre-cargar datos del cliente.
    - [x] Modificar `CitaController@store` para asociar el `ticket_id`.
- [x] **Frontend:**
    - [x] Añadir botón "Agendar Cita" en la vista de detalle de Ticket.
    - [x] Adaptar el formulario de creación de Cita para mostrar que viene de un Ticket.

## 🛠️ Fase 2: Enlace Cita -> Venta
**Objetivo:** Convertir el trabajo realizado en campo directamente en una nota de venta o factura.

- [x] **Base de Datos:**
    - [x] Añadir `cita_id` a la tabla `ventas`.
- [x] **Modelos:**
    - [x] Añadir relación `cita()` en el modelo `Venta`.
    - [x] Añadir relación `venta()` en el modelo `Cita`.
- [x] **Backend:**
    - [x] Modificar `VentaQueryService@getCreateData` para precargar datos de la cita.
    - [x] Modificar `VentaCreationService@createVenta` para guardar `cita_id`.
- [x] **Frontend:**
    - [x] Añadir botón "Generar Cobro" en la vista de detalle de Cita.
    - [x] Modificar formulario de creación de Venta para cargar items desde la Cita automáticamente.

## 📊 Fase 3: Vista 360° del Cliente
**Objetivo:** Centralizar toda la información del cliente en un solo lugar.

- [x] **Backend:**
    - [x] Modificar `ClienteController@show` para incluir tickets, citas y pólizas del cliente.
- [x] **Frontend:**
    - [x] Rediseñar la vista de detalle de Cliente para incluir secciones de:
        - Tickets (Historial de soporte).
        - Citas (Servicios en campo).
        - Ventas (Historial financiero).
        - Pólizas (Contratos vigentes).

## 🤖 Fase 4: Automatización y Pólizas
**Objetivo:** Reducir la carga administrativa mediante triggers automáticos.

- [x] **Lógica:**
    - [x] Al vencer un periodo de mantenimiento en una Póliza, generar Ticket y Cita automáticamente.
    - [x] Notificaciones automáticas al cliente sobre el estado de su servicio unificado.
- [x] **Comandos:**
    - [x] Creación de `app:process-poliza-maintenance` para procesamiento diario.

---
*Estado actual: Todas las fases del ciclo de servicio unificado han sido completadas.*
