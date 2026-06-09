<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramientas: { type: Object, required: true },
  estadisticas: { type: Object, default: () => ({}) },
  categorias: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')
const categoria = ref(props.filters?.categoria || '')
const mantenimiento = ref(props.filters?.mantenimiento || '')
const items = computed(() => props.herramientas?.data || [])

// Modal para ver herramienta y alerta
const showModal = ref(false)
const selected = ref(null)
const openModal = (h) => { selected.value = h; showModal.value = true }
const closeModal = () => { showModal.value = false; selected.value = null }

const doFilter = () => {
  router.get('/herramientas', {
    search: search.value,
    estado: estado.value,
    categoria: categoria.value,
    mantenimiento: mantenimiento.value,
  }, { preserveState: true, preserveScroll: true })
}

const clearFilters = () => {
  search.value = ''
  estado.value = ''
  categoria.value = ''
  mantenimiento.value = ''
  doFilter()
}

const getEstadoColor = (estado) => {
  const colors = {
    'disponible': 'bg-green-100 text-green-800',
    'asignada': 'bg-blue-100 text-blue-800',
    'mantenimiento': 'bg-yellow-100 text-yellow-800',
    'baja': 'bg-red-100 text-red-800',
    'perdida': 'bg-red-100 text-red-800',
  }
  return colors[estado] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES')
}

const pageLinks = computed(() => props.herramientas?.links || [])
</script>

