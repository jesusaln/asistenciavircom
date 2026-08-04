# Análisis Profundo: Inventario Propio vs Inventario de Proveedor (CVA)

> **Objetivo:** Documentar el patrón actual para identificar productos "míos" vs "del proveedor", analizar cómo lo resuelve la industria (MercadoLibre, Amazon, Shopify, etc.) y proponer una evolución hacia un modelo multi-proveedor escalable para `asistenciavircom`.

---

## 1. ESTADO ACTUAL DEL PROYECTO

### 1.1 Lo que ya existe (¡bien resuelto a nivel macro!)

| Pieza | Ubicación | Función |
|---|---|---|
| Campo `origen` en `productos` | `database/migrations/2026_01_13_214015_add_cva_fields_to_productos_table.php:15` | `string('origen')->default('local')->index()` — etiqueta `'local' \| 'CVA'` |
| Campo `cva_clave` | misma migración:16 | índice para lookup rápido del SKU del proveedor |
| `stock_cedis` + `cva_last_sync` | misma migración:17-18 | stock en CEDIS y fecha de última sincronización |
| `CVAService::normalizeProduct()` | `app/Services/CVAService.php:191` | normaliza cualquier producto CVA con utilidad calculada, IVA, stock por sucursal |
| `CVAService::getOrCreateLocalProduct()` | `app/Services/CVAService.php:321` | **el corazón del patrón**: importa un producto CVA como `Producto` local con `origen='CVA'` y `cva_clave` |
| Ramas separadas en checkout | `CheckoutController.php:235-313` | valida y descuenta stock **solo** si `origen === 'local'`; si `origen === 'CVA'` valida contra API y empuja pedido a `pedidos_web/crear_orden` |
| Ramas separadas en carrito | `PedidoOnline.php:37-80` | descuenta stock automáticamente solo para `origen === 'local'` |
| Distinción visual frontend | `Catalogo/Show.vue:307` y `Catalogo/Index.vue:208` | badge "CEDIS", modal de advertencia y mensaje diferenciado en WhatsApp |
| Tabla `proveedores` | `app/Models/Proveedor.php` | ya existe la entidad `Proveedor`, pero **no se está usando** para catalogar a CVA |

### 1.2 Lo que falta o es débil

| # | Debilidad | Impacto |
|---|---|---|
| 🔴 | El campo `origen` es un `string` libre, no una FK ni un ENUM | Riesgo de typos (`'cva'`, `'CVA '`, `'CVA-'`), imposible garantizar integridad referencial |
| 🔴 | El carrito (`useCart.js`) **no guarda** el campo `origen` al hacer `addItem` | El checkout tiene que adivinar el origen por prefijo `CVA-` en el ID (línea CheckoutController.php:63) — frágil |
| 🟡 | No existe UI admin para filtrar/ver "mis productos" vs "del proveedor" | El dueño tiene que ir a la BD para saber qué tiene en almacén vs qué se surte vía CVA |
| 🟡 | CVA está "hardcodeado" en muchas partes del código (rutas `tienda.cva.*`, campo `cva_*`, `cva_clave`) | Si mañana se agrega otro mayorista (Intcomex, PCH, CT Internacional) hay que duplicar todo |
| 🟡 | No existe tabla pivote `producto_proveedor` (un producto con varios proveedores para comparar precio) | Estás casado a un solo proveedor por producto |
| 🟡 | El pedido en CVA se crea **al confirmar pago** pero no se valida `cva_pedido_id` en transacciones | Si la llamada a CVA falla, el pedido queda sin ID de proveedor — pérdida de trazabilidad |
| 🟢 | Falta log de auditoría: "este producto CVA se sincronizó pero ya no existe" | CVA a veces descontinúa productos |

---

## 2. CÓMO LO RESUELVEN LOS GRANDES (PATRONES DE LA INDUSTRIA)

### 2.1 Amazon — Tres modelos de inventario
| Modelo | Cómo identifica | Cómo vende |
|---|---|---|
| **FBA** (Fulfillment by Amazon) | El SKU pertenece al seller, pero está físicamente en almacén de Amazon | Amazon descuenta y envía al cliente final |
| **FBM** (Fulfillment by Merchant) | Stock en tienda del seller | El seller envía |
| **SFP** (Seller Fulfilled Prime) | Stock propio pero con la insignia Prime | Seller envía, Amazon valida tiempos |

**Patrón clave:** Amazon **nunca almacena el catálogo del proveedor como si fuera suyo**. El seller crea primero un listing propio con su `seller_sku`, y luego vincula el `asin` (Amazon Standard Identification Number) del proveedor. Esto permite:
- Múltiples sellers vendiendo el mismo producto.
- Trazabilidad: cada venta registra `seller_sku` + `asin`.
- Independizar precio, descripción y fotos del seller de las del fabricante.

### 2.2 MercadoLibre — Catálogo + Publicación
| Capa | Tabla | Para qué sirve |
|---|---|---|
| **Catálogo** | `items` (público de ML) | Producto "maestro" del proveedor/marca, compartido entre todos los sellers |
| **Publicación** | `listings` (del seller) | Tu oferta: precio, stock, condición, envío |

