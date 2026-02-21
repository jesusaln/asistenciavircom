<template>
  <div class="compras-table-container">
    
    <!-- Tooltip (Premium Glassmorphism) -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95 -translate-y-2"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 -translate-y-2"
      >
        <div
          v-if="showTooltip && hoveredDoc"
          class="fixed z-[9999] bg-white/90 dark:bg-slate-900/90 backdrop-blur-2xl rounded-[2.5rem] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.3)] border border-white/20 dark:border-slate-800/50 w-80 pointer-events-none"
          :style="tooltipStyle"
        >
          <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex flex-col">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400">Detalle de Items</h3>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Compra #{{ hoveredDoc.numero_compra || hoveredDoc.id }}</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-blue-500/10 flex items-center justify-center">
                    <span class="text-xs font-black text-blue-600 dark:text-blue-400">{{ getProductosDelDoc(hoveredDoc)?.length || 0 }}</span>
                </div>
            </div>

            <div class="space-y-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                <div 
                    v-for="(producto, index) in getProductosDelDoc(hoveredDoc)" 
                    :key="index"
                    class="group p-4 bg-slate-50 dark:bg-slate-950/50 rounded-2xl border border-slate-100 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-300"
                >
                    <div class="flex justify-between items-start mb-2">
                        <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight leading-tight leading-relaxed max-w-[70%] line-clamp-2">
                            {{ producto.nombre || producto.descripcion || 'Sin nombre' }}
                        </p>
                        <span class="text-[10px] font-black text-blue-600 dark:text-blue-400">{{ producto.cantidad }} UN</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Precio Unit.</span>
                        <span class="text-xs font-black text-slate-900 dark:text-white">${{ formatearMoneda(producto.precio || producto.pv || 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800/50 flex justify-between items-center">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Subtotal Items</span>
                <span class="text-sm font-black text-slate-900 dark:text-white">${{ formatearMoneda(hoveredDoc.total || 0) }}</span>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Table Container -->
    <div class="overflow-x-auto rounded-[2.5rem] bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-200/10 dark:shadow-none">
      <table class="min-w-full border-separate border-spacing-y-2 px-6 pb-6">
        <thead>
          <tr class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
            <th class="px-6 py-8 text-left cursor-pointer hover:text-blue-600 transition-colors group" @click="onSort('fecha')">
                <div class="flex items-center gap-2">
                    FECHA
                    <svg v-if="sortBy.startsWith('fecha')" :class="['w-3 h-3 transition-transform duration-300', sortBy === 'fecha-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </th>
            <th class="px-6 py-8 text-left cursor-pointer hover:text-blue-600 transition-colors group" @click="onSort('proveedor')">
                <div class="flex items-center gap-2">
                    PROVEEDOR
                    <svg v-if="sortBy.startsWith('proveedor')" :class="['w-3 h-3 transition-transform duration-300', sortBy === 'proveedor-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </th>
            <th class="px-6 py-8 text-left cursor-pointer hover:text-blue-600 transition-colors group" @click="onSort('numero_compra')">
                <div class="flex items-center gap-2">
                    FOLIO
                    <svg v-if="sortBy.startsWith('numero_compra')" :class="['w-3 h-3 transition-transform duration-300', sortBy === 'numero_compra-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </th>
            <th class="px-6 py-8 text-left">ORIGEN</th>
            <th class="px-6 py-8 text-left cursor-pointer hover:text-blue-600 transition-colors group" @click="onSort('total')">
                <div class="flex items-center gap-2">
                    TOTAL
                    <svg v-if="sortBy.startsWith('total')" :class="['w-3 h-3 transition-transform duration-300', sortBy === 'total-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </th>
            <th class="px-6 py-8 text-left">ITEMS</th>
            <th class="px-6 py-8 text-left">ESTADO</th>
            <th class="px-6 py-8 text-right">ACCIONES</th>
          </tr>
        </thead>

        <tbody class="space-y-4">
          <template v-if="items.length > 0">
            <tr
              v-for="doc in items"
              :key="doc.id"
              class="group hover:scale-[1.01] transition-all duration-500 ease-out"
              :class="doc.estado === 'cancelada' ? 'opacity-50' : ''"
            >
              <!-- Fecha -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 first:rounded-l-[2rem] border-y border-l border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex flex-col">
                  <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ formatearFecha(doc.fecha) }}</span>
                  <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Registrado</span>
                </div>
              </td>

              <!-- Proveedor -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500 dark:text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all duration-500 uppercase">
                        {{ (doc.proveedor?.nombre_razon_social || '?').charAt(0) }}
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight max-w-[150px] truncate">{{ doc.proveedor?.nombre_razon_social || 'Desconocido' }}</span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">PROVEEDOR</span>
                    </div>
                </div>
              </td>

              <!-- Folio -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-black text-slate-900 dark:text-white font-mono">#{{ doc.numero_compra || doc.id }}</span>
                    <div v-if="doc.cfdi_uuid" class="text-blue-500 animate-pulse" title="XML Vinculado">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
              </td>

              <!-- Origen -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <span
                  :class="[
                    'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                    doc.cfdi_uuid ? 'bg-purple-500/5 text-purple-600 border-purple-500/20' : 
                    (doc.origen === 'orden_compra' ? 'bg-blue-500/5 text-blue-600 border-blue-500/20' : 'bg-emerald-500/5 text-emerald-600 border-emerald-500/20')
                  ]"
                >
                  {{ doc.cfdi_uuid ? 'XML IMPORT' : (doc.origen === 'orden_compra' ? 'ORDEN C.' : 'DIRECTA') }}
                </span>
              </td>

              <!-- Total -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex items-baseline gap-1">
                    <span class="text-[9px] font-black text-slate-400">$</span>
                    <span class="text-sm font-black text-slate-900 dark:text-white tracking-tighter">{{ formatearMoneda(doc.total) }}</span>
                </div>
              </td>

              <!-- Items Hover -->
              <td 
                class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900 cursor-help"
                @mouseenter="showProductTooltip(doc, $event)"
                @mouseleave="hideProductTooltip"
              >
                <div class="flex items-center gap-2 group/items">
                    <div class="w-7 h-7 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center group-hover/items:bg-blue-600 group-hover/items:text-white transition-all duration-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ getProductosDelDoc(doc)?.length || 0 }}</span>
                </div>
              </td>

              <!-- Estado -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full" :class="obtenerColorPuntoEstado(doc.estado)"></div>
                        <span class="text-[10px] font-black uppercase tracking-[0.1em] text-slate-900 dark:text-white">{{ obtenerLabelEstado(doc.estado) }}</span>
                    </div>
                    <div class="flex">
                        <span 
                            :class="[
                                'text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border',
                                doc.estatus_pago === 'pagado' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 
                                (doc.estatus_pago === 'vencido' ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20')
                            ]"
                        >
                            {{ doc.estatus_pago || 'PENDIENTE' }}
                        </span>
                    </div>
                </div>
              </td>

              <!-- Acciones -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 last:rounded-r-[2rem] border-y border-r border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900 text-right">
                <div class="flex items-center justify-end gap-1">
                    <button @click="onVerDetalles(doc)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-blue-600 hover:text-white transition-all duration-300" title="Detalles">
                        <font-awesome-icon icon="eye" class="text-xs" />
                    </button>
                    <button v-if="doc.estado === 'procesada'" @click="onEditar(doc.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-amber-500 hover:text-white transition-all duration-300" title="Editar">
                        <font-awesome-icon icon="edit" class="text-xs" />
                    </button>
                    <button @click="onImprimir(doc)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-indigo-600 hover:text-white transition-all duration-300" title="Imprimir">
                        <font-awesome-icon icon="print" class="text-xs" />
                    </button>
                    <button v-if="doc.estado === 'procesada' || doc.estado === 'cancelada'" @click="onEliminar(doc.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all duration-300" title="Eliminar/Cancelar">
                        <font-awesome-icon :icon="doc.estado === 'procesada' ? 'times-circle' : 'trash'" class="text-xs" />
                    </button>
                </div>
              </td>
            </tr>
          </template>

          <tr v-else>
            <td colspan="8" class="text-center py-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl">
              <div class="flex flex-col items-center">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-[2rem] flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m0 0V9a2 2 0 012-2h2m2 2v4" /></svg>
                </div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">No hay registros disponibles</p>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest mt-2">Prueba cambiando los filtros de búsqueda</p>
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
  documentos: { type: Array, default: () => [] },
  searchTerm: { type: String, default: '' },
  sortBy: { type: String, default: 'fecha-desc' },
  filtroEstado: { type: String, default: '' },
  filtroOrigen: { type: String, default: '' }
})

