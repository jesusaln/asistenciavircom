<template>
  <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-800 overflow-hidden transition-all duration-300">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse">
        <thead class="bg-gray-50/50 dark:bg-slate-950/50">
          <tr>
            <th class="px-6 py-5 text-left">
              <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em]">Fecha</span>
            </th>
            <th class="px-6 py-5 text-left cursor-pointer group" @click="emitSort('cliente')">
              <div class="flex items-center space-x-2">
                <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em] group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Cliente</span>
                <svg v-if="sortBy.startsWith('cliente')" class="w-3 h-3 text-emerald-500" :class="{ 'rotate-180': sortBy.endsWith('desc') }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>
            <th class="px-6 py-5 text-left">
              <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em]">Número</span>
            </th>
            <th class="px-6 py-5 text-right cursor-pointer group" @click="emitSort('total')">
              <div class="flex items-center justify-end space-x-2">
                <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em] group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Total</span>
                <svg v-if="sortBy.startsWith('total')" class="w-3 h-3 text-emerald-500" :class="{ 'rotate-180': sortBy.endsWith('desc') }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>
            <th class="px-6 py-5 text-center">
                <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estado</span>
            </th>
            <th class="px-6 py-5 text-right">
              <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em]">Acciones</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-slate-800/50">
          <tr v-for="documento in items" :key="documento.id"
              class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-200">
            <!-- Fecha -->
            <td class="px-6 py-5">
              <div class="flex flex-col">
                <span class="text-xs font-black text-gray-900 dark:text-white uppercase">{{ formatearFecha(documento.fecha || documento.created_at) }}</span>
                <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ formatearHora(documento.created_at) }}</span>
              </div>
            </td>

            <!-- Cliente -->
            <td class="px-6 py-5">
              <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center font-black text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-tighter">
                  {{ (documento.cliente?.nombre || documento.cliente?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                </div>
                <div class="flex flex-col max-w-[200px]">
                  <span class="text-xs font-black text-gray-900 dark:text-white uppercase truncate">{{ documento.cliente?.nombre || documento.cliente?.nombre_razon_social || 'Desconocido' }}</span>
                  <span v-if="documento.vendedor" class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">Vendedor: {{ documento.vendedor.name }}</span>
                </div>
              </div>
            </td>

            <!-- Número -->
            <td class="px-6 py-5">
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-slate-800 text-[10px] font-black text-gray-600 dark:text-slate-400 uppercase tracking-widest">
                #{{ documento.numero_venta || documento.id }}
              </span>
            </td>

            <!-- Total -->
            <td class="px-6 py-5 text-right">
              <div class="flex flex-col items-end">
                <span class="text-xs font-black text-gray-900 dark:text-white">${{ formatearMoneda(documento.total) }}</span>
                <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mt-0.5 italic">{{ documento.moneda || 'MXN' }}</span>
              </div>
            </td>

            <!-- Estado -->
            <td class="px-6 py-5 text-center">
              <div class="flex flex-col items-center space-y-1">
                <span :class="obtenerClasesEstado(documento)"
                      class="inline-flex items-center px-3 py-1.5 rounded-full text-[8px] font-black uppercase tracking-widest border border-current transition-all">
                  {{ obtenerLabelEstado(documento) }}
                </span>
                <span v-if="documento.pagado"
                      class="inline-flex items-center px-2 py-0.5 rounded text-[7px] font-black italic bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter">
                  ¡Liquidado!
                </span>
              </div>
            </td>

            <!-- Acciones -->
            <!-- Acciones -->
            <td class="px-6 py-5 text-right">
              <div class="flex items-center justify-end gap-2 flex-nowrap">
                <button
                  @click="onVerDetalles(documento)"
                  class="p-2 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 rounded-lg hover:bg-sky-100 dark:hover:bg-sky-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                  title="Ver Detalles"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </button>

                <button
                  v-if="!esCancelada(documento)"
                  @click="onEditar(documento.id)"
                  class="p-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                  title="Editar"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>

                <button
                  @click="onImprimir(documento)"
                  class="p-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                  title="PDF"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </button>

                <button
                  v-if="!esCancelada(documento) && !documento.factura_uuid"
                  @click="emit('facturar', documento)"
                  class="p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                  title="Facturar Venta"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>

                <button
                   @click="onMarcarPagado(documento)"
                   v-if="!documento.pagado && !esCancelada(documento)"
                   class="p-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                   title="Registrar Pago"
                 >
                   <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>

                <button
                  v-if="!esCancelada(documento)"
                  @click="onCancelar(documento.id)"
                  class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                  title="Cancelar"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="6" class="px-6 py-20 text-center">
              <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-gray-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">No se encontraron ventas</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  documentos: { type: Array, default: () => [] },
  searchTerm: { type: String, default: '' },
  sortBy: { type: String, default: 'fecha-desc' }
})

const emit = defineEmits([
  'ver-detalles', 'editar', 'eliminar', 'imprimir', 'sort', 'marcar-pagado', 'cancelar', 'enviar-email', 'facturar'
])

// Configuración de estados
const estadosConfig = {
  'borrador': { label: 'Borrador', classes: 'text-gray-500 border-gray-200 dark:text-slate-400 dark:border-slate-700' },
  'pendiente': { label: 'Pendiente', classes: 'text-amber-600 border-amber-200 dark:text-amber-400 dark:border-amber-800/50' },
  'aprobada': { label: 'Aprobada', classes: 'text-emerald-600 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800/50' },
  'cancelada': { label: 'Cancelada', classes: 'text-red-600 border-red-200 dark:text-red-400 dark:border-red-800/50' }
}

const determinarEstadoCorrecto = (doc) => {
  const estado = String(doc.estado || '').toLowerCase()
  if (estado.includes('cancel')) return 'cancelada'
  if (doc.pagado || estado === 'aprobada' || estado === 'aprobado') return 'aprobada'
  if (estado === 'borrador') return 'borrador'
  return 'pendiente'
}

const obtenerClasesEstado = (doc) => {
  const estado = determinarEstadoCorrecto(doc)
  return estadosConfig[estado]?.classes || 'text-gray-400 border-gray-100'
}

const obtenerLabelEstado = (doc) => {
  const estado = determinarEstadoCorrecto(doc)
  return estadosConfig[estado]?.label || String(doc.estado || 'Pendiente').toUpperCase()
}

const esCancelada = (doc) => determinarEstadoCorrecto(doc) === 'cancelada'

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number.isFinite(value) ? value : 0)
}

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatearHora = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

const items = computed(() => {
  if (!Array.isArray(props.documentos)) return []
  return props.documentos
})

const onVerDetalles = (doc) => emit('ver-detalles', doc)
const onEditar = (id) => emit('editar', id)
const onImprimir = (doc) => emit('imprimir', doc)
const onMarcarPagado = (doc) => emit('marcar-pagado', doc)
const onCancelar = (id) => emit('cancelar', id)

const emitSort = (field) => {
  const currentDir = props.sortBy.endsWith('desc') ? 'asc' : 'desc'
  emit('sort', `${field}-${currentDir}`)
}
</script>
