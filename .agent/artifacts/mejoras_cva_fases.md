# Plan de Mejoras CVA - Implementación por Fases

> **Fecha de creación:** 2026-01-13
> **Estado:** ✅ Implementado
> **Aplicación:** ASISTENCIA VIRCOM - Tienda en Línea

---

## 📋 Resumen Ejecutivo

Este documento describe el plan de implementación de mejoras para la integración con CVA, basado en la documentación oficial del Web Service. Las mejoras se implementarán en 5 fases para minimizar riesgos y permitir pruebas incrementales.

### 🔄 Funcionalidad Adicional: Auto-Sincronización

**Cuando se vende un producto CVA:**
- El producto se guarda automáticamente en la base de datos local
- Se crea la categoría y marca si no existen
- El producto queda disponible para pedidos, ventas, cotizaciones, etc.

**Endpoints de sincronización:**
- `POST /api/tienda/cva/sync-local` - Sincroniza un producto CVA individual
- `POST /api/tienda/cva/sync-categorias` - Importa todas las categorías de CVA
- `POST /api/tienda/cva/sync-marcas` - Importa todas las marcas de CVA


## 🎯 Fases de Implementación

### FASE 1: Imágenes de Alta Calidad ⭐
**Estado:** 🔄 En progreso
**Impacto:** Visual premium, mejor experiencia de usuario

#### Objetivo
Obtener múltiples imágenes en alta resolución para cada producto CVA.

#### Endpoint
```
GET /catalogo_clientes_xml/imagenes_alta.xml?cliente={ID}&clave={CLAVE}
```

#### Cambios requeridos
- [ ] Crear método `getHighResImages($clave)` en `CVAService.php`
- [ ] Modificar `normalizeProduct()` para incluir imágenes HD
- [ ] Actualizar `Show.vue` para mostrar galería de imágenes
- [ ] Implementar caché de imágenes (24 horas)

#### Ejemplo de respuesta esperada
```xml
<producto>
  <imagen>http://www.grupocva.com/detalle_articulo/img_large.php?id=108475</imagen>
  <imagen>http://www.grupocva.com/detalle_articulo/img_large.php?id=108476</imagen>
</producto>
```

---

### FASE 2: Productos Compatibles/Similares 🔗
**Estado:** ⏳ Pendiente
**Impacto:** Aumenta ventas cruzadas, mejor navegación

#### Objetivo
Mostrar productos relacionados en la página de detalle del producto.

#### Endpoint
```
GET /catalogo_clientes_xml/productos_compatibles.xml?clave={CLAVE}
```

#### Cambios requeridos
- [ ] Crear método `getCompatibleProducts($clave)` en `CVAService.php`
- [ ] Agregar sección "Productos Compatibles" en `Show.vue`
- [ ] Implementar carrusel horizontal de productos relacionados
- [ ] Caché de productos compatibles (2 horas)

#### Ubicación en UI
- Debajo de la descripción del producto
- Título: "Productos que te pueden interesar" o "Compatibles con este producto"

---

### FASE 3: Información Técnica Desglosada 📋
**Estado:** ⏳ Pendiente
**Impacto:** Mejor UX, información estructurada

#### Objetivo
Mostrar especificaciones técnicas en formato de tabla estructurada.

#### Endpoint
```
GET /catalogo_clientes_xml/informacion_tecnica.xml?cliente={ID}&clave={CLAVE}
```

#### Cambios requeridos
- [ ] Crear método `getTechnicalSpecs($clave)` en `CVAService.php`
- [ ] Parsear respuesta XML a array de especificaciones
- [ ] Crear componente `TechnicalSpecs.vue` con tabla estilizada
- [ ] Integrar en `Show.vue` como sección colapsable

#### Ejemplo de datos
| Característica | Valor |
|----------------|-------|
| Resolución | 1920 x 1080 |
| Color | Negro |
| Conectividad | HDMI, USB |
| Garantía | 2 años |

---

### FASE 4: Tipo de Producto (Por Salir/Liquidación) ⚠️
**Estado:** ⏳ Pendiente
**Impacto:** Generación de urgencia, liquidación de inventario

#### Objetivo
Identificar y destacar productos en liquidación o últimas unidades.

