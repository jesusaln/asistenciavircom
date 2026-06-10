<template>
  <Transition name="modal">
    <div
      v-if="show"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Compra</h2>
                            <span class="inline-flex items-center px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-[0.2em]">
                                {{ numeroCompra }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                            Registrada el {{ formatearFecha(selected?.fecha || selected?.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span :class="obtenerClasesEstado(selected?.estado)" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wide border border-current">
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
                <!-- Columna Principal (Info + Productos) -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Cards de Información Superior -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Proveedor -->
                        <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-4">Información del Proveedor</span>
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
                        <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-4">Control Administrativo</span>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Comprador</span>
                                    <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ selected.usuario?.name || 'Sistema' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Origen</span>
                                    <span class="text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected.origen === 'orden_compra' ? 'O. Compra' : 'Directa' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Pago</span>
                                    <span v-if="selected?.pue_pagado || selected?.pagado_con_rep" class="text-[10px] font-black text-emerald-600 dark:text-emerald-500 uppercase italic">¡Liquidado!</span>
                                    <span v-else class="text-[10px] font-black text-brand-500 uppercase">Pendiente</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Fiscal (CFDI) -->
                    <div v-if="selected?.cfdi_uuid" class="p-6 bg-blue-50/30 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100 dark:border-blue-900/20">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Información Fiscal (CFDI)</h3>
                                <p class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Documento Timbrado Correctamente</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">UUID SAT</p>
                                <p class="text-[11px] font-mono font-bold text-blue-600 dark:text-blue-400 break-all bg-white dark:bg-slate-950 p-2 rounded-xl border border-blue-50 dark:border-blue-900/30">{{ selected.cfdi_uuid }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Folio / Serie</p>
                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ selected.cfdi_serie || '' }}{{ selected.cfdi_folio || '---' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="bg-white dark:bg-slate-950 rounded-[2rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="px-6 py-5 bg-transparent dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Detalle de Productos</h3>
                            <span class="px-3 py-1 rounded-xl bg-white dark:bg-slate-950 text-[10px] font-black text-slate-500 dark:text-slate-400 border border-slate-100 dark:border-slate-800">
                                {{ itemsCalculados.length }} ITEMS
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-transparent/30 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Producto</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cant.</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Precio Unit.</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template v-for="producto in itemsCalculados" :key="producto.id">
                                        <tr class="group hover:bg-transparent dark:hover:bg-slate-900/50 transition-colors">
                                            <td class="px-6 py-5">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                        </svg>
                                                    </div>
                                                    <div class="max-w-[240px]">
                                                        <p class="text-xs font-black text-slate-900 dark:text-white uppercase truncate">{{ producto.nombre || producto.producto_nombre }}</p>
                                                        <p v-if="producto.descuento > 0" class="text-[9px] font-black text-rose-600 uppercase tracking-wide">Desc: {{ producto.descuento }}%</p>
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
                                        <!-- Series -->
                                        <tr v-if="producto.requiere_serie && producto.series?.length > 0" class="bg-blue-50/30 dark:bg-blue-900/10">
                                            <td colspan="4" class="px-8 py-4">
                                                <div class="flex flex-wrap gap-2">
                                                    <div v-for="(serie, idx) in producto.series" :key="idx" class="px-3 py-1.5 bg-white dark:bg-slate-950 border border-blue-100 dark:border-blue-900/30 rounded-xl shadow-sm">
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

                <!-- Columna Lateral (Resumen + Acciones) -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Resumen Financiero -->
                    <div class="p-8 bg-slate-900 dark:bg-white rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
                        <!-- Decoración -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 dark:bg-slate-900/5 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                        
                        <h3 class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-8">Resumen Financiero</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center text-white dark:text-slate-900">
                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-60">Subtotal</span>
                                <span class="text-sm font-black">${{ formatCurrency(selected?.subtotal || calcularSubtotal()) }}</span>
                            </div>
                            <div v-if="selected?.descuento_general > 0" class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-rose-400 dark:text-rose-600 uppercase tracking-wide opacity-60">Descuento</span>
                                <span class="text-sm font-black text-rose-400 dark:text-rose-600">-${{ formatCurrency(selected.descuento_general) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-white dark:text-slate-900">
                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-60">IVA (16%)</span>
                                <span class="text-sm font-black">${{ formatCurrency(selected?.iva || 0) }}</span>
                            </div>
                            <div class="pt-6 mt-6 border-t border-white/10 dark:border-slate-100">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-2">Total a Pagar</p>
                                <p class="text-4xl font-black text-white dark:text-slate-900 tracking-tighter">
                                    <span class="text-xl font-bold opacity-50 mr-1">$</span>{{ formatCurrency(selected?.total || calcularTotal()) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Auditoría Premium -->
                    <div v-if="auditoriaSafe" class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                        <h4 class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Auditoría de Registro</h4>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase">{{ auditoriaSafe.creado_por || 'Sistema' }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Creación: {{ formatearFechaHora(auditoriaSafe.creado_en) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="grid grid-cols-1 gap-3">
                        <button @click="verPdfEnNavegador(selected?.id)" class="w-full py-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] text-slate-600 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 transition-all flex items-center justify-center gap-3 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Ver PDF
                        </button>
                        
                        <button v-if="selected?.estado === 'procesada'" @click="$emit('editar', selected?.id)" class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Editar Registro
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
import { useFormatters } from '@/Composables/useFormatters';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

const { colors } = useCompanyColors()

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null },
  auditoria: { type: Object, default: null }
})

const emit = defineEmits(['close', 'confirm-delete', 'eliminar', 'editar', 'imprimir'])

const modalRef = ref(null)
const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

const onClose = () => emit('close')
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const numeroCompra = computed(() => props.selected?.numero_compra || `#${props.selected?.id || '---'}`)

const obtenerClasesEstado = (estado) => {
    const map = {
        procesada: 'text-emerald-600 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800/50',
        cancelada: 'text-rose-600 border-rose-200 dark:text-rose-400 dark:border-rose-800/50',
        borrador: 'text-slate-500 border-slate-200 dark:text-slate-400 dark:border-slate-800',
        pendiente: 'text-brand-500 border-brand-200 dark:text-brand-400 dark:border-brand-800/50'
    }
    return map[estado] || 'text-slate-400 border-slate-200'
}

const obtenerLabelEstado = (estado) => {
    const map = {
        procesada: 'Procesada',
        cancelada: 'Cancelada',
        borrador: 'Borrador',
        pendiente: 'Pendiente'
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
    const precio = parseFloat(item.precio || item.precio_unitario || item.costo || 0)
    const descuento = parseFloat(item.descuento || 0)
    const subtotalBase = precio * cantidad
    const subtotal = subtotalBase - (subtotalBase * (descuento / 100))
    return { ...item, cantidad, precio, descuento, subtotal }
  })
})

const calcularSubtotal = () => itemsCalculados.value.reduce((sum, item) => sum + (item.subtotal || 0), 0)
const calcularTotal = () => {
    const sub = calcularSubtotal()
    const desc = parseFloat(props.selected?.descuento_general || 0)
    const iva = parseFloat(props.selected?.iva || 0)
    return sub - desc + iva
}

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
    window.open(`/admin/compras/${id}/pdf`, '_blank')
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from,
.modal-leave-to { opacity: 0; transform: scale(0.95) translateY(20px); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
