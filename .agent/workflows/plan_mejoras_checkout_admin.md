---
description: Plan de implementación de mejoras post-lanzamiento para el Checkout y Administración (Correos, PDF y Comprobantes).
---

# Plan de Mejoras: Checkout y Administración de Pedidos

Este documento detalla los pasos técnicos para implementar las funcionalidades solicitadas tras la validación exitosa del Checkout.

## FASE 1: Notificaciones por Correo Electrónico

### 1. Correo de Confirmación al Cliente (Mejora)
Actualmente existe `PedidoCreadoNotification`, pero requiere ajustes:
- [ ] **Validar Datos Bancarios:** Asegurar que los datos mostrados en el correo (Banco, CLABE, Cuenta) sean dinámicos o coincidan exactamente con los de la vista de checkout.
- [ ] **Diseño:** Mejorar el `HtmlString` para asegurar que se vea profesional en móviles.

### 2. Notificación al Administrador (Nuevo)
Crear una alerta para que el dueño/admin se entere inmediatamente de una venta nueva.
- [ ] **Crear Notificación:** `php artisan make:notification NuevoPedidoAdmin`
- [ ] **Contenido:**
    - Asunto: "💰 Nueva Venta Web: #PO-XXXX ($Monto)"
    - Cuerpo: Resumen rápido (Cliente, Items, Total, Método de Pago).
    - Acción: Botón "Ver Pedido en Panel" (Lleva a `/admin/pedidos-online/{id}`).
- [ ] **Trigger:** Disparar esta notificación en `CheckoutController` (dentro de la transacción o evento `created`) hacia el email del administrador (configurado en `.env` o base de datos).

---

## FASE 2: Generación de Recibo PDF

Permitir al cliente y al admin descargar un comprobante formal del pedido.

### 1. Backend (Laravel)
- [ ] **Controlador:** Agregar método `downloadPdf($id)` en `PedidoOnlineController`.
- [ ] **Librería PDF:** Verificar si ya existe `barryvdh/laravel-dompdf`. Si no, instalarla o usar una vista Blade simple de impresión (`window.print()`).
    - *Recomendación:* Usar librería backend para asegurar formato consistente.
- [ ] **Vista Blade:** Crear `resources/views/pdfs/pedido_online.blade.php` con:
    - Logotipo y Datos de la Empresa.
    - Datos del Cliente y Envío.
    - Tabla de productos (Items).
    - Totales y desglose de impuestos.

### 3. Frontend (Vue)
- [ ] **Botón en "Pedido Confirmado":** Agregar botón "📄 Descargar Recibo" que apunte a la ruta del PDF.
- [ ] **Botón en Admin:** Agregar el mismo botón en `Admin/PedidosOnline/Show.vue`.

---

## FASE 3: Subida de Comprobantes de Pago

Facilitar la validación de transferencias permitiendo al cliente subir su captura.

### 1. Base de Datos
- [ ] **Migración:** Agregar columna a `pedidos_online`:
    ```php
    $table->string('comprobante_pago_path')->nullable(); // Ruta del archivo
    ```

### 2. Frontend (Cliente)
- [ ] **Vista "Pedido Confirmado":**
    - Si el método es "Transferencia" y estado es "Pendiente": Mostrar formulario `input type="file"`.
    - Botón "Enviar Comprobante".
- [ ] **Lógica Vue:** Manejar subida con `useForm` de Inertia.

### 3. Backend (Controlador)
- [ ] **Ruta:** `POST /pedidos-online/{id}/comprobante`
- [ ] **Lógica:**
    - Validar imagen/PDF (max 2MB).
    - Guardar en `storage/app/public/comprobantes`.
    - Actualizar ruta en BD.
    - (Opcional) Notificar al admin: "Comprobante cargado para pedido #PO-XXXX".

### 4. Admin
- [ ] **Vista Detalles:** Si existe comprobante, mostrar botón "Ver Comprobante" o previsualización de la imagen.
- [ ] **Acción Rápida:** Botón "Aprobar Pago" junto al comprobante.

---

## Ejecución
Para iniciar cada fase, utilizar el comando o prompt:
- *"Ejecuta la Fase 1 del plan de mejoras de checkout"*
