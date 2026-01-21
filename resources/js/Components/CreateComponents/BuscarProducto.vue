<template>
  <div class="buscar-producto">
    <!-- Campo de búsqueda -->
    <div class="mb-6">
      <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2 uppercase tracking-wide">
        {{ label }}
      </label>
      <div class="relative group">
        <input
          ref="inputBusqueda"
          type="text"
          v-model="busqueda"
          @input="filtrarItems"
          @focus="mostrarLista = true"
          :placeholder="placeholder"
          class="w-full px-4 py-3 bg-white dark:bg-slate-950 border-2 border-gray-200 dark:border-slate-800 rounded-xl focus:ring-0 focus:border-indigo-500 dark:focus:border-indigo-500 text-sm font-medium text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 transition-all shadow-sm"
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none transition-colors group-focus-within:text-indigo-500 text-gray-400 dark:text-slate-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
      </div>
      
      <!-- Filtros rápidos -->
      <div class="flex flex-wrap gap-2 mt-3">
        <button
          type="button"
          @click="filtroActivo = 'todos'"
          :class="[
            'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border',
            filtroActivo === 'todos'
              ? 'bg-slate-800 dark:bg-slate-100 text-white dark:text-slate-900 border-slate-800 dark:border-slate-100 shadow-md transform scale-105'
              : 'bg-white dark:bg-slate-900 text-gray-500 dark:text-slate-500 border-gray-200 dark:border-slate-800 hover:border-gray-300 dark:hover:border-slate-600'
          ]"
        >
          {{ textoTodos }} ({{ itemsFiltrados.length }})
        </button>
        <button
          type="button"
          @click="filtroActivo = 'productos'"
          :class="[
            'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border',
            filtroActivo === 'productos'
              ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/20 transform scale-105'
              : 'bg-white dark:bg-slate-900 text-gray-500 dark:text-slate-500 border-gray-200 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-900 hover:text-blue-500'
          ]"
        >
          {{ textoProductos }} ({{ productosCount }})
        </button>
        <button
          v-if="!soloProductos"
          type="button"
          @click="filtroActivo = 'servicios'"
          :class="[
            'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border',
            filtroActivo === 'servicios'
              ? 'bg-purple-600 text-white border-purple-600 shadow-md shadow-purple-500/20 transform scale-105'
              : 'bg-white dark:bg-slate-900 text-gray-500 dark:text-slate-500 border-gray-200 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-900 hover:text-purple-500'
          ]"
        >
          {{ textoServicios }} ({{ serviciosCount }})
        </button>
      </div>
    </div>

    <!-- Usar Teleport para renderizar fuera del componente -->
    <Teleport to="#app">
      <div
        v-if="mostrarLista && itemsFiltrados.length > 0"
        class="z-[100] mt-2 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-2xl shadow-2xl max-h-96 overflow-y-auto ring-1 ring-black/5 dark:ring-white/10"
        :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }"
      >
        <!-- Encabezados -->
        <div class="sticky top-0 bg-gray-50/95 dark:bg-slate-950/95 backdrop-blur-sm border-b border-gray-100 dark:border-slate-800 px-4 py-3 z-10">
          <div class="grid grid-cols-12 gap-3 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-slate-500">
            <div class="col-span-1 text-center">Tipo</div>
            <div class="col-span-3">Descripción</div>
            <div class="col-span-2">Código</div>
            <div class="col-span-2">Categoría</div>
            <div class="col-span-2 text-right">Precio</div>
            <div class="col-span-1 text-center">Stock</div>
            <div class="col-span-1 text-center">Acción</div>
          </div>
        </div>
        
        <!-- Items -->
        <div
          v-for="item in itemsFiltrados"
          :key="`${item.tipo}-${item.id}`"
          @mousedown.prevent="agregarItem(item)"
          class="group px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800/50 last:border-b-0 transition-colors cursor-pointer"
        >
          <div class="grid grid-cols-12 gap-3 items-center">
            <!-- Tipo Badge -->
            <div class="col-span-1 flex justify-center">
              <span :class="[
                'w-6 h-6 flex items-center justify-center rounded-lg text-[10px] font-black shadow-sm',
                item.tipo === 'producto'
                  ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                  : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400'
              ]">
                {{ item.tipo === 'producto' ? 'P' : 'S' }}
              </span>
            </div>
            
            <!-- Nombre y Desc -->
            <div class="col-span-3">
              <div class="font-bold text-gray-900 dark:text-white text-xs leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                  {{ item.nombre }}
              </div>
              <div v-if="item.descripcion" class="text-[10px] text-gray-400 dark:text-slate-500 truncate mt-0.5">
                  {{ item.descripcion }}
              </div>
            </div>
            
            <!-- Código -->
            <div class="col-span-2">
              <span class="text-xs font-mono text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                  {{ item.codigo || '---' }}
              </span>
            </div>
            
            <!-- Categoría -->
            <div class="col-span-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">
                  {{ typeof item.categoria === 'string' ? item.categoria : (item.categoria?.nombre || 'General') }}
              </span>
            </div>
            
            <!-- Precio -->
            <div class="col-span-2 text-right">
              <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                ${{ formatearPrecio(getPrecioMostrar(item)) }}
              </span>
            </div>
            
            <!-- Stock -->
            <div class="col-span-1 flex justify-center">
              <div v-if="item.tipo === 'producto'">
                <span v-if="item.tipo_producto === 'kit'" class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 uppercase">
                  Kit
                </span>
                <template v-else>
                  <span :class="[
                    'text-[10px] px-2 py-0.5 rounded-full font-bold',
                    getStock(item) > 10 ? 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' :
                    getStock(item) > 0 ? 'bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' :
                    'bg-rose-100 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400'
                  ]">
                    {{ getStock(item) }}
                  </span>
                </template>
              </div>
              <span v-else class="text-xs text-gray-300 dark:text-slate-600">∞</span>
            </div>
            
            <!-- Botón agregar -->
            <div class="col-span-1 flex justify-center">
              <button
                type="button"
                @mousedown.prevent="agregarItem(item)"
                :disabled="props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0"
                :class="[
                  'w-8 h-8 flex items-center justify-center rounded-xl transition-all duration-200 transform active:scale-95 shadow-sm',
                  props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0
                    ? 'bg-gray-100 dark:bg-slate-800 text-gray-300 dark:text-slate-600 cursor-not-allowed'
                    : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-500/30 hover:shadow-indigo-500/50'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-if="busqueda && itemsFiltrados.length === 0" class="z-50 px-4 py-12 text-center bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-2xl shadow-xl" :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }">
        <div class="w-16 h-16 mx-auto bg-gray-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-3">
             <svg class="w-8 h-8 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
             </svg>
        </div>
        <p class="text-sm font-bold text-gray-900 dark:text-white">No encontramos coincidencias</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Intenta buscar con otro nombre o código</p>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { resolverPrecio } from '@/Utils/precioHelper';