#### Parámetro
```
tipo=1 → Retorna <TipoProducto>NORMAL</TipoProducto> o <TipoProducto>POR SALIR</TipoProducto>
depto=1 → Retorna clasificación A, B, C, POR SALIR, SPF
```

#### Cambios requeridos
- [ ] Agregar parámetros `tipo=1` y `depto=1` a consultas CVA
- [ ] Modificar `normalizeProduct()` para incluir `tipo_producto` y `departamento`
- [ ] Crear badge "🔥 Últimas Unidades" para productos POR SALIR
- [ ] Opción de filtro: "Solo liquidaciones"

#### Clasificación de departamentos
| Código | Significado | Badge sugerido |
|--------|-------------|----------------|
| A | Ventas altas, stock regular | ✅ Disponible |
| B | Ventas medias | ✅ Disponible |
| C | Ventas bajas, inventario bajo | ⚠️ Pocas unidades |
| POR SALIR | Descontinuado | 🔥 Últimas unidades |
| SPF | Bajo pedido | 📦 Sobre pedido |

---

### FASE 5: Disponibilidad por Sucursal 📍
**Estado:** ⏳ Pendiente
**Impacto:** Útil para clientes regionales, mejor información de entrega

#### Objetivo
Mostrar disponibilidad del producto en diferentes sucursales de CVA en México.

#### Parámetros
```
sucursales=1 → Muestra existencias por sucursal
TotalSuc=1 → Suma total de existencias
```

#### Cambios requeridos
- [ ] Agregar parámetro `sucursales=1` al detalle de producto
- [ ] Crear método `parseBranchAvailability($item)` en `CVAService.php`
- [ ] Crear componente `BranchAvailability.vue` con mapa/lista
- [ ] Mostrar sucursal más cercana con base en ubicación del cliente (futuro)

#### Sucursales CVA disponibles
- Guadalajara, Monterrey, CDMX, Tijuana, Cancún, Mérida
- Hermosillo, Culiacán, Chihuahua, Puebla, Querétaro
- Y más...

#### UI sugerida
```
📍 Disponibilidad:
  • CDMX Taller: 42 unidades
  • Monterrey: 5 unidades  
  • Guadalajara: 3 unidades
  → Total nacional: 50 unidades
```

---

## 📅 Cronograma Estimado

| Fase | Descripción | Duración estimada | Estado |
|------|-------------|-------------------|--------|
| 1 | Imágenes Alta Calidad | 30 min | 🔄 En progreso |
| 2 | Productos Compatibles | 45 min | ⏳ Pendiente |
| 3 | Info Técnica | 30 min | ⏳ Pendiente |
| 4 | Tipo Producto | 20 min | ⏳ Pendiente |
| 5 | Disponibilidad Sucursal | 45 min | ⏳ Pendiente |

**Total estimado:** ~3 horas

---

## 🔧 Archivos a Modificar

### Backend (PHP/Laravel)
- `app/Services/CVAService.php` - Nuevos métodos para cada endpoint
- `app/Http/Controllers/CatalogoController.php` - Pasar datos al frontend
- `app/Http/Controllers/Tienda/CVAProxyController.php` - Nuevos endpoints API

### Frontend (Vue.js)
- `resources/js/Pages/Catalogo/Show.vue` - Integrar nuevas secciones
- `resources/js/Components/TechnicalSpecs.vue` - Nuevo componente (Fase 3)
- `resources/js/Components/BranchAvailability.vue` - Nuevo componente (Fase 5)
- `resources/js/Components/RelatedProducts.vue` - Nuevo componente (Fase 2)

---

## 📝 Notas Importantes

1. **Caché:** Todas las consultas adicionales deben usar caché para no saturar la API de CVA
2. **Fallbacks:** Si una consulta falla, mostrar el producto sin esa información extra
3. **XML vs JSON:** Algunos endpoints de CVA solo devuelven XML, necesitamos parsear
4. **Rate Limiting:** No hacer más de 1 consulta por segundo a CVA

---

## ✅ Checklist de Despliegue

Para cada fase:
- [ ] Implementar cambios en local
- [ ] Probar con productos reales de CVA
- [ ] Compilar frontend (`npm run build`)
- [ ] Subir a producción (`rsync`)
- [ ] Limpiar caché (`php artisan cache:clear`)
- [ ] Verificar en producción
- [ ] Marcar fase como completada

---

*Documento generado automáticamente. Última actualización: 2026-01-13*
