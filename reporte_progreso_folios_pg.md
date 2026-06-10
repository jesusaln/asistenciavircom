# Item de Conocimiento (KI): Corrección de Folios y Compatibilidad con PostgreSQL

## Resumen
Se han resuelto problemas críticos relacionados con la sincronización de folios y errores de tipos en la base de datos PostgreSQL. Además, se realizó una refactorización de servicios para mejorar la modularidad del sistema.

## Cambios Implementados

### 1. Sistema de Folios
- **Sincronización Atómica**: El `FolioService` se actualizó para identificar correctamente el número máximo de folio en las tablas `ventas`, `facturas` y `cfdis`.
- **Consistencia en CFDI**: Se modificó `CfdiJsonBuilder` para que el folio del XML coincida con el `numero_venta` interno, eliminando prefijos y asegurando que la representación numérica sea correcta para el SAT.
- **Modularización**: Los servicios de creación, actualización y cancelación de ventas se movieron al espacio de nombres `App\Services\Ventas`.

### 2. Compatibilidad con PostgreSQL (Casting de UUID)
- Se resolvieron los errores `SQLSTATE[22P02]: Invalid text representation` añadiendo `CAST(uuid AS TEXT)` en todas las consultas de búsqueda que utilizan el operador `LIKE` o `ILIKE` sobre columnas de tipo UUID.
- Archivos afectados:
    - `CfdiController.php`
    - `CuentasPorPagarController.php`
    - `CompraCfdiController.php`
    - `PaymentProcessingService.php`

### 3. Integridad en Inventarios
- Se aplicaron correcciones similares de casting para búsquedas por ID numérico en:
    - `TraspasoController.php`
    - `AjusteInventarioController.php`
    - `MovimientoManualController.php`

## Impacto
- Se eliminaron los cierres inesperados (crashes) al realizar búsquedas en los módulos de finanzas e inventario.
- Se garantiza que los números de folio generados sean secuenciales y no dupliquen registros existentes, incluso después de desincronizaciones manuales.
