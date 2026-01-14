<template>
  <Head title="Crear cotización" />
  <div class="cotizaciones-create min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <Header
        title="Nueva Cotización"
        description="Crea una nueva cotización para tus clientes"
        :can-preview="clienteSeleccionado && selectedProducts.length > 0"
        :back-url="route('cotizaciones.index')"
        :show-shortcuts="mostrarAtajos"
        @preview="handlePreview"
        @close-shortcuts="closeShortcuts"
      />

      <form @submit.prevent="crearCotizacion" class="space-y-8">
        <!-- Información General -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información General
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Número de Cotización -->
            <div>
              <label for="numero_cotizacion" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Número de Cotización *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Número fijo
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_cotizacion"
                  v-model="numeroCotizacionActual"
                  type="text"
                  class="w-full bg-gray-50 text-gray-500 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  :placeholder="numeroCotizacionActual"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Este número se genera automáticamente
              </p>
            </div>

            <!-- Fecha de Cotización -->
            <div>
              <label for="fecha_cotizacion" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Cotización *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Automática
                </span>
              </label>
              <div class="relative">
                <input
                  id="fecha_cotizacion"
                  v-model="form.fecha_cotizacion"
                  type="date"
                  class="w-full bg-gray-50 text-gray-500 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Esta fecha se establece automáticamente con la fecha de creación
              </p>
            </div>
          </div>
        </div>

        <!-- Cliente -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Información del Cliente
            </h2>
          </div>
          <div class="p-6 space-y-6">
            <BuscarCliente
              ref="buscarClienteRef"
              :clientes="clientesActivos"
              :cliente-seleccionado="clienteSeleccionado"
              @cliente-seleccionado="onClienteSeleccionado"
              @crear-nuevo-cliente="crearNuevoCliente"
            />

            <!-- Lista de Precios -->
            <div v-if="clienteSeleccionado">
              <label for="price_list_id" class="block text-sm font-medium text-gray-700 mb-2">
                Lista de Precios
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full ml-2">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                  </svg>
                  Automática
                </span>
              </label>
              <select
                id="price_list_id"
                v-model="priceListSeleccionada"
                @change="onPriceListChange"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Seleccionar lista de precios...</option>
                <option
                  v-for="lista in priceLists"
                  :key="lista.id"
                  :value="lista.id"
                >
                  {{ lista.nombre }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500">
                Se usará la lista de precios del cliente por defecto. Cambia aquí si necesitas usar otra lista.
              </p>
            </div>
          </div>
        </div>

        <!-- Productos y Servicios -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Productos y Servicios
            </h2>
          </div>
          <div class="p-6">
            <BuscarProducto
              ref="buscarProductoRef"
              :productos="productos"
              :servicios="servicios"
              :validar-stock="false"
              :price-list-id="priceListSeleccionada"
              @agregar-producto="agregarProducto"
            />
            <PySSeleccionados
              :selectedProducts="selectedProducts"
              :productos="productos"
              :servicios="servicios"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
            />
          </div>
        </div>

         <!-- Notas -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
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
              rows="3"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-vertical"
              placeholder="Añade notas adicionales para esta cotización..."
            ></textarea>
          </div>
        </div>

        <!-- Totales -->
        <Totales
          :show-margin-calculator="false"
          :margin-data="{ costoTotal: 0, precioVenta: 0, ganancia: 0, margenPorcentaje: 0 }"
          :totals="totales"
          :item-count="selectedProducts.length"
          :total-quantity="Object.values(quantities).reduce((sum, qty) => sum + (qty || 0), 0)"
          :iva-porcentaje="ivaPorcentaje"
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="Number(props.defaults?.retencionIvaDefault || 0)"
          :retencion-isr-default="Number(props.defaults?.retencionIsrDefault || 0)"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
          @update:descuento-general="val => form.descuento_general = val"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('cotizaciones.index')"
          :is-processing="form.processing"
          :can-submit="form.cliente_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Crear Cotización'"
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
    </div>
  </div>

  <!-- Modal Vista Previa -->
  <VistaPreviaModal
    :show="mostrarVistaPrevia"
    type="cotizacion"
    :cliente="clienteSeleccionado"
    :items="selectedProducts"
    :totals="totales"
    :notas="form.notas"
    @close="mostrarVistaPrevia = false"
    @print="() => window.print()"
  />

  <!-- Modal Alerta Margen -->
  <MarginAlertModal
    :show="mostrarAlertaMargen"
    :productos-bajo-margen="productosBajoMargen"
    :processing="procesandoAjusteMargen"
    @close="cerrarAlertaMargen"
    @ajustar-automaticamente="ajustarPreciosAutomaticamente"
  />

  <!-- Modal Crear Cliente -->
  <CrearClienteModal
    :show="mostrarModalCliente"
    :catalogs="catalogs"
    :nombre-inicial="nombreClienteBuscado"
    @close="mostrarModalCliente = false"
    @cliente-creado="onClienteCreado"
  />
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import { resolverPrecio } from '@/Utils/precioHelper'; // ✅ Importar helper
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import PySSeleccionados from '@/Components/CreateComponents/PySSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';
import MarginAlertModal from '@/Components/MarginAlertModal.vue';
import CrearClienteModal from '@/Components/Modals/CrearClienteModal.vue';

// Inicializar notificaciones
const notyf = new Notyf({
  duration: 5000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10B981', icon: { className: 'notyf__icon--success', tagName: 'i', text: '✓' } },
    { type: 'error', background: '#EF4444', icon: { className: 'notyf__icon--error', tagName: 'i', text: '✗' } },
    { type: 'info', background: '#3B82F6', icon: { className: 'notyf__icon--info', tagName: 'i', text: 'ℹ' } },
  ],
});

