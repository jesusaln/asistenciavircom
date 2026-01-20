<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })
const { colors, cssVars } = useCompanyColors()

const props = defineProps({
  factura: Object,
  cfdi: Object
})

const formatearMoneda = (monto) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(monto)
}

const getStatusClasses = (status) => {
  const classes = {
    borrador: 'bg-slate-700/50 text-slate-300 ring-1 ring-inset ring-slate-600/20',
    enviada: 'bg-indigo-400/10 text-indigo-400 ring-1 ring-inset ring-indigo-400/20',
    pagada: 'bg-emerald-400/10 text-emerald-400 ring-1 ring-inset ring-emerald-400/20',
    vencida: 'bg-rose-400/10 text-rose-400 ring-1 ring-inset ring-rose-400/20',
    cancelada: 'bg-pink-400/10 text-pink-400 ring-1 ring-inset ring-pink-400/20',
    anulada: 'bg-gray-400/10 text-gray-400 ring-1 ring-inset ring-gray-400/20',
  }
  return classes[status] || 'bg-slate-700/50 text-slate-300 ring-1 ring-inset ring-slate-600/20'
}

// Cancelar
const showCancelModal = ref(false)
const motivoCancelacion = ref('02')
const uuidSustitucion = ref('')
const processingCancel = ref(false)

const cancelarFactura = () => {
  if (!confirm('¿Estás seguro de cancelar esta factura? Esta acción no se puede deshacer.')) return

  processingCancel.value = true
  router.post(route('facturas.cancelar', props.factura.id), {
    motivo: motivoCancelacion.value,
    uuid_sustitucion: uuidSustitucion.value
  }, {
    onFinish: () => {
      processingCancel.value = false
      showCancelModal.value = false
    }
  })
}

// Timbrar (retry)
const timbrarFactura = () => {
    router.post(route('facturas.timbrar', props.factura.id))
}
</script>

