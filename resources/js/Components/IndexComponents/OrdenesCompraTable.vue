<template>
  <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-300">
    <!-- Tooltip Premium (Mismo estilo que Compras) -->
    <Teleport to="body">
      <div
        v-if="showTooltip && hoveredDoc"
        class="fixed z-[9999] bg-white/80 dark:bg-slate-900/90 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 backdrop-blur-xl w-80 max-h-96 pointer-events-auto transform transition-all duration-200 ease-out"
        :style="tooltipStyle"
        @mouseenter="clearHideTimeout"
        @mouseleave="hideProductTooltip"
      >
        <div class="p-5 border-b border-slate-100 dark:border-slate-800/50">
            <div class="flex items-center justify-between">
              <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-wide">Productos de la Orden</h3>
              <span class="px-2 py-0.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-500 dark:text-slate-400">
                {{ getProductosDelDoc(hoveredDoc)?.length || 0 }}
              </span>
            </div>
          </div>

          <div class="max-h-72 overflow-y-auto px-5 pb-5 custom-scrollbar">
            <div v-if="getProductosDelDoc(hoveredDoc)?.length" class="space-y-3 pt-3">
              <div
                v-for="(producto, index) in getProductosDelDoc(hoveredDoc)"
                :key="index"
                class="group p-3 bg-transparent dark:bg-slate-800/30 rounded-xl hover:bg-white dark:hover:bg-slate-800/50 border border-transparent hover:border-slate-100 dark:hover:border-slate-700 transition-all duration-150"
              >
               <div class="flex items-start justify-between">
                 <div class="flex-1 min-w-0 mr-3">
                   <p class="text-[11px] font-black text-slate-900 dark:text-white uppercase truncate">
                     {{ producto.nombre || producto.descripcion || 'Sin nombre' }}
                   </p>
                   <div class="flex items-center mt-1.5 space-x-2 text-[9px] font-bold uppercase tracking-wide">
                     <span class="text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 px-2 py-0.5 rounded-xl border border-slate-100 dark:border-slate-800">
                       {{ producto.cantidad || 0 }} UNID
                     </span>
                     <span class="text-slate-300 dark:text-slate-600">•</span>
                     <span class="text-slate-500 dark:text-slate-400">
                       ${{ formatearMoneda(producto.precio || 0) }}
                     </span>
                   </div>
                 </div>
                 <div class="text-right flex-shrink-0">
                   <p class="text-[11px] font-black text-slate-900 dark:text-white">
                     ${{ formatearMoneda((producto.cantidad || 0) * (producto.precio || 0)) }}
                   </p>
                 </div>
               </div>
              </div>
            </div>
          </div>
      </div>
    </Teleport>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-transparent dark:bg-slate-800/50">
          <tr>
            <th class="px-6 py-5 text-left">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Fecha Emisión</span>
            </th>

            <th class="px-6 py-5 text-left">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Proveedor Designado</span>
            </th>

            <th class="px-6 py-5 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Folio Control</span>
            </th>

            <th class="px-6 py-5 text-right">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Importe Total</span>
            </th>

            <th class="px-6 py-5 text-center">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estatus</span>
            </th>

            <th class="px-6 py-5 text-right">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Gestión</span>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
          <template v-if="items.length > 0">
            <tr
              v-for="doc in items"
              :key="doc.id"
              class="group hover:bg-transparent dark:hover:bg-slate-800/30 transition-all duration-200"
              :class="{ 'opacity-60': doc.estado === 'cancelada' }"
            >
              <!-- Fecha -->
              <td class="px-6 py-5">
                <div class="flex flex-col">
                  <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ formatearFecha(doc.fecha_orden || doc.created_at) }}</span>
                  <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-0.5">{{ formatearHora(doc.created_at) }}</span>
                </div>
              </td>

              <!-- Proveedor -->
              <td class="px-6 py-5">
                <div class="flex items-center space-x-3">
                  <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide shadow-sm border border-slate-200/50 dark:border-slate-700/50">
                    {{ (doc.proveedor?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                  </div>
                  <div class="flex flex-col max-w-[200px]">
                    <span class="text-xs font-black text-slate-900 dark:text-white uppercase truncate">{{ doc.proveedor?.nombre_razon_social || 'Sin proveedor' }}</span>
                    <span v-if="doc.proveedor?.email" class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-0.5 truncate">{{ doc.proveedor?.email }}</span>
                  </div>
                </div>
              </td>

              <!-- Folio -->
              <td class="px-6 py-5">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                    #{{ doc.numero_orden || doc.id }}
                  </span>
                </div>
              </td>

              <!-- Total -->
              <td class="px-6 py-5 text-right">
                <div class="flex flex-col items-end">
                  <span class="text-xs font-black text-slate-900 dark:text-white">${{ formatearMoneda(doc.total) }}</span>
                  <div
                    class="flex items-center gap-1 cursor-help group/items mt-0.5"
                    @mouseenter="getProductosDelDoc(doc)?.length ? showProductTooltip(doc, $event) : null"
                    @mouseleave="hideProductTooltip"
                    @mousemove="getProductosDelDoc(doc)?.length ? updateTooltipPosition($event) : null"
                  >
                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ getProductosDelDoc(doc)?.length || 0 }} ITEMS</span>
                    <svg class="w-2.5 h-2.5 text-slate-300 dark:text-slate-600 group-hover/items:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </td>

              <!-- Estado -->
              <td class="px-6 py-5 text-center">
                <div class="flex flex-col items-center space-y-1.5">
                  <span
                    :class="obtenerClasesEstado(doc.estado)"
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-[8px] font-black uppercase tracking-wide border border-current transition-all"
                  >
                    {{ obtenerLabelEstado(doc.estado) }}
                  </span>
                  
                  <span 
                    v-if="doc.email_enviado" 
                    class="inline-flex items-center px-2 py-0.5 rounded-xl text-[7px] font-black italic bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 uppercase tracking-wide"
                  >
                    E-MAIL ENVIADO
                  </span>
                </div>
              </td>

              <!-- Acciones -->
              <td class="px-6 py-5 text-right">
                <div class="flex items-center justify-end gap-2 flex-nowrap">
                  <button
                    @click="onVerDetalles(doc)"
                    class="p-2 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 rounded-xl hover:bg-sky-100 dark:hover:bg-sky-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                    title="Ver Detalles"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </button>

                  <button
                    v-if="['borrador', 'pendiente', 'aprobada', 'enviado_a_proveedor'].includes(doc.estado)"
                    @click="onEditar(doc.id)"
                    class="p-2 bg-indigo-50 dark:bg-sky-900/20 text-indigo-600 dark:text-indigo-400 rounded-xl hover:bg-sky-100 dark:hover:bg-indigo-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                    title="Editar"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </button>

                  <button
                    @click="onImprimir(doc)"
                    class="p-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                    title="PDF"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                  </button>

                  <button
                    v-if="['pendiente', 'aprobada', 'enviado_a_proveedor'].includes(doc.estado)"
                    @click="onEliminar(doc.id)"
                    class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 transition-all shadow-sm hover:shadow-md hover:scale-105 active:scale-95"
                    title="Cancelar"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                  </button>
                </div>
              </td>
            </tr>
          </template>

          <tr v-else>
            <td colspan="6" class="px-6 py-20 text-center">
              <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-transparent dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                </div>
                <p class="text-sm font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">No se encontraron órdenes de compra</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'ver-detalles', 'editar', 'eliminar', 'imprimir'
])

