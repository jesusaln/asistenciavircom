import { ref, computed } from 'vue';

/**
 * Composable for managing series selection
 * ✅ HIGH PRIORITY FIX #10: Refactorización de Create.vue
 */
export function useSeriesSelector() {
    const showSeriesPicker = ref(false);
    const pickerKey = ref('');
    const pickerProducto = ref(null);
    const pickerSeries = ref([]);
    const pickerSearch = ref('');
    const selectedSeries = ref([]);

    const pickerRequired = computed(() => {
        if (!pickerKey.value) return 0;
        const match = pickerKey.value.match(/producto_(\d+)/);
        if (!match) return 0;

        // Obtener cantidad requerida del contexto
        return pickerProducto.value?.cantidad || 0;
    });

    const filteredSeries = computed(() => {
        if (!pickerSearch.value) return pickerSeries.value;

        const search = pickerSearch.value.toLowerCase();
        return pickerSeries.value.filter(serie =>
            serie.numero_serie.toLowerCase().includes(search)
        );
    });

    const openSeriesPicker = (key, producto, seriesDisponibles, almacenId, currentSeries = []) => {
        pickerKey.value = key;
        pickerProducto.value = producto;

        // Filtrar series por almacén
        let seriesFiltradas = seriesDisponibles.filter(s =>
            s.producto_id === producto.id &&
            s.estado === 'en_stock'
        );

        if (almacenId) {
            seriesFiltradas = seriesFiltradas.filter(s =>
                String(s.almacen_id) === String(almacenId)
            );
        }

        pickerSeries.value = seriesFiltradas;
        selectedSeries.value = [...currentSeries];
        pickerSearch.value = '';
        showSeriesPicker.value = true;
    };

    const toggleSerie = (numeroSerie) => {
        const index = selectedSeries.value.indexOf(numeroSerie);

        if (index > -1) {
            selectedSeries.value.splice(index, 1);
        } else {
            selectedSeries.value.push(numeroSerie);
        }
    };

    const confirmSeries = (callback) => {
        const cantidad = pickerProducto.value?.cantidad || 0;

        if (selectedSeries.value.length !== cantidad) {
            return {
                valid: false,
                error: `Debes seleccionar exactamente ${cantidad} serie(s). Seleccionadas: ${selectedSeries.value.length}`
            };
        }

        if (callback) {
            callback(pickerKey.value, selectedSeries.value);
        }

        closePicker();
        return { valid: true };
    };

    const closePicker = () => {
        showSeriesPicker.value = false;
        pickerKey.value = '';
        pickerProducto.value = null;
        pickerSeries.value = [];
        selectedSeries.value = [];
        pickerSearch.value = '';
    };

    return {
        // State
        showSeriesPicker,
        pickerKey,
        pickerProducto,
        pickerSeries,
        pickerSearch,
        selectedSeries,

        // Computed
        pickerRequired,
        filteredSeries,

        // Methods
        openSeriesPicker,
        toggleSerie,
        confirmSeries,
        closePicker,
    };
}
