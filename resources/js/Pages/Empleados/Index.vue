<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Swal from '@/Utils/Swal'
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

const verExpediente = (id) => {
  router.visit(`/empleados/${id}/expediente`)
}

const eliminarEmpleado = async (id) => {
  const { isConfirmed } = await Swal.fire({ title: '¿Dar de baja?', text: '¿Está seguro de dar de baja a este empleado?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Dar de baja', cancelButtonText: 'Cancelar' })
  if (isConfirmed) {
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

// Importación XML
const fileInput = ref(null)
const importing = ref(false)

const triggerImport = () => {
  fileInput.value.click()
}

const handleImport = (e) => {
  const files = e.target.files
  if (!files.length) return

  importing.value = true
  const formData = new FormData()
  for (let i = 0; i < files.length; i++) {
    formData.append('files[]', files[i])
  }

  router.post('/empleados/import-xml', formData, {
    onSuccess: () => {
      importing.value = false
      notyf.success('Importación completada')
      // Reset input
      e.target.value = ''
    },
    onError: (errors) => {
      importing.value = false
      notyf.error(errors.error || 'Error al importar XML')
    }
  })
}
</script>

<template>
  <Head title="Empleados - RRHH" />

  <div class="min-h-screen bg-[var(--ui-surface)] dark:bg-slate-800">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Recursos Humanos</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestión de empleados y datos laborales</p>
          </div>
          <div class="flex items-center space-x-3">
            <input
              type="file"
              ref="fileInput"
              class="hidden"
              multiple
              accept=".xml"
              @change="handleImport"
            />
            
            <button
              @click="triggerImport"
              :disabled="importing"
              class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-2xl shadow-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 transition-all duration-200"
            >
              <svg v-if="!importing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <svg v-else class="animate-spin h-4 w-4 mr-2 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ importing ? 'Importando...' : 'Importar XML' }}
            </button>

            <Link
              href="/empleados/cumplimiento"
              class="inline-flex items-center px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-semibold rounded-2xl shadow-sm hover:scale-105 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              Matriz de Cumplimiento
            </Link>

            <Link
              href="/empleados/plantillas"
              class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-2xl shadow-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Gestionar Plantillas
            </Link>

            <button
              @click="importarExcel"
              class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-2xl shadow-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Importar Excel
            </button>

            <button
              @click="crearEmpleado"
              class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-2xl shadow-sm hover:from-emerald-700 hover:to-teal-700 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Nuevo Empleado
            </button>
          </div>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-50 dark:bg-sky-900/20 rounded-xl flex items-center justify-center">
              <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Empleados</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ estadisticas.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-slate-800/50 rounded-xl flex items-center justify-center">
              <svg class="w-10 h-10 text-emerald-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Activos</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-slate-400">{{ estadisticas.activos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center">
              <svg class="w-10 h-10 text-slate-500 dark:text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Inactivos</p>
              <p class="text-2xl font-bold text-slate-500 dark:text-slate-200">{{ estadisticas.inactivos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Departamentos</p>
              <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ Object.keys(estadisticas.por_departamento || {}).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="md:col-span-2">
            <div class="relative">
              <input
                v-model="searchTerm"
                @keyup.enter="handleSearch"
                type="text"
                placeholder="Buscar por nombre, número, RFC..."
                class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />
              <svg class="absolute left-3 top-2.5 h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <select
            v-model="filtroDepartamento"
            @change="aplicarFiltros"
            class="border border-slate-300 dark:border-slate-700 rounded-xl py-2 px-3 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500"
          >
            <option value="">Todos los departamentos</option>
            <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
          </select>

          <select
            v-model="filtroTipoContrato"
            @change="aplicarFiltros"
            class="border border-slate-300 dark:border-slate-700 rounded-xl py-2 px-3 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500"
          >
            <option value="">Tipo de contrato</option>
            <option v-for="tipo in tiposContrato" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
          </select>

          <button
            @click="limpiarFiltros"
            class="px-4 py-2 text-slate-500 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors border border-slate-300 dark:border-slate-700"
          >
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 dark:from-slate-700/50 dark:to-slate-800/50 px-6 py-4 border-b border-slate-200/60 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Lista de Empleados</h2>
            <div class="text-sm text-slate-500 dark:text-slate-200 bg-white/70 dark:bg-slate-700/50 px-3 py-1 rounded-full border border-slate-200/50 dark:border-slate-700">
              {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }}
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Empleado</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Puesto</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Departamento</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Contratación</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Salario</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr
                v-for="empleado in empleados.data"
                :key="empleado.id"
                class="group hover:bg-white/60 transition-all duration-150"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-semibold">
                      {{ empleado.name?.charAt(0) || '?' }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-slate-900">{{ empleado.name || 'Sin nombre' }}</div>
                      <div class="text-[10px] text-slate-500 font-black uppercase tracking-tighter">NSS: {{ empleado.nss || '—' }}</div>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900">{{ empleado.puesto || '—' }}</div>
                  <div class="text-xs text-slate-500">{{ empleado.tipo_contrato_formateado || empleado.tipo_contrato }}</div>
                </td>

                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-purple-100 text-purple-800">
                    {{ empleado.departamento || 'Sin departamento' }}
                  </span>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900">{{ formatearFecha(empleado.fecha_contratacion) }}</div>
                  <div v-if="empleado.antiguedad_formateada" class="text-xs text-slate-500">{{ empleado.antiguedad_formateada }}</div>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900">{{ formatearMoneda(empleado.salario_base) }}</div>
                  <div class="text-xs text-slate-500 capitalize">{{ empleado.frecuencia_pago || "mensual" }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="verEmpleado(empleado.id)"
                      class="p-2 rounded-xl bg-sky-50 dark:bg-sky-900/20 text-blue-600 hover:bg-sky-100 transition-colors"
                      title="Ver detalles"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <button
                      @click="editarEmpleado(empleado.id)"
                      class="p-2 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 hover:bg-brand-100 transition-colors"
                      title="Editar"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <Link
                      :href="`/nominas/create?empleado_id=${empleado.id}`"
                      class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 transition-colors"
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
                      class="p-2 rounded-xl bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors"
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
                      class="p-2 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-sky-100 transition-colors"
                      title="Ver Contrato Adjunto"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>

                    <!-- Botón Expediente Digital (Fase 1) -->
                    <button
                      @click="verExpediente(empleado.id)"
                      class="p-2 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 hover:bg-orange-100 transition-colors"
                      title="Expediente Digital y Contratos"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>

                    <button
                      @click="eliminarEmpleado(empleado.id)"
                      class="p-2 rounded-xl bg-rose-50 dark:bg-rose-900/20 text-rose-600 hover:bg-rose-100 transition-colors"
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
                  <div class="flex flex-col items-center space-y-6">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-slate-700 font-medium">No hay empleados</p>
                      <p class="text-sm text-slate-500">Agrega tu primer empleado para comenzar</p>
                    </div>
                    <button @click="crearEmpleado" class="mt-4 px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors">
                      Agregar Empleado
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.last_page > 1" class="px-6 py-4 border-t border-slate-200 flex justify-center">
          <div class="flex space-x-1">
            <button
              v-for="p in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(x => x > 0 && x <= paginationData.last_page)"
              :key="p"
              @click="goToPage(p)"
              :class="[
                'px-3 py-2 text-sm font-medium border rounded-xl',
                p === paginationData.current_page
                  ? 'bg-brand-500 text-white border-emerald-500'
                  : 'text-slate-700 bg-white hover:bg-white border-slate-300'
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
