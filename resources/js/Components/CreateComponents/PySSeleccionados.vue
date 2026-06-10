<template>
  <div class="productos-seleccionados">
    <div v-if="selectedProducts.length > 0" class="mt-6">
      <h3 class="text-sm font-black text-slate-900 dark:text-white mb-5 flex items-center uppercase tracking-[0.15em]">
        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Items Seleccionados
        <span class="ml-3 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[10px] font-black px-2.5 py-1 rounded-full border border-indigo-500/20">
          {{ selectedProducts.length }}
        </span>
      </h3>

      <!-- Tabla Header -->
      <div class="hidden lg:grid grid-cols-12 gap-3 px-6 py-3 mb-2 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">
        <div class="col-span-4">Producto / Servicio</div>
        <div class="col-span-2 text-center">Cantidad</div>
        <div class="col-span-2 text-center">Precio</div>
        <div class="col-span-1 text-center">Desc %</div>
        <div class="col-span-2 text-right">Subtotal</div>
        <div class="col-span-1 text-center"></div>
      </div>

      <div class="space-y-3">
        <div
          v-for="entry in selectedProducts"
          :key="`${entry.tipo}-${entry.id}`"
          class="bg-white dark:bg-slate-900/80 border border-slate-100 dark:border-slate-800 rounded-2xl hover:shadow-lg hover:border-slate-200 dark:hover:border-slate-700 transition-all duration-300 group overflow-hidden"
        >
          <!-- Desktop Layout -->
          <div class="hidden lg:grid grid-cols-12 gap-3 items-center px-6 py-4">
            <!-- Nombre y tipo -->
            <div class="col-span-4 min-w-0">
              <div class="flex items-center gap-3">
                <span class="flex-shrink-0 inline-flex items-center px-2 py-1 rounded-xl text-[9px] font-black uppercase tracking-wider"
                      :class="entry.tipo === 'producto'
                        ? 'bg-blue-50 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-800'
                        : 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800'">
                  {{ entry.tipo === 'producto' ? 'PROD' : 'SERV' }}
                </span>
                <div class="min-w-0">
                  <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate leading-tight">{{ getItemInfo(entry).nombre }}</h4>
                  <div class="flex flex-wrap gap-1 mt-1">
                    <span v-for="tag in getTrazabilidadTags(getItemInfo(entry).nombre)" :key="tag"
                          class="px-1 py-0.5 rounded-xl text-[8px] font-black border bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
                      {{ tag }}
                    </span>
                    <p v-if="getItemInfo(entry).descripcion" class="text-[10px] text-slate-400 dark:text-slate-500 truncate inline-block ml-1">{{ getItemInfo(entry).descripcion }}</p>
                  </div>
                </div>
              </div>
              <!-- Kit components inline pill -->
              <div v-if="getItemInfo(entry).tipo_producto === 'kit' && getItemInfo(entry).kit_items && getItemInfo(entry).kit_items.length > 0"
                   class="mt-2 ml-12 flex flex-wrap gap-1">
                <span v-for="kItem in getItemInfo(entry).kit_items" :key="kItem.id"
                      class="inline-flex items-center text-[9px] font-bold px-2 py-0.5 rounded-xl bg-indigo-50 dark:bg-sky-900/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                  {{ kItem.item ? kItem.item.nombre : 'N/A' }} x{{ kItem.cantidad }}
                </span>
              </div>
            </div>

            <!-- Cantidad -->
            <div class="col-span-2 flex justify-center">
              <div class="flex items-center bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <button type="button" @click="decrementQuantity(entry)"
                        class="px-2 py-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" /></svg>
                </button>
                <input type="number"
                       :value="quantities[`${entry.tipo}-${entry.id}`] || 1"
                       min="1" step="1"
                       @input="e => updateQuantity(entry, e.target.value)"
                       class="w-14 text-center bg-transparent border-0 py-2 text-sm font-black text-slate-900 dark:text-white focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"/>
                <button type="button" @click="incrementQuantity(entry)"
                        class="px-2 py-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                </button>
              </div>
            </div>

            <!-- Precio (editable) -->
            <div class="col-span-2 flex justify-center">
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs font-bold">$</span>
                <input type="number"
                       :value="prices[`${entry.tipo}-${entry.id}`] || 0"
                       min="0" step="0.01"
                       @input="e => updatePrice(entry, e.target.value)"
                       class="w-28 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-7 pr-3 py-2 text-sm font-bold text-slate-900 dark:text-white text-right focus:border-brand-500 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"/>
              </div>
            </div>

            <!-- Descuento -->
            <div class="col-span-1 flex justify-center">
              <div class="relative">
                <input type="number"
                       :value="discounts[`${entry.tipo}-${entry.id}`] || 0"
                       min="0" max="100" step="0.5"
                       @input="e => updateDiscount(entry, e.target.value)"
                       class="w-16 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2 py-2 text-sm font-bold text-slate-900 dark:text-white text-center focus:border-brand-500 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"/>
                <span class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 text-[10px] font-black">%</span>
              </div>
            </div>

            <!-- Subtotal -->
            <div class="col-span-2 text-right">
              <div class="text-sm font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                ${{ calcularSubtotalItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </div>
              <div v-if="(discounts[`${entry.tipo}-${entry.id}`] || 0) > 0" class="text-[10px] text-rose-500 font-bold mt-0.5">
                -${{ calcularDescuentoItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </div>
            </div>

            <!-- Eliminar -->
            <div class="col-span-1 flex justify-center">
              <button
                type="button"
                @click="eliminarItem(entry)"
                class="w-8 h-8 flex items-center justify-center text-slate-300 dark:text-slate-600 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all duration-200"
                title="Eliminar"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Mobile Layout -->
          <div class="lg:hidden p-5">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-xl text-[9px] font-black uppercase"
                        :class="entry.tipo === 'producto'
                          ? 'bg-blue-50 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400'
                          : 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400'">
                    {{ entry.tipo === 'producto' ? 'Producto' : 'Servicio' }}
                  </span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ getItemInfo(entry).nombre }}</h4>
              </div>
              <button type="button" @click="eliminarItem(entry)"
                      class="text-slate-300 dark:text-slate-600 hover:text-rose-500 p-1 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Kit components for mobile -->
            <div v-if="getItemInfo(entry).tipo_producto === 'kit' && getItemInfo(entry).kit_items && getItemInfo(entry).kit_items.length > 0"
                 class="mb-4 p-3 bg-indigo-50 dark:bg-sky-900/10 rounded-xl border border-indigo-100 dark:border-indigo-800">
              <p class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wide mb-2">Componentes</p>
              <div class="flex flex-wrap gap-1">
                <span v-for="kItem in getItemInfo(entry).kit_items" :key="kItem.id"
                      class="text-[9px] font-bold px-2 py-0.5 rounded-xl bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                  {{ kItem.item ? kItem.item.nombre : 'N/A' }} x{{ kItem.cantidad }}
                </span>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Cantidad</label>
                <input type="number"
                       :value="quantities[`${entry.tipo}-${entry.id}`] || 1"
                       min="1" step="1"
                       @input="e => updateQuantity(entry, e.target.value)"
                       class="w-full bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm font-bold border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:border-brand-500 focus:ring-0"/>
              </div>
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Precio</label>
                <input type="number"
                       :value="prices[`${entry.tipo}-${entry.id}`] || 0"
                       min="0" step="0.01"
                       @input="e => updatePrice(entry, e.target.value)"
                       class="w-full bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm font-bold border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:border-brand-500 focus:ring-0"/>
              </div>
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Descuento %</label>
                <input type="number"
                       :value="discounts[`${entry.tipo}-${entry.id}`] || 0"
                       min="0" max="100" step="0.5"
                       @input="e => updateDiscount(entry, e.target.value)"
                       class="w-full bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm font-bold border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:border-brand-500 focus:ring-0"/>
              </div>
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Subtotal</label>
                <div class="px-3 py-2 text-sm font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800 rounded-xl text-center tabular-nums">
                  ${{ calcularSubtotalItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Series section -->
          <div
            v-if="entry.tipo === 'producto' && (getItemInfo(entry)?.requiere_serie || getItemInfo(entry)?.tipo_producto === 'kit')"
            class="px-6 py-3 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                  {{ getItemInfo(entry)?.tipo_producto === 'kit' ? 'Series por componente' : 'Series requeridas' }}
                </span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                  {{ getItemInfo(entry)?.tipo_producto === 'kit'
                    ? ''
                    : `(${serialCount(entry)}/${quantities[`${entry.tipo}-${entry.id}`] || 1})` }}
                </span>
              </div>
              <button
                type="button"
                @click="getItemInfo(entry)?.tipo_producto === 'kit' ? emit('open-kit-serials', entry) : emit('open-serials', entry)"
                :class="[
                  'inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all',
                  needsSerials(entry)
                    ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 hover:bg-brand-100 dark:hover:bg-brand-900/30 animate-pulse'
                    : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'
                ]"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                {{ needsSerials(entry) ? 'Seleccionar' : 'Series ✓' }}
              </button>
            </div>
            <!-- Show selected series (Simple Product) -->
            <div v-if="getItemInfo(entry)?.tipo_producto !== 'kit' && getSerials(entry).length > 0" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="serie in getSerials(entry)"
                :key="serie"
                class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl border border-slate-200 dark:border-slate-700"
              >
                {{ serie }}
              </span>
            </div>

            <!-- Show selected series (Kit) - Traceability grouped -->
            <div v-if="getItemInfo(entry)?.tipo_producto === 'kit'" class="mt-3 space-y-2">
               <div v-for="kItem in getItemInfo(entry).kit_items" :key="kItem.id" class="pl-2 border-l-2 border-slate-100 dark:border-slate-800">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                      {{ kItem.item?.nombre || 'Componente' }}
                    </span>
                    <div class="flex gap-1">
                       <span v-for="tag in getTrazabilidadTags(kItem.item?.nombre || '')" :key="tag"
                             class="px-1.5 py-0.5 rounded-xl text-[8px] font-black border bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
                         {{ tag }}
                       </span>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-1">
                     <span v-for="serie in getComponentSerials(entry.id, kItem)" :key="serie"
                           class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold bg-indigo-50 dark:bg-sky-900/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                        {{ serie }}
                     </span>
                     <span v-if="getComponentSerials(entry.id, kItem).length === 0" class="text-[9px] text-slate-400 italic">Sin series seleccionadas</span>
                  </div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="mt-6 p-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl text-center">
      <div class="w-16 h-16 mx-auto bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
      </div>
      <p class="text-slate-600 dark:text-slate-400 text-sm font-bold">No hay productos seleccionados</p>
      <p class="text-slate-400 dark:text-slate-500 text-xs mt-1">Busca y agrega productos o servicios para comenzar</p>
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

const emit = defineEmits(['eliminar-producto','update-quantity','update-price','update-discount','update-serials','open-serials','open-kit-serials','calcular-total']);

const getItemInfo = (entry) => {
  const items = entry.tipo === 'producto' ? props.productos : props.servicios;
  const itemFound = items.find(i => i.id === entry.id);
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

const incrementQuantity = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const current = Number.parseFloat(props.quantities[key]) || 1;
  emit('update-quantity', key, current + 1);
};

const decrementQuantity = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const current = Number.parseFloat(props.quantities[key]) || 1;
  if (current > 1) {
    emit('update-quantity', key, current - 1);
  }
};

