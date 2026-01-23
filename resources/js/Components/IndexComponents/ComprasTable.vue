<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-200 dark:border-slate-800/60">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white tracking-tight">Compras</h2>
        <div class="text-sm text-gray-600 bg-white dark:bg-slate-900/70 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-800/50">
          {{ items.length }} de {{ total }} compras
        </div>
      </div>
    </div>

    <!-- Tooltip -->
    <Teleport to="body">
      <div
        v-if="showTooltip && hoveredDoc"
        class="fixed z-[9999] bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-gray-200 dark:border-slate-800/50 backdrop-blur-sm w-80 max-h-96 pointer-events-auto transform transition-all duration-200 ease-out"
        :style="tooltipStyle"
        @mouseenter="clearHideTimeout"
        @mouseleave="hideProductTooltip"
      >
        <div class="p-4 border-b border-gray-100 dark:border-slate-800">
           <div class="flex items-center justify-between">
             <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Productos</h3>
             <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full font-medium">
               {{ getProductosDelDoc(hoveredDoc)?.length || 0 }}
             </span>
           </div>
         </div>

         <div class="max-h-72 overflow-y-auto px-4 pb-4 custom-scrollbar">
           <div v-if="getProductosDelDoc(hoveredDoc)?.length" class="space-y-2 pt-2">
             <div
               v-for="(producto, index) in getProductosDelDoc(hoveredDoc)"
               :key="index"
               class="group p-3 bg-gray-50/70 rounded-lg hover:bg-gray-100/70 hover:shadow-sm transition-all duration-150"
             >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0 mr-3">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-gray-800">
                    {{ producto.nombre || producto.descripcion || 'Sin nombre' }}
                  </p>
                  <div class="flex items-center mt-1.5 space-x-2 text-xs">
                    <span class="text-gray-600 bg-white dark:bg-slate-900/60 px-2 py-0.5 rounded-md">
                      {{ producto.cantidad || 0 }} und
                    </span>
                    <span class="text-gray-400">•</span>
                    <span class="text-gray-600">
                      ${{ formatearMoneda(producto.precio || producto.precio_venta || producto.pv || 0) }}
                    </span>
                  </div>
                  <p v-if="producto.descripcion" class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 line-clamp-2">
                    {{ producto.descripcion }}
                  </p>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    ${{ formatearMoneda((producto.cantidad || 0) * (producto.precio || producto.precio_venta || producto.pv || 0)) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m0 0V9a2 2 0 012-2h2m2 2v4" />
              </svg>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Sin productos registrados</p>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200/60">
        <thead class="bg-gray-50/60">
          <tr>
            <!-- Fecha -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
              @click="onSort('fecha')"
            >
              <div class="flex items-center space-x-1">
                <span>Fecha</span>
                <svg
                  v-if="sortBy.startsWith('fecha')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'fecha-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- Proveedor -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
              @click="onSort('proveedor')"
            >
              <div class="flex items-center space-x-1">
                <span>Proveedor</span>
                <svg
                  v-if="sortBy.startsWith('proveedor')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'proveedor-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- N° Compra -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
              @click="onSort('numero_compra')"
            >
              <div class="flex items-center space-x-1">
                <span>N° Compra</span>
                <svg
                  v-if="sortBy.startsWith('numero_compra')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'numero_compra-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- Origen -->
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Origen
            </th>

            <!-- Total -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
              @click="onSort('total')"
            >
              <div class="flex items-center space-x-1">
                <span>Total</span>
                <svg
                  v-if="sortBy.startsWith('total')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'total-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- Productos -->
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Productos
            </th>

            <!-- Estado -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
              @click="onSort('estado')"
            >
              <div class="flex items-center space-x-1">
                <span>Estado</span>
                <svg
                  v-if="sortBy.startsWith('estado')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'estado-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- Acciones -->
            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Acciones
            </th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200/40">
          <template v-if="items.length > 0">
            <tr
              v-for="doc in items"
              :key="doc.id"
              :class="[
                'group hover:bg-gray-50/60 transition-all duration-150 hover:shadow-sm',
                doc.estado === 'cancelada' ? 'opacity-50' : ''
              ]"
            >
              <!-- Fecha -->
              <td class="px-6 py-4">
                <div class="flex flex-col space-y-0.5">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatearFecha(doc.fecha) }}
                  </div>
                  <div class="text-[10px] text-gray-400 italic">
                    Reg: {{ formatearFecha(doc.fecha_registro || doc.created_at) }}
                  </div>
                </div>
              </td>

              <!-- Proveedor -->
              <td class="px-6 py-4">
                <div class="flex flex-col space-y-0.5">
                  <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-gray-800">
                    {{ doc.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                  </div>
                  <div v-if="doc.proveedor?.email" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-48">
                    {{ doc.proveedor?.email }}
                  </div>
                </div>
              </td>

              <!-- N° Compra -->
              <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white group-hover:text-gray-800">
                <div class="flex items-center space-x-2">
                  <div class="text-sm font-mono font-medium text-gray-700 bg-gray-100/60 px-2 py-1 rounded-md inline-block">
                    {{ doc.numero_compra || 'N/A' }}
                  </div>
                  <div v-if="doc.cfdi_uuid" class="text-blue-500 flex-shrink-0" title="Compra vinculada a CFDI (XML)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <!-- Indicador de origen de importación -->
                  <span v-if="doc.origen_importacion === 'bulk_import'" class="px-1.5 py-0.5 text-[9px] font-medium bg-purple-100 text-purple-700 rounded" title="Importación automática masiva">
                    AUTO
                  </span>
                </div>
              </td>

              <!-- Origen -->
              <td class="px-6 py-4">
                <span
                  :class="doc.cfdi_uuid ? 'bg-purple-100 text-purple-800' : (doc.origen === 'orden_compra' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800')"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  <svg
                    :class="doc.cfdi_uuid ? 'text-purple-500' : (doc.origen === 'orden_compra' ? 'text-blue-500' : 'text-green-500')"
                    class="w-3 h-3 mr-1"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                     <path v-if="doc.cfdi_uuid" fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                     <path v-else-if="doc.origen === 'orden_compra'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                     <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                  {{ doc.cfdi_uuid ? 'IMPORTADA XML' : (doc.origen === 'orden_compra' ? 'Orden de Compra' : 'Compra Directa') }}
                </span>
              </td>

              <!-- Total -->
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                  <template v-if="typeof doc.total !== 'undefined' && doc.total !== null">
                    ${{ formatearMoneda(doc.total) }}
                  </template>
                  <template v-else>-</template>
                </div>
              </td>

              <!-- Productos -->
              <td
                class="px-6 py-4 relative"
                @mouseenter="getProductosDelDoc(doc)?.length ? showProductTooltip(doc, $event) : null"
                @mouseleave="hideProductTooltip"
                @mousemove="getProductosDelDoc(doc)?.length ? updateTooltipPosition($event) : null"
              >
                <div class="flex items-center text-sm text-gray-600" :class="getProductosDelDoc(doc)?.length ? 'cursor-help hover:text-gray-800 transition-colors duration-150' : 'opacity-60'">
                  <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-2 group-hover:bg-blue-100 transition-colors duration-150">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                      />
                    </svg>
                  </div>
                  <span class="font-medium">{{ getProductosDelDoc(doc)?.length || 0 }}</span>
                  <span class="text-gray-400 ml-1">items</span>
                </div>
              </td>

              <!-- Estado -->
              <td class="px-6 py-4">
                <div class="flex flex-col items-start space-y-1.5">
                  <span
                    :class="obtenerClasesEstado(doc.estado)"
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                  >
                    <span
                      class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                      :class="obtenerColorPuntoEstado(doc.estado)"
                    ></span>
                    {{ obtenerLabelEstado(doc.estado) }}
                  </span>
                  
                  <!-- Indicadores de Pago Simplificado -->
                  <div class="mt-1">
                    <span 
                      v-if="doc.estatus_pago === 'pagado'" 
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 shadow-sm border border-green-200"
                    >
                      <svg class="w-2.5 h-2.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      PAGADO
                    </span>
                    <span 
                      v-else-if="doc.estatus_pago === 'vencido'" 
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 shadow-sm border border-red-200"
                    >
                      VENCIDO
                    </span>
                    <span 
                      v-else
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 shadow-sm border border-gray-200 dark:border-slate-800"
                    >
                      NO PAGADO
                    </span>
                  </div>
                </div>
              </td>

              <!-- Acciones -->
              <td class="px-6 py-4">
                <div class="flex items-center justify-end space-x-1">
                  <button
                    @click="onVerDetalles(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                    title="Ver detalles"
                  >
                    <font-awesome-icon icon="eye" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <button
                    v-if="doc.estado === 'procesada'"
                    @click="onEditar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                    title="Editar compra"
                  >
                    <font-awesome-icon icon="edit" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <button
                    @click="onImprimir(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-1"
                    title="Imprimir"
                  >
                    <font-awesome-icon icon="print" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <button
                    v-if="doc.estado === 'procesada' || doc.estado === 'cancelada'"
                    @click="onEliminar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                    :title="doc.estado === 'procesada' ? 'Cancelar compra' : 'Eliminar compra'"
                  >
                    <font-awesome-icon :icon="doc.estado === 'procesada' ? 'times-circle' : 'trash'" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>
                </div>
              </td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else>
            <td :colspan="8" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center space-y-4">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="space-y-1">
                  <p class="text-gray-700 font-medium">No hay compras</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Las compras aparecerán aquí cuando se creen</p>
                </div>
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
.custom-scrollbar { scrollbar-width: thin; scrollbar-color: #d1d5db #f3f4f6; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }

@media (prefers-contrast: high) {
  .bg-gray-50 { background-color: #f9fafb; }
  .border-gray-200 dark:border-slate-800 { border-color: #d1d5db; }
}

button:focus-visible { outline: 2px solid; outline-offset: 2px; }

@media (hover: none) {
  .hover\:bg-gray-50:hover { background-color: transparent; }
  .group:hover { transform: none; }
}
</style>
