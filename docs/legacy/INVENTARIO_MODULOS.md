# 📦 Inventario de Módulos - CDD App (ERP)

> **Generado**: 26 Diciembre 2025  
> **Versión**: 2.4 (VentaEnLinea)

## Resumen Ejecutivo

| Componente | Cantidad |
|------------|----------|
| Controladores | 83 |
| Modelos | 113 |
| Módulos de Vistas (Vue) | 60 |
| Servicios | 46 |
| Jobs (Trabajos en Cola) | 3 |
| Commands (Artisan) | 63 |
| Eventos | 4 |
| Listeners | 4 |
| Observers | 5 |
| Policies | 5 |

---

## 🏢 Módulos Principales

### 1. Gestión Comercial

#### 1.1 Ventas
- **Controlador**: `VentaController.php` (73KB)
- **Modelo**: `Venta.php`, `VentaItem.php`, `VentaItemSerie.php`
- **Vistas**: `Ventas/Index`, `Ventas/Create`, `Ventas/Edit`, `Ventas/Show`
- **Funcionalidad**: Gestión completa de ventas, facturación, seguimiento de series

#### 1.2 Compras
- **Controlador**: `CompraController.php` (115KB - el más grande)
- **Modelo**: `Compra.php`, `CompraItem.php`
- **Vistas**: `Compras/Index`, `Compras/Create`, `Compras/Edit`, `Compras/Show`
- **Funcionalidad**: Gestión de compras, recepción de mercancía

#### 1.3 Cotizaciones
- **Controlador**: `CotizacionController.php` (84KB)
- **Modelo**: `Cotizacion.php`, `CotizacionItem.php`
- **Vistas**: `Cotizaciones/Index`, `Cotizaciones/Create`, `Cotizaciones/Edit`, `Cotizaciones/Show`

#### 1.4 Pedidos
- **Controlador**: `PedidoController.php` (86KB)
- **Modelo**: `Pedido.php`, `PedidoItem.php`, `PedidoOnline.php`
- **Vistas**: `Pedidos/Index`, `Pedidos/Create`, `Pedidos/Edit`, `Pedidos/Show`

#### 1.5 Órdenes de Compra
- **Controlador**: `OrdenCompraController.php` (55KB)
- **Modelo**: `OrdenCompra.php`
- **Vistas**: `OrdenesCompra/Index`, `OrdenesCompra/Create`, `OrdenesCompra/Show`

---

### 2. Inventario y Almacén

| Módulo | Controlador | Modelo | Vistas |
|--------|-------------|--------|--------|
| Productos | ProductoController (48KB) | Producto, ProductoSerie | 5 vistas |
| Almacenes | AlmacenController (10KB) | Almacen | 3 vistas |
| Traspasos | TraspasoController (24KB) | Traspaso, TraspasoItem | 4 vistas |
| Ajustes | AjusteInventarioController (17KB) | AjusteInventario | 2 vistas |
| Kits | KitController (25KB) | KitItem | 4 vistas |
| Movimientos | MovimientoInventarioController | InventarioMovimiento | 1 vista |

---

### 3. Finanzas y Contabilidad

| Módulo | Controlador | Modelo | Funcionalidad |
|--------|-------------|--------|---------------|
| Cuentas x Cobrar | CuentasPorCobrarController (30KB) | CuentasPorCobrar, Cobranza | Deudas clientes |
| Cuentas x Pagar | CuentasPorPagarController (23KB) | CuentasPorPagar | Deudas proveedores |
| Bancos | CuentaBancariaController (13KB) | CuentaBancaria, MovimientoBancario | Control bancario |
| Gastos | GastoController (21KB) | CategoriaGasto | Gastos operativos |
| Caja Chica | CajaChicaController (9KB) | CajaChica | Efectivo menor |
| Préstamos | PrestamoController (30KB) | Prestamo, PagoPrestamo | Préstamos |
| Comisiones | ComisionController (7KB) | PagoComision | Comisiones vendedores |

---

### 4. Facturación Electrónica (CFDI)

