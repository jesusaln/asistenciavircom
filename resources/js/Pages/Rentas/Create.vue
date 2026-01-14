<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarEquipo from '@/Components/CreateComponents/BuscarEquipo.vue';
import EquiposSeleccionados from '@/Components/CreateComponents/EquiposSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';

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
  equipos: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16 }) },
});

// Copia reactiva de clientes para evitar mutación de props
const clientesList = ref([...props.clientes]);

// Formulario
const form = useForm({
  cliente_id: '',
  subtotal: 0,
  descuento_items: 0,
  iva: 0,
  total: 0,
  equipos: [],
  fecha_inicio: new Date().toISOString().split('T')[0],
  duracion_meses: 12,
  deposito_garantia: '',
  dia_pago: 1,
  forma_pago: 'transferencia',
  observaciones: '',
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
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const saving = ref(false);

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
    showNotification('Selecciona un cliente y al menos un equipo', 'error');
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
    saveState();
    showNotification('Selección de cliente limpiada', 'info');
    return;
  }
  if (clienteSeleccionado.value?.id === cliente.id) return;
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;
  saveState();
  showNotification(`Cliente seleccionado: ${cliente.nombre_razon_social}`);
};

const crearNuevoCliente = async (nombreBuscado) => {
  try {
    const response = await axios.post(route('clientes.store'), { nombre_razon_social: nombreBuscado });
    const nuevoCliente = response.data;

    // Actualizar la copia reactiva en lugar de mutar props
    if (!clientesList.value.some(c => c.id === nuevoCliente.id)) {
      clientesList.value.push(nuevoCliente);
    }

    onClienteSeleccionado(nuevoCliente);
    showNotification(`Cliente creado: ${nuevoCliente.nombre_razon_social}`);
  } catch (error) {
    console.error('Error al crear cliente:', error);
    showNotification('No se pudo crear el cliente', 'error');
  }
};

