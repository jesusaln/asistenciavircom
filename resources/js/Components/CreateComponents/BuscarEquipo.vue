<template>
  <div class="buscar-equipo">
    <!-- Campo de búsqueda -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Buscar Equipos Disponibles
      </label>
      <div class="relative">
        <input
          ref="inputBusqueda"
          type="text"
          v-model="busqueda"
          @input="filtrarItems"
          @focus="mostrarLista = true"
          placeholder="Buscar por nombre, código, marca, modelo..."
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
          Todos ({{ itemsFiltrados.length }})
        </button>
        <button
          type="button"
          @click="filtroActivo = 'disponibles'"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors duration-200',
            filtroActivo === 'disponibles'
              ? 'bg-blue-100 text-blue-800 border-blue-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'
          ]"
        >
          Disponibles ({{ disponiblesCount }})
        </button>
      </div>
    </div>

    <!-- Equipos agregados recientemente -->
    <div v-if="equiposRecientes.length > 0" class="mt-6">
      <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Agregados recientemente
      </h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="equipo in equiposRecientes"
          :key="`reciente-${equipo.id}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
        >
          {{ equipo.nombre }}
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
          v-for="equipo in sugerenciasRapidas"
          :key="`sugerencia-${equipo.id}`"
          @click="agregarEquipo(equipo)"
          class="p-3 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 cursor-pointer transition-all duration-200"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-2 bg-blue-100 text-blue-800">
                  E
                </span>
                <span class="text-sm font-medium text-gray-900">{{ equipo.nombre }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ equipo.marca }} {{ equipo.modelo }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-green-600">
                ${{ formatearPrecio(equipo.precio_renta_mensual) }}/mes
              </div>
              <div class="text-xs text-gray-500">
                Código: {{ equipo.codigo }}
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
            <div class="col-span-2">Marca/Modelo</div>
            <div class="col-span-2">Precio Mensual</div>
            <div class="col-span-1">Estado</div>
            <div class="col-span-1">Acción</div>
          </div>
        </div>
        <!-- Items -->
        <div
          v-for="equipo in itemsFiltrados"
          :key="equipo.id"
          class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
        >
          <div class="grid grid-cols-12 gap-2 items-center">
            <!-- Tipo -->
            <div class="col-span-1">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                E
              </span>
            </div>
            <!-- Nombre -->
            <div class="col-span-3">
              <div class="font-medium text-gray-900 text-sm">{{ equipo.nombre }}</div>
              <div v-if="equipo.descripcion" class="text-xs text-gray-500 truncate">{{ equipo.descripcion }}</div>
            </div>
            <!-- Código -->
            <div class="col-span-2">
              <span class="text-sm text-gray-600 font-mono">{{ equipo.codigo || 'N/A' }}</span>
            </div>
            <!-- Marca/Modelo -->
            <div class="col-span-2">
              <span class="text-sm text-gray-600">{{ equipo.marca }} {{ equipo.modelo }}</span>
            </div>
            <!-- Precio Mensual -->
            <div class="col-span-2">
              <span class="text-sm font-semibold text-green-600">
                ${{ formatearPrecio(equipo.precio_renta_mensual) }}
              </span>
            </div>
            <!-- Estado -->
            <div class="col-span-1">
              <span :class="[
                'text-xs px-2 py-1 rounded-full font-medium',
                equipo.estado === 'disponible' ? 'bg-green-100 text-green-800' :
                equipo.estado === 'rentado' ? 'bg-red-100 text-red-800' :
                'bg-yellow-100 text-yellow-800'
              ]">
                {{ equipo.estado === 'disponible' ? 'Disp.' : equipo.estado === 'rentado' ? 'Rent.' : 'Maint.' }}
              </span>
            </div>
            <!-- Botón agregar -->
            <div class="col-span-1">
              <button
                type="button"
                @click="agregarEquipo(equipo)"
                :disabled="equipo.estado !== 'disponible'"
                :class="[
                  'w-full px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200',
                  equipo.estado !== 'disponible'
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

const props = defineProps({
  equipos: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['agregar-producto']);

// Variables reactivas
const busqueda = ref('');
const mostrarLista = ref(false);
const filtroActivo = ref('todos');
const equiposRecientes = ref([]);
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

// Filtrar items según búsqueda y filtro activo
const itemsFiltrados = computed(() => {
  let items = props.equipos || [];
  // Filtrar por estado
  if (filtroActivo.value === 'disponibles') {
    items = items.filter(equipo => equipo.estado === 'disponible');
  }
  // Filtrar por búsqueda
  if (busqueda.value) {
    const termino = busqueda.value.toLowerCase();
    items = items.filter(equipo =>
      equipo.nombre.toLowerCase().includes(termino) ||
      (equipo.codigo && equipo.codigo.toLowerCase().includes(termino)) ||
      (equipo.marca && equipo.marca.toLowerCase().includes(termino)) ||
      (equipo.modelo && equipo.modelo.toLowerCase().includes(termino)) ||
      (equipo.descripcion && equipo.descripcion.toLowerCase().includes(termino))
    );
  }
  return items.slice(0, 50); // Limitar a 50 resultados
});

// Contadores para los filtros
const disponiblesCount = computed(() => {
  return (props.equipos || []).filter(equipo => equipo.estado === 'disponible').length;
});

// Sugerencias rápidas (equipos disponibles con mejor precio)
const sugerenciasRapidas = computed(() => {
  return (props.equipos || [])
    .filter(equipo => equipo.estado === 'disponible')
    .sort((a, b) => {
      // Ordenar por precio mensual (menor primero)
      return (a.precio_renta_mensual || 0) - (b.precio_renta_mensual || 0);
    })
    .slice(0, 6);
});

// Funciones
const filtrarItems = () => {
  mostrarLista.value = true;
  actualizarPosicionLista();
};

const agregarEquipo = (equipo) => {
  // Verificar que esté disponible
  if (equipo.estado !== 'disponible') {
    return;
  }
  // Agregar a equipos recientes
  const equipoReciente = { ...equipo };
  const index = equiposRecientes.value.findIndex(
    e => e.id === equipo.id
  );
  if (index === -1) {
    equiposRecientes.value.unshift(equipoReciente);
    // Mantener solo los últimos 5
    if (equiposRecientes.value.length > 5) {
      equiposRecientes.value.pop();
    }
  }
  // Emitir evento al componente padre
  emit('agregar-producto', { ...equipo, tipo: 'equipo' });
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
  if (!event.target.closest('.buscar-equipo')) {
    mostrarLista.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', cerrarLista);
});

onUnmounted(() => {
  document.removeEventListener('click', cerrarLista);
});
</script>