const showNotification = (message, type = 'success') => {
  notyf.open({ type, message });
};

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  clientes: Array,
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  catalogs: { type: Object, default: () => ({}) },
  priceLists: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16, isrPorcentaje: 1.25 }) },
});

// Filtrar solo clientes activos
const clientesActivos = computed(() => {
  return props.clientes.filter(cliente => {
    // Verificar diferentes formas de representar el estado activo
    const estado = cliente.estado || cliente.status || cliente.activo;

    // Manejar diferentes tipos de valores para estado activo
    if (typeof estado === 'string') {
      return estado.toLowerCase() === 'activo' || estado.toLowerCase() === 'active';
    } else if (typeof estado === 'boolean') {
      return estado === true;
    } else if (typeof estado === 'number') {
      return estado === 1;
    }

    // Si no hay campo de estado, asumir que está activo (por compatibilidad)
    return true;
  });
});

// Catalogs para el modal
const catalogs = computed(() => props.catalogs);

// Copia reactiva de clientes activos para evitar mutación de props
const clientesList = ref([]);

// Obtener el siguiente número de cotización disponible
const getSiguienteNumeroCotizacion = async () => {
  try {
    const response = await axios.get('/cotizaciones/siguiente-numero');
    return response.data.siguiente_numero;
  } catch (error) {
    console.error('Error al obtener siguiente número:', error);
    return '1'; // fallback
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

// Estado para el número de cotización
const numeroCotizacionActual = ref('1');

// Formulario
const form = useForm({
  numero_cotizacion: null, // Se generará automáticamente
  fecha_cotizacion: getCurrentDate(),
  cliente_id: '',
  subtotal: 0,
  descuento_items: 0,
  iva: 0,
  total: 0,
  productos: [],
  notas: '',
  estado: 'borrador',
  retencion_iva: 0,
  retencion_isr: 0,
  aplicar_retencion_iva: false,
  aplicar_retencion_isr: false
});

// Referencias
const buscarClienteRef = ref(null);
const buscarProductoRef = ref(null);

// Estado
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const clienteSeleccionado = ref(null);
const priceListSeleccionada = ref(null);  // ✅ Inicializar como null en lugar de string vacío
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const mostrarAlertaMargen = ref(false);
const productosBajoMargen = ref([]);
const procesandoAjusteMargen = ref(false);
const mostrarModalCliente = ref(false);
const nombreClienteBuscado = ref('');

// Estado fiscal
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);
const ivaPorcentaje = computed(() => Number(props.defaults?.ivaPorcentaje ?? 16));
const isrPorcentaje = computed(() => Number(props.defaults?.isrPorcentaje ?? 1.25));
const retencionIvaDefault = computed(() => Number(props.defaults?.retencionIvaDefault || 0));
const retencionIsrDefault = computed(() => Number(props.defaults?.retencionIsrDefault || 0));

// Función para manejar localStorage de forma segura
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

// --- FUNCIONES ---

// Header
const handlePreview = () => {
  if (clienteSeleccionado.value && selectedProducts.value.length > 0) {
    mostrarVistaPrevia.value = true;
  } else {
    showNotification('Selecciona un cliente y al menos un producto', 'error');
  }
};

const closeShortcuts = () => {
  mostrarAtajos.value = false;
};

// Cliente
const onClienteSeleccionado = (cliente) => {
  if (!cliente) {
    clienteSeleccionado.value = null;
    form.cliente_id = '';
    priceListSeleccionada.value = null;  // ✅ Usar null en lugar de string vacío
    saveState();
    showNotification('Selección de cliente limpiada', 'info');
    return;
  }
  if (clienteSeleccionado.value?.id === cliente.id) return;
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;

  // Establecer lista de precios del cliente por defecto
  if (cliente.price_list_id) {
    priceListSeleccionada.value = cliente.price_list_id;
  } else {
    // Si no tiene lista específica, usar la primera lista activa disponible
    const primeraLista = props.priceLists?.[0];
    if (primeraLista) {
      priceListSeleccionada.value = primeraLista.id;
    }
  }

  saveState();
  showNotification(`Cliente seleccionado: ${cliente.nombre_razon_social}`);
};

const crearNuevoCliente = (nombreBuscado) => {
  nombreClienteBuscado.value = nombreBuscado;
  mostrarModalCliente.value = true;
};

const onClienteCreado = (nuevoCliente) => {
  // Verificar que el nuevo cliente esté activo antes de agregarlo
  if (nuevoCliente.estado === 'activo' || nuevoCliente.estado === 'Activo' || nuevoCliente.estado === true || nuevoCliente.estado === 1) {
    // Actualizar la copia reactiva solo con clientes activos
    if (!clientesList.value.some(c => c.id === nuevoCliente.id)) {
      clientesList.value.push(nuevoCliente);
    }

    onClienteSeleccionado(nuevoCliente);
  } else {
    showNotification('No se puede seleccionar un cliente inactivo', 'error');
  }
};

const onPriceListChange = async () => {
  if (!clienteSeleccionado.value || selectedProducts.value.length === 0) {
    saveState();
    return;
  }

  try {
    // Mostrar indicador de carga
    const loadingToast = notyf.open({
      type: 'info',
      message: 'Recalculando precios...',
      duration: 0 // No se cierra automáticamente
    });

    // Preparar datos para la API
    const productosParaRecalcular = selectedProducts.value.map(entry => ({
      id: entry.id,
      tipo: entry.tipo
    }));

    // Llamar a la API para obtener nuevos precios
    const response = await axios.post('/api/precios/recalcular', {
      cliente_id: clienteSeleccionado.value.id,
      price_list_id: priceListSeleccionada.value,
      productos: productosParaRecalcular
    });

    // Actualizar precios con los nuevos valores
    if (response.data.precios) {
      Object.keys(response.data.precios).forEach(key => {
        if (prices.value[key] !== undefined) {
          prices.value[key] = response.data.precios[key];
        }
      });
    }

    // Recalcular totales
    calcularTotal();
    saveState();

    // Cerrar notificación de carga y mostrar éxito
    notyf.dismiss(loadingToast);
    showNotification('Precios recalculados correctamente');

  } catch (error) {
    console.error('Error al recalcular precios:', error);
    showNotification('Error al recalcular precios', 'error');
  }
};

// En el script
const limpiarFormulario = () => {
  // Limpiar cliente
  clienteSeleccionado.value = null;
  form.cliente_id = '';

  // Limpiar lista de precios
  priceListSeleccionada.value = '';

  // Limpiar productos
  selectedProducts.value = [];

  // Reiniciar cantidades, precios y descuentos
  quantities.value = {};
  prices.value = {};
  discounts.value = {};

  // Limpiar notas
  form.notas = '';

  // Restablecer número y fecha
  form.numero_cotizacion = null;
  form.fecha_cotizacion = getCurrentDate();

  // Actualizar el número mostrado
  getSiguienteNumeroCotizacion().then(num => {
    numeroCotizacionActual.value = num;
  });

  // Limpiar localStorage si es necesario
  localStorage.removeItem(`cotizacion_edit_${props.cotizacion?.id}`);

  // Notificación
  notyf.success('Formulario limpiado correctamente');

  // Si necesitas forzar actualización de algún componente
  // keyComponent.value += 1;
};

// Productos
const agregarProducto = (item) => {
  if (!item || typeof item.id === 'undefined' || !item.tipo) {
    showNotification('Producto inválido', 'error');
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

    // Validar precios con fallbacks seguros - usar parseFloat para manejar strings y numbers
    let precio = 0;
    if (item.tipo === 'producto') {
      // ✅ Resolver precio según lista seleccionada
      precio = resolverPrecio(item, priceListSeleccionada.value);
    } else {
      precio = parseFloat(item.precio) || 0;
    }

    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    showNotification(`Producto añadido: ${item.nombre || item.descripcion || 'Item'}`);
  }
};

const eliminarProducto = (entry) => {
  if (!entry || typeof entry.id === 'undefined' || !entry.tipo) {
    return;
  }

  const key = `${entry.tipo}-${entry.id}`;
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

// Cálculos
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

  const subtotalConDescuentos = Math.max(0, subtotal - descuentoItems);
  const ivaRate = ivaPorcentaje.value / 100;
  const iva = subtotalConDescuentos * ivaRate;

  // Calculate Retencion IVA
  let retencionIva = 0;
  if (aplicarRetencionIva.value) {
    const retIvaRate = retencionIvaDefault.value / 100;
    retencionIva = subtotalConDescuentos * retIvaRate;
  }

  // Calculate ISR (Both legacy automatic and new manual retention)
  let isr = 0; // Legacy
  let retencionIsrMonto = 0; // New explicit

  // Caso 1: Manual
  if (aplicarRetencionIsr.value) {
    const retIsrRate = retencionIsrDefault.value / 100;
    retencionIsrMonto = subtotalConDescuentos * retIsrRate;
  } 
  // Caso 2: Automático PM
  else if (props.defaults?.enableIsr && clienteSeleccionado.value?.tipo_persona === 'moral') {
    const isrRate = isrPorcentaje.value / 100;
    isr = subtotalConDescuentos * isrRate;
  }
  
  // Total = Subtotal + IVA - Retenciones - ISR (Legacy)
  const total = subtotalConDescuentos + iva - isr - retencionIva - retencionIsrMonto;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    isr: parseFloat(isr.toFixed(2)), // Legacy
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsrMonto.toFixed(2)),
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.isr = totales.value.isr;
  form.retencion_iva = totales.value.retencion_iva;
  form.retencion_isr = totales.value.retencion_isr;
  form.total = totales.value.total;
  
  // Update flags for backend recalculation
  form.aplicar_retencion_iva = aplicarRetencionIva.value;
  form.aplicar_retencion_isr = aplicarRetencionIsr.value;
};

