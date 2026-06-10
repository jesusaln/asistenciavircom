# Errores y riesgos - Modulo Finanzas

> Alcance: controllers financieros (cuentas por cobrar/pagar, cuentas bancarias, caja chica, gastos, prestamos, comisiones).

## Criticos

### C-01 Falta de scoping por empresa en consultas y mutaciones
- Archivo/ruta: `app/Http/Controllers/CuentasPorCobrarController.php`, `app/Http/Controllers/CuentasPorPagarController.php`, `app/Http/Controllers/CuentaBancariaController.php`, `app/Http/Controllers/CajaChicaController.php`, `app/Http/Controllers/GastoController.php`, `app/Http/Controllers/PrestamoController.php`, `app/Http/Controllers/ComisionController.php`
- Descripcion: No se observa filtrado por `empresa_id` o scope de tenant en listados, busquedas y operaciones `findOrFail`.
- Severidad: Alta
- Impacto: Si el sistema es multi-empresa, un usuario puede leer/modificar datos financieros de otra empresa.
- Sugerencia: Aplicar scope global por empresa o agregar `where('empresa_id', ...)` en todas las consultas y mutaciones; reforzar con policies.

### C-02 Importacion de pagos puede cruzar empresas por UUID
- Archivo/ruta: `app/Http/Controllers/CuentasPorCobrarController.php`
- Descripcion: `importPaymentXml` usa `Venta::where('cfdi_uuid', ...)` y luego busca `CuentasPorCobrar` sin validar empresa/tenant.
- Severidad: Alta
- Impacto: Un XML con UUID valido de otra empresa puede asociar pagos a cuentas ajenas.
- Sugerencia: Filtrar ventas y cuentas por `empresa_id` antes de asociar pagos; validar ownership del CFDI.

## Medios

### M-01 Parsing de XML sin hardening contra entidades externas
- Archivo/ruta: `app/Http/Controllers/CuentasPorCobrarController.php`
- Descripcion: `parsePaymentXml` usa `simplexml_load_string` sin deshabilitar entidades/externals.
- Severidad: Media
- Impacto: Riesgo de XXE/SSRF o entity expansion si el XML es malicioso.
- Sugerencia: Usar `libxml_disable_entity_loader` (si aplica) y `LIBXML_NONET`, o un parser seguro que bloquee entidades externas.

### M-02 Logs sensibles y debug en calculo de pagos
- Archivo/ruta: `app/Http/Controllers/PrestamoController.php`
- Descripcion: `calcularPagos` registra `request->all()`, session id y devuelve un bloque `debug` con informacion de sesion/CSRF.
- Severidad: Media
- Impacto: Exposicion de datos sensibles en logs y respuestas, util para abuso o fingerprinting.
- Sugerencia: Eliminar logs detallados en produccion y remover el bloque `debug` de la respuesta.

### M-03 Lectura de XML de CFDI sin validar pertenencia
- Archivo/ruta: `app/Http/Controllers/CuentasPorPagarController.php`
- Descripcion: `getPaymentCfdis` y `processPaymentCfdi` leen archivos XML desde `storage` segun `xml_url` del CFDI sin validar ownership/empresa.
- Severidad: Media
- Impacto: Posible exposicion de XML de otras empresas si no existe scoping en `Cfdi::recibidos()`.
- Sugerencia: Asegurar scoping por empresa en `Cfdi` y validar que el archivo pertenece al tenant actual.

## Bajos

### B-01 N+1 y queries extra en cuentas bancarias
- Archivo/ruta: `app/Http/Controllers/CuentaBancariaController.php`
- Descripcion: En `index` se calcula `movimientos()->count()` dentro de un `map`; en `show` se hace `Venta::find` por movimiento.
- Severidad: Baja
- Impacto: Rendimiento degradado en cuentas con muchos movimientos.
- Sugerencia: Usar `withCount` y precargar relaciones/ventas en bloque.

### B-02 Export de caja chica sin limite
- Archivo/ruta: `app/Http/Controllers/CajaChicaController.php`
- Descripcion: `export` descarga todos los registros filtrados sin limite de volumen.
- Severidad: Baja
- Impacto: Exportaciones grandes pueden agotar memoria/tiempo.
- Sugerencia: Agregar paginacion en export o exportacion asincrona por lotes.

### B-03 Estadisticas de cuentas por cobrar ignoran filtros
- Archivo/ruta: `app/Http/Controllers/CuentasPorCobrarController.php`
- Descripcion: `stats` se calcula sobre todas las cuentas, no sobre los filtros aplicados al listado.
- Severidad: Baja
- Impacto: El dashboard puede mostrar totales inconsistentes con el filtro activo.
- Sugerencia: Reusar el query base con filtros para calcular `stats`.
