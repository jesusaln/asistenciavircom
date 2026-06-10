import { ref } from 'vue';

export function useBulkReview({
  axios,
  Swal,
  cfdiData,
  loading,
  error,
  selectedCfdis,
  selectedRfc,
  selectedProducts,
  receivedCfdis,
  fetchReceivedCfdis,
  handleCfdiLoaded,
  emit,
}) {
  const bulkImporting = ref(false);
  const bulkProgress = ref(0);
  const bulkReviewMode = ref(false);
  const bulkQueue = ref([]);
  const bulkCurrentIndex = ref(0);
  const bulkResults = ref({ success: [], skipped: [], errors: [] });

  const bulkImportCfdis = async () => {
    if (selectedCfdis.value.length === 0) return;

    bulkReviewMode.value = true;
    bulkQueue.value = [...selectedCfdis.value];
    bulkCurrentIndex.value = 0;
    bulkResults.value = { success: [], skipped: [], errors: [] };

    await loadCfdiForReview(bulkQueue.value[0]);
  };

  const loadCfdiForReview = async (cfdiId) => {
    loading.value = true;
    error.value = '';
    try {
      const response = await axios.get(`/compras/cfdi/${cfdiId}/preview`);
      await handleCfdiLoaded(response.data);
    } catch (err) {
      console.error('Error loading CFDI for review:', err);
      const errorMsg = err.response?.data?.message || err.message || 'Error desconocido';
      bulkResults.value.errors.push({
        id: cfdiId,
        folio: receivedCfdis.value.find(c => c.id === cfdiId)?.folio || cfdiId,
        error: errorMsg,
      });
      nextInBulkQueue();
    } finally {
      loading.value = false;
    }
  };

  const nextInBulkQueue = async () => {
    bulkCurrentIndex.value++;
    cfdiData.value = null;

    if (bulkCurrentIndex.value >= bulkQueue.value.length) {
      showBulkSummary();
    } else {
      await loadCfdiForReview(bulkQueue.value[bulkCurrentIndex.value]);
    }
  };

  const skipCurrentInBulk = () => {
    const cfdiId = bulkQueue.value[bulkCurrentIndex.value];
    const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
    bulkResults.value.skipped.push({
      id: cfdiId,
      folio: cfdi ? `${cfdi.serie}${cfdi.folio}` : cfdiId,
    });
    nextInBulkQueue();
  };

  const cancelBulkReview = () => {
    bulkReviewMode.value = false;
    bulkQueue.value = [];
    bulkCurrentIndex.value = 0;
    bulkResults.value = { success: [], skipped: [], errors: [] };
    cfdiData.value = null;
    selectedCfdis.value = [];
    selectedRfc.value = null;
    selectedProducts.value = [];
  };

  const showBulkSummary = () => {
    bulkReviewMode.value = false;
    cfdiData.value = null;

    const successCount = bulkResults.value.success.length;
    const skippedCount = bulkResults.value.skipped.length;
    const errorCount = bulkResults.value.errors.length;

    let message = `✅ Importación masiva completada:\n\n`;
    message += `• ${successCount} compra(s) creada(s)\n`;
    if (skippedCount > 0) {
      message += `• ${skippedCount} omitida(s)\n`;
    }
    if (errorCount > 0) {
      message += `• ${errorCount} error(es):\n`;
      bulkResults.value.errors.forEach(e => {
        message += `  - ${e.folio}: ${e.error}\n`;
      });
    }

    Swal.fire({
      icon: errorCount > 0 ? 'warning' : 'success',
      title: 'Resumen de Importación Masiva',
      text: message,
      confirmButtonColor: '#10B981',
    }).then(() => {
      fetchReceivedCfdis();

      if (successCount > 0) {
        emit('import', { compra_creada: true, count: successCount });
      }
    });

    selectedCfdis.value = [];
    selectedRfc.value = null;
    selectedProducts.value = [];
    bulkQueue.value = [];
    bulkCurrentIndex.value = 0;
    bulkResults.value = { success: [], skipped: [], errors: [] };

    fetchReceivedCfdis();
    emit('imported');

    if (successCount > 0) {
      window.location.reload();
    }
  };

  return {
    bulkImporting,
    bulkProgress,
    bulkReviewMode,
    bulkQueue,
    bulkCurrentIndex,
    bulkResults,
    bulkImportCfdis,
    loadCfdiForReview,
    nextInBulkQueue,
    skipCurrentInBulk,
    cancelBulkReview,
    showBulkSummary,
  };
}
