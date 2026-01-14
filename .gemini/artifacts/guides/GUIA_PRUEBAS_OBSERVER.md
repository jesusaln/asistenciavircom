# Guía de Pruebas del ProductoSerieObserver

## 🧪 Métodos de Prueba

Tienes **3 opciones** para probar el observer:

---

## Opción 1: Script Automático (Recomendado) ⭐

### Ejecutar el script de pruebas

```bash
cd c:\inetpub\wwwroot\cdd_app
php test_producto_serie_observer.php
```

### Qué hace el script:
- ✅ **TEST 1**: Crear serie en stock → verifica incremento de inventario
- ✅ **TEST 2**: Crear serie vendida → verifica que NO incrementa
- ✅ **TEST 3**: Cambiar de en_stock a vendido → verifica decremento
- ✅ **TEST 4**: Cambiar de vendido a en_stock → verifica incremento (devolución)
- ✅ **TEST 5**: Soft delete de serie → verifica decremento
- ✅ **TEST 6**: Restaurar serie → verifica incremento
- ✅ **TEST 7**: Mover entre almacenes → verifica decremento/incremento
- ✅ **TEST 8**: Stock total del producto se actualiza

### Resultado esperado:
```
🧪 INICIANDO PRUEBAS DEL PRODUCTO SERIE OBSERVER
--------------------------------------------------------------------------------
📦 Producto: BOILER FLUX BLANCO (ID: 8)
🏢 Almacén: Almacén Principal (ID: 1)
--------------------------------------------------------------------------------
ℹ️  TEST 1: Crear serie en stock
✅ Serie creada: TEST-SERIE-1-1732677123
✅ TEST 1: Inventario correcto = 11
--------------------------------------------------------------------------------
...
✅ Todas las pruebas completadas
```

---

## Opción 2: Prueba Manual en Tinker 🔧

### 1. Abrir Tinker
```bash
php artisan tinker
```

### 2. Ejecutar pruebas paso a paso

```php
// Obtener producto y almacén
$producto = App\Models\Producto::where('requiere_serie', true)->first();
$almacen = App\Models\Almacen::where('estado', 'activo')->first();

// Ver inventario inicial
$inventario = App\Models\Inventario::where('producto_id', $producto->id)
    ->where('almacen_id', $almacen->id)
    ->first();
echo "Stock inicial: " . ($inventario ? $inventario->cantidad : 0) . "\n";

// TEST 1: Crear serie en stock
$serie = App\Models\ProductoSerie::create([
    'producto_id' => $producto->id,
    'almacen_id' => $almacen->id,
    'numero_serie' => 'MANUAL-TEST-' . time(),
    'estado' => 'en_stock'
]);

// Verificar que incrementó
$inventario->refresh();
echo "Stock después de crear serie: " . $inventario->cantidad . "\n";
// Esperado: +1

// TEST 2: Cambiar a vendido
$serie->update(['estado' => 'vendido']);

// Verificar que decrementó
$inventario->refresh();
echo "Stock después de vender: " . $inventario->cantidad . "\n";
// Esperado: -1

// TEST 3: Devolver (cambiar a en_stock)
$serie->update(['estado' => 'en_stock']);

// Verificar que incrementó
$inventario->refresh();
echo "Stock después de devolver: " . $inventario->cantidad . "\n";
// Esperado: +1

// Limpiar
$serie->forceDelete();
```

---

## Opción 3: Prueba en la Interfaz Web 🌐

### 1. Crear una compra con series

1. Ve a **Compras → Nueva Compra**
2. Agrega un producto que requiera serie
3. Ingresa números de serie (ej: `TEST-WEB-001`, `TEST-WEB-002`)
4. Guarda la compra

### 2. Verificar inventario

```bash
php artisan tinker
```

```php
// Buscar las series creadas
$series = App\Models\ProductoSerie::where('numero_serie', 'like', 'TEST-WEB-%')->get();

foreach ($series as $serie) {
    echo "Serie: {$serie->numero_serie}, Estado: {$serie->estado}, Almacén: {$serie->almacen_id}\n";
}

// Verificar inventario
$producto = $series->first()->producto;
$almacen = $series->first()->almacen;

$inventario = App\Models\Inventario::where('producto_id', $producto->id)
    ->where('almacen_id', $almacen->id)
    ->first();

echo "Stock en inventario: " . $inventario->cantidad . "\n";
echo "Cantidad de series en_stock: " . App\Models\ProductoSerie::where('producto_id', $producto->id)
    ->where('almacen_id', $almacen->id)
    ->where('estado', 'en_stock')
    ->count() . "\n";

// Esperado: Ambos números deben ser iguales
```

