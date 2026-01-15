# Plan de Optimización de Integración CVA

Este documento detalla las fases para convertir la integración de CVA en un sistema de e-commerce robusto, escalable y optimizado para SEO.

## Fase 1: Fiabilidad y Precisión en Tiempo Real (Prioridad Alta)
*   **Validación Crítica en Checkout:** Implementar un middleware o validación en el `CarritoController` que consulte el stock real en CVA justo antes de procesar el pago.
*   **Sincronización de Precios Dinámica:** Asegurar que el precio mostrado en el detalle del producto sea el más reciente (actualizar el modelo local al entrar a la vista de detalle).
*   **Mejora del Proxy de Imágenes:** Implementar un fallback (imagen por defecto) si la URL de CVA falla o no existe.

## Fase 2: Escalabilidad y Rendimiento (Infraestructura)
*   **Sincronización por Colas (Laravel Jobs):** Migrar `SyncCVACatalog` a un sistema de Jobs para procesar los 12,000 productos en bloques, evitando bloqueos del servidor y mejorando la estabilidad.
*   **Almacenamiento Local de Imágenes (Lazy Download):** Almacenar permanentemente las imágenes en el servidor local la primera vez que se solicitan, optimizándolas a formato WebP.
*   **Búsqueda Optimizada:** Indexar el catálogo local sincronizado para búsquedas instantáneas (In-database fulltext o Meilisearch).

## Fase 3: Lógica de Negocio y Logística
*   **Márgenes por Categoría/Marca:** Crear una interfaz en `EmpresaConfiguracion` para definir reglas de precios personalizadas.
*   **Cálculo de Envío Avanzado:** Utilizar las dimensiones y peso proporcionados por CVA para una cotización de paquetería más exacta mediante APIs de logística.

## Fase 4: Crecimiento y SEO
*   **SEO Dinámico:** Generación automática de Meta Tags (Title, Description, OpenGraph) para los 12,000 productos sincronizados.
*   **Generador de Sitemap:** Incluir automáticamente las rutas de productos CVA en el `sitemap.xml` para indexación masiva en Google.

---

# Estado de Implementación: Fase 1 en Curso

### Paso 1: Validación en Tiempo Real en el Carrito
**Objetivo:** Evitar que un cliente compre un producto que se agotó en CVA entre la sincronización y la compra.
