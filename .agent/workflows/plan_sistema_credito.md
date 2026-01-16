# Plan de Implementación: Sistema de Crédito para Clientes

Este plan detalla las fases para implementar un sistema de crédito robusto que permita a los clientes autorizados realizar compras en la tienda online y gestionar su proceso de alta (documentación y contrato).

## Fase 1: Gestión de Documentación y Expediente de Crédito (Admin)
**Objetivo:** Permitir que los administradores carguen y validen documentos para autorizar créditos.

- [ ] **Base de Datos**: Crear tabla `cliente_documentos` para almacenar rutas de archivos (INE, Comprobante de Domicilio, etc.).
- [ ] **Interfaz Admin**: Agregar sección "Expediente de Crédito" en la edición del cliente.
- [ ] **Subida de Archivos**: Implementar funcionalidad para arrastrar y soltar documentos.
- [ ] **Estatus de Crédito**: Implementar estados: `Sin Crédito`, `En Revisión`, `Autorizado`, `Suspendido`.

## Fase 2: Contrato de Crédito Automatizado (PDF)
**Objetivo:** Generar el contrato legal de apertura de crédito con un solo clic.

- [ ] **Plantilla Blade**: Crear `resources/views/pdf/contrato-credito.blade.php`.
- [ ] **Generador**: Botón en Admin para "Generar Contrato" prellenado con datos del cliente y límite de crédito.
- [ ] **Firma Digital (Opcional)**: Espacio para firma o integración básica.

## Fase 3: Integración en Checkout (Venta Web)
**Objetivo:** Habilitar el crédito como método de pago.

- [ ] **Lógica de Checkout**: Mostrar opción "Pagar con Crédito" solo si `credito_activo` es true y `credito_disponible >= total`.
- [ ] **Procesamiento de Pago**:
    - Crear el Pedido/Venta.
    - Generar automáticamente una "Cuenta por Cobrar" vinculada al cliente.
    - Restar del balance disponible.
- [ ] **Validaciones**: Verificar vencimientos de facturas anteriores antes de permitir nuevo crédito.

## Fase 4: Gestión en Portal de Cliente
**Objetivo:** Transparencia total para el cliente.

- [ ] **Dashboard de Crédito**: Mostrar Límite, Saldo Utilizado y Saldo Disponible.
- [ ] **Historial de Pagos**: Listado de facturas pagadas con crédito y fechas de vencimiento.
- [ ] **Solicitud de Crédito**: Botón para que el cliente suba sus documentos desde el portal si no tiene crédito aún.

---
**¿Cómo lo hacen otros sistemas?**
Sistemas como SAP o Salesforce manejan "Credit Limits" y "Credit Control Areas". Lo ideal es no solo tener un límite, sino también un "Bloqueo por Vencimiento": si el cliente tiene facturas con más de X días de retraso, la opción de crédito se oculta automáticamente aunque tenga saldo disponible.
