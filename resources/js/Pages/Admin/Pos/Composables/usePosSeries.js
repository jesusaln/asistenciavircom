import { ref, computed } from 'vue';
import axios from 'axios';

export const usePosSeries = ({ almacenId, notify, addItem, saveSeriesToCache, getSeriesFromCache }) => {
    const showSeriesModal = ref(false);
    const availableSeries = ref([]);
    const seriesSearch = ref('');
    const selectedSeries = ref([]);
    const productForSeries = ref(null);
    const loadingSeries = ref(false);
    const seriesError = ref(null);

    const openSeriesModal = async (item) => {
        productForSeries.value = item;
        selectedSeries.value = [];
        seriesSearch.value = '';
        showSeriesModal.value = true;
        loadingSeries.value = true;
        seriesError.value = null;

        try {
            // Intentar cargar del servidor si hay conexión
            if (navigator.onLine) {
                const response = await axios.get(route('pos.productos.series', item.id), {
                    params: { almacen_id: almacenId.value }
                });
                availableSeries.value = response.data;
                // Guardar en caché para uso offline
                await saveSeriesToCache(item.id, response.data);
            } else {
                // Si estamos offline, intentar recuperar de la caché
                const cached = await getSeriesFromCache(item.id);
                if (cached) {
                    availableSeries.value = cached;
                    notify.info('Cargando series desde la memoria local (Offline)');
                } else {
                    seriesError.value = '⚠️ MODO OFFLINE: No hay series guardadas en caché para este producto.';
                }
            }
        } catch (error) {
            console.error('Error al cargar series:', error);
            // Si falla la red (pero navigator.onLine es true), intentar caché
            const cached = await getSeriesFromCache(item.id);
            if (cached) {
                availableSeries.value = cached;
                notify.warning('Error de red. Usando series en caché local.');
            } else if (!navigator.onLine || error.code === 'ERR_NETWORK' || error.message?.includes('Network Error')) {
                seriesError.value = '⚠️ MODO OFFLINE: La consulta de series requiere conexión o caché previa.';
            } else {
                seriesError.value = error.response?.data?.message || 'No se pudieron cargar las series del servidor.';
            }
        } finally {
            loadingSeries.value = false;
        }
    };

    const retrySeries = () => {
        if (productForSeries.value) {
            openSeriesModal(productForSeries.value);
        }
    };

    const filteredSeries = computed(() => {
        const q = seriesSearch.value.trim().toLowerCase();
        if (!q) return availableSeries.value;
        return availableSeries.value.filter(s =>
            s.numero_serie.toLowerCase().includes(q) ||
            (s.lote && s.lote.toLowerCase().includes(q))
        );
    });

    const toggleSerie = (serie) => {
        const idx = selectedSeries.value.findIndex(s => s.id === serie.id);
        if (idx >= 0) {
            selectedSeries.value.splice(idx, 1);
        } else {
            selectedSeries.value.push(serie);
        }
    };

    const confirmSeriesSelection = () => {
        if (selectedSeries.value.length === 0) {
            notify.warning('Selecciona al menos una serie');
            return;
        }

        addItem(productForSeries.value, 'producto', selectedSeries.value);
        showSeriesModal.value = false;
        productForSeries.value = null;
        selectedSeries.value = [];
    };

    return {
        showSeriesModal,
        availableSeries,
        seriesSearch,
        selectedSeries,
        productForSeries,
        loadingSeries,
        seriesError,
        openSeriesModal,
        retrySeries,
        filteredSeries,
        toggleSerie,
        confirmSeriesSelection,
    };
};
