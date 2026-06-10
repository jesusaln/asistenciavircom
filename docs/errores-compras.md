# Errores y riesgos - Modulo Compras

> Alcance: controllers, services, models y rutas relacionadas con compras.

## Criticos

### C-01 Falta de scoping por empresa en consultas y mutaciones
- Archivo/ruta: `app/Http/Controllers/CompraController.php`
- Descripcion: Las consultas y operaciones sobre compras no filtran por `empresa_id` (ej. `Compra::with(...)`, `Compra::findOrFail(...)`).
- Severidad: Alta
- Impacto: Si el sistema es multi-empresa, un usuario puede ver/modificar compras de otra empresa.
- Sugerencia: Aplicar scoping por `empresa_id` en todas las consultas y mutaciones (scope global o `where('empresa_id', Auth::user()->empresa_id)`).

### C-02 Lectura e importacion de CFDIs sin validacion de empresa
- Archivo/ruta: `app/Http/Controllers/CompraCfdiController.php`
- Descripcion: Se usan `Cfdi::findOrFail` y queries de CFDI sin filtrar por empresa (ej. `getReceivedCfdis`, `parseXmlCfdi`, `getCfdiPreview`).
- Severidad: Alta
- Impacto: Un usuario podria leer CFDIs de otra empresa y usarlos para crear compras.
- Sugerencia: Filtrar por `empresa_id` en todas las consultas de CFDI y validar ownership antes de leer archivos XML.

### C-03 Proveedor y almacen sin scoping en importacion masiva
- Archivo/ruta: `app/Http/Controllers/CompraCfdiController.php`
- Descripcion: `Proveedor::firstOrCreate(['rfc' => ...])` y `Almacen::first()` no filtran por empresa.
- Severidad: Alta
- Impacto: Posible cruce de datos entre empresas (proveedor/almacen incorrectos) y exposicion de datos.
- Sugerencia: Asegurar `empresa_id` en el lookup y seleccion de almacen por empresa.

## Medios

### M-01 Endpoint forceDestroy no funciona sin SoftDeletes
- Archivo/ruta: `app/Http/Controllers/CompraEstadoController.php`
- Descripcion: Usa `Compra::withTrashed()` y `deleted_at` pero `Compra` no usa `SoftDeletes`.
- Severidad: Media
- Impacto: La ruta `/compras/{id}/force` puede lanzar error en runtime.
- Sugerencia: Activar `SoftDeletes` en `app/Models/Compra.php` o eliminar el endpoint/uso de `withTrashed`.

### M-02 Totales inconsistentes cuando hay productos inactivos
- Archivo/ruta: `app/Http/Controllers/CompraController.php`
- Descripcion: En `store`, si un producto esta inactivo se omite crear item/inventario, pero el total ya fue calculado con esos productos.
- Severidad: Media
- Impacto: Compra con totales inflados y sin items correspondientes; afecta cuentas por pagar e inventario.
- Sugerencia: Rechazar productos inactivos en validacion o recalcular totales solo con productos validos.

### M-03 Importacion CFDI falla con cantidades decimales
- Archivo/ruta: `app/Http/Controllers/CompraCfdiController.php`
- Descripcion: `normalizeCantidad` exige enteros y lanza excepcion con decimales.
- Severidad: Media
- Impacto: CFDIs con unidades fraccionarias (kg, litros) no se pueden importar.
- Sugerencia: Permitir decimales o mapear a unidades configurables.

### M-04 Almacen opcional en importacion masiva sin validacion
- Archivo/ruta: `app/Http/Controllers/CompraCfdiController.php`
- Descripcion: `bulkImportSingle` no valida `almacen_id` y usa `Almacen::first()` si no se envia.
- Severidad: Media
- Impacto: Compras importadas a un almacen incorrecto o nulo; riesgo de inventario mal asignado.
- Sugerencia: Validar `almacen_id` obligatorio o usar almacen del usuario/empresa.

## Bajos

### B-01 Registro de request completo en logs
- Archivo/ruta: `app/Http/Controllers/CompraController.php`
- Descripcion: Se loggea `request->all()` en `store`.
- Severidad: Baja
- Impacto: Posible exposicion de datos sensibles en logs (cuentas, CFDI).
- Sugerencia: Loggear solo campos no sensibles o usar logging redacted.

### B-02 Validacion duplicada y metodo sin uso
- Archivo/ruta: `app/Http/Controllers/CompraController.php`
- Descripcion: Existe `validateCompraRequest()` pero `store/update` usan `CompraValidacionService`.
- Severidad: Baja
- Impacto: Codigo muerto/confuso y reglas duplicadas.
- Sugerencia: Eliminar el metodo o unificar validaciones en un solo lugar.

### B-03 N+1 en validacion de series por producto
- Archivo/ruta: `app/Services/Compras/CompraValidacionService.php`
- Descripcion: Por cada producto se hace `Producto::find` y luego queries de series.
- Severidad: Baja
- Impacto: Rendimiento degradado en compras grandes.
- Sugerencia: Precargar productos y series en bloque antes de validar.
