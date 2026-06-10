import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

/**
 * Composable for managing sale form state and calculations
 * ✅ HIGH PRIORITY FIX #10: Refactorización de Create.vue
 */
export function useVentaForm(initialData = {}, config = { ivaPorcentaje: 16 }) {
    // Form state
    const form = useForm({
        numero_venta: initialData.numero_venta || '',
        fecha: initialData.fecha || new Date().toISOString().split('T')[0],
        cliente_id: initialData.cliente_id || null,
        almacen_id: initialData.almacen_id || null,
        metodo_pago: initialData.metodo_pago || 'efectivo',
        productos: initialData.productos || [],
        servicios: initialData.servicios || [],
        descuento_general: initialData.descuento_general || 0,
        notas: initialData.notas || '',
    });

    // Selected items state
    const selectedProducts = ref(initialData.selectedProducts || []);
    const selectedServices = ref(initialData.selectedServices || []);
    const quantities = ref(initialData.quantities || {});
    const prices = ref(initialData.prices || {});
    const discounts = ref(initialData.discounts || {});
    const serialsMap = ref(initialData.serialsMap || {});

    // Computed totals
    const subtotal = computed(() => {
        let total = 0;

        // Productos
        selectedProducts.value.forEach(producto => {
            const key = `producto_${producto.id}`;
            const cantidad = quantities.value[key] || 1;
            const precio = prices.value[key] || producto.precio_venta || 0;
            const descuento = discounts.value[key] || 0;

            const subtotalItem = cantidad * precio;
            const descuentoMonto = subtotalItem * (descuento / 100);
            total += subtotalItem - descuentoMonto;
        });

        // Servicios
        selectedServices.value.forEach(servicio => {
            const key = `servicio_${servicio.id}`;
            const cantidad = quantities.value[key] || 1;
            const precio = prices.value[key] || servicio.precio || 0;
            const descuento = discounts.value[key] || 0;

            const subtotalItem = cantidad * precio;
            const descuentoMonto = subtotalItem * (descuento / 100);
            total += subtotalItem - descuentoMonto;
        });

        return total;
    });

    const descuentoGeneralMonto = computed(() => {
        return parseFloat(form.descuento_general) || 0;
    });

    const subtotalDespuesDescuento = computed(() => {
        return subtotal.value - descuentoGeneralMonto.value;
    });

    const iva = computed(() => {
        const ivaRate = (config.ivaPorcentaje ?? 16) / 100;
        return subtotalDespuesDescuento.value * ivaRate;
    });

    const total = computed(() => {
        return subtotalDespuesDescuento.value + iva.value;
    });

    // Methods
    const agregarProducto = (producto) => {
        const key = `producto_${producto.id}`;

        if (!selectedProducts.value.find(p => p.id === producto.id)) {
            selectedProducts.value.push(producto);
            quantities.value[key] = 1;
            prices.value[key] = producto.precio_venta || 0;
            discounts.value[key] = 0;

            if (producto.maneja_series) {
                serialsMap.value[key] = [];
            }
        }
    };

    const eliminarProducto = (productoId) => {
        const key = `producto_${productoId}`;
        selectedProducts.value = selectedProducts.value.filter(p => p.id !== productoId);
        delete quantities.value[key];
        delete prices.value[key];
        delete discounts.value[key];
        delete serialsMap.value[key];
    };

    const agregarServicio = (servicio) => {
        const key = `servicio_${servicio.id}`;

        if (!selectedServices.value.find(s => s.id === servicio.id)) {
            selectedServices.value.push(servicio);
            quantities.value[key] = 1;
            prices.value[key] = servicio.precio || 0;
            discounts.value[key] = 0;
        }
    };

    const eliminarServicio = (servicioId) => {
        const key = `servicio_${servicioId}`;
        selectedServices.value = selectedServices.value.filter(s => s.id !== servicioId);
        delete quantities.value[key];
        delete prices.value[key];
        delete discounts.value[key];
    };

    const prepararDatosParaEnvio = () => {
        // Preparar productos
        form.productos = selectedProducts.value.map(producto => {
            const key = `producto_${producto.id}`;
            const data = {
                id: producto.id,
                cantidad: quantities.value[key] || 1,
                precio: prices.value[key] || producto.precio_venta || 0,
                descuento: discounts.value[key] || 0,
            };

            if (producto.maneja_series && serialsMap.value[key]) {
                data.series = serialsMap.value[key];
            }

            return data;
        });

        // Preparar servicios
        form.servicios = selectedServices.value.map(servicio => {
            const key = `servicio_${servicio.id}`;
            return {
                id: servicio.id,
                cantidad: quantities.value[key] || 1,
                precio: prices.value[key] || servicio.precio || 0,
                descuento: discounts.value[key] || 0,
            };
        });
    };

    const resetForm = () => {
        selectedProducts.value = [];
        selectedServices.value = [];
        quantities.value = {};
        prices.value = {};
        discounts.value = {};
        serialsMap.value = {};
        form.reset();
    };

    return {
        // State
        form,
        selectedProducts,
        selectedServices,
        quantities,
        prices,
        discounts,
        serialsMap,

        // Computed
        subtotal,
        descuentoGeneralMonto,
        subtotalDespuesDescuento,
        iva,
        total,

        // Methods
        agregarProducto,
        eliminarProducto,
        agregarServicio,
        eliminarServicio,
        prepararDatosParaEnvio,
        resetForm,
    };
}
