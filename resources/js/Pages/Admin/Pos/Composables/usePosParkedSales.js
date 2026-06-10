import { ref, watch } from 'vue';

export function usePosParkedSales(notify) {
    const STORAGE_KEY = 'cdd_pos_parked_sales';
    const PARKED_TTL_MS = 43200000; // 12 horas en milisegundos
    const parkedSales = ref([]);

    // Cargar ventas guardadas al iniciar y limpiar expiradas
    const loadParkedSales = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                const allSales = JSON.parse(stored);
                const now = Date.now();
                // Filtrar ventas expiradas (más de 12 horas)
                parkedSales.value = allSales.filter(sale => {
                    const saleTime = new Date(sale.timestamp).getTime();
                    return (now - saleTime) < PARKED_TTL_MS;
                });
                // Si se eliminaron algunas, guardar la versión limpia
                if (parkedSales.value.length !== allSales.length) {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(parkedSales.value));
                }
            }
        } catch (e) {
            console.error('Error loading parked sales:', e);
            parkedSales.value = [];
        }
    };

    // Guardar cambios en localStorage
    watch(parkedSales, (newVal) => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
    }, { deep: true });

    const parkSale = (items, clientId, priceListId, clientName, totals, user) => {
        if (!items || items.length === 0) {
            notify?.error('No hay productos para poner en espera');
            return false;
        }

        const sale = {
            id: Date.now(), // ID único basado en timestamp
            timestamp: new Date().toISOString(),
            items: [...items], // Copia profunda simple
            clientId,
            priceListId,
            clientName: clientName || 'Cliente General',
            total: totals.total,
            itemCount: items.length,
            userVal: user?.name // Quien la guardó
        };

        parkedSales.value.unshift(sale); // Agregar al inicio
        notify?.success('Venta puesta en espera correctamente');
        return true;
    };

    const removeParkedSale = (index) => {
        parkedSales.value.splice(index, 1);
        notify?.success('Venta en espera eliminada');
    };

    const getParkedSale = (index) => {
        if (index < 0 || index >= parkedSales.value.length) return null;
        return parkedSales.value[index];
    };

    // Inicializar carga
    loadParkedSales();

    return {
        parkedSales,
        parkSale,
        removeParkedSale,
        getParkedSale
    };
}
