import { ref, computed, watch } from 'vue'

const CART_STORAGE_KEY = 'tienda_carrito'

// Estado global reactivo del carrito
const items = ref([])
const isLoaded = ref(false)

// Cargar carrito desde localStorage
const loadCart = () => {
    if (isLoaded.value) return

    try {
        const stored = localStorage.getItem(CART_STORAGE_KEY)
        if (stored) {
            items.value = JSON.parse(stored)
        }
    } catch (e) {
        console.error('Error loading cart:', e)
        items.value = []
    }
    isLoaded.value = true
}

// Guardar carrito en localStorage
const saveCart = () => {
    try {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items.value))
    } catch (e) {
        console.error('Error saving cart:', e)
    }
}

// Watch para auto-guardar
watch(items, saveCart, { deep: true })

export function useCart() {
    // Cargar al usar
    loadCart()

    // Computed properties
    const itemCount = computed(() => {
        return items.value.reduce((sum, item) => sum + item.cantidad, 0)
    })

    // Subtotal (precios que ya incluyen IVA)
    const subtotal = computed(() => {
        return items.value.reduce((sum, item) => sum + (item.precio * item.cantidad), 0)
    })

    // Subtotal sin IVA
    const subtotalSinIva = computed(() => {
        return items.value.reduce((sum, item) => {
            const precioBase = item.precio_sin_iva || (item.precio / 1.16)
            return sum + (precioBase * item.cantidad)
        }, 0)
    })

    // IVA calculado (16%)
    const iva = computed(() => {
        return subtotal.value - subtotalSinIva.value
    })

    // Total (igual a subtotal si ya incluye IVA)
    const total = computed(() => subtotal.value)

    const isEmpty = computed(() => items.value.length === 0)

    // Agregar producto al carrito
    // Retorna { success: boolean, message: string }
    const addItem = (producto, cantidad = 1) => {
        const stockDisponible = producto.stock || 0

        // Verificar si hay stock
        if (stockDisponible <= 0) {
            return { success: false, message: 'Producto sin stock disponible' }
        }

        const existingIndex = items.value.findIndex(item => item.producto_id === producto.id)
        let cantidadActual = 0

        if (existingIndex >= 0) {
            cantidadActual = items.value[existingIndex].cantidad
        }

        const cantidadTotal = cantidadActual + cantidad

        // Verificar si la cantidad total excede el stock
        if (cantidadTotal > stockDisponible) {
            const cantidadPosible = stockDisponible - cantidadActual
            if (cantidadPosible <= 0) {
                return { success: false, message: `Ya tienes el máximo disponible (${stockDisponible}) en tu carrito` }
            }
            // Agregar solo lo que se puede
            cantidad = cantidadPosible
        }

        if (existingIndex >= 0) {
            // Incrementar cantidad
            items.value[existingIndex].cantidad += cantidad
            items.value[existingIndex].stock = stockDisponible // Actualizar stock por si cambió
        } else {
            // Agregar nuevo
            items.value.push({
                producto_id: producto.id,
                nombre: producto.nombre,
                imagen: producto.imagen,
                precio: parseFloat(producto.precio || producto.precio_venta || 0),
                precio_sin_iva: parseFloat(producto.precio_sin_iva || producto.precio_venta || 0),
                cantidad: cantidad,
                stock: stockDisponible,
                peso: parseFloat(producto.peso || 0),
            })
        }

        return { success: true, message: cantidad < 1 ? 'Cantidad ajustada al stock disponible' : 'Producto agregado al carrito' }
    }

    // Quitar producto del carrito
    const removeItem = (productoId) => {
        const index = items.value.findIndex(item => item.producto_id === productoId)
        if (index >= 0) {
            items.value.splice(index, 1)
        }
    }

    // Actualizar cantidad
    const updateQuantity = (productoId, cantidad) => {
        const item = items.value.find(item => item.producto_id === productoId)
        if (item) {
            if (cantidad <= 0) {
                removeItem(productoId)
            } else {
                item.cantidad = Math.min(cantidad, item.stock)
            }
        }
    }

    // Incrementar cantidad
    const incrementQuantity = (productoId) => {
        const item = items.value.find(item => item.producto_id === productoId)
        if (item && item.cantidad < item.stock) {
            item.cantidad++
        }
    }

    // Decrementar cantidad
    const decrementQuantity = (productoId) => {
        const item = items.value.find(item => item.producto_id === productoId)
        if (item) {
            if (item.cantidad > 1) {
                item.cantidad--
            } else {
                removeItem(productoId)
            }
        }
    }

    // Vaciar carrito
    const clearCart = () => {
        items.value = []
    }

    // Sincronizar con el servidor (Validar Stock y Precios Reales)
    const syncWithServer = async () => {
        if (items.value.length === 0) return { valid: true }

        try {
            const response = await axios.post(route('tienda.carrito.validar'), {
                items: items.value.map(i => ({
                    producto_id: i.producto_id,
                    cantidad: i.cantidad
                }))
            })

            if (response.data && response.data.results) {
                let hasChanges = false
                const newItems = [...items.value]

                response.data.results.forEach(res => {
                    const idx = newItems.findIndex(i => i.producto_id === res.producto_id)
                    if (idx >= 0) {
                        const item = newItems[idx]

                        // Actualizar Precio si cambió
                        if (res.latest_price && Math.abs(item.precio - res.latest_price) > 0.01) {
                            item.precio = res.latest_price
                            item.precio_sin_iva = res.latest_price / 1.16
                            hasChanges = true
                        }

                        // Actualizar Stock si cambió o es insuficiente
                        if (res.latest_stock !== undefined && item.stock !== res.latest_stock) {
                            item.stock = res.latest_stock
                            if (item.cantidad > item.stock) {
                                item.cantidad = item.stock
                            }
                            hasChanges = true
                        }

                        // Si no se encontró, marcar para remover o alertar
                        if (res.status === 'not_found') {
                            newItems.splice(idx, 1)
                            hasChanges = true
                        }
                    }
                })

                if (hasChanges) {
                    items.value = newItems
                    saveCart()
                }

                return {
                    valid: response.data.valid,
                    results: response.data.results,
                    changed: hasChanges
                }
            }
        } catch (e) {
            console.error('Error syncing cart:', e)
            return { error: 'No se pudo validar el carrito con el servidor' }
        }
    }

    // Obtener item por ID
    const getItem = (productoId) => {
        return items.value.find(item => item.producto_id === productoId)
    }

    // Verificar si producto está en carrito
    const isInCart = (productoId) => {
        return items.value.some(item => item.producto_id === productoId)
    }

    // Obtener cantidad de un producto
    const getQuantity = (productoId) => {
        const item = items.value.find(item => item.producto_id === productoId)
        return item ? item.cantidad : 0
    }

    return {
        items,
        itemCount,
        subtotal,
        subtotalSinIva,
        iva,
        total,
        isEmpty,
        addItem,
        removeItem,
        updateQuantity,
        incrementQuantity,
        decrementQuantity,
        clearCart,
        syncWithServer,
        getItem,
        isInCart,
        getQuantity,
    }
}