<template>
  <div :style="cssVars" class="min-h-screen bg-slate-900 font-sans text-slate-300">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-8">
            <Link :href="route('facturas.index')" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium flex items-center mb-2 transition-colors">
                &larr; Volver a Facturas
            </Link>
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-3xl font-bold text-white sm:truncate">
                        Factura <span class="text-indigo-500">{{ factura.numero_factura || 'Borrador #' + factura.id }}</span>
                    </h2>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-4">
                        <div class="flex items-center text-sm text-slate-400 mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                            :class="getStatusClasses(factura.estado)">
                            {{ factura.estado }}
                            </span>
                        </div>
                        <div class="flex items-center text-sm text-slate-400 mt-1">
                            <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Emitida el {{ new Date(factura.fecha_emision).toLocaleDateString() }}
                        </div>
                         <div class="flex items-center text-sm text-slate-400 mt-1" v-if="cfdi && cfdi.uuid">
                            <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ cfdi.uuid }}
                        </div>
                    </div>
                </div>
                <!-- Actions -->
                <div class="mt-5 flex lg:mt-0 lg:ml-4 space-x-3">
                    <a v-if="factura.estado !== 'borrador' && factura.estado !== 'cancelada'" 
                    :href="route('facturas.pdf', factura.id)" 
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 border border-slate-700 rounded-xl shadow-sm text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition-all">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-slate-400 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar PDF
                    </a>

                    <button v-if="factura.estado === 'borrador'" 
                        @click="timbrarFactura"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 transition-all transform hover:scale-105">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Timbrar Ahora
                    </button>

                    <button v-if="factura.estado === 'enviada' || factura.estado === 'pagada'" 
                        @click="showCancelModal = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-rose-600/90 hover:bg-rose-500 transition-all">
                        Cancelar Factura
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Left Column: Items -->
            <div class="md:col-span-2 space-y-8">
                <!-- Conceptos Table -->
                <div class="bg-slate-800 shadow-xl border border-slate-700/50 rounded-2xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-700/50 flex justify-between items-center bg-slate-900/30">
                        <h3 class="text-lg font-medium text-white">Conceptos Facturados</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-700/50">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Cant</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Concepto</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">P. Unitario</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Importe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-800">
                                <template v-for="venta in factura.ventas" :key="venta.id">
                                    <tr class="bg-slate-900/20">
                                        <td colspan="4" class="px-6 py-2 text-xs font-bold text-indigo-400/80 border-b border-slate-700/30">
                                            VENTA #{{ venta.folio || venta.id }} - {{ new Date(venta.fecha).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                    <tr v-for="item in venta.items" :key="item.id" class="hover:bg-slate-700/20">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ item.cantidad }}</td>
                                        <td class="px-6 py-4 text-sm text-white">
                                            {{ item.ventable?.nombre || 'Producto eliminado' }}
                                            <div class="text-xs text-slate-500 mt-0.5">{{ item.ventable?.codigo }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-400">{{ formatearMoneda(item.precio) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-emerald-400 font-medium">{{ formatearMoneda(item.cantidad * item.precio) }}</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Timbre Fiscal info -->
                <div class="bg-slate-800 shadow-xl border border-slate-700/50 rounded-2xl overflow-hidden" v-if="cfdi">
                    <div class="px-6 py-5 border-b border-slate-700/50 bg-slate-900/30">
                        <h3 class="text-lg font-medium text-white">Timbre Fiscal Digital</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div class="sm:col-span-1">
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Folio Fiscal (UUID)</dt>
                                <dd class="mt-1 text-sm text-white font-mono bg-slate-900/50 p-2 rounded-lg border border-slate-700/50">{{ cfdi.uuid }}</dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Fecha Timbrado</dt>
                                <dd class="mt-1 text-sm text-white">{{ new Date(cfdi.fecha_timbrado).toLocaleString() }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sello SAT</dt>
                                <dd class="mt-1 text-xs text-slate-400 break-all font-mono">{{ cfdi.sello_sat || 'No disponible' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Right Column: Info & Totals -->
            <div class="md:col-span-1 space-y-8">
                 <!-- Totals Card -->
                 <div class="bg-slate-800 shadow-xl border border-slate-700/50 rounded-2xl p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5">
                       <svg class="w-32 h-32 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                       </svg>
                   </div>
                    <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Resumen</h3>
                    <div class="space-y-3 relative z-10">
                         <div class="flex justify-between text-sm text-slate-400">
                            <span>Subtotal</span>
                            <span>{{ formatearMoneda(factura.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-slate-400">
                            <span>IVA (16%)</span>
                            <span>{{ formatearMoneda(factura.iva) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-slate-700">
                            <span class="text-lg font-bold text-white">Total</span>
                            <span class="text-2xl font-bold text-emerald-400">{{ formatearMoneda(factura.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Client Info Card -->
                <div class="bg-slate-800 shadow-xl border border-slate-700/50 rounded-2xl p-6">
                    <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Cliente</h3>
                    <div class="space-y-4">
                        <div>
                             <div class="text-xs text-slate-500 uppercase">Razón Social</div>
                             <div class="text-white font-medium">{{ factura.cliente.nombre_razon_social }}</div>
                        </div>
                        <div>
                             <div class="text-xs text-slate-500 uppercase">RFC</div>
                             <div class="text-white font-mono">{{ factura.cliente.rfc }}</div>
                        </div>
                        <div>
                             <div class="text-xs text-slate-500 uppercase">Régimen Fiscal</div>
                             <div class="text-slate-300">{{ factura.datos_fiscales?.regimen_fiscal || factura.cliente.regimen_fiscal }}</div>
                        </div>
                        <div>
                             <div class="text-xs text-slate-500 uppercase">Uso CFDI</div>
                             <div class="text-slate-300">{{ factura.datos_fiscales?.uso_cfdi || '-' }}</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

      </div>
    </div>

    <!-- Modal Cancelación -->
    <div v-if="showCancelModal" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showCancelModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700/50">
                <div class="bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-rose-900/50 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Cancelar Factura</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-400 mb-1">Motivo de Cancelación (SAT)</label>
                                    <select v-model="motivoCancelacion" class="block w-full pl-3 pr-10 py-2 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl">
                                        <option value="01">01 - Comprobante emitido con errores con relación</option>
                                        <option value="02">02 - Comprobante emitido con errores sin relación</option>
                                        <option value="03">03 - No se llevó a cabo la operación</option>
                                        <option value="04">04 - Operación nominativa relacionada</option>
                                    </select>
                                </div>
                                <div v-if="motivoCancelacion === '01'">
                                    <label class="block text-sm font-medium text-slate-400 mb-1">UUID Sustitución</label>
                                    <input type="text" v-model="uuidSustitucion" class="block w-full pl-3 pr-3 py-2 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-700/50">
                    <button type="button" 
                        class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-rose-600 text-base font-medium text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                        @click="cancelarFactura"
                        :disabled="processingCancel"
                    >
                        {{ processingCancel ? 'Cancelando...' : 'Confirmar Cancelación' }}
                    </button>
                    <button type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-600 shadow-sm px-4 py-2 bg-transparent text-base font-medium text-slate-300 hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                        @click="showCancelModal = false"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>
