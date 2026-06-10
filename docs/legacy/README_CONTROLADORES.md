# 📋 README - Análisis Detallado de Controladores

> **Proyecto**: CDD App (ERP)  
> **Total Controladores**: 103 (incluyendo API y subdirectorios)  
> **Fecha de Análisis**: 26 Diciembre 2025

---

## 📊 Resumen de Controladores

| # | Controlador | Métodos | Líneas | Tamaño | Categoría |
|---|-------------|---------|--------|--------|-----------|
| 1 | CompraController | 37 | 2548 | 115KB | Compras |
| 2 | PedidoController | 21 | 1909 | 87KB | Comercial |
| 3 | CotizacionController | 21 | 1763 | 84KB | Comercial |
| 4 | VentaController | 27 | 1711 | 73KB | Ventas |
| 5 | EmpresaConfiguracionController | 3 | 252 | 10KB | Administración |
| 6 | ReporteController | 30+ | 1800+ | 72KB | Reportes |
| 7 | CfdiController | 40 | 1579 | 63KB | Facturación |
| 8 | OrdenCompraController | 25+ | 1400+ | 55KB | Compras |
| 9 | ClienteController | 38 | 1164 | 49KB | Clientes |
| 10 | ProductoController | 24 | 1194 | 48KB | Inventario |

---

## 🏢 1. MÓDULO COMERCIAL

### 1.1 VentaController.php
**Ubicación**: `app/Http/Controllers/VentaController.php`  
**Tamaño**: 73KB | 1,711 líneas | 27 métodos

#### Dependencias Inyectadas
```php
- FolioGeneratorInterface $folioGenerator
- VentaCreationService $ventaCreationService
- VentaUpdateService $ventaUpdateService
- VentaCancellationService $ventaCancellationService
```

#### Métodos Principales

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `index()` | 32-264 | Lista ventas con paginación, filtros, estadísticas |
| `create()` | 266-381 | Formulario de nueva venta, carga catálogos |
| `store()` | 495-551 | Crea venta usando VentaCreationService |
| `show()` | 553-671 | Muestra detalle de venta con relaciones |
| `edit()` | 673-741 | Formulario de edición |
| `update()` | 743-795 | Actualiza venta usando VentaUpdateService |
| `cancel()` | 1172-1224 | Cancela venta usando VentaCancellationService |
| `marcarPagado()` | 1226-1334 | Marca venta como pagada, actualiza CxC |
| `facturar()` | 1336-1378 | Genera CFDI 4.0 para la venta |
| `cancelarFactura()` | 1380-1443 | Cancela factura en SAT |
| `generarPDF()` | 1014-1141 | Genera PDF de la venta |
| `generarTicket()` | 1143-1170 | Genera ticket térmico 80mm |
| `enviarEmail()` | 947-1012 | Envía venta por correo |

#### Características Especiales
- ✅ Arquitectura limpia con Services separados
- ✅ Validación de series únicas
- ✅ Sincronización de secuencias PostgreSQL
- ✅ Integración con CFDI 4.0
- ✅ Generación de folios con locks

#### Observaciones de complejidad
- Mezcla listado, CRUD, pagos, CFDI, documentos y notificaciones.
- Maneja lógica de negocio y formato de salida en el mismo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `VentaIndexController`: listado, filtros, paginación.
  - `VentaCrudController`: create/store/edit/update básicos.
  - `VentaPagoController`: marcar pagado y cuentas por cobrar.
  - `VentaDocumentoController`: PDF/ticket/exports.
  - `VentaFacturaController`: timbrado y cancelación CFDI.
  - `VentaNotificacionController`: envío por email/whatsapp.
- **Services**
  - `VentaQueryService`, `VentaPagoService`, `VentaDocumentoService`, `VentaFacturaService`, `VentaNotificacionService`.

#### Estado de refactor actual
- `VentaDocumentoController`: email, PDF y ticket.
- `VentaEstadoController`: cancelación.

---

