<!-- /resources/js/Pages/Prestamos/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import PrestamosHeader from '@/Components/IndexComponents/PrestamosHeader.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  prestamos: {
    type: Object,
    default: () => ({ data: [] })
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total: 0,
      activos: 0,
      completados: 0,
      cancelados: 0,
      monto_total_prestado: 0,
      monto_total_pagado: 0,
      monto_total_pendiente: 0,
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({ sort_by: 'created_at', sort_direction: 'desc' })
  },
  pagination: {
    type: Object,
    default: () => ({})
  },
})

/* =========================
   Configuración de notificaciones
========================= */
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

/* =========================
   Estado local y modal
========================= */
const showModal = ref(false)
const modalMode = ref('details')
const selectedPrestamo = ref(null)
const selectedId = ref(null)
const loading = ref(false)

/* =========================
   Filtros, orden y datos
========================= */
const searchTerm = ref('')
const sortBy = ref('created_at-desc')
const filtroEstado = ref('')
const filtroCliente = ref('')

/* =========================
   Paginación del servidor
========================= */
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

const goToPage = (page) => {
  const query = {
    page,
    search: searchTerm.value,
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/prestamos', { data: query })
}

const nextPage = () => {
  const currentPage = props.pagination?.current_page || 1
  const lastPage = props.pagination?.last_page || 1

  if (currentPage < lastPage) {
    goToPage(currentPage + 1)
  }
}

const prevPage = () => {
  const currentPage = props.pagination?.current_page || 1

  if (currentPage > 1) {
    goToPage(currentPage - 1)
  }
}

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  filtroCliente.value = ''
  router.visit('/prestamos')
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  if (newSort && typeof newSort === 'string') {
    sortBy.value = newSort
    const query = {
      sort_by: newSort.split('-')[0],
      sort_direction: newSort.split('-')[1] || 'desc',
      search: searchTerm.value,
      estado: filtroEstado.value,
      cliente_id: filtroCliente.value
    }
    router.visit('/prestamos', { data: query })
  }
}

const changePerPage = (event) => {
  const perPage = event.target.value
  const query = {
    per_page: perPage,
    search: searchTerm.value,
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/prestamos', { data: query })
}

const handleSearch = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/prestamos', { data: query })
}

const handleFilter = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/prestamos', { data: query })
}

