import { ref, watch, onMounted } from 'vue';
import { debounce } from 'lodash';

export function useCfdiReceivedList({ axios, route, activeTab, searchCfdi, showImported, propsShow }) {
  const receivedCfdis = ref([]);
  const loadingCfdis = ref(false);

  const fetchReceivedCfdis = async () => {
    loadingCfdis.value = true;
    try {
      const oneYearAgo = new Date();
      oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
      const fechaInicio = oneYearAgo.toISOString().split('T')[0];

      const response = await axios.get(route('compras.received-cfdis'), {
        params: {
          search: searchCfdi.value,
          fecha_inicio: fechaInicio,
        },
      });
      receivedCfdis.value = response.data;
    } catch (e) {
      console.error('Error fetching received cfdis', e);
    } finally {
      loadingCfdis.value = false;
    }
  };

  const debounceSearch = debounce(() => {
    fetchReceivedCfdis();
  }, 500);

  watch(activeTab, (newVal) => {
    if (newVal === 'select') {
      fetchReceivedCfdis();
    }
  });

  watch(propsShow, (newVal) => {
    if (newVal && activeTab.value === 'select') {
      fetchReceivedCfdis();
    }
  });

  onMounted(async () => {
    if (activeTab.value === 'select') {
      fetchReceivedCfdis();
    }
  });

  watch(showImported, () => {
    if (activeTab.value === 'select') {
      fetchReceivedCfdis();
    }
  });

  return {
    receivedCfdis,
    loadingCfdis,
    fetchReceivedCfdis,
    debounceSearch,
  };
}
