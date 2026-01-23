<template>
  <div class="productos-seleccionados">
    <!-- Lista de productos seleccionados -->
    <div v-if="selectedProducts.length > 0" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Productos Seleccionados
        <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ selectedProducts.length }}
        </span>
      </h3>

      <div class="space-y-4">
        <div
          v-for="entry in selectedProducts"
          :key="`${entry.tipo}-${entry.id}`"
          class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 hover:shadow-md transition-all duration-200"
        >
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Información del producto -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-3 bg-blue-100 text-blue-800">
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                      </svg>
                      Producto
                    </span>
                  </div>

                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                    {{ getItemInfo(entry).nombre }}
                  </h4>

                  <p v-if="getItemInfo(entry).descripcion" class="text-sm text-gray-600 mb-2">
                    {{ getItemInfo(entry).descripcion }}
                  </p>

                 <div class="space-y-1">
  <!-- Precio de venta -->
  <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
    </svg>
    Precio unitario: ${{ getItemInfo(entry).precio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
  </div>

  <!-- Precio de compra -->
  <div v-if="getItemInfo(entry).precio_compra > 0" class="flex items-center text-sm text-gray-400">
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 2.5M7 13l2.5 2.5"/>
    </svg>
    Precio compra: ${{ getItemInfo(entry).precio_compra.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
  </div>
</div>

                </div>


                <!-- Botón eliminar -->
                <button
                  type="button"
                  @click="eliminarItem(entry)"
                  class="text-red-500 hover:text-red-700 hover:bg-red-100 p-2 rounded-full transition-colors duration-200 flex-shrink-0"
                  title="Eliminar producto"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Controles de cantidad, descuento y totales -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 lg:w-96">
              <!-- Cantidad -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                <div class="relative">
                  <input
  type="number"
  :value="quantities[`${entry.tipo}-${entry.id}`] || 1"
  @input="(event) => updateQuantity(entry, event.target.value)"
  min="1"
  step="1"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>

              <!-- Descuento -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Descuento %</label>
                <div class="relative">
                  <input
                    type="number"
                    :value="discounts[`${entry.tipo}-${entry.id}`] || 0"
                    @input="updateDiscount(entry, $event.target.value)"
                    min="0"
                    max="100"
                    step="0.01"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  />
                  <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                    <span class="text-gray-400 text-xs">%</span>
                  </div>
                </div>
              </div>

              <!-- Precio (editable) -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Precio Unit.</label>
                <div class="relative">
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    :value="prices[`${entry.tipo}-${entry.id}`] || 0"
                    @input="updatePrice(entry, $event.target.value)"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="0.00"
                  />
                  <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                    <span class="text-gray-400 text-xs">$</span>
                  </div>
                </div>
                <div class="mt-1 flex items-center gap-2">
                  <button
                    @click="openEditPriceModal(entry)"
                    class="text-xs text-blue-600 hover:text-blue-800 underline"
                    title="Editar precio con historial"
                  >
                    Editar precio
                  </button>
                  <button
                    @click="verHistorial(entry)"
                    class="text-xs text-green-600 hover:text-green-800 underline"
                    title="Ver historial de precios"
                  >
                    Historial
                  </button>
                </div>
              </div>

                            <!-- Series (si el producto requiere) -->
              <div v-if="getItemInfo(entry)?.requiere_serie" class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Series requeridas</label>

                <div class="flex items-center gap-3 mb-2">
                  <button
                    type="button"
                    @click="emit('open-serials', entry)"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                    title="Capturar series una por una"
                  >
                    Capturar series ({{ serialCount(entry) }}/{{ quantities[`${entry.tipo}-${entry.id}`] || 1 }})
                  </button>
                  <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ getSerialsString(entry) || 'Sin series capturadas' }}
                  </span>
                </div>

                <textarea
                  :value="getSerialsString(entry)"
                  @input="updateSerials(entry, $event.target.value)"
                  :placeholder="`Pega series separadas por coma o por línea (deben ser ${quantities[`${entry.tipo}-${entry.id}`] || 1})`"
                  rows="2"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-y"
                ></textarea>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Puedes usar el botón “Capturar series” o pegar valores separados por coma o por línea. Debes ingresar exactamente {{ quantities[`${entry.tipo}-${entry.id}`] || 1 }} series únicas.
                </p>
              </div>
              <!-- Subtotal del item -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Subtotal</label>
                <div class="px-3 py-2 text-sm font-semibold text-green-600 bg-green-50 border border-green-200 rounded-lg">
                  ${{ calcularSubtotalItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Información adicional del item (descuento aplicado) -->
          <div v-if="(discounts[`${entry.tipo}-${entry.id}`] || 0) > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div class="text-gray-600">
                <span class="font-medium">Subtotal sin descuento:</span>
                <span class="float-right">${{ calcularSubtotalSinDescuento(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
              </div>
              <div class="text-orange-600">
                <span class="font-medium">Descuento aplicado:</span>
                <span class="float-right">-${{ calcularDescuentoItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensaje cuando no hay productos -->
    <div v-else class="mt-6 p-8 border-2 border-dashed border-gray-300 rounded-xl text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
      </svg>
      <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No hay productos seleccionados</p>
      <p class="text-gray-400 text-sm mt-1">Busca y agrega productos para comenzar</p>
    </div>

    <!-- Modal para editar precio con historial -->
    <div v-if="showEditPriceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Header del modal -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-800">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Editar Precio - {{ productoSeleccionado?.nombre }}
          </h3>
          <button @click="closeEditPriceModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6">
          <div v-if="loadingHistorial" class="text-center py-8">
            <svg class="w-8 h-8 animate-spin mx-auto text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-600">Cargando historial...</p>
          </div>

          <div v-else>
            <!-- Información del producto actual -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Precio Actual</label>
                  <p class="text-lg font-semibold text-gray-900 dark:text-white">${{ productoSeleccionado?.precio_compra?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Precio en Orden</label>
                  <p class="text-lg font-semibold text-blue-600">${{ precioActualOrden?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}</p>
                </div>
              </div>
            </div>

            <!-- Formulario de nuevo precio -->
            <div class="mb-6">
              <label for="nuevo_precio" class="block text-sm font-medium text-gray-700 mb-2">
                Nuevo Precio de Compra
              </label>
              <input
                id="nuevo_precio"
                v-model="nuevoPrecio"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ingresa el nuevo precio"
              />
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Este precio se aplicará tanto a la orden de compra como al producto en el catálogo
              </p>
            </div>

            <!-- Notas del cambio -->
            <div class="mb-6">
              <label for="notas_cambio" class="block text-sm font-medium text-gray-700 mb-2">
                Notas del Cambio (opcional)
              </label>
              <textarea
                id="notas_cambio"
                v-model="notasCambio"
                rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Describe el motivo del cambio de precio..."
              ></textarea>
            </div>

            <!-- Historial de precios (últimos 5 cambios) -->
            <div v-if="historialPrecios.length > 0" class="mb-6">
              <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Historial de Precios</h4>
              <div class="space-y-2 max-h-40 overflow-y-auto">
                <div v-for="registro in historialPrecios.slice(0, 5)" :key="registro.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      ${{ registro.precio_compra_anterior?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} →
                      ${{ registro.precio_compra_nuevo?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ registro.fecha }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-sm font-medium" :class="registro.cambio_compra >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ registro.cambio_compra >= 0 ? '+' : '' }}${{ registro.cambio_compra?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ registro.tipo_cambio }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer del modal -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50">
          <button @click="closeEditPriceModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            Cancelar
          </button>
          <button
            @click="guardarNuevoPrecio"
            :disabled="!nuevoPrecio || loadingGuardar"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loadingGuardar" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loadingGuardar ? 'Guardando...' : 'Guardar Precio' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  selectedProducts: {
    type: Array,
    required: true,
  },
  productos: {
    type: Array,
    default: () => [],
  },
  quantities: {
    type: Object,
    required: true,
  },
  prices: {
    type: Object,
    required: true,
  },
  discounts: {
    type: Object,
    required: true,
  },
  // Mapa de series actuales por item key ("tipo-id")
  serials: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  'eliminar-producto',
  'update-quantity',
  'update-discount',
  'update-price',
  'update-serials',
  'open-serials',
  'calcular-total',
  'editar-precio-producto',
  'ver-historial-precios'
]);

// Función para obtener información del item
// 1. Actualizar la función getItemInfo para incluir precio_compra:

const getItemInfo = (entry) => {
  console.log('getItemInfo llamado con:', entry);
  console.log('Productos disponibles:', props.productos?.length || 0);

  // Validar entrada
  if (!entry || !entry.id || !entry.tipo) {
    console.error('Entrada inválida para getItemInfo:', entry);
    return {
      nombre: 'Entrada inválida',
      descripcion: '',
      precio: 0,
      precio_compra: 0,
      requiere_serie: false
    };
  }

  // Solo manejar productos
  if (entry.tipo !== 'producto') {
    console.warn('Tipo no soportado:', entry.tipo);
    return {
      nombre: 'Tipo no soportado',
      descripcion: '',
      precio: 0,
      precio_compra: 0,
      requiere_serie: false
    };
  }

  // Buscar el producto en la lista de productos disponibles
  let item = props.productos.find(i => i.id === entry.id);

  // Si no se encuentra en props.productos, intentar usar información del entry mismo
  // (útil cuando el producto viene de props.ordenCompra.items)
  if (!item && entry.nombre) {
    console.log('Producto no encontrado en lista, usando datos del entry:', entry);
    item = {
      id: entry.id,
      nombre: entry.nombre,
      descripcion: entry.descripcion || '',
      precio: entry.precio || 0,
      precio_compra: entry.precio_compra || 0,
      precio_venta: entry.precio_venta || entry.precio || 0,
      requiere_serie: entry.requiere_serie || false
    };
  }

  if (!item) {
    console.warn('Producto no encontrado para ID:', entry.id);
    console.log('Productos disponibles:', props.productos);
    return {
      nombre: 'Producto no encontrado',
      descripcion: '',
      precio: 0,
      precio_compra: 0,
      requiere_serie: false
    };
  }

  const result = {
    nombre: item.nombre || 'Sin nombre',
    descripcion: item.descripcion || '',
    precio: item.precio_venta || item.precio || 0,
    precio_compra: item.precio_compra || 0,
    requiere_serie: item.requiere_serie || false
  };

  console.log('Información del producto retornada:', result);
  return result;
};

// Función para calcular el subtotal sin descuento de un item
const calcularSubtotalSinDescuento = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const cantidad = Number.parseFloat(props.quantities[key]) || 1;
  const precio = Number.parseFloat(props.prices[key]) || 0;

  return cantidad * precio;
};

const handleQuantityInput = (entry, event) => {
  console.log(`handleQuantityInput llamado:`, {
    entry,
    eventTarget: event.target,
    value: event.target.value,
    valueType: typeof event.target.value
  });

  const value = event.target.value;
  updateQuantity(entry, value);
};

// Función para calcular el descuento de un item
const calcularDescuentoItem = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const subtotalSinDescuento = calcularSubtotalSinDescuento(entry);
  const descuento = Number.parseFloat(props.discounts[key]) || 0;

  return subtotalSinDescuento * (descuento / 100);
};

// Función para calcular el subtotal final de un item (con descuento)
const calcularSubtotalItem = (entry) => {
  const subtotalSinDescuento = calcularSubtotalSinDescuento(entry);
  const descuentoItem = calcularDescuentoItem(entry);

  return subtotalSinDescuento - descuentoItem;
};

// Función para eliminar un item
const eliminarItem = (entry) => {
  emit('eliminar-producto', entry);
};

// Función para actualizar cantidad
const updateQuantity = (entry, value) => {
  console.log(`ProductosSeleccionados - updateQuantity:`, {
    entry: entry?.tipo ? `${entry.tipo}-${entry.id}` : 'entry inválido',
    value,
    valueType: typeof value
  });

  // Validar que entry sea válido
  if (!entry || !entry.tipo || !entry.id) {
    console.error('Entry inválido:', entry);
    return;
  }

  const key = `${entry.tipo}-${entry.id}`;

  // Manejar valores undefined, null o vacíos
  if (value === undefined || value === null || value === '') {
    console.warn(`Valor vacío para ${key}, usando 1 como default`);
    value = '1';
  }

  // Convertir a número y asegurar que sea al menos 1
  const numericValue = Number.parseFloat(value);
  const quantity = isNaN(numericValue) ? 1 : Math.max(1, numericValue);

  console.log(`ProductosSeleccionados - Emitiendo:`, { key, quantity });

  emit('update-quantity', key, quantity);
};

// Función para actualizar descuento
const updateDiscount = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const discount = Math.min(100, Math.max(0, Number.parseFloat(value) || 0));
  emit('update-discount', key, discount);
};

// Función para actualizar precio
const updatePrice = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const price = Math.max(0, Number.parseFloat(value) || 0);
  emit('update-price', key, price);
};

// Variables para el modal de edición de precios
const showEditPriceModal = ref(false);
const productoSeleccionado = ref(null);
const precioActualOrden = ref(0);
const nuevoPrecio = ref('');
const notasCambio = ref('');
const historialPrecios = ref([]);
const loadingHistorial = ref(false);
const loadingGuardar = ref(false);

// Función para abrir modal de edición de precio
const openEditPriceModal = (entry) => {
  productoSeleccionado.value = getItemInfo(entry);
  precioActualOrden.value = prices.value[`${entry.tipo}-${entry.id}`] || 0;
  nuevoPrecio.value = precioActualOrden.value.toString();
  notasCambio.value = '';
  showEditPriceModal.value = true;

  // Cargar historial de precios
  cargarHistorialPrecios(entry.id);
};

// Función para cerrar modal
const closeEditPriceModal = () => {
  showEditPriceModal.value = false;
  productoSeleccionado.value = null;
  precioActualOrden.value = 0;
  nuevoPrecio.value = '';
  notasCambio.value = '';
  historialPrecios.value = [];
};

// Función para cargar historial de precios
const cargarHistorialPrecios = async (productoId) => {
  loadingHistorial.value = true;
  try {
    const response = await fetch(route('productos.historial-precios', productoId));
    const data = await response.json();

    if (data.success) {
      historialPrecios.value = data.historial;
    } else {
      console.error('Error al cargar historial:', data.message);
    }
  } catch (error) {
    console.error('Error de red:', error);
  } finally {
    loadingHistorial.value = false;
  }
};

// Función para guardar nuevo precio
const guardarNuevoPrecio = async () => {
  if (!nuevoPrecio.value || !productoSeleccionado.value) return;

  loadingGuardar.value = true;
  try {
    // Aquí necesitarías obtener el ID de la orden de compra actual
    // Por ahora, asumiré que viene de las props o contexto
    const ordenCompraId = window.ordenCompraId || null; // Necesitarás pasar este valor

    if (!ordenCompraId) {
      throw new Error('ID de orden de compra no disponible');
    }

    const response = await fetch(route('ordenescompra.editar-precio-producto', {
      ordenId: ordenCompraId,
      productoId: productoSeleccionado.value.id
    }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({
        precio: parseFloat(nuevoPrecio.value),
        notas: notasCambio.value
      })
    });

    const data = await response.json();

    if (data.success) {
      // Actualizar el precio en el componente padre
      const key = `producto-${productoSeleccionado.value.id}`;
      emit('update-price', key, parseFloat(nuevoPrecio.value));

      closeEditPriceModal();

      // Mostrar notificación de éxito
      if (window.notyf) {
        window.notyf.success(data.message || 'Precio actualizado exitosamente');
      }
    } else {
      throw new Error(data.message || 'Error al actualizar precio');
    }
  } catch (error) {
    console.error('Error al guardar precio:', error);
    if (window.notyf) {
      window.notyf.error('Error al actualizar el precio: ' + error.message);
    }
  } finally {
    loadingGuardar.value = false;
  }
};

// Función para ver historial completo
const verHistorial = (entry) => {
  // Navegar a la página completa de historial de precios
  window.open(route('reportes.historial-precios', entry.id), '_blank');
};

// Función para contar series capturadas
const serialCount = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials.length : 0;
};

// Función para obtener las series como string separado por comas
const getSerialsString = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials.join(', ') : '';
};

// Función para actualizar las series desde el input de texto
const updateSerials = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = (value || '')
    .split(/,|\r?\n/)
    .map(s => s.trim())
    .filter(s => s.length > 0);
  const uniqueSerials = [...new Set(serials)];
  emit('update-serials', key, uniqueSerials);
};
</script>

<style scoped>
/* Animaciones para la entrada de productos */
@keyframes slideInFromRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.productos-seleccionados > div:not(:first-child) {
  animation: slideInFromRight 0.3s ease-out;
}

/* Estilos para hover en productos */
.hover-product {
  transition: all 0.3s ease;
}

.hover-product:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Estilos para inputs focus */
input:focus {
  transform: scale(1.02);
  transition: transform 0.2s ease;
}

/* Estilos para botones de eliminar */
.delete-btn {
  transition: all 0.2s ease;
}

.delete-btn:hover {
  transform: scale(1.1);
}

/* Estilos responsivos */
@media (max-width: 640px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .grid-cols-4 {
    grid-template-columns: 1fr;
  }
}

/* Estilos para indicadores de estado */
.readonly-input {
  background-image: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 2px,
    rgba(0, 0, 0, 0.05) 2px,
    rgba(0, 0, 0, 0.05) 4px
  );
}

/* Efectos de loading */
.loading {
  opacity: 0.7;
  pointer-events: none;
}

/* Animación para números que cambian */
.number-change {
  transition: all 0.3s ease;
}

.number-increase {
  color: #10b981;
  font-weight: bold;
}

.number-decrease {
  color: #ef4444;
  font-weight: bold;
}
</style>


