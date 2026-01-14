<template>
  <div class="productos-seleccionados">
    <div v-if="selectedProducts.length > 0" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Productos y Servicios Seleccionados
        <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ selectedProducts.length }}
        </span>
      </h3>

      <div class="space-y-4">
        <div
          v-for="entry in selectedProducts"
          :key="`${entry.tipo}-${entry.id}`"
          class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-all duration-200"
        >
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-3"
                          :class="entry.tipo === 'producto' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                      {{ entry.tipo === 'producto' ? 'Producto' : 'Servicio' }}
                    </span>
                  </div>

                  <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ getItemInfo(entry).nombre }}</h4>
                  <p v-if="getItemInfo(entry).descripcion" class="text-sm text-gray-600 mb-2">{{ getItemInfo(entry).descripcion }}</p>

                  <div v-if="getItemInfo(entry).tipo_producto === 'kit' && getItemInfo(entry).kit_items && getItemInfo(entry).kit_items.length > 0" class="mt-2 mb-3 bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <p class="text-xs font-semibold text-blue-800 mb-2 flex items-center">
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                      </svg>
                      Contenido del Kit:
                    </p>
                    <ul class="space-y-1">
                      <li v-for="kItem in getItemInfo(entry).kit_items" :key="kItem.id" class="text-xs text-blue-700 flex justify-between items-center border-b border-blue-100 last:border-0 pb-1 last:pb-0">
                        <span class="flex-1">{{ kItem.item ? kItem.item.nombre : 'Producto no disponible' }}</span>
                        <span class="font-medium bg-blue-100 px-2 py-0.5 rounded-full text-[10px] ml-2">x{{ kItem.cantidad }}</span>
                      </li>
                    </ul>
                  </div>

                  <div class="space-y-1">
                    <div class="flex items-center text-sm text-gray-500">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                      </svg>
                      Precio unitario: ${{ getItemInfo(entry).precio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </div>
                  </div>
                </div>

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

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 lg:w-96">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                <input type="number"
                       :value="quantities[`${entry.tipo}-${entry.id}`] || 1"
                       min="1" step="1"
                       @input="e => updateQuantity(entry, e.target.value)"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"/>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Descuento %</label>
                <input type="number"
                       :value="discounts[`${entry.tipo}-${entry.id}`] || 0"
                       min="0" max="100" step="0.01"
                       @input="e => updateDiscount(entry, e.target.value)"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"/>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Precio Unit.</label>
                <input type="text"
                       :value="'$' + (prices[`${entry.tipo}-${entry.id}`] || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"
                       readonly
                       class="w-full px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed"/>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Subtotal</label>
                <div class="px-3 py-2 text-sm font-semibold text-green-600 bg-green-50 border border-green-200 rounded-lg">
                  ${{ calcularSubtotalItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="entry.tipo === 'producto' && (getItemInfo(entry)?.requiere_serie || getItemInfo(entry)?.tipo_producto === 'kit')"
            class="mt-3 space-y-2"
          >
            <div class="flex items-center gap-2">
              <span class="block text-xs font-medium text-gray-700">
                {{ getItemInfo(entry)?.tipo_producto === 'kit' ? 'Series de componentes' : 'Series requeridas' }}
              </span>
              <span class="text-[11px] text-gray-500">
                {{
                  getItemInfo(entry)?.tipo_producto === 'kit'
                    ? 'Selecciona series para cada componente del kit'
                    : `Necesitas ${quantities[`${entry.tipo}-${entry.id}`] || 1} serie(s)`
                }}
              </span>
            </div>

            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="getItemInfo(entry)?.tipo_producto === 'kit' ? emit('open-kit-serials', entry) : emit('open-serials', entry)"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Seleccionar series
              </button>

              <span class="text-xs text-gray-600 truncate">
                {{ getSerialsString(entry) || (getItemInfo(entry)?.tipo_producto === 'kit' ? 'Series por componente pendientes' : 'Sin series seleccionadas') }}
              </span>
            </div>

            <div v-if="getItemInfo(entry)?.requiere_serie && getSerials(entry).length > 0" class="flex flex-wrap gap-1">
              <span
                v-for="serie in getSerials(entry)"
                :key="serie"
                class="inline-flex items-center px-2 py-1 text-[11px] font-medium bg-gray-100 text-gray-700 rounded"
              >
                {{ serie }}
              </span>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div v-else class="mt-6 p-8 border-2 border-dashed border-gray-300 rounded-xl text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
      </svg>
      <p class="text-gray-500 text-lg font-medium">No hay productos seleccionados</p>
      <p class="text-gray-400 text-sm mt-1">Busca y agrega productos o servicios para comenzar</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  selectedProducts: { type: Array, required: true },
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  quantities: { type: Object, required: true },
  prices: { type: Object, required: true },
  discounts: { type: Object, required: true },
  serials: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['eliminar-producto','update-quantity','update-discount','update-serials','open-serials','open-kit-serials','calcular-total']);

const getItemInfo = (entry) => {
  const items = entry.tipo === 'producto' ? props.productos : props.servicios;
  const item = items.find(i => i.id === entry.id);
  if (!item) return { nombre: 'Item no encontrado', descripcion: '', precio: 0, precio_compra: 0, requiere_serie: false };
  return {
    nombre: item.nombre,
    descripcion: item.descripcion || '',
    precio: entry.tipo === 'producto' ? (item.precio_venta || 0) : (item.precio || 0),
    precio_compra: item.precio_compra || 0,
    requiere_serie: !!item.requiere_serie,
    tipo_producto: item.tipo_producto,
    kit_items: item.kit_items || item.kitItems || [],
  };
};

const eliminarItem = (entry) => emit('eliminar-producto', entry);

const updateQuantity = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const numericValue = Number.parseFloat(value);
  const quantity = isNaN(numericValue) ? 1 : Math.max(1, numericValue);
  emit('update-quantity', key, quantity);
};

const updateDiscount = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const discount = Math.min(100, Math.max(0, Number.parseFloat(value) || 0));
  emit('update-discount', key, discount);
};

const calcularSubtotalSinDescuento = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const cantidad = Number.parseFloat(props.quantities[key]) || 1;
  const precio = Number.parseFloat(props.prices[key]) || 0;
  return cantidad * precio;
};

const calcularDescuentoItem = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const subtotalSinDescuento = calcularSubtotalSinDescuento(entry);
  const descuento = Number.parseFloat(props.discounts[key]) || 0;
  return subtotalSinDescuento * (descuento / 100);
};

const calcularSubtotalItem = (entry) => {
  const subtotalSinDescuento = calcularSubtotalSinDescuento(entry);
  const descuentoItem = calcularDescuentoItem(entry);
  return subtotalSinDescuento - descuentoItem;
};

const getSerialsString = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials.join(', ') : '';
};

const getSerials = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials : [];
};

const updateSerials = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = (value || '')
    .split(/,|\r?\n/)
    .map(s => s.trim())
    .filter(s => s.length > 0);
  const uniqueSerials = [...new Set(serials)];
  emit('update-serials', key, uniqueSerials);
};

// Contar series seleccionadas actualmente
const serialCount = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials.length : 0;
};
</script>