// Equipos
const agregarProducto = (equipo) => {
  if (!equipo || typeof equipo.id === 'undefined') {
    showNotification('Equipo inválido', 'error');
    return;
  }

  const itemEntry = { id: equipo.id, tipo: 'equipo' };
  const exists = selectedProducts.value.some(
    (entry) => entry.id === equipo.id && entry.tipo === 'equipo'
  );

  if (!exists) {
    selectedProducts.value.push(itemEntry);
    const key = `equipo-${equipo.id}`;
    quantities.value[key] = 1;

    // Usar precio de renta mensual (parseFloat porque PostgreSQL devuelve DECIMAL como string)
    let precio = parseFloat(equipo.precio_renta_mensual) || 0;

    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    showNotification(`Equipo añadido: ${equipo.nombre || equipo.descripcion || 'Equipo'}`);
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
  showNotification(`Equipo eliminado: ${entry.nombre || entry.descripcion || 'Equipo'}`, 'info');
};

const limpiarFormulario = () => {
  // Limpiar cliente
  clienteSeleccionado.value = null;
  form.cliente_id = '';

  // Limpiar equipos
  selectedProducts.value = [];

  // Reiniciar cantidades, precios y descuentos
  quantities.value = {};
  prices.value = {};
  discounts.value = {};

  // Limpiar otros campos
  form.fecha_inicio = new Date().toISOString().split('T')[0];
  form.duracion_meses = 12;
  form.deposito_garantia = '';
  form.dia_pago = 1;
  form.forma_pago = 'transferencia';
  form.observaciones = '';

  // Limpiar localStorage si es necesario
  localStorage.removeItem(`renta_edit_${props.renta?.id}`);

  // Notificación
  notyf.success('Formulario limpiado correctamente');

  // Si necesitas forzar actualización de algún componente
  // keyComponent.value += 1;
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
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalConDescuentos * ivaRate;
  const total = subtotalConDescuentos + iva;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.total = totales.value.total;
};

// Validar datos antes de crear renta
const validarDatos = () => {
  if (!form.cliente_id) {
    showNotification('Selecciona un cliente', 'error');
    return false;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Agrega al menos un equipo', 'error');
    return false;
  }

  if (!form.fecha_inicio) {
    showNotification('Selecciona una fecha de inicio', 'error');
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

// Crear renta
const crearVenta = () => {
  if (!validarDatos()) {
    return;
  }

  // Asignar equipos al formulario
  form.equipos = selectedProducts.value.map((entry) => {
    const key = `${entry.tipo}-${entry.id}`;
    return {
      equipo_id: entry.id,
      precio_mensual: parseFloat(prices.value[key]) || 0,
    };
  });

  // Calcular totales
  calcularTotal();

  // Preparar datos para enviar al backend
  const data = {
    cliente_id: form.cliente_id,
    equipos: form.equipos,
    fecha_inicio: form.fecha_inicio,
    duracion_meses: form.duracion_meses,
    precio_mensual: form.subtotal,
    deposito_garantia: form.deposito_garantia || 0,
    dia_pago: form.dia_pago,
    forma_pago: form.forma_pago,
    observaciones: form.observaciones,
    tiene_prorroga: false
  };

  saving.value = true;

  // Enviar formulario
  router.post(route('rentas.store'), data, {
    onSuccess: () => {
      removeFromLocalStorage('rentaEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      clienteSeleccionado.value = null;
      form.reset();
      saving.value = false;
      showNotification('Renta creada con éxito');
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
      const firstError = Object.values(errors)[0];
      if (Array.isArray(firstError)) {
        showNotification(firstError[0], 'error');
      } else {
        showNotification('Hubo errores de validación', 'error');
      }
      saving.value = false;
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
    cliente_id: form.cliente_id,
    cliente: clienteSeleccionado.value,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
    fecha_inicio: form.fecha_inicio,
    duracion_meses: form.duracion_meses,
    deposito_garantia: form.deposito_garantia,
    dia_pago: form.dia_pago,
    forma_pago: form.forma_pago,
    observaciones: form.observaciones,
  };
  saveToLocalStorage('rentaEnProgreso', stateToSave);
};

// Lifecycle hooks
onMounted(() => {
  const savedData = loadFromLocalStorage('rentaEnProgreso');
  if (savedData && typeof savedData === 'object') {
    try {
      form.cliente_id = savedData.cliente_id || '';
      clienteSeleccionado.value = savedData.cliente || null;
      selectedProducts.value = Array.isArray(savedData.selectedProducts) ? savedData.selectedProducts : [];
      quantities.value = savedData.quantities || {};
      prices.value = savedData.prices || {};
      discounts.value = savedData.discounts || {};
      form.fecha_inicio = savedData.fecha_inicio || new Date().toISOString().split('T')[0];
      form.duracion_meses = savedData.duracion_meses || 12;
      form.deposito_garantia = savedData.deposito_garantia || '';
      form.dia_pago = savedData.dia_pago || 1;
      form.forma_pago = savedData.forma_pago || 'transferencia';
      form.observaciones = savedData.observaciones || '';
      calcularTotal();
    } catch (error) {
      console.warn('Error al cargar datos guardados:', error);
      removeFromLocalStorage('rentaEnProgreso');
    }
  }

  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
  <Head title="Crear Renta" />
  <div class="ventas-create min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <Header
        title="Nueva Renta"
        description="Crea una nueva renta de equipos para tus clientes"
        :can-preview="clienteSeleccionado && selectedProducts.length > 0"
        :back-url="route('rentas.index')"
        :show-shortcuts="mostrarAtajos"
        @preview="handlePreview"
        @close-shortcuts="closeShortcuts"
      />

      <form @submit.prevent="crearVenta" class="space-y-8">
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
          <div class="p-6">
            <BuscarCliente
              ref="buscarClienteRef"
              :clientes="clientesList"
              :cliente-seleccionado="clienteSeleccionado"
              @cliente-seleccionado="onClienteSeleccionado"
              @crear-nuevo-cliente="crearNuevoCliente"
            />
          </div>
        </div>

        <!-- Equipos -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Equipos para Rentar
            </h2>
          </div>
          <div class="p-6">
            <BuscarEquipo
              ref="buscarProductoRef"
              :equipos="props.equipos"
              @agregar-producto="agregarProducto"
            />
            <EquiposSeleccionados
              :selectedProducts="selectedProducts"
              :equipos="props.equipos"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
            />
          </div>
        </div>

        <!-- Configuración de Renta -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Configuración del Contrato
            </h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Inicio</label>
                <input
                  v-model="form.fecha_inicio"
                  type="date"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duración (meses)</label>
                <select
                  v-model="form.duracion_meses"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="6">6 meses</option>
                  <option value="12">12 meses</option>
                  <option value="18">18 meses</option>
                  <option value="24">24 meses</option>
                  <option value="36">36 meses</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Día de Pago</label>
                <select
                  v-model="form.dia_pago"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="dia in 28" :key="dia" :value="dia">Día {{ dia }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pago</label>
                <select
                  v-model="form.forma_pago"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="transferencia">Transferencia Bancaria</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="tarjeta">Tarjeta de Crédito</option>
                  <option value="cheque">Cheque</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Depósito de Garantía</label>
                <input
                  v-model="form.deposito_garantia"
                  type="number"
                  step="0.01"
                  placeholder="0.00"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
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
              v-model="form.observaciones"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
              rows="4"
              placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para la renta..."
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
          :deposito-garantia="form.deposito_garantia"
          @update:descuento-general="val => form.descuento_general = val"
        />


        <!-- Botones -->
        <BotonesAccion
          :back-url="route('rentas.index')"
          :is-processing="saving"
          :can-submit="form.cliente_id && selectedProducts.length > 0"
          :button-text="saving ? 'Guardando...' : 'Crear Renta'"
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
    type="renta"
    :cliente="clienteSeleccionado"
    :items="selectedProducts"
    :totals="totales"
    :notas="form.observaciones"
    :deposito-garantia="form.deposito_garantia"
    @close="mostrarVistaPrevia = false"
    @print="() => window.print()"
  />
</template>

<style scoped>
@import "tailwindcss" reference;
.ventas-create {
  min-height: 100vh;
  background: linear-gradient(to bottom right, #f8fafc, #e0f2fe);
}
</style>

