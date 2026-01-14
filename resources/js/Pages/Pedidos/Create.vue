<!-- /resources/js/Pages/Pedidos/Create.vue -->
<template>
  <Head title="Crear Pedido" />
  <div class="pedidos-create min-h-screen p-6" :style="[cssVars, subtleGradientStyle]">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <Header
        title="Nuevo Pedido"
        description="Crea un nuevo pedido para tus clientes"
        :can-preview="clienteSeleccionado && selectedProducts.length > 0"
        :back-url="route('pedidos.index')"
        :show-shortcuts="mostrarAtajos"
        @preview="handlePreview"
        @close-shortcuts="closeShortcuts"
      />

      <form @submit.prevent="crearPedido" class="space-y-8">
        <!-- Información General -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4" :style="headerGradientStyle">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información General
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Número de Pedido -->
            <div>
              <label for="numero_pedido" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Número de Pedido *
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full" :style="{ backgroundColor: `${colors.principal}20`, color: colors.principal }">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Número fijo
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_pedido"
                  v-model="form.numero_pedido"
                  type="text"
                  class="w-full bg-gray-50 text-gray-500 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent"
                  :style="focusRingStyle"
                  placeholder="P0001"
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
                Este número es fijo para todos los pedidos
              </p>
            </div>

            <!-- Fecha de Pedido -->
            <div>
              <label for="fecha_pedido" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Pedido *
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full" :style="{ backgroundColor: `${colors.principal}20`, color: colors.principal }">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Automática
                </span>
              </label>
              <div class="relative">
                <input
                  id="fecha_pedido"
                  v-model="form.fecha_pedido"
                  type="date"
                  class="w-full bg-gray-50 text-gray-500 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent"
                  :style="focusRingStyle"
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
          <div class="px-6 py-4" :style="headerGradientStyle">
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
              :clientes="clientesList"
              :cliente-seleccionado="clienteSeleccionado"
              @cliente-seleccionado="onClienteSeleccionado"
              @crear-nuevo-cliente="crearNuevoCliente"
            />

            <!-- Lista de Precios -->
            <div v-if="clienteSeleccionado">
              <label for="price_list_id" class="block text-sm font-medium text-gray-700 mb-2">
                Lista de Precios
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full ml-2" :style="{ backgroundColor: `${colors.principal}20`, color: colors.principal }">
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
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent"
                :style="focusRingStyle"
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
          <div class="px-6 py-4" :style="headerGradientStyle">
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
          <div class="px-6 py-4" :style="headerGradientStyle">
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
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent resize-vertical"
              :style="focusRingStyle"
              rows="4"
              placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para el pedido..."
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
          @update:descuento-general="val => form.descuento_general = val"
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="retencionIvaDefault"
          :retencion-isr-default="retencionIsrDefault"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('pedidos.index')"
          :is-processing="form.processing"
          :can-submit="form.cliente_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Crear Pedido'"
          @limpiar="limpiarFormulario"
        />
      </form>

      <!-- Atajos de teclado -->
      <button
        @click="mostrarAtajos = !mostrarAtajos"
        class="fixed bottom-4 left-4 text-white p-3 rounded-full shadow-lg hover:brightness-110 transition-all duration-200"
        :style="primaryButtonStyle"
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
    type="pedido"
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
import { Head, useForm, router } from '@inertiajs/vue3';
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
import { useCompanyColors } from '@/Composables/useCompanyColors';

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

const { colors, cssVars, headerGradientStyle, subtleGradientStyle, focusRingStyle, primaryButtonStyle } = useCompanyColors();

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  clientes: Array,
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  catalogs: { type: Object, default: () => ({}) },
  priceLists: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({}) },
});

// Copia reactiva de clientes para evitar mutación de props
const clientesList = ref([...props.clientes]);

// Catalogs para el modal
const catalogs = computed(() => props.catalogs);

// Número de pedido (se obtiene del backend)
const numeroPedidoFijo = ref('P0001');