// Validar datos antes de crear cotización
const validarDatos = () => {
  if (!form.cliente_id) {
    return false;
  }

  // Verificar que el cliente seleccionado siga activo
  const clienteActivo = clientesActivos.value.find(c => c.id === form.cliente_id);
  if (!clienteActivo) {
    showNotification('El cliente seleccionado no está activo', 'error');
    clienteSeleccionado.value = null;
    form.cliente_id = '';
    return false;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Agrega al menos un producto o servicio', 'error');
    return false;
  }

  // Validar descuentos
  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const discount = parseFloat(discounts.value[key]) || 0;
    const quantity = parseFloat(quantities.value[key]) || 0;
    const price = parseFloat(prices.value[key]) || 0;

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
  }

  return true;
};

// Crear cotización
const crearCotizacion = () => {
  if (!validarDatos()) {
    return;
  }

  // Asignar productos al formulario
  form.productos = selectedProducts.value.map((entry) => {
    const key = `${entry.tipo}-${entry.id}`;
    return {
      id: entry.id,
      tipo: entry.tipo,
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
    };
  });

  // Agregar lista de precios seleccionada
  form.price_list_id = priceListSeleccionada.value || null;

  // Calcular totales
  calcularTotal();

    // ⭐ ASIGNA EL ESTADO AQUÍ ⭐
  form.estado = 'borrador'; // o 'pendiente'

  // Permitir que el modelo genere el número automáticamente
  form.numero_cotizacion = null;

  // Enviar formulario
  form.post(route('cotizaciones.store'), {
    onSuccess: (page) => {
      // Verificar si hay alerta de margen insuficiente
      if (page.props.flash?.warning && page.props.flash?.requiere_confirmacion_margen) {
        // Usar los datos estructurados enviados por el backend
        productosBajoMargen.value = page.props.flash.productos_bajo_margen || [];
        mostrarAlertaMargen.value = true;
        return;
      }

      // Éxito normal
      removeFromLocalStorage('cotizacionEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      clienteSeleccionado.value = null;
      form.reset();
      showNotification('Cotización creada con éxito');
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
      const firstError = Object.values(errors)[0];
      if (Array.isArray(firstError)) {
        showNotification(firstError[0], 'error');
      } else {
        showNotification('Hubo errores de validación', 'error');
      }
    },
  });
};

// Manejo de eventos del navegador
const handleBeforeUnload = (event) => {
  if (form.cliente_id || selectedProducts.value.length > 0) {
    event.preventDefault();
    event.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
  }
};

// Guardar estado automáticamente
const saveState = () => {
  const stateToSave = {
    numero_cotizacion: numeroCotizacionActual.value,
    fecha_cotizacion: form.fecha_cotizacion,
    cliente_id: form.cliente_id,
    cliente: clienteSeleccionado.value,
    price_list_id: priceListSeleccionada.value,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
  };
  saveToLocalStorage('cotizacionEnProgreso', stateToSave);
};

// Función para asegurar que la fecha sea siempre la actual
const asegurarFechaActual = () => {
  const fechaActual = getCurrentDate();
  if (form.fecha_cotizacion !== fechaActual) {
    form.fecha_cotizacion = fechaActual;
  }
};

// Cargar el siguiente número de cotización
const cargarSiguienteNumero = async () => {
  try {
    numeroCotizacionActual.value = await getSiguienteNumeroCotizacion();
  } catch (error) {
    console.error('Error al cargar número de cotización:', error);
    numeroCotizacionActual.value = '1';
  }
};

// Lifecycle hooks
onMounted(async () => {
  // Inicializar la lista de clientes activos
  clientesList.value = [...clientesActivos.value];

  // Cargar el siguiente número de cotización
  await cargarSiguienteNumero();

  const savedData = loadFromLocalStorage('cotizacionEnProgreso');
  if (savedData && typeof savedData === 'object') {
    try {
      // Usar el número guardado o cargar uno nuevo
      if (savedData.numero_cotizacion) {
        numeroCotizacionActual.value = savedData.numero_cotizacion;
      } else {
        numeroCotizacionActual.value = await getSiguienteNumeroCotizacion();
      }
      form.fecha_cotizacion = getCurrentDate(); // Siempre usar fecha actual

      // Verificar que el cliente guardado siga activo
      if (savedData.cliente_id && savedData.cliente) {
        const clienteActivo = clientesActivos.value.find(c => c.id === savedData.cliente_id);
        if (clienteActivo) {
          form.cliente_id = savedData.cliente_id;
          clienteSeleccionado.value = savedData.cliente;
        } else {
          // Cliente ya no está activo, limpiar selección
          showNotification('El cliente guardado ya no está activo', 'info');
        }
      }

      selectedProducts.value = Array.isArray(savedData.selectedProducts) ? savedData.selectedProducts : [];
      quantities.value = savedData.quantities || {};
      prices.value = savedData.prices || {};
      discounts.value = savedData.discounts || {};
      priceListSeleccionada.value = savedData.price_list_id || null;  // ✅ Usar null como fallback
      calcularTotal();
    } catch (error) {
      console.warn('Error al cargar datos guardados:', error);
      removeFromLocalStorage('cotizacionEnProgreso');
    }
  }

  // Verificar la fecha cada 5 minutos para mantenerla actual
  const fechaInterval = setInterval(() => {
    asegurarFechaActual();
  }, 5 * 60 * 1000); // 5 minutos

  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);

  // Limpiar el intervalo de fecha si existe
  if (typeof fechaInterval !== 'undefined') {
    clearInterval(fechaInterval);
  }
});