const props = defineProps({
  productos: {
    type: Array,
    default: () => [],
  },
  servicios: {
    type: Array,
    default: () => [],
  },
  validarStock: {
    type: Boolean,
    default: true,
  },
  label: {
    type: String,
    default: 'Buscar Productos y Servicios',
  },
  placeholder: {
    type: String,
    default: 'Buscar por nombre, código, categoría o descripción...',
  },
  textoTodos: {
    type: String,
    default: 'Todos',
  },
  textoProductos: {
    type: String,
    default: 'Productos',
  },
  textoServicios: {
    type: String,
    default: 'Servicios',
  },
  soloProductos: {
    type: Boolean,
    default: false,
  },
  almacenId: {
    type: [Number, String],
    default: null,
  },
  priceListId: {
    type: [Number, String, null],
    default: null,
  },
});

const emit = defineEmits(['agregar-producto']);

// Variables reactivas
const busqueda = ref('');
const mostrarLista = ref(false);
const filtroActivo = ref(props.soloProductos ? 'productos' : 'todos');
const productosRecientes = ref([]);
const inputBusqueda = ref(null);
const inputWidth = ref(0);
const inputPosition = ref({ top: 0, left: 0, height: 0 });

// Exponer el método focus
defineExpose({
  focus: () => {
    if (inputBusqueda.value) {
      inputBusqueda.value.focus();
    }
  }
});

// Combinar productos y servicios con tipo
const todosLosItems = computed(() => {
  const productosConTipo = (props.productos || []).map(producto => ({
    ...producto,
    tipo: 'producto'
  }));
  const serviciosConTipo = props.soloProductos ? [] : (props.servicios || []).map(servicio => ({
    ...servicio,
    tipo: 'servicio'
  }));
  return [...productosConTipo, ...serviciosConTipo];
});

