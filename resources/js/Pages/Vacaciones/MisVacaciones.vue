<template>
  <Head title="Mis Vacaciones" />
  <div class="mis-vacaciones min-h-screen bg-white dark:bg-slate-900">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mis Vacaciones</h1>
            <p class="text-gray-600 dark:text-gray-300 mt-1">Consulta el historial de tus solicitudes de vacaciones</p>
          </div>
          <Link
            :href="route('vacaciones.create')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nueva Solicitud
          </Link>
        </div>
      </div>

      <!-- Estadísticas personales -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Pendientes</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ vacaciones.data.filter(v => v.estado === 'pendiente').length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Aprobadas</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ vacaciones.data.filter(v => v.estado === 'aprobada').length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ vacaciones.total }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por estado</label>
            <select
              v-model="filters.estado"
              @change="applyFilters"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
      <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
            <thead class="bg-white dark:bg-slate-900">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Fechas
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Días
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Motivo
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Solicitado
                </th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
              <tr v-for="vacacion in vacaciones.data" :key="vacacion.id" class="hover:bg-white dark:bg-slate-900 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatDate(vacacion.fecha_inicio) }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    hasta {{ formatDate(vacacion.fecha_fin) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white">{{ vacacion.dias_solicitados }} días</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="getEstadoClasses(vacacion.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getEstadoLabel(vacacion.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white max-w-xs truncate" :title="vacacion.motivo">
                    {{ vacacion.motivo || 'Sin motivo especificado' }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white">{{ formatDate(vacacion.created_at) }}</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <Link
                      :href="route('vacaciones.show', vacacion.id)"
                      class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150 flex items-center justify-center"
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
                        class="w-8 h-8 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors duration-150 flex items-center justify-center"
                        title="Aprobar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                      </button>

                      <button
                        v-if="vacacion.estado === 'pendiente'"
                        @click="rechazarVacacion(vacacion)"
                        class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-150 flex items-center justify-center"
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
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No tienes solicitudes de vacaciones</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Tus solicitudes aparecerán aquí cuando las crees</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="vacaciones.last_page > 1" class="bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 px-4 py-3">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Mostrando {{ vacaciones.from }} - {{ vacaciones.to }} de {{ vacaciones.total }} resultados
            </div>

            <div class="flex space-x-1">
              <button
                v-for="page in getPageNumbers()"
                :key="page"
                @click="changePage(page)"
                :class="page === vacaciones.current_page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white dark:bg-slate-900 border-gray-300 text-gray-500 dark:text-gray-400 hover:bg-white dark:bg-slate-900'"
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