const emit = defineEmits([
  'ver-detalles', 'editar', 'eliminar', 'imprimir', 'sort'
])

// Función para obtener productos del documento (igual que en órdenes de compra)
const getProductosDelDoc = (doc) => {
  if (!doc) return [];
  const productos = doc.productos || doc.items || [];

  // Validar que sea un array y tenga elementos válidos
  if (!Array.isArray(productos)) return [];
  if (!productos.length) return [];

  // Filtrar productos válidos
  return productos.filter(p =>
    p &&
    (p.nombre || p.descripcion) &&
    (p.cantidad > 0 || p.cantidad !== null)
  );
}

// Tooltip
const showTooltip = ref(false)
const hoveredDoc = ref(null)
const tooltipPosition = ref({ x: 0, y: 0 })
let tooltipTimeout = null

const getViewport = () => {
  if (typeof window === 'undefined') return { w: 1280, h: 800 }
  return { w: window.innerWidth, h: window.innerHeight }
}

const tooltipStyle = computed(() => {
  const OFFSET = 20, TOOLTIP_WIDTH = 320, TOOLTIP_HEIGHT = 384, VIEWPORT_PADDING = 16
  const { w, h } = getViewport()

  let x = tooltipPosition.value.x + OFFSET
  let y = tooltipPosition.value.y - (TOOLTIP_HEIGHT / 2)

  if (x + TOOLTIP_WIDTH > w - VIEWPORT_PADDING) x = tooltipPosition.value.x - TOOLTIP_WIDTH - OFFSET
  if (x < VIEWPORT_PADDING) x = VIEWPORT_PADDING
  if (y < VIEWPORT_PADDING) y = VIEWPORT_PADDING
  else if (y + TOOLTIP_HEIGHT > h - VIEWPORT_PADDING) y = h - TOOLTIP_HEIGHT - VIEWPORT_PADDING

  return {
    left: `${x}px`,
    top: `${y}px`,
    transform: showTooltip.value ? 'scale(1) translateY(0)' : 'scale(0.95) translateY(-10px)',
    opacity: showTooltip.value ? '1' : '0'
  }
})