const updatePrice = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const price = Math.max(0, Number.parseFloat(value) || 0);
  emit('update-price', key, price);
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

const needsSerials = (entry) => {
  const info = getItemInfo(entry);
  if (info?.tipo_producto === 'kit') {
    return serialCount(entry) === 0;
  }
  const key = `${entry.tipo}-${entry.id}`;
  const required = Number.parseFloat(props.quantities[key]) || 1;
  return serialCount(entry) < required;
};

const getSerialsString = (entry) => {
  const info = getItemInfo(entry);
  if (info?.tipo_producto === 'kit') {
    const kitId = entry.id;
    const componentSerials = [];
    Object.keys(props.serials || {}).forEach(k => {
      if (k.startsWith(`kit-${kitId}-component-`)) {
        const s = props.serials[k];
        if (Array.isArray(s) && s.length > 0) componentSerials.push(...s);
      }
    });
    return componentSerials.join(', ');
  }
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials.join(', ') : '';
};

const getSerials = (entry) => {
  const info = getItemInfo(entry);
  if (info?.tipo_producto === 'kit') {
    const kitId = entry.id;
    const componentSerials = [];
    Object.keys(props.serials || {}).forEach(k => {
      if (k.startsWith(`kit-${kitId}-component-`)) {
        const s = props.serials[k];
        if (Array.isArray(s) && s.length > 0) componentSerials.push(...s);
      }
    });
    return componentSerials;
  }
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials : [];
};