### 1.2 CompraController.php
**Ubicación**: `app/Http/Controllers/CompraController.php`  
**Tamaño**: 115KB | 2,548 líneas | 37 métodos (EL MÁS GRANDE)

#### Dependencias Inyectadas
```php
- InventarioService $inventarioService
- CompraCalculosService $calculosService
- CompraValidacionService $validacionService
- CompraSerieService $serieService
- CompraCuentasPagarService $cuentasPagarService
- CompraInventarioService $compraInventarioService
```

#### Métodos Principales

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `index()` | 43-85 | Lista compras con filtros y estadísticas |
| `create()` | 260-353 | Formulario nueva compra |
| `store()` | 900-1169 | Crea compra, actualiza inventario |
| `edit()` | 439-524 | Formulario edición |
| `update()` | 527-897 | Actualiza compra (370 líneas!) |
| `cancel()` | 1291-1450 | Cancela y revierte inventario |
| `actualizarSeries()` | 1171-1289 | Gestiona series de productos |
| `getCfdiConceptos()` | - | Importa conceptos desde CFDI recibido |
| `getCfdiPreview()` | - | Preview de CFDI antes de importar |

#### Características Especiales
- ✅ Importación desde CFDI recibidos
- ✅ Control de series en recepción
- ✅ Generación de Cuentas por Pagar
- ✅ Cálculo automático de impuestos
- ✅ Historial de precios de compra

#### Estado de refactor actual
- `CompraCfdiController`: CFDI/parse/importaciones masivas.
- `CompraSeriesController`: actualización de series.
- `CompraEstadoController`: cancelación y eliminación definitiva.

#### Observaciones de complejidad
- CRUD, importación CFDI, inventario y CxP están acoplados en un solo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CompraIndexController`: listado y filtros.
  - `CompraCrudController`: create/store/edit/update.
  - `CompraInventarioController`: entradas, series y ajustes.
  - `CompraCuentasPagarController`: generación y seguimiento de CxP.
  - `CompraCfdiController`: importación y previsualización CFDI.
- **Services**
  - `CompraQueryService`, `CompraInventarioService`, `CompraCuentasPagarService`, `CompraCfdiService`.

---

### 1.3 CotizacionController.php
**Ubicación**: `app/Http/Controllers/CotizacionController.php`  
**Tamaño**: 84KB | 1,763 líneas | 21 métodos

#### Métodos Principales

| Método | Descripción |
|--------|-------------|
| `index()` | Lista con paginación servidor y filtros |
| `store()` | Crea cotización con productos/servicios |
| `convertirAVenta()` | Convierte cotización a venta |
| `enviarAPedido()` | Convierte a pedido |
| `duplicate()` | Duplica cotización |
| `enviarEmail()` | Envía por correo con PDF adjunto |
| `generarPDF()` | Genera PDF branded |

#### Flujo de Conversión
```
Cotización → Pedido → Venta → Factura (CFDI)
```

#### Observaciones de complejidad
- Maneja CRUD, conversión y generación de documentos en el mismo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CotizacionIndexController`: listado, filtros, paginación.
  - `CotizacionCrudController`: create/store/edit/update.
  - `CotizacionConversionController`: convertir a pedido/venta.
  - `CotizacionDocumentoController`: PDF y exports.
  - `CotizacionNotificacionController`: envío por correo.
- **Services**
  - `CotizacionQueryService`, `CotizacionConversionService`, `CotizacionDocumentoService`.

#### Estado de refactor actual
- `CotizacionConversionController`: convertir a venta y enviar a pedido.
- `CotizacionAccionController`: duplicar cotización.
- `CotizacionDocumentoController`: email, PDF y ticket.
- `CotizacionBorradorController`: guardar borradores.

---

### 1.4 PedidoController.php
**Ubicación**: `app/Http/Controllers/PedidoController.php`  
**Tamaño**: 87KB | 1,909 líneas | 21 métodos

#### Métodos Principales