// Funciones para manejar alerta de margen insuficiente
const cerrarAlertaMargen = () => {
  mostrarAlertaMargen.value = false;
  productosBajoMargen.value = [];
};

const ajustarPreciosAutomaticamente = async () => {
  procesandoAjusteMargen.value = true;

  try {
    // Agregar el flag de ajuste automático al formulario
    form.ajustar_margen = true;

    // Permitir que el modelo genere el número automáticamente
    form.numero_cotizacion = null;

    // Reenviar el formulario con el flag de ajuste
    form.post(route('cotizaciones.store'), {
      onSuccess: () => {
        removeFromLocalStorage('cotizacionEnProgreso');
        selectedProducts.value = [];
        quantities.value = {};
        prices.value = {};
        discounts.value = {};
        clienteSeleccionado.value = null;
        form.reset();
        mostrarAlertaMargen.value = false;
        productosBajoMargen.value = [];
        showNotification('Cotización creada con precios ajustados automáticamente');
      },
      onError: (errors) => {
        console.error('Errores al ajustar precios:', errors);
        showNotification('Error al ajustar precios automáticamente', 'error');
      },
    });
  } catch (error) {
    console.error('Error al ajustar precios:', error);
    showNotification('Error al procesar el ajuste automático', 'error');
  } finally {
    procesandoAjusteMargen.value = false;
  }
};

</script>
