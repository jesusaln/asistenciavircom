/**
 * Helper para resolver precios de productos según lista de precios
 */

/**
 * Resuelve el precio de un producto según la lista de precios seleccionada
 * @param {Object} producto - Producto con precios_listas
 * @param {Number|null} priceListId - ID de la lista de precios
 * @returns {Number} - Precio resuelto
 */
export function resolverPrecio(producto, priceListId) {
    // Si es un servicio, usar precio base (servicios no usan listas por ahora)
    if (producto.tipo === 'servicio') {
        return parseFloat(producto.precio || producto.precio_venta || 0);
    }

    // Si no hay lista seleccionada, usar precio base
    if (!priceListId || !producto.precios_listas) {
        return parseFloat(producto.precio_venta || producto.precio || 0);
    }

    // Buscar precio en la lista seleccionada
    const precioLista = producto.precios_listas[priceListId];
    if (precioLista !== undefined && precioLista !== null) {
        return parseFloat(precioLista);
    }

    // Fallback: precio base
    return parseFloat(producto.precio_venta || producto.precio || 0);
}

/**
 * Resuelve el precio de un producto con información de fallback
 * @param {Object} producto - Producto con precios_listas
 * @param {Number|null} priceListId - ID de la lista de precios
 * @returns {Object} - { precio: Number, esFallback: Boolean }
 */
export function resolverPrecioConFallbackInfo(producto, priceListId) {
    // Si es un servicio, usar precio base (no es fallback para servicios)
    if (producto.tipo === 'servicio') {
        return {
            precio: parseFloat(producto.precio || producto.precio_venta || 0),
            esFallback: false
        };
    }

    // Si no hay lista seleccionada, no se considera fallback
    if (!priceListId || !producto.precios_listas) {
        return {
            precio: parseFloat(producto.precio_venta || producto.precio || 0),
            esFallback: false
        };
    }

    // Buscar precio en la lista seleccionada
    const precioLista = producto.precios_listas[priceListId];
    if (precioLista !== undefined && precioLista !== null) {
        return {
            precio: parseFloat(precioLista),
            esFallback: false
        };
    }

    // Fallback: precio base - ESTO ES UN FALLBACK
    return {
        precio: parseFloat(producto.precio_venta || producto.precio || 0),
        esFallback: true
    };
}

/**
 * Detecta productos que no tienen precio definido en la lista seleccionada
 * @param {Array} productos - Lista de productos seleccionados (con id, tipo, nombre)
 * @param {Array} catalogoProductos - Catálogo completo de productos con precios_listas
 * @param {Number|null} priceListId - ID de la lista de precios seleccionada
 * @returns {Array} - Lista de productos sin precio en la lista [{id, nombre}]
 */
export function detectarProductosSinPrecioEnLista(productos, catalogoProductos, priceListId) {
    // Si no hay lista seleccionada, no hay fallbacks que reportar
    if (!priceListId) {
        return [];
    }

    const productosSinPrecio = [];

    for (const item of productos) {
        // Solo aplicar a productos, no a servicios
        if (item.tipo !== 'producto') {
            continue;
        }

        // Buscar el producto en el catálogo
        const catalogo = catalogoProductos.find(p => p.id === item.id);
        if (!catalogo) {
            continue;
        }

        // Verificar si tiene precio en la lista
        const precioLista = catalogo.precios_listas?.[priceListId];
        if (precioLista === undefined || precioLista === null) {
            productosSinPrecio.push({
                id: item.id,
                nombre: item.nombre || catalogo.nombre,
                precioUsado: catalogo.precio_venta || 0
            });
        }
    }

    return productosSinPrecio;
}

/**
 * Formatea un precio para mostrar en la UI
 * @param {Number} precio - Precio a formatear
 * @returns {String} - Precio formateado
 */
export function formatearPrecio(precio) {
    const precioNum = parseFloat(precio) || 0;
    return precioNum.toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
