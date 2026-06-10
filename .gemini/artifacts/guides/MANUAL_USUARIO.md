# 📖 Manual de Usuario - CDD App (ERP)

> **Versión**: 2.4  
> **Fecha**: Diciembre 2025

---

## 📋 Índice

1. [Introducción](#1-introducción)
2. [Primeros Pasos](#2-primeros-pasos)
3. [Panel Principal](#3-panel-principal)
4. [Módulos del Sistema](#4-módulos-del-sistema)
5. [Preguntas Frecuentes](#5-preguntas-frecuentes)

---

## 1. Introducción

### ¿Qué es CDD App?

CDD App es un **Sistema ERP (Enterprise Resource Planning)** que integra todas las operaciones de tu negocio:

- 📦 **Inventario**: Control de productos, almacenes, series
- 💰 **Ventas**: Cotizaciones, pedidos, facturas
- 🛒 **Compras**: Órdenes de compra, recepción de mercancía
- 📊 **Finanzas**: Cuentas por cobrar/pagar, bancos
- 🧾 **Facturación**: CFDI 4.0 (SAT México)
- 👥 **RRHH**: Empleados, nóminas, vacaciones
- 🔧 **Servicios**: Tickets, mantenimientos, citas
- 🛍️ **Tienda Online**: Catálogo, carrito, pagos

---

## 2. Primeros Pasos

### 2.1 Acceder al Sistema

1. Abre tu navegador web
2. Ingresa la URL: `https://admin.tudominio.com`
3. Introduce tu **correo** y **contraseña**
4. Clic en **Iniciar Sesión**

### 2.2 Cambiar Contraseña

1. Clic en tu **nombre** (esquina superior derecha)
2. Selecciona **Perfil**
3. Ve a la sección **Actualizar Contraseña**
4. Ingresa la contraseña actual y la nueva
5. Clic en **Guardar**

### 2.3 Configurar Autenticación de Dos Factores (2FA)

1. Ve a **Perfil** → **Autenticación de Dos Factores**
2. Clic en **Habilitar**
3. Escanea el código QR con tu app de autenticación (Google Authenticator, Authy)
4. Ingresa el código de 6 dígitos
5. **Guarda los códigos de recuperación** en un lugar seguro

---

## 3. Panel Principal

### 3.1 Dashboard

El panel principal muestra un resumen de tu negocio:

| Widget | Descripción |
|--------|-------------|
| **Ventas del Día** | Total de ventas realizadas hoy |
| **Ventas del Mes** | Acumulado del mes actual |
| **Cuentas por Cobrar** | Dinero pendiente de clientes |
| **Cuentas por Pagar** | Deudas con proveedores |
| **Productos Bajos en Stock** | Alertas de inventario |
| **Tickets Abiertos** | Tickets de soporte pendientes |

### 3.2 Menú de Navegación

El menú lateral contiene todas las secciones del sistema:

```
📊 Panel (Dashboard)
├── 💼 Comercial
│   ├── Ventas
│   ├── Cotizaciones
│   ├── Pedidos
│   └── Clientes
├── 🛒 Compras
│   ├── Compras
│   ├── Órdenes de Compra
│   └── Proveedores
├── 📦 Inventario
│   ├── Productos
│   ├── Almacenes
│   ├── Traspasos
│   └── Ajustes
├── 💰 Finanzas
│   ├── Cuentas por Cobrar
│   ├── Cuentas por Pagar
│   ├── Bancos
│   └── Gastos
├── 🧾 Facturación (CFDI)
├── 👥 RRHH
│   ├── Empleados
│   ├── Nóminas
│   └── Vacaciones
├── 🔧 Servicios
│   ├── Tickets
│   ├── Mantenimientos
│   └── Citas
├── 📈 Reportes
└── ⚙️ Configuración
```

---

## 4. Módulos del Sistema

### 4.1 Ventas

#### Crear una Venta

1. Ve a **Comercial** → **Ventas**
2. Clic en **+ Nueva Venta**
3. Selecciona el **Cliente**
4. Agrega productos:
   - Busca por nombre o código
   - Ingresa cantidad
   - Clic en **Agregar**
5. Configura:
   - Método de pago
   - Condiciones de pago
   - Descuentos (si aplica)
6. Clic en **Guardar** o **Guardar y Facturar**

#### Estados de Venta

| Estado | Descripción |
|--------|-------------|
| 🟡 Pendiente | Venta creada, sin pagar |
| 🟢 Pagada | Venta pagada completamente |
| 🔵 Parcial | Pago parcial recibido |
| ⚫ Cancelada | Venta cancelada |

---

### 4.2 Cotizaciones

#### Crear Cotización

1. Ve a **Comercial** → **Cotizaciones**
2. Clic en **+ Nueva Cotización**
3. Selecciona cliente y agrega productos
4. Configura vigencia (días)
5. Clic en **Guardar**
6. Envía al cliente por **Correo** o **WhatsApp**

#### Convertir a Venta

1. Abre la cotización
2. Clic en **Convertir a Venta**
3. Confirma los datos
4. La cotización se marcará como "Aceptada"

---

### 4.3 Inventario

#### Consultar Stock

1. Ve a **Inventario** → **Productos**
2. Usa el buscador para encontrar productos
3. La columna **Stock** muestra existencias por almacén

#### Ajuste de Inventario

1. Ve a **Inventario** → **Ajustes**
2. Clic en **+ Nuevo Ajuste**
3. Selecciona el almacén
4. Agrega productos con:
   - **Cantidad positiva**: Aumentar stock
   - **Cantidad negativa**: Disminuir stock
5. Escribe el **motivo** del ajuste
6. Clic en **Guardar**

#### Traspaso entre Almacenes

1. Ve a **Inventario** → **Traspasos**
2. Clic en **+ Nuevo Traspaso**
3. Selecciona:
   - Almacén **origen**
   - Almacén **destino**
4. Agrega productos a transferir
5. Clic en **Guardar**

---

### 4.4 Facturación (CFDI)

#### Timbrar Factura

1. Al crear una venta, clic en **Guardar y Facturar**
2. O desde una venta existente, clic en **Facturar**
3. Verifica los datos fiscales del cliente
4. Clic en **Timbrar**
5. La factura se generará y podrás:
   - **Descargar PDF**
   - **Descargar XML**
   - **Enviar por correo**

#### Cancelar CFDI

1. Abre la factura
2. Clic en **Cancelar**
3. Selecciona el **motivo de cancelación**
4. Si se requiere, ingresa el **UUID sustituto**
5. Confirma la cancelación

> ⚠️ **Importante**: Las facturas solo pueden cancelarse dentro del plazo que marca el SAT.

---

### 4.5 Finanzas

#### Registrar Pago de Cliente

1. Ve a **Finanzas** → **Cuentas por Cobrar**
2. Busca la cuenta pendiente
3. Clic en **Registrar Pago**
4. Ingresa:
   - Monto
   - Fecha de pago
   - Método de pago
   - Cuenta bancaria
5. Clic en **Guardar**

#### Registrar Pago a Proveedor

1. Ve a **Finanzas** → **Cuentas por Pagar**
2. Busca la cuenta pendiente
3. Clic en **Registrar Pago**
4. Completa los datos
5. Clic en **Guardar**

---

### 4.6 Tickets de Soporte

#### Crear Ticket

1. Ve a **Servicios** → **Tickets**
2. Clic en **+ Nuevo Ticket**
3. Completa:
   - Cliente
   - Categoría
   - Prioridad
   - Descripción del problema
4. Clic en **Guardar**

#### Gestionar Ticket

1. Abre el ticket
2. Puedes:
   - **Agregar comentarios**
   - **Cambiar estado** (Abierto, En Proceso, Resuelto, Cerrado)
   - **Asignar técnico**
   - **Adjuntar archivos**

---

### 4.7 Reportes

#### Generar Reporte

1. Ve a **Reportes**
2. Selecciona el tipo:
   - Ventas
   - Compras
   - Inventario
   - Finanzas
   - Comisiones
3. Configura filtros:
   - Rango de fechas
   - Cliente/Proveedor
   - Productos
4. Clic en **Generar**
5. Opciones de exportación:
   - **Excel** (.xlsx)
   - **PDF**

---

### 4.8 Configuración

#### Datos de la Empresa

1. Ve a **Configuración** → **Empresa**
2. Pestaña **General**:
   - Nombre, RFC, Razón Social
   - Dirección fiscal
   - Teléfono, Email
3. Pestaña **Apariencia**:
   - Logo
   - Colores corporativos
4. Clic en **Guardar**

#### Gestionar Usuarios

1. Ve a **Configuración** → **Usuarios**
2. Clic en **+ Nuevo Usuario**
3. Completa:
   - Nombre
   - Email
   - Contraseña
   - Rol (Admin, Vendedor, Almacenista, etc.)
4. Clic en **Guardar**

---

## 5. Preguntas Frecuentes

### ¿Cómo recupero mi contraseña?

1. En la pantalla de login, clic en **¿Olvidaste tu contraseña?**
2. Ingresa tu correo electrónico
3. Revisa tu bandeja de entrada
4. Sigue el enlace para crear una nueva contraseña

### ¿Cómo agrego un nuevo almacén?

1. Ve a **Inventario** → **Almacenes**
2. Clic en **+ Nuevo Almacén**
3. Ingresa nombre y ubicación
4. Clic en **Guardar**

### ¿Por qué no puedo facturar?

Verifica que:
- El cliente tenga RFC válido
- Los productos tengan clave SAT asignada
- La empresa tenga certificados CFDI configurados

### ¿Cómo exporto datos a Excel?

En la mayoría de las listas (productos, clientes, ventas), hay un botón **Exportar** o **Excel** que genera un archivo descargable.

### ¿Cómo contacto soporte técnico?

1. Dentro del sistema, ve a **Ayuda** → **Soporte**
2. Crea un ticket describiendo tu problema
3. O contacta por WhatsApp/Email según la configuración de tu empresa

---

## 📞 Soporte

Si necesitas ayuda adicional:

- **Email**: soporte@tuempresa.com
- **Teléfono**: (662) XXX-XXXX
- **WhatsApp**: +52 662 XXX XXXX

---

*Este manual fue generado automáticamente. Última actualización: Diciembre 2025*
