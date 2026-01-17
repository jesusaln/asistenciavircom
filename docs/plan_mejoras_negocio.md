# Plan de Mejoras Estratégicas - Asistencia Vircom 🚀

Este documento detalla el plan de implementación para las mejoras de lógica de negocio sugeridas, divididas en fases para asegurar una integración coherente y escalable.

---

## 📅 Fase 1: Control de Ingresos, Ventas y Cartera (Inmediato)
*Enfocada en detener fugas de dinero, automatizar la facturación de servicios y dar transparencia al cliente.*

### 1.1 Gestión de Refacciones y Servicios en Citas
- **Objetivo**: Registrar qué materiales y servicios adicionales se usan en cada visita.
- **Acciones**:
    - Interfaz en **"Mi Agenda"** para que el técnico añada productos dal catálogo (refacciones) y servicios (horas extra).
    - Lógica de descuento de stock en tiempo real al completar la cita.
    - **Validación**: El sistema calcula automáticamente si el servicio entra en póliza o es un cargo extra.

### 1.2 Integración con Ventas y Cuentas por Cobrar (CxC)
- **Objetivo**: Que cada servicio "cobrable" genere su flujo financiero- [x] **Flujo Cita -> Venta -> CxC (Backend):**
    - [x] Modificación de `CitaController` para procesar "cierre" de cita con cargos.
    - [x] Generación automática de `Venta` al completar cita.
    - [x] Creación de `CuentasPorCobrar` vinculada a la venta.
- [x] **Interfaz de Técnico (Frontend):**
    - [x] Integrar buscador de productos/servicios en "Mi Agenda" (`Edit.vue`).
    - [x] Permitir agregar refacciones y mano de obra con cantidades y precios.
- [x] **Portal de Cliente (Visualización):**
    - [x] Mostrar historial de servicios con desglose de cargos.
    - [x] Botón de "Pagar Ahora" (integración futura con pasarela).
- [ ] **Notificaciones:**
    - [ ] Enviar resumen de visita y cargos por WhatsApp/Email al cerrar la cita.

### 1.3 Panel de Transparencia para el Cliente (Integrado en Portal)
- **Objetivo**: Que el cliente reciba y vea sus cargos de inmediato.
- **Acciones**:
    - [x] **Integración con Portal de Clientes**: Se decidió utilizar el portal autenticado existente para mayor seguridad y centralización.
    - [x] **Historial de Ventas**: Visualización completa de historial de compras y servicios con paginación.
    - [x] **Descarga de PDF**: Posibilidad de descargar facturas y notas de venta directamente desde el portal.
    - [x] **Pagos Pendientes**: Visualización clara de saldos (Cuentas por Cobrar).

### 💎 Mejoras Sugeridas para Fase 1 (Plus):
- [x] **Alertas de Cobranza (Cliente)**: Modal persistente de deuda vencida al ingresar al portal y botones de pago online inmediatos.
- **Alertas de Cobranza (Admin)**: Notificación automática al administrador cuando una venta generada por una cita no sea pagada en X días.
- **Pre-autorización**: Que el técnico pueda enviar una "Cotización Rápida" desde la cita para que el cliente la autorice antes de instalar refacciones caras.

---

## 📂 Fase 2: Profesionalización y Documentación
*Enfocada en la formalidad de la empresa y automatización administrativa.*

### 2.1 Hojas de Servicio Técnicas en PDF
- **Objetivo**: Entregar un comprobante profesional con evidencias visuales.
- **Acciones**:
    - Plantilla PDF con: Reporte técnico, fotos de evidencias (antes/después), materiales usados y las dos firmas.

### 2.2 Portal del Cliente Full (Zonas de Usuario)
- **Objetivo**: Autoservicio completo para el cliente corporativo.
- **Acciones**:
    - Dashboard de pólizas: "Horas usadas vs Incluidas", "Visitas restantes".
    - Repositorio histórico de todas las facturas y hojas de servicio.

---

## 🤖 Fase 3: Inteligencia y Control Geográfico
*Enfocada en optimización avanzada y supervisión.*

### 3.1 Mantenimiento Preventivo Proactivo
- **Acciones**: Cron Job que genera citas de mantenimiento 15 días antes del vencimiento programado en la póliza.

### 3.2 Auditoría GPS (Check-in/Check-out)
- **Acciones**: Validar que el inicio y fin del servicio coincidan geográficamente con la ubicación del cliente.

---

## 📈 Impacto Esperado
1. **Financiero**: Incremento de facturación por materiales y horas extras no detectadas previamente.
2. **Operativo**: Menor tiempo dedicado a programar mantenimientos manuales.
3. **Lealtad**: Mejora drástica en la percepción de valor del cliente gracias al Portal y los PDFs profesionales.
