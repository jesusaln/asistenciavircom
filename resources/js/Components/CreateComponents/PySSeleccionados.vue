<template>
  <div class="productos-seleccionados">
    <div v-if="selectedProducts.length > 0" class="mt-6">
      <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Productos y Servicios Seleccionados
        <span class="ml-2 bg-indigo-500/10 text-indigo-400 text-xs font-medium px-2.5 py-0.5 rounded-full border border-indigo-500/20">
          {{ selectedProducts.length }}
        </span>
      </h3>

      <div class="space-y-4">
        <div
          v-for="entry in selectedProducts"
          :key="`${entry.tipo}-${entry.id}`"
          class="bg-slate-900/40 border border-slate-800 rounded-2xl p-6 hover:bg-slate-900/60 transition-all duration-200 group"
        >
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider mr-3"
                          :class="entry.tipo === 'producto' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20'">
                      {{ entry.tipo === 'producto' ? 'Producto' : 'Servicio' }}
                    </span>
                  </div>
 
                  <h4 class="text-lg font-semibold text-white mb-1 leading-tight">{{ getItemInfo(entry).nombre }}</h4>
                  <p v-if="getItemInfo(entry).descripcion" class="text-sm text-slate-400 mb-2 line-clamp-1">{{ getItemInfo(entry).descripcion }}</p>

                  <div v-if="getItemInfo(entry).tipo_producto === 'kit' && getItemInfo(entry).kit_items && getItemInfo(entry).kit_items.length > 0" class="mt-4 mb-4 bg-slate-950 border border-indigo-500/20 rounded-2xl p-4 shadow-inner">
                    <p class="text-[10px] font-black text-indigo-400 mb-3 flex items-center uppercase tracking-widest">
                      <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                      </svg>
                      Contenido del Bundle
                    </p>
                    <ul class="space-y-2">
                      <li v-for="kItem in getItemInfo(entry).kit_items" :key="kItem.id" class="text-xs text-slate-300 flex justify-between items-center group/kitem">
                        <span class="flex-1 font-medium group-hover/kitem:text-white transition-colors">{{ kItem.item ? kItem.item.nombre : 'Producto no disponible' }}</span>
                        <span class="font-black bg-indigo-500/10 text-indigo-400 px-2 py-0.5 rounded-lg text-[9px] ml-3 border border-indigo-500/10 uppercase tracking-tighter">x{{ kItem.cantidad }} UNID</span>
                      </li>
                    </ul>
                  </div>

                  <div class="space-y-1">
                    <div class="flex items-center text-xs text-slate-500">
                      <svg class="w-4 h-4 mr-1 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                      </svg>
                      P. Unitario: <span class="text-slate-300 ml-1 font-medium font-mono">${{ getItemInfo(entry).precio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    </div>
                  </div>
                </div>

                <button
                  type="button"
                  @click="eliminarItem(entry)"
                  class="text-slate-500 hover:text-rose-500 hover:bg-rose-500/10 p-2.5 rounded-xl transition-all duration-200 flex-shrink-0 border border-transparent hover:border-rose-500/20"
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
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Cant</label>
                <input type="number"
                       :value="quantities[`${entry.tipo}-${entry.id}`] || 1"
                       min="1" step="1"
                       @input="e => updateQuantity(entry, e.target.value)"
                       class="w-full bg-slate-950 px-3 py-2 text-sm border border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-white transition-all"/>
              </div>
 
              <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Desc %</label>
                <input type="number"
                       :value="discounts[`${entry.tipo}-${entry.id}`] || 0"
                       min="0" max="100" step="0.01"
                       @input="e => updateDiscount(entry, e.target.value)"
                       class="w-full bg-slate-950 px-3 py-2 text-sm border border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-white transition-all"/>
              </div>
 
              <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Precio</label>
                <input type="text"
                       :value="'$' + (prices[`${entry.tipo}-${entry.id}`] || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"
                       readonly
                       class="w-full bg-slate-800/50 px-3 py-2 text-sm border border-slate-800 rounded-xl text-slate-500 font-mono cursor-not-allowed"/>
              </div>
 
              <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Subtotal</label>
                <div class="px-3 py-2 text-sm font-black text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-xl font-mono text-center">
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
              <span class="text-[11px] text-gray-500 dark:text-gray-400">
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
      <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No hay productos seleccionados</p>
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
  const itemFound = items.find(i => i.id === entry.id);
  
  // Usar el item encontrado en props o el entry mismo si ya trae la info
  const item = itemFound || entry;

  if (!item) return { nombre: 'Item no encontrado', descripcion: '', precio: 0, precio_compra: 0, requiere_serie: false };

  return {
    nombre: item.nombre || item.descripcion || 'Item sin nombre',
    descripcion: item.descripcion || '',
    precio: entry.tipo === 'producto' ? (item.precio_venta || item.precio || 0) : (item.precio || 0),
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