| Método | Descripción |
|--------|-------------|
| `confirmar()` | Reserva inventario |
| `cancel()` | Libera inventario reservado |
| `enviarAVenta()` | Convierte a venta (descuenta inventario) |
| `duplicate()` | Duplica pedido |

#### Observaciones de complejidad
- El método `index()` realiza: filtros, ordenamiento, paginación, transformación de payload, estadísticas y opciones de filtros, todo en un solo bloque.
- Combina lógica de UI (shape de respuesta para Inertia) con reglas de negocio (transformaciones de ítems y estados).

#### Estado de refactor actual
- `PedidoEstadoController`: confirmar y cancelar.
- `PedidoVentaController`: enviar a venta + helpers.
- `PedidoAccionController`: duplicar + generación de número.
- `PedidoDocumentoController`: PDF/ticket/email.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `PedidoIndexController`: listado, filtros, sorting, paginación (solo lectura).
  - `PedidoCrudController`: create/store/edit/update básicos.
  - `PedidoEstadoController`: `confirmar`, `cancel`, `enviarAVenta` y transiciones de estado.
  - `PedidoDocumentoController`: generación de PDF/ticket/exports (si aplica).
  - `PedidoNotificacionController`: envío de email y notificaciones.
- **Services**
  - `PedidoQueryService`: construcción de query con filtros + ordenamiento.
  - `PedidoTransformService`: normaliza payload para Inertia (cliente, ítems, metadata).
  - `PedidoStatsService`: estadísticas y KPIs de pedidos.
  - `PedidoEstadoService`: reglas de transición, inventario y side-effects.
  - `PedidoConversionService`: conversión a venta/cotización.

**Resultado esperado**: controladores más pequeños, reglas de negocio centralizadas y tests más focalizados.

---

### 1.5 OrdenCompraController.php
**Ubicación**: `app/Http/Controllers/OrdenCompraController.php`  
**Tamaño**: 55KB | 1,400+ líneas | 25+ métodos

#### Observaciones de complejidad
- Combina CRUD, autorizaciones, estados y documentos en un solo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `OrdenCompraIndexController`: listado, filtros, paginación.
  - `OrdenCompraCrudController`: create/store/edit/update.
  - `OrdenCompraEstadoController`: aprobaciones y cancelaciones.
  - `OrdenCompraDocumentoController`: PDF/exports.
- **Services**
  - `OrdenCompraQueryService`, `OrdenCompraEstadoService`, `OrdenCompraDocumentoService`.

---

## 📦 2. MÓDULO INVENTARIO

### 2.1 ProductoController.php
**Tamaño**: 48KB | 1,194 líneas | 24 métodos

#### Métodos Principales

| Método | Descripción |
|--------|-------------|
| `index()` | Lista productos con paginación |
| `store()` | Crea producto con imagen WebP |
| `series()` | Lista series del producto |
| `storeSeries()` | Registra nuevas series |
| `validateStock()` | Valida stock en tiempo real |
| `export()` | Exporta a CSV |
| `toggle()` | Activa/desactiva producto |
| `getStockDetalle()` | Stock por almacén |
| `recalcularPrecios()` | Recalcula según lista de precios |

#### Observaciones de complejidad
- Controla CRUD, series, stock y exportaciones en un único punto.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `ProductoCrudController`: create/store/edit/update/toggle.
  - `ProductoSeriesController`: series y validaciones.
  - `ProductoStockController`: stock y detalle por almacén.
  - `ProductoExportController`: exportaciones.
- **Services**
  - `ProductoQueryService`, `ProductoSeriesService`, `ProductoStockService`, `ProductoExportService`.

---

### 2.2 AlmacenController.php
**Tamaño**: 10KB | ~300 líneas

CRUD básico de almacenes con validaciones.

#### Observaciones de complejidad
- Actualmente es compacto; no requiere división inmediata.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `AlmacenCrudController`: CRUD básico.
  - `AlmacenReporteController`: reportes si se agregan en el futuro.
- **Services**
  - `AlmacenValidationService` si crecen reglas específicas.

