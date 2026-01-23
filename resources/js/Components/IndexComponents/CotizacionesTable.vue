<template>
  <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 dark:border-gray-700 overflow-hidden transition-colors">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-800 dark:to-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-slate-800/60 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white tracking-tight">Cotizaciones</h2>
        <div class="text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-slate-900/70 dark:bg-gray-700/70 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-800/50 dark:border-gray-600/50">
          {{ items.length }} de {{ total }} cotizaciones
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200/60 dark:divide-gray-700">
        <thead class="bg-gray-50/60 dark:bg-gray-900/60">
          <tr>
            <!-- Fecha -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700/60 transition-colors duration-150"
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

            <!-- Cliente -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700/60 transition-colors duration-150"
              @click="onSort('cliente')"
            >
              <div class="flex items-center space-x-1">
                <span>Cliente</span>
                <svg
                  v-if="sortBy.startsWith('cliente')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'cliente-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- N° Cotización -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700/60 transition-colors duration-150"
              @click="onSort('numero_cotizacion')"
            >
              <div class="flex items-center space-x-1">
                <span>N° Cotización</span>
                <svg
                  v-if="sortBy.startsWith('numero_cotizacion')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'numero_cotizacion-desc' ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </th>

            <!-- Total -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700/60 transition-colors duration-150"
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

            <!-- Estado -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700/60 transition-colors duration-150"
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
            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
              Acciones
            </th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200/40 dark:divide-gray-700/40">
          <template v-if="items.length > 0">
            <tr
              v-for="doc in items"
              :key="doc.id"
              :class="[
                'group hover:bg-gray-50/60 dark:hover:bg-gray-700/60 transition-all duration-150 hover:shadow-sm',
                doc.estado === 'cancelado' ? 'opacity-50' : ''
              ]"
            >
              <!-- Fecha -->
              <td class="px-6 py-4">
                <div class="flex flex-col space-y-0.5">
                  <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                    {{ formatearFecha(doc.created_at || doc.fecha) }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    {{ formatearHora(doc.created_at || doc.fecha) }}
                  </div>
                </div>
              </td>

              <!-- Cliente -->
              <td class="px-6 py-4">
                <div class="flex flex-col space-y-0.5">
                  <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white group-hover:text-gray-800 dark:group-hover:text-gray-100">
                    {{ doc.cliente?.nombre || 'Sin cliente' }}
                  </div>
                  <div v-if="doc.cliente?.email" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate max-w-48">
                    {{ doc.cliente.email }}
                  </div>
                </div>
              </td>

              <!-- N° Cotización -->
              <td class="px-6 py-4">
                <div class="text-sm font-mono font-medium text-gray-700 dark:text-gray-300 bg-gray-100/60 dark:bg-gray-700/60 px-2 py-1 rounded-md inline-block">
                  {{ doc.numero_cotizacion_display || doc.numero_cotizacion || doc.id || 'N/A' }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900 dark:text-white dark:text-white">
                  <template v-if="typeof doc.total !== 'undefined' && doc.total !== null">
                    ${{ formatearMoneda(doc.total) }}
                  </template>
                  <template v-else>-</template>
                </div>
              </td>

              <!-- Estado -->
              <td class="px-6 py-4">
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
                    v-if="doc.estado !== 'cancelado' && doc.estado !== 'enviado_pedido'"
                    @click="onEditar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                    title="Editar"
                  >
                    <font-awesome-icon icon="edit" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <!-- Enviar a Pedido (solo cotizaciones no enviadas a pedido) -->
                  <button
                    v-if="doc.estado !== 'cancelado' && doc.estado !== 'enviado_pedido'"
                    @click="onEnviarPedido(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-50 text-amber-600 hover:bg-indigo-100 hover:text-indigo-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                    title="Enviar a Pedido"
                  >
                    <font-awesome-icon icon="paper-plane" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <button
                    v-if="doc.estado !== 'cancelado' && doc.cliente?.email"
                    @click="onEnviarEmail(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
                    :title="doc.email_enviado ? 'Reenviar por Email' : 'Enviar por Email'"
                  >
                    <font-awesome-icon
                      :icon="doc.email_enviado ? 'envelope-open' : 'envelope'"
                      class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110"
                    />
                  </button>

                  <button
                    v-if="doc.estado !== 'cancelado'"
                    @click="onImprimir(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-1"
                    title="Imprimir"
                  >
                    <font-awesome-icon icon="print" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <button
                    v-if="doc.estado !== 'cancelada'"
                    @click="onEliminar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                    title="Eliminar"
                  >
                    <font-awesome-icon icon="trash" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>
                </div>
              </td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else>
            <td :colspan="6" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center space-y-4">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="space-y-1">
                  <p class="text-gray-700 dark:text-gray-300 font-medium">No hay cotizaciones</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Las cotizaciones aparecerán aquí cuando se creen</p>
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
import { computed } from 'vue'

const props = defineProps({
  documentos: { type: Array, default: () => [] },
  searchTerm: { type: String, default: '' },
  sortBy: { type: String, default: 'fecha-desc' }
})

const emit = defineEmits([
  'ver-detalles','editar','eliminar','imprimir','sort','enviar-pedido','enviar-email'
])

// Estados de cotizaciones
const estadosConfig = {
  'borrador': { label: 'Borrador', classes: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300', color: 'bg-yellow-400 dark:bg-yellow-500' },
  'pendiente': { label: 'Pendiente', classes: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300', color: 'bg-gray-400 dark:bg-gray-500' },
  'aprobada': { label: 'Aprobada', classes: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', color: 'bg-blue-400 dark:bg-blue-500' },
  'rechazada': { label: 'Rechazada', classes: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300', color: 'bg-red-400 dark:bg-red-500' },
  'enviada': { label: 'Enviada', classes: 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', color: 'bg-purple-400 dark:bg-purple-500' },
  'enviado_pedido': { label: 'Enviado a Pedido', classes: 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300', color: 'bg-orange-400 dark:bg-orange-500' },
  'convertida_pedido': { label: 'Convertida a Pedido', classes: 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', color: 'bg-green-400 dark:bg-green-500' },
  'cancelado': { label: 'Cancelado', classes: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300', color: 'bg-red-400 dark:bg-red-500' },
  'sin_estado': { label: 'Sin Estado', classes: 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 dark:text-gray-400', color: 'bg-gray-400 dark:bg-gray-500' }
}

const obtenerClasesEstado = (estado) => estadosConfig[estado]?.classes || 'bg-gray-100 text-gray-700'
const obtenerColorPuntoEstado = (estado) => estadosConfig[estado]?.color || 'bg-gray-400'
const obtenerLabelEstado = (estado) => estadosConfig[estado]?.label || 'Pendiente'

// Cache de formatos
const formatCache = new Map()

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  const cacheKey = `fecha-${date}`
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey)
  try {
    const time = new Date(date).getTime()
    if (Number.isNaN(time)) return 'Fecha inválida'
    const formatted = new Date(time).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
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
  if (!Array.isArray(props.documentos)) return []

  let filtered = props.documentos.slice()

  if (props.searchTerm) {
    const term = props.searchTerm.toLowerCase()
    filtered = filtered.filter(doc => {
      return (
        (doc.cliente?.nombre || '').toLowerCase().includes(term) ||
        (doc.numero_cotizacion || doc.id || '').toString().toLowerCase().includes(term) ||
        (doc.estado || '').toLowerCase().includes(term)
      )
    })
  }

  const [field, direction] = props.sortBy.split('-')

  return filtered.sort((a, b) => {
    let aVal, bVal

    switch (field) {
      case 'fecha':
        aVal = new Date(a.created_at || a.fecha || 0).getTime()
        bVal = new Date(b.created_at || b.fecha || 0).getTime()
        break
      case 'cliente':
        aVal = (a.cliente?.nombre || '').toLowerCase()
        bVal = (b.cliente?.nombre || '').toLowerCase()
        break
      case 'numero_cotizacion':
        aVal = (a.numero_cotizacion || a.id || '').toString().toLowerCase()
        bVal = (b.numero_cotizacion || b.id || '').toString().toLowerCase()
        break
      case 'total':
        aVal = parseFloat(a.total || 0)
        bVal = parseFloat(b.total || 0)
        break
      case 'estado':
        aVal = (a.estado || '').toLowerCase()
        bVal = (b.estado || '').toLowerCase()
        break
      default:
        aVal = (a[field] || '').toLowerCase()
        bVal = (b[field] || '').toLowerCase()
    }

    const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0
    return direction === 'desc' ? -comparison : comparison
  })
})

const total = computed(() => props.documentos?.length || 0)

// Emits helpers
const onVerDetalles = (doc) => emit('ver-detalles', doc)
const onEditar = (id) => emit('editar', id)
const onEliminar = (id) => emit('eliminar', id)
const onImprimir = (doc) => emit('imprimir', doc)
const onEnviarPedido = (doc) => emit('enviar-pedido', doc)
const onEnviarEmail = (doc) => emit('enviar-email', doc)

const onSort = (field) => {
  const current = props.sortBy.startsWith(field) ? props.sortBy : `${field}-desc`
  const newOrder = current === `${field}-desc` ? `${field}-asc` : `${field}-desc`
  emit('sort', newOrder)
}
</script>

<style scoped>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }

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

