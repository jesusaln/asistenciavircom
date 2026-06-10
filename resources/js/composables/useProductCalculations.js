/**
 * Composable for product calculations
 * ✅ HIGH PRIORITY FIX #10: Refactorización de Create.vue
 */
export function useProductCalculations() {
    /**
     * Calculate item subtotal with discount
     */
    const calcularSubtotal = (cantidad, precio, descuento = 0) => {
        const subtotal = cantidad * precio;
        const descuentoMonto = subtotal * (descuento / 100);
        return subtotal - descuentoMonto;
    };

    /**
     * Calculate profit margin
     */
    const calcularMargen = (precioVenta, costo) => {
        if (costo === 0 || !costo) return 100;
        if (precioVenta <= 0) return 0;

        return ((precioVenta - costo) / precioVenta) * 100;
    };

    /**
     * Check if margin is below minimum
     */
    const validarMargen = (precioVenta, costo, margenMinimo = 10) => {
        const margen = calcularMargen(precioVenta, costo);

        return {
            margen,
            valido: margen >= margenMinimo,
            advertencia: margen < margenMinimo ?
                `Margen bajo: ${margen.toFixed(2)}% (mínimo: ${margenMinimo}%)` :
                null
        };
    };

    /**
     * Calculate discount amount
     */
    const calcularDescuentoMonto = (subtotal, descuentoPorcentaje) => {
        return subtotal * (descuentoPorcentaje / 100);
    };

    /**
     * Calculate price with discount
     */
    const calcularPrecioConDescuento = (precio, descuentoPorcentaje) => {
        return precio * (1 - descuentoPorcentaje / 100);
    };

    /**
     * Format currency
     */
    const formatearMoneda = (valor) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(valor);
    };

    /**
     * Format percentage
     */
    const formatearPorcentaje = (valor) => {
        return `${valor.toFixed(2)}%`;
    };

    return {
        calcularSubtotal,
        calcularMargen,
        validarMargen,
        calcularDescuentoMonto,
        calcularPrecioConDescuento,
        formatearMoneda,
        formatearPorcentaje,
    };
}
