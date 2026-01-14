<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import { useCompanyColors } from '@/Composables/useCompanyColors';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import PySSeleccionados from '@/Components/CreateComponents/PySSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';

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

defineOptions({ layout: AppLayout });

const props = defineProps({
  pedido: Object,
  clientes: Array,
  productos: Array,
  servicios: Array,
  clientes: Array,
  productos: Array,
  servicios: Array,
  priceLists: Array,
  defaults: { type: Object, default: () => ({}) },
});

// --- FORMULARIO ---
const form = useForm({
  cliente_id: props.pedido?.cliente?.id || '',
  price_list_id: props.pedido?.price_list_id || null,  // ✅ Agregar price_list_id
  numero_pedido: props.pedido?.numero_pedido || '',
  subtotal: props.pedido?.subtotal || 0,
  iva: props.pedido?.iva || 0,
  retencion_iva: props.pedido?.retencion_iva || 0,
  retencion_isr: props.pedido?.retencion_isr || 0,
  aplicar_retencion_iva: props.pedido?.aplicar_retencion_iva === 1 || props.pedido?.aplicar_retencion_iva === true,
  aplicar_retencion_isr: props.pedido?.aplicar_retencion_isr === 1 || props.pedido?.aplicar_retencion_isr === true,
  total: props.pedido?.total || 0,
  productos: [],
  notas: props.pedido?.notas || '',
});

// --- ESTADO ---
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const clienteSeleccionado = ref(props.pedido?.cliente || null);
const priceListSeleccionada = ref(props.pedido?.price_list_id || null);  // ✅ Lista de precios seleccionada
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const isLoading = ref(false);
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);

const retencionIvaDefault = computed(() => {
  return props.defaults?.retencionIvaDefault ? parseFloat(props.defaults.retencionIvaDefault) : 0;
});

const retencionIsrDefault = computed(() => {
  return props.defaults?.retencionIsrDefault ? parseFloat(props.defaults.retencionIsrDefault) : 0;
});

// --- VALIDACIONES ---
const isValidNumber = (value, min = 0) => {
  const num = parseFloat(value);
  return !isNaN(num) && num >= min;
};

const validateQuantity = (quantity) => {
  return isValidNumber(quantity, 0.01);
};

const validatePrice = (price) => {
  return isValidNumber(price, 0);
};

const validateDiscount = (discount) => {
  const num = parseFloat(discount);
  return !isNaN(num) && num >= 0 && num <= 100;
};

// --- CARGAR DATOS ---
onMounted(() => {
  if (props.pedido?.productos) {
    props.pedido.productos.forEach(item => {
      const key = `${item.tipo}-${item.id}`;
      selectedProducts.value.push({ id: item.id, tipo: item.tipo });
      quantities.value[key] = item.pivot?.cantidad || 1;
      prices.value[key] = item.pivot?.precio || 0;
      discounts.value[key] = item.pivot?.descuento || 0;
    });

    
    // Inicializar estados de retención basados en datos existentes o defaults
    // Si ya tiene retenciones guardadas (> 0), activar los switches
    if (parseFloat(props.pedido.retencion_iva) > 0) {
        aplicarRetencionIva.value = true;
    } else {
        aplicarRetencionIva.value = props.pedido.aplicar_retencion_iva ? true : false;
    }

    if (parseFloat(props.pedido.retencion_isr) > 0) {
        aplicarRetencionIsr.value = true;
    } else {
        aplicarRetencionIsr.value = props.pedido.aplicar_retencion_isr ? true : false;
    }
    
    calcularTotal();
  }
});

// --- FUNCIONES ---
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

const onClienteSeleccionado = (cliente) => {
  if (!cliente) {
    clienteSeleccionado.value = null;
    form.cliente_id = '';
    priceListSeleccionada.value = null;
    showNotification('Cliente eliminado', 'info');
    return;
  }
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;
  
  // ✅ Establecer lista de precios del cliente por defecto
  if (cliente.price_list_id) {
    priceListSeleccionada.value = cliente.price_list_id;
  } else {
    const primeraLista = props.priceLists?.[0];
    if (primeraLista) {
      priceListSeleccionada.value = primeraLista.id;
    }
  }
  
  showNotification(`Cliente: ${cliente.nombre_razon_social || cliente.nombre || 'Sin nombre'}`);
};