const showProductTooltip = (doc, event) => {
  if (!getProductosDelDoc(doc)?.length) return
  clearTimeout(tooltipTimeout)
  hoveredDoc.value = doc
  updateTooltipPosition(event)
  tooltipTimeout = setTimeout(() => { showTooltip.value = true }, 500)
}

const hideProductTooltip = () => {
  clearTimeout(tooltipTimeout)
  tooltipTimeout = setTimeout(() => {
    showTooltip.value = false
    hoveredDoc.value = null
  }, 300)
}

const clearHideTimeout = () => {
  clearTimeout(tooltipTimeout)
}

const updateTooltipPosition = (event) => {
  tooltipPosition.value = { x: event.clientX, y: event.clientY }
}

// Estados específicos para compras
const configEstados = {
  'borrador': {
    label: 'Borrador',
    classes: 'bg-gray-100 text-gray-700',
    color: 'bg-gray-400'
  },
  'pendiente': {
    label: 'Pendiente',
    classes: 'bg-yellow-100 text-yellow-700',
    color: 'bg-yellow-400'
  },
  'procesada': {
    label: 'Procesada',
    classes: 'bg-green-100 text-green-700',
    color: 'bg-green-400'
  },
  'cancelada': {
    label: 'Cancelada',
    classes: 'bg-red-100 text-red-700',
    color: 'bg-red-400'
  }
}

const obtenerClasesEstado = (estado) => configEstados[estado]?.classes || 'bg-gray-100 text-gray-700'
const obtenerColorPuntoEstado = (estado) => configEstados[estado]?.color || 'bg-gray-400'
const obtenerLabelEstado = (estado) => configEstados[estado]?.label || 'Pendiente'

