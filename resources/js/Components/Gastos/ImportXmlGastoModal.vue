<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="close"></div>

      <!-- Modal -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-5xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl transform transition-all">
          <!-- Header -->
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4 rounded-t-xl">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Importar Gasto desde XML (CFDI)
              </h3>
              <button @click="close" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6">
            <!-- Estado: Subir archivo -->
            <div v-if="!cfdiData && !loading">
              <div
                class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-indigo-400 transition-colors cursor-pointer"
                :class="{ 'border-amber-500 bg-indigo-50': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <input
                  ref="fileInput"
                  type="file"
                  accept=".xml"
                  class="hidden"
                  @change="handleFileSelect"
                />
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-lg font-medium text-gray-700 mb-2">
                  Arrastra tu archivo XML aquí
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  o haz clic para seleccionar
                </p>
                <p class="text-xs text-gray-400 mt-2">
                  Archivos XML de CFDI (Facturas de gastos/servicios)
                </p>
              </div>

              <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center text-red-700">
                  <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                  <span>{{ error }}</span>
                </div>
              </div>
            </div>

            <!-- Estado: Cargando -->
            <div v-if="loading" class="text-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto mb-4"></div>
              <p class="text-gray-600">Procesando XML...</p>
            </div>

            <!-- Estado: Datos del CFDI -->
            <div v-if="cfdiData && !loading">
              <!-- Total destacado -->
              <div class="mb-6 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-indigo-100 text-sm uppercase tracking-wide">Total de la Factura</p>
                    <p class="text-4xl font-bold">${{ formatMoney(cfdiData.total) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-indigo-100 text-sm">Folio: {{ cfdiData.serie }}{{ cfdiData.folio }}</p>
                    <p class="text-indigo-100 text-sm">{{ formatDate(cfdiData.fecha) }}</p>
                    <p v-if="cfdiData.timbre?.uuid" class="text-indigo-200 text-xs mt-1">UUID: {{ cfdiData.timbre.uuid.substring(0, 18) }}...</p>
                  </div>
                </div>
              </div>

              <!-- Desglose de montos -->
              <div class="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 dark:border-slate-800">
                  <h4 class="text-sm font-semibold text-gray-700">Desglose de Montos</h4>
                </div>
                <div class="p-4 space-y-2">
                  <div class="flex justify-between items-center">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium text-gray-900 dark:text-white">${{ formatMoney(cfdiData.subtotal) }}</span>
                  </div>
                  <div v-if="cfdiData.descuento > 0" class="flex justify-between items-center text-red-600">
                    <span>Descuento:</span>
                    <span class="font-medium">-${{ formatMoney(cfdiData.descuento) }}</span>
                  </div>
                  <div class="flex justify-between items-center border-t pt-2">
                    <span class="text-gray-600">Base para IVA:</span>
                    <span class="font-medium text-gray-900 dark:text-white">${{ formatMoney(cfdiData.subtotal - (cfdiData.descuento || 0)) }}</span>
                  </div>
                  <div class="flex justify-between items-center text-blue-600">
                    <span>IVA (16%):</span>
                    <span class="font-medium">${{ formatMoney(cfdiData.impuestos?.total_impuestos_trasladados || 0) }}</span>
                  </div>
                  <div v-if="cfdiData.impuestos?.total_impuestos_retenidos > 0" class="flex justify-between items-center text-orange-600">
                    <span>Retenciones:</span>
                    <span class="font-medium">-${{ formatMoney(cfdiData.impuestos.total_impuestos_retenidos) }}</span>
                  </div>
                  <div class="flex justify-between items-center border-t pt-2 text-lg font-bold">
                    <span class="text-gray-900 dark:text-white">Total:</span>
                    <span class="text-amber-600">${{ formatMoney(cfdiData.total) }}</span>
                  </div>
                </div>
              </div>

              <!-- Información del CFDI -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tipo Comprobante</p>
                  <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ cfdiData.tipo_comprobante_nombre || cfdiData.tipo_comprobante }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Forma de Pago</p>
                  <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ getFormaPagoNombre(cfdiData.forma_pago) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Método de Pago</p>
                  <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ cfdiData.metodo_pago === 'PUE' ? 'Pago en Una Exhibición' : cfdiData.metodo_pago === 'PPD' ? 'Pago en Parcialidades' : cfdiData.metodo_pago || 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Moneda</p>
                  <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ cfdiData.moneda || 'MXN' }}</p>
                </div>
              </div>

              <!-- Receptor (Tu empresa) -->
              <div v-if="cfdiData.receptor" class="mb-4 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-lg p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1">Receptor (Tu empresa)</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ cfdiData.receptor.nombre }}</p>
                <p class="text-xs text-gray-600">RFC: {{ cfdiData.receptor.rfc }} | Uso CFDI: {{ cfdiData.receptor.uso_cfdi }}</p>
              </div>

              <!-- Emisor (Proveedor) -->
              <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs text-blue-600 uppercase tracking-wide font-medium">Proveedor (Emisor)</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ cfdiData.emisor?.nombre }}</p>
                    <p class="text-sm text-gray-600">RFC: {{ cfdiData.emisor?.rfc }}</p>
                  </div>
                  <div v-if="cfdiData.proveedor_encontrado" class="flex items-center text-green-600">
                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium">Proveedor encontrado</span>
                  </div>
                  <div v-else class="flex flex-col items-end space-y-2">
                    <div class="flex items-center text-amber-600">
                      <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                      </svg>
                      <span class="text-sm font-medium">Proveedor no registrado</span>
                    </div>
                    <button 
                      @click="registrarProveedorExpress"
                      :disabled="registrandoProveedor"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                    >
                      <svg v-if="!registrandoProveedor" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ registrandoProveedor ? 'Registrando...' : 'Agregar Proveedor' }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Conceptos / Descripción Editable -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Descripción del Gasto (Editable)
                </label>
                <textarea
                  v-model="descripcionEditada"
                  rows="4"
                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500"
                  placeholder="Edita la descripción del gasto..."
                ></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Esta descripción se usará para el gasto. Puedes editarla según necesites.
                </p>
              </div>

              <!-- Lista de conceptos para referencia -->
              <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Conceptos del CFDI ({{ cfdiData.conceptos?.length || 0 }})</p>
                <div class="max-h-40 overflow-y-auto border border-gray-200 dark:border-slate-800 rounded-lg">
                  <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descripción</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Importe</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200">
                      <tr v-for="(concepto, index) in cfdiData.conceptos" :key="index">
                        <td class="px-3 py-2 text-gray-900 dark:text-white">{{ concepto.descripcion }}</td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white text-right">${{ formatMoney(concepto.importe) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4 rounded-b-xl flex justify-between items-center">
            <button
              v-if="cfdiData"
              @click="resetUpload"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white dark:bg-slate-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Subir otro archivo
            </button>
            <div class="flex space-x-3">
              <button
                @click="close"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white dark:bg-slate-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                v-if="cfdiData && cfdiData.es_factura_valida"
                @click="confirmarImportacion"
                class="px-6 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors flex items-center"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirmar e Importar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'import']);

