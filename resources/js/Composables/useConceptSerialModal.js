import { ref, computed } from 'vue';

export function useConceptSerialModal({ cfdiData, Swal }) {
  const showSerialModal = ref(false);
  const serialModalIndex = ref(null);
  const conceptSerialInput = ref('');
  const currentSerials = ref([]);

  const serialRequiredCantidad = computed(() => {
    if (serialModalIndex.value === null) return 0;
    return cfdiData.value?.conceptos?.[serialModalIndex.value]?.cantidad || 0;
  });

  const openSerialModal = (index) => {
    const concepto = cfdiData.value.conceptos[index];
    serialModalIndex.value = index;
    currentSerials.value = [...(concepto.seriales || [])];
    conceptSerialInput.value = '';
    showSerialModal.value = true;
  };

  const addConceptSerial = () => {
    const serial = conceptSerialInput.value.trim();
    if (!serial) return;

    const concepto = cfdiData.value.conceptos[serialModalIndex.value];
    if (!concepto.seriales) concepto.seriales = [];

    if (concepto.seriales.includes(serial)) {
      Swal.fire({
        icon: 'warning',
        title: 'Serie Duplicada',
        text: 'Este número de serie ya fue agregado.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
      });
      conceptSerialInput.value = '';
      return;
    }

    if (concepto.seriales.length >= concepto.cantidad) {
      Swal.fire({
        icon: 'info',
        title: 'Completo',
        text: `Solo se requieren ${concepto.cantidad} serie(s) para este producto.`,
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    concepto.seriales.push(serial);
    conceptSerialInput.value = '';
  };

  const removeConceptSerial = (index) => {
    currentSerials.value.splice(index, 1);
  };

  const closeSerialModal = (save = false) => {
    if (save && serialModalIndex.value !== null) {
      cfdiData.value.conceptos[serialModalIndex.value].seriales = [...currentSerials.value];
    }
    showSerialModal.value = false;
    serialModalIndex.value = null;
    currentSerials.value = [];
    conceptSerialInput.value = '';
  };

  return {
    showSerialModal,
    serialModalIndex,
    conceptSerialInput,
    currentSerials,
    serialRequiredCantidad,
    openSerialModal,
    addConceptSerial,
    removeConceptSerial,
    closeSerialModal,
  };
}
