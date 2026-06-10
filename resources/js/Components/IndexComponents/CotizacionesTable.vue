<template>
  <div class="bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-500">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
        <thead class="bg-transparent dark:bg-slate-900/50">
          <tr>
            <th class="px-8 py-6 text-left cursor-pointer group" @click="onSort('fecha')">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Programación</span>
                <svg v-if="sortBy.startsWith('fecha')" :class="['w-3 h-3 transition-transform', sortBy === 'fecha-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
              </div>
            </th>
            <th class="px-8 py-6 text-left cursor-pointer group" @click="onSort('cliente')">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Socio Comercial</span>
                <svg v-if="sortBy.startsWith('cliente')" :class="['w-3 h-3 transition-transform', sortBy === 'cliente-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
              </div>
            </th>
            <th class="px-8 py-6 text-left cursor-pointer group" @click="onSort('numero_cotizacion')">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Identificador</span>
                <svg v-if="sortBy.startsWith('numero_cotizacion')" :class="['w-3 h-3 transition-transform', sortBy === 'numero_cotizacion-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
              </div>
            </th>
            <th class="px-8 py-6 text-left cursor-pointer group" @click="onSort('total')">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Inversión</span>
                <svg v-if="sortBy.startsWith('total')" :class="['w-3 h-3 transition-transform', sortBy === 'total-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
              </div>
            </th>
            <th class="px-8 py-6 text-left cursor-pointer group" @click="onSort('estado')">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estatus Comercial</span>
                <svg v-if="sortBy.startsWith('estado')" :class="['w-3 h-3 transition-transform', sortBy === 'estado-desc' ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
              </div>
            </th>
            <th class="px-8 py-6 text-right">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Acciones</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-900 bg-white dark:bg-slate-950">
          <tr v-for="doc in items" :key="doc.id" class="group hover:bg-slate-50/80 dark:hover:bg-slate-900/50 transition-all duration-300">
            <!-- Fecha -->
            <td class="px-8 py-6 whitespace-nowrap">
              <div class="flex flex-col">
                <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-wide">{{ formatearFecha(doc.created_at || doc.fecha) }}</span>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-wide">{{ formatearHora(doc.created_at || doc.fecha) }}</span>
              </div>
            </td>

            <!-- Cliente -->
            <td class="px-8 py-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-[10px] text-slate-400 dark:text-slate-500 shadow-sm border border-slate-200/50 dark:border-slate-700">
                  {{ (doc.cliente?.nombre || '?').substring(0, 2).toUpperCase() }}
                </div>
                <div class="flex flex-col max-w-[200px]">
                  <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase truncate">{{ doc.cliente?.nombre || 'Desconocido' }}</span>
                  <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-0.5 truncate">{{ doc.cliente?.rfc || 'Sin RFC' }}</span>
                </div>
              </div>
            </td>

            <!-- N° Cotización -->
            <td class="px-8 py-6">
              <span class="text-[10px] font-mono font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-sky-900/20 px-3 py-1.5 rounded-xl border border-blue-100 dark:border-blue-800/50 shadow-sm">
                {{ doc.numero_cotizacion_display || doc.numero_cotizacion || doc.id }}
              </span>
            </td>

            <!-- Total -->
            <td class="px-8 py-6">
              <div class="flex flex-col">
                <span class="text-xs font-black text-slate-900 dark:text-white tracking-tight">${{ formatearMoneda(doc.total) }}</span>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">I.V.A. Inc.</span>
              </div>
            </td>

            <!-- Estado -->
            <td class="px-8 py-6 whitespace-nowrap">
              <span :class="obtenerEstadoClase(doc.estado)" class="inline-flex items-center px-4 py-2 rounded-full text-[9px] font-black uppercase tracking-[0.15em] border border-current shadow-sm transition-all duration-300">
                <span class="w-1.5 h-1.5 rounded-full mr-2 animate-pulse bg-current"></span>
                {{ obtenerLabelEstado(doc.estado) }}
              </span>
            </td>

            <!-- Acciones -->
            <td class="px-8 py-6 text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-4 group-hover:translate-x-0">
                <button @click="$emit('ver-detalles', doc)" class="w-10 h-10 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Análisis de Propuesta">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button v-if="doc.estado !== 'cancelado'" @click="$emit('editar', doc.id)" class="w-10 h-10 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-brand-600 hover:border-brand-600 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Modificar Configuración">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button v-if="doc.estado !== 'cancelado'" @click="$emit('imprimir', doc)" class="w-10 h-10 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-purple-600 hover:border-purple-600 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Generar Expediente PDF">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                  </svg>
                </button>
                <button v-if="doc.estado !== 'cancelado'" @click="$emit('eliminar', doc.id)" class="w-10 h-10 bg-rose-50 dark:bg-rose-900/20 text-rose-400 hover:text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/40 border-2 border-transparent rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Revocar Propuesta">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="items.length === 0">
            <td colspan="6" class="px-8 py-32 text-center">
              <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                <div class="w-24 h-24 bg-transparent dark:bg-slate-900 rounded-[2rem] flex items-center justify-center mb-8 shadow-inner border border-slate-100 dark:border-slate-800 animate-pulse">
                  <svg class="w-12 h-12 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.3em] mb-2">Sin Operaciones Comerciales</h4>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-loose">
                  No se han registrado cotizaciones bajo los criterios estratégicos actuales.
                </p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed } from 'vue'

const props = defineProps({
  documentos: { type: Array, required: true },
  sortBy: { type: String, default: 'fecha-desc' }
})

const emit = defineEmits(['ver-detalles', 'editar', 'eliminar', 'imprimir', 'sort'])

const items = computed(() => props.documentos || [])

const onSort = (field) => {
  const current = props.sortBy.startsWith(field) ? props.sortBy : `${field}-desc`
  const newOrder = current === `${field}-desc` ? `${field}-asc` : `${field}-desc`
  emit('sort', newOrder)
}

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatearHora = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

const formatearMoneda = (num) => {
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)
}

const obtenerEstadoClase = (estado) => {
  const e = estado?.toLowerCase() || 'pendiente'
  const m = {
    'pendiente': 'text-brand-600 dark:text-brand-400 border-brand-100 dark:border-brand-900/30 bg-brand-50/50 dark:bg-brand-900/10',
    'aprobada': 'text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-brand-900/30 bg-emerald-50/50 dark:bg-emerald-900/10',
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
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
