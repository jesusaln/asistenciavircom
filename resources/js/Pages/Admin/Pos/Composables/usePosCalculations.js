import { computed } from 'vue';
import { resolverPrecio } from '@/Utils/precioHelper';

export const usePosCalculations = ({ defaults, priceListId, selectedItems, amountReceived }) => {
    const formatCurrency = (val) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(val || 0);
    };

    const round2 = (val) => Math.round((Number(val || 0) + Number.EPSILON) * 100) / 100;
    const priceWithIva = (price) => round2(Number(price || 0) * (1 + (defaults.value.ivaPorcentaje / 100)));

    const getDisplayPrice = (item) => {
        const base = resolverPrecio(item, priceListId.value, { serviciosUsanListasPrecios: defaults.value?.serviciosUsanListasPrecios });
        return priceWithIva(base);
    };

    const totals = computed(() => {
        let subtotal = 0;
        selectedItems.value.forEach(item => {
            subtotal += item.cantidad * item.precio * (1 - (item.descuento / 100));
        });
        subtotal = round2(subtotal);
        const iva = round2(subtotal * (defaults.value.ivaPorcentaje / 100));
        const total = round2(subtotal + iva);
        return { subtotal, iva, total };
    });

    const change = computed(() => {
        return Math.max(0, round2(amountReceived.value - totals.value.total));
    });

    return {
        formatCurrency,
        round2,
        priceWithIva,
        getDisplayPrice,
        totals,
        change,
    };
};
