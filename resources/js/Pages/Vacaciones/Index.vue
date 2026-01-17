<template>
  <Head title="Gestión de Vacaciones" />
  <div class="vacaciones-index min-h-screen bg-gray-50">
    <div class="w-full px-6 py-8">
      <!-- Flash success -->
      <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        {{$page.props.flash.success}}
      </div>

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Vacaciones</h1>
            <p class="text-gray-600 mt-1">Administra las solicitudes de vacaciones de los empleados</p>
          </div>
          <div class="flex gap-3">
            <Link
              :href="route('vacaciones.create')"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              Nueva Solicitud
            </Link>
            <button
              @click="showCrearParaEmpleado = true"
              class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Crear para Empleado
            </button>
            <button
              @click="irRegistro"
              :disabled="!selectedEmpleadoId"
              class="inline-flex items-center px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
              </svg>
              Ver Registro
            </button>
          </div>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Solicitudes</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Pendientes</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.pendientes }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Aprobadas</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.aprobadas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-2 bg-red-100 rounded-lg">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Rechazadas</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.rechazadas }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal para crear vacaciones para empleado específico -->
      <div v-if="showCrearParaEmpleado" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCrearParaEmpleado = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Crear Vacaciones para Empleado</h3>
            <button @click="showCrearParaEmpleado = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Empleado</label>
              <select
                v-model="empleadoSeleccionado"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Elegir empleado...</option>
                <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                  {{ empleado.name }}
                </option>
              </select>
            </div>

            <div class="flex justify-end gap-3">
              <button
                @click="showCrearParaEmpleado = false"
                class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancelar
              </button>
              <button
                @click="crearParaEmpleado"
                :disabled="!empleadoSeleccionado"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Crear Vacaciones
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
            <select
              v-model="filters.empleado"
              @change="applyFilters"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos los empleados</option>
              <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                {{ empleado.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
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

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
            <input
              v-model="filters.fecha_desde"
              type="date"
              @change="applyFilters"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
            <input
              v-model="filters.fecha_hasta"
              type="date"
              @change="applyFilters"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Empleado
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Fechas
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Días
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Motivo
                </th>
                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Registro</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="vacacion in vacaciones.data" :key="vacacion.id" class="hover:bg-gray-50 transition-colors duration-150" :class="vacacion.id === Number(props.highlightId) ? 'bg-green-50 ring-2 ring-green-300' : ''" :ref="el => { if (vacacion.id && el) { rowRefs.value = rowRefs.value || {}; rowRefs.value[String(vacacion.id)] = el } }">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">
                {{ vacacion.empleado.name }}
                  </div>
                  <div class="text-sm text-gray-500">{{ vacacion.empleado.puesto }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">
                    {{ formatDate(vacacion.fecha_inicio) }} - {{ formatDate(vacacion.fecha_fin) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ vacacion.dias_solicitados }} días</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="getEstadoClasses(vacacion.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getEstadoLabel(vacacion.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 max-w-xs truncate" :title="vacacion.motivo">
                    {{ vacacion.motivo || 'Sin motivo especificado' }}
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <Link :href="route('registro-vacaciones.por-empleado', vacacion.user_id)" class="text-amber-600 hover:underline">Ver</Link>
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
                      <p class="text-gray-700 font-medium">No hay solicitudes de vacaciones</p>
                      <p class="text-sm text-gray-500">Las solicitudes aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="vacaciones.last_page > 1" class="bg-white border-t border-gray-200 px-4 py-3">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Mostrando {{ vacaciones.from }} - {{ vacaciones.to }} de {{ vacaciones.total }} resultados
            </div>

            <div class="flex space-x-1">
              <button
                v-for="page in getPageNumbers()"
                :key="page"
                @click="changePage(page)"
                :class="page === vacaciones.current_page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
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
  <!-- Botón flotante para Ajustar días (abre el modal) -->
  <button
    @click="showAjuste = true"
    class="fixed bottom-6 right-6 inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-full shadow-lg hover:bg-purple-700"
    title="Ajustar días de vacaciones"
  >
    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
    </svg>
    Ajustar días
  </button>

  <!-- Modal de ajuste de días por Admin -->
  <div v-if="showAjuste" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeAjuste()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Ajustar días de vacaciones</h3>
        <button @click="closeAjuste" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
          <select v-model="ajuste.empleadoId" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <option value="">Seleccione un empleado</option>
                <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                  {{ empleado.name }}
                </option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Año</label>
            <input type="number" v-model.number="ajuste.anio" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Días (+/-)</label>
            <input type="number" v-model.number="ajuste.dias" class="w-full border rounded-lg px-3 py-2" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Motivo (opcional)</label>
          <textarea v-model="ajuste.motivo" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
        </div>
        <p v-if="ajusteError" class="text-sm text-red-600">{{ ajusteError }}</p>
      </div>
      <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
        <button @click="closeAjuste" class="px-4 py-2 border rounded-lg">Cancelar</button>
        <button @click="submitAjuste" :disabled="ajusteLoading" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
          {{ ajusteLoading ? 'Aplicando...' : 'Aplicar ajuste' }}
        </button>
      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  vacaciones: Object,
  stats: Object,
  empleados: Array,
  filters: Object,
  sorting: Object,
  highlightId: [Number, String]
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const filters = ref({
  empleado: props.filters?.empleado || '',
  estado: props.filters?.estado || '',
  fecha_desde: props.filters?.fecha_desde || '',
  fecha_hasta: props.filters?.fecha_hasta || '',
})

const showCrearParaEmpleado = ref(false)
const empleadoSeleccionado = ref('')
const selectedEmpleadoId = computed(() => empleadoSeleccionado.value || filters.value.empleado)

const irRegistro = () => {
  if (selectedEmpleadoId.value) {
    router.visit(route('registro-vacaciones.por-empleado', selectedEmpleadoId.value))
  }
}

// Destacar y desplazarse a la última solicitud creada
const rowRefs = ref({})
onMounted(() => {
  if (props.highlightId) {
    nextTick(() => {
      const id = String(props.highlightId)
      const el = rowRefs.value ? rowRefs.value[id] : null
      if (el && el.scrollIntoView) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    })
  }
})

// UI de ajuste manual de días (solo admin; el endpoint valida rol)
const showAjuste = ref(false)
const ajusteLoading = ref(false)
const ajusteError = ref('')
const ajuste = ref({
  empleadoId: '',
  anio: new Date().getFullYear(),
  dias: 1,
  motivo: ''
})

const closeAjuste = () => {
  showAjuste.value = false
  ajusteLoading.value = false
  ajusteError.value = ''
  ajuste.value = { empleadoId: '', anio: new Date().getFullYear(), dias: 1, motivo: '' }
}

const submitAjuste = async () => {
  ajusteError.value = ''
  const a = ajuste.value
  if (!a.empleadoId) {
    ajusteError.value = 'Seleccione un empleado'
    return
  }
  if (!Number.isInteger(a.dias) || a.dias < -365 || a.dias > 365) {
    ajusteError.value = 'Ingrese un número de días entre -365 y 365'
    return
  }
  ajusteLoading.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch(route('registro-vacaciones.ajustar', a.empleadoId), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ dias: a.dias, anio: a.anio, motivo: a.motivo })
    })
    if (!res.ok) {
      const data = await res.json().catch(() => ({}))
      throw new Error(data?.message || 'No se pudo aplicar el ajuste')
    }
    closeAjuste()
    notyf.success('Ajuste aplicado correctamente')
    // Refrescar listado para reflejar cambios, si aplica
    router.reload({ only: ['vacaciones', 'stats'] })
  } catch (e) {
    ajusteError.value = e.message
    notyf.error(ajusteError.value)
  } finally {
    ajusteLoading.value = false
  }
}

const applyFilters = () => {
  router.get(route('vacaciones.index'), {
    empleado: filters.value.empleado,
    estado: filters.value.estado,
    fecha_desde: filters.value.fecha_desde,
    fecha_hasta: filters.value.fecha_hasta,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const aprobarVacacion = (vacacion) => {
  if (confirm('¿Estás seguro de que deseas aprobar esta solicitud de vacaciones?')) {
    router.post(route('vacaciones.aprobar', vacacion.id), {
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

const rechazarVacacion = (vacacion) => {
  const observaciones = prompt('Ingresa el motivo del rechazo (opcional):')
  if (confirm('¿Estás seguro de que deseas rechazar esta solicitud de vacaciones?')) {
    router.post(route('vacaciones.rechazar', vacacion.id), {
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
  router.get(route('vacaciones.index'), {
    ...filters.value,
    page: page
  }, { preserveState: true, preserveScroll: true })
}

const crearParaEmpleado = () => {
  if (empleadoSeleccionado.value) {
    router.visit(route('vacaciones.create-para-empleado', empleadoSeleccionado.value))
    showCrearParaEmpleado.value = false
    empleadoSeleccionado.value = ''
  }
}
</script>

<style scoped>
.vacaciones-index {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>




