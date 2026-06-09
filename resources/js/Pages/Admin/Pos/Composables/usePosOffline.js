import { ref, onMounted, onUnmounted } from 'vue';
import { get, set, del, keys } from 'idb-keyval';
import axios from 'axios';

const STORE_KEY = 'offline-sales';

const SERIES_STORE_KEY = 'offline-series';

export const usePosOffline = ({ notify }) => {
    const isOnline = ref(navigator.onLine);
    const isSyncing = ref(false);
    const pendingSalesCount = ref(0);

    const checkStatus = () => {
        isOnline.value = navigator.onLine;
    };

    const updatePendingCount = async () => {
        try {
            const allKeys = await keys();
            const salesKeys = allKeys.filter(k => k.toString().startsWith(STORE_KEY));
            pendingSalesCount.value = salesKeys.length;
        } catch (e) {
            console.error('Error counting offline sales', e);
        }
    };

    const saveSaleOffline = async (saleData) => {
        const id = `${STORE_KEY}-${Date.now()}`;
        try {
            // 🔥 Asegurar que los datos sean clonables (quitar Proxies de Vue)
            const serializedData = JSON.parse(JSON.stringify(saleData));
            const storageValue = {
                data: serializedData, // Almacenar en una propiedad específica para evitar problemas de spread
                offline_created_at: new Date().toISOString(),
                offline_id: id
            };

            await set(id, storageValue);
            notify.success('Venta guardada en modo OFFLINE. Se sincronizará al recuperar conexión.');
            await updatePendingCount();
            return true;
        } catch (e) {
            console.error('Error saving offline sale', e);
            notify.error('Error crítico guardando venta offline. Verifica almacenamiento.');
            return false;
        }
    };

    const saveSeriesToCache = async (productId, series) => {
        const key = `${SERIES_STORE_KEY}-${productId}`;
        try {
            const serializedSeries = JSON.parse(JSON.stringify(series));
            await set(key, {
                series: serializedSeries,
                cached_at: new Date().toISOString()
            });
            return true;
        } catch (e) {
            console.error('Error caching series', e);
            return false;
        }
    };

    const getSeriesFromCache = async (productId) => {
        const key = `${SERIES_STORE_KEY}-${productId}`;
        try {
            const data = await get(key);
            return data ? data.series : null;
        } catch (e) {
            console.error('Error getting cached series', e);
            return null;
        }
    };

    const syncSales = async () => {
        if (!isOnline.value || isSyncing.value) return;

        // Check if there are sales to sync
        const allKeys = await keys();
        const salesKeys = allKeys.filter(k => k.toString().startsWith(STORE_KEY));

        if (salesKeys.length === 0) {
            pendingSalesCount.value = 0;
            return;
        }

        isSyncing.value = true;
        let successCount = 0;
        let errorCount = 0;

        try {
            const sales = await Promise.all(salesKeys.map(key => get(key)));

            for (let i = 0; i < sales.length; i++) {
                const sale = sales[i];
                const key = salesKeys[i];

                try {
                    // Si el objeto tiene la nueva estructura { data: ... }
                    const saleData = sale.data || sale;
                    const payload = { ...saleData, is_offline_sync: true, offline_id: sale.offline_id };

                    const response = await axios.post(route('pos.checkout'), payload);

                    if (response.data?.success || response.status === 201) {
                        await del(key);
                        successCount++;
                    } else {
                        console.error('Sync failed for sale', key, response);
                        errorCount++;
                    }
                } catch (err) {
                    console.error('Sync error for sale', key, err);
                    // If 422 (Validation), maybe we should keep it or flag it?
                    // For now, keep it to avoid data loss.
                    errorCount++;
                }
            }

            if (successCount > 0) {
                notify.success(`Sincronizadas ${successCount} ventas offline éxitosamente.`);
            }
            if (errorCount > 0) {
                notify.warning(`Falló sincronización de ${errorCount} ventas. Se reintentará luego.`);
            }
        } catch (e) {
            console.error('Critical sync error', e);
        } finally {
            isSyncing.value = false;
            await updatePendingCount();
        }
    };

    onMounted(async () => {
        window.addEventListener('online', checkStatus);
        window.addEventListener('offline', checkStatus);
        window.addEventListener('online', syncSales);

        // Check pending on load
        await updatePendingCount();

        // Try sync on load if online
        if (isOnline.value) {
            syncSales();
        }
    });

    onUnmounted(() => {
        window.removeEventListener('online', checkStatus);
        window.removeEventListener('offline', checkStatus);
        window.removeEventListener('online', syncSales);
    });

    return {
        isOnline,
        isSyncing,
        pendingSalesCount,
        saveSaleOffline,
        saveSeriesToCache,
        getSeriesFromCache,
        syncSales
    };
};
