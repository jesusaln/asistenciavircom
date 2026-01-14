<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramientas: { type: Object, required: true },
})

const selectedHerramientas = ref([])
const costoMantenimiento = ref('')
const descripcionMantenimiento = ref('')
const fechaMantenimiento = ref(new Date().toISOString().split('T')[0])
const proximoMantenimientoDias = ref('')

const items = computed(() => props.herramientas?.data || [])

const toggleSeleccion = (herramientaId) => {
  const index = selectedHerramientas.value.indexOf(herramientaId)
  if (index === -1) {
    selectedHerramientas.value.push(herramientaId)
  } else {
    selectedHerramientas.value.splice(index, 1)
  }
}

const seleccionarTodas = () => {
  if (selectedHerramientas.value.length === items.value.length) {
    selectedHerramientas.value = []
  } else {
    selectedHerramientas.value = items.value.map(h => h.id)
  }
}

const registrarMantenimientoMasivo = () => {
  if (selectedHerramientas.value.length === 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Selecciona al menos una herramienta'
    })
    return
  }

  if (!descripcionMantenimiento.value.trim()) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Ingresa una descripción del mantenimiento'
    })
    return
  }

  // Aquí iría la lógica para registrar mantenimiento masivo
  // Por ahora, procesamos una por una
  selectedHerramientas.value.forEach(herramientaId => {
    const herramienta = items.value.find(h => h.id === herramientaId)
    if (herramienta) {
      router.post(route('herramientas.registrar-mantenimiento', herramientaId), {
        fecha_mantenimiento: fechaMantenimiento.value,
        costo_mantenimiento: costoMantenimiento.value,
        descripcion_mantenimiento: descripcionMantenimiento.value,
        proximo_mantenimiento_dias: proximoMantenimientoDias.value,
      }, {
        preserveScroll: true,
        onSuccess: () => {
          // Remover de la lista después del mantenimiento
          const index = selectedHerramientas.value.indexOf(herramientaId)
          if (index !== -1) {
            selectedHerramientas.value.splice(index, 1)
          }
        }
      })
    }
  })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES')
}

const calcularDiasDesdeMantenimiento = (fecha) => {
  if (!fecha) return 'N/A'
  const diffTime = Math.abs(new Date() - new Date(fecha))
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const pageLinks = computed(() => props.herramientas?.links || [])
</script>

<template>
  <Head title="Mantenimiento de Herramientas" />

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Mantenimiento Preventivo</h1>
    <Link class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :href="route('herramientas.dashboard')">
      Volver al Dashboard
    </Link>
  </div>

  <!-- Formulario de mantenimiento masivo -->
  <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">Registrar Mantenimiento Masivo</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Mantenimiento</label>
        <input
          v-model="fechaMantenimiento"
          type="date"
          class="w-full border rounded px-3 py-2"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Costo Total</label>
        <input
          v-model="costoMantenimiento"
          type="number"
          step="0.01"
          placeholder="0.00"
          class="w-full border rounded px-3 py-2"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Próximo Mantenimiento (días)</label>
        <input
          v-model="proximoMantenimientoDias"
          type="number"
          placeholder="90"
          class="w-full border rounded px-3 py-2"
        />
      </div>
      <div class="flex items-end">
        <button
          @click="registrarMantenimientoMasivo"
          :disabled="selectedHerramientas.length === 0"
          class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-gray-400"
        >
          Registrar Mantenimiento ({{ selectedHerramientas.length }})
        </button>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del Mantenimiento</label>
      <textarea
        v-model="descripcionMantenimiento"
        rows="3"
        placeholder="Describe el mantenimiento realizado..."
        class="w-full border rounded px-3 py-2"
      ></textarea>
    </div>
  </div>

  <!-- Lista de herramientas que requieren mantenimiento -->
  <div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6 border-b">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Herramientas que Requieren Mantenimiento</h2>
        <button
          @click="seleccionarTodas"
          class="px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {{ selectedHerramientas.length === items.length ? 'Deseleccionar Todas' : 'Seleccionar Todas' }}
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <input type="checkbox" :checked="selectedHerramientas.length === items.length && items.length > 0" @change="seleccionarTodas" />
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Herramienta</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Mantenimiento</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Días Transcurridos</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="herramienta in items" :key="herramienta.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <input
                type="checkbox"
                :value="herramienta.id"
                v-model="selectedHerramientas"
              />
            </td>
            <td class="px-6 py-4">
              <div>
                <div class="font-medium text-gray-900">{{ herramienta.nombre }}</div>
                <div class="text-sm text-gray-500">{{ herramienta.numero_serie }}</div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span class="text-sm text-gray-900">{{ herramienta.categoria_herramienta?.nombre || 'Sin categoría' }}</span>
            </td>
            <td class="px-6 py-4">
              <span class="text-sm text-gray-900">{{ formatDate(herramienta.fecha_ultimo_mantenimiento) }}</span>
            </td>
            <td class="px-6 py-4">
              <span class="text-sm font-medium text-red-600">
                {{ calcularDiasDesdeMantenimiento(herramienta.fecha_ultimo_mantenimiento) }} días
              </span>
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                Requiere Mantenimiento
              </span>
            </td>
            <td class="px-6 py-4 text-sm font-medium">
              <Link :href="route('herramientas.show', herramienta.id)" class="text-blue-600 hover:underline mr-3">
                Ver
              </Link>
              <Link :href="route('herramientas.edit', herramienta.id)" class="text-green-600 hover:underline">
                Editar
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <Link v-for="l in pageLinks" :key="l.url + l.label" :href="l.url || '#'" preserve-scroll :class="[
          'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
          l.active ? 'z-10 bg-indigo-50 border-amber-500 text-amber-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
        ]" v-html="l.label" />
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Mostrando <span class="font-medium">{{ items.length }}</span> herramientas que requieren mantenimiento
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <Link v-for="l in pageLinks" :key="l.url + l.label" :href="l.url || '#'" preserve-scroll :class="[
              'relative inline-flex items-center px-2 py-2 rounded-l-md border text-sm font-medium',
              l.active ? 'z-10 bg-indigo-50 border-amber-500 text-amber-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
            ]" v-html="l.label" />
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