// Filtrar items según búsqueda y filtro activo
const itemsFiltrados = computed(() => {
  let items = todosLosItems.value;
  // Filtrar por tipo
  if (filtroActivo.value === 'productos') {
    items = items.filter(item => item.tipo === 'producto');
  } else if (filtroActivo.value === 'servicios') {
    items = items.filter(item => item.tipo === 'servicio');
  }
  // Filtrar por búsqueda
  if (busqueda.value) {
    const termino = busqueda.value.toLowerCase();
    items = items.filter(item =>
      item.nombre.toLowerCase().includes(termino) ||
      (item.codigo && item.codigo.toLowerCase().includes(termino)) ||
      (item.categoria && (
        typeof item.categoria === 'string'
          ? item.categoria.toLowerCase().includes(termino)
          : item.categoria.nombre.toLowerCase().includes(termino)
      )) ||
      (item.descripcion && item.descripcion.toLowerCase().includes(termino))
    );
  }
  return items.slice(0, 50); // Limitar a 50 resultados
});

// Contadores para los filtros
const productosCount = computed(() => {
  return todosLosItems.value.filter(item => item.tipo === 'producto').length;
});

const serviciosCount = computed(() => {
  // Contar servicios disponibles
  return todosLosItems.value.filter(item => item.tipo === 'servicio').length;
});

// Funciones
const filtrarItems = () => {
  mostrarLista.value = true;
  actualizarPosicionLista();
};

const agregarItem = (item) => {
  // Verificar stock para productos solo si validarStock es true (excluyendo kits)
  if (props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0) {
    return;
  }
  // Agregar a productos recientes
  const itemReciente = { ...item };
  const index = productosRecientes.value.findIndex(
    p => p.id === item.id && p.tipo === item.tipo
  );
  if (index === -1) {
    productosRecientes.value.unshift(itemReciente);
    // Mantener solo los últimos 5
    if (productosRecientes.value.length > 5) {
      productosRecientes.value.pop();
    }
  }
  // Emitir evento al componente padre
  emit('agregar-producto', item);
  // Limpiar búsqueda y ocultar lista
  busqueda.value = '';
  mostrarLista.value = false;
};

const formatearPrecio = (precio) => {
  const precioNum = Number.parseFloat(precio) || 0;
  return precioNum.toLocaleString('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const actualizarPosicionLista = () => {
  if (!inputBusqueda.value) return;
  const rect = inputBusqueda.value.getBoundingClientRect();
  inputWidth.value = rect.width;
  inputPosition.value = {
    top: rect.top + window.scrollY,
    left: rect.left + window.scrollX,
    height: rect.height
  };
};

// Cerrar lista cuando se hace clic fuera
const cerrarLista = (event) => {
  if (!event.target.closest('.buscar-producto')) {
    mostrarLista.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', cerrarLista);
});

onUnmounted(() => {
  document.removeEventListener('click', cerrarLista);
});

const getStock = (item) => {
  if (item.tipo !== 'producto') return 9999;
  
  if (props.almacenId && item.inventarios) {
    const inventario = item.inventarios.find(inv => String(inv.almacen_id) === String(props.almacenId));
    return inventario ? parseFloat(inventario.cantidad) : 0;
  }
  
  return parseFloat(item.stock_total || item.stock || 0);
};

const getAvailabilityInfo = (item) => {
  if (item.tipo !== 'producto' || !props.almacenId || !item.inventarios) return null;
  
  const otrosAlmacenes = item.inventarios.filter(inv => 
    String(inv.almacen_id) !== String(props.almacenId) && parseFloat(inv.cantidad) > 0
  );
  
  if (otrosAlmacenes.length > 0) {
    const nombres = otrosAlmacenes.map(inv => inv.almacen?.nombre || 'Otro').join(', ');
    return `Disponible en: ${nombres}`;
  }
  
  return null;
};

const getPrecioMostrar = (item) => {
  if (item.tipo === 'producto') {
    return resolverPrecio(item, props.priceListId);
  }
  return item.precio || item.precio_venta || 0;
};
</script>

<style>
/* Estilos adicionales si es necesario */
</style>
