import { ref } from 'vue';

export function useCompraImport({
  axios,
  Swal,
  cfdiData,
  selectedAlmacenId,
  puePagado,
  pueMetodoPago,
  pueCuentaBancariaId,
  bulkReviewMode,
  bulkQueue,
  bulkCurrentIndex,
  bulkResults,
  nextInBulkQueue,
  receivedCfdis,
  emit,
  close,
  error,
  fetchReceivedCfdis,
}) {
  const creandoCompra = ref(false);

  const confirmarImportacion = async () => {
    if (!cfdiData.value) return;

    if (!selectedAlmacenId.value) {
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'Por favor selecciona un almacén destino.',
        confirmButtonColor: '#10B981',
      });
      return;
    }

    if (!cfdiData.value.proveedor_encontrado) {
      Swal.fire({
        icon: 'warning',
        title: 'Proveedor no registrado',
        text: 'Por favor registra el proveedor antes de continuar.',
        confirmButtonColor: '#10B981',
      });
      return;
    }

    const productosNoMapeados = cfdiData.value.conceptos.filter(c => !c.producto_id);
    if (productosNoMapeados.length > 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Productos sin vincular',
        text: `Hay ${productosNoMapeados.length} producto(s) sin mapear. Por favor agrega los productos faltantes antes de importar.`,
        confirmButtonColor: '#10B981',
      });
      return;
    }

    const productosSinSeries = cfdiData.value.conceptos.filter(c =>
      c.requiere_serie && (!c.seriales || c.seriales.length < c.cantidad)
    );

    if (productosSinSeries.length > 0) {
      const nombres = productosSinSeries.map(p => p.descripcion.substring(0, 30) + '...').join('<br>');
      Swal.fire({
        icon: 'warning',
        title: 'Series Faltantes',
        html: `Los siguientes productos requieren captura de series:<br><br><b>${nombres}</b><br><br>Por favor captura las series faltantes usando el botón en la columna \"Estatus\" antes de continuar.`,
        confirmButtonColor: '#10B981',
      });
      return;
    }

    if (cfdiData.value.metodo_pago === 'PUE' && puePagado.value) {
      if (!pueCuentaBancariaId.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Falta Cuenta Bancaria',
          text: 'Por favor selecciona una cuenta bancaria para el pago.',
          confirmButtonColor: '#10B981',
        });
        return;
      }
      if (!pueMetodoPago.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Falta Método de Pago',
          text: 'Por favor selecciona un método de pago.',
          confirmButtonColor: '#10B981',
        });
        return;
      }
    }

    creandoCompra.value = true;

    try {
      const productos = cfdiData.value.conceptos.map(concepto => ({
        id: concepto.producto_id,
        cantidad: parseInt(concepto.cantidad) || 1,
        precio: parseFloat(concepto.valor_unitario) || 0,
        descuento: 0,
        seriales: concepto.seriales || [],
      }));

      const payload = {
        proveedor_id: cfdiData.value.proveedor_encontrado.id,
        almacen_id: selectedAlmacenId.value,
        metodo_pago: cfdiData.value.metodo_pago || 'transferencia',
        productos: productos,
        descuento_general: cfdiData.value.descuento || 0,
        aplicar_retencion_iva: false,
        aplicar_retencion_isr: false,
        notas: `Importado desde CFDI ${cfdiData.value.serie || ''}${cfdiData.value.folio || ''} - UUID: ${cfdiData.value.uuid || ''}`,
        fecha_compra: cfdiData.value.fecha || cfdiData.value.fecha_emision,
        cfdi_uuid: cfdiData.value.uuid,
        cfdi_serie: cfdiData.value.serie,
        cfdi_folio: cfdiData.value.folio,
        cfdi_fecha: cfdiData.value.fecha || cfdiData.value.fecha_emision,
        cfdi_emisor_rfc: cfdiData.value.emisor?.rfc,
        cfdi_emisor_nombre: cfdiData.value.emisor?.nombre || cfdiData.value.emisor?.Nombre,
        cfdi_total: cfdiData.value.total,
        pagado_importacion: puePagado.value,
        pue_metodo_pago: pueMetodoPago.value,
        pue_cuenta_bancaria_id: pueCuentaBancariaId.value,
      };

      console.log('Creando compra con payload:', payload);

      const response = await axios.post(route('compras.store'), payload);

      if (bulkReviewMode.value) {
        const cfdiId = bulkQueue.value[bulkCurrentIndex.value];
        const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
        bulkResults.value.success.push({
          id: cfdiId,
          folio: cfdi ? `${cfdi.serie}${cfdi.folio}` : cfdiId,
          compra_id: response.data.compra?.id || response.data.id,
        });
        nextInBulkQueue();
      } else {
        emit('import', { compra_creada: true, ...response.data });
        close();
      }
    } catch (err) {
      console.error('Error al crear compra:', err);

      if (err.response?.data?.error_code === 'SALDO_INSUFICIENTE') {
        const d = err.response.data.details;
        Swal.fire({
          icon: 'error',
          title: `⚠️ Saldo Insuficiente en ${d.banco}`,
          html: `
            <div class="text-left mt-2">
              <p class="mb-1"><strong>💰 Disponible:</strong> $${d.disponible}</p>
              <p class="mb-1"><strong>📉 Requerido:</strong> $${d.requerido}</p>
              <p class="mb-3 text-red-600 font-bold"><strong>❗ Faltante:</strong> $${d.faltante}</p>
              <p class="text-sm text-gray-600">Por favor ingresa saldo a la cuenta o desmarca el pago automático.</p>
            </div>
          `,
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#EF4444',
        });
        error.value = '';
        return;
      }

      const errorMsg = err.response?.data?.message || err.response?.data?.error || err.message || 'Error desconocido al crear la compra';

      if (bulkReviewMode.value) {
        const cfdiId = bulkQueue.value[bulkCurrentIndex.value];
        const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
        bulkResults.value.errors.push({
          id: cfdiId,
          folio: cfdi ? `${cfdi.serie}${cfdi.folio}` : cfdiId,
          error: errorMsg,
        });
        nextInBulkQueue();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error al importar',
          text: errorMsg,
          confirmButtonColor: '#EF4444',
        });
        error.value = errorMsg;
      }
    } finally {
      creandoCompra.value = false;
    }
  };

  return { creandoCompra, confirmarImportacion };
}
