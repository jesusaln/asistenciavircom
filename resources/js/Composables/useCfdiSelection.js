import { ref, computed } from 'vue';

export function useCfdiSelection({ receivedCfdis, showImported, searchCfdi, Swal, axios }) {
  const selectedCfdis = ref([]);
  const selectedRfc = ref(null);
  const selectedProducts = ref([]);

  const displayedCfdis = computed(() => {
    let cfdis = receivedCfdis.value;

    if (!showImported.value) {
      cfdis = cfdis.filter(c => !c.importado);
    }

    if (selectedRfc.value) {
      cfdis = cfdis.filter(c => c.emisor_rfc === selectedRfc.value);
    }

    if (searchCfdi.value) {
      const search = searchCfdi.value.toLowerCase();
      cfdis = cfdis.filter(c =>
        (c.serie + c.folio).toLowerCase().includes(search) ||
        (c.emisor_nombre || '').toLowerCase().includes(search) ||
        (c.emisor_rfc || '').toLowerCase().includes(search)
      );
    }

    return cfdis;
  });

  const allSelected = computed(() => {
    return displayedCfdis.value.length > 0 && selectedCfdis.value.length === displayedCfdis.value.length;
  });

  const selectedEmisorNombre = computed(() => {
    if (!selectedRfc.value) return null;
    const cfdi = receivedCfdis.value.find(c => c.emisor_rfc === selectedRfc.value);
    return cfdi?.emisor_nombre || selectedRfc.value;
  });

  const totalSeleccionado = computed(() => {
    return receivedCfdis.value
      .filter(c => selectedCfdis.value.includes(c.id))
      .reduce((sum, c) => sum + (parseFloat(c.total) || 0), 0);
  });

  const canSelectCfdi = (cfdi) => {
    if (selectedCfdis.value.length === 0) return true;
    return cfdi.emisor_rfc === selectedRfc.value;
  };

  const fetchSelectedProducts = async () => {
    if (selectedCfdis.value.length === 0) {
      selectedProducts.value = [];
      return;
    }

    try {
      const response = await axios.post('/compras/get-cfdi-conceptos', {
        cfdi_ids: selectedCfdis.value,
      });
      selectedProducts.value = response.data.conceptos || [];
    } catch (err) {
      console.error('Error fetching conceptos:', err);
      selectedProducts.value = [];
    }
  };

  const toggleCfdiSelection = async (cfdiId) => {
    const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
    if (!cfdi || cfdi.importado) return;

    const index = selectedCfdis.value.indexOf(cfdiId);
    if (index === -1) {
      if (selectedCfdis.value.length === 0) {
        selectedRfc.value = cfdi.emisor_rfc;
      } else if (cfdi.emisor_rfc !== selectedRfc.value) {
        Swal.fire({
          icon: 'error',
          title: 'Emisor Diferente',
          text: `Solo puedes seleccionar CFDIs del mismo emisor.\nEmisor actual: ${selectedEmisorNombre.value}`,
          confirmButtonColor: '#EF4444',
        });
        return;
      }
      selectedCfdis.value.push(cfdiId);
    } else {
      selectedCfdis.value.splice(index, 1);
      if (selectedCfdis.value.length === 0) {
        selectedRfc.value = null;
        selectedProducts.value = [];
      }
    }

    await fetchSelectedProducts();
  };

  const toggleSelectAll = () => {
    if (allSelected.value) {
      selectedCfdis.value = [];
      selectedRfc.value = null;
      selectedProducts.value = [];
    } else {
      const targetRfc = selectedRfc.value || receivedCfdis.value.find(c => !c.importado)?.emisor_rfc;

      if (targetRfc) {
        selectedRfc.value = targetRfc;
        selectedCfdis.value = receivedCfdis.value
          .filter(cfdi => cfdi.emisor_rfc === targetRfc && !cfdi.importado)
          .map(cfdi => cfdi.id);
        fetchSelectedProducts();
      }
    }
  };

  return {
    selectedCfdis,
    selectedRfc,
    selectedProducts,
    displayedCfdis,
    allSelected,
    selectedEmisorNombre,
    totalSeleccionado,
    canSelectCfdi,
    fetchSelectedProducts,
    toggleCfdiSelection,
    toggleSelectAll,
  };
}
