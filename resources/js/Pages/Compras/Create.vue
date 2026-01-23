<!-- Resources/js/Pages/Compras/CrearCompra.vue -->
<template>
  <Head title="Crear Compra" />
  <div class="compras-create min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-6">
        <Header
          title="Nueva Compra"
          description="Crea una nueva compra para tus proveedores"
          :can-preview="proveedorSeleccionado && form.almacen_id && selectedProducts.length > 0"
          :back-url="route('compras.index')"
          :show-shortcuts="mostrarAtajos"
          @preview="handlePreview"
          @close-shortcuts="mostrarAtajos = false"
        />
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-md p-3">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                <strong>Nota:</strong> El IVA se calcula automaticamente segun la configuracion de la empresa. Ingrese los precios SIN IVA.
              </p>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="crearCompra" class="space-y-8">
        <!-- Informacion General -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Informacion General
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Numero de Compra -->
            <div>
              <label for="numero_compra" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Numero de Compra *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Numero fijo
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_compra"
                  v-model="form.numero_compra"
                  type="text"
                  class="w-full bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  placeholder="C0001"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Este numero es fijo para todas las compras
              </p>
            </div>

            <!-- Fecha de Compra -->
            <div>
              <label for="fecha_compra" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Compra *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Automatica
                </span>
              </label>
              <div class="relative">
                <input
                  id="fecha_compra"
                  v-model="form.fecha_compra"
                  type="date"
                  class="w-full bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Esta fecha se establece automaticamente con la fecha de creacion
              </p>
            </div>

            <!-- Almacen -->
            <div>
              <label for="almacen_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Almacen *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  Requerido
                </span>
              </label>
              <div class="relative">
                <select
                  id="almacen_id"
                  v-model="form.almacen_id"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  required
                >
                  <option value="">Selecciona un almacen</option>
                  <option
                    v-for="almacen in props.almacenes"
                    :key="almacen.id"
                    :value="almacen.id"
                    :selected="almacen.id === props.almacen_predeterminado"
                  >
                    {{ almacen.nombre }}
                    <span v-if="almacen.id === props.almacen_predeterminado" class="text-xs text-blue-600">(Predeterminado)</span>
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Selecciona el almacen donde se recibiran los productos
                <span v-if="props.almacen_predeterminado" class="text-blue-600">
                  {{ props.recordatorio_almacen }}
                </span>
              </p>
            </div>
          </div>
        </div>

        <!-- Información Fiscal (CFDI) -->
        <div v-if="form.cfdi_uuid" class="bg-white dark:bg-slate-900 rounded-xl border border-blue-200 shadow-sm overflow-hidden animate-fadeIn">
          <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información Fiscal (CFDI Vinculado)
            </h2>
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">XML Importado</span>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">UUID Fiscal</span>
                <span class="block text-sm font-mono font-bold text-slate-700 truncate" :title="form.cfdi_uuid">{{ form.cfdi_uuid }}</span>
              </div>
              <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Folio / Serie</span>
                <span class="block text-sm font-bold text-slate-700">{{ form.cfdi_serie }}{{ form.cfdi_folio || '---' }}</span>
              </div>
              <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Emisor (RFC)</span>
                <span class="block text-sm font-bold text-slate-700 truncate" :title="form.cfdi_emisor_nombre">
                  {{ form.cfdi_emisor_rfc }} - {{ form.cfdi_emisor_nombre }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Proveedor -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Informacion del Proveedor
            </h2>
          </div>
          <div class="p-6">
            <BuscarProveedor
              :proveedores="proveedoresList"
              :proveedor-seleccionado="proveedorSeleccionado"
              label-busqueda="Proveedor"
              placeholder-busqueda="Buscar proveedor por nombre, RFC, email..."
              requerido
              @proveedor-seleccionado="onProveedorSeleccionado"
            />
          </div>
        </div>



        <!-- Productos Disponibles -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Productos
            </h2>
          </div>
          <div class="p-6">
            <div class="mb-6 text-sm text-gray-600 dark:text-gray-300">
              Usa el buscador para agregar productos; no se listan todos para evitar sobrecarga.
            </div>

            <div class="pt-2 border-t border-gray-200 dark:border-slate-800">
              <BuscarProducto
                ref="buscarProductoRef"
                :productos="props.productos"
                :servicios="props.servicios"
                :validar-stock="false"
                @agregar-producto="agregarProducto"
              />
            </div>

            <!-- Productos seleccionados -->
       <ProductosSeleccionados
         :selected-products="selectedProducts"
         :productos="props.productos"
         :servicios="props.servicios"
         :quantities="quantities"
         :prices="prices"
         :discounts="discounts"
         :serials="serialsMap"
         @open-serials="openSerials"
         @eliminar-producto="eliminarProducto"
         @update-quantity="updateQuantity"
         @update-discount="updateDiscount"
         @update-serials="updateSerials"
       />
    
       <!-- Modal para capturar series -->
       <div v-if="showSerialsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
         <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-md mx-4">
           <div class="p-6">
             <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
               Capturar Series - {{ currentSerialProduct?.nombre }}
             </h3>
    
             <div class="mb-4">
               <p class="text-sm text-gray-600 dark:text-gray-300">
                 Cantidad: {{ currentSerialQty }} unidades
               </p>
               <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                 Debe capturar exactamente {{ currentSerialQty }} series unicas
               </p>
             </div>
    
             <div class="space-y-2 max-h-60 overflow-y-auto">
               <div
                 v-for="(serie, index) in serialsForEntry"
                 :key="index"
                 class="flex items-center space-x-2"
               >
                 <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-6">{{ index + 1 }}.</span>
                 <input
                   v-model="serialsForEntry[index]"
                   type="text"
                   :placeholder="`Serie ${index + 1}`"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   required
                 />
               </div>
             </div>
    
             <div class="flex justify-end space-x-3 mt-6">
               <button
                 @click="cancelSerials"
                 class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
               >
                 Cancelar
               </button>
               <button
                 @click="saveSerials"
                 class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
               >
                 Guardar Series
               </button>
             </div>
           </div>
         </div>
       </div>
          </div>
        </div>

        <!-- Notas -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Notas Adicionales
            </h2>
          </div>
          <div class="p-6">
            <textarea
              v-model="form.notas"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
              rows="4"
              placeholder="Agrega notas adicionales, terminos y condiciones, o informacion relevante para la compra..."
            ></textarea>
          </div>
        </div>

        <!-- Descuento General -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
              Descuento General
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div>
                <label for="descuento_general" class="block text-sm font-medium text-gray-700 mb-2">
                  Descuento General ($)
                </label>
                <input
                  id="descuento_general"
                  type="number"
                  step="0.01"
                  min="0"
                  v-model="form.descuento_general"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                  placeholder="0.00"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Este descuento se aplica al subtotal despues de los descuentos por item
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Totales -->
        <Totales
          :show-margin-calculator="false"
          :margin-data="{ costoTotal: 0, precioVenta: 0, ganancia: 0, margenPorcentaje: 0 }"
          :totals="totales"
          :item-count="selectedProducts.length"
          :total-quantity="Object.values(quantities).reduce((sum, qty) => sum + (qty || 0), 0)"
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="retencionIvaDefault"
          :retencion-isr-default="retencionIsrDefault"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
          @update:descuento-general="val => form.descuento_general = val"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('compras.index')"
          :is-processing="form.processing"
          :can-submit="form.proveedor_id && form.almacen_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Crear Compra'"
          @limpiar="limpiarFormulario"
        />
      </form>

      <!-- Atajos de teclado -->
      <button
        @click="mostrarAtajos = !mostrarAtajos"
        class="fixed bottom-4 left-4 bg-gray-600 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition-colors duration-200"
        title="Mostrar atajos de teclado"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>

      <!-- Modal Vista Previa -->
      <VistaPreviaModal
        :show="mostrarVistaPrevia"
        type="compra"
        :proveedor="proveedorSeleccionado"
        :items="selectedProducts"
        :totals="totales"
        :notas="form.notas"
        @close="mostrarVistaPrevia = false"
        @print="() => window.print()"
      />

      <!-- Modal de Pago -->
      <PagoCompraModal
        :show="showPaymentModal"
        :total-compra="totales.total"
        :is-processing="form.processing"
        @close="showPaymentModal = false"
        @confirm="confirmarCompra"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarProveedor from '@/Components/CreateComponents/BuscarProveedor.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import ProductosSeleccionados from '@/Components/CreateComponents/ProductosSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';
import PagoCompraModal from '@/Components/Modals/PagoCompraModal.vue';

// Inicializar notificaciones
const notyf = new Notyf({
  duration: 5000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10B981', icon: { className: 'notyf__icon--success', tagName: 'i', text: 'âœ“' } },
    { type: 'error', background: '#EF4444', icon: { className: 'notyf__icon--error', tagName: 'i', text: 'âœ—' } },
    { type: 'info', background: '#3B82F6', icon: { className: 'notyf__icon--info', tagName: 'i', text: 'â„¹' } },
  ],
});

