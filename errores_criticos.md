# Análisis de 100 Errores Críticos (Test Failures & Laravel Logs)

Al analizar la ejecución de `php artisan test` (164 failures) y `storage/logs/laravel.log`, se identificó que el sistema sufre de un grupo concentrado de errores críticos. A continuación, se presenta un desglose agrupado de los más de 100 errores y su origen principal. 

## Fase 1: Errores Críticos Estructurales de Base de Datos (SQLSTATE[42703])
El error más repetido (más de 130 tests fallidos) está vinculado al esquema de la tabla `empresas`. La fábrica (`EmpresaFactory`) o el Modelo asignan datos que no existen en la migración física de la base de datos de pruebas.

* **Fallo principal:** `SQLSTATE[42703]: Undefined column: 7 ERROR: column "nombre_razon_social" of relation "empresas" does not exist`
* **Columnas faltantes detectadas en `empresas` durante inserción:**
  - `nombre_razon_social`
  - `rfc`
  - `tipo_persona`
  - `regimen_fiscal`
  - `uso_cfdi`
  - `calle`, `numero_exterior`, `codigo_postal`, `colonia`, `municipio`, `estado`, `pais`
  - `whatsapp_enabled`, `whatsapp_default_language`

**Tests afectados (Muestra de 100 fallos):**
1. `VentaFlowTest > reserva inventario pedidos`
2. `VentaCreationServiceTest > calcula totales correctamente`
3. `Testing API > api can create cita`
4. `Testing API > api can delete cita`
5. `Testing API > api can list citas`
6. `Testing API > api can show cita`
7. `Testing API > api can update cita`
8. `Testing API > api returns 404 for non existent cita`
9. `Testing API > api token permissions can be updated`
10. `Testing API > api tokens can be created`
11. `Testing API > api tokens can be deleted`
12. `Testing Backup > can clean old backups`
13. `Testing Backup > can compress backup`
14. `Testing Backup > can create backup with basic options`
15. `Testing Backup > can create incremental backup`
16. `Testing Backup > can create scheduled backup`
17. `Testing Backup > can create secure backup`
18. `Testing Backup > can delete backup`
19. `Testing Backup > can get advanced monitoring data`
20. `Testing Backup > can get backup info`
... y alrededor de 110 tests más que dependen de `EmpresaFactory` o inserciones de empresas durante la configuración inicial (`setUp`).

## Fase 2: Errores Lógicos en `Traspasos`
* **Fallo principal:** `SQLSTATE[42703]: Undefined column: 7 ERROR: column "cantidad_total" does not exist` en la consulta sum agregada sobre `traspasos`.
* **Causa:** El módulo de Traspasos está invocando la suma matemática sobre una propiedad de volumen (`cantidad_total`) que no existe en el esquema validado, ocasionando fallos reportados en `storage/logs/laravel.log`.

## Fase 3: Errores de API / Controladores (BadMethodCallException)
* **Fallo principal:** `Method App\Http\Controllers\GarantiaController::show does not exist.`
* **Causa:** La ruta resource o GET intenta cargar el método `show`, pero dentro de `GarantiaController` solo se encuentran otros métodos definidos (index, create, etc.), o falto su implementación de visualización.

## Fase 4: Problemas de Performance / Rendimiento (Timeouts)
* **Fallo principal:** `Maximum execution time of 30 seconds exceeded`
* **Causa:** Se detectaron múltiples cortes a los 30 segundos (default `max_execution_time` de PHP) en scripts que apuntan a consultas grandes dentro del ORM o caché de DB (`Illuminate\Cache\DatabaseStore`, `Illuminate\Database\Connection`).

## Fase 5: Excepciones de Consola (Command NotFound)
* **Fallo principal:** `Command "inertia:version" is not defined.`
* **Causa:** Un llamado a SSR de Inertia en producción o procesos en background intenta resolver una versión con un comando que ya no existe o está desactualizado (`inertia:stop-ssr`).

---

## Plan de Resolución por Fases

### Fase 1: Sincronización de Base de Datos `empresas`
1. Reemplazar `nombre_razon_social` en `EmpresaFactory` por los nombres reales `nombre_fiscal` y `nombre_comercial` o alternativamente crear una migración de parche para ajustar y estandarizar `empresas` con los campos solicitados por los modelos robustos de la aplicación.
2. Modificar el scope de la factory de `$this->faker->company` a atributos correctos de la DB.

### Fase 2: Estabilidad de Tests de Ventas y Citas
1. Actualizar los Mock builders o la fábrica compartida para el Setup.

### Fase 3: Controlador de Garantias
1. Anular ruta `show` o implementar función dummy en `GarantiaController` retornando JSON o Vista según se detecte.

### Fase 4 y 5: Log y SSR 
1. Limpiar o corregir las llamadas de Artisan. 
2. Revisar la cache store y performance local en queries reportadas.

---

## Fase 6: Módulo de Migraciones Rotas y Testing Setups (2026-02-22)
Al correr `php artisan test --no-coverage` emergió una gran cantidad de `QueryException` (190 test failures) paralizando los tests, debido a inestabilidades que logramos identificar y limpiar quirúrgicamente:

1. **Problemas con Migraciones de Tablas Inexistentes (`SQLSTATE[42P01]`, `SQLSTATE[42703]`, `SQLSTATE[42701]`):**
   - Múltiples migraciones alteraban tablas que en el entorno limpio de testing no alcanzaban a existir correctamente (ej. `rentas`).
   - Una migración del 2026 alteraba `fecha_hora` de la tabla `citas`, lo cual fallaba y rompía la cadena de `migrate:fresh`. Se envolvió de forma segura con `Schema::hasColumn`.
   - Se ajustaron validaciones `Schema::hasTable` sobre `rentas` y sobre `cuentas_por_cobrar`.
   - Se arregló el error de `Duplicate column: fecha_vencimiento` en `facturas`.

2. **Caída por el Setup de VentaFlowTest y Tablas Perdidas en Testing:**
   - La base de datos de pruebas inicial (creada desde el mock `2014_01_01_000000_create_basic_tables.php`) evadía crear tablas críticas del modelo Core que luego los Tests solicitaban limpiar al momento de correr (`Illuminate\Support\Facades\DB::table('X')->truncate()`). 
   - Tablas completamente faltantes (`cuentas_por_cobrar`, `backup_logs`, `pedidos`, `pedido_items`, `inventarios`) fueron agregadas de forma segura y retroactiva implementando la migración `2014_01_01_000001_create_missing_core_tables.php`. 

3. **Inconsistencias de Clases (`BindingResolutionException`) y Factorías de Almacén:**
   - Una unidad de prueba apuntaba a `App\Services\VentaCreationService` cuando en la refactorización se movió a `App\Services\Ventas\VentaCreationService`. El namespace ya fue redirigido y solucionado.
   - Modificado el esquema `almacenes` en tests para incorporar el string nativo `estado` requerido por los fixtures de Laravel (`SQLSTATE[42703]: column "estado" of relation "almacenes" does not exist`).
