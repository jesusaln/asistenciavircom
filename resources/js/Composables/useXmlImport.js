export function useXmlImport({
  axios,
  cfdiData,
  loading,
  error,
  isDragging,
  uploadInputKey,
  newProviderEmail,
  newProviderPhone,
  puePagado,
  pueMetodoPago,
  pueCuentaBancariaId,
  cuentasBancarias,
  fetchCuentasBancarias,
}) {
  const resetUpload = () => {
    cfdiData.value = null;
    error.value = '';
    loading.value = false;
    uploadInputKey.value += 1;
    newProviderEmail.value = '';
    newProviderPhone.value = '';
    puePagado.value = false;
    pueMetodoPago.value = 'transferencia';
    pueCuentaBancariaId.value = '';
  };

  const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
      processFile(file);
    }
  };

  const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
      processFile(file);
    }
  };

  async function handleCfdiLoaded(data) {
    cfdiData.value = data;

    puePagado.value = false;
    pueMetodoPago.value = 'transferencia';
    pueCuentaBancariaId.value = '';

    if (data?.metodo_pago === 'PUE') {
      puePagado.value = true;

      if (cuentasBancarias.value.length === 0) {
        await fetchCuentasBancarias();
      }

      if (cuentasBancarias.value.length > 0) {
        pueCuentaBancariaId.value = cuentasBancarias.value[0].id;
      }

      if (data.forma_pago) {
        const mapping = {
          '01': 'efectivo',
          '02': 'cheque',
          '03': 'transferencia',
          '04': 'tarjeta',
          '28': 'tarjeta',
        };
        const fp = data.forma_pago.substring(0, 2);
        pueMetodoPago.value = mapping[fp] || 'transferencia';
      }
    }
  }

  const processFile = async (file) => {
    if (!file.name.toLowerCase().endsWith('.xml')) {
      error.value = 'El archivo debe tener extensión .xml';
      return;
    }

    loading.value = true;
    error.value = '';

    try {
      const formData = new FormData();
      formData.append('xml_file', file);

      const response = await axios.post('/compras/parse-xml', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (response.data.success) {
        await handleCfdiLoaded(response.data.data);
      } else {
        error.value = response.data.message || 'Error al procesar el XML';
      }
    } catch (err) {
      console.error('Error al procesar XML:', err);
      error.value = err.response?.data?.message || 'Error al procesar el archivo XML';
    } finally {
      loading.value = false;
    }
  };

  return {
    resetUpload,
    handleFileSelect,
    handleDrop,
    handleCfdiLoaded,
    processFile,
  };
}