---

### 2.3 TraspasoController.php
**Tamaño**: 24KB

Gestiona traspasos entre almacenes con validación de stock.

#### Observaciones de complejidad
- Lógica de validación de stock y movimientos mezclada con la UI.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `TraspasoCrudController`: creación y consulta de traspasos.
  - `TraspasoInventarioController`: validaciones y movimientos.
- **Services**
  - `TraspasoService`, `TraspasoInventarioService`.

---

### 2.4 AjusteInventarioController.php
**Tamaño**: 17KB

Ajustes positivos/negativos con registro de motivo.

#### Observaciones de complejidad
- Mezcla operaciones de ajuste con validaciones de inventario.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `AjusteInventarioCrudController`: creación/consulta.
  - `AjusteInventarioValidacionController`: reglas y motivos.
- **Services**
  - `AjusteInventarioService`, `AjusteInventarioRulesService`.

---

## 💰 3. MÓDULO FINANZAS

### 3.1 CuentasPorCobrarController.php
**Tamaño**: 30KB | 701 líneas | 15 métodos

#### Métodos Principales

| Método | Descripción |
|--------|-------------|
| `index()` | Lista cuentas con filtros por estado |
| `registrarPago()` | Registra pago parcial/total |
| `timbrarReciboPago()` | Genera CFDI tipo P (Pago) |
| `importPaymentXml()` | Importa XML de complemento de pago |
| `applyPaymentsFromXml()` | Aplica pagos desde XML |

#### Observaciones de complejidad
- Opera pagos, timbrado y carga desde XML en el mismo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CuentasPorCobrarIndexController`: listado y filtros.
  - `CuentasPorCobrarPagoController`: registrar pagos.
  - `CuentasPorCobrarCfdiController`: timbrado P.
  - `CuentasPorCobrarImportController`: importación de XML.
- **Services**
  - `CuentasPorCobrarPagoService`, `CuentasPorCobrarCfdiService`, `CuentasPorCobrarImportService`.

---

### 3.2 CuentasPorPagarController.php
**Tamaño**: 23KB

Similar a CxC pero para proveedores.

#### Observaciones de complejidad
- Pagos, estados y reportes en una sola clase.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CuentasPorPagarIndexController`: listado y filtros.
  - `CuentasPorPagarPagoController`: pagos.
  - `CuentasPorPagarEstadoController`: cambios de estado.
- **Services**
  - `CuentasPorPagarPagoService`, `CuentasPorPagarEstadoService`.

---

### 3.3 CuentaBancariaController.php
**Tamaño**: 13KB

CRUD de cuentas bancarias con movimientos.

#### Observaciones de complejidad
- CRUD y movimientos financieros en el mismo flujo.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CuentaBancariaCrudController`: altas/bajas/edición.
  - `CuentaBancariaMovimientoController`: movimientos y conciliación.
- **Services**
  - `CuentaBancariaService`, `CuentaBancariaMovimientoService`.

---

### 3.4 PrestamoController.php
**Tamaño**: 30KB

Gestión de préstamos a empleados/clientes con plan de pagos.

#### Observaciones de complejidad
- Mezcla creación, pagos y estado de préstamos.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `PrestamoCrudController`: creación/edición.
  - `PrestamoPagoController`: pagos y amortizaciones.
  - `PrestamoEstadoController`: cambios de estado.
- **Services**
  - `PrestamoPagoService`, `PrestamoEstadoService`.

---

## 🧾 4. MÓDULO CFDI

### 4.1 CfdiController.php
**Tamaño**: 63KB | 1,579 líneas | 40 métodos

#### Métodos Principales

| Método | Descripción |
|--------|-------------|
| `index()` | Lista CFDIs con filtros avanzados |
| `store()` | Timbra nuevo CFDI |
| `previewXml()` | Preview antes de timbrar |
| `checkSatStatus()` | Verifica estado en SAT |
| `solicitarCancelacion()` | Solicita cancelación |
| `solicitarDescargaMasiva()` | Inicia descarga masiva SAT |
| `verificarDescargaMasiva()` | Verifica progreso |
| `importarSeleccionados()` | Importa CFDIs del staging |
| `descargarXml()` | Descarga XML |
| `verPdf()` | Genera PDF desde XML |
| `enviarCorreo()` | Envía CFDI por email |
| `bulkDownload()` | Descarga masiva ZIP |
| `bulkCheckSatStatus()` | Verificación masiva |

#### Observaciones de complejidad
- Emisión, cancelación, consulta, descarga y notificaciones en un solo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CfdiEmisionController`: timbrado y creación.
  - `CfdiCancelacionController`: cancelación y estatus.
  - `CfdiConsultaController`: búsqueda y detalle.
  - `CfdiDescargaController`: descargas masivas.
  - `CfdiNotificacionController`: envíos y correos.
