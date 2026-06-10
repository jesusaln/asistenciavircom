<template>
  <div class="bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-500">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
        <thead class="bg-transparent dark:bg-slate-900/50">
          <tr>
            <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Incorporación</th>
            <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Socio Comercial</th>
            <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Identificación Fiscal</th>
            <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estatus</th>
            <th class="px-8 py-6 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-900 bg-white dark:bg-slate-950">
          <tr v-for="proveedor in proveedores" :key="proveedor.id" class="group hover:bg-slate-50/80 dark:hover:bg-slate-900/50 transition-all duration-300">
            <!-- Fecha -->
            <td class="px-8 py-6 whitespace-nowrap">
              <div class="flex flex-col">
                <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-wide">{{ formatearFecha(proveedor.fecha) }}</span>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wide mt-0.5">Registro Inicial</span>
              </div>
            </td>

            <!-- Socio Comercial -->
            <td class="px-8 py-6">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wide shadow-sm border border-slate-200/50 dark:border-slate-700 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
                  {{ proveedor.titulo.substring(0, 2).toUpperCase() }}
                </div>
                <div>
                  <div class="text-[11px] font-black text-slate-900 dark:text-white uppercase leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ proveedor.titulo }}</div>
                  <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-1 lowercase">{{ proveedor.subtitulo || 'Sin contacto digital' }}</div>
                </div>
              </div>
            </td>

            <!-- RFC -->
            <td class="px-8 py-6 whitespace-nowrap">
              <div class="flex flex-col">
                <span class="text-[11px] font-mono font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider bg-transparent dark:bg-slate-900 px-3 py-1 rounded-xl border border-slate-100 dark:border-slate-800 self-start">
                  {{ proveedor.raw.rfc || 'XAXX010101000' }}
                </span>
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2 italic">
                  {{ proveedor.raw.tipo_persona === 'moral' ? 'Persona Moral' : 'Persona Física' }}
                </span>
              </div>
            </td>

            <!-- Estatus -->
            <td class="px-8 py-6 whitespace-nowrap">
              <span :class="obtenerClasesEstado(proveedor.estado)" class="inline-flex items-center px-4 py-2 rounded-full text-[9px] font-black uppercase tracking-[0.15em] border border-current shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-current mr-2 animate-pulse"></span>
                {{ obtenerLabelEstado(proveedor.estado) }}
              </span>
            </td>

            <!-- Acciones -->
            <td class="px-8 py-6 text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-4 group-hover:translate-x-0">
                <button @click="$emit('ver-detalles', proveedor)" class="w-11 h-11 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Detalle Estratégico">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button @click="$emit('editar', proveedor.id)" class="w-11 h-11 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-brand-600 hover:border-brand-600 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Configurar Perfil">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button @click="$emit('toggle', proveedor.id)" class="w-11 h-11 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-emerald-600 hover:border-emerald-600 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Alternar Disponibilidad">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:rotate-180 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                </button>
                <button @click="$emit('eliminar', proveedor.id)" class="w-11 h-11 bg-rose-50 dark:bg-rose-900/20 text-rose-400 hover:text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/40 border-2 border-transparent rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Revocar Acceso">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Empty State -->
          <tr v-if="proveedores.length === 0">
            <td colspan="5" class="px-8 py-32 text-center">
              <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                <div class="w-24 h-24 bg-transparent dark:bg-slate-900 rounded-[2rem] flex items-center justify-center mb-8 shadow-inner border border-slate-100 dark:border-slate-800 animate-pulse">
                  <svg class="w-12 h-12 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.3em] mb-2">Sin registros activos</h4>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-loose">
                  No se encontraron socios comerciales bajo los criterios de filtrado seleccionados actualmente.
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
defineProps({
  proveedores: { type: Array, required: true }
})

defineEmits(['ver-detalles', 'editar', 'eliminar', 'toggle'])

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  })
}

const obtenerClasesEstado = (estado) => {
  const clases = {
    'activo': 'text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-900/10',
    'inactivo': 'text-rose-600 dark:text-rose-400 border-rose-100 dark:text-rose-900/30 bg-rose-50/50 dark:bg-rose-900/10'
  }
  return clases[estado] || 'text-slate-400 border-slate-100 bg-transparent'
}

const obtenerLabelEstado = (estado) => {
  const labels = {
    'activo': 'Activo / Operativo',
    'inactivo': 'Suspendido / Pausado'
  }
  return labels[estado] || 'Pendiente'
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