const showNotification = (message, type = 'success') => {
  notyf.open({ type, message });
};

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  proveedores: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  almacen_predeterminado: { type: [String, Number], default: null },
  recordatorio_almacen: { type: String, default: null },
  defaults: {
    type: Object,
    default: () => ({
      ivaPorcentaje: 16,
      enableIsr: false,
      enableRetencionIva: false,
      enableRetencionIsr: false,
      retencionIvaDefault: 10.6667,
      retencionIsrDefault: 10
    })
  }
});

// Copia reactiva de proveedores (ref para poder mutar localmente)
const proveedoresList = ref(props.proveedores || []);

// Sincronizar con props si cambian (navegación Inertia)
watch(() => props.proveedores, (newVal) => {
  if (newVal) proveedoresList.value = newVal;
}, { deep: true });
const recordatorioAlmacenLimpio = computed(() => (props.recordatorio_almacen || '').replace(/^\uFEFF/, ''));

// Numero de compra (se obtiene del backend)
const numeroCompraFijo = ref('');

// Obtener el siguiente numero de compra del backend
const fetchNextNumeroCompra = async () => {
  try {
  const response = await axios.get('/compras/siguiente-numero');
  if (response.data && response.data.siguiente_numero) {
    numeroCompraFijo.value = response.data.siguiente_numero;
    form.numero_compra = response.data.siguiente_numero;
    saveState(); // guardamos el nuevo n?mero en localStorage
  }
} catch (error) {
  console.error('Error al obtener el numero de compra:', error);
  // fallback
  numeroCompraFijo.value = form.numero_compra || 'C0001';
}
};