const crearNuevoCliente = async (nombreBuscado) => {
  if (!nombreBuscado?.trim()) {
    showNotification('El nombre del cliente es requerido', 'error');
    return;
  }

  isLoading.value = true;
  try {
    if (typeof route !== 'function') {
      throw new Error('Route helper no está disponible');
    }

    const response = await axios.post(route('clientes.store'), {
      nombre_razon_social: nombreBuscado.trim()
    });

    if (response.data) {
      const nuevoCliente = response.data;
      props.clientes.push(nuevoCliente);
      onClienteSeleccionado(nuevoCliente);
      showNotification(`Cliente creado: ${nuevoCliente.nombre_razon_social || nuevoCliente.nombre || 'Sin nombre'}`);
    }
  } catch (error) {
    console.error('Error al crear cliente:', error);

    if (error.response?.status === 422) {
      const errors = error.response.data?.errors;
      if (errors) {
        const errorMessages = Object.values(errors).flat().join(', ');
        showNotification(`Errores de validación: ${errorMessages}`, 'error');
      } else {
        showNotification('Datos de cliente inválidos', 'error');
      }
    } else if (error.response?.status === 409) {
      showNotification('Ya existe un cliente con ese nombre', 'error');
    } else if (error.response?.status >= 500) {
      showNotification('Error del servidor. Intenta nuevamente', 'error');
    } else {
      showNotification('No se pudo crear el cliente', 'error');
    }
  } finally {
    isLoading.value = false;
  }
};

const agregarProducto = (item) => {
  if (!item?.id || !item?.tipo) {
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

    let defaultPrice = 0;
    if (item.tipo === 'producto') {
      defaultPrice = item.precio_venta || item.precio || 0;
    } else if (item.tipo === 'servicio') {
      defaultPrice = item.precio || item.precio_venta || 0;
    }

    prices.value[key] = defaultPrice;
    discounts.value[key] = 0;
    calcularTotal();

    const itemName = item.nombre || item.descripcion || item.titulo || `${item.tipo} ${item.id}`;
    showNotification(`Añadido: ${itemName}`);
  } else {
    const itemName = item.nombre || item.descripcion || item.titulo || `${item.tipo} ${item.id}`;
    showNotification(`${itemName} ya está agregado`, 'info');
  }
};

