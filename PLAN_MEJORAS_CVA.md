# Plan de Mejoras e Integración Profunda con API CVA

Este documento detalla las fases propuestas para potenciar la tienda en línea utilizando al máximo las capacidades de la API de Grupo CVA.

## Fase 1: Experiencia de Búsqueda y Navegación Avanzada (Corto Plazo)
**Objetivo:** Ayudar al cliente a encontrar exactamente lo que busca entre los miles de productos disponibles.

1.  **Filtros de Especificaciones Técnicas (Ficha Técnica)**
    *   **Descripción:** Utilizar los datos de `informacion_tecnica.xml` o el detalle del producto para extraer atributos clave (Procesador, RAM, Almacenamiento, Tamaño de Pantalla) y crear filtros dinámicos en la barra lateral.
    *   **Beneficio:** Evita que el usuario tenga que navegar por 50 páginas de "Laptops".
    *   **Implementación:**
        *   Cachear los atributos más comunes por categoría.
        *   Interfaz de frontend con checkboxes dinámicos.

2.  **Búsqueda Inteligente y Autocompletado**
    *   **Descripción:** Mejorar la barra de búsqueda para que sugiera productos, categorías y marcas mientras el usuario escribe, consultando la API de CVA en tiempo real (con debounce).
    *   **Beneficio:** Agiliza el descubrimiento de productos.

3.  **Categorización Multinivel**
    *   **Descripción:** Sincronizar la estructura completa de "Grupos" y "Subgrupos" de CVA para crear un menú de navegación más rico y jerárquico.

## Fase 2: Logística y Disponibilidad en Tiempo Real (Mediano Plazo)
**Objetivo:** Dar certidumbre al cliente sobre cuándo recibirá su producto.

1.  **Disponibilidad por Sucursal Detallada**
    *   **Descripción:** Mostrar en la ficha del producto un desglose de dónde está el stock (ej. "5 en Guadalajara", "2 en CDMX").
    *   **Endpoint:** `/catalogo_clientes/sucursales` y datos de stock en `lista_precios`.
    *   **Beneficio:** Permite al cliente local saber si puede recibirlo el mismo día o si tardará.

2.  **Calculadora de Costo y Tiempo de Envío Real**
    *   **Descripción:** Utilizar el peso y dimensiones del producto (disponibles en la API) junto con el CP del cliente para cotizar el envío exacto usando paqueterías de CVA o propias.
    *   **Beneficio:** Evita cobrar de menos en el envío o asustar al cliente con costos fijos altos.

3.  **Filtrado por "Envío Inmediato"**
    *   **Descripción:** Un filtro ("Solo productos en mi ciudad") que muestre únicamente lo que está en la sucursal más cercana configurada.

## Fase 3: Automatización de Pedidos y Tracking (Largo Plazo)
**Objetivo:** Reducir la carga administrativa y mejorar la post-venta.

1.  **Dropshipping Automatizado (Pedido Web CVA)**
    *   **Descripción:** Cuando un cliente pague en la tienda, generar automáticamente la orden de compra en CVA con los datos de envío del cliente final.
    *   **Endpoint:** `/pedidos_web/crear_orden` (Ya existe código base, falta activar y probar en prod).
    *   **Beneficio:** Cero intervención manual para procesar ventas.

2.  **Sincronización de Estatus y Guías de Rastreo**
    *   **Descripción:** Un cron job que consulte periódicamente el estado de los pedidos en CVA. Cuando CVA asigne una guía (DHL/FedEx), actualizar el pedido local y notificar al cliente por correo/WhatsApp.
    *   **Endpoint:** `/pedidos_web/consulta_pedido`.
    *   **Beneficio:** Transparencia total para el cliente y menos llamadas de "¿dónde está mi pedido?".

3.  **Gestión de Garantías (RMA)**
    *   **Descripción:** Módulo para que el cliente pueda solicitar garantías desde su perfil, conectándose con el flujo de RMA de CVA si es posible, o al menos gestionando la logística de retorno.

## Fase 4: Herramientas de Marketing y Ventas (Valor Agregado)
**Objetivo:** Aumentar el ticket promedio y la conversión.

1.  **Sección de "Ofertas y Promociones CVA"**
    *   **Descripción:** Una página dedicada que lista automáticamente los productos que CVA tiene en promoción (flag `promocion` o `descuento` en la API).
    *   **Beneficio:** Atrae a cazadores de ofertas y mueve inventario rotativo.

2.  **Generador de Cotizaciones PDF (Marca Blanca)**
    *   **Descripción:** Botón en el producto para "Descargar Ficha PDF". Genera un documento con la foto, specs, precio (con margen de la tienda) y logo de la empresa, listo para que un vendedor lo envíe a un prospecto.

3.  **Bundles y "Comprados Juntos Habitualmente"**
    *   **Descripción:** Usar la API de "Productos Compatibles" (`productos_compatibles.xml`) para sugerir el tóner exacto para la impresora que están viendo, o el docking station para la laptop.
    *   **Beneficio:** Cross-selling automático y preciso.