// Cache de formatos
const formatCache = new Map()

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  const cacheKey = `fecha-${date}`
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey)

  try {
    // Parsear la fecha manualmente para evitar interpretación UTC
    const cleanStr = String(date).replace('T', ' ').split(' ')[0];
    const [year, month, day] = cleanStr.split('-').map(Number);
    
    if (!year || !month || !day) return 'Fecha inválida';
    
    const localDate = new Date(year, month - 1, day);
    const formatted = localDate.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
    formatCache.set(cacheKey, formatted)
    return formatted
  } catch {
    return 'Fecha inválida'
  }
}

const formatearHora = (date) => {
  if (!date) return ''
  const cacheKey = `hora-${date}`
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey)

  try {
    const time = new Date(date).getTime()
    if (Number.isNaN(time)) return ''
    const formatted = new Date(time).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
    formatCache.set(cacheKey, formatted)
    return formatted
  } catch {
    return ''
  }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  const cacheKey = `moneda-${safe}`
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey)
  const formatted = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
  formatCache.set(cacheKey, formatted)
  return formatted
}

// Items filtrados y ordenados
const items = computed(() => {
  if (!Array.isArray(props.documentos)) {
    console.warn('⚠️ Documentos is not an array:', props.documentos)
    return []
  }

  let filtered = props.documentos.slice()

  // Filtro de búsqueda
  if (props.searchTerm) {
    const term = props.searchTerm.toLowerCase().trim()
    filtered = filtered.filter(doc => {
      return (
        (doc.proveedor?.nombre_razon_social || '').toLowerCase().includes(term) ||
        (doc.productos || []).some(p => (p.nombre || '').toLowerCase().includes(term)) ||
        (doc.numero_compra || doc.id || '').toString().toLowerCase().includes(term)
      )
    })
  }

  // Filtro de estado
  if (props.filtroEstado) {
    filtered = filtered.filter(doc => doc.estado === props.filtroEstado)
  }

  // Filtro de origen
  if (props.filtroOrigen) {
    filtered = filtered.filter(doc => doc.origen === props.filtroOrigen)
  }

  // Ordenamiento
  if (props.sortBy) {
    const [field, direction] = props.sortBy.split('-')

    filtered.sort((a, b) => {
      let aVal, bVal

      switch (field) {
        case 'fecha':
          aVal = new Date(a.created_at || a.fecha).getTime() || 0
          bVal = new Date(b.created_at || b.fecha).getTime() || 0
          break
        case 'proveedor':
          aVal = (a.proveedor?.nombre_razon_social || '').toLowerCase()
          bVal = (b.proveedor?.nombre_razon_social || '').toLowerCase()
          break
        case 'numero_compra':
          aVal = (a.numero_compra || a.id || '').toString().toLowerCase()
          bVal = (b.numero_compra || b.id || '').toString().toLowerCase()
          break
        case 'total':
          aVal = parseFloat(a.total) || 0
          bVal = parseFloat(b.total) || 0
          break
        case 'estado':
          aVal = obtenerLabelEstado(a.estado).toLowerCase()
          bVal = obtenerLabelEstado(b.estado).toLowerCase()
          break
        default:
          aVal = (a[field] || '').toString().toLowerCase()
          bVal = (b[field] || '').toString().toLowerCase()
      }

      const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0
      return direction === 'desc' ? -comparison : comparison
    })
  }

  return filtered
})

const total = computed(() => props.documentos?.length || 0)

// Emits helpers
const onVerDetalles = (doc) => emit('ver-detalles', doc)
const onEditar = (id) => emit('editar', id)
const onEliminar = (id) => emit('eliminar', id)
const onImprimir = (doc) => emit('imprimir', doc)

const onSort = (field) => {
  const current = props.sortBy.startsWith(field) ? props.sortBy : `${field}-desc`
  const newOrder = current === `${field}-desc` ? `${field}-asc` : `${field}-desc`
  emit('sort', newOrder)
}
</script>

<style scoped>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}

/* Hide arrow from selects in Firefox */
select {
    -moz-appearance: none;
    -webkit-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1rem;
}

.dark select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
}

/* Transition smooth focus */
input:focus, select:focus {
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }

@media (prefers-contrast: high) {
  .bg-gray-50 { background-color: #f9fafb; }
  .border-gray-200 { border-color: #d1d5db; }
}

button:focus-visible { outline: 2px solid; outline-offset: 2px; }

@media (hover: none) {
  .hover\:bg-gray-50:hover { background-color: transparent; }
  .group:hover { transform: none; }
}
</style>
