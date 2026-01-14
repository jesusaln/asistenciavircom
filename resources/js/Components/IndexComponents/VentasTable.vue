<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-200/60">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 tracking-tight">Ventas</h2>
        <div class="text-sm text-gray-600 bg-white/70 px-3 py-1 rounded-full border border-gray-200/50">
          {{ items.length }} de {{ total }} ventas
        </div>
      </div>
    </div>

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

            <!-- Cliente -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
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

            <!-- N° Venta -->
            <th
              class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 transition-colors duration-150"
              @click="onSort('numero_venta')"
            >
              <div class="flex items-center space-x-1">
                <span>N° Venta</span>
                <svg
                  v-if="sortBy.startsWith('numero_venta')"
                  :class="['w-4 h-4 transition-transform duration-200', sortBy === 'numero_venta-desc' ? 'rotate-180' : '']"
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

            <!-- Pago -->
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Pago
            </th>

            <!-- Estado Factura -->
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Estado
            </th>

            <!-- Acciones -->
            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Acciones
            </th>
          </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200/40">
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
                  <div class="text-sm font-medium text-gray-900">
                    {{ formatearFecha(doc.created_at || doc.fecha) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ formatearHora(doc.created_at || doc.fecha) }}
                  </div>
                </div>
              </td>

              <!-- Cliente -->
              <td class="px-6 py-4">
                <div class="flex flex-col space-y-0.5">
                  <div class="text-sm font-medium text-gray-900 group-hover:text-gray-800">
                    {{ doc.cliente?.nombre_razon_social || 'Sin cliente' }}
                  </div>
                  <div v-if="doc.cliente?.email" class="text-xs text-gray-500 truncate max-w-48">
                    {{ doc.cliente.email }}
                  </div>
                </div>
              </td>

              <!-- N° Venta -->
              <td class="px-6 py-4">
                <div class="text-sm font-mono font-medium text-gray-700 bg-gray-100/60 px-2 py-1 rounded-md inline-block">
                  {{ doc.numero_venta || doc.id || 'N/A' }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900">
                  <template v-if="typeof doc.total !== 'undefined' && doc.total !== null">
                    ${{ formatearMoneda(doc.total) }}
                  </template>
                  <template v-else>-</template>
                </div>
              </td>

              <!-- Pago -->
              <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                      doc.pagado ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                    ]"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full mr-1.5"
                      :class="doc.pagado ? 'bg-green-400' : 'bg-gray-400'"
                    ></span>
                    {{ doc.pagado ? 'Pagado' : 'Pendiente' }}
                  </span>
                  <span v-if="doc.pagado && doc.metodo_pago" class="text-xs text-gray-500">
                    ({{ doc.metodo_pago }})
                  </span>
                </div>
              </td>

              <!-- Estado Factura -->
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <!-- Cancelada -->
                  <span
                    v-if="doc.cfdi_cancelado || (doc.cfdi_actual && doc.cfdi_actual.estatus === 'cancelado')"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-red-400"></span>
                    Cancelada
                  </span>
                  <!-- Facturada -->
                  <a
                    v-else-if="doc.esta_facturada && doc.factura_uuid"
                    :href="route('cfdi.ver-pdf-view', doc.factura_uuid)"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200 transition-colors"
                    title="Ver CFDI"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-green-400"></span>
                    Facturada
                  </a>
                  <span
                    v-else-if="doc.esta_facturada"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-green-400"></span>
                    Facturada
                  </span>
                  <!-- No Facturada -->
                  <span 
                    v-else 
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-gray-400"></span>
                    No Facturada
                  </span>
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
                    v-if="doc.estado !== 'cancelada'"
                    @click="onEditar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                    title="Editar"
                  >
                    <font-awesome-icon icon="edit" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <!-- Marcar como Pagado (solo si no está pagado) -->
                  <button
                    v-if="!doc.pagado && doc.estado !== 'cancelada'"
                    @click="onMarcarPagado(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
                    title="Marcar como Pagado"
                  >
                    <font-awesome-icon icon="dollar-sign" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <button
                    v-if="doc.estado !== 'cancelada' && doc.cliente?.email"
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
                    v-if="doc.estado !== 'cancelada'"
                    @click="onImprimir(doc)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-1"
                    title="Imprimir"
                  >
                    <font-awesome-icon icon="print" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <!-- Cancelar Venta -->
                  <button
                    v-if="doc.estado !== 'cancelada'"
                    @click="onCancelar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 hover:text-orange-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:ring-offset-1"
                    title="Cancelar Venta"
                  >
                    <font-awesome-icon icon="times-circle" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <!-- Eliminar Venta (solo si está cancelada Y NO tiene CFDI) -->
                  <button
                    v-if="(doc.estado === 'cancelada' || doc.estado?.value === 'cancelada') && !doc.esta_facturada && !doc.cfdi_cancelado"
                    @click="onEliminar(doc.id)"
                    class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                    title="Eliminar Venta"
                  >
                    <font-awesome-icon icon="times" class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" />
                  </button>

                  <!-- Indicador de que no se puede eliminar (tiene CFDI) -->
                  <span
                    v-if="(doc.estado === 'cancelada' || doc.estado?.value === 'cancelada') && (doc.esta_facturada || doc.cfdi_cancelado)"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed"
                    title="No se puede eliminar: CFDI registrado ante el SAT"
                  >
                    <font-awesome-icon icon="lock" class="w-4 h-4" />
                  </span>

                </div>
              </td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else>
            <td :colspan="6" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center space-y-4">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="space-y-1">
                  <p class="text-gray-700 font-medium">No hay ventas</p>
                  <p class="text-sm text-gray-500">Las ventas aparecerán aquí cuando se creen</p>
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
  'ver-detalles','editar','eliminar','imprimir','sort','marcar-pagado','cancelar','enviar-email'
])