// Obtener fecha actual en formato YYYY-MM-DD (zona horaria local)
const getCurrentDate = () => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Formulario
const form = useForm({
  numero_compra: numeroCompraFijo.value || 'C0001',
  fecha_compra: getCurrentDate(),
  proveedor_id: '',
  almacen_id: props.almacen_predeterminado || '',
  metodo_pago: 'efectivo',
  cuenta_bancaria_id: null,
  descuento_general: 0,
  subtotal: 0,
  descuento_items: 0,
  descuento_items: 0,
  iva: 0,
  retencion_iva: 0,
  retencion_isr: 0,
  aplicar_retencion_iva: false,
  aplicar_retencion_isr: false,
  total: 0,
  productos: [],
  notas: '',
  estado: 'borrador',
  // Campos CFDI
  cfdi_uuid: '',
  cfdi_folio: '',
  cfdi_serie: '',
  cfdi_fecha: '',
  cfdi_emisor_rfc: '',
  cfdi_emisor_nombre: '',
});

// Referencias
const buscarProductoRef = ref(null);
const proveedorSeleccionado = ref(null);
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);

const retencionIvaDefault = computed(() => parseFloat(props.defaults?.retencionIvaDefault ?? 10.6667));
const retencionIsrDefault = computed(() => parseFloat(props.defaults?.retencionIsrDefault ?? 10));

// Series por producto seleccionado
const serialsMap = ref({});
const showSerialsModal = ref(false);
const serialsForEntry = ref([]);
const currentSerialKey = ref('');
const currentSerialQty = ref(1);
const currentSerialProduct = ref(null);
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const productoSeleccionado = ref(null);
const showPaymentModal = ref(false);

// Productos disponibles para seleccion directa
const productosDisponibles = computed(() => {
  return props.productos.filter(producto => {
    // Filtrar productos que tienen informacion completa
    return producto.nombre &&
           producto.precio_compra > 0 &&
           producto.codigo &&
           producto.categoria;
  });
});