// Obtener el siguiente número de pedido del backend
const fetchNextNumeroPedido = async () => {
  try {
    const response = await axios.get('/pedidos/siguiente-numero');
    if (response.data && response.data.siguiente_numero) {
      numeroPedidoFijo.value = response.data.siguiente_numero;
      form.numero_pedido = response.data.siguiente_numero;
    }
  } catch (error) {
    console.error('Error al obtener el número de pedido:', error);
    numeroPedidoFijo.value = 'P0001';
    form.numero_pedido = 'P0001';
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
  numero_pedido: numeroPedidoFijo.value || 'P0001',
  fecha_pedido: getCurrentDate(),
  cliente_id: '',
  subtotal: 0,
  descuento_items: 0,
  iva: 0,
  retencion_iva: 0,
  retencion_isr: 0,
  total: 0,
  productos: [],
  notas: '',
  estado: 'borrador',
  aplicar_retencion_iva: false,
  aplicar_retencion_isr: false,
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
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);

const retencionIvaDefault = computed(() => {
  return props.defaults?.retencionIvaDefault ? parseFloat(props.defaults.retencionIvaDefault) : 0;
});

const retencionIsrDefault = computed(() => {
  return props.defaults?.retencionIsrDefault ? parseFloat(props.defaults.retencionIsrDefault) : 0;
});

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
    priceListSeleccionada.value = '';
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
  // Actualizar la copia reactiva en lugar de mutar props
  if (!clientesList.value.some(c => c.id === nuevoCliente.id)) {
    clientesList.value.push(nuevoCliente);
  }

  onClienteSeleccionado(nuevoCliente);
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

    // Debug: mostrar precio en consola
    console.log('🔍 Pedido - Agregando:', item.nombre || item.descripcion, '- Precio:', precio, '- Tipo:', item.tipo, '- Precio raw:', item.precio || item.precio_venta);

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
    (item) => !(item.id === entry.id && item.tipo === item.tipo)
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
  const ivaRate = props.defaults?.ivaPorcentaje ? (parseFloat(props.defaults.ivaPorcentaje) / 100) : 0.16;
  const iva = subtotalConDescuentos * ivaRate;

  // Calculo de retenciones
  let retencionIva = 0;
  if (aplicarRetencionIva.value && props.defaults?.enableRetencionIva) {
      retencionIva = subtotalConDescuentos * (retencionIvaDefault.value / 100);
  }

  let retencionIsrMonto = 0;
  let isr = 0;

  // Lógica de Retención ISR
  if (aplicarRetencionIsr.value && props.defaults?.enableRetencionIsr) {
      retencionIsrMonto = subtotalConDescuentos * (retencionIsrDefault.value / 100);
  } else if (clienteSeleccionado.value && clienteSeleccionado.value.tipo_persona === 'moral' && props.defaults?.enableIsr) {
       // Fallback: Lógica anterior automática para Personas Morales (si está habilitado globalmente)
      // Asumiendo que isrPorcentaje viene en defaults o hardcoded 1.25 si no está (aunque debería venir)
      const isrPorc = props.defaults?.isrPorcentaje ? parseFloat(props.defaults.isrPorcentaje) : 1.25;
      isr = subtotalConDescuentos * (isrPorc / 100);
  }

  // Total: Subtotal + IVA - Retenciones - ISR (legacy)
  const total = subtotalConDescuentos + iva - retencionIva - retencionIsrMonto - isr;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsrMonto.toFixed(2)),
    isr: parseFloat(isr.toFixed(2)), // Para visualización si es necesario
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.retencion_iva = totales.value.retencion_iva;
  form.retencion_isr = totales.value.retencion_isr;
  form.aplicar_retencion_iva = aplicarRetencionIva.value;
  form.aplicar_retencion_isr = aplicarRetencionIsr.value;
  form.total = totales.value.total;
  // isr legacy podría asignarse si el modelo lo soporta, para pedidos parece que el controller lo calcula
  // pero el controller ahora usa aplicar_retencion_...
};

