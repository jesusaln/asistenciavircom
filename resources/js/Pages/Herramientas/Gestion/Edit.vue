<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnico: { type: Object, required: true },
  asignadas: { type: Array, default: () => [] },
  disponibles: { type: Array, default: () => [] },
  tecnicos: { type: Array, default: () => [] },
})

const form = useForm({ asignadas: props.asignadas.map(h => h.id) })
const showReasignarForm = ref(false)
const herramientaAReasignar = ref(null)
const nuevoTecnicoId = ref('')
const observacionesReasignacion = ref('')
const searchAsignadas = ref('')
const searchDisponibles = ref('')

const toggle = (id) => {
  const i = form.asignadas.indexOf(id)
  if (i === -1) form.asignadas.push(id)
  else form.asignadas.splice(i, 1)
}

const isChecked = (id) => form.asignadas.includes(id)

const herramientasAsignadasFiltradas = computed(() => {
  if (!searchAsignadas.value) return props.asignadas
  return props.asignadas.filter(h =>
    h.nombre.toLowerCase().includes(searchAsignadas.value.toLowerCase()) ||
    (h.numero_serie && h.numero_serie.toLowerCase().includes(searchAsignadas.value.toLowerCase()))
  )
})

const herramientasDisponiblesFiltradas = computed(() => {
  if (!searchDisponibles.value) return props.disponibles
  return props.disponibles.filter(h =>
    h.nombre.toLowerCase().includes(searchDisponibles.value.toLowerCase()) ||
    (h.numero_serie && h.numero_serie.toLowerCase().includes(searchDisponibles.value.toLowerCase()))
  )
})

const getEstadoColor = (estado) => {
  const colors = {
    'disponible': 'bg-green-100 text-green-800',
    'asignada': 'bg-blue-100 text-blue-800',
    'mantenimiento': 'bg-yellow-100 text-yellow-800',
    'baja': 'bg-red-100 text-red-800',
    'perdida': 'bg-red-100 text-red-800',
  }
  return colors[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100'
}

const submit = () => form.put(route('herramientas.gestion.update', props.tecnico.id))

const abrirModalReasignacion = (herramienta) => {
  herramientaAReasignar.value = herramienta
  showReasignarForm.value = true
}

const cerrarModalReasignacion = () => {
  showReasignarForm.value = false
  herramientaAReasignar.value = null
  nuevoTecnicoId.value = ''
  observacionesReasignacion.value = ''
}

const reasignarHerramienta = () => {
  if (!nuevoTecnicoId.value) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Selecciona un técnico'
    })
    return
  }

  router.post('/herramientas/reasignar', {
    herramienta_id: herramientaAReasignar.value.id,
    tecnico_anterior_id: props.tecnico.id,
    tecnico_nuevo_id: nuevoTecnicoId.value,
    observaciones: observacionesReasignacion.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cerrarModalReasignacion()
      // Recargar la página para actualizar los datos
      window.location.reload()
    }
  })
}
</script>