- **Services**
  - `CfdiEmisionService`, `CfdiCancelacionService`, `CfdiDescargaService`, `CfdiNotificacionService`.

---

## 👥 5. MÓDULO CLIENTES

### 5.1 ClienteController.php
**Tamaño**: 49KB | 1,164 líneas | 38 métodos

#### Características
- Integración completa con catálogos SAT
- Validación de RFC
- Importación desde CFDI
- Exportación a Excel
- Caché de catálogos

#### Observaciones de complejidad
- Controla catálogos, exportaciones y reglas de negocio en un mismo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `ClienteCrudController`: altas/bajas/edición.
  - `ClienteCatalogoController`: catálogos SAT y selects.
  - `ClienteImportExportController`: importación y exportación.
  - `ClienteEstadoController`: activación/desactivación.
- **Services**
  - `ClienteCatalogoService`, `ClienteImportExportService`, `ClienteEstadoService`.

---

## 🔧 6. MÓDULO SERVICIOS

### 6.1 MantenimientoController.php
**Tamaño**: 33KB

Órdenes de trabajo, asignación de técnicos, seguimiento.

#### Observaciones de complejidad
- Maneja agenda, estados y comunicación en un solo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `MantenimientoCrudController`: creación/edición.
  - `MantenimientoEstadoController`: cambios de estado.
  - `MantenimientoAgendaController`: programación.
- **Services**
  - `MantenimientoEstadoService`, `MantenimientoAgendaService`.

---

### 6.2 CitaController.php
**Tamaño**: 28KB

Agendamiento de citas con técnicos.

