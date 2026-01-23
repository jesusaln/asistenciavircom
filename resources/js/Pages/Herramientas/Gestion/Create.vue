<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnicos: { type: Array, default: () => [] },
  herramientas: { type: Array, default: () => [] },
})

const form = useForm({ tecnico_id: '', herramientas: [] })
const searchHerramientas = ref('')

const toggleHerramienta = (id) => {
  const i = form.herramientas.indexOf(id)
  if (i === -1) form.herramientas.push(id)
  else form.herramientas.splice(i, 1)
}

const toggleSeleccionarTodas = () => {
  if (form.herramientas.length === herramientasFiltradas.value.length) {
    form.herramientas = []
  } else {
    form.herramientas = herramientasFiltradas.value.map(h => h.id)
  }
}

const herramientasFiltradas = computed(() => {
  if (!searchHerramientas.value) return props.herramientas
  return props.herramientas.filter(h =>
    h.nombre.toLowerCase().includes(searchHerramientas.value.toLowerCase()) ||
    (h.numero_serie && h.numero_serie.toLowerCase().includes(searchHerramientas.value.toLowerCase()))
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

const submit = () => form.post(route('herramientas.gestion.asignar'))
</script>

<template>
  <Head title="Asignar Herramientas" />

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900">Asignar Herramientas</h1>
      <p class="text-gray-600 dark:text-gray-300 mt-1">Selecciona un técnico y asigna las herramientas disponibles</p>
    </div>
    <div class="flex gap-3">
      <Link class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700" :href="route('herramientas.gestion.index')">
        ← Volver a Gestión
      </Link>
      <Link class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :href="route('herramientas.dashboard')">
        Dashboard
      </Link>
    </div>
  </div>

  <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border p-6">
    <!-- Selección de técnico -->
    <div class="mb-8">
      <label class="block text-lg font-medium text-gray-700 mb-3">Seleccionar Técnico</label>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <label v-for="tecnico in props.tecnicos" :key="tecnico.id" class="relative">
          <input
            type="radio"
            :value="tecnico.id"
            v-model="form.tecnico_id"
            name="tecnico_id"
            class="sr-only peer"
            required
          />
          <div class="p-4 border-2 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-white dark:bg-slate-900">
            <div class="font-medium text-gray-900 dark:text-white">{{ tecnico.nombre_completo || tecnico.nombre }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ tecnico.telefono || 'Sin teléfono' }}</div>
          </div>
        </label>
      </div>
      <div v-if="form.errors.tecnico_id" class="text-sm text-red-600 mt-2">{{ form.errors.tecnico_id }}</div>
    </div>

    <!-- Selección de herramientas -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-medium text-gray-700">Herramientas Disponibles</h2>
        <div class="flex items-center gap-3">
          <input
            v-model="searchHerramientas"
            type="search"
            placeholder="Buscar herramientas..."
            class="border rounded px-3 py-2 text-sm"
          />
          <button
            type="button"
            @click="toggleSeleccionarTodas"
            class="px-3 py-2 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
          >
            {{ form.herramientas.length === herramientasFiltradas.length ? 'Deseleccionar Todas' : 'Seleccionar Todas' }}
          </button>
        </div>
      </div>

      <!-- Resumen de selección -->
      <div class="mb-4 p-3 bg-blue-50 rounded-lg">
        <div class="flex items-center justify-between">
          <span class="text-sm text-blue-800">
            {{ form.herramientas.length }} herramienta{{ form.herramientas.length !== 1 ? 's' : '' }} seleccionada{{ form.herramientas.length !== 1 ? 's' : '' }}
          </span>
          <span class="text-sm text-blue-600">
            {{ herramientasFiltradas.length }} disponible{{ herramientasFiltradas.length !== 1 ? 's' : '' }}
          </span>
        </div>
      </div>

      <!-- Grid de herramientas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
        <label v-for="herramienta in herramientasFiltradas" :key="herramienta.id" class="relative">
          <input
            type="checkbox"
            :value="herramienta.id"
            v-model="form.herramientas"
            class="sr-only peer"
          />
          <div class="p-4 border-2 rounded-lg cursor-pointer transition-all peer-checked:border-green-500 peer-checked:bg-green-50 hover:bg-white dark:bg-slate-900">
            <div class="flex items-start gap-3">
              <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-12 h-12 object-cover rounded" />
              <div v-else class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-medium text-gray-900 dark:text-white">{{ herramienta.nombre }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Serie: {{ herramienta.numero_serie || 'N/A' }}</p>
                <span :class="['text-xs px-2 py-1 rounded-full mt-1 inline-block', getEstadoColor(herramienta.estado)]">
                  {{ herramienta.estado }}
                </span>
              </div>
            </div>
          </div>
        </label>
      </div>

      <!-- Sin herramientas disponibles -->
      <div v-if="herramientasFiltradas.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        <p>No hay herramientas disponibles para asignar</p>
      </div>

      <div v-if="form.errors.herramientas" class="text-sm text-red-600 mt-2">{{ form.errors.herramientas }}</div>
    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-between pt-6 border-t">
      <Link :href="route('herramientas.gestion.index')" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
        Cancelar
      </Link>
      <button
        :disabled="form.processing || !form.tecnico_id || form.herramientas.length === 0"
        type="submit"
        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
      >
        {{ form.processing ? 'Asignando...' : `Asignar ${form.herramientas.length} Herramienta${form.herramientas.length !== 1 ? 's' : ''}` }}
      </button>
    </div>
  </form>
</template>


