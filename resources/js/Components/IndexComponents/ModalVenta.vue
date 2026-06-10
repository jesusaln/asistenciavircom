<template>
  <Transition name="modal">
    <div
      v-if="show && selected"
      class="fixed inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        class="bg-white dark:bg-slate-950 rounded-[2rem] shadow-2xl w-full max-w-6xl max-h-[92vh] overflow-hidden outline-none border border-slate-100 dark:border-slate-800 transition-all duration-300"
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >
        <!-- Header con gradiente -->
        <div class="relative px-8 py-8 border-b border-slate-100 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}08 0%, ${colors.secundario}05 100%)` }">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center space-x-5">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform hover:scale-105" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                        <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Venta</h2>
                            <span class="inline-flex items-center px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-[0.2em]">
                                {{ selected.numero_venta || `#${selected.id}` }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                            Emitida el {{ formatearFecha(selected.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span :class="getEstadoStyle(selected.estado)" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wide border border-current">
                        {{ getEstadoLabel(selected.estado) }}
                    </span>
                    <button @click="onClose" class="p-3 rounded-2xl bg-transparent dark:bg-slate-900 text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-y-auto max-h-[calc(92vh-120px)] custom-scrollbar">
            <div class="p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Columna Principal -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Cards de Información Superior -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Cliente -->
                        <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-4">Información del Cliente</span>
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center font-black text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wide shadow-sm border border-slate-100 dark:border-slate-700">
                                    {{ (selected.cliente?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase leading-tight">{{ selected.cliente?.nombre_razon_social || 'Público General' }}</p>
                                    <div class="mt-2 space-y-1">
                                        <p v-if="selected.cliente?.rfc" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">RFC: {{ selected.cliente.rfc }}</p>
                                        <p v-if="selected.cliente?.email" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email: {{ selected.cliente.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles Administrativos -->
                        <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-4">Control Administrativo</span>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Vendedor</span>
                                    <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ selected.vendedor?.nombre || 'Sistema' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Almacén</span>
                                    <span class="text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected.almacen?.nombre || 'General' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Estado Cobro</span>
                                    <span v-if="selected.pagado" class="text-[10px] font-black text-emerald-600 dark:text-emerald-500 uppercase italic">¡Liquidado!</span>
                                    <span v-else class="text-[10px] font-black text-brand-500 uppercase">Pendiente</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Facturación Sat (CFDI) -->
                    <div v-if="selected.esta_facturada" class="p-6 bg-blue-50/30 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100 dark:border-blue-900/20">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944V22m0-19.056c1.11 0 2.22.12 3.291.352 3.174.694 5.254 3.012 5.254 6.225 0 2.969-1.928 5.48-4.686 6.305" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Factura Electrónica (CFDI 4.0)</h3>
                                <p class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Documento Timbrado SAT</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">UUID SAT</p>
                                <p class="text-[11px] font-mono font-bold text-blue-600 dark:text-blue-400 break-all bg-white dark:bg-slate-950 p-2 rounded-xl border border-blue-50 dark:border-blue-900/30">{{ selected.factura_uuid }}</p>
                            </div>
                            <div class="flex items-center gap-2 mt-auto">
                                <button @click="verFacturaSat(selected.id)" class="flex-1 py-3 bg-white dark:bg-slate-900 border-2 border-blue-100 dark:border-blue-900/30 rounded-xl text-[9px] font-black uppercase text-blue-600 dark:text-blue-400 hover:bg-blue-50 transition-all">PDF CFDI</button>
                                <button @click="verXmlSat(selected.id)" class="flex-1 py-3 bg-white dark:bg-slate-900 border-2 border-blue-100 dark:border-blue-900/30 rounded-xl text-[9px] font-black uppercase text-blue-600 dark:text-blue-400 hover:bg-blue-50 transition-all">XML SAT</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="bg-white dark:bg-slate-950 rounded-[2rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="px-6 py-5 bg-transparent dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Conceptos de la Venta</h3>
                            <span class="px-3 py-1 rounded-xl bg-white dark:bg-slate-950 text-[10px] font-black text-slate-500 dark:text-slate-400 border border-slate-100 dark:border-slate-800">
                                {{ itemsCalculados.length }} POSICIONES
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-transparent/30 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Concepto / Servicio</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cant.</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">P. Unitario</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template v-for="item in itemsCalculados" :key="item.id">
                                        <tr class="group hover:bg-transparent dark:hover:bg-slate-900/50 transition-colors">
                                            <td class="px-6 py-5">
                                                <div class="flex items-center gap-3">
                                                    <div :class="item.tipo === 'producto' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'" 
                                                         class="w-10 h-10 rounded-xl border flex items-center justify-center font-mono text-xs font-black shadow-sm flex-shrink-0">
                                                        {{ item.tipo === 'producto' ? 'PR' : 'SR' }}
                                                    </div>
                                                    <div class="max-w-[240px]">
                                                        <p class="text-xs font-black text-slate-900 dark:text-white uppercase truncate">{{ item.nombre }}</p>
                                                        <p v-if="item.pivot?.descuento > 0" class="text-[9px] font-black text-rose-600 uppercase tracking-wide">Desc: {{ item.pivot.descuento }}%</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <span class="inline-flex items-center justify-center h-8 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-900 dark:text-white">
                                                    {{ item.pivot?.cantidad || 1 }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-5 text-right">
                                                <p class="text-xs font-black text-slate-900 dark:text-white">${{ formatCurrency(item.pivot?.precio || 0) }}</p>
                                            </td>
                                            <td class="px-6 py-5 text-right">
                                                <p class="text-xs font-black text-slate-900 dark:text-white">${{ formatCurrency(item.pivot?.subtotal || 0) }}</p>
                                            </td>
                                        </tr>
                                        <!-- Series -->
                                        <tr v-if="item.requiere_serie && item.series?.length > 0" class="bg-blue-50/30 dark:bg-blue-900/10">
                                            <td colspan="4" class="px-8 py-4">
                                                <div class="flex flex-wrap gap-2">
                                                    <div v-for="(serie, idx) in item.series" :key="idx" class="px-3 py-1.5 bg-white dark:bg-slate-950 border border-blue-100 dark:border-blue-900/30 rounded-xl shadow-sm">
                                                        <p class="text-[10px] font-mono font-black text-blue-600 dark:text-blue-400">{{ serie.numero_serie || serie }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Columna Lateral -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Resumen Financiero -->
                    <div class="p-8 bg-slate-900 dark:bg-white rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 dark:bg-slate-900/5 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                        <h3 class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-8">Liquidación de Venta</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center text-white dark:text-slate-900">
                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-60">Subtotal</span>
                                <span class="text-sm font-black">${{ formatCurrency(selected.subtotal || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-white dark:text-slate-900">
                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-60">Impuestos (16%)</span>
                                <span class="text-sm font-black">${{ formatCurrency(selected.iva || 0) }}</span>
                            </div>
                            <div class="pt-6 mt-6 border-t border-white/10 dark:border-slate-100">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-2">Total Facturado</p>
                                <p class="text-4xl font-black text-white dark:text-slate-900 tracking-tighter">
                                    <span class="text-xl font-bold opacity-50 mr-1">$</span>{{ formatCurrency(selected.total || 0) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Estado de Pago -->
                    <div :class="selected.pagado ? 'bg-emerald-50/50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-800' : 'bg-brand-50/50 dark:bg-brand-900/10 border-brand-100 dark:border-amber-800'" 
                         class="p-6 rounded-[2rem] border">
                        <div class="flex items-center gap-3 mb-4">
                            <div :class="selected.pagado ? 'bg-emerald-500' : 'bg-brand-500'" class="w-8 h-8 rounded-xl flex items-center justify-center text-white shadow-lg">
                                <svg v-if="selected.pagado" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wide" :class="selected.pagado ? 'text-emerald-600' : 'text-amber-600'">
                                {{ selected.pagado ? 'Pago Liquidado' : 'Pendiente de Cobro' }}
                            </span>
                        </div>
                        <p v-if="selected.pagado" class="text-[11px] font-black text-slate-900 dark:text-white uppercase leading-snug">
                            {{ labelMetodoPagoVenta(selected.metodo_pago) }} · {{ selected.fecha_pago ? new Date(selected.fecha_pago).toLocaleDateString() : '' }}
                        </p>
                        <button v-else @click="$emit('marcar-pagado', selected)" class="w-full py-3 bg-brand-500 text-white rounded-xl text-[9px] font-black uppercase tracking-wide shadow-lg transform active:scale-95 transition-all">Marcar como Pagado</button>
                    </div>

                    <!-- Entrega de Dinero -->
                    <div v-if="selected.tiene_entrega_dinero" class="p-6 bg-emerald-50/30 dark:bg-emerald-900/10 rounded-[2rem] border border-emerald-100 dark:border-emerald-900/20">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Dinero Entregado</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Estado</span>
                                <span class="text-[9px] font-black uppercase" :class="selected.entrega_dinero?.estado === 'recibido' ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ selected.entrega_dinero?.estado === 'recibido' ? 'Recibido por Admin' : 'Pendiente Recibir' }}
                                </span>
                            </div>
                            <div v-if="selected.entrega_dinero?.recibido_por_nombre" class="flex justify-between">
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Recibido por</span>
                                <span class="text-[9px] font-black text-slate-900 dark:text-white uppercase">{{ selected.entrega_dinero.recibido_por_nombre }}</span>
                            </div>
                            <div v-if="selected.entrega_dinero?.fecha_recibido" class="flex justify-between">
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Fecha Recibo</span>
                                <span class="text-[9px] font-black text-slate-900 dark:text-white uppercase">{{ new Date(selected.entrega_dinero.fecha_recibido).toLocaleDateString() }}</span>
                            </div>
                        </div>
                        <Link :href="`/entregas-dinero/${selected.entrega_dinero?.id}`" class="block w-full mt-4 py-2 text-center bg-white dark:bg-slate-950 border border-emerald-100 dark:border-emerald-900/30 rounded-xl text-[9px] font-black text-emerald-600 uppercase hover:bg-emerald-50 transition-all">Ver Entrega #{{ selected.entrega_dinero?.id }}</Link>
                    </div>
                    <div v-else-if="selected.metodo_pago === 'efectivo' && selected.pagado" class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wide italic">Efectivo pendiente de entrega</span>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="grid grid-cols-1 gap-3">
                        <button @click="verPdfEnNavegador(selected.id)" class="w-full py-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] text-slate-600 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 transition-all flex items-center justify-center gap-3 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Detalle PDF
                        </button>



                        <button @click="onClose" class="w-full py-3 font-black text-slate-400 hover:text-slate-900 dark:hover:text-white uppercase text-[10px] tracking-wide transition-colors">
                            Cerrar Visor
                        </button>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import { labelMetodoPagoVenta } from '@/Utils/ventaPagoLabels'

const { colors } = useCompanyColors()
const notyf = new Notyf({ position: { x: 'right', y: 'top' } })

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null },
  auditoria: { type: Object, default: null }
})

const emit = defineEmits(['close', 'marcar-pagado'])

const modalRef = ref(null)
const isProcessingFactura = ref(false)

const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

const onClose = () => emit('close')
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatCurrency = (num) => {
  const value = parseFloat(num)
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number.isFinite(value) ? value : 0)
}

const getEstadoStyle = (estado) => {
  const map = {
    borrador: 'text-slate-500 border-slate-200 dark:text-slate-400 dark:border-slate-800',
    aprobada: 'text-indigo-600 border-indigo-200 dark:text-indigo-400 dark:border-indigo-800',
    cancelada: 'text-rose-600 border-rose-200 dark:text-rose-400 dark:border-rose-800'
  }
  return map[estado?.toLowerCase()] || 'text-emerald-600 border-emerald-200'
}

const getEstadoLabel = (estado) => {
  const map = {
    borrador: 'Borrador',
    aprobada: 'Aprobada',
    cancelada: 'Cancelada',
    pagado: 'Liquidada'
  }
  return map[estado?.toLowerCase()] || String(estado || '---').toUpperCase()
}

const itemsCalculados = computed(() => {
  const lista = Array.isArray(props.selected?.items) ? props.selected.items : []
  return lista.map((item) => {
    const cantidad = parseFloat(item.cantidad || 1)
    const precio = parseFloat(item.precio || 0)
    const descuento = parseFloat(item.descuento || 0)
    const subtotalBase = precio * cantidad
    const subtotal = subtotalBase - (subtotalBase * (descuento / 100))
    return { ...item, pivot: { cantidad, precio, descuento, subtotal } }
  })
})

const verPdfEnNavegador = (id) => window.open(`/ventas/${id}/pdf`, '_blank')



const verFacturaSat = (id) => {
  if (props.selected?.factura_uuid) {
    window.open(route('cfdi.ver-pdf-view', props.selected.factura_uuid), '_blank')
  }
}

const verXmlSat = (id) => {
  if (props.selected?.factura_uuid) {
    window.open(route('cfdi.xml', { uuid: props.selected.factura_uuid, inline: 1 }), '_blank')
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95) translateY(20px); }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