#### Observaciones de complejidad
- Combina CRUD con agenda y notificaciones.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CitaCrudController`: alta/edición.
  - `CitaAgendaController`: reprogramación.
  - `CitaNotificacionController`: recordatorios.
- **Services**
  - `CitaAgendaService`, `CitaNotificacionService`.

---

### 6.3 TicketController.php
**Tamaño**: 20KB

Sistema de tickets de soporte con categorías y estados.

#### Observaciones de complejidad
- Comentarios, SLA y estados unidos en el mismo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `TicketCrudController`: alta/edición.
  - `TicketComentarioController`: comentarios.
  - `TicketSlaController`: métricas y vencimientos.
  - `TicketNotificacionController`: avisos.
- **Services**
  - `TicketSlaService`, `TicketNotificacionService`.

---

## 🛠️ 7. MÓDULO HERRAMIENTAS

### 7.1 HerramientaController.php
**Tamaño**: 27KB

Control de herramientas, asignaciones, historial.

#### Observaciones de complejidad
- Controla catálogo y estados en una sola clase.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `HerramientaCrudController`: catálogo principal.
  - `HerramientaEstadoController`: estados y disponibilidad.
- **Services**
  - `HerramientaEstadoService`.

### 7.2 GestionHerramientasController.php
**Tamaño**: 22KB

Asignaciones masivas, transferencias, estadísticas.

#### Observaciones de complejidad
- Asignación, transferencias y reportes en un mismo flujo.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `HerramientaAsignacionController`: asignaciones.
  - `HerramientaTransferenciaController`: transferencias.
  - `HerramientaReporteController`: reportes/historial.
- **Services**
  - `HerramientaAsignacionService`, `HerramientaTransferenciaService`.

---

## 👔 8. MÓDULO RRHH

### 8.1 EmpleadoController.php
**Tamaño**: 16KB

CRUD empleados con datos completos.

#### Observaciones de complejidad
- Controla datos y documentos del empleado en el mismo flujo.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `EmpleadoCrudController`: datos generales.
  - `EmpleadoDocumentoController`: documentos/archivos.
- **Services**
  - `EmpleadoDocumentoService`.

### 8.2 NominaController.php
**Tamaño**: 19KB

Cálculo y pago de nómina.

#### Observaciones de complejidad
- Mezcla cálculo, pagos y reportes.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `NominaCalculoController`: cálculo.
  - `NominaPagoController`: dispersión/pagos.
  - `NominaReporteController`: reportes.
- **Services**
  - `NominaCalculoService`, `NominaPagoService`.

### 8.3 VacacionController.php
**Tamaño**: 14KB

Solicitudes, saldos, calendario.

#### Observaciones de complejidad
- Solicitud, aprobación y calendario en el mismo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `VacacionSolicitudController`: solicitudes.
  - `VacacionAprobacionController`: aprobaciones.
  - `VacacionCalendarioController`: calendario/saldos.
- **Services**
  - `VacacionPolicyService`, `VacacionSaldoService`.

---

## 📈 9. MÓDULO CRM

### 9.1 CrmController.php
**Tamaño**: 34KB

Prospectos, campañas, actividades, metas, scripts.

#### Observaciones de complejidad
- Un solo controlador para múltiples entidades del CRM.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `CrmProspectoController`
  - `CrmCampaniaController`
  - `CrmActividadController`
  - `CrmMetaController`
  - `CrmScriptController`
- **Services**
  - `CrmPipelineService`, `CrmReporteService`.

---

## 📊 9.5 MÓDULO REPORTES

### 9.5.1 ReporteController.php
**Ubicación**: `app/Http/Controllers/ReporteController.php`  
**Tamaño**: 72KB | 1,800+ líneas | 30+ métodos

#### Observaciones de complejidad
- Muchos tipos de reportes y filtros en un solo controlador.
- Mezcla construcción de datos, formatos y exportación.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `ReporteVentasController`
  - `ReporteInventarioController`
  - `ReporteFinanzasController`
  - `ReporteDashboardController`
  - `ReporteExportController`
- **Services**
  - `ReporteQueryService`, `ReporteBuilderService`, `ReporteExportService`.

---

## ⚙️ 10. MÓDULO ADMINISTRACIÓN

### 10.1 EmpresaConfiguracionController.php
**Tamaño**: 10KB | 252 líneas | 3 métodos

Controlador de administración general reducido. Maneja:
- Pantalla principal (index)
- API de configuración
- Zona de peligro (eliminación masiva)

Ya se movieron a controllers dedicados en `app/Http/Controllers/Config/`:
- `AparienciaConfigController` (logos, favicon, colores)
- `EmailConfigController` (SMTP, pruebas y reportes)
- `CertificadosConfigController` (FIEL/CSD + PAC)
- `GeneralConfigController` (información general)
- `DocumentosConfigController` (pies de página, términos, privacidad)
- `ImpuestosConfigController` (IVA/ISR/moneda)
- `BancariosConfigController` (datos bancarios)
- `SistemaConfigController` (mantenimiento/backups)
- `SeguridadConfigController` (intentos, 2FA, DKIM)
- `RedConfigController` (dominio, SSL, ZeroTier)
- `TiendaConfigController` (OAuth/pagos tienda)
- `CobrosConfigController` (reglas y emails de cobranza)
- `PagosConfigController` (reglas y emails de pagos)

#### Observaciones de complejidad
- La zona de peligro sigue siendo el bloque más sensible por el volumen de acciones destructivas.

#### Propuesta pendiente
- **Services**
  - `EmpresaConfiguracionService`: lectura/escritura de config por módulo.
  - `EmpresaSecretsService`: manejo de secretos (enmascarar, rotar, validar).

---

### 10.2 UserController.php
**Tamaño**: 23KB

CRUD usuarios con roles y permisos Spatie.

#### Observaciones de complejidad
- Gestión de perfiles, roles y seguridad en un solo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `UserCrudController`: datos básicos.
  - `UserRoleController`: asignación de roles/permisos.
  - `UserSecurityController`: contraseñas/2FA (si aplica).
- **Services**
  - `UserRoleService`, `UserSecurityService`.

### 10.3 RoleController.php
**Tamaño**: 10KB

Gestión de roles y permisos.

#### Observaciones de complejidad
- Actualmente pequeño; separar solo si crece.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `RoleCrudController`
  - `RolePermissionController`
- **Services**
  - `RolePermissionService`.

### 10.4 DatabaseBackupController.php
**Tamaño**: 38KB

Backups automáticos, restauración, cloud storage.

#### Observaciones de complejidad
- Backup, restore, monitoring y storage en un solo controlador.

#### Propuesta: dividir en múltiples controllers/services
- **Controllers**
  - `BackupCrudController`: listado/creación/eliminación.
  - `BackupRestoreController`: restauraciones.
  - `BackupMonitoringController`: métricas/alertas.
  - `BackupStorageController`: cloud y uploads.
- **Services**
  - `BackupService`, `BackupRestoreService`, `BackupMonitoringService`.

---

## 🌐 11. API CONTROLLERS (20)

Ubicación: `app/Http/Controllers/Api/`

| Controlador | Descripción |
|-------------|-------------|
| AuthController | Login/Logout API |
| ClienteController | CRUD clientes API |
| ProductoController | CRUD productos API |
| VentaController | Ventas API |
| CotizacionController | Cotizaciones API |
| PedidoController | Pedidos API |
| CategoriaController | Categorías API |
| MarcaController | Marcas API |
| ProveedorController | Proveedores API |
| AlmacenController | Almacenes API |
| TecnicoController | Técnicos API |
| CitaController | Citas API |
| ConfigController | Configuración API |
| PrecioController | Precios API |
| PriceListController | Listas de precios API |

---

## 🎯 12. PORTAL DE CLIENTES (4)

Ubicación: `app/Http/Controllers/ClientPortal/`

| Controlador | Descripción |
|-------------|-------------|
| PortalController | Dashboard del cliente |
| AuthController | Login/Registro portal |
| PasswordResetLinkController | Recuperar contraseña |
| NewPasswordController | Nueva contraseña |

---

## 📊 ESTADÍSTICAS FINALES

| Métrica | Valor |
|---------|-------|
| **Total Controladores** | 103 |
| **Controladores Principales** | 83 |
| **Controladores API** | 20 |
| **Total Métodos (estimado)** | 800+ |
| **Total Líneas (estimado)** | 40,000+ |
| **Controlador más grande** | CompraController (115KB) |
| **Controlador más complejo** | CfdiController (40 métodos) |

---

## 🔄 PATRONES DE DISEÑO IDENTIFICADOS

1. **Dependency Injection**: Servicios inyectados en constructores
2. **Service Layer**: Lógica de negocio en Services, no en Controllers
3. **Repository Pattern**: (implícito en Eloquent)
4. **Form Requests**: Validación separada en Request classes
5. **Resource Controllers**: CRUD estándar de Laravel
6. **Traits**: Funcionalidad compartida (ej: Concerns)

---

## 🚀 RECOMENDACIONES DE REFACTORING

1. **CompraController** (115KB) - Dividir en múltiples controllers/services
2. **EmpresaConfiguracionController** (79KB) - Separar por tabs/funcionalidad
3. **Estandarizar respuestas de error** - Usar trait común
4. **Más tests unitarios** - Especialmente para services
5. **Documentar APIs** - Swagger/OpenAPI