// Validar datos antes de crear pedido
const validarDatos = () => {
  if (!form.cliente_id) {
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

// En el script
const limpiarFormulario = async () => {
  // Limpiar cliente
  clienteSeleccionado.value = null;
  form.cliente_id = '';

  // Limpiar lista de precios
  priceListSeleccionada.value = null;  // ✅ Usar null en lugar de string vacío

  // Limpiar productos
  selectedProducts.value = [];

  // Reiniciar cantidades, precios y descuentos
  quantities.value = {};
  prices.value = {};
  discounts.value = {};

  // Limpiar notas
  form.notas = '';

  // Obtener nuevo número de pedido
  await fetchNextNumeroPedido();
  form.fecha_pedido = getCurrentDate();

  // Limpiar localStorage si es necesario
  localStorage.removeItem(`pedido_edit_${props.pedido?.id}`);

  // Notificación
  notyf.success('Formulario limpiado correctamente');

  // Si necesitas forzar actualización de algún componente
  // keyComponent.value += 1;
};

// Crear pedido
const crearPedido = () => {
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

  // Enviar formulario
  form.post(route('pedidos.store'), {
    onSuccess: (page) => {
      // Verificar si hay alerta de margen insuficiente
      if (page.props.flash?.warning && page.props.flash?.requiere_confirmacion_margen) {
        // Usar los datos estructurados enviados por el backend
        productosBajoMargen.value = page.props.flash.productos_bajo_margen || [];
        mostrarAlertaMargen.value = true;
        return;
      }

      // Éxito normal
      removeFromLocalStorage('pedidoEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      clienteSeleccionado.value = null;
      priceListSeleccionada.value = null;  // ✅ Usar null en lugar de string vacío
      form.reset();
      showNotification('Pedido creado con éxito');
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
    numero_pedido: numeroPedidoFijo.value,
    fecha_pedido: form.fecha_pedido,
    cliente_id: form.cliente_id,
    cliente: clienteSeleccionado.value,
    price_list_id: priceListSeleccionada.value,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
  };
  saveToLocalStorage('pedidoEnProgreso', stateToSave);
};

// Función para asegurar que la fecha sea siempre la actual
const asegurarFechaActual = () => {
  const fechaActual = getCurrentDate();
  if (form.fecha_pedido !== fechaActual) {
    form.fecha_pedido = fechaActual;
  }
};

// Lifecycle hooks
onMounted(async () => {
  // Obtener el siguiente número de pedido
  await fetchNextNumeroPedido();

  const savedData = loadFromLocalStorage('pedidoEnProgreso');
  if (savedData && typeof savedData === 'object') {
    try {
      // Usar número guardado o el generado automáticamente
      form.numero_pedido = savedData.numero_pedido || numeroPedidoFijo.value;
      form.fecha_pedido = getCurrentDate(); // Siempre usar fecha actual

      form.cliente_id = savedData.cliente_id || '';
      clienteSeleccionado.value = savedData.cliente || null;
      selectedProducts.value = Array.isArray(savedData.selectedProducts) ? savedData.selectedProducts : [];
      quantities.value = savedData.quantities || {};
      prices.value = savedData.prices || {};
      discounts.value = savedData.discounts || {};
      priceListSeleccionada.value = savedData.price_list_id || null;  // ✅ Usar null como fallback
      calcularTotal();
    } catch (error) {
      console.warn('Error al cargar datos guardados:', error);
      removeFromLocalStorage('pedidoEnProgreso');
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

    // Reenviar el formulario con el flag de ajuste
    form.post(route('pedidos.store'), {
      onSuccess: () => {
        mostrarAlertaMargen.value = false;
        productosBajoMargen.value = [];
        removeFromLocalStorage('pedidoEnProgreso');
        selectedProducts.value = [];
        quantities.value = {};
        prices.value = {};
        discounts.value = {};
        clienteSeleccionado.value = null;
        form.reset();
        showNotification('Pedido creado con precios ajustados automáticamente');
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
