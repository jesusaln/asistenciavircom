<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  nominas: { type: Object, default: () => ({ data: [] }) },
  estadisticas: { type: Object, default: () => ({}) },
  empleados: { type: Array, default: () => [] },
  aniosDisponibles: { type: Array, default: () => [] },
  anioActual: { type: Number, default: () => new Date().getFullYear() },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({}) },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const page = usePage()
onMounted(() => {
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
})

const filtroEmpleado = ref(props.filters.empleado_id || '')
const filtroEstado = ref(props.filters.estado || '')
const filtroAnio = ref(props.filters.anio || props.anioActual)

const aplicarFiltros = () => {
  router.visit('/nominas', {
    data: {
      empleado_id: filtroEmpleado.value,
      estado: filtroEstado.value,
      anio: filtroAnio.value,
    }
  })
}

const limpiarFiltros = () => {
  filtroEmpleado.value = ''
  filtroEstado.value = ''
  filtroAnio.value = new Date().getFullYear()
  router.visit('/nominas')
}

const crearNomina = () => router.visit('/nominas/create')
const verNomina = (id) => router.visit(`/nominas/${id}`)

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2 })}`
}

const paginationData = computed(() => ({
  current_page: props.nominas?.current_page || 1,
  last_page: props.nominas?.last_page || 1,
  from: props.nominas?.from || 0,
  to: props.nominas?.to || 0,
  total: props.nominas?.total || 0,
}))

const goToPage = (p) => {
  router.visit('/nominas', {
    data: { page: p, empleado_id: filtroEmpleado.value, estado: filtroEstado.value, anio: filtroAnio.value }
  })
}
</script>

<template>
  <Head title="Nóminas" />

  <div class="min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Nóminas</h1>
            <p class="mt-1 text-sm text-gray-500">Gestión de pagos de nómina a empleados</p>
          </div>
          <button @click="crearNomina" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-lg shadow-sm hover:from-emerald-700 hover:to-teal-700 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nueva Nómina
          </button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Nóminas</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Pendientes</p>
              <p class="text-2xl font-bold text-amber-600">{{ estadisticas.procesadas }}</p>
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
              <p class="text-sm font-medium text-gray-500">Pagadas</p>
              <p class="text-2xl font-bold text-green-600">{{ estadisticas.pagadas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Pagado</p>
              <p class="text-xl font-bold text-emerald-600">{{ formatearMoneda(estadisticas.monto_total_pagado) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <select v-model="filtroEmpleado" @change="aplicarFiltros" class="border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-emerald-500">
            <option value="">Todos los empleados</option>
            <option v-for="emp in empleados" :key="emp.value" :value="emp.value">{{ emp.label }}</option>
          </select>

          <select v-model="filtroEstado" @change="aplicarFiltros" class="border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-emerald-500">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="procesada">Procesada</option>
            <option value="pagada">Pagada</option>
            <option value="cancelada">Cancelada</option>
          </select>

          <select v-model="filtroAnio" @change="aplicarFiltros" class="border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-emerald-500">
            <option v-for="anio in aniosDisponibles" :key="anio" :value="anio">{{ anio }}</option>
          </select>

          <button @click="limpiarFiltros" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
            Limpiar filtros
          </button>

          <Link href="/nominas/create" class="px-4 py-2 bg-emerald-600 text-white text-center rounded-lg hover:bg-emerald-700 transition-colors">
            + Nueva Nómina
          </Link>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-200/60">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Lista de Nóminas</h2>
            <div class="text-sm text-gray-600 bg-white/70 px-3 py-1 rounded-full border border-gray-200/50">
              {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }}
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200/60">
            <thead class="bg-white/60">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Empleado</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Período</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Percepciones</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Deducciones</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Neto</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
              </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200/40">
              <tr v-for="nomina in nominas.data" :key="nomina.id" class="group hover:bg-white/60 transition-all">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                      {{ nomina.empleado?.user?.name?.charAt(0) || '?' }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ nomina.empleado?.user?.name || 'Sin nombre' }}</div>
                      <div class="text-xs text-gray-500">{{ nomina.empleado?.numero_empleado }}</div>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ nomina.periodo_formateado }}</div>
                  <div class="text-xs text-gray-500">Período #{{ nomina.numero_periodo }}</div>
                </td>

                <td class="px-6 py-4">
                  <span class="text-sm text-gray-700">{{ nomina.tipo_periodo_formateado }}</span>
                </td>

                <td class="px-6 py-4 text-right">
                  <span class="text-sm font-medium text-green-600">{{ formatearMoneda(nomina.total_percepciones) }}</span>
                </td>

                <td class="px-6 py-4 text-right">
                  <span class="text-sm font-medium text-red-600">{{ formatearMoneda(nomina.total_deducciones) }}</span>
                </td>

                <td class="px-6 py-4 text-right">
                  <span class="text-sm font-bold text-gray-900">{{ formatearMoneda(nomina.total_neto) }}</span>
                </td>

                <td class="px-6 py-4">
                  <span :class="nomina.estado_info?.color" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                    <span :class="nomina.estado_info?.dot" class="w-2 h-2 rounded-full mr-1.5"></span>
                    {{ nomina.estado_info?.label }}
                  </span>
                </td>

                <td class="px-6 py-4">
                  <div class="flex items-center justify-end space-x-2">
                    <button @click="verNomina(nomina.id)" class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Ver detalle">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <Link :href="`/nominas/${nomina.id}/pdf`" class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" title="Descargar PDF">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                    </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="!nominas.data?.length">
                <td colspan="8" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div>
                      <p class="text-gray-700 font-medium">No hay nóminas</p>
                      <p class="text-sm text-gray-500">Genera tu primera nómina para comenzar</p>
                    </div>
                    <button @click="crearNomina" class="mt-4 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                      Crear Nómina
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
              :class="['px-3 py-2 text-sm font-medium border rounded-md', p === paginationData.current_page ? 'bg-emerald-500 text-white border-emerald-500' : 'text-gray-700 bg-white hover:bg-white border-gray-300']"
            >
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