const loading = ref(false);
const error = ref('');
const isDragging = ref(false);
const cfdiData = ref(null);
const fileInput = ref(null);
const descripcionEditada = ref('');
const registrandoProveedor = ref(false);

// Cuando se cargan datos del CFDI, inicializar la descripción
watch(() => cfdiData.value, (newData) => {
  if (newData?.descripcion_sugerida) {
    descripcionEditada.value = newData.descripcion_sugerida;
  }
});

const close = () => {
  resetUpload();
  emit('close');
};

const resetUpload = () => {
  cfdiData.value = null;
  error.value = '';
  loading.value = false;
  descripcionEditada.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
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

    const response = await axios.post('/gastos/parse-xml', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.success) {
      cfdiData.value = response.data.data;
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

const confirmarImportacion = () => {
  if (cfdiData.value) {
    // Añadir la descripción editada y datos completos del proveedor
    const dataToEmit = {
      ...cfdiData.value,
      descripcion_final: descripcionEditada.value || cfdiData.value.descripcion_sugerida,
      // Incluir datos completos del proveedor para que Create.vue pueda usarlos
      proveedor_completo: cfdiData.value.proveedor_encontrado ? {
        id: cfdiData.value.proveedor_encontrado.id,
        nombre_razon_social: cfdiData.value.proveedor_encontrado.nombre || cfdiData.value.proveedor_encontrado.nombre_razon_social,
        rfc: cfdiData.value.proveedor_encontrado.rfc || cfdiData.value.emisor?.rfc,
      } : null,
    };
    emit('import', dataToEmit);
    close();
  }
};

const formatMoney = (value) => {
  const num = parseFloat(value) || 0;
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch {
    return dateStr;
  }
};

// Catálogo de formas de pago SAT
const getFormaPagoNombre = (codigo) => {
  const formasPago = {
    '01': 'Efectivo',
    '02': 'Cheque',
    '03': 'Transferencia',
    '04': 'Tarjeta de Crédito',
    '05': 'Monedero Electrónico',
    '06': 'Dinero Electrónico',
    '08': 'Vales de Despensa',
    '12': 'Dación en Pago',
    '13': 'Pago por Subrogación',
    '14': 'Pago por Consignación',
    '15': 'Condonación',
    '17': 'Compensación',
    '23': 'Novación',
    '24': 'Confusión',
    '25': 'Remisión de Deuda',
    '26': 'Prescripción o Caducidad',
    '27': 'A Satisfacción del Acreedor',
    '28': 'Tarjeta de Débito',
    '29': 'Tarjeta de Servicios',
    '30': 'Aplicación de Anticipo',
    '31': 'Intermediario Pagos',
    '99': 'Por Definir',
  };
  return formasPago[codigo] || codigo || 'N/A';
};

const registrarProveedorExpress = async () => {
  if (!cfdiData.value?.emisor) return;

  registrandoProveedor.value = true;

  try {
    const emisor = cfdiData.value.emisor;
    const nombreEmisor = emisor.nombre || '';
    
    if (!nombreEmisor) {
      Swal.fire({
        icon: 'error',
        title: 'Error de proveedor',
        text: 'El nombre del proveedor no se encontró en el XML.'
      });
      return;
    }

    const payload = {
      nombre_razon_social: nombreEmisor,
      rfc: emisor.rfc,
      regimen_fiscal: emisor.regimen_fiscal,
      tipo_persona: emisor.rfc && emisor.rfc.length === 13 ? 'fisica' : 'moral',
      activo: true,
    };

    const response = await axios.post(route('proveedores.store'), payload);

    if (response.data.success) {
      cfdiData.value.proveedor_encontrado = response.data.proveedor;
      if (cfdiData.value.emisor) {
        cfdiData.value.emisor.id = response.data.proveedor.id;
      }
      cfdiData.value.proveedor_id = response.data.proveedor.id;
    }
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: 'error',
      title: 'Error al registrar proveedor',
      text: error.response?.data?.message || error.message || 'Error desconocido'
    });
  } finally {
    registrandoProveedor.value = false;
  }
};
</script>

