<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleados: {
    type: Object,
    default: () => ({ data: [] })
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total: 0,
      activos: 0,
      inactivos: 0,
      por_departamento: {}
    })
  },
  departamentos: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({ sort_by: 'created_at', sort_direction: 'desc' })
  }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Filtros
const searchTerm = ref(props.filters.search || '')
const filtroDepartamento = ref(props.filters.departamento || '')
const filtroTipoContrato = ref(props.filters.tipo_contrato || '')
const filtroActivo = ref(props.filters.activo || '')
const sortBy = ref(`${props.sorting.sort_by}-${props.sorting.sort_direction}`)

const tiposContrato = [
  { value: 'tiempo_completo', label: 'Tiempo Completo' },
  { value: 'medio_tiempo', label: 'Medio Tiempo' },
  { value: 'temporal', label: 'Temporal' },
  { value: 'honorarios', label: 'Honorarios' },
  { value: 'indefinido', label: 'Tiempo Indefinido' },
]

const imprimirContrato = (id) => {
  window.open(`/empleados/${id}/imprimir-contrato`, '_blank')
}

const descargarContrato = (id) => {
  window.open(`/empleados/${id}/descargar-contrato`, '_blank')
}

const handleSearch = () => {
  aplicarFiltros()
}

const aplicarFiltros = () => {
  const [sort_by, sort_direction] = sortBy.value.split('-')
  router.visit('/empleados', {
    data: {
      search: searchTerm.value,
      departamento: filtroDepartamento.value,
      tipo_contrato: filtroTipoContrato.value,
      activo: filtroActivo.value,
      sort_by,
      sort_direction
    }
  })
}

const limpiarFiltros = () => {
  searchTerm.value = ''
  filtroDepartamento.value = ''
  filtroTipoContrato.value = ''
  filtroActivo.value = ''
  sortBy.value = 'created_at-desc'
  router.visit('/empleados')
}

const crearEmpleado = () => {
  router.visit('/empleados/create')
}

const verEmpleado = (id) => {
  router.visit(`/empleados/${id}`)
}

const editarEmpleado = (id) => {
  router.visit(`/empleados/${id}/edit`)
}

const eliminarEmpleado = (id) => {
  if (confirm('¿Está seguro de dar de baja a este empleado?')) {
    router.delete(`/empleados/${id}`, {
      onSuccess: () => notyf.success('Empleado dado de baja'),
      onError: () => notyf.error('Error al dar de baja al empleado')
    })
  }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

const formatearFecha = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Paginación
const paginationData = computed(() => ({
  current_page: props.empleados?.current_page || 1,
  last_page: props.empleados?.last_page || 1,
  from: props.empleados?.from || 0,
  to: props.empleados?.to || 0,
  total: props.empleados?.total || 0,
}))

const goToPage = (page) => {
  const [sort_by, sort_direction] = sortBy.value.split('-')
  router.visit('/empleados', {
    data: {
      page,
      search: searchTerm.value,
      departamento: filtroDepartamento.value,
      tipo_contrato: filtroTipoContrato.value,
      sort_by,
      sort_direction
    }
  })
}
</script>

<template>
  <Head title="Empleados - RRHH" />

  <div class="min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Recursos Humanos</h1>
            <p class="mt-1 text-sm text-gray-500">Gestión de empleados y datos laborales</p>
          </div>
          <button
            @click="crearEmpleado"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-lg shadow-sm hover:from-emerald-700 hover:to-teal-700 transition-all duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nuevo Empleado
          </button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Empleados</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Activos</p>
              <p class="text-2xl font-bold text-green-600">{{ estadisticas.activos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Inactivos</p>
              <p class="text-2xl font-bold text-gray-600">{{ estadisticas.inactivos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Departamentos</p>
              <p class="text-2xl font-bold text-purple-600">{{ Object.keys(estadisticas.por_departamento || {}).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="md:col-span-2">
            <div class="relative">
              <input
                v-model="searchTerm"
                @keyup.enter="handleSearch"
                type="text"
                placeholder="Buscar por nombre, número, RFC..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              />
              <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <select
            v-model="filtroDepartamento"
            @change="aplicarFiltros"
            class="border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-emerald-500"
          >
            <option value="">Todos los departamentos</option>
            <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
          </select>

          <select
            v-model="filtroTipoContrato"
            @change="aplicarFiltros"
            class="border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-emerald-500"
          >
            <option value="">Tipo de contrato</option>
            <option v-for="tipo in tiposContrato" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
          </select>

          <button
            @click="limpiarFiltros"
            class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-200/60">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Lista de Empleados</h2>
            <div class="text-sm text-gray-600 bg-white/70 px-3 py-1 rounded-full border border-gray-200/50">
              {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }}
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200/60">
            <thead class="bg-white/60">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Empleado</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Puesto</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Departamento</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contratación</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Salario</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200/40">
              <tr
                v-for="empleado in empleados.data"
                :key="empleado.id"
                class="group hover:bg-white/60 transition-all duration-150"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-semibold">
                      {{ empleado.user?.name?.charAt(0) || '?' }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ empleado.user?.name || 'Sin nombre' }}</div>
                      <div class="text-xs text-gray-500">{{ empleado.numero_empleado || '—' }}</div>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ empleado.puesto || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ empleado.tipo_contrato_formateado || empleado.tipo_contrato }}</div>
                </td>

                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ empleado.departamento || 'Sin departamento' }}
                  </span>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(empleado.fecha_contratacion) }}</div>
                  <div v-if="empleado.antiguedad_formateada" class="text-xs text-gray-500">{{ empleado.antiguedad_formateada }}</div>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ formatearMoneda(empleado.salario_base) }}</div>
                  <div class="text-xs text-gray-500">mensual</div>
                </td>

                <td class="px-6 py-4">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="verEmpleado(empleado.id)"
                      class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                      title="Ver detalles"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <button
                      @click="editarEmpleado(empleado.id)"
                      class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors"
                      title="Editar"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <Link
                      :href="`/nominas/create?empleado_id=${empleado.id}`"
                      class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                      title="Generar nómina"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </Link>

                    <!-- Botón Imprimir (Condicional) -->
                    <button
                      v-if="empleado.puede_imprimir_contrato"
                      @click="imprimirContrato(empleado.id)"
                      class="p-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors"
                      title="Imprimir Contrato"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                      </svg>
                    </button>

                    <!-- Botón Descargar (Si tiene adjunto) -->
                    <button
                      v-if="empleado.contrato_adjunto"
                      @click="descargarContrato(empleado.id)"
                      class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                      title="Ver Contrato Adjunto"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>

                    <button
                      @click="eliminarEmpleado(empleado.id)"
                      class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                      title="Dar de baja"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!empleados.data?.length">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay empleados</p>
                      <p class="text-sm text-gray-500">Agrega tu primer empleado para comenzar</p>
                    </div>
                    <button @click="crearEmpleado" class="mt-4 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                      Agregar Empleado
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex justify-center">
          <div class="flex space-x-1">
            <button
              v-for="p in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(x => x > 0 && x <= paginationData.last_page)"
              :key="p"
              @click="goToPage(p)"
              :class="[
                'px-3 py-2 text-sm font-medium border rounded-md',
                p === paginationData.current_page
                  ? 'bg-emerald-500 text-white border-emerald-500'
                  : 'text-gray-700 bg-white hover:bg-white border-gray-300'
              ]"
            >
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
