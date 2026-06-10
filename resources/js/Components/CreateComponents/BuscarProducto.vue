<template>
  <div ref="root" class="buscar-producto">
    <!-- Campo de búsqueda -->
    <div class="mb-6">
      <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wide">
        {{ label }}
      </label>
      <div class="relative group">
        <input
          ref="inputBusqueda"
          type="text"
          v-model="busqueda"
          @input="handleInput"
          @focus="mostrarLista = true"
          :placeholder="placeholder"
          class="w-full px-4 py-3 bg-white dark:bg-slate-950 border-2 border-slate-200 dark:border-slate-800 rounded-xl focus:ring-0 focus:border-brand-500 dark:focus:border-brand-500 text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 transition-all shadow-sm"
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none transition-colors group-focus-within:text-indigo-500 text-slate-400 dark:text-slate-600">
          <svg v-if="!cargando" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </div>
      </div>
      
      <!-- Filtros rápidos -->
      <div class="flex flex-wrap gap-2 mt-3">
        <button
          type="button"
          @click="filtroActivo = 'todos'"
          :class="[
            'px-4 py-1.5 text-[10px] font-black uppercase tracking-wide rounded-xl transition-all border',
            filtroActivo === 'todos'
              ? 'bg-slate-800 dark:bg-slate-100 text-white dark:text-slate-900 border-slate-800 dark:border-slate-100 shadow-md transform scale-105'
              : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-600'
          ]"
        >
          {{ textoTodos }} ({{ itemsFiltrados.length }})
        </button>
        <button
          type="button"
          @click="filtroActivo = 'productos'"
          :class="[
            'px-4 py-1.5 text-[10px] font-black uppercase tracking-wide rounded-xl transition-all border',
            filtroActivo === 'productos'
              ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-sky-500/20 transform scale-105'
              : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-900 hover:text-blue-500'
          ]"
        >
          {{ textoProductos }} ({{ productosCount }})
        </button>
        <button
          v-if="!soloProductos"
          type="button"
          @click="filtroActivo = 'servicios'"
          :class="[
            'px-4 py-1.5 text-[10px] font-black uppercase tracking-wide rounded-xl transition-all border',
            filtroActivo === 'servicios'
              ? 'bg-purple-600 text-white border-purple-600 shadow-md shadow-purple-500/20 transform scale-105'
              : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-900 hover:text-purple-500'
          ]"
        >
          {{ textoServicios }} ({{ serviciosCount }})
        </button>
      </div>
    </div>

    <SearchDropdown
      :show="mostrarLista"
      :items="itemsFiltrados"
      :width="inputWidth"
      :position="inputPosition"
      max-height="24rem"
      :empty="!!busqueda"
      empty-title="No encontramos coincidencias"
      empty-subtitle="Intenta buscar con otro nombre o código"
      :item-key="item => `${item.tipo}-${item.id}`"
    >
      <template #header>
        <div class="grid grid-cols-12 gap-3 text-[10px] font-black uppercase tracking-wider text-[var(--ui-text-soft)]">
          <div class="col-span-1 text-center">Tipo</div>
          <div class="col-span-3">Descripción</div>
          <div class="col-span-2">Código</div>
          <div class="col-span-2">Categoría</div>
          <div class="col-span-2 text-right">Precio</div>
          <div class="col-span-1 text-center">Stock</div>
          <div class="col-span-1 text-center">Acción</div>
        </div>
      </template>

      <template #item="{ item }">
        <div
          @mousedown.prevent="agregarItem(item)"
          class="group px-4 py-3 hover:bg-black/5 dark:hover:bg-white/5 border-b border-[var(--ui-border)] last:border-b-0 transition-colors cursor-pointer"
        >
          <div class="grid grid-cols-12 gap-3 items-center">
            <div class="col-span-1 flex justify-center">
              <span :class="[
                'w-6 h-6 flex items-center justify-center rounded-xl text-[10px] font-black shadow-sm',
                item.tipo === 'producto'
                  ? 'bg-blue-50 dark:bg-sky-900/20/30 text-blue-700 dark:text-blue-400'
                  : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400'
              ]">
                {{ item.tipo === 'producto' ? 'P' : 'S' }}
              </span>
            </div>

            <div class="col-span-3">
              <div class="font-bold text-xs leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                  {{ item.nombre }}
              </div>
              <div v-if="item.descripcion" class="text-[10px] text-[var(--ui-text-soft)] truncate mt-0.5">
                  {{ item.descripcion }}
              </div>
              <div v-if="getStock(item) <= 0 && getAvailabilityInfo(item)" class="text-[10px] text-brand-600 dark:text-brand-400 font-bold mt-1 animate-pulse">
                {{ getAvailabilityInfo(item) }}
              </div>
            </div>

            <div class="col-span-2">
              <span class="text-xs font-mono text-[var(--ui-text-muted)] bg-[var(--ui-surface-alt)] px-1.5 py-0.5 rounded-xl border border-[var(--ui-border)]">
                  {{ item.codigo || '---' }}
              </span>
            </div>

            <div class="col-span-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--ui-text-soft)]">
                  {{ typeof item.categoria === 'string' ? item.categoria : (item.categoria?.nombre || 'General') }}
              </span>
            </div>

            <div class="col-span-2 text-right">
              <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                ${{ formatearPrecio(getPrecioMostrar(item)) }}
              </span>
            </div>

            <div class="col-span-1 flex justify-center">
              <div v-if="item.tipo === 'producto'">
                <span v-if="item.tipo_producto === 'kit'" class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-sky-100 dark:bg-sky-900/30 text-indigo-700 dark:text-indigo-400 uppercase">
                  Kit
                </span>
                <template v-else>
                  <span :class="[
                    'text-[10px] px-2 py-0.5 rounded-full font-bold',
                    getStock(item) > 10 ? 'bg-emerald-50 dark:bg-emerald-900/20/20 text-emerald-700 dark:text-emerald-400' :
                    getStock(item) > 0 ? 'bg-brand-50 dark:bg-brand-900/20/20 text-brand-700 dark:text-amber-400' :
                    'bg-rose-50 dark:bg-rose-900/20/20 text-rose-700 dark:text-rose-400'
                  ]">
                    {{ getStock(item) }}
                  </span>
                </template>
              </div>
              <span v-else class="text-xs text-[var(--ui-text-soft)]">∞</span>
            </div>

            <div class="col-span-1 flex justify-center">
              <button
                type="button"
                @mousedown.prevent="agregarItem(item)"
                :disabled="props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0"
                :class="[
                  'w-8 h-8 flex items-center justify-center rounded-xl transition-all duration-200 transform active:scale-95 shadow-sm',
                  props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0
                    ? 'bg-slate-100 dark:bg-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed'
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
      </template>

      <template #empty>
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-[var(--ui-surface-alt)] rounded-full flex items-center justify-center mb-3 border border-[var(--ui-border)]">
            <svg class="w-8 h-8 text-[var(--ui-text-soft)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <p class="text-sm font-bold text-[var(--ui-text)]">No encontramos coincidencias</p>
          <p class="text-xs text-[var(--ui-text-soft)] mt-1">Intenta buscar con otro nombre o código</p>
        </div>
      </template>
    </SearchDropdown>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { resolverPrecio } from '@/Utils/precioHelper';