/* =========================
   Validaciones y utilidades
========================= */
function validarPrestamo(prestamo) {
  if (!prestamo?.id) {
    throw new Error('ID de préstamo no válido')
  }
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (prestamo) => {
  try {
    validarPrestamo(prestamo)
    selectedPrestamo.value = prestamo
    modalMode.value = 'details'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarPrestamo = (id) => {
  try {
    const prestamoId = id || selectedPrestamo.value?.id
    if (!prestamoId) throw new Error('ID de préstamo no válido')

    router.visit(`/prestamos/${prestamoId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const confirmarEliminacion = (id) => {
  try {
    if (!id) throw new Error('ID de préstamo no válido')

    selectedId.value = id
    modalMode.value = 'confirm'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const eliminarPrestamo = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ningún préstamo')

    loading.value = true

    router.delete(`/prestamos/${selectedId.value}`, {
      onStart: () => {
        notyf.success('Eliminando préstamo...')
      },
      onSuccess: (response) => {
        notyf.success('Préstamo eliminado exitosamente')
        showModal.value = false
        selectedId.value = null
      },
      onError: (errors) => {
        console.error('Error al eliminar:', errors)
        notyf.error('Error al eliminar el préstamo')
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
  }
}

const crearNuevoPrestamo = () => {
  router.visit('/prestamos/create')
}

const verPagos = (prestamoId) => {
  router.visit(`/pagos?prestamo_id=${prestamoId}`)
}

const cambiarEstado = async (prestamo, nuevoEstado) => {
  try {
    loading.value = true

    router.patch(`/prestamos/${prestamo.id}/cambiar-estado`, {
      estado: nuevoEstado
    }, {
      onSuccess: (response) => {
        notyf.success('Estado actualizado correctamente')
      },
      onError: (errors) => {
        console.error('Error al cambiar estado:', errors)
        notyf.error('Error al cambiar el estado del préstamo')
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
  }
}

const registrarPago = (prestamoId) => {
  try {
    router.visit(`/pagos/create?prestamo_id=${prestamoId}`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const generarPagare = (prestamoId) => {
   try {
     router.visit(`/prestamos/${prestamoId}/pagare`)
   } catch (error) {
     notyf.error(error.message)
   }
}

const enviarRecordatorioWhatsApp = async (prestamo) => {
   try {
     if (!prestamo.cliente?.telefono) {
       notyf.error('El cliente no tiene número de teléfono registrado')
       return
     }

     // Verificar que WhatsApp esté configurado
     try {
       const configResponse = await fetch('/api/whatsapp/test', {
         method: 'POST',
         headers: {
           'Content-Type': 'application/json',
           'Accept': 'application/json',
         },
         body: JSON.stringify({
           telefono: prestamo.cliente.telefono,
           mensaje: 'Verificación de configuración WhatsApp'
         })
       })

       if (!configResponse.ok) {
         const errorData = await configResponse.json()
         notyf.error('WhatsApp no está configurado: ' + (errorData.message || 'Error de configuración'))
         return
       }
     } catch (error) {
       notyf.error('Error verificando configuración de WhatsApp')
       return
     }

     // Confirmar envío
     if (confirm(`¿Enviar recordatorio de pago por WhatsApp a ${prestamo.cliente.nombre_razon_social}?`)) {
       try {
         const response = await fetch(`/prestamos/${prestamo.id}/enviar-recordatorio-whatsapp`, {
           method: 'POST',
           headers: {
             'Content-Type': 'application/json',
             'Accept': 'application/json',
             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
           }
         })

         const data = await response.json()

         if (response.ok && data.success) {
           notyf.success(data.message)
         } else {
           notyf.error('Error: ' + (data.message || 'Error desconocido'))
         }
       } catch (error) {
         console.error('Error enviando recordatorio:', error)
         notyf.error('Error de conexión: ' + error.message)
       }
     }
   } catch (error) {
     notyf.error(error.message)
   }
}

// Configuración de estados para préstamos
const configEstados = {
  'activo': {
    label: 'Activo',
    classes: 'bg-green-100 text-green-700',
    color: 'bg-green-400'
  },
  'completado': {
    label: 'Completado',
    classes: 'bg-blue-100 text-blue-700',
    color: 'bg-blue-400'
  },
  'cancelado': {
    label: 'Cancelado',
    classes: 'bg-red-100 text-red-700',
    color: 'bg-red-400'
  }
};

const obtenerClasesEstado = (estado) => {
  return configEstados[estado]?.classes || 'bg-gray-100 text-gray-700';
}

const obtenerColorPuntoEstado = (estado) => {
  return configEstados[estado]?.color || 'bg-gray-400';
}

const obtenerLabelEstado = (estado) => {
  return configEstados[estado]?.label || 'Pendiente';
}

// Función para formatear moneda
const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
}

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible';
  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return 'Fecha inválida';
    return new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch {
    return 'Fecha inválida';
  }
}

// Funciones para Modal
const modalRef = ref(null)

const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => showModal, (v) => { if (v) setTimeout(focusFirst, 0) })

const onKey = (e) => { if (e.key === 'Escape' && showModal.value) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const onCancel = () => { showModal.value = false; selectedPrestamo.value = null; selectedId.value = null; }
const onConfirm = () => { eliminarPrestamo() }
const onClose = () => { showModal.value = false; selectedPrestamo.value = null; selectedId.value = null; }
const onEditarFila = () => { editarPrestamo(selectedPrestamo.value?.id) }
</script>

<template>
  <Head title="Préstamos" />

  <div class="prestamos-index min-h-screen bg-white dark:bg-slate-900">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header específico de préstamos -->
      <PrestamosHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :completados="estadisticas.completados"
        :cancelados="estadisticas.cancelados"
        :monto_total_prestado="estadisticas.monto_total_prestado"
        :monto_total_pagado="estadisticas.monto_total_pagado"
        :monto_total_pendiente="estadisticas.monto_total_pendiente"
        :clientes="[]"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-cliente="filtroCliente"
        @crear-nueva="crearNuevoPrestamo"
        @search-change="handleSearch"
        @filtro-estado-change="handleFilter"
        @filtro-cliente-change="handleFilter"
        @sort-change="updateSort"
        @limpiar-filtros="handleLimpiarFiltros"
      />

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-gray-300">
        <div>
          Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} préstamos
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="paginationData.per_page"
            @change="changePerPage"
            class="border border-gray-300 rounded px-2 py-1 text-sm"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Tabla de préstamos -->
      <div class="mt-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-200 dark:border-slate-800/60">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white tracking-tight">Préstamos</h2>
              <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-slate-900/70 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-800/50">
                {{ props.prestamos.data?.length || 0 }} de {{ paginationData.total }} préstamos
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/60">
              <thead class="bg-white dark:bg-slate-900/60">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Monto</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Tasa Mensual</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Pagos</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                  <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>

              <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800/40">
                <template v-if="props.prestamos.data && props.prestamos.data.length > 0">
                  <tr
                    v-for="prestamo in props.prestamos.data"
                    :key="prestamo.id"
                    class="group hover:bg-white dark:bg-slate-900/60 transition-all duration-150 hover:shadow-sm"
                  >
                    <!-- Fecha -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ formatearFecha(prestamo.fecha_inicio) }}
                        </div>
                        <div class="text-xs text-gray-400">
                          Reg: {{ formatearFecha(prestamo.created_at) }}
                        </div>
                      </div>
                    </td>

                    <!-- Cliente -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-gray-800 dark:text-gray-100">
                          {{ prestamo.cliente?.nombre_razon_social || 'Sin cliente' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          {{ prestamo.cliente?.rfc || '' }}
                        </div>
                      </div>
                    </td>

                    <!-- Monto -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          ${{ formatearMoneda(prestamo.monto_prestado) }}
                        </div>
                        <div class="text-xs text-green-600">
                          Pago: ${{ formatearMoneda(prestamo.pago_periodico) }}
                        </div>
                      </div>
                    </td>

                    <!-- Tasa Mensual -->
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700">
                        {{ prestamo.tasa_interes_mensual }}%
                      </div>
                    </td>

                    <!-- Pagos -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm text-gray-900 dark:text-white">
                          {{ prestamo.pagos_realizados || 0 }} / {{ prestamo.numero_pagos }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          {{ prestamo.numero_pagos > 0 ? Math.round(((prestamo.pagos_realizados || 0) / prestamo.numero_pagos) * 100) : 0 }}% completado
                        </div>
                        <div v-if="prestamo.tiene_pagos_atrasados" class="text-xs text-red-600 font-medium">
                          ¡Pagos atrasados!
                        </div>
                      </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesEstado(prestamo.estado)"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                      >
                        <span
                          class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                          :class="obtenerColorPuntoEstado(prestamo.estado)"
                        ></span>
                        {{ obtenerLabelEstado(prestamo.estado) }}
                      </span>
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end space-x-2">
                        <!-- Ver detalles -->
                        <button
                          @click="verDetalles(prestamo)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                          title="Ver detalles"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>

                        <!-- Ver pagos -->
                        <button
                          @click="verPagos(prestamo.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-1"
                          title="Ver pagos del préstamo"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                          </svg>
                        </button>

                        <!-- Generar pagaré -->
                        <button
                          @click="generarPagare(prestamo.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                          title="Generar pagaré PDF"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                          </svg>
                        </button>

                        <!-- Editar -->
                        <button
                          @click="editarPrestamo(prestamo.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                          title="Editar préstamo"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
<!-- Registrar pago (solo activos) -->
 <button
   v-if="prestamo.estado === 'activo'"
   @click="registrarPago(prestamo.id)"
   class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
   title="Registrar pago"
 >
   <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
   </svg>
 </button>

 <!-- Enviar recordatorio WhatsApp (solo activos con cliente) -->
 <button
   v-if="prestamo.estado === 'activo' && prestamo.cliente?.telefono"
   @click="enviarRecordatorioWhatsApp(prestamo)"
   class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-1"
   title="Enviar recordatorio por WhatsApp"
 >
   <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
   </svg>
 </button>

                        <!-- Eliminar (cancelados o activos sin pagos) -->
                        <button
                          v-if="prestamo.estado === 'cancelado' || (prestamo.estado === 'activo' && prestamo.pagos_realizados === 0 && prestamo.monto_pagado == 0)"
                          @click="confirmarEliminacion(prestamo.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                          title="Eliminar préstamo"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <!-- Empty State -->
                <tr v-else>
                  <td :colspan="7" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center space-y-4">
                      <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                      </div>
                      <div class="space-y-1">
                        <p class="text-gray-700 font-medium">No hay préstamos</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Los préstamos aparecerán aquí cuando se creen</p>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Controles de paginación -->
      <div v-if="paginationData.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
        <button
          @click="prevPage"
          :disabled="paginationData.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 border border-gray-300 rounded-md hover:bg-white dark:bg-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <button
            v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 text-sm font-medium border border-gray-300 rounded-md',
              page === paginationData.current_page
                ? 'bg-green-500 text-white border-green-500'
                : 'text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button
          @click="nextPage"
          :disabled="paginationData.current_page === paginationData.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 border border-gray-300 rounded-md hover:bg-white dark:bg-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Modal de detalles / confirmación -->
    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="onClose"
      >
        <div
          class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 outline-none"
          role="dialog"
          aria-modal="true"
          :aria-label="`Modal de Préstamo`"
          tabindex="-1"
          ref="modalRef"
          @keydown.esc.prevent="onClose"
        >
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="text-center">
            <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-medium mb-2">
              ¿Eliminar préstamo?
            </h3>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
              Esta acción no se puede deshacer.
            </p>
            <div class="flex gap-3">
              <button
                @click="onCancel"
                class="flex-1 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="onConfirm"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                Eliminar
              </button>
            </div>
          </div>

          <!-- Modo: Detalles -->
          <div v-else-if="modalMode === 'details'" class="space-y-4">
            <h3 class="text-lg font-medium mb-1 flex items-center gap-2">
              Detalles de Préstamo
              <span v-if="selectedPrestamo?.id" class="text-sm text-gray-500 dark:text-gray-400">#{{ selectedPrestamo.id }}</span>
            </h3>

            <div v-if="selectedPrestamo" class="space-y-4">
              <!-- Información general -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Cliente:</strong> {{ selectedPrestamo.cliente?.nombre_razon_social || 'Sin cliente' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Monto Prestado:</strong> ${{ formatearMoneda(selectedPrestamo.monto_prestado) }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Tasa de Interés:</strong> {{ selectedPrestamo.tasa_interes_mensual }}%
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Pago Periódico:</strong> ${{ formatearMoneda(selectedPrestamo.pago_periodico) }}
                  </p>
                </div>

                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Estado:</strong>
                    <span
                      :class="obtenerClasesEstado(selectedPrestamo.estado)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-2"
                    >
                      {{ obtenerLabelEstado(selectedPrestamo.estado) }}
                    </span>
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Pagos Realizados:</strong> {{ selectedPrestamo.pagos_realizados }} / {{ selectedPrestamo.numero_pagos }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Fecha de Inicio:</strong> {{ formatearFecha(selectedPrestamo.fecha_inicio) }}
                  </p>
                </div>
              </div>

              <!-- Información financiera -->
              <div class="bg-white dark:bg-slate-900 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Resumen Financiero</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p class="text-gray-600 dark:text-gray-300">Total a Pagar:</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">${{ formatearMoneda(selectedPrestamo.monto_total_pagar) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600 dark:text-gray-300">Total Pagado:</p>
                    <p class="text-lg font-semibold text-green-600">${{ formatearMoneda(selectedPrestamo.monto_pagado) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600 dark:text-gray-300">Monto Pendiente:</p>
                    <p class="text-lg font-semibold text-orange-600">${{ formatearMoneda(selectedPrestamo.monto_pendiente) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600 dark:text-gray-300">Interés Total:</p>
                    <p class="text-lg font-semibold text-blue-600">${{ formatearMoneda(selectedPrestamo.monto_interes_total) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-wrap justify-end gap-2 mt-6">
              <button
                @click="onEditarFila"
                class="px-3 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm"
              >
                ✏️ Editar
              </button>

              <button
                @click="onClose"
                class="px-3 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 transition-colors text-sm"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
          <span class="text-gray-700">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prestamos-index {
  min-height: 100vh;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
.modal-enter-to,
.modal-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>