<template>
  <Head :title="`Gestión de Herramientas - ${props.tecnico.nombre}`" />

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900">Gestión de Herramientas</h1>
      <p class="text-gray-600 dark:text-gray-300 mt-1">Administrar herramientas de {{ props.tecnico.nombre_completo || props.tecnico.nombre }}</p>
    </div>
    <div class="flex gap-3">
      <Link class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700" :href="route('herramientas.gestion.index')">
        ← Volver a Gestión
      </Link>
      <Link class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :href="route('herramientas.dashboard')">
        Dashboard
      </Link>
      <Link
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        :href="`/herramientas/gestion/${tecnico.id}/exportar`"
      >
        📋 Ver Reporte
      </Link>
      <Link
        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
        :href="`/herramientas/gestion/${tecnico.id}/descargar`"
      >
        📥 Exportar CSV
      </Link>
    </div>
  </div>

  <!-- Información del técnico -->
  <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border p-6 mb-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ props.tecnico.nombre_completo || props.tecnico.nombre }}</h2>
        <p class="text-gray-600 dark:text-gray-300">{{ props.tecnico.email || 'Sin email' }} • {{ props.tecnico.telefono || 'Sin teléfono' }}</p>
      </div>
      <div class="text-right">
        <div class="text-sm text-gray-500 dark:text-gray-400">Herramientas asignadas</div>
        <div class="text-3xl font-bold text-blue-600">{{ form.asignadas.length }}</div>
      </div>
    </div>
  </div>

  <form @submit.prevent="submit" class="grid lg:grid-cols-2 gap-6">
    <!-- Herramientas asignadas -->
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-700">Herramientas Asignadas</h3>
          <div class="flex items-center gap-3">
            <input
              v-model="searchAsignadas"
              type="search"
              placeholder="Buscar..."
              class="border rounded px-3 py-2 text-sm"
            />
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ herramientasAsignadasFiltradas.length }} de {{ props.asignadas.length }}</span>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div v-if="herramientasAsignadasFiltradas.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <p>No hay herramientas asignadas que coincidan con la búsqueda</p>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto">
          <label v-for="herramienta in herramientasAsignadasFiltradas" :key="`a-${herramienta.id}`" class="flex items-center gap-3 p-3 border rounded-lg hover:bg-white dark:bg-slate-900">
            <input
              type="checkbox"
              :value="herramienta.id"
              v-model="form.asignadas"
              class="w-4 h-4 text-blue-600 rounded"
            />
            <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded" />
            <div v-else class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h4 class="font-medium text-gray-900 dark:text-white">{{ herramienta.nombre }}</h4>
              <p class="text-sm text-gray-600 dark:text-gray-300">Serie: {{ herramienta.numero_serie || 'N/A' }}</p>
              <span :class="['text-xs px-2 py-1 rounded-full', getEstadoColor(herramienta.estado)]">
                {{ herramienta.estado }}
              </span>
            </div>
            <button
              @click="abrirModalReasignacion(herramienta)"
              class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Reasignar
            </button>
          </label>
        </div>
      </div>
    </div>

    <!-- Herramientas disponibles -->
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-700">Herramientas Disponibles</h3>
          <div class="flex items-center gap-3">
            <input
              v-model="searchDisponibles"
              type="search"
              placeholder="Buscar..."
              class="border rounded px-3 py-2 text-sm"
            />
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ herramientasDisponiblesFiltradas.length }} disponible{{ herramientasDisponiblesFiltradas.length !== 1 ? 's' : '' }}</span>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div v-if="herramientasDisponiblesFiltradas.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <p>No hay herramientas disponibles que coincidan con la búsqueda</p>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto">
          <label v-for="herramienta in herramientasDisponiblesFiltradas" :key="`d-${herramienta.id}`" class="flex items-center gap-3 p-3 border rounded-lg hover:bg-white dark:bg-slate-900">
            <input
              type="checkbox"
              :value="herramienta.id"
              v-model="form.asignadas"
              class="w-4 h-4 text-green-600 rounded"
            />
            <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded" />
            <div v-else class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h4 class="font-medium text-gray-900 dark:text-white">{{ herramienta.nombre }}</h4>
              <p class="text-sm text-gray-600 dark:text-gray-300">Serie: {{ herramienta.numero_serie || 'N/A' }}</p>
              <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                Disponible
              </span>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Acciones -->
    <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg shadow-sm border p-6">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-300">
          {{ form.asignadas.length }} herramienta{{ form.asignadas.length !== 1 ? 's' : '' }} seleccionada{{ form.asignadas.length !== 1 ? 's' : '' }} para asignar
        </div>
        <div class="flex gap-3">
          <Link :href="route('herramientas.gestion.index')" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Cancelar
          </Link>
          <button
            :disabled="form.processing"
            type="submit"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
        </div>
      </div>
    </div>
  </form>

  <!-- Modal de Reasignación -->
  <div v-if="showReasignarForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-lg shadow-lg">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">Reasignar Herramienta</h2>
        <button @click="cerrarModalReasignacion" class="text-gray-500 dark:text-gray-400 hover:text-gray-700">✕</button>
      </div>
      <div class="p-6">
        <div v-if="herramientaAReasignar" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Herramienta</label>
            <p class="text-lg font-medium">{{ herramientaAReasignar.nombre }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-300">Serie: {{ herramientaAReasignar.numero_serie || 'N/A' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Técnico Actual</label>
            <p class="text-gray-900 dark:text-white">{{ props.tecnico.nombre_completo || props.tecnico.nombre }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nuevo Técnico</label>
            <select v-model="nuevoTecnicoId" class="w-full border rounded px-3 py-2" required>
              <option value="">Seleccionar técnico</option>
              <option v-for="tecnico in props.tecnicos" :key="tecnico.id" :value="tecnico.id">
                {{ tecnico.nombre_completo || tecnico.nombre }} {{ tecnico.id === props.tecnico.id ? '(Actual)' : '' }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
            <textarea
              v-model="observacionesReasignacion"
              rows="3"
              placeholder="Opcional: agregar observaciones sobre la reasignación..."
              class="w-full border rounded px-3 py-2"
            ></textarea>
          </div>
          <div class="flex gap-3 pt-4">
            <button @click="cerrarModalReasignacion" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
              Cancelar
            </button>
            <button @click="reasignarHerramienta" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              Reasignar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