// Estados de ventas - lógica de negocio actualizada
const estadosConfig = {
  'borrador': { label: 'Borrador', classes: 'bg-gray-100 text-gray-700', color: 'bg-gray-400' },
  'pendiente': { label: 'Pendiente', classes: 'bg-yellow-100 text-yellow-700', color: 'bg-yellow-400' },
  'aprobado': { label: 'Aprobado', classes: 'bg-blue-100 text-blue-700', color: 'bg-blue-400' },
  'aprobada': { label: 'Aprobada', classes: 'bg-blue-100 text-blue-700', color: 'bg-blue-400' },
  'vendido': { label: 'Vendido', classes: 'bg-green-100 text-green-700', color: 'bg-green-400' },
  'cancelado': { label: 'Cancelado', classes: 'bg-red-100 text-red-700', color: 'bg-red-400' },
  'cancelada': { label: 'Cancelada', classes: 'bg-red-100 text-red-700', color: 'bg-red-400' }
}

// Función para determinar el estado correcto basado en el pago
const determinarEstadoCorrecto = (venta) => {
  // Si está cancelada, mantener ese estado
  if (venta.estado === 'cancelado' || venta.estado === 'cancelada') {
    return venta.estado
  }

  // Si está pagada, el estado debería ser "aprobada" o "vendido"
  if (venta.pagado) {
    // Si ya tiene estado "aprobada" o "vendido", mantenerlo
    if (venta.estado === 'aprobada' || venta.estado === 'aprobado' || venta.estado === 'vendido') {
      return venta.estado
    }
    // Si está pagada pero tiene otro estado, cambiar a "aprobada"
    return 'aprobada'
  }

  // Si no está pagada, mantener el estado original
  return venta.estado || 'pendiente'
}

const obtenerClasesEstado = (venta) => {
  const estadoCorrecto = determinarEstadoCorrecto(venta)
  return estadosConfig[estadoCorrecto]?.classes || 'bg-gray-100 text-gray-700'
}

const esCancelada = (venta) => {
  const estadoCorrecto = determinarEstadoCorrecto(venta)
  return estadoCorrecto === 'cancelada' || estadoCorrecto === 'cancelado'
}

const obtenerColorPuntoEstado = (venta) => {
  const estadoCorrecto = determinarEstadoCorrecto(venta)
  return estadosConfig[estadoCorrecto]?.color || 'bg-gray-400'
}

const obtenerLabelEstado = (venta) => {
  const estadoCorrecto = determinarEstadoCorrecto(venta)
  return estadosConfig[estadoCorrecto]?.label || 'Pendiente'
}

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
      const estadoCorrecto = determinarEstadoCorrecto(doc)
      return (
        (doc.cliente?.nombre_razon_social || '').toLowerCase().includes(term) ||
        (doc.numero_venta || doc.id || '').toString().toLowerCase().includes(term) ||
        estadoCorrecto.toLowerCase().includes(term)
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
        aVal = (a.cliente?.nombre_razon_social || '').toLowerCase()
        bVal = (b.cliente?.nombre_razon_social || '').toLowerCase()
        break
      case 'numero_venta':
        aVal = (a.numero_venta || a.id || '').toString().toLowerCase()
        bVal = (b.numero_venta || b.id || '').toString().toLowerCase()
        break
      case 'total':
        aVal = parseFloat(a.total || 0)
        bVal = parseFloat(b.total || 0)
        break
      case 'estado':
        aVal = determinarEstadoCorrecto(a).toLowerCase()
        bVal = determinarEstadoCorrecto(b).toLowerCase()
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
const onMarcarPagado = (doc) => emit('marcar-pagado', doc)
const onCancelar = (id) => emit('cancelar', id)
const onEnviarEmail = (doc) => emit('enviar-email', doc)

const onSort = (field) => {
  const current = props.sortBy.startsWith(field) ? props.sortBy : `${field}-desc`
  const newOrder = current === `${field}-desc` ? `${field}-asc` : `${field}-desc`
  emit('sort', newOrder)
}
</script>

<style scoped>
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
