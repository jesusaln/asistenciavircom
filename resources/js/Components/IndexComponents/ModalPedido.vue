<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 outline-none"
        role="dialog"
        aria-modal="true"
        aria-label="Modal de Pedido"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-2">
          <!-- Columna Principal -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Encabezado -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-blue-700 text-white rounded-t-lg">
                <div class="flex justify-between items-start">
                  <div>
                    <h2 class="text-xl font-bold">PEDIDO</h2>
                    <p class="text-indigo-100 text-sm mt-1">{{ numeroPedido }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-indigo-100">Fecha</p>
                    <p class="font-semibold">{{ formatearFecha(selected?.fecha || selected?.created_at) }}</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="grid grid-cols-2 gap-6">
                  <!-- Cliente -->
                  <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cliente</h3>
                    <p class="text-base font-bold text-gray-900">{{ selected?.cliente?.nombre_razon_social || 'Desconocido' }}</p>
                    <div class="mt-2 space-y-1 text-sm text-gray-600">
                      <p v-if="selected?.cliente?.rfc && selected.cliente.rfc !== 'N/A'"><span class="font-medium">RFC:</span> {{ selected.cliente.rfc }}</p>
                      <p v-if="selected?.cliente?.email && selected.cliente.email !== 'N/A'"><span class="font-medium">Email:</span> {{ selected.cliente.email }}</p>
                      <p v-if="selected?.cliente?.telefono && selected.cliente.telefono !== 'N/A'"><span class="font-medium">Tel:</span> {{ selected.cliente.telefono }}</p>
                    </div>
                  </div>

                  <!-- Información -->
                  <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Información</h3>
                    <div class="space-y-2 text-sm">
                      <div v-if="selected?.almacen" class="space-y-1">
                        <div class="flex items-center">
                          <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                          </svg>
                          <span class="text-gray-600">Almacén:</span>
                          <span class="ml-2 font-medium text-gray-900">{{ selected.almacen?.nombre }}</span>
                        </div>
                        <div v-if="selected.almacen?.descripcion" class="ml-6 text-sm text-gray-500">{{ selected.almacen.descripcion }}</div>
                        <div v-if="selected.almacen?.ubicacion" class="ml-6 text-sm text-gray-500">Ubicación: {{ selected.almacen.ubicacion }}</div>
                      </div>
                      <div v-if="selected?.vendedor" class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-gray-600">Vendedor:</span>
                        <span class="ml-2 font-medium text-gray-900">{{ selected.vendedor?.nombre }}</span>
                      </div>
                      <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="text-gray-600">Estado:</span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="estadoClase">{{ estadoLabel }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="selected?.notas" class="px-6 py-3 bg-yellow-50 border-b border-yellow-100">
                <div class="flex items-start">
                  <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                  </svg>
                  <div>
                    <p class="text-xs font-semibold text-yellow-800 uppercase">Notas</p>
                    <p class="text-sm text-yellow-900 mt-1">{{ selected.notas }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Productos -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex justify-between items-center">
                  <h2 class="text-lg font-semibold text-gray-900">Productos y Servicios</h2>
                  <span class="text-sm text-gray-500">{{ itemsCalculados.length + (productoPrincipal ? 1 : 0) }} items</span>
                </div>
              </div>

              <div v-if="itemsCalculados.length > 0 || productoPrincipal" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto/Servicio</th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desc.</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="producto in productoPrincipal ? [productoPrincipal] : itemsCalculados" :key="producto.id">
                      <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                          <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg" :class="producto.tipo === 'producto' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700'">
                              <svg v-if="producto.tipo === 'producto'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                              </svg>
                              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                              </svg>
                            </div>
                            <div class="ml-4">
                              <div class="text-sm font-medium text-gray-900">{{ producto.nombre }}</div>
                              <div class="flex items-center mt-1 space-x-2">
                                <span :class="producto.tipo === 'producto' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize">{{ producto.tipo }}</span>
                                <span v-if="producto.requiere_serie && producto.series && producto.series.length > 0" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                  </svg>
                                  {{ producto.series.length }} serie(s)
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                          <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 text-sm font-semibold text-gray-900">{{ producto.pivot.cantidad }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">{{ formatCurrency(producto.pivot.precio) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                          <span v-if="producto.pivot.descuento > 0" class="text-red-600 font-medium">{{ producto.pivot.descuento }}%</span>
                          <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">{{ formatCurrency(producto.pivot.subtotal) }}</td>
                      </tr>
                      <tr v-if="producto.requiere_serie && producto.series && producto.series.length > 0" class="bg-blue-50">
                        <td colspan="5" class="px-6 py-3">
                          <div class="text-xs font-medium text-blue-900 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            Series:
                          </div>
                          <div class="flex flex-wrap gap-2">
                            <div v-for="(serie, idx) in producto.series" :key="idx" class="inline-flex items-center bg-white border border-blue-200 rounded-md px-3 py-1.5 shadow-sm">
                              <span class="text-sm font-mono font-semibold text-gray-900">{{ serie.numero_serie }}</span>
                              <span class="ml-2 text-xs text-gray-500">Almacén {{ serie.almacen || 'N/D' }}</span>
                            </div>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              <div v-else class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay productos asociados</h3>
                <p class="mt-1 text-sm text-gray-500">Este pedido no tiene productos o servicios registrados.</p>
              </div>
            </div>
          </div>

          <!-- Columna Lateral -->
          <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Resumen</h2>
              </div>
              <div class="px-6 py-4 space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotal</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(selected?.subtotal || calcularSubtotal()) }}</span>
                </div>
                <div v-if="selected?.descuento_general > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">Descuento General</span>
                  <span class="font-medium text-red-600">-{{ formatCurrency(selected.descuento_general) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">IVA</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(selected?.iva || 0) }}</span>
                </div>
                <div class="pt-3 border-t-2 border-gray-300">
                  <div class="flex justify-between items-center">
                    <span class="text-base font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-amber-600">{{ formatCurrency(selected?.total || calcularTotal()) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-lg border" :class="estadoClaseBg">
              <div class="flex items-center mb-3 px-4 pt-4">
                <span class="h-2 w-2 rounded-full mr-2" :class="estadoPunto"></span>
                <h3 class="text-sm font-semibold" :class="estadoTexto">Estado: {{ estadoLabel }}</h3>
              </div>
              <div class="px-4 pb-4 text-sm text-gray-700">
                <p v-if="selected?.estado_detalle">{{ selected.estado_detalle }}</p>
                <p v-else>Revisa el pedido antes de enviarlo a venta.</p>
              </div>
            </div>

            <div v-if="auditoriaSafe" class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Auditoría</h2>
              </div>
              <div class="px-6 py-4 space-y-3 text-sm">
                <div>
                  <dt class="text-xs font-medium text-gray-500">Creado por</dt>
                  <dd class="text-gray-900 font-medium">{{ auditoriaSafe.creado_por || 'N/A' }}</dd>
                  <dd class="text-xs text-gray-500">{{ formatearFechaHora(auditoriaSafe.creado_en || selected?.created_at) }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-medium text-gray-500">Última actualización</dt>
                  <dd class="text-gray-900 font-medium">{{ auditoriaSafe.actualizado_por || 'N/A' }}</dd>
                  <dd class="text-xs text-gray-500">{{ formatearFechaHora(auditoriaSafe.actualizado_en || selected?.updated_at) }}</dd>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-2">
              <button @click="verPdfEnNavegador(selected?.id)" 
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Ver PDF
              </button>
              <button @click="descargarPdf(selected?.id, numeroPedido)" 
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar PDF
              </button>
              <button @click="$emit('editar', selected?.id)" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Pedido
              </button>
              <button @click="$emit('duplicar', selected)" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V7M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
                Duplicar Pedido
              </button>
              <button v-if="selected?.estado !== 'cancelado'" @click="$emit('enviar-a-venta', selected)" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Enviar a Venta
              </button>
              <button v-if="selected?.estado !== 'cancelado'" @click="$emit('cancelar', selected.id)" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Cancelar Pedido
              </button>
              <button v-else @click="$emit('eliminar', selected.id)" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Eliminar Pedido
              </button>
              <button @click="onClose" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition-colors">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null },
  auditoria: { type: Object, default: null }
})

const emit = defineEmits(['close', 'enviar-a-venta', 'cancelar', 'eliminar', 'editar', 'duplicar', 'imprimir'])

const modalRef = ref(null)
const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

const onClose = () => emit('close')
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const numeroPedido = computed(() => props.selected?.numero_pedido || `P${String(props.selected?.id || '').padStart(3, '0')}`)

const estadoLabel = computed(() => {
  const map = {
    borrador: 'Borrador',
    pendiente: 'Pendiente',
    confirmado: 'Confirmado',
    en_preparacion: 'En preparación',
    listo_entrega: 'Listo para entrega',
    entregado: 'Entregado',
    enviado_venta: 'Enviado a venta',
    cancelado: 'Cancelado'
  }
  return map[props.selected?.estado] || props.selected?.estado || 'Sin estado'
})

const estadoClase = computed(() => {
  const map = {
    borrador: 'bg-gray-100 text-gray-800',
    pendiente: 'bg-yellow-100 text-yellow-800',
    confirmado: 'bg-blue-100 text-blue-800',
    en_preparacion: 'bg-orange-100 text-orange-800',
    listo_entrega: 'bg-purple-100 text-purple-800',
    entregado: 'bg-green-100 text-green-800',
    enviado_venta: 'bg-indigo-100 text-indigo-800',
    cancelado: 'bg-red-100 text-red-800'
  }
  return map[props.selected?.estado] || 'bg-gray-100 text-gray-800'
})

const estadoClaseBg = computed(() => {
  const map = {
    cancelado: 'bg-red-50 border-red-200',
    entregado: 'bg-green-50 border-green-200',
    confirmado: 'bg-blue-50 border-blue-200',
    pendiente: 'bg-yellow-50 border-yellow-200',
    en_preparacion: 'bg-orange-50 border-orange-200',
    listo_entrega: 'bg-purple-50 border-purple-200',
    enviado_venta: 'bg-indigo-50 border-indigo-200'
  }
  return map[props.selected?.estado] || 'bg-gray-50 border-gray-200'
})

const estadoPunto = computed(() => {
  const map = {
    cancelado: 'bg-red-500',
    entregado: 'bg-green-500',
    confirmado: 'bg-blue-500',
    pendiente: 'bg-yellow-500',
    en_preparacion: 'bg-orange-500',
    listo_entrega: 'bg-purple-500',
    enviado_venta: 'bg-amber-500'
  }
  return map[props.selected?.estado] || 'bg-gray-400'
})

const estadoTexto = computed(() => {
  const map = {
    cancelado: 'text-red-800',
    entregado: 'text-green-800',
    confirmado: 'text-blue-800',
    pendiente: 'text-yellow-800',
    en_preparacion: 'text-orange-800',
    listo_entrega: 'text-purple-800',
    enviado_venta: 'text-indigo-800'
  }
  return map[props.selected?.estado] || 'text-gray-800'
})

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const t = new Date(date).getTime()
    if (Number.isNaN(t)) return 'Fecha inválida'
    return new Date(t).toLocaleDateString('es-MX', {
      year: 'numeric', month: 'long', day: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  } catch { return 'Fecha inválida' }
}

const formatearFechaHora = formatearFecha

const formatCurrency = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

const itemsCalculados = computed(() => {
  const lista = Array.isArray(props.selected?.items) ? props.selected.items : (Array.isArray(props.selected?.productos) ? props.selected.productos : [])
  return lista.map((item) => {
    const cantidad = parseFloat(item.cantidad || 1)
    const precio = parseFloat(item.precio || item.precio_unitario || 0)
    const descuento = parseFloat(item.descuento || 0)
    const subtotalBase = precio * (cantidad || 1)
    const subtotal = subtotalBase - (subtotalBase * (descuento / 100))
    return {
      ...item,
      pivot: {
        cantidad: cantidad || 1,
        precio,
        descuento,
        subtotal,
      },
      requiere_serie: item.requiere_serie || false,
      series: item.series || [],
      tipo: item.tipo || 'producto',
      nombre: item.nombre || item.producto_nombre || 'Producto/Servicio',
    }
  })
})

const productoPrincipal = computed(() => {
  const s = props.selected || {}
  if ((Array.isArray(s.productos) && s.productos.length > 0) || (Array.isArray(s.items) && s.items.length > 0)) return null
  const nombre = s.producto_nombre || s.producto || s.concepto || null
  if (!nombre) return null
  const cantidad = parseFloat(s.cantidad || 1)
  const precio = parseFloat(s.precio_unitario || s.precio || s.total || 0)
  const subtotal = precio * (cantidad || 1)
  return {
    id: `pedido-${s.id || 'sin-id'}`,
    nombre,
    tipo: 'producto',
    pivot: {
      cantidad: cantidad || 1,
      precio,
      descuento: 0,
      subtotal,
    },
    requiere_serie: false,
    series: [],
  }
})

// ✅ FIX: Funciones para manejar PDF con JavaScript (bypassa problemas de IIS headers) - Igual que ModalCotizacion
const verPdfEnNavegador = async (pedidoId) => {
  try {
    const response = await fetch(route('pedidos.pdf', pedidoId) + '?modo=inline', {
      method: 'GET',
      headers: {
        'Accept': 'application/pdf'
      }
    })
    
    if (!response.ok) throw new Error('Error al obtener PDF')
    
    const blob = await response.blob()
    // Crear blob con tipo correcto
    const pdfBlob = new Blob([blob], { type: 'application/pdf' })
    const url = URL.createObjectURL(pdfBlob)
    
    // Abrir en nueva pestaña
    window.open(url, '_blank')
    
    // Liberar URL después de un tiempo
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (error) {
    console.error('Error al ver PDF:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error al generar el PDF',
      text: 'No se pudo generar el PDF. Por favor, inténtelo de nuevo.'
    })
  }
}

const descargarPdf = async (pedidoId, numeroPedido) => {
  try {
    const response = await fetch(route('pedidos.pdf', pedidoId) + '?modo=download', {
      method: 'GET',
      headers: {
        'Accept': 'application/pdf'
      }
    })
    
    if (!response.ok) throw new Error('Error al obtener PDF')
    
    const blob = await response.blob()
    const pdfBlob = new Blob([blob], { type: 'application/pdf' })
    const url = URL.createObjectURL(pdfBlob)
    
    // Crear enlace de descarga
    const link = document.createElement('a')
    link.href = url
    link.download = `pedido-${numeroPedido || pedidoId}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error al descargar PDF:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error al descargar el PDF',
      text: 'No se pudo descargar el PDF. Por favor, inténtelo de nuevo.'
    })
  }
}


const calcularSubtotal = () => {
  if (itemsCalculados.value.length === 0) return 0
  return itemsCalculados.value.reduce((sum, item) => sum + (item.pivot?.subtotal || 0), 0)
}

const calcularIVA = () => {
  return parseFloat(props.selected?.iva || 0)
}

const calcularTotal = () => {
  const subtotal = calcularSubtotal()
  const descuento = parseFloat(props.selected?.descuento_general || 0)
  const iva = calcularIVA()
  return subtotal - descuento + iva
}

const auditoriaSafe = computed(() => {
  if (props.auditoria) return props.auditoria
  if (props.selected) {
    return {
      creado_por: props.selected.created_by_user_name || props.selected.creado_por_nombre || 'N/A',
      actualizado_por: props.selected.updated_by_user_name || props.selected.actualizado_por_nombre || 'N/A',
      creado_en: props.selected.created_at,
      actualizado_en: props.selected.updated_at,
    }
  }
  return null
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; transform: scale(0.97); }
.modal-enter-to,
.modal-leave-from { opacity: 1; transform: scale(1); }
</style>

