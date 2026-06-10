<template>
  <div class="bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-500">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
        <thead class="bg-transparent dark:bg-slate-900/50">
          <tr>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Programación</span>
            </th>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Servicio / Folio</span>
            </th>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Socio Comercial</span>
            </th>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Especialista</span>
            </th>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estatus Operativo</span>
            </th>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Evidencias</span>
            </th>
            <th class="px-8 py-6 text-right">
              <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Acciones</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-900 bg-white dark:bg-slate-950">
          <tr v-for="cita in items" :key="cita.id" class="group hover:bg-slate-50/80 dark:hover:bg-slate-900/50 transition-all duration-300">
            <!-- Fecha/Hora -->
            <td class="px-8 py-6 whitespace-nowrap">
              <div class="flex flex-col">
                <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-wide">{{ formatearFecha(cita.raw.fecha_hora) }}</span>
                <div class="flex items-center gap-1.5 mt-1">
                   <div class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></div>
                   <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ formatearHora(cita.raw.fecha_hora) }}</span>
                </div>
              </div>
            </td>

            <!-- Servicio -->
            <td class="px-8 py-6">
              <div class="flex flex-col">
                <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase leading-tight truncate max-w-[150px]">{{ (cita.raw.tipo_servicio || 'Servicio General').replace(/_/g, ' ') }}</span>
                <span class="text-[12px] font-mono font-black text-blue-600 dark:text-blue-400 mt-1 uppercase tracking-wider">{{ cita.raw.folio || 'S/F' }}</span>
              </div>
            </td>

            <!-- Cliente -->
            <td class="px-8 py-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-[10px] text-slate-400 dark:text-slate-500 shadow-sm border border-slate-200/50 dark:border-slate-700">
                  {{ (cita.raw.cliente?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                </div>
                <div class="flex flex-col max-w-[180px]">
                  <span class="text-[11px] font-black text-slate-900 dark:text-white uppercase truncate">{{ cita.raw.cliente?.nombre_razon_social || 'Desconocido' }}</span>
                  <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-0.5 lowercase truncate">{{ cita.raw.cliente?.telefono || 'Sin contacto' }}</span>
                </div>
              </div>
            </td>

            <!-- Técnico -->
            <td class="px-8 py-6">
               <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-sky-900/30 flex items-center justify-center text-[9px] font-black text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-800/50 shadow-sm">
                    {{ getInitials(cita.raw.tecnico?.name) }}
                  </div>
                  <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ cita.raw.tecnico?.name || 'No asignado' }}</span>
               </div>
            </td>

            <!-- Estado -->
            <td class="px-8 py-6 whitespace-nowrap">
              <span :class="obtenerEstadoCitaClase(cita.raw)" class="inline-flex items-center px-4 py-2 rounded-full text-[9px] font-black uppercase tracking-[0.15em] border border-current shadow-sm transition-all duration-300">
                {{ obtenerEstadoCitaLabel(cita.raw) }}
              </span>
            </td>

            <!-- Fotos / Reporte -->
            <td class="px-8 py-6 whitespace-nowrap">
              <div v-if="cita.raw.fotos_finales?.length > 0" class="flex items-center gap-3">
                <div class="flex items-center -space-x-3 overflow-hidden group/gallery cursor-pointer" @click="$emit('ver-galeria', cita.raw.fotos_finales, `Evidencias - Cita #${cita.id}`)">
                   <img 
                     v-for="(foto, idx) in cita.raw.fotos_finales.slice(0, 3)" 
                     :key="idx" 
                     :src="storageSrc(foto)" 
                     @error="handleImageError"
                     class="inline-block h-8 w-8 rounded-xl ring-4 ring-white dark:ring-slate-950 object-cover shadow-2xl group-hover/gallery:translate-x-2 transition-transform duration-500" 
                   />
                   <div v-if="cita.raw.fotos_finales.length > 3" class="relative z-10 flex items-center justify-center h-8 w-8 rounded-xl bg-slate-900 text-[9px] font-black text-white ring-4 ring-white dark:ring-slate-950 shadow-2xl">
                     +{{ cita.raw.fotos_finales.length - 3 }}
                   </div>
                </div>
                <button @click="$emit('descargar-evidencias', cita.id)" class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-sky-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-sky-900/50 transition-colors border border-blue-100 dark:border-blue-800/50 group/dl" title="Descargar Todas las Fotos (ZIP)">
                  <svg class="w-4 h-4 transition-transform group-hover/dl:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                </button>
              </div>
              <div v-else class="text-[9px] font-black text-slate-300 dark:text-slate-700 uppercase tracking-wide italic">
                Sin Evidencias
              </div>
            </td>

            <!-- Acciones -->
            <td class="px-8 py-6 text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-2 transition-all duration-300">
                <button @click="$emit('ver-detalles', cita)" class="h-11 px-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 rounded-2xl transition-all shadow-sm flex items-center justify-center gap-2 group/btn" title="Ver Expediente / Ficha 360°">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span class="text-[10px] font-black uppercase tracking-wider">Detalles</span>
                </button>
                <button @click="$emit('editar', cita.id)" class="w-11 h-11 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-brand-600 hover:border-brand-600 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Modificar Planificación">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button v-if="puedeReprogramar(cita.raw)" @click="$emit('reprogramar', cita.raw)" class="w-11 h-11 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 text-slate-400 hover:text-purple-600 hover:border-purple-600 rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Reajustar Cronograma">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </button>
                <button v-if="puedeCancelar(cita.raw)" @click="$emit('cancelar', cita.raw)" class="w-11 h-11 bg-rose-50 dark:bg-rose-900/20 text-rose-400 hover:text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/40 border-2 border-transparent rounded-2xl transition-all shadow-sm flex items-center justify-center group/btn" title="Cancelar Cita">
                  <svg class="w-5 h-5 transition-transform group-hover/btn:scale-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          
          <!-- Empty State -->
          <tr v-if="items.length === 0">
            <td colspan="7" class="px-8 py-32 text-center">
              <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                <div class="w-24 h-24 bg-transparent dark:bg-slate-900 rounded-[2rem] flex items-center justify-center mb-8 shadow-inner border border-slate-100 dark:border-slate-800 animate-pulse">
                  <svg class="w-12 h-12 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.3em] mb-2">Sin Operaciones Registradas</h4>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-loose">
                  No se encontraron citas bajo los criterios estratégicos seleccionados actualmente.
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
  items: { type: Array, required: true }
})

