# Integración MercadoLibre → CVA (Dropshipping)

**Objetivo:** Publicar productos del catálogo CVA en MercadoLibre, vender sin tener inventario propio, y que CVA envíe directo al cliente final usando tu margen.

---

## Fase 0 — Requisitos técnicos base (previo a ML)

### 0.1 App de MercadoLibre
- Crear aplicación en [developers.mercadolibre.com](https://developers.mercadolibre.com)
- Obtener: `APP_ID`, `CLIENT_SECRET`, configurar `redirect_uri`
- Solicitar permisos: `read`, `write`, `offline_access`, `items_write`, `orders_read`

### 0.2 Config necesaria en tu sistema
- Tabla `mercadolibre_config`: token de acceso, refresh token, user_id, tienda activa
- Tabla `mercadolibre_listings`: producto local ↔ listing_id de ML, estado, precio publicado, stock
- Campo `productos.meli_attributes`: atributos requeridos por categoría ML (ej. marca, modelo, condición)

### 0.3 Costos mensuales
- MercadoLibre cobra comisión por venta (~10-16% + IVA)
- MercadoLibre cobra por publicidad si usas (opcional)
- CVA no cobra extra por enviar directo al cliente (tú pones la orden con la dirección del cliente final)

---

## Fase 1 — Catálogo: Sincronizar productos CVA → MercadoLibre

### 1.1 Mapeo de categorías
CVA usa grupos (ej. "DISCOS DUROS"). ML usa categorías con ID numérico (ej. `MLM1648` = "Discos Duros Internos"). Crear tabla de mapeo:
```
cva_grupo → meli_category_id + atributos requeridos
```

### 1.2 Publicación inicial (comando: `meli:sync-catalog`)
```
Por cada producto CVA activo:
  // ⚠️ Solo publicar si hay mínimo 3 unidades disponibles
  //    (evita vender productos que CVA ya no tiene)
  stock_total = stock_local + stock_cedis
  SI stock_total < 3 → SALTAR (no publicar)

  1. Buscar listing existente por cva_clave
  2. Si no existe → crear item en ML via API:
     - title: nombre del producto (max 60 chars)
     - category_id: según mapeo de grupo
     - price: precio_venta (con margen)
     - currency_id: MXN
     - available_quantity: min(stock_total, 10)  // ML上限10
     - buying_mode: buy_it_now
     - listing_type_id: gold_special (gratis, 60 días)
     - pictures: imágenes del producto
     - attributes: marca, modelo, condición (nuevo)
  3. Guardar relación producto ↔ listing_id
```

> 📌 **Stock mínimo**: No se publican productos con menos de 3 unidades. Si ya publicado baja de 3 → se pausa automático en `meli:sync-stock`.

### 1.3 Actualización de stock/precios (comando: `meli:sync-stock`)
```
Cada N minutos (ej. 30):
  Por cada listing activo:
    stock_total = stock_local + stock_cedis
    
    // ⚠️ Si bajó de 3 → pausar para evitar vender algo sin stock
    SI stock_total < 3:
      → PUT /items/{listing_id} con available_quantity = 0
      → status = paused
      → SALTAR (no seguir procesando este listing)
    
    // Si sigue disponible, actualizar cantidad en ML
    SI cantidad en DB ≠ cantidad publicada en ML:
      → PUT /items/{listing_id} con available_quantity = min(stock_total, 10)
    
    SI precio cambió:
      → PUT /items/{listing_id} con new price
```

### 1.4 Despublicar automático
- Si stock total < 3 → pausar listing (se reactiva solo si vuelve a ≥3)
- Si stock total = 0 por más de 7 días → cerrar listing definitivamente
- Si producto descontinuado en CVA → eliminar listing

### 1.5 OAuth token management
- Access token expira en 6 horas
- Refresh token expira en 180 días
- Servicio debe refrescar automáticamente antes de cada llamada

---

## Fase 2 — Ventas: Recibir órdenes de ML

### 2.1 Webhook / Notificaciones
ML envía notificaciones a una URL pública:
```
POST /api/meli/webhook
  - topics: orders_v2, items, questions
```
Al recibir `orders_v2` con `status = paid`:
1. Obtener detalle de la orden via API
2. Identificar productos comprados (listing_id → producto local)
3. Validar que haya stock suficiente

### 2.2 Procesar orden (comando o job)
```
Por cada orden pagada:
  1. Crear PedidoOnline:
     - cliente: datos del comprador de ML
     - items: productos comprados
     - metodo_pago: mercadopago (ML paga con MP)
     - estado: pagado
     - direccion_envio: dirección que ML envía
  2. Crear pedido en CVA:
     - tipo_flete: FF (flete facturado por CVA)
     - flete: calle, numero, cp, colonia, estado, ciudad del cliente
     - productos: clave CVA + cantidad
     - Guardar cva_pedido_id
  3. Notificar al cliente con número de guía cuando CVA la asigne
```

### 2.3 Manejo de cancelaciones
- Si cancelan en ML → cancelar pedido en CVA
- Si CVA no puede surtir → cancelar en ML y reembolsar

### 2.4 Flujo de guía / tracking
- `SyncCvaOrders` (ya existe) actualiza guías de CVA automáticamente
- Cuando se asigna guía → actualizar ML con `shipments` y notificar al cliente

---

## Fase 3 — Logística: CVA envía al cliente final

### 3.1 Configuración de envío
- En el pedido a CVA, usar la dirección del comprador de ML
- Tipo de flete: `FF` (facturado por CVA, se paga en la factura)
- Paquetería: `PAQUETEXPRESS` (clave 4) — es la que soporta cotización automática
- CVA genera la guía y la asigna al pedido

### 3.2 Costo de envío
- El flete lo paga quien tú decidas:
  - **Tú lo absorbes**: lo incluyes en el precio del producto
  - **El cliente lo paga**: ML calcula el envío y se lo cobra al comprador
- ML tiene su propio cálculo de envíos (MercadoEnvíos). Si usas MercadoEnvíos, ML gestiona la logística y solo te conectas a CVA para el despacho.

### 3.3 Devoluciones
- ML tiene política de devolución de 30 días
- Si hay devolución, el producto regresa a ti, no a CVA
- Necesitas una estrategia: ¿recibes el producto? ¿lo reincorporas a tu stock?

---

## Fase 4 — Tablas en base de datos

```sql
-- Configuración de MercadoLibre por empresa
CREATE TABLE mercadolibre_config (
    id SERIAL PRIMARY KEY,
    empresa_id BIGINT REFERENCES empresas(id),
    app_id VARCHAR(255),
    client_secret TEXT,
    access_token TEXT,
    refresh_token TEXT,
    user_id BIGINT,
    expires_at TIMESTAMP,
    active BOOLEAN DEFAULT false,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Productos publicados en ML
CREATE TABLE mercadolibre_listings (
    id SERIAL PRIMARY KEY,
    empresa_id BIGINT REFERENCES empresas(id),
    producto_id BIGINT REFERENCES productos(id),
    listing_id BIGINT NOT NULL,        -- ID del item en ML
    permalink TEXT,                     -- URL del producto en ML
    status VARCHAR(50),                -- active, paused, closed
    price DECIMAL(15,2),
    stock_published INT DEFAULT 0,
    meli_category_id VARCHAR(50),
    last_sync_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Mapeo de categorías CVA → ML
CREATE TABLE meli_category_mappings (
    id SERIAL PRIMARY KEY,
    cva_grupo VARCHAR(255) NOT NULL,
    meli_category_id VARCHAR(50) NOT NULL,
    attributes_template JSONB,         -- atributos fijos por categoría
    created_at TIMESTAMP
);
```

---

## Fase 5 — Comandos Artisan

| Comando | Descripción | Frecuencia |
|---------|-------------|-----------|
| `meli:auth` | Obtener/refrescar token de ML | Cada 5 horas |
| `meli:sync-catalog` | Publicar productos nuevos en ML | Diario |
| `meli:sync-stock` | Actualizar stock/precios en ML | Cada 30 min |
| `meli:sync-orders` | Procesar órdenes nuevas de ML | Cada 5 min |
| `meli:sync-shipments` | Actualizar guías de envío | Cada 15 min |

---

## Resumen de esfuerzo estimado

| Fase | Días aprox | Dependencias |
|------|-----------|-------------|
| Fase 0: App ML + tablas | 1 día | Nada |
| Fase 1: Publicar catálogo | 3-4 días | Mapeo de categorías + imágenes |
| Fase 2: Recibir órdenes | 3-4 días | Webhook + procesar pedido CVA |
| Fase 3: Logística | 2 días | Probar con pedido real |
| Fase 4: Ajustes y QA | 2-3 días | Pruebas con test=1 en CVA |
| **Total** | **~11-14 días** | |

---

## Riesgos

- **Comisiones ML**: 10-16% + IVA. Con tus márgenes actuales, verifica que el pricing sea rentable después de comisión y flete.
- **Stock en tiempo real**: Si CVA se queda sin stock entre tus sincronizaciones, puedes vender algo que ya no tienen. ML penaliza las cancelaciones.
- **Imágenes**: ML requiere imágenes de alta calidad. El proxy de CVA puede fallar.
- **CVA no soporta dropshipping directamente**: Tendrás que poner la dirección del cliente final en la orden, lo cual es permitido pero CVA puede preguntar. Habla con tu ejecutivo de CVA para que autoricen el modelo.

---

¿Quieres que empiece con la Fase 0 (crear la app de MercadoLibre y las migraciones)?