// Tooltip Management
const showTooltip = ref(false)
const hoveredDoc = ref(null)
const tooltipPosition = ref({ x: 0, y: 0 })
let tooltipTimeout = null

const tooltipStyle = computed(() => {
  const OFFSET = 20, TOOLTIP_WIDTH = 320, TOOLTIP_HEIGHT = 384
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight

  let x = tooltipPosition.value.x + OFFSET
  let y = tooltipPosition.value.y - 100

  if (x + TOOLTIP_WIDTH > viewportWidth - 20) {
    x = tooltipPosition.value.x - TOOLTIP_WIDTH - OFFSET
  }

  if (y + TOOLTIP_HEIGHT > viewportHeight - 20) {
    y = viewportHeight - TOOLTIP_HEIGHT - 20
  }
  if (y < 20) y = 20

  return {
    left: `${x}px`,
    top: `${y}px`,
  }
})

const getProductosDelDoc = (doc) => {
  if (!doc) return [];
  return Array.isArray(doc.productos) ? doc.productos : (Array.isArray(doc.items) ? doc.items : []);
}

const showProductTooltip = (doc, event) => {
  clearTimeout(tooltipTimeout)
  hoveredDoc.value = doc
  updateTooltipPosition(event)
  tooltipTimeout = setTimeout(() => { showTooltip.value = true }, 300)
}

const hideProductTooltip = () => {
  clearTimeout(tooltipTimeout)
  showTooltip.value = false
  hoveredDoc.value = null
}

const updateTooltipPosition = (event) => {
  tooltipPosition.value = { x: event.clientX, y: event.clientY }
}

const clearHideTimeout = () => clearTimeout(tooltipTimeout)

const configEstados = {
  'borrador': { label: 'Borrador', classes: 'text-slate-500 border-slate-200 dark:text-slate-400 dark:border-slate-800' },
  'pendiente': { label: 'Pendiente', classes: 'text-brand-500 border-brand-200 dark:text-brand-400 dark:border-brand-800/50' },
  'aprobada': { label: 'Aprobada', classes: 'text-sky-500 border-sky-200 dark:text-sky-400 dark:border-sky-800/50' },
  'enviado_a_proveedor': { label: 'Enviada', classes: 'text-blue-600 border-blue-200 dark:text-blue-400 dark:border-blue-800/50' },
  'procesada': { label: 'Procesada', classes: 'text-emerald-600 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800/50' },
  'cancelada': { label: 'Cancelada', classes: 'text-rose-600 border-rose-200 dark:text-rose-400 dark:border-rose-800/50' }
}

const obtenerClasesEstado = (estado) => configEstados[estado]?.classes || 'text-slate-400 border-slate-100'
const obtenerLabelEstado = (estado) => configEstados[estado]?.label || String(estado || 'PENDIENTE').toUpperCase()

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

const onVerDetalles = (doc) => emit('ver-detalles', doc)
const onEditar = (id) => emit('editar', id)
const onImprimir = (doc) => emit('imprimir', doc)
const onEliminar = (id) => emit('eliminar', id)
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