import { includesSearch } from '@/Utils/searchHelper';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { useClickOutside } from '@/Composables/useClickOutside';
import { useDropdownPosition } from '@/Composables/useDropdownPosition';
import SearchDropdown from '@/Components/CreateComponents/SearchDropdown.vue';

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
  serviciosUsanListasPrecios: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['agregar-producto']);

// Variables reactivas
const busqueda = ref('');
const mostrarLista = ref(false);
const filtroActivo = ref(props.soloProductos ? 'productos' : 'todos');
const productosRecientes = ref([]);
const inputBusqueda = ref(null);
const root = ref(null);
const cargando = ref(false);
const resultadosApi = ref([]);
const ultimaBusquedaApi = ref('');
const { inputWidth, inputPosition, updatePosition } = useDropdownPosition(inputBusqueda);

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
  
  // Combinar con resultados de API, evitando duplicados
  const combinados = [...productosConTipo, ...serviciosConTipo];
  
  resultadosApi.value.forEach(itemApi => {
      const existe = combinados.some(c => c.id === itemApi.id && c.tipo === itemApi.tipo);
      if (!existe) combinados.push(itemApi);
  });
  
  return combinados;
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
  // Filtrar por búsqueda local si ya tenemos resultados
  if (busqueda.value) {
    const termino = busqueda.value.toLowerCase();
    items = items.filter(item =>
      includesSearch(item.nombre, busqueda.value) ||
      includesSearch(item.codigo, busqueda.value) ||
      (item.categoria && (
        typeof item.categoria === 'string'
          ? includesSearch(item.categoria, busqueda.value)
          : includesSearch(item.categoria.nombre, busqueda.value)
      )) ||
      includesSearch(item.descripcion, busqueda.value)
    );
  }
  return items.slice(0, 150); // Limitar a 150 resultados
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
const handleInput = () => {
    mostrarLista.value = true;
    updatePosition();
    buscarEnApi();
};

const buscarEnApi = debounce(async () => {
    const q = busqueda.value.trim();
    if (q.length < 3 || q === ultimaBusquedaApi.value) return;
    
    ultimaBusquedaApi.value = q;
    cargando.value = true;
    
    try {
        const promises = [];
        
        // Buscar productos
        if (filtroActivo.value === 'todos' || filtroActivo.value === 'productos') {
            promises.push(axios.get('/api/productos', { params: { search: q, per_page: 20 } }));
        }
        
        // Buscar servicios
        if (!props.soloProductos && (filtroActivo.value === 'todos' || filtroActivo.value === 'servicios')) {
            promises.push(axios.get('/api/servicios', { params: { search: q, per_page: 20 } }));
        }
        
        const responses = await Promise.all(promises);
        let nuevosResultados = [];
        
        responses.forEach((res, index) => {
            const data = res.data.data || res.data || [];
            const items = Array.isArray(data) ? data : (data.items || data.data || []);
            
            // Determinar si es producto o servicio según el orden de las promesas
            const esProducto = (filtroActivo.value === 'todos' && index === 0) || (filtroActivo.value === 'productos');
            
            items.forEach(item => {
                nuevosResultados.push({
                    ...item,
                    tipo: esProducto ? 'producto' : 'servicio'
                });
            });
        });
        
        resultadosApi.value = nuevosResultados;
    } catch (error) {
        if (error?.response?.status === 419) {
          window.location.reload();
          return;
        }
        console.error('Error buscando en API:', error);
    } finally {
        cargando.value = false;
    }
}, 400);

const filtrarItems = () => {
  mostrarLista.value = true;
  updatePosition();
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

useClickOutside([root], () => {
  mostrarLista.value = false;
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
  return resolverPrecio(item, props.priceListId, { serviciosUsanListasPrecios: props.serviciosUsanListasPrecios });
};
</script>

<style>
/* Estilos adicionales si es necesario */
</style>
