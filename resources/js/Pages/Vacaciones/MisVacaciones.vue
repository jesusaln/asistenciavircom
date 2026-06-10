<template>
  <Head title="Mis Vacaciones" />
  <div class="mis-vacaciones min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Mis Vacaciones</h1>
            <p class="text-slate-500 mt-1">Consulta el historial de tus solicitudes de vacaciones</p>
          </div>
          <Link
            :href="route('vacaciones.create')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors duration-200"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nueva Solicitud
          </Link>
        </div>
      </div>

      <!-- Estadísticas personales -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-brand-100 rounded-xl">
              <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Pendientes</p>
              <p class="text-2xl font-semibold text-slate-900">{{ vacaciones.data.filter(v => v.estado === 'pendiente').length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-emerald-100 rounded-xl">
              <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Aprobadas</p>
              <p class="text-2xl font-semibold text-slate-900">{{ vacaciones.data.filter(v => v.estado === 'aprobada').length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-sky-100 rounded-xl">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Total</p>
              <p class="text-2xl font-semibold text-slate-900">{{ vacaciones.total }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-2">Filtrar por estado</label>
            <select
              v-model="filters.estado"
              @change="applyFilters"
              class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            >
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="aprobada">Aprobada</option>
              <option value="rechazada">Rechazada</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Fechas
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Días
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Motivo
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Solicitado
                </th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="vacacion in vacaciones.data" :key="vacacion.id" class="hover:bg-white transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900">
                    {{ formatDate(vacacion.fecha_inicio) }}
                  </div>
                  <div class="text-sm text-slate-500">
                    hasta {{ formatDate(vacacion.fecha_fin) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900">{{ vacacion.dias_solicitados }} días</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="getEstadoClasses(vacacion.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                    {{ getEstadoLabel(vacacion.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900 max-w-xs truncate" :title="vacacion.motivo">
                    {{ vacacion.motivo || 'Sin motivo especificado' }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900">{{ formatDate(vacacion.created_at) }}</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <Link
                      :href="route('vacaciones.show', vacacion.id)"
                      class="w-10 h-10 bg-sky-50 dark:bg-sky-900/20 text-blue-600 rounded-xl hover:bg-sky-100 transition-colors duration-150 flex items-center justify-center"
                      title="Ver detalles"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </Link>

                    <!-- Solo mostrar acciones para administradores -->
                    <div v-if="$page.props.auth.user && ($page.props.auth.user.is_admin || ($page.props.auth.user.roles && $page.props.auth.user.roles.some(role => ['admin', 'super-admin'].includes(role.name))))">
                      <button
                        v-if="vacacion.estado === 'pendiente'"
                        @click="aprobarVacacion(vacacion)"
                        class="w-10 h-10 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-xl hover:bg-emerald-100 transition-colors duration-150 flex items-center justify-center"
                        title="Aprobar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                      </button>

                      <button
                        v-if="vacacion.estado === 'pendiente'"
                        @click="rechazarVacacion(vacacion)"
                        class="w-10 h-10 bg-rose-50 dark:bg-rose-900/20 text-rose-600 rounded-xl hover:bg-rose-100 transition-colors duration-150 flex items-center justify-center"
                        title="Rechazar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </td>
              </tr>

              <tr v-if="vacaciones.data.length === 0">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-6">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-slate-700 font-medium">No tienes solicitudes de vacaciones</p>
                      <p class="text-sm text-slate-500">Tus solicitudes aparecerán aquí cuando las crees</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="vacaciones.last_page > 1" class="bg-white border-t border-slate-200 px-4 py-3">
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-700">
              Mostrando {{ vacaciones.from }} - {{ vacaciones.to }} de {{ vacaciones.total }} resultados
            </div>

            <div class="flex space-x-1">
              <button
                v-for="page in getPageNumbers()"
                :key="page"
                @click="changePage(page)"
                :class="page === vacaciones.current_page ? 'bg-sky-50 dark:bg-sky-900/20 border-blue-500 text-blue-600' : 'bg-white border-slate-300 text-slate-500 hover:bg-white'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  vacaciones: Object,
  filters: Object,
})

const filters = ref({
  estado: props.filters?.estado || '',
})

const applyFilters = () => {
  router.get(route('vacaciones.mis-vacaciones'), {
    estado: filters.value.estado,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return 'Fecha inválida'
  }
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

const getPageNumbers = () => {
  const currentPage = props.vacaciones.current_page
  const lastPage = props.vacaciones.last_page
  const pages = []

  for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
    pages.push(i)
  }

  return pages
}

const changePage = (page) => {
  router.get(route('vacaciones.mis-vacaciones'), {
    ...filters.value,
    page: page
  }, { preserveState: true, preserveScroll: true })
}
</script>

<style scoped>
.mis-vacaciones {
  min-height: 100vh;
}
</style>
