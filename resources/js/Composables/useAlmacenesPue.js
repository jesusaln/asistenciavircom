import { ref, watch } from 'vue';

export function useAlmacenesPue({ axios, almacenesList }) {
  const almacenes = ref([]);
  const selectedAlmacenId = ref('');

  const puePagado = ref(false);
  const pueMetodoPago = ref('transferencia');
  const pueCuentaBancariaId = ref('');
  const cuentasBancarias = ref([]);

  const fetchAlmacenes = async () => {
    try {
      const response = await axios.get('/api/almacenes');
      if (response.data && Array.isArray(response.data.data)) {
        almacenes.value = response.data.data;
      } else if (Array.isArray(response.data)) {
        almacenes.value = response.data;
      } else {
        almacenes.value = [];
      }

      if (almacenes.value.length > 0) {
        selectedAlmacenId.value = almacenes.value[0].id;
      }
    } catch (e) {
      console.error('Error cargando almacenes:', e);
      almacenes.value = [];
    }
  };

  const fetchCuentasBancarias = async () => {
    try {
      const response = await axios.get('/api/cuentas-bancarias/activas');
      if (Array.isArray(response.data)) {
        cuentasBancarias.value = response.data;
      } else if (response.data && Array.isArray(response.data.data)) {
        cuentasBancarias.value = response.data.data;
      } else {
        cuentasBancarias.value = [];
      }
    } catch (e) {
      console.error('Error cargando cuentas bancarias:', e);
    }
  };

  watch(almacenesList, (newVal) => {
    if (newVal && newVal.length > 0) {
      almacenes.value = newVal;
      if (!selectedAlmacenId.value && almacenes.value.length > 0) {
        selectedAlmacenId.value = almacenes.value[0].id;
      }
    }
  }, { immediate: true });

  return {
    almacenes,
    selectedAlmacenId,
    puePagado,
    pueMetodoPago,
    pueCuentaBancariaId,
    cuentasBancarias,
    fetchAlmacenes,
    fetchCuentasBancarias,
  };
}