const serialCount = (entry) => {
  const info = getItemInfo(entry);
  if (info?.tipo_producto === 'kit') {
    const kitId = entry.id;
    let count = 0;
    Object.keys(props.serials || {}).forEach(k => {
      if (k.startsWith(`kit-${kitId}-component-`)) {
        const s = props.serials[k];
        if (Array.isArray(s)) count += s.length;
      }
    });
    return count;
  }
  const key = `${entry.tipo}-${entry.id}`;
  const serials = props.serials?.[key] || [];
  return Array.isArray(serials) ? serials.length : 0;
};

const getTrazabilidadTags = (nombre) => {
  if (!nombre) return [];
  const tags = [];
  const lower = nombre.toLowerCase();
  
  if (lower.includes('condensador')) tags.push('C');
  if (lower.includes('manejadora') || lower.includes('evaporador')) tags.push('M');
  if (lower.includes('solo frío')) tags.push('S/F');
  if (lower.includes('calor') || lower.includes('calefacción')) tags.push('C/H');
  
  return tags;
};

const getComponentSerials = (kitId, kItem) => {
  const item_id = kItem.item_id || kItem.item?.id;
  const key = `kit-${kitId}-component-${item_id}`;
  const series = props.serials?.[key] || [];
  return Array.isArray(series) ? series : [];
};
</script>
