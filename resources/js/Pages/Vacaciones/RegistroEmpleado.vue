<template>
  <Head :title="`Registro de Vacaciones - ${empleado.name}`" />
  <div class="min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Registro de Vacaciones</h1>
          <p class="text-slate-500">Empleado: <span class="font-medium">{{ empleado.name }}</span></p>
        </div>
        <div class="flex gap-3">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-700">Año:</label>
            <input
              type="number"
              v-model.number="anioLocal"
              class="w-20 border border-slate-300 rounded-xl px-3 py-1 text-sm"
              @change="cambiarAnio"
            />
          </div>
          <Link :href="route('vacaciones.index')" class="px-4 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-700">Volver</Link>
        </div>
      </div>

      <!-- Resumen -->
      <div v-if="registro" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
          <p class="text-xs text-slate-500">Año</p>
          <p class="text-xl font-semibold text-slate-900">{{ anio }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
          <p class="text-xs text-slate-500">Días correspondientes</p>
          <p class="text-xl font-semibold text-slate-900">{{ registro.dias_correspondientes ?? 0 }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
          <p class="text-xs text-slate-500">Disponibles</p>
          <p class="text-xl font-semibold text-slate-900">{{ registro.dias_disponibles ?? 0 }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
          <p class="text-xs text-slate-500">Utilizados</p>
          <p class="text-xl font-semibold text-slate-900">{{ registro.dias_utilizados ?? 0 }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
          <p class="text-xs text-slate-500">Días restantes</p>
          <p class="text-xl font-semibold" :class="getDiasRestantesColor(registro.dias_disponibles - registro.dias_utilizados)">
            {{ (registro.dias_disponibles - registro.dias_utilizados) >= 0 ? (registro.dias_disponibles - registro.dias_utilizados) : 0 }}
          </p>
        </div>
      </div>
      <div v-else class="bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800/30 rounded-xl p-6 mb-6">
        <div class="flex items-center">
          <svg class="w-4 h-4 text-brand-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          <div>
            <h3 class="text-lg font-medium text-brand-800 dark:text-amber-200">Registro no encontrado</h3>
            <p class="text-brand-800 dark:text-brand-200 dark:text-brand-200 mt-1">No se encontró un registro de vacaciones para el año {{ anio }}.</p>
            <p class="text-brand-600 text-sm mt-2">Contacta al administrador para crear el registro de vacaciones.</p>
          </div>
        </div>
      </div>

      <!-- Vacaciones del empleado -->
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-200">
          <h2 class="text-lg font-semibold text-slate-900">Solicitudes de Vacaciones</h2>
        </div>
        <div v-if="vacaciones && vacaciones.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Fechas</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Días</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Motivo</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Solicitado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="vacacion in vacaciones" :key="vacacion.id" class="hover:bg-white">
                <td class="px-4 py-3 text-sm text-slate-900">
                  <div>{{ formatDate(vacacion.fecha_inicio) }}</div>
                  <div class="text-slate-500 text-xs">hasta {{ formatDate(vacacion.fecha_fin) }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ vacacion.dias_solicitados }} días</td>
                <td class="px-4 py-3 text-sm">
                  <span :class="getEstadoClasses(vacacion.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                    {{ getEstadoLabel(vacacion.estado) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ vacacion.motivo || '-' }}</td>
                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(vacacion.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-8 text-center">
          <svg class="w-10 h-10 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="text-slate-500">No hay solicitudes de vacaciones registradas</p>
        </div>
      </div>

      <!-- Ajustes -->
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Ajustes ({{ anio }} y {{ anio - 1 }})</h2>
        </div>
        <div v-if="ajustes && ajustes.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Año</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Días (+/-)</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Motivo</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Aplicado por</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="a in ajustes" :key="a.id">
                <td class="px-4 py-3 text-sm text-slate-900">{{ formatDate(a.created_at) }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ a.anio }}</td>
                <td class="px-4 py-3 text-sm" :class="a.dias >= 0 ? 'text-emerald-800 dark:text-emerald-200 dark:text-emerald-200' : 'text-rose-800 dark:text-rose-200 dark:text-rose-200'">{{ a.dias }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ a.motivo || '-' }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ a.creador?.name || 'Sistema' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="p-4 text-sm text-slate-500">No hay ajustes registrados.</p>
      </div>
    </div>
  </div>
  
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
    'pendiente': 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-amber-200',
    'aprobada': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200',
    'rechazada': 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200',
  }
  return classes[estado] || 'bg-slate-100 text-slate-700'
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
  if (diasRestantes > 10) return 'text-emerald-600'
  if (diasRestantes > 5) return 'text-amber-600'
  return 'text-rose-600'
}
</script>

<style scoped>
</style>


