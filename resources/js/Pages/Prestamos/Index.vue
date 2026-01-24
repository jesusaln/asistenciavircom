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
  clientes: {
    type: Array,
    default: () => []
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
    classes: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-1 ring-inset ring-emerald-500/20',
    color: 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]'
  },
  'completado': {
    label: 'Completado',
    classes: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 ring-1 ring-inset ring-blue-500/20',
    color: 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]'
  },
  'cancelado': {
    label: 'Cancelado',
    classes: 'bg-red-500/10 text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-500/20',
    color: 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]'
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

  <div class="prestamos-index min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
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
        :clientes="clientes"
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
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-slate-400">
        <div>
          Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} préstamos
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="paginationData.per_page"
            @change="changePerPage"
            class="border border-gray-300 dark:border-slate-800 rounded px-2 py-1 text-sm bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 focus:ring-green-500 focus:border-green-500"
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
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-slate-900 dark:to-slate-800/50 px-6 py-4 border-b border-gray-200 dark:border-slate-800/60">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 tracking-tight">Préstamos</h2>
              <div class="text-sm text-gray-600 dark:text-slate-400 bg-white dark:bg-slate-950/70 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-800/50">
                {{ props.prestamos.data?.length || 0 }} de {{ paginationData.total }} préstamos
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/60">
              <thead>
                <tr class="bg-gray-50/50 dark:bg-slate-950/60">
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Contrato / Folio</th>
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Adjudicación</th>
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Titular / Beneficiario</th>
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Carga Financiera</th>
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Tasa</th>
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Amortización</th>
                  <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Estatus</th>
                  <th class="px-6 py-5 text-right text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Operaciones</th>
                </tr>
              </thead>

              <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800/40">
                <template v-if="props.prestamos.data && props.prestamos.data.length > 0">
                  <tr
                    v-for="prestamo in props.prestamos.data"
                    :key="prestamo.id"
                    class="group hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-all duration-150"
                  >
                    <!-- Folio -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-950 flex items-center justify-center border border-gray-200 dark:border-slate-800">
                           <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <span class="text-sm font-black text-gray-900 dark:text-slate-100 tracking-tight">{{ prestamo.folio || `#${prestamo.id}` }}</span>
                      </div>
                    </td>
                    <!-- Fecha -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col">
                        <div class="text-sm font-bold text-gray-900 dark:text-slate-200">
                          {{ formatearFecha(prestamo.fecha_inicio) }}
                        </div>
                        <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                           ADJ: {{ formatearFecha(prestamo.created_at) }}
                        </div>
                      </div>
                    </td>

                    <!-- Cliente -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col">
                        <div class="text-sm font-bold text-gray-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                          {{ prestamo.cliente?.nombre_razon_social || 'Sin cliente' }}
                        </div>
                        <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                          RFC: {{ prestamo.cliente?.rfc || 'XAXX010101000' }}
                        </div>
                      </div>
                    </td>

                    <!-- Monto -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col">
                        <div class="text-sm font-black text-gray-900 dark:text-slate-100">
                          ${{ formatearMoneda(prestamo.monto_prestado) }}
                        </div>
                        <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-widest mt-0.5">
                          PAGO: ${{ formatearMoneda(prestamo.pago_periodico) }}
                        </div>
                      </div>
                    </td>

                    <!-- Tasa Mensual -->
                    <td class="px-6 py-4">
                      <div class="inline-flex items-center px-2 py-1 bg-slate-100 dark:bg-slate-950 rounded-lg text-xs font-black text-slate-600 dark:text-slate-400 border border-gray-200 dark:border-slate-800">
                        {{ prestamo.tasa_interes_mensual }}%
                      </div>
                    </td>

                    <!-- Pagos -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col">
                        <div class="text-sm font-bold text-gray-900 dark:text-slate-200">
                          {{ prestamo.pagos_realizados || 0 }} <span class="text-xs text-gray-400 font-medium">/ {{ prestamo.numero_pagos }}</span>
                        </div>
                         <!-- Barra de progreso mini -->
                        <div class="w-16 bg-gray-100 dark:bg-slate-950 rounded-full h-1 my-1.5 overflow-hidden">
                           <div class="bg-blue-600 h-full rounded-full transition-all duration-1000" :style="{ width: (prestamo.numero_pagos > 0 ? ((prestamo.pagos_realizados || 0) / prestamo.numero_pagos) * 100 : 0) + '%' }"></div>
                        </div>
                        <div v-if="prestamo.tiene_pagos_atrasados" class="flex items-center gap-1 text-[9px] text-red-600 font-black uppercase tracking-tighter animate-pulse">
                           <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                           MOROSO
                        </div>
                      </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesEstado(prestamo.estado)"
                        class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm border border-black/5"
                      >
                        <span
                          class="w-1.5 h-1.5 rounded-full mr-2"
                          :class="obtenerColorPuntoEstado(prestamo.estado)"
                        ></span>
                        {{ obtenerLabelEstado(prestamo.estado) }}
                      </span>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-right">
                      <div class="flex items-center justify-end gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                        <!-- Ver Detalles -->
                        <Link
                          :href="`/prestamos/${prestamo.id}`"
                          class="p-2 hover:bg-blue-500/10 text-slate-400 hover:text-blue-500 rounded-xl transition-all"
                          title="Explorar Crédito"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </Link>

                        <!-- Generar pagaré -->
                        <button
                          @click="generarPagare(prestamo.id)"
                          class="p-2 hover:bg-emerald-500/10 text-slate-400 hover:text-emerald-500 rounded-xl transition-all"
                          title="Descargar Pagaré Legal"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </button>

                        <!-- Editar -->
                        <button
                          @click="editarPrestamo(prestamo.id)"
                          class="p-2 hover:bg-amber-500/10 text-slate-400 hover:text-amber-500 rounded-xl transition-all"
                          title="Modificar Términos"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>

                        <!-- Registrar pago (solo activos) -->
                         <button
                           v-if="prestamo.estado === 'activo'"
                           @click="registrarPago(prestamo.id)"
                           class="p-2 hover:bg-green-500/10 text-slate-400 hover:text-green-500 rounded-xl transition-all"
                           title="Abonar a Capital"
                         >
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                         </button>

                         <!-- WhatsApp -->
                         <button
                           v-if="prestamo.estado === 'activo' && prestamo.cliente?.telefono"
                           @click="enviarRecordatorioWhatsApp(prestamo)"
                           class="p-2 hover:bg-emerald-600/10 text-slate-400 hover:text-emerald-600 rounded-xl transition-all"
                           title="Recordatorio WhatsApp"
                         >
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                         </button>

                        <button
                          v-if="prestamo.estado === 'cancelado' || (prestamo.estado === 'activo' && prestamo.pagos_realizados === 0 && prestamo.monto_pagado == 0)"
                          @click="confirmarEliminacion(prestamo.id)"
                          class="p-2 hover:bg-red-500/10 text-slate-400 hover:text-red-500 rounded-xl transition-all"
                          title="Eliminar Registro"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-else>
                  <td :colspan="8" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center space-y-4">
                      <div class="w-20 h-20 bg-gray-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center border border-gray-100 dark:border-slate-700/50 shadow-inner">
                        <svg class="w-10 h-10 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                      </div>
                      <div class="space-y-1">
                        <p class="text-gray-900 dark:text-slate-100 font-semibold text-lg">No hay préstamos</p>
                        <p class="text-gray-500 dark:text-slate-400 max-w-xs mx-auto">Tu lista de préstamos aparecerá aquí una vez que comiences a registrarlos.</p>
                      </div>
                      <button @click="crearNuevoPrestamo" class="mt-2 text-green-600 dark:text-green-500 font-medium hover:underline">
                        Crear mi primer préstamo →
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Controles de paginación Premium -->
      <div v-if="paginationData.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-10">
        <p class="text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest leading-none">
           Página {{ paginationData.current_page }} de {{ paginationData.last_page }}
        </p>

        <div class="flex items-center gap-1">
          <button
            @click="prevPage"
            :disabled="paginationData.current_page === 1"
            class="p-3 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-xl text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 disabled:opacity-30 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </button>

          <div class="flex items-center gap-1 mx-2">
            <button
              v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'w-10 h-10 rounded-xl text-xs font-black transition-all flex items-center justify-center',
                page === paginationData.current_page
                  ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30 scale-110'
                  : 'text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800'
              ]"
            >
              {{ page }}
            </button>
          </div>

          <button
            @click="nextPage"
            :disabled="paginationData.current_page === paginationData.last_page"
            class="p-3 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-xl text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 disabled:opacity-30 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </button>
        </div>
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
          class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-8 outline-none border border-gray-100 dark:border-slate-800 relative"
          role="dialog"
          aria-modal="true"
          :aria-label="`Modal de Préstamo`"
          tabindex="-1"
          ref="modalRef"
          @keydown.esc.prevent="onClose"
        >
          <!-- Botón cerrar -->
          <button @click="onClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="text-center py-4">
            <div class="w-20 h-20 mx-auto bg-red-50 dark:bg-red-900/20 rounded-full flex items-center justify-center mb-6 border border-red-100 dark:border-red-900/30">
              <svg class="w-10 h-10 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100 mb-3">
              ¿Eliminar préstamo?
            </h3>
            <p class="text-gray-600 dark:text-slate-400 mb-8 max-w-sm mx-auto">
              Esta acción es irreversible y eliminará todo el historial relacionado con este préstamo.
            </p>
            <div class="flex gap-4">
              <button
                @click="onCancel"
                class="flex-1 px-6 py-3 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-slate-700 transition-all"
              >
                No, mantener
              </button>
              <button
                @click="onConfirm"
                class="flex-1 px-6 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 shadow-lg shadow-red-600/20 transition-all"
              >
                Sí, eliminar
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
    <div v-if="loading" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center z-[100] transition-all">
      <div class="bg-white dark:bg-slate-900 p-8 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 flex flex-col items-center space-y-4">
        <div class="relative w-12 h-12">
          <div class="absolute inset-0 border-4 border-green-500/20 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-green-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <span class="text-gray-900 dark:text-slate-100 font-semibold tracking-wide">Procesando préstamo...</span>
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




