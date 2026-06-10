<template>
  <div class="buscar-equipo">
    <!-- Campo de búsqueda -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-slate-700 mb-2">
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
          class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border-slate-300'
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
              ? 'bg-sky-100 text-sky-800 border-blue-300'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border-slate-300'
          ]"
        >
          Disponibles ({{ disponiblesCount }})
        </button>
      </div>
    </div>

    <!-- Equipos agregados recientemente -->
    <div v-if="equiposRecientes.length > 0" class="mt-6">
      <h3 class="text-sm font-medium text-slate-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Agregados recientemente
      </h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="equipo in equiposRecientes"
          :key="`reciente-${equipo.id}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"
        >
          {{ equipo.nombre }}
          <svg class="w-3 h-3 ml-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </span>
      </div>
    </div>

    <!-- Sugerencias rápidas -->
    <div v-if="!busqueda && sugerenciasRapidas.length > 0" class="mt-6">
      <h3 class="text-sm font-medium text-slate-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Sugerencias rápidas
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="equipo in sugerenciasRapidas"
          :key="`sugerencia-${equipo.id}`"
          @click="agregarEquipo(equipo)"
          class="p-3 border border-slate-200 rounded-xl hover:border-emerald-300 hover:bg-emerald-50 cursor-pointer transition-all duration-200"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-2 bg-sky-100 text-sky-800">
                  E
                </span>
                <span class="text-sm font-medium text-slate-900">{{ equipo.nombre }}</span>
              </div>
              <div class="text-xs text-slate-500 mt-1">{{ equipo.marca }} {{ equipo.modelo }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-emerald-600">
                ${{ formatearPrecio(equipo.precio_renta_mensual) }}/mes
              </div>
              <div class="text-xs text-slate-500">
                Código: {{ equipo.codigo }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <SearchDropdown
      :show="mostrarLista"
      :items="itemsFiltrados"
      :width="inputWidth"
      :position="inputPosition"
      max-height="20rem"
      :empty="!!busqueda"
      empty-title="No se encontraron resultados"
      empty-subtitle="Intenta con otros términos de búsqueda"
      item-key="id"
    >
      <template #header>
        <div class="grid grid-cols-12 gap-2 text-xs font-medium text-[var(--ui-text-muted)]">
          <div class="col-span-1">Tipo</div>
          <div class="col-span-3">Nombre</div>
          <div class="col-span-2">Código</div>
          <div class="col-span-2">Marca/Modelo</div>
          <div class="col-span-2">Precio Mensual</div>
          <div class="col-span-1">Estado</div>
          <div class="col-span-1">Acción</div>
        </div>
      </template>

      <template #item="{ item }">
        <div class="px-4 py-3 hover:bg-black/5 dark:hover:bg-white/5 border-b border-[var(--ui-border)] last:border-b-0">
          <div class="grid grid-cols-12 gap-2 items-center">
            <div class="col-span-1">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                E
              </span>
            </div>
            <div class="col-span-3">
              <div class="font-medium text-sm">{{ item.nombre }}</div>
              <div v-if="item.descripcion" class="text-xs text-[var(--ui-text-soft)] truncate">{{ item.descripcion }}</div>
            </div>
            <div class="col-span-2">
              <span class="text-sm text-[var(--ui-text-muted)] font-mono">{{ item.codigo || 'N/A' }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-sm text-[var(--ui-text-muted)]">{{ item.marca }} {{ item.modelo }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-sm font-semibold text-emerald-600">
                ${{ formatearPrecio(item.precio_renta_mensual) }}
              </span>
            </div>
            <div class="col-span-1">
              <span :class="[
                'text-xs px-2 py-1 rounded-full font-medium',
                item.estado === 'disponible' ? 'bg-emerald-100 text-emerald-800' :
                item.estado === 'rentado' ? 'bg-rose-100 text-rose-800' :
                'bg-yellow-100 text-yellow-800'
              ]">
                {{ item.estado === 'disponible' ? 'Disp.' : item.estado === 'rentado' ? 'Rent.' : 'Maint.' }}
              </span>
            </div>
            <div class="col-span-1">
              <button
                type="button"
                @click="agregarEquipo(item)"
                :disabled="item.estado !== 'disponible'"
                :class="[
                  'w-full px-2 py-1 text-xs font-medium rounded-xl transition-colors duration-200',
                  item.estado !== 'disponible'
                    ? 'bg-slate-100/80 dark:bg-slate-800 text-slate-400 cursor-not-allowed'
                    : 'bg-emerald-500 text-white hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1'
                ]"
              >
                <svg class="w-3 h-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </template>
    </SearchDropdown>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import SearchDropdown from '@/Components/CreateComponents/SearchDropdown.vue';

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