- **Controlador**: `CfdiController.php` (63KB)
- **Modelos**: `Cfdi.php`, `CfdiConcepto.php`
- **Catálogos SAT**: 15+ modelos para catálogos oficiales
- **Funcionalidad**: CFDI 4.0, timbrado, cancelación, descarga masiva

---

### 5. Recursos Humanos

| Módulo | Controlador | Modelo | Vistas |
|--------|-------------|--------|--------|
| Empleados | EmpleadoController (16KB) | Empleado | 4 vistas |
| Técnicos | TecnicoController (10KB) | Tecnico | 4 vistas |
| Nóminas | NominaController (19KB) | Nomina, NominaConcepto | 3 vistas |
| Vacaciones | VacacionController (14KB) | Vacacion, RegistroVacaciones | 6 vistas |

---

### 6. Gestión de Herramientas

- **Controladores**: `HerramientaController.php` (27KB), `GestionHerramientasController.php` (22KB)
- **8 Modelos**: Herramienta, CategoriaHerramienta, EstadoHerramienta, HistorialHerramienta, ResponsabilidadHerramienta, AsignacionHerramienta, AsignacionMasiva, DetalleAsignacionMasiva
- **12 Vistas**: Index, Create, Edit, Show, Historial, Asignacion, AsignacionMasiva, Responsabilidades, Estados, Categorias, Estadisticas, Transferencias

---

### 7. Servicios y Mantenimiento

| Módulo | Controlador | Vistas |
|--------|-------------|--------|
| Servicios | ServicioController (13KB) | 3 vistas |
| Mantenimientos | MantenimientoController (33KB) | 3 vistas |
| Citas | CitaController (28KB) | 4 vistas |
| Garantías | GarantiaController (14KB) | 2 vistas |

---

### 8. CRM

- **Controlador**: `CrmController.php` (34KB)
- **6 Modelos**: CrmProspecto, CrmActividad, CrmCampania, CrmMeta, CrmScript, CrmTarea
- **7 Vistas**: Index, Prospectos, Campañas, Tareas, Metas, Scripts, Reportes

---

### 9. Soporte y Tickets

- **Controlador**: `TicketController.php` (20KB)
- **Modelos**: Ticket, TicketCategory, TicketComment
- **9 Vistas**: Index, Create, Edit, Show, Dashboard, Categorias, Estadisticas, Configuracion, Base
- **Adicional**: Base de Conocimientos, Soporte Remoto (RustDesk)

---

### 10. Tienda Online / E-Commerce

| Componente | Descripción |
|------------|-------------|
| Catálogo | Catálogo público de productos |
| Carrito | Carrito de compras (CarroController) |
| Checkout | Proceso de pago (MercadoPago/PayPal) |
| Portal Clientes | Área de clientes con pedidos, tickets, perfil |
| Landing Page | Página de inicio personalizable |

---

### 11-16. Otros Módulos

| Módulo | Descripción |
|--------|-------------|
| **Rentas** | Alquiler de equipos/herramientas |
| **Vehículos** | Gestión de flota vehicular |
| **Reportes** | 15+ tipos de reportes (ventas, inventario, finanzas, etc.) |
| **Administración** | Usuarios, Roles, Configuración, Respaldos, Bitácora |
| **API** | 20 controladores API REST |
| **Integraciones** | WhatsApp Business, OAuth, MercadoPago, PayPal |

---

## 🏗️ Arquitectura

```
Frontend:  Vue 3 + Inertia.js + Tailwind CSS
Backend:   Laravel 10 + Sanctum + Spatie Permission
Database:  PostgreSQL + Redis
Servicios: SAT (CFDI) | WhatsApp API | MercadoPago | PayPal | OAuth
```

---

## 📁 Estructura

```
cdd_app/
├── app/
│   ├── Console/         # 63 comandos Artisan
│   ├── Http/Controllers/# 83 controladores
│   ├── Models/          # 113 modelos
│   └── Services/        # 46 servicios
├── resources/js/Pages/  # 60 módulos Vue
├── routes/
│   ├── web.php          # 500+ rutas web
│   └── api.php          # 100+ rutas API
└── database/migrations/ # 100+ migraciones
```