const eliminarProducto = (entry) => {
  if (!entry?.id || !entry?.tipo) {
    showNotification('Error al eliminar producto', 'error');
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

  const itemName = entry.nombre || entry.descripcion || entry.titulo || `${entry.tipo} ${entry.id}`;
  showNotification(`Eliminado: ${itemName}`, 'info');
};

const updateQuantity = (key, quantity) => {
  if (!validateQuantity(quantity)) {
    showNotification('La cantidad debe ser mayor a 0', 'error');
    return;
  }
  quantities.value[key] = parseFloat(quantity);
  calcularTotal();
};

const updatePrice = (key, price) => {
  if (!validatePrice(price)) {
    showNotification('El precio debe ser mayor o igual a 0', 'error');
    return;
  }
  prices.value[key] = parseFloat(price);
  calcularTotal();
};

const updateDiscount = (key, discount) => {
  if (!validateDiscount(discount)) {
    showNotification('El descuento debe estar entre 0% y 100%', 'error');
    return;
  }
  discounts.value[key] = parseFloat(discount);
  calcularTotal();
};

// ✅ Función para recalcular precios cuando cambia la lista
const onPriceListChange = async () => {
  if (!clienteSeleccionado.value || selectedProducts.value.length === 0) {
    form.price_list_id = priceListSeleccionada.value;
    return;
  }

  try {
    showNotification('Recalculando precios...', 'info');

    const productosParaRecalcular = selectedProducts.value
      .filter(entry => entry.tipo === 'producto')
      .map(entry => ({
        id: entry.id,
        tipo: entry.tipo
      }));

    if (productosParaRecalcular.length === 0) {
      form.price_list_id = priceListSeleccionada.value;
      showNotification('No hay productos para recalcular', 'info');
      return;
    }

    const response = await axios.post('/api/precios/recalcular', {
      cliente_id: clienteSeleccionado.value.id,
      price_list_id: priceListSeleccionada.value,
      productos: productosParaRecalcular
    });

    if (response.data?.precios) {
      Object.entries(response.data.precios).forEach(([productoId, nuevoPrecio]) => {
        const key = `producto-${productoId}`;
        if (prices.value[key] !== undefined) {
          prices.value[key] = nuevoPrecio;
        }
      });

      form.price_list_id = priceListSeleccionada.value;
      calcularTotal();
      showNotification('Precios actualizados correctamente');
    }
  } catch (error) {
    console.error('Error al recalcular precios:', error);
    showNotification('Error al recalcular precios', 'error');
  }
};

// --- CÁLCULOS ---
const totales = computed(() => {
  let subtotal = 0;
  let descuentoItems = 0;

  selectedProducts.value.forEach(entry => {
    const key = `${entry.tipo}-${entry.id}`;
    const cantidad = parseFloat(quantities.value[key]) || 0;
    const precio = parseFloat(prices.value[key]) || 0;
    const descuento = parseFloat(discounts.value[key]) || 0;

    const subtotalItem = cantidad * precio;
    const descuentoItem = subtotalItem * (descuento / 100);

    subtotal += subtotalItem;
    descuentoItems += descuentoItem;
  });

  const subtotalConDescuentos = subtotal - descuentoItems;
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalConDescuentos * ivaRate;

  // Calculo de retenciones
  let retencionIva = 0;
  if (aplicarRetencionIva.value && props.defaults?.enableRetencionIva) {
      retencionIva = subtotalConDescuentos * (retencionIvaDefault.value / 100);
  }

  let retencionIsrMonto = 0;
  let isr = 0;

  // Lógica de Retención ISR e ISR Legacy
  if (aplicarRetencionIsr.value && props.defaults?.enableRetencionIsr) {
      retencionIsrMonto = subtotalConDescuentos * (retencionIsrDefault.value / 100);
  } else if (clienteSeleccionado.value && clienteSeleccionado.value.tipo_persona === 'moral' && props.defaults?.enableIsr) {
       // Fallback: Lógica anterior automática para Personas Morales
      const isrPorc = props.defaults?.isrPorcentaje ? parseFloat(props.defaults.isrPorcentaje) : 1.25;
      isr = subtotalConDescuentos * (isrPorc / 100);
  }

  const total = subtotalConDescuentos + iva - retencionIva - retencionIsrMonto - isr;

  return {
    subtotal: Number(subtotal.toFixed(2)),
    descuentoItems: Number(descuentoItems.toFixed(2)),
    subtotalConDescuentos: Number(subtotalConDescuentos.toFixed(2)),
    iva: Number(iva.toFixed(2)),
    retencion_iva: Number(retencionIva.toFixed(2)),
    retencion_isr: Number(retencionIsrMonto.toFixed(2)),
    isr: Number(isr.toFixed(2)),
    total: Number(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  const totals = totales.value;
  form.subtotal = totals.subtotal;
  form.iva = totals.iva;
  form.retencion_iva = totals.retencion_iva;
  form.retencion_isr = totals.retencion_isr;
  form.aplicar_retencion_iva = aplicarRetencionIva.value;
  form.aplicar_retencion_isr = aplicarRetencionIsr.value;
  form.total = totals.total;
};

// --- GUARDAR CAMBIOS ---
const actualizarPedido = () => {
  if (!form.cliente_id) {
    showNotification('Selecciona un cliente', 'error');
    return;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Agrega al menos un producto o servicio', 'error');
    return;
  }

  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const quantity = quantities.value[key];
    const price = prices.value[key];
    const discount = discounts.value[key] || 0;

    if (!validateQuantity(quantity)) {
      showNotification(`Cantidad inválida para ${entry.tipo} ${entry.id}`, 'error');
      return;
    }

    if (!validatePrice(price)) {
      showNotification(`Precio inválido para ${entry.tipo} ${entry.id}`, 'error');
      return;
    }

    if (!validateDiscount(discount)) {
      showNotification(`Descuento inválido para ${entry.tipo} ${entry.id}`, 'error');
      return;
    }
  }

  form.productos = selectedProducts.value.map(entry => {
    const key = `${entry.tipo}-${entry.id}`;
    return {
      id: entry.id,
      tipo: entry.tipo,
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
    };
  });
  
  // ✅ Asegurar que price_list_id se envíe
  form.price_list_id = priceListSeleccionada.value;

  calcularTotal();

  if (typeof route !== 'function') {
    showNotification('Error del sistema: Route helper no disponible', 'error');
    return;
  }

  form.put(route('pedidos.update', props.pedido?.id), {
    onSuccess: () => {
      showNotification('Pedido actualizado con éxito');
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);

      if (typeof errors === 'object' && errors !== null) {
        const errorMessages = Object.values(errors).flat().join(', ');
        showNotification(`Errores: ${errorMessages}`, 'error');
      } else {
        showNotification('Hubo errores al actualizar el pedido', 'error');
      }
    },
  });
};
</script>

<template>
  <Head title="Editar Pedido" />
  <div class="pedidos-edit min-h-screen p-6" :style="[cssVars, subtleGradientStyle]">
    <div class="max-w-6xl mx-auto">
      <!-- Loading overlay -->
      <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2" :style="{ borderBottomColor: colors.principal }"></div>
          <span class="text-gray-700">Procesando...</span>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <Header
          title="Editar Pedido"
          description="Modifica los detalles del pedido"
          :can-preview="clienteSeleccionado && selectedProducts.length > 0"
          :back-url="route && route('pedidos.index')"
          :show-shortcuts="mostrarAtajos"
          @preview="handlePreview"
          @close-shortcuts="closeShortcuts"
        />
      </div>

      <form @submit.prevent="actualizarPedido" class="space-y-8 mt-6">
        <!-- Cliente -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4" :style="headerGradientStyle">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Cliente
            </h2>
          </div>
          <div class="p-6 space-y-6">
            <BuscarCliente
              :clientes="clientes"
              :cliente-seleccionado="clienteSeleccionado"
              @cliente-seleccionado="onClienteSeleccionado"
              @crear-nuevo-cliente="crearNuevoCliente"
            />
            
            <!-- ✅ Lista de Precios -->
            <div v-if="clienteSeleccionado && priceLists && priceLists.length > 0">
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
                <option :value="null">Sin lista de precios</option>
                <option
                  v-for="priceList in priceLists"
                  :key="priceList.id"
                  :value="priceList.id"
                >
                  {{ priceList.nombre }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500">
                Los precios se actualizarán automáticamente al cambiar la lista
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
              :productos="productos"
              :servicios="servicios"
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
          :total-quantity="Object.values(quantities).reduce((sum, qty) => sum + (parseFloat(qty) || 0), 0)"
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="retencionIvaDefault"
          :retencion-isr-default="retencionIsrDefault"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route && route('pedidos.index')"
          :is-processing="form.processing"
          :can-submit="form.cliente_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Actualizar Pedido'"
        />
      </form>

      <!-- Botón ayuda -->
      <button
        @click="mostrarAtajos = !mostrarAtajos"
        class="fixed bottom-4 left-4 bg-gray-600 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition-colors duration-200"
        title="Mostrar/Ocultar atajos de teclado"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>
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
  </div>
</template>
