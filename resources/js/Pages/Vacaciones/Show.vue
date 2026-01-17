<template>
  <Head title="Detalles de Vacaciones" />
  <div class="vacaciones-show min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Detalles de Vacaciones</h1>
            <p class="text-gray-600 mt-1">Información completa de la solicitud de vacaciones</p>
          </div>
          <Link
            :href="route('vacaciones.index')"
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver a Gestión
          </Link>
        </div>
      </div>

      <!-- Información de la vacación -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Información del empleado -->
          <div class="space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Información del Empleado
              </h2>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Nombre:</span>
<span class="font-medium">{{ vacacion.empleado ? (vacacion.empleado.name || 'N/A') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Puesto:</span>
                  <span class="font-medium">{{ vacacion.empleado?.puesto || 'No especificado' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Departamento:</span>
                  <span class="font-medium">{{ vacacion.empleado?.departamento || 'No especificado' }}</span>
                </div>
              </div>
            </div>

            <!-- Información de la vacación -->
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Información de Vacaciones
              </h2>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Fecha de Inicio:</span>
                  <span class="font-medium">{{ vacacion.fecha_inicio ? formatDate(vacacion.fecha_inicio) : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Fecha de Fin:</span>
                  <span class="font-medium">{{ vacacion.fecha_fin ? formatDate(vacacion.fecha_fin) : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Días Solicitados:</span>
                  <span class="font-medium">{{ (vacacion.dias_solicitados > 0) ? vacacion.dias_solicitados + ' días' : '0 días' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Estado:</span>
                  <span :class="getEstadoClasses(vacacion.estado || 'pendiente')" class="font-medium">
                    {{ getEstadoLabel(vacacion.estado || 'pendiente') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Información adicional -->
          <div class="space-y-6">
            <!-- Motivo -->
            <div>
              <h3 class="text-md font-semibold text-gray-900 mb-2">Motivo</h3>
              <div class="bg-white rounded-lg p-4">
                <p class="text-gray-700">{{ vacacion.motivo || 'No se especificó motivo' }}</p>
              </div>
            </div>

            <!-- Observaciones (si las hay) -->
            <div v-if="vacacion.observaciones">
              <h3 class="text-md font-semibold text-gray-900 mb-2">Observaciones</h3>
              <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-blue-800">{{ vacacion.observaciones }}</p>
              </div>
            </div>

            <!-- Información del aprobador -->
            <div v-if="vacacion.aprobador && vacacion.aprobador.name">
              <h3 class="text-md font-semibold text-gray-900 mb-2">Información de Aprobación</h3>
              <div class="bg-green-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Aprobado por:</span>
                  <span class="font-medium">{{ vacacion.aprobador.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Fecha de aprobación:</span>
                  <span class="font-medium">{{ vacacion.fecha_aprobacion ? formatDate(vacacion.fecha_aprobacion) : 'N/A' }}</span>
                </div>
              </div>
            </div>

            <!-- Información del registro de vacaciones -->
            <div v-if="props.registroVacaciones">
              <h3 class="text-md font-semibold text-gray-900 mb-2">Registro de Vacaciones {{ props.registroVacaciones.anio }}</h3>
              <div class="bg-purple-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Días correspondientes:</span>
                  <span class="font-medium">{{ props.registroVacaciones.dias_correspondientes }} días</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Días disponibles:</span>
                  <span class="font-medium">{{ props.registroVacaciones.dias_disponibles }} días</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Días utilizados:</span>
                  <span class="font-medium">{{ props.registroVacaciones.dias_utilizados }} días</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Días pendientes:</span>
                  <span class="font-medium">{{ props.registroVacaciones.dias_pendientes }} días</span>
                </div>
              </div>
            </div>

            <!-- Fechas de creación y actualización -->
            <div>
              <h3 class="text-md font-semibold text-gray-900 mb-2">Fechas del Sistema</h3>
              <div class="bg-white rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Fecha de creación:</span>
                  <span class="font-medium">{{ vacacion.created_at ? formatDate(vacacion.created_at) : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Última actualización:</span>
                  <span class="font-medium">{{ vacacion.updated_at ? formatDate(vacacion.updated_at) : 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Historial de ajustes de vacaciones (año de la solicitud) -->
        <div class="mt-8">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3m6 0c0-1.657-1.343-3-3-3m0 0V5m0 6h3m-3 0H9m9 4a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Ajustes de vacaciones (año)
          </h2>
          <div v-if="(ajustesVacaciones && ajustesVacaciones.length)" class="overflow-x-auto bg-white border border-gray-200 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Días (+/-)</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Motivo</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aplicado por</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="a in ajustesVacaciones" :key="a.id">
                  <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(a.created_at) }}</td>
                  <td class="px-4 py-3 text-sm" :class="a.dias >= 0 ? 'text-green-700' : 'text-red-700'">{{ a.dias }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ a.motivo || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ a.creador?.name || 'Sistema' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-sm text-gray-600">No hay ajustes registrados para este año.</p>
        </div>

        <!-- Acciones (solo para administradores y vacaciones pendientes) -->
        <div v-if="vacacion.estado === 'pendiente' && $page.props.auth.user && ($page.props.auth.user.is_admin || ($page.props.auth.user.roles && $page.props.auth.user.roles.some(role => ['admin', 'super-admin'].includes(role.name))))" class="mt-8 pt-6 border-t border-gray-200">
          <div class="flex justify-end gap-3">
            <button
              @click="rechazarVacacion"
              class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Rechazar
            </button>
            <button
              @click="aprobarVacacion"
              class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Aprobar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  vacacion: Object,
  registroVacaciones: Object,
  ajustesVacaciones: {
    type: Array,
    default: () => []
  }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const aprobarVacacion = () => {
  if (confirm('¿Estás seguro de que deseas aprobar esta solicitud de vacaciones?')) {
    router.post(route('vacaciones.aprobar', props.vacacion.id), {
      observaciones: ''
    }, {
      onSuccess: () => {
        notyf.success('Vacaciones aprobadas exitosamente')
      },
      onError: () => {
        notyf.error('Error al aprobar las vacaciones')
      }
    })
  }
}

const rechazarVacacion = () => {
  const observaciones = prompt('Ingresa el motivo del rechazo (opcional):')
  if (confirm('¿Estás seguro de que deseas rechazar esta solicitud de vacaciones?')) {
    router.post(route('vacaciones.rechazar', props.vacacion.id), {
      observaciones: observaciones || ''
    }, {
      onSuccess: () => {
        notyf.success('Vacaciones rechazadas')
      },
      onError: () => {
        notyf.error('Error al rechazar las vacaciones')
      }
    })
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
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
</script>

<style scoped>
.vacaciones-show {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>

