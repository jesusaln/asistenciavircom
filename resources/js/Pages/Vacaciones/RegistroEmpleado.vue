<template>
  <Head :title="`Registro de Vacaciones - ${empleado.name}`" />
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-6 py-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Registro de Vacaciones</h1>
          <p class="text-gray-600">Empleado: <span class="font-medium">{{ empleado.name }}</span></p>
        </div>
        <div class="flex gap-3">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Año:</label>
            <input
              type="number"
              v-model.number="anioLocal"
              class="w-20 border border-gray-300 rounded-lg px-3 py-1 text-sm"
              @change="cambiarAnio"
            />
          </div>
          <Link :href="route('vacaciones.index')" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Volver</Link>
        </div>
      </div>

      <!-- Resumen -->
      <div v-if="registro" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <p class="text-xs text-gray-600">Año</p>
          <p class="text-xl font-semibold text-gray-900">{{ anio }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <p class="text-xs text-gray-600">Días correspondientes</p>
          <p class="text-xl font-semibold text-gray-900">{{ registro.dias_correspondientes ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <p class="text-xs text-gray-600">Disponibles</p>
          <p class="text-xl font-semibold text-gray-900">{{ registro.dias_disponibles ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <p class="text-xs text-gray-600">Utilizados</p>
          <p class="text-xl font-semibold text-gray-900">{{ registro.dias_utilizados ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <p class="text-xs text-gray-600">Días restantes</p>
          <p class="text-xl font-semibold" :class="getDiasRestantesColor(registro.dias_disponibles - registro.dias_utilizados)">
            {{ (registro.dias_disponibles - registro.dias_utilizados) >= 0 ? (registro.dias_disponibles - registro.dias_utilizados) : 0 }}
          </p>
        </div>
      </div>
      <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          <div>
            <h3 class="text-lg font-medium text-yellow-800">Registro no encontrado</h3>
            <p class="text-yellow-700 mt-1">No se encontró un registro de vacaciones para el año {{ anio }}.</p>
            <p class="text-yellow-600 text-sm mt-2">Contacta al administrador para crear el registro de vacaciones.</p>
          </div>
        </div>
      </div>

      <!-- Vacaciones del empleado -->
      <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Solicitudes de Vacaciones</h2>
        </div>
        <div v-if="vacaciones && vacaciones.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fechas</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Días</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Motivo</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Solicitado</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="vacacion in vacaciones" :key="vacacion.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">
                  <div>{{ formatDate(vacacion.fecha_inicio) }}</div>
                  <div class="text-gray-500 text-xs">hasta {{ formatDate(vacacion.fecha_fin) }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ vacacion.dias_solicitados }} días</td>
                <td class="px-4 py-3 text-sm">
                  <span :class="getEstadoClasses(vacacion.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getEstadoLabel(vacacion.estado) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ vacacion.motivo || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(vacacion.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-8 text-center">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="text-gray-500">No hay solicitudes de vacaciones registradas</p>
        </div>
      </div>

      <!-- Ajustes -->
      <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Ajustes ({{ anio }} y {{ anio - 1 }})</h2>
        </div>
        <div v-if="ajustes && ajustes.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Año</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Días (+/-)</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Motivo</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aplicado por</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="a in ajustes" :key="a.id">
                <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(a.created_at) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ a.anio }}</td>
                <td class="px-4 py-3 text-sm" :class="a.dias >= 0 ? 'text-green-700' : 'text-red-700'">{{ a.dias }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ a.motivo || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ a.creador?.name || 'Sistema' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="p-4 text-sm text-gray-600">No hay ajustes registrados.</p>
      </div>
    </div>
  </div>
  
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  empleado: Object,
  anio: Number,
  registro: Object,
  ajustes: Array,
  vacaciones: {
    type: Array,
    default: () => []
  }
})

const anioLocal = ref(props.anio)

const cambiarAnio = () => {
  router.get(route('registro-vacaciones.por-empleado', props.empleado.id), {
    anio: anioLocal.value
  }, { preserveState: true, preserveScroll: true })
}

const formatDate = (date) => {
  try {
    return new Date(date).toLocaleString('es-MX', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  } catch { return date }
}

const getEstadoClasses = (estado) => {
  const classes = {
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'aprobada': 'bg-green-100 text-green-700',
    'rechazada': 'bg-red-100 text-red-700',
  }
  return classes[estado] || 'bg-gray-100 text-gray-700'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'aprobada': 'Aprobada',
    'rechazada': 'Rechazada',
  }
  return labels[estado] || 'Desconocido'
}

const getDiasRestantesColor = (diasRestantes) => {
  if (diasRestantes > 10) return 'text-green-600'
  if (diasRestantes > 5) return 'text-yellow-600'
  return 'text-red-600'
}
</script>

<style scoped>
@import "tailwindcss" reference;
</style>