**Patrón clave:** cuando subes un producto y ML lo reconoce, te pregunta "¿es el mismo que este del catálogo?". Si dices sí, usas el `catalog_product_id` y tu listing queda atado a los datos del fabricante. Si dices no, creas un "catálogo custom" propio. Esto evita duplicados y mantiene fotos/specs consistentes.

**Lo que adoptamos:** la separación `catálogo (proveedor) ↔ publicación (mi oferta)`. Hoy en `asistenciavircom` **mezclamos ambos conceptos en `productos`**: el mismo registro guarda los datos del fabricante CVA **y** mi oferta de venta. Funciona porque solo hay 1 proveedor, pero escalará mal.

### 2.3 Shopify — Locations + Product Variants + Inventory Items
Shopify tiene **tres entidades separadas**:
1. `Product` — el "producto" (título, descripción).
2. `ProductVariant` — cada versión (talla, color).
3. `InventoryItem` — la unidad física rastreable (linkeada a una `Location`).

Cada `Location` es un almacén: puede ser tu tienda física, un drop-shipper, o un 3PL. Al hacer una venta, Shopify consulta el stock por `Location` y decide de dónde sale.

**Lo que adoptamos:** modelo deLocations (almacenes virtuales = "Almacén Propio", "CEDIS CVA", "CEDIS Intcomex"). Cada producto tiene `stock_por_almacen`. Los movimientos entre almacenes son explícitos (`transfer_inventory`).

### 2.4 Tiendas especializadas (ej. PC Factory, Cyberpuerta, Tyga, Amazon México de Tecnología)
Patrón dominante:
- **SKU propio** (siempre único, jamás el del proveedor).
- **Tabla `producto_proveedor`** (1 producto → N proveedores, con `costo`, `lead_time`, `min_qty`, `proveedor_principal_id`).
- **Sincronización periódica** (cron cada 4h o diaria, vía API o XML/CSV del mayorista).
- **Reserva temporal de stock** al iniciar checkout (15 min hold).
- **Split fulfillment**: si el carrito tiene productos locales + mayorista, separan en 2 envíos automáticamente.

---

## 3. MODELO OBJETIVO PROPUESTO PARA ASISTENCIAVIRCOM

### 3.1 De string-libre a FK real

**Antes (actual):**
```php
// productos.origen: string libre, default 'local'
$producto->origen = 'CVA'; // ← alguien puede escribir 'cva' y rompe
$producto->cva_clave = 'X-123'; // ← clave acoplada a un proveedor
```

**Después (propuesto):**
```php
// Nueva tabla 'fuentes_producto' (catálogo cerrado)
enum: 'local' | 'proveedor' | 'dropshipping' | 'consignacion'

// Producto.link_externo_id apuntando a la fuente (proveedor + clave)
$producto->fuente_id = FuenteProducto::firstOrCreate(['tipo' => 'proveedor', 'proveedor_id' => $cvaId])->id;
$producto->sku_proveedor = 'X-123';
```

### 3.2 Modelo de datos escalable

```sql
proveedores                 (ya existe)
├─ id, nombre, tipo, configuracion(json), activo, ...

fuentes_producto            (NUEVA — antes era string 'origen')
├─ id, empresa_id, producto_id, fuente (ENUM), proveedor_id (FK NULL), sku_proveedor, url_externa,
   costo, lead_time_dias, activo, ultima_sync_at
   UNIQUE(producto_id, fuente, proveedor_id)

almacenes                   (NUEVA — Locations de Shopify)
├─ id, empresa_id, nombre, tipo (ENUM: 'propio'|'cedis_cva'|'cedis_intcomex'|'transito'),
   direccion, cp, activo

stock_por_almacen           (NUEVA — Inventory de Shopify)
├─ id, empresa_id, producto_id, almacen_id, cantidad, reservado, updated_at
   UNIQUE(producto_id, almacen_id)

pedido_items_fuente         (NUEVA — split fulfillment)
├─ id, pedido_id, fuente (ENUM: 'local'|'proveedor'), proveedor_id, items(json), estado_envio
```

### 3.3 Flujo de checkout multi-fuente

```
1. Carrito mezclado (50% mío, 50% CVA)
                │
                ▼
2. POST /checkout/procesar
   ├─ DB::transaction
   │   ├─ lockForUpdate productos locales
   │   ├─ validar stock_por_almacen vs pedido.cantidad
   │   └─ split del pedido por fuente
   │
   ├─ Pedido principal (id=pedido_maestro)
   │
   ├─ PedidoHijo[1] — fuente=local
   │   ├─ descuenta stock_por_almacen (Almacén Propio)
   │   └─ emite VentaItem y CxC
   │
   └─ PedidoHijo[2] — fuente=proveedor, proveedor=CVA
       ├─ llama CVAService::createOrder() con los items de su fuente
       ├─ guarda cva_pedido_id + stock reservado en stock_por_almacen[tipo=transito]
       └─ emite evento para webhook de tracking
```