// Funcion para seleccionar producto con click
const seleccionarProducto = (producto) => {
  productoSeleccionado.value = producto;

  // Agregar automaticamente a la lista de productos seleccionados
  const itemEntry = { id: producto.id, tipo: 'producto' };
  const exists = selectedProducts.value.some(
    (entry) => entry.id === producto.id && entry.tipo === 'producto'
  );

  if (!exists) {
    selectedProducts.value.push(itemEntry);
    const key = `producto-${producto.id}`;
    quantities.value[key] = 1;
    prices.value[key] = producto.precio_compra || 0;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    showNotification(`Producto seleccionado: ${producto.nombre}`);
  } else {
    showNotification('Este producto ya esta seleccionado', 'info');
  }
};

// Funcion para formatear precios
const formatearPrecio = (precio) => {
  const precioNum = Number.parseFloat(precio) || 0;
  return precioNum.toLocaleString('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Guardar y cargar estado en localStorage
const saveToLocalStorage = (key, data) => {
  try {
    localStorage.setItem(key, JSON.stringify(data));
  } catch (error) {
    console.warn('No se pudo guardar en localStorage:', error);
  }
};

const loadFromLocalStorage = (key) => {
  try {
    const item = localStorage.getItem(key);
    return item ? JSON.parse(item) : null;
  } catch (error) {
    console.warn('No se pudo cargar desde localStorage:', error);
    return null;
  }
};

const removeFromLocalStorage = (key) => {
  try {
    localStorage.removeItem(key);
  } catch (error) {
    console.warn('No se pudo eliminar de localStorage:', error);
  }
};

// Funciones
const handlePreview = () => {
  if (proveedorSeleccionado.value && form.almacen_id && selectedProducts.value.length > 0) {
    mostrarVistaPrevia.value = true;
  } else {
    showNotification('Selecciona un proveedor, almacen y al menos un producto', 'error');
  }
};

const onProveedorSeleccionado = (proveedor) => {
  if (!proveedor) {
    proveedorSeleccionado.value = null;
    form.proveedor_id = '';
    saveState();
    showNotification('Seleccion de proveedor limpiada', 'info');
    return;
  }
  if (proveedorSeleccionado.value?.id === proveedor.id) return;
  proveedorSeleccionado.value = proveedor;
  form.proveedor_id = proveedor.id;
  saveState();
  showNotification(`Proveedor seleccionado: ${proveedor.nombre_razon_social}`);
};

const agregarProducto = (item) => {
  if (!item || typeof item.id === 'undefined' || !item.tipo) {
    showNotification('Producto invalido', 'error');
    return;
  }

  const itemEntry = { id: item.id, tipo: item.tipo };
  const exists = selectedProducts.value.some(
    (entry) => entry.id === item.id && entry.tipo === item.tipo
  );

  if (!exists) {
    selectedProducts.value.push(itemEntry);
    const key = `${item.tipo}-${item.id}`;
    quantities.value[key] = 1;

    // FIX: Usar parseFloat para manejar strings y numbers
    let precio = 0;
    if (item.tipo === 'producto') {
      precio = parseFloat(item.precio_compra) || 0;
    } else {
      precio = parseFloat(item.precio) || 0;
    }

    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    showNotification(`Producto anadido: ${item.nombre || item.descripcion || 'Item'}`);
  }
};

const eliminarProducto = (entry) => {
  if (!entry || typeof entry.id === 'undefined' || !entry.tipo) {
    return;
  }

  const key = `${entry.tipo}-${entry.id}`;
  // FIX: Cambiar item.tipo a entry.tipo
  selectedProducts.value = selectedProducts.value.filter(
    (item) => !(item.id === entry.id && item.tipo === entry.tipo)
  );
  delete quantities.value[key];
  delete prices.value[key];
  delete discounts.value[key];
  calcularTotal();
  saveState();
  showNotification(`Producto eliminado: ${entry.nombre || entry.descripcion || 'Item'}`, 'info');
};

const updateQuantity = (key, quantity) => {
  const numQuantity = parseFloat(quantity);
  if (isNaN(numQuantity) || numQuantity < 0) {
    return;
  }
  quantities.value[key] = numQuantity;
  calcularTotal();
  saveState();
};

const updateDiscount = (key, discount) => {
  const numDiscount = parseFloat(discount);
  if (isNaN(numDiscount) || numDiscount < 0 || numDiscount > 100) {
    return;
  }
  discounts.value[key] = numDiscount;
  calcularTotal();
  saveState();
};

const updateSerials = (key, serials) => {
  serialsMap.value[key] = serials;
  saveState();
};

const totales = computed(() => {
  let subtotal = 0;
  let descuentoItems = 0;

  selectedProducts.value.forEach(entry => {
    const key = `${entry.tipo}-${entry.id}`;
    const cantidad = parseFloat(quantities.value[key]) || 0;
    const precio = parseFloat(prices.value[key]) || 0;
    const descuento = parseFloat(discounts.value[key]) || 0;

    if (cantidad > 0 && precio >= 0) {
      const subtotalItem = cantidad * precio;
      descuentoItems += subtotalItem * (descuento / 100);
      subtotal += subtotalItem;
    }
  });

  const descuentoGeneral = parseFloat(form.descuento_general) || 0;
  const subtotalConDescuentos = Math.max(0, subtotal - descuentoItems - descuentoGeneral);
  
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalConDescuentos * ivaRate;

  // Calculo de retenciones
  let retencionIva = 0;
  if (aplicarRetencionIva.value && props.defaults?.enableRetencionIva) {
      retencionIva = subtotalConDescuentos * (retencionIvaDefault.value / 100);
  }

  let retencionIsr = 0;
  if (aplicarRetencionIsr.value && props.defaults?.enableRetencionIsr) {
      retencionIsr = subtotalConDescuentos * (retencionIsrDefault.value / 100);
  }

  const total = subtotalConDescuentos + iva - retencionIva - retencionIsr;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    descuentoGeneral: parseFloat(descuentoGeneral.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsr.toFixed(2)),
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.retencion_iva = totales.value.retencion_iva;
  form.retencion_isr = totales.value.retencion_isr;
  form.aplicar_retencion_iva = aplicarRetencionIva.value;
  form.aplicar_retencion_isr = aplicarRetencionIsr.value;
  form.total = totales.value.total;
};

const validarDatos = () => {
  if (!form.proveedor_id) {
    showNotification('Selecciona un proveedor', 'error');
    return false;
  }

  if (!form.almacen_id) {
    showNotification('Selecciona un almacen de recepcion', 'error');
    return false;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Agrega al menos un producto o servicio', 'error');
    return false;
  }

  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const discount = parseFloat(discounts.value[key]) || 0;
    const quantity = parseFloat(quantities.value[key]) || 0;
    const price = parseFloat(prices.value[key]) || 0;
    // Buscar en props, si no está (producto nuevo), usar el entry mismo
    const producto = props.productos.find(p => p.id === entry.id) || entry;

    if (discount < 0 || discount > 100) {
      showNotification('Los descuentos deben estar entre 0% y 100%.', 'error');
      return false;
    }

    if (quantity <= 0) {
      showNotification('Las cantidades deben ser mayores a 0', 'error');
      return false;
    }

    if (price < 0) {
      showNotification('Los precios no pueden ser negativos', 'error');
      return false;
    }

    // Validacion de series: si el producto requiere serie, debe capturar exactamente 'quantity' series unicas
    if (producto && producto.requiere_serie) {
      const serials = serialsMap.value[key] || [];
      if (!Array.isArray(serials) || serials.length !== quantity) {
        showNotification(`El producto "${producto.nombre}" requiere ${quantity} series.`, 'error');
        return false;
      }
      const unique = new Set(serials.map(s => (s || '').trim()).filter(Boolean));
      if (unique.size !== serials.length) {
        showNotification(`Las series del producto "${producto.nombre}" deben ser unicas.`, 'error');
        return false;
      }
    }
  }

  return true;
};

const crearCompra = () => {
  if (!validarDatos()) {
    return;
  }
  // Abrir el modal de pago en lugar de enviar directamente
  showPaymentModal.value = true;
};

// Función que se ejecuta cuando el usuario confirma en el modal de pago
const confirmarCompra = (datosPago) => {
  showPaymentModal.value = false;

  // Actualizar el form con los datos del modal
  form.metodo_pago = datosPago.metodo_pago;
  form.cuenta_bancaria_id = datosPago.cuenta_bancaria_id;
  form.notas_pago = datosPago.notas_pago || '';

  form.productos = selectedProducts.value.map((entry) => {
    const key = `${entry.tipo}-${entry.id}`;
    const seriales = serialsMap.value[key];
    const productoData = {
      id: entry.id,
      tipo: entry.tipo,
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
    };

    // Solo incluir seriales si existen y no estan vacios
    if (seriales && Array.isArray(seriales) && seriales.length > 0) {
      productoData.seriales = seriales;
    }

    return productoData;
  });

  calcularTotal();

  form.post(route('compras.store'), {
    onSuccess: () => {
      removeFromLocalStorage('compraEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      proveedorSeleccionado.value = null;
      form.reset();
      showNotification('Compra creada con exito');
    },
    onError: (errors) => {
      console.error('Errores de validacion:', errors);
      const firstError = Object.values(errors)[0];
      if (Array.isArray(firstError)) {
        showNotification(firstError[0], 'error');
      } else {
        showNotification('Hubo errores de validacion', 'error');
      }
    },
  });
};

// Captura de series
const openSerials = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  currentSerialKey.value = key;
  currentSerialQty.value = Number(quantities.value[key]) || 1;

  // Encontrar el producto para mostrar su nombre
  const producto = props.productos.find(p => p.id === entry.id);
  currentSerialProduct.value = producto || null;

  const existentes = serialsMap.value[key] || [];
  serialsForEntry.value = Array.from({ length: currentSerialQty.value }, (_, i) => existentes[i] || '');
  showSerialsModal.value = true;
};

const saveSerials = () => {
  const serials = serialsForEntry.value.map(s => (s || '').trim()).filter(Boolean);
  if (serials.length !== currentSerialQty.value) {
    showNotification(`Debes capturar exactamente ${currentSerialQty.value} series.`, 'error');
    return;
  }
  if ((new Set(serials)).size !== serials.length) {
    showNotification('Las series deben ser unicas.', 'error');
    return;
  }
  serialsMap.value[currentSerialKey.value] = serials;
  showSerialsModal.value = false;
  showNotification(`Series capturadas correctamente para ${currentSerialProduct.value?.nombre || 'el producto'}`, 'success');
};

const cancelSerials = () => {
  showSerialsModal.value = false;
  currentSerialProduct.value = null;
  serialsForEntry.value = [];
  currentSerialKey.value = '';
  currentSerialQty.value = 1;
};

const limpiarFormulario = () => {
  proveedorSeleccionado.value = null;
  form.numero_compra = numeroCompraFijo.value || 'C0001';
  form.fecha_compra = getCurrentDate();
  form.proveedor_id = '';
  form.descuento_general = 0;
  selectedProducts.value = [];
  quantities.value = {};
  prices.value = {};
  discounts.value = {};
  serialsMap.value = {};
  form.notas = '';
  form.notas = '';
  form.metodo_pago = 'efectivo';
  aplicarRetencionIva.value = false;
  aplicarRetencionIsr.value = false;
  removeFromLocalStorage('compraEnProgreso');
  showNotification('Formulario limpiado correctamente');
};

const saveState = () => {
  const stateToSave = {
    numero_compra: form.numero_compra || numeroCompraFijo.value || 'C0001',
    fecha_compra: form.fecha_compra,
    proveedor_id: form.proveedor_id,
    descuento_general: form.descuento_general,
    proveedor: proveedorSeleccionado.value,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
    serials: serialsMap.value,
    metodo_pago: form.metodo_pago,
    aplicar_retencion_iva: aplicarRetencionIva.value,
    aplicar_retencion_isr: aplicarRetencionIsr.value,
    // Persistir campos CFDI
    cfdi_uuid: form.cfdi_uuid,
    cfdi_folio: form.cfdi_folio,
    cfdi_serie: form.cfdi_serie,
    cfdi_fecha: form.cfdi_fecha,
    cfdi_emisor_rfc: form.cfdi_emisor_rfc,
    cfdi_emisor_nombre: form.cfdi_emisor_nombre,
  };
  saveToLocalStorage('compraEnProgreso', stateToSave);
};

const handleBeforeUnload = (event) => {
  if (form.proveedor_id || selectedProducts.value.length > 0) {
    event.preventDefault();
    event.returnValue = 'Tienes cambios sin guardar. Estas seguro de que quieres salir?';
  }
};

// Funcion para asegurar que la fecha sea válida (si está vacía, poner actual)
const asegurarFechaValida = () => {
  if (!form.fecha_compra) {
    form.fecha_compra = getCurrentDate();
  }
};

/**
 * Cargar datos desde XML CFDI importado
 * @param {Object} cfdiData - Datos parseados del CFDI
 */
const cargarDatosXmlImportado = (cfdiData) => {
  if (!cfdiData) return;

  // Asignar datos fiscales del CFDI al formulario
  form.cfdi_uuid = cfdiData.uuid || ''
  form.cfdi_folio = cfdiData.folio || ''
  form.cfdi_serie = cfdiData.serie || ''
  form.cfdi_fecha = cfdiData.fecha_emision || ''
  form.cfdi_emisor_rfc = cfdiData.emisor?.rfc || ''
  form.cfdi_emisor_nombre = cfdiData.emisor?.nombre || ''

  // Priorizar fecha de emisión del XML para la fecha de compra
  if (cfdiData.fecha_emision) {
    // No truncar para conservar precisión, pero asegurar que sea un string limpio
    form.fecha_compra = cfdiData.fecha_emision;
    const fechaMostrar = cfdiData.fecha_emision.split('T')[0];
    showNotification(`Fecha de compra ajustada al XML: ${fechaMostrar}`, 'info');
  }

  // 1. Seleccionar proveedor si fue encontrado
  const proveedorObj = cfdiData.proveedor_encontrado;
  
  // Si viene un objeto proveedor nuevo (del quick add), agregarlo a la lista local
  if (proveedorObj && typeof proveedorObj === 'object' && proveedorObj.id) {
     const existe = proveedoresList.value.some(p => p.id === proveedorObj.id);
     if (!existe) {
         proveedoresList.value.push(proveedorObj);
         showNotification(`Proveedor agregado a lista temporal: ${proveedorObj.nombre_razon_social || 'Nuevo'}`, 'info');
     }
  }

  // Intentar seleccionar
  const proveedorIdTarget = cfdiData.proveedor_id || (proveedorObj?.id);
  
  if (proveedorIdTarget) {
    // Buscar en la lista local actualizada
    const proveedor = proveedoresList.value.find(p => p.id === parseInt(proveedorIdTarget));
    if (proveedor) {
      proveedorSeleccionado.value = proveedor;
      form.proveedor_id = proveedor.id;
    }
  }

  // 2. Agregar productos mapeados
  if (cfdiData.conceptos && Array.isArray(cfdiData.conceptos)) {
    cfdiData.conceptos.forEach(concepto => {
      // Solo agregar si el producto fue mapeado (tiene producto_id)
      if (concepto.producto_id) {
        let producto = props.productos.find(p => p.id === concepto.producto_id);
        
        // Si no está en la lista (recién creado), construir objeto temporal con datos del concepto
        if (!producto) {
            producto = {
                id: concepto.producto_id,
                nombre: concepto.producto_nombre || 'Producto Nuevo',
                codigo: concepto.producto_codigo || '',
                precio_compra: concepto.valor_unitario || 0, // Fallback
                requiere_serie: concepto.requiere_serie || false,
                unidad_medida: concepto.unidad || 'PZA',
            };
        }

        if (producto) {
          // Verificar que no esté ya agregado
          // Verificar que no esté ya agregado
          const yaAgregado = selectedProducts.value.some(p => p.id === producto.id);
          if (!yaAgregado) {
            // Asegurar estructura correcta con tipo: 'producto'
            selectedProducts.value.push({ ...producto, tipo: 'producto' });
            
            const key = `producto-${producto.id}`;

            // Establecer cantidad del XML
            quantities.value[key] = concepto.cantidad || 1;
            
            // Establecer precio del XML (sin IVA)
            prices.value[key] = concepto.valor_unitario || producto.precio_compra || 0;
            
            // Establecer descuento si existe
            if (concepto.descuento && concepto.importe > 0) {
              // Calcular porcentaje de descuento
              const descuentoPorcentaje = (concepto.descuento / concepto.importe) * 100;
              discounts.value[key] = Math.round(descuentoPorcentaje * 100) / 100;
            } else {
              discounts.value[key] = 0;
            }
          }
        }
      }
    });

    // Recalcular totales
    calcularTotal();
  }

  // 3. Mostrar info de conceptos no mapeados
  const noMapeados = cfdiData.conceptos?.filter(c => c.match_type === 'none') || [];
  if (noMapeados.length > 0) {
    showNotification(
      `${noMapeados.length} producto(s) del XML no fueron encontrados y deberán agregarse manualmente`,
      'info'
    );
  }

  // Guardar estado
  saveState();
};

// Lifecycle hooks
onMounted(async () => {
  // Obtener el siguiente numero de compra
  await fetchNextNumeroCompra();

  // Mostrar info sobre almacen predeterminado si existe
  if (props.almacen_predeterminado && recordatorioAlmacenLimpio.value) {
    showNotification(`Almacen predeterminado: ${recordatorioAlmacenLimpio.value}`, 'info');
  }

  // Nota sobre numero de compra fijo
  showNotification(`Numero de compra generado: ${numeroCompraFijo.value}`, 'info');

  const savedData = loadFromLocalStorage('compraEnProgreso');
  if (savedData && typeof savedData === 'object') {
    try {
      // Usar numero guardado o el generado automaticamente
      form.numero_compra = numeroCompraFijo.value || savedData.numero_compra || 'C0001';
      
      // Si ya tiene una fecha (ej. de un XML cargado antes de recargar), mantenerla
      if (!form.fecha_compra) {
        form.fecha_compra = savedData.fecha_compra || getCurrentDate();
      }

      form.proveedor_id = savedData.proveedor_id || '';
      // Solo sobrescribir almacen_id si existe en savedData
      if (savedData.almacen_id) {
        form.almacen_id = savedData.almacen_id;
      }
      form.metodo_pago = savedData.metodo_pago || 'efectivo';
      form.descuento_general = savedData.descuento_general || 0;
      aplicarRetencionIva.value = savedData.aplicar_retencion_iva || false;
      aplicarRetencionIsr.value = savedData.aplicar_retencion_isr || false;
      proveedorSeleccionado.value = savedData.proveedor || null;
      selectedProducts.value = Array.isArray(savedData.selectedProducts) ? savedData.selectedProducts : [];
      quantities.value = savedData.quantities || {};
      prices.value = savedData.prices || {};
      discounts.value = savedData.discounts || {};
      serialsMap.value = savedData.serials || {};

      // Cargar campos CFDI si existen
      form.cfdi_uuid = savedData.cfdi_uuid || '';
      form.cfdi_folio = savedData.cfdi_folio || '';
      form.cfdi_serie = savedData.cfdi_serie || '';
      form.cfdi_fecha = savedData.cfdi_fecha || '';
      form.cfdi_emisor_rfc = savedData.cfdi_emisor_rfc || '';
      form.cfdi_emisor_nombre = savedData.cfdi_emisor_nombre || '';

      calcularTotal();
    } catch (error) {
      console.warn('Error al cargar datos guardados:', error);
      removeFromLocalStorage('compraEnProgreso');
    }
  }

  // Forzar que el numero mostrado sea siempre el obtenido del backend
  form.numero_compra = numeroCompraFijo.value || 'C0001';
  saveState();

  // Verificar si viene de importación XML
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('from_xml') === '1') {
    const cfdiDataStr = sessionStorage.getItem('cfdi_import_data');
    if (cfdiDataStr) {
      try {
        const cfdiData = JSON.parse(cfdiDataStr);
        cargarDatosXmlImportado(cfdiData);
        sessionStorage.removeItem('cfdi_import_data'); // Limpiar después de cargar
        showNotification('Datos del XML cargados correctamente', 'success');
      } catch (error) {
        console.error('Error al cargar datos del XML:', error);
        showNotification('Error al cargar datos del XML importado', 'error');
      }
    }
  }

  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>