<template>
  <Head title="Herramientas" />

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Gestión de Herramientas</h1>
    <div class="flex gap-3">
      <Link class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" href="/herramientas/create">
        Nueva Herramienta
      </Link>
      <Link class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700" href="/herramientas-dashboard">
        Dashboard
      </Link>
    </div>
  </div>

  <!-- Estadísticas rápidas -->
  <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-blue-600">{{ estadisticas.total || 0 }}</div>
      <div class="text-sm text-gray-600">Total</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-green-600">{{ estadisticas.disponibles || 0 }}</div>
      <div class="text-sm text-gray-600">Disponibles</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-blue-600">{{ estadisticas.asignadas || 0 }}</div>
      <div class="text-sm text-gray-600">Asignadas</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.mantenimiento || 0 }}</div>
      <div class="text-sm text-gray-600">En Mant.</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-red-600">{{ estadisticas.baja || 0 }}</div>
      <div class="text-sm text-gray-600">De Baja</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-red-600">{{ estadisticas.perdida || 0 }}</div>
      <div class="text-sm text-gray-600">Perdidas</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-orange-600">{{ estadisticas.requieren_mantenimiento || 0 }}</div>
      <div class="text-sm text-gray-600">Req. Mant.</div>
    </div>
  </div>

  <!-- Filtros avanzados -->
  <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
        <input
          v-model="search"
          @keyup.enter="doFilter"
          type="search"
          placeholder="Nombre, serie o descripción"
          class="w-full border rounded px-3 py-2"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
        <select v-model="estado" @change="doFilter" class="w-full border rounded px-3 py-2">
          <option value="">Todos los estados</option>
          <option value="disponible">Disponible</option>
          <option value="asignada">Asignada</option>
          <option value="mantenimiento">En Mantenimiento</option>
          <option value="baja">De Baja</option>
          <option value="perdida">Perdida</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
        <select v-model="categoria" @change="doFilter" class="w-full border rounded px-3 py-2">
          <option value="">Todas las categorías</option>
          <option value="sin_categoria">Sin categoría</option>
          <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Mantenimiento</label>
        <select v-model="mantenimiento" @change="doFilter" class="w-full border rounded px-3 py-2">
          <option value="">Todos</option>
          <option value="requiere">Requiere mantenimiento</option>
          <option value="proximo">Próximo mantenimiento</option>
          <option value="vencida">Mantenimiento vencido</option>
        </select>
      </div>
      <div class="flex items-end">
        <button @click="clearFilters" class="w-full px-3 py-2 bg-white0 text-white rounded hover:bg-gray-600">
          Limpiar Filtros
        </button>
      </div>
    </div>
  </div>

  <!-- Lista de herramientas -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div v-for="h in items" :key="h.id" class="border rounded-lg p-4 bg-white shadow-sm hover:shadow-md transition-shadow">
      <div class="flex items-start gap-3 mb-3">
        <img v-if="h.foto" :src="`/storage/${h.foto}`" alt="Foto" class="w-16 h-16 object-cover rounded" />
        <div v-else class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-lg">{{ h.nombre }}</h3>
          <p class="text-sm text-gray-600">Serie: {{ h.numero_serie || 'N/A' }}</p>
          <p v-if="h.categoria_herramienta?.nombre" class="text-sm text-blue-600">{{ h.categoria_herramienta.nombre }}</p>
        </div>
      </div>

      <div class="space-y-2 mb-3">
        <span :class="['px-2 py-1 rounded-full text-xs font-medium', getEstadoColor(h.estado)]">
          {{ h.estado }}
        </span>

        <!-- Información de mantenimiento -->
        <div v-if="h.requiere_mantenimiento && h.fecha_ultimo_mantenimiento" class="text-xs text-gray-600">
          <span v-if="h.necesita_mantenimiento" class="text-red-600 font-medium">
            ¡Requiere mantenimiento!
          </span>
          <span v-else class="text-orange-600">
            Último mant.: {{ formatDate(h.fecha_ultimo_mantenimiento) }}
          </span>
        </div>

        <!-- Información del técnico asignado -->
        <div v-if="h.tecnico?.nombre" class="text-xs text-blue-600">
          Asignada a: {{ h.tecnico.nombre }}
        </div>
      </div>

      <div class="flex items-center justify-between text-sm">
        <div class="flex gap-2">
          <Link :href="`/herramientas/${h.id}`" class="text-blue-600 hover:underline">Ver</Link>
          <Link :href="`/herramientas/${h.id}/edit`" class="text-green-600 hover:underline">Editar</Link>
        </div>
        <Link as="button" method="delete" :href="`/herramientas/${h.id}`" class="text-red-600 hover:underline" preserve-scroll>
          Eliminar
        </Link>
      </div>
    </div>
  </div>

  <!-- Paginación -->
  <div class="flex gap-2 mt-6 justify-center">
    <Link v-for="l in pageLinks" :key="l.url + l.label" :href="l.url || '#'" preserve-scroll :class="[
      'px-4 py-2 border rounded-lg',
      l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-white'
    ]" v-html="l.label" />
  </div>

  <!-- Modal Detalle Herramienta / Alerta -->
  <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white w-full max-w-xl rounded shadow-lg">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h2 class="text-lg font-semibold">Detalle de Herramienta</h2>
        <button @click="closeModal" class="text-slate-500 hover:text-slate-700">✕</button>
      </div>
      <div class="p-4 grid gap-4 md:grid-cols-2">
        <div>
          <div class="text-sm text-gray-500">Nombre</div>
          <div class="font-medium">{{ selected?.nombre }}</div>
          <div class="mt-3 text-sm text-gray-500">Número de serie</div>
          <div class="font-medium">{{ selected?.numero_serie || 'N/A' }}</div>
          <div class="mt-3 text-sm text-gray-500">Estado</div>
          <div class="font-medium capitalize">{{ selected?.estado }}</div>
          <div class="mt-3 text-sm text-gray-500">Descripción / Alerta</div>
          <div class="font-medium whitespace-pre-wrap">{{ selected?.descripcion || '—' }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-500 mb-2">Foto de condición</div>
          <img v-if="selected?.foto" :src="`/storage/${selected.foto}`" class="w-full h-auto max-h-64 object-cover rounded" />
          <div v-else class="text-gray-500">Sin imagen</div>
        </div>
      </div>
      <div class="px-4 py-3 border-t flex justify-end">
        <button @click="closeModal" class="px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">Cerrar</button>
      </div>
    </div>
  </div>
</template>