### 3.4 Cómo identificar visualmente "mío vs del proveedor" — UI

| Vista | Indicador visual |
|---|---|
| Catálogo / Producto | Etiqueta pegada arriba a la derecha: 🟢 **Stock propio** (envío 24-48 h desde Hermosillo) \| 🟠 **CEDIS CVA** (envío 3-5 días) \| 🔵 **Dropshipping** |
| Carrito | Cada item tiene un icono de almacén (caja propia vs camión) |
| Admin · Inventario | Tabs/filtros: **Mi inventario · CVA · Todos**; columna "Almacén actual" |
| Admin · Producto | Sección "Fuentes y proveedores": lista de proveedores, costo, lead time, link |

---

## 4. PLAN DE IMPLEMENTACIÓN POR FASES

### Fase 1 — Endurecer lo actual (1 semana, sin cambiar UX)
- [ ] Convertir `origen` a ENUM en migración
- [ ] Agregar `proveedor_id` (FK nullable a `proveedores`) a `productos`
- [ ] Sembrar `CVA` como proveedor_id por defecto en productos con `origen='CVA'`
- [ ] Guardar `origen` y `proveedor_id` en el `useCart.js` (campos `item.origen`, `item.proveedor_id`)
- [ ] Crear scope: `Producto::locales()` y `Producto::deProveedor($proveedorId)`
- [ ] Filtro en admin de productos por origen

### Fase 2 — Multi-proveedor real (2 semanas)
- [ ] Tabla `fuentes_producto`
- [ ] Tabla `almacenes` + `stock_por_almacen` (migración de stock actual a un almacén virtual "Principal")
- [ ] Permitir que un producto tenga varias fuentes (CVA + local) con costos distintos
- [ ] Comando `app:sync-producto --proveedor=cva` (ahora acepta múltiples proveedores)
- [ ] UI admin: reasignar un producto a otro proveedor

### Fase 3 — Split fulfillment (1 semana)
- [ ] Tabla `pedido_fuentes` (cabecera de cada split)
- [ ] Refactor `CheckoutController::procesar` para crear N sub-pedidos (uno por fuente)
- [ ] Evento `PedidoFuenteConfirmada` que dispara envío a la API del proveedor correspondiente
- [ ] UI: el cliente ve el tracking de cada envío por separado

### Fase 4 — Reserva temporal (1 día)
- [ ] Al iniciar checkout, marcar `stock_por_almacen.reservado += item.cantidad` por 15 min
- [ ] Job que limpia reservas expiradas

---

## 5. RESPUESTAS A LAS PREGUNTAS DEL USUARIO

### P: ¿Cómo identifico lo mío vs lo del proveedor?
**R:** Hoy usas `producto.origen ∈ {local, CVA}` y `producto.cva_clave`. Funciona, pero mañana que agregues Intcomex tendrás `origen='INTCOMEX'` y a romper. Recomendación: migrar a `producto.fuente_producto_id` (FK a `fuentes_producto`).

### P: ¿Cómo le hacen los demás en sus proyectos cuando agregan estas APIs para vender?
**R:** Patrón universal es **SKU propio + tabla pivote producto↔proveedor**:
- Amazon: `seller_sku` ↔ `asin`
- MercadoLibre: `listing` ↔ `catalog_product_id`
- Shopify: `Product` + `InventoryItem` por `Location` (3PL, drop-shipper, propio)
- Tiendas tech mexicanas: SKU propio, `producto_proveedor(costo, lead_time, principal)`, split fulfillment al vender.

### P: ¿Crear un agente que vigile esto?
**R:** Sí — creado en `~/.config/opencode/agents/inventario-hibrido.md`. Su rol es auditar el módulo inventario/tienda para mantener el patrón multi-fuente y evitar regresiones al modelo "todo es CVA" o "todo es local".

---

## 6. RIESGOS DE NO MIGRAR (si te quedas como estás)

1. **Segundo proveedor = reescritura 80% del código CVA.** Hoy tienes `cva_*` por todos lados.
2. **Bug de stock al mezclar carrito:** si el `addItem()` no guarda `origen`, dependes del prefijo `CVA-` en el ID — frágil.
3. **Imposible hacer razonable "compara precios entre proveedores"** para el mismo SKU.
4. **Imposible ofrecer "envío desde tu tienda (24h)" vs "envío desde CEDIS (3-5 días)"** sin separar stock por almacén.
5. **Trazabilidad fiscal rota:** el SAT requiere saber de quién es cada producto. Hoy tu CFDI no diferencia si lo compraste a CVA o lo fabricaste tú (impacta **diot, inventarios físicos,valuación de almacén**).

---

## 7. CONCLUSIÓN

El proyecto **ya tiene resuelto el 70% del patrón**: campo `origen`, `cva_clave`, `origen` en carrito, ramas separadas en checkout, badges visuales, modal de CEDIS. **Falta la migración a un modelo multi-fuente formal**, que es lo que cualquier tienda que hoy tiene 1 proveedor y mañana tendrá 2+ necesita. **Recomiendo ejecutar la Fase 1 antes de agregar cualquier segundo mayorista.**
