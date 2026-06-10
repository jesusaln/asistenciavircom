# Errores y riesgos - Modulo Ventas

> Alcance: controllers, services, models y rutas relacionadas con ventas.

## Criticos

### C-01 Cancelacion de ventas falla por namespace incorrecto
- Archivo/ruta: `app/Http/Controllers/VentaEstadoController.php`
- Descripcion: Se importa `App\Services\Ventas\VentaCancellationService`, pero el servicio real esta en `App\Services\VentaCancellationService`.
- Severidad: Alta
- Impacto: El endpoint `/ventas/{id}/cancel` lanza error de clase no encontrada y no permite cancelar ventas.
- Sugerencia: Corregir el namespace o mover el servicio a la ruta esperada.

## Medios

### M-01 marcarPagado actualiza venta y luego procesa pagos sin transaccion
- Archivo/ruta: `app/Http/Controllers/VentaController.php`
- Descripcion: Se actualiza la venta (fecha/metodo/cuenta) y despues se llama a `PaymentService` sin transaccion envolvente.
- Severidad: Media
- Impacto: Si falla el servicio de pagos, la venta queda parcialmente actualizada (estado y CxC inconsistentes).
- Sugerencia: Envolver todo en una transaccion y revertir cambios si falla el registro del pago.

### M-02 obtenerSiguienteNumero puede fallar si la secuencia no existe
- Archivo/ruta: `app/Http/Controllers/VentaController.php`
- Descripcion: `previewNextFolio` consulta una secuencia en PostgreSQL sin validacion/try-catch.
- Severidad: Media
- Impacto: Si la secuencia no existe o cambia el nombre, el endpoint responde 500.
- Sugerencia: Validar existencia de la secuencia o agregar fallback con try-catch y query alternativa.

### M-03 Falta scoping por empresa en consultas de ventas y catalogos
- Archivo/ruta: `app/Http/Controllers/VentaController.php`, `app/Services/VentaCreationService.php`
- Descripcion: Las consultas de ventas, clientes, productos y servicios no filtran por `empresa_id`.
- Severidad: Media
- Impacto: En escenarios multi-empresa, se exponen datos cruzados entre empresas.
- Sugerencia: Aplicar scoping por empresa en queries o usar scopes globales.

## Bajos

### B-01 Cancelar factura registra request completo en logs
- Archivo/ruta: `app/Http/Controllers/VentaController.php`
- Descripcion: Se loggea `request_data` completo en `cancelarFactura`.
- Severidad: Baja
- Impacto: Posible exposicion de datos sensibles en logs.
- Sugerencia: Loggear solo campos no sensibles o aplicar redaccion.

### B-02 Metodo validateSeriesUniqueness no se usa
- Archivo/ruta: `app/Http/Controllers/VentaController.php`
- Descripcion: El metodo privado no se invoca y contiene validacion parcial.
- Severidad: Baja
- Impacto: Codigo muerto y confusion de mantenimiento.
- Sugerencia: Eliminarlo o integrar su uso en la validacion real.

### B-03 N+1 en VentaUpdateService al cargar productos
- Archivo/ruta: `app/Services/VentaUpdateService.php`
- Descripcion: Se usa `Producto::findOrFail` por item en un loop.
- Severidad: Baja
- Impacto: Rendimiento degradado en ventas con muchos items.
- Sugerencia: Precargar productos con `whereIn` y mapear por id.
