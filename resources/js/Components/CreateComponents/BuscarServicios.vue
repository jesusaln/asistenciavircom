<template>
  <div class="buscar-servicios">
    <!-- Campo de búsqueda -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Buscar Servicios
      </label>
      <div class="relative">
        <input
          ref="inputBusqueda"
          type="text"
          v-model="busqueda"
          @input="filtrarServicios"
          @focus="mostrarLista = true"
          placeholder="Buscar servicios por nombre, categoría o descripción..."
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"
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
              ? 'bg-purple-100 text-purple-800 border-purple-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'
          ]"
        >
          Todos ({{ serviciosFiltrados.length }})
        </button>
      </div>
    </div>

    <!-- Servicios agregados recientemente -->
    <div v-if="serviciosRecientes.length > 0" class="mt-6">
      <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Servicios recientes
      </h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="servicio in serviciosRecientes"
          :key="`reciente-${servicio.id}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
        >
          {{ servicio.nombre }}
          <svg class="w-3 h-3 ml-1 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        Servicios populares
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="servicio in sugerenciasRapidas"
          :key="`sugerencia-${servicio.id}`"
          @click="seleccionarServicio(servicio)"
          class="p-3 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 cursor-pointer transition-all duration-200"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-2 bg-purple-100 text-purple-800">
                  S
                </span>
                <span class="text-sm font-medium text-gray-900">{{ servicio.nombre }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ servicio.categoria || 'Sin categoría' }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-purple-600">
                ${{ formatearPrecio(servicio.precio) }}
              </div>
              <div class="text-xs text-gray-500">
                {{ servicio.duracion || '∞' }} min
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Usar Teleport para renderizar fuera del componente -->
    <Teleport to="#app">
      <div
        v-if="mostrarLista && serviciosFiltrados.length > 0"
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
            <div class="col-span-4">Nombre</div>
            <div class="col-span-2">Categoría</div>
            <div class="col-span-2">Precio</div>
            <div class="col-span-2">Duración</div>
            <div class="col-span-2">Acción</div>
          </div>
        </div>
        <!-- Servicios -->
        <div
          v-for="servicio in serviciosFiltrados"
          :key="servicio.id"
          class="px-4 py-3 hover:bg-purple-50 border-b border-gray-100 last:border-b-0"
        >
          <div class="grid grid-cols-12 gap-2 items-center">
            <!-- Nombre -->
            <div class="col-span-4">
              <div class="font-medium text-gray-900 text-sm">{{ servicio.nombre }}</div>
              <div v-if="servicio.descripcion" class="text-xs text-gray-500 truncate">{{ servicio.descripcion }}</div>
            </div>
            <!-- Categoría -->
            <div class="col-span-2">
              <span class="text-sm text-gray-600">{{ servicio.categoria || 'Sin categoría' }}</span>
            </div>
            <!-- Precio -->
            <div class="col-span-2">
              <span class="text-sm font-semibold text-purple-600">
                ${{ formatearPrecio(servicio.precio) }}
              </span>
            </div>
            <!-- Duración -->
            <div class="col-span-2">
              <span class="text-sm text-gray-600">{{ servicio.duracion || '∞' }} min</span>
            </div>
            <!-- Botón seleccionar -->
            <div class="col-span-2">
              <button
                type="button"
                @click="seleccionarServicio(servicio)"
                class="w-full px-2 py-1 text-xs font-medium rounded-md bg-purple-500 text-white hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 transition-colors duration-200"
              >
                <svg class="w-3 h-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-if="busqueda && serviciosFiltrados.length === 0" class="px-4 py-8 text-center text-gray-500 z-50 bg-white border border-gray-300 rounded-lg shadow-lg" :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm font-medium">No se encontraron servicios</p>
        <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
  servicios: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['servicio-seleccionado']);

// Variables reactivas
const busqueda = ref('');
const mostrarLista = ref(false);
const filtroActivo = ref('todos');
const serviciosRecientes = ref([]);
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

// Filtrar servicios según búsqueda y filtro activo
const serviciosFiltrados = computed(() => {
  let servicios = props.servicios;

  // Aplicar filtro de búsqueda
  if (busqueda.value) {
    const termino = busqueda.value.toLowerCase();
    servicios = servicios.filter(servicio =>
      servicio.nombre.toLowerCase().includes(termino) ||
      (servicio.categoria && servicio.categoria.toLowerCase().includes(termino)) ||
      (servicio.descripcion && servicio.descripcion.toLowerCase().includes(termino))
    );
  }

  return servicios.slice(0, 50); // Limitar a 50 resultados
});

// Sugerencias rápidas (servicios más populares)
const sugerenciasRapidas = computed(() => {
  return props.servicios
    .sort((a, b) => {
      // Ordenar por precio (más económicos primero) o por nombre
      return a.precio - b.precio;
    })
    .slice(0, 6);
});

// Funciones
const filtrarServicios = () => {
  mostrarLista.value = true;
  actualizarPosicionLista();
};

const seleccionarServicio = (servicio) => {
  // Agregar a servicios recientes
  const servicioReciente = { ...servicio };
  const index = serviciosRecientes.value.findIndex(s => s.id === servicio.id);
  if (index === -1) {
    serviciosRecientes.value.unshift(servicioReciente);
    // Mantener solo los últimos 5
    if (serviciosRecientes.value.length > 5) {
      serviciosRecientes.value.pop();
    }
  }

  // Emitir evento al componente padre
  emit('servicio-seleccionado', servicio);

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
  if (!event.target.closest('.buscar-servicios')) {
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

