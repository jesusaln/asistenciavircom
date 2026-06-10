<template>
  <Transition name="modal-fade">
    <div v-if="show && selected" class="fixed inset-0 z-[110] flex items-center justify-center p-4 md:p-8" @click.self="onClose">
      <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md"></div>
      
      <div class="relative w-full max-w-6xl bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col transition-all duration-500 animate-in fade-in zoom-in-95 max-h-[95vh]">
        <!-- Header Estratégico -->
        <div class="px-8 py-8 border-b border-slate-100 dark:border-slate-800 bg-transparent/30 dark:bg-slate-900/50 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            
            <div class="flex items-center gap-6 relative z-10">
                <div class="w-16 h-16 rounded-[1.5rem] bg-slate-900 dark:bg-white flex items-center justify-center text-white dark:text-slate-900 shadow-xl shadow-slate-900/20 dark:shadow-white/10">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide">Análisis de Cotización</h2>
                        <span class="text-[10px] font-mono font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-sky-900/20 px-3 py-1 rounded-xl border border-blue-100 dark:border-blue-800/50">
                            {{ numeroCotizacion }}
                        </span>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Expediente Comercial • {{ formatearFechaFull(selected.created_at) }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4 relative z-10">
                <span :class="obtenerEstadoClase(selected.estado)" class="px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-current shadow-sm">
                    <span class="w-2 h-2 rounded-full mr-2 animate-pulse bg-current inline-block"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                </span>
                <button @click="onClose" class="w-12 h-12 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 rounded-2xl transition-all shadow-sm group">
                    <svg class="w-6 h-6 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Columna Operativa -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Cards de Información -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 relative group overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] block mb-6">Socio Comercial</span>
                            <div class="flex items-start gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center font-black text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wide shadow-sm border border-slate-100 dark:border-slate-700">
                                    {{ (selected.cliente?.nombre || '?').substring(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-base font-black text-slate-900 dark:text-white uppercase leading-tight mb-2">{{ selected.cliente?.nombre || 'Desconocido' }}</p>
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5l-2-2z" /></svg>
                                            {{ selected.cliente?.rfc || 'Sin RFC registrado' }}
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            {{ selected.cliente?.email || 'Sin correo electrónico' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 relative group overflow-hidden">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] block mb-6">Logística de Almacén</span>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:border-slate-900 dark:hover:border-slate-700">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Origen</span>
                                    <span class="text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected.almacen?.nombre || 'General' }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:border-slate-900 dark:hover:border-slate-700">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Vendedor</span>
                                    <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ selected.vendedor?.nombre || 'Sistema' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notas Técnicas -->
                    <div v-if="selected.notas" class="p-8 bg-slate-900 dark:bg-white rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 dark:bg-slate-900/5 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                        <div class="flex items-start gap-6 relative z-10">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 dark:bg-slate-100 flex items-center justify-center text-white dark:text-slate-900 shadow-lg shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-2">Observaciones Estratégicas</h3>
                                <p class="text-sm text-white/80 dark:text-slate-600 leading-relaxed font-medium italic">{{ selected.notas }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Partidas de la Cotización -->
                    <div class="bg-white dark:bg-slate-950 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-transparent/30 dark:bg-slate-900/50">
                            <h3 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.3em]">Desglose de Partidas</h3>
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ itemsCalculados.length }} Conceptos Registrados</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                <thead class="bg-transparent dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-wide">Concepto</th>
                                        <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-wide">Cant.</th>
                                        <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-wide">Unitario</th>
                                        <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-wide">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-900">
                                    <tr v-for="item in itemsCalculados" :key="item.id" class="group hover:bg-transparent dark:hover:bg-slate-900/50 transition-all duration-300">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div :class="item.tipo === 'producto' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 'bg-purple-500/10 text-purple-500 border-purple-500/20'" class="w-12 h-12 rounded-xl border flex items-center justify-center font-black text-[10px] shadow-sm shrink-0">
                                                    {{ item.tipo === 'producto' ? 'PRD' : 'SRV' }}
                                                </div>
                                                <div class="max-w-[300px]">
                                                    <p class="text-xs font-black text-slate-900 dark:text-white uppercase truncate">{{ item.nombre }}</p>
                                                    <p v-if="item.pivot?.descuento > 0" class="text-[9px] font-black text-rose-500 uppercase tracking-wide mt-1">Beneficio: {{ item.pivot.descuento }}% Off</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="inline-flex items-center justify-center h-9 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-900 dark:text-white border border-slate-200/50 dark:border-slate-700">
                                                {{ item.pivot?.cantidad || 1 }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <p class="text-xs font-black text-slate-900 dark:text-white tracking-tight">${{ formatCurrency(item.pivot?.precio || 0) }}</p>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <p class="text-xs font-black text-slate-900 dark:text-white tracking-tight font-mono">${{ formatCurrency(item.pivot?.subtotal || 0) }}</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Columna Financiera -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="p-10 bg-slate-950 dark:bg-white rounded-[3rem] shadow-2xl relative overflow-hidden group border border-slate-800 dark:border-slate-100">
                        <div class="absolute bottom-0 right-0 w-48 h-48 bg-blue-500/10 rounded-full -mr-24 -mb-24 transition-transform group-hover:scale-110 blur-2xl"></div>
                        
                        <h3 class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.4em] mb-10">Resumen Financiero</h3>
                        
                        <div class="space-y-6">
                            <div class="flex justify-between items-center text-white/60 dark:text-slate-400">
                                <span class="text-[10px] font-black uppercase tracking-wide">Base Imponible</span>
                                <span class="text-sm font-black text-white dark:text-slate-900">${{ formatCurrency(selected.subtotal || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-white/60 dark:text-slate-400">
                                <span class="text-[10px] font-black uppercase tracking-wide">I.V.A. (16%)</span>
                                <span class="text-sm font-black text-white dark:text-slate-900">${{ formatCurrency(selected.iva || 0) }}</span>
                            </div>
                            
                            <div class="pt-8 mt-8 border-t border-white/10 dark:border-slate-100">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.3em]">Inversión Total</p>
                                </div>
                                <p class="text-5xl font-black text-white dark:text-slate-900 tracking-tighter">
                                    <span class="text-2xl font-bold opacity-30 mr-1">$</span>{{ formatCurrency(selected.total || 0) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="auditoriaSafe" class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-6">Trazabilidad de Sistema</h4>
                        <div class="space-y-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-black text-slate-900 dark:text-white uppercase leading-none mb-1.5">{{ auditoriaSafe.creado_por || 'Sistema' }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Generación: {{ formatearFechaFull(auditoriaSafe.creado_en) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button @click="verPdfEnNavegador(selected.id)" class="w-full py-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] text-slate-600 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 transition-all flex items-center justify-center gap-3 shadow-sm group/btn">
                            <svg class="w-4 h-4 transition-transform group-hover/btn:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Expediente PDF
                        </button>
                        
                        <button v-if="selected.estado?.toLowerCase() === 'aprobada'" @click="$emit('enviar-a-pedido', selected)" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] shadow-xl shadow-blue-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                            Ejecutar Pedido
                        </button>

                        <button @click="$emit('editar', selected.id)" class="w-full py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11" /></svg>
                            Modificar Propuesta
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

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null },
  auditoria: { type: Object, default: null }
})

const emit = defineEmits(['close', 'editar', 'enviar-a-pedido'])

const onClose = () => emit('close')

const numeroCotizacion = computed(() => props.selected?.numero_cotizacion || `#${props.selected?.id || '---'}`)

const obtenerEstadoClase = (estado) => {
  const e = estado?.toLowerCase() || 'pendiente'
  const m = {
    'pendiente': 'text-brand-600 dark:text-brand-400 border-brand-100 dark:border-brand-900/30 bg-brand-50/50 dark:bg-brand-900/10',
    'aprobada': 'text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-900/10',
    'rechazada': 'text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-900/10',
    'enviada': 'text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/30 bg-sky-50/50 dark:bg-sky-900/10',
    'enviado_pedido': 'text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/30 bg-sky-50/50 dark:bg-sky-900/10',
    'convertida_pedido': 'text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-900/10',
    'cancelado': 'text-slate-400 dark:text-slate-500 border-slate-100 dark:border-slate-800 bg-transparent dark:bg-slate-900/50'
  }
  return m[e] || m.pendiente
}

const obtenerLabelEstado = (estado) => {
  const e = estado?.toLowerCase() || 'pendiente'
  const m = {
    'pendiente': 'Pendiente',
    'aprobada': 'Aprobada',
    'rechazada': 'Rechazada',
    'enviada': 'Enviada',
    'enviado_pedido': 'En Pedido',
    'convertida_pedido': 'Convertida',
    'cancelado': 'Cancelada'
  }
  return m[e] || 'Desconocido'
}

const formatearFechaFull = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatCurrency = (num) => {
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)
}

const itemsCalculados = computed(() => {
  const lista = Array.isArray(props.selected?.productos) ? props.selected.productos : (Array.isArray(props.selected?.items) ? props.selected.items : [])
  return lista.map((item) => {
    const cantidad = parseFloat(item.pivot?.cantidad || item.cantidad || 1)
    const precio = parseFloat(item.pivot?.precio || item.precio || item.precio_unitario || 0)
    const descuento = parseFloat(item.pivot?.descuento || item.descuento || 0)
    const subtotalBase = precio * cantidad
    const subtotal = subtotalBase - (subtotalBase * (descuento / 100))
    return { ...item, pivot: { cantidad, precio, descuento, subtotal }, nombre: item.nombre || item.producto_nombre || 'Producto/Servicio' }
  })
})

const auditoriaSafe = computed(() => {
  if (props.auditoria) return props.auditoria
  if (props.selected) {
    return {
      creado_por: props.selected.created_by_user_name || props.selected.creado_por_nombre || 'Sistema',
      creado_en: props.selected.created_at,
    }
  }
  return null
})

const verPdfEnNavegador = (id) => {
    if (!id) return
    window.open(`/cotizaciones/${id}/pdf`, '_blank')
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }

@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in { from { transform: scale(0.9); } to { transform: scale(1); } }
.animate-in { animation: fade-in 0.5s ease-out, zoom-in 0.5s ease-out; }
</style>