defineEmits(['ver-detalles', 'editar', 'cancelar', 'reprogramar', 'ver-galeria', 'descargar-evidencias'])

// Utils
const getInitials = (name) => {
  if (!name) return 'N/A'
  const parts = name.split(' ')
  if (parts.length > 1) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatearHora = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

const storageSrc = (foto) => {
  if (!foto) return 'https://ui-avatars.com/api/?name=?&color=94a3b8&background=f1f5f9'
  const path = typeof foto === 'object' && foto !== null ? (foto.path || foto.url || '') : String(foto || '')
  const p = path.trim()
  if (!p) return 'https://ui-avatars.com/api/?name=?&color=94a3b8&background=f1f5f9'
  if (/^https?:\/\//i.test(p)) return p
  if (p.startsWith('/storage/')) return p
  if (p.startsWith('storage/')) return `/${p}`
  return `/storage/${p.replace(/^\/+/, '')}`
}

const handleImageError = (event) => {
  event.target.src = 'https://ui-avatars.com/api/?name=?&color=cbd5e1&background=f8fafc'
  event.target.classList.add('opacity-30', 'grayscale')
}

const isAtrasada = (cita) => {
  if (!cita || !cita.fecha_hora) return false
  const estadosAtrasables = ['pendiente', 'pendiente_asignacion', 'programado', 'programada', 'reprogramado', 'reprogramada']
  if (!estadosAtrasables.includes(cita.estado)) return false
  const citaDate = new Date(cita.fecha_hora)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return citaDate < today
}

const obtenerEstadoCitaClase = (citaObj) => {
  if (isAtrasada(citaObj)) return 'text-rose-600 dark:text-rose-100 border-rose-200 dark:border-rose-700 bg-rose-50 dark:bg-rose-900/60 shadow-sm'
  const estado = citaObj?.estado || 'desconocido'
  const clases = {
    'pendiente': 'text-orange-600 dark:text-orange-100 border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/60 shadow-sm',
    'pendiente_asignacion': 'text-brand-700 dark:text-brand-100 border-brand-200 dark:border-brand-800 bg-brand-100/30 dark:bg-brand-900/60 shadow-sm',
    'programado': 'text-blue-600 dark:text-blue-100 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/60 shadow-sm',
    'programada': 'text-blue-600 dark:text-blue-100 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/60 shadow-sm',
    'en_proceso': 'text-indigo-600 dark:text-indigo-100 border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/60 shadow-sm',
    'completado': 'text-emerald-600 dark:text-emerald-100 border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 shadow-sm',
    'completada': 'text-emerald-600 dark:text-emerald-100 border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 shadow-sm',
    'cancelado': 'text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-800 bg-transparent dark:bg-slate-900/40 shadow-sm',
    'cancelada': 'text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-800 bg-transparent dark:bg-slate-900/40 shadow-sm',
    'reprogramado': 'text-purple-600 dark:text-purple-100 border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/60 shadow-sm',
    'reprogramada': 'text-purple-600 dark:text-purple-100 border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/60 shadow-sm'
  }
  return clases[estado] || 'text-slate-400 border-slate-100 bg-transparent'
}

const obtenerEstadoCitaLabel = (citaObj) => {
  if (isAtrasada(citaObj)) return 'Atrasada / Urgente'
  const estado = citaObj?.estado || 'desconocido'
  const labels = {
    'pendiente': 'Pendiente',
    'pendiente_asignacion': 'Sin Asignar',
    'programado': 'Programado',
    'programada': 'Programada',
    'en_proceso': 'En Proceso',
    'completado': 'Completado',
    'completada': 'Completada',
    'cancelado': 'Cancelado',
    'cancelada': 'Cancelada',
    'reprogramado': 'Reajustado',
    'reprogramada': 'Reajustada'
  }
  return labels[estado] || 'Desconocido'
}

const puedeReprogramar = (cita) => {
  const estados = ['pendiente', 'programado', 'programada', 'pendiente_asignacion', 'reprogramado', 'reprogramada']
  return estados.includes(cita.estado)
}

const puedeCancelar = (cita) => {
  if (!cita) return false
  return !['completado', 'completada', 'cancelado', 'cancelada'].includes(cita.estado)
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