### 3. Crear una venta con esas series

1. Ve a **Ventas → Nueva Venta**
2. Selecciona el producto
3. Ingresa las series `TEST-WEB-001`, `TEST-WEB-002`
4. Completa la venta

### 4. Verificar que el inventario decrementó

```php
$inventario->refresh();
echo "Stock después de venta: " . $inventario->cantidad . "\n";
// Esperado: Decrementó en 2
```

---

## 📊 Verificar Logs del Observer

### Ver logs en tiempo real

```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 50 | Select-String "ProductoSerieObserver"

# O simplemente ver el archivo
notepad storage\logs\laravel.log
```

### Buscar en logs:

Busca líneas que contengan:
- `ProductoSerieObserver: Inventario sincronizado`
- `ProductoSerieObserver: Serie movida entre almacenes`
- `ProductoSerieObserver: Error sincronizando inventario` (si hay errores)

### Ejemplo de log exitoso:

```
[2025-11-26 22:50:15] local.INFO: ProductoSerieObserver: Inventario sincronizado  
{
    "serie_id": 123,
    "numero_serie": "TEST-SERIE-1-1732677123",
    "producto_id": 8,
    "almacen_id": 1,
    "tipo": "incremento",
    "nueva_cantidad": 11,
    "stock_total": 11
}
```

---

## 🔍 Verificación Manual de Sincronización

### Consulta SQL directa

```sql
-- Ver inventario vs series reales
SELECT 
    p.nombre as producto,
    a.nombre as almacen,
    i.cantidad as stock_inventario,
    COUNT(ps.id) as series_en_stock,
    (i.cantidad - COUNT(ps.id)) as diferencia
FROM inventarios i
JOIN productos p ON p.id = i.producto_id
JOIN almacenes a ON a.id = i.almacen_id
LEFT JOIN producto_series ps ON ps.producto_id = p.id 
    AND ps.almacen_id = a.almacen_id 
    AND ps.estado = 'en_stock'
    AND ps.deleted_at IS NULL
WHERE p.requiere_serie = true
GROUP BY p.id, p.nombre, a.id, a.nombre, i.cantidad
HAVING (i.cantidad - COUNT(ps.id)) != 0;
```

**Resultado esperado**: 0 filas (sin diferencias)

Si hay diferencias, el observer no está funcionando correctamente.

---

## ⚠️ Solución de Problemas

### El observer no se ejecuta

1. **Verificar que está registrado**:
   ```php
   php artisan tinker
   
   // Verificar observers registrados
   $observers = app('events')->getListeners('eloquent.created: App\Models\ProductoSerie');
   dd($observers);
   ```

2. **Limpiar caché**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan optimize:clear
   ```

3. **Verificar que el archivo existe**:
   ```bash
   dir app\Observers\ProductoSerieObserver.php
   ```

### El inventario no se actualiza

1. **Verificar logs de errores**:
   ```bash
   Get-Content storage\logs\laravel.log -Tail 100 | Select-String "ProductoSerieObserver.*Error"
   ```

2. **Verificar transacciones**:
   El observer funciona dentro de transacciones. Si hay un error, se hace rollback automático.

3. **Verificar permisos de BD**:
   El observer necesita permisos de UPDATE en las tablas `inventarios` y `productos`.

---

## ✅ Checklist de Verificación

- [ ] Script de pruebas ejecutado sin errores
- [ ] Todos los 8 tests pasaron
- [ ] Logs muestran sincronizaciones exitosas
- [ ] Consulta SQL no muestra diferencias
- [ ] Prueba manual en interfaz web funciona
- [ ] Stock se actualiza al crear series
- [ ] Stock se actualiza al vender series
- [ ] Stock se actualiza al mover entre almacenes

---

## 📝 Notas Importantes

> [!IMPORTANT]
> El observer se ejecuta **automáticamente** en todos los eventos de `ProductoSerie`. No necesitas llamarlo manualmente.

> [!TIP]
> Si modificas el observer, ejecuta `php artisan config:clear` para recargar los cambios.

> [!WARNING]
> No ejecutes el script de pruebas en producción. Usa un ambiente de desarrollo o testing.
