<template>
  <div class="buscar-producto">
    <!-- Campo de búsqueda -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ label }}
      </label>
      <div class="relative">
        <input
          ref="inputBusqueda"
          type="text"
          v-model="busqueda"
          @input="filtrarItems"
          @focus="mostrarLista = true"
          :placeholder="placeholder"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            'px-3 py-1 text-xs font-medium rounded-full transition-colors duration-200',
            filtroActivo === 'todos'
              ? 'bg-green-100 text-green-800 border-green-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'
          ]"
        >
          {{ textoTodos }} ({{ itemsFiltrados.length }})
        </button>
        <button
          type="button"
          @click="filtroActivo = 'productos'"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors duration-200',
            filtroActivo === 'productos'
              ? 'bg-blue-100 text-blue-800 border-blue-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'
          ]"
        >
          {{ textoProductos }} ({{ productosCount }})
        </button>
        <button
          v-if="!soloProductos"
          type="button"
          @click="filtroActivo = 'servicios'"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors duration-200',
            filtroActivo === 'servicios'
              ? 'bg-purple-100 text-purple-800 border-purple-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'
          ]"
        >
          {{ textoServicios }} ({{ serviciosCount }})
        </button>
      </div>
    </div>

    <!-- Productos agregados recientemente -->
    <div v-if="productosRecientes.length > 0" class="mt-6">
      <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Agregados recientemente
      </h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="item in productosRecientes"
          :key="`reciente-${item.tipo}-${item.id}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
        >
          {{ item.nombre }}
          <svg class="w-3 h-3 ml-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </span>
      </div>
    </div>

    <!-- Sugerencias rápidas -->
    <div v-if="!busqueda && sugerenciasRapidas.length > 0" class="mt-6">
      <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Sugerencias rápidas
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="item in sugerenciasRapidas"
          :key="`sugerencia-${item.tipo}-${item.id}`"
          @click="agregarItem(item)"
          class="p-3 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 cursor-pointer transition-all duration-200"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center">
                <span :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-2',
                  item.tipo === 'producto' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                ]">
                  {{ item.tipo === 'producto' ? 'P' : 'S' }}
                </span>
                <span class="text-sm font-medium text-gray-900">{{ item.nombre }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ typeof item.categoria === 'string' ? item.categoria : (item.categoria?.nombre || 'Sin categoría') }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-green-600">
                ${{ formatearPrecio(getPrecioMostrar(item)) }}
              </div>
              <div v-if="item.tipo === 'producto'" class="text-xs text-gray-500">
                Stock: {{ item.stock_total || item.stock || 0 }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Usar Teleport para renderizar fuera del componente -->
    <Teleport to="#app">
      <div
        v-if="mostrarLista && itemsFiltrados.length > 0"
        class="z-50 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-80 overflow-y-auto"
        :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }"
      >
        <!-- Encabezados -->
        <div class="sticky top-0 bg-gray-50 border-b border-gray-200 px-4 py-2">
          <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-600">
            <div class="col-span-1">Tipo</div>
            <div class="col-span-3">Nombre</div>
            <div class="col-span-2">Código</div>
            <div class="col-span-2">Categoría</div>
            <div class="col-span-2">Precio</div>
            <div class="col-span-1">Stock</div>
            <div class="col-span-1">Acción</div>
          </div>
        </div>
        <!-- Items -->
        <div
          v-for="item in itemsFiltrados"
          :key="`${item.tipo}-${item.id}`"
          class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
        >
          <div class="grid grid-cols-12 gap-2 items-center">
            <!-- Tipo -->
            <div class="col-span-1">
              <span :class="[
                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                item.tipo === 'producto'
                  ? 'bg-blue-100 text-blue-800'
                  : 'bg-purple-100 text-purple-800'
              ]">
                <svg v-if="item.tipo === 'producto'" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"/>
                </svg>
                <svg v-else class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ item.tipo === 'producto' ? 'P' : 'S' }}
              </span>
            </div>
            <!-- Nombre -->
            <div class="col-span-3">
              <div class="font-medium text-gray-900 text-sm">{{ item.nombre }}</div>
              <div v-if="item.descripcion" class="text-xs text-gray-500 truncate">{{ item.descripcion }}</div>
            </div>
            <!-- Código -->
            <div class="col-span-2">
              <span class="text-sm text-gray-600 font-mono">{{ item.codigo || 'N/A' }}</span>
            </div>
            <!-- Categoría -->
            <div class="col-span-2">
              <span class="text-sm text-gray-600">{{ typeof item.categoria === 'string' ? item.categoria : (item.categoria?.nombre || 'Sin categoría') }}</span>
            </div>
            <!-- Precio -->
            <div class="col-span-2">
              <span class="text-sm font-semibold text-green-600">
                ${{ formatearPrecio(getPrecioMostrar(item)) }}
              </span>
            </div>
            <!-- Stock -->
            <div class="col-span-1">
              <div v-if="item.tipo === 'producto'">
                <span v-if="item.tipo_producto === 'kit'" class="text-xs px-2 py-1 rounded-full font-medium bg-blue-100 text-blue-800">
                  Kit
                </span>
                <template v-else>
                  <span :class="[
                    'text-xs px-2 py-1 rounded-full font-medium',
                    getStock(item) > 10 ? 'bg-green-100 text-green-800' :
                    getStock(item) > 0 ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                  ]">
                    {{ getStock(item) }}
                  </span>
                  <div v-if="getStock(item) <= 0 && getAvailabilityInfo(item)" class="text-[10px] text-blue-600 mt-1 leading-tight">
                    {{ getAvailabilityInfo(item) }}
                  </div>
                </template>
              </div>
              <span v-else class="text-xs text-gray-400">∞</span>
            </div>
            <!-- Botón agregar -->
            <div class="col-span-1">
              <button
                type="button"
                @click="agregarItem(item)"
                :disabled="props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0"
                :class="[
                  'w-full px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200',
                  props.validarStock && item.tipo === 'producto' && item.tipo_producto !== 'kit' && getStock(item) <= 0
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : 'bg-green-500 text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1'
                ]"
              >
                <svg class="w-3 h-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-if="busqueda && itemsFiltrados.length === 0" class="px-4 py-8 text-center text-gray-500 z-50 bg-white border border-gray-300 rounded-lg shadow-lg" :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm font-medium">No se encontraron resultados</p>
        <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
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
  return todosLosItems.value.filter(item => item.tipo === 'servicio').length;
});

// Sugerencias rápidas (productos más vendidos o servicios populares)
const sugerenciasRapidas = computed(() => {
  return todosLosItems.value
    .filter(item => item.tipo === 'producto' ? (item.stock_total || item.stock) > 0 : true)
    .sort((a, b) => {
      // Ordenar por stock para productos y por precio para servicios
      if (a.tipo === 'producto' && b.tipo === 'producto') {
        return (b.stock_total || b.stock) - (a.stock_total || a.stock);
      }
      return 0;
    })
    .slice(0, 6);
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
  //console.log('Productos:', props.productos);
  //console.log('Servicios:', props.servicios);
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

