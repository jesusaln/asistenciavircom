<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        class="bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl w-full max-w-6xl max-h-[92vh] overflow-hidden outline-none border border-slate-100 dark:border-slate-800 transition-all duration-300"
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >
        <!-- Header con gradiente Premium -->
        <div class="relative px-8 py-8 border-b border-slate-100 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}08 0%, ${colors.secundario}05 100%)` }">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center space-x-5">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform hover:scale-105" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                        <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Orden de Compra</h2>
                            <span class="inline-flex items-center px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-[0.2em]">
                                #{{ selected?.numero_orden || selected?.id }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                            Generada el {{ formatearFecha(selected?.fecha_orden || selected?.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span :class="obtenerClasesEstado(selected?.estado)" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wide border border-current transition-all">
                        {{ obtenerLabelEstado(selected?.estado) }}
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
                    <!-- Cards de Información -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Proveedor -->
                        <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-4">Proveedor Designado</span>
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center font-black text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wide shadow-sm border border-slate-100 dark:border-slate-700">
                                    {{ (selected?.proveedor?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase leading-tight">{{ selected?.proveedor?.nombre_razon_social || 'Sin proveedor' }}</p>
                                    <div class="mt-2 space-y-1">
                                        <p v-if="selected?.proveedor?.rfc" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">RFC: {{ selected.proveedor.rfc }}</p>
                                        <p v-if="selected?.proveedor?.email" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email: {{ selected.proveedor.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles Administrativos -->
                        <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-4">Control Administrativo</span>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Responsable</span>
                                    <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ selected?.usuario?.name || selected?.creado_por_nombre || 'Sistema' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Almacén Destino</span>
                                    <span class="text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected?.almacen?.nombre || 'General' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Vigencia</span>
                                    <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ formatearFecha(selected?.fecha_vencimiento) || 'Indefinida' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="bg-white dark:bg-slate-950 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm transition-all duration-500">
                        <div class="px-6 py-5 bg-transparent dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Listado de Productos</h3>
                            <span class="px-3 py-1 rounded-xl bg-white dark:bg-slate-950 text-[10px] font-black text-slate-500 dark:text-slate-400 border border-slate-100 dark:border-slate-800">
                                {{ itemsCalculados.length }} ITEMS
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-transparent/30 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Producto / Descripción</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cant.</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Precio Unit.</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="producto in itemsCalculados" :key="producto.id" class="group hover:bg-transparent dark:hover:bg-slate-900/50 transition-colors">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 shadow-inner">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                                <div class="max-w-[280px]">
                                                    <p class="text-xs font-black text-slate-900 dark:text-white uppercase truncate">{{ producto.nombre || producto.producto_nombre }}</p>
                                                    <p v-if="producto.descripcion" class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase truncate mt-0.5">{{ producto.descripcion }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <span class="inline-flex items-center justify-center h-8 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-900 dark:text-white">
                                                {{ producto.cantidad }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <p class="text-xs font-black text-slate-900 dark:text-white">${{ formatCurrency(producto.precio) }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <p class="text-xs font-black text-slate-900 dark:text-white">${{ formatCurrency(producto.subtotal) }}</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Columna Lateral -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Resumen Financiero Premium -->
                    <div class="p-8 bg-slate-900 dark:bg-white rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 dark:bg-slate-900/5 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                        
                        <h3 class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-8">Estado Financiero</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center text-white dark:text-slate-900">
                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-60">Subtotal</span>
                                <span class="text-sm font-black">${{ formatCurrency(selected?.subtotal || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-white dark:text-slate-900">
                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-60">IVA (16%)</span>
                                <span class="text-sm font-black">${{ formatCurrency(selected?.iva || 0) }}</span>
                            </div>
                            <div class="pt-6 mt-6 border-t border-white/10 dark:border-slate-100">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-2">Total Presupuestado</p>
                                <p class="text-4xl font-black text-white dark:text-slate-900 tracking-tighter transition-all">
                                    <span class="text-xl font-bold opacity-50 mr-1">$</span>{{ formatCurrency(selected?.total || 0) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Auditoría y Trazabilidad -->
                    <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <h4 class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Auditoría de Documento</h4>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase">{{ selected?.creado_por_nombre || selected?.created_by_user_name || 'Sistema' }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Creación: {{ formatearFechaHora(selected?.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="selected?.updated_at" class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase">Última Modificación</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">{{ formatearFechaHora(selected?.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Estratégicas -->
                    <div class="grid grid-cols-1 gap-3">
                        <button @click="$emit('imprimir', selected)" class="w-full py-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] text-slate-600 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 transition-all flex items-center justify-center gap-3 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Imprimir Documento
                        </button>
                        
                        <button v-if="['borrador', 'pendiente', 'aprobada', 'enviado_a_proveedor'].includes(selected?.estado)" @click="$emit('editar', selected?.id)" class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Editar Orden
                        </button>

                        <button @click="onClose" class="w-full py-3 font-black text-slate-400 hover:text-slate-900 dark:hover:text-white uppercase text-[10px] tracking-wide transition-colors">
                            Cerrar Panel
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
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

const { colors } = useCompanyColors()

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null }
})

const emit = defineEmits(['close', 'editar', 'imprimir'])

const modalRef = ref(null)
const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

const onClose = () => emit('close')
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const obtenerClasesEstado = (estado) => {
    const map = {
        pendiente: 'text-brand-500 border-brand-200 dark:text-brand-400 dark:border-brand-800/50',
        aprobada: 'text-sky-500 border-sky-200 dark:text-sky-400 dark:border-sky-800/50',
        enviado_a_proveedor: 'text-blue-600 border-blue-200 dark:text-blue-400 dark:border-blue-800/50',
        procesada: 'text-emerald-600 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800/50',
        cancelada: 'text-rose-600 border-rose-200 dark:text-rose-400 dark:border-rose-800/50'
    }
    return map[estado] || 'text-slate-400 border-slate-200'
}

const obtenerLabelEstado = (estado) => {
    const map = {
        pendiente: 'Pendiente',
        aprobada: 'Aprobada',
        enviado_a_proveedor: 'Enviada',
        procesada: 'Procesada',
        cancelada: 'Cancelada'
    }
    return map[estado] || String(estado || '---').toUpperCase()
}

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
}

const formatearFechaHora = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const formatCurrency = (num) => {
  const value = parseFloat(num)
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number.isFinite(value) ? value : 0)
}

const itemsCalculados = computed(() => {
  const lista = Array.isArray(props.selected?.productos) ? props.selected.productos : (Array.isArray(props.selected?.items) ? props.selected.items : [])
  return lista.map((item) => {
    const cantidad = parseFloat(item.cantidad || 1)
    const precio = parseFloat(item.precio || item.precio_unitario || 0)
    const subtotal = precio * cantidad
    return { ...item, cantidad, precio, subtotal }
  })
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from,
.modal-leave-to { opacity: 0; transform: scale(0.95) translateY(30px); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
