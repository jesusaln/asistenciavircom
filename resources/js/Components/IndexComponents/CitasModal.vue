<template>
  <Transition name="modal-fade">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 md:p-8" @click.self="$emit('close')">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"></div>

      <!-- Modal Content -->
      <div class="relative w-full max-w-6xl bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-[0_32px_64px_-12px_rgba(0,0,0,0.5)] border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] transition-all transform scale-100">
        
        <!-- Premium Header -->
        <div class="px-8 py-8 border-b border-slate-100 dark:border-slate-800 bg-transparent/30 dark:bg-slate-900/50 flex items-center justify-between">
          <div class="flex items-center gap-6">
            <div class="w-16 h-16 rounded-3xl bg-blue-600 dark:bg-blue-500 shadow-xl shadow-sky-500/20 flex items-center justify-center text-white">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Expediente de Cita</h2>
                <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide border border-slate-200/50 dark:border-slate-700">#{{ selected.id }}</span>
              </div>
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mt-1.5 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                Sincronizado con sistema de operaciones
              </p>
            </div>
          </div>
          <button @click.stop.prevent="$emit('close')" class="w-12 h-12 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 dark:text-slate-600 hover:text-slate-900 dark:hover:text-white transition-all duration-300 flex items-center justify-center border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Scrollable Content -->
        <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
          <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
            
            <!-- Left Column: Core Data -->
            <div class="xl:col-span-8 space-y-8">
              
              <!-- Client & Tech Cards -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Socio Comercial -->
                <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 group">
                  <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Socio Comercial</p>
                  <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-lg font-black text-slate-900 dark:text-white transform group-hover:rotate-3 transition-transform">
                      {{ (selected.cliente?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                      <p class="text-sm font-black text-slate-900 dark:text-white uppercase leading-tight">{{ selected.cliente?.nombre_razon_social || 'N/A' }}</p>
                      <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wide">{{ selected.cliente?.rfc || 'Sin RFC' }}</p>
                      <div class="flex items-center gap-2 mt-2">
                        <span class="px-2 py-0.5 rounded-xl bg-blue-50 dark:bg-sky-900/30 text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected.cliente?.telefono || 'Sin Tel' }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Especialista -->
                <div class="p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800/50 hover:border-indigo-500/30 transition-all duration-500 group">
                  <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Especialista Asignado</p>
                  <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-lg font-black text-indigo-600 dark:text-indigo-400 overflow-hidden">
                      <img v-if="selected.tecnico?.profile_photo_url" :src="selected.tecnico.profile_photo_url" class="w-full h-full object-cover" />
                      <span v-else>{{ getInitials(selected.tecnico?.name) }}</span>
                    </div>
                    <div>
                      <p class="text-sm font-black text-slate-900 dark:text-white uppercase leading-tight">{{ selected.tecnico?.name || 'Por Asignar' }}</p>
                      <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wide">{{ selected.tecnico?.email || 'operaciones@vircom.com' }}</p>
                      <div class="flex items-center gap-2 mt-2">
                        <span class="px-2 py-0.5 rounded-xl bg-indigo-50 dark:bg-sky-900/30 text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase">Personal Técnico</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Location & Description -->
              <div class="space-y-6">
                <div class="p-8 bg-slate-950 dark:bg-slate-900 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden group">
                  <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </div>
                  <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4">Punto de Servicio</label>
                  <p class="text-lg font-black text-white leading-tight uppercase tracking-wider">{{ textoUbicacion(selected) || 'Dirección de cliente registrada' }}</p>
                  <div class="flex items-center gap-4 mt-6">
                    <a v-if="selected.direccion_google_maps" :href="selected.direccion_google_maps" target="_blank" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-[10px] font-black text-white uppercase tracking-wide transition-all border border-white/10 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V5.618a2 2 0 011.447-1.817L9 2l12 2v12l-12 4z"/></svg>
                      Ver en Maps
                    </a>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                   <div class="p-6 bg-rose-50/30 dark:bg-rose-900/10 rounded-[2rem] border border-rose-100/50 dark:border-rose-900/30">
                      <label class="block text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-wide mb-3">Diagnóstico Reportado</label>
                      <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-relaxed italic">"{{ selected.problema_reportado || 'No se especificó diagnóstico inicial.' }}"</p>
                   </div>
                   <div class="p-6 bg-blue-50/30 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100/50 dark:border-blue-900/30">
                      <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-3">Notas Internas</label>
                      <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ selected.notas_internas || 'Sin anotaciones adicionales.' }}</p>
                   </div>
                </div>
              </div>

              <!-- Reporte Final Section -->
              <div v-if="selected.trabajo_realizado" class="p-8 bg-emerald-50/30 dark:bg-emerald-900/10 rounded-[2.5rem] border border-emerald-100/50 dark:border-emerald-900/30">
                <div class="flex items-center gap-3 mb-6">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </div>
                  <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-400 uppercase tracking-wider">Resolución Técnica</h3>
                </div>
                <div class="bg-white/50 dark:bg-slate-900/50 p-6 rounded-3xl border border-emerald-100/50 dark:border-emerald-800/30">
                   <p class="text-sm font-black text-slate-900 dark:text-white leading-relaxed whitespace-pre-wrap">{{ selected.trabajo_realizado }}</p>
                </div>
              </div>

              <!-- Gallery Section -->
              <div v-if="selected.fotos_finales?.length > 0" class="space-y-4">
                 <div class="flex items-center justify-between px-2">
                   <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Evidencias de Campo ({{ selected.fotos_finales.length }})</label>
                   <button @click="$emit('descargar-evidencias', selected.id)" class="text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide hover:underline flex items-center gap-2">
                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                     Bajar archivo ZIP
                   </button>
                 </div>
                 <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(foto, idx) in selected.fotos_finales" :key="idx" class="aspect-square rounded-[1.5rem] overflow-hidden border border-slate-100 dark:border-slate-800 bg-transparent dark:bg-slate-900 group cursor-pointer hover:shadow-2xl transition-all duration-500" @click="$emit('ver-galeria', [foto], 'Evidencia')">
                       <img :src="storageSrc(foto)" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" loading="lazy" />
                       <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                 </div>
              </div>
            </div>

            <!-- Right Column: Specs & Status -->
            <div class="xl:col-span-4 space-y-6">
              <!-- Summary Card -->
              <div class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800/50 space-y-8 sticky top-0">
                
                <!-- Status Big Badge -->
                <div class="text-center">
                   <div :class="obtenerEstadoCitaClase(selected)" class="inline-flex items-center px-6 py-3 rounded-full text-xs font-black uppercase tracking-[0.2em] border-2 shadow-2xl mb-4">
                     {{ obtenerEstadoCitaLabel(selected) }}
                   </div>
                   <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Estatus Actual</p>
                </div>

                <!-- Info Grid -->
                <div class="space-y-6 divide-y divide-slate-100 dark:divide-slate-800">
                  <div class="pt-6 first:pt-0">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3">Cronometría</p>
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">Inicio:</span>
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ formatearFecha(selected.fecha_hora) }} · {{ formatearHora(selected.fecha_hora) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">Fin Est:</span>
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ formatearHora(selected.fecha_hora_fin) || 'No definido' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-6">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3">Identificación Operativa</p>
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">Folio:</span>
                        <span class="text-sm font-mono font-black text-blue-600 dark:text-blue-400">{{ selected.folio || 'S/F' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">Servicio:</span>
                        <span class="text-[10px] font-black bg-sky-100 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 px-3 py-1 rounded-xl uppercase tracking-wider">{{ selected.tipo_servicio || 'GENERAL' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">Prioridad:</span>
                        <span :class="clasePrioridad(selected.prioridad)" class="text-[10px] font-black px-3 py-1 rounded-xl uppercase tracking-wider">{{ selected.prioridad || 'Media' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-6">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3">Activo de Servicio</p>
                    <div class="p-4 bg-white dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                       <p class="text-[11px] font-black text-slate-900 dark:text-white uppercase leading-tight">{{ selected.marca_equipo || 'Marca N/A' }} {{ selected.modelo_equipo || 'S/M' }}</p>
                       <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-wide">{{ selected.tipo_equipo || 'Equipo General' }}</p>
                    </div>
                  </div>

                  <!-- Auditoría en miniatura -->
                  <div class="pt-6">
                     <div class="flex flex-col gap-1.5 opacity-60">
                        <div class="flex items-center gap-2">
                           <div class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                           <p class="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase">Creado por {{ auditoria?.creado_por }} el {{ formatearFecha(auditoria?.creado_en) }}</p>
                        </div>
                        <div v-if="auditoria?.actualizado_en" class="flex items-center gap-2">
                           <div class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                           <p class="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase">Actualizado el {{ formatearFecha(auditoria?.actualizado_en) }}</p>
                        </div>
                     </div>
                  </div>
                </div>

                <!-- Strategic Actions -->
                <div class="grid grid-cols-2 gap-3 pt-4">
                   <button @click="$emit('editar', selected.id)" class="px-4 py-3 bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-2xl text-[10px] font-black text-slate-700 dark:text-white uppercase tracking-wide hover:border-slate-900 dark:hover:border-slate-700 transition-all active:scale-95">Configurar</button>
                   <Link :href="route('citas.show', selected.id)" class="px-4 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-wide text-center hover:opacity-90 transition-all active:scale-95 flex items-center justify-center">Ficha 360°</Link>
                </div>
                <button v-if="puedeCancelar(selected)" @click="$emit('cancelar', selected)" class="w-full py-3 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-[9px] font-black uppercase tracking-[0.3em] rounded-2xl hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-all">Cancelar Cita</button>
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
import { Link } from '@inertiajs/vue3'

defineProps({
  show: { type: Boolean, required: true },
  selected: { type: Object, default: () => ({}) },
  auditoria: { type: Object, default: () => ({}) }
})

defineEmits(['close', 'editar', 'cancelar', 'descargar-evidencias', 'ver-galeria'])

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

const storageSrc = (path) => {
  if (!path) return ''
  const p = path.trim()
  if (/^https?:\/\//i.test(p)) return p
  if (p.startsWith('/storage/')) return p
  if (p.startsWith('storage/')) return `/${p}`
  return `/storage/${p.replace(/^\/+/, '')}`
}

const textoUbicacion = (c) => {
  if (!c) return ''
  const ds = c.direccion_servicio && String(c.direccion_servicio).trim()
  if (ds) return ds
  const partes = []
  if (c.direccion_calle) partes.push(String(c.direccion_calle).trim())
  if (c.direccion_colonia) partes.push(`Col. ${String(c.direccion_colonia).trim()}`)
  if (c.direccion_cp) partes.push(`C.P. ${String(c.direccion_cp).trim()}`)
  if (partes.length) return partes.join(' · ')
  return ''
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
  if (isAtrasada(citaObj)) return 'text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-900/10'
  const estado = citaObj?.estado || 'desconocido'
  const clases = {
    'pendiente': 'text-brand-600 dark:text-brand-400 border-brand-100 dark:border-brand-900/30 bg-brand-50/50 dark:bg-brand-900/10',
    'pendiente_asignacion': 'text-brand-700 dark:text-brand-500 border-brand-200 dark:border-brand-800/30 bg-brand-100/30 dark:bg-brand-800/10',
    'programado': 'text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/30 bg-sky-50/50 dark:bg-sky-900/10',
    'programada': 'text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/30 bg-sky-50/50 dark:bg-sky-900/10',
    'en_proceso': 'text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/30 bg-sky-50/50 dark:bg-sky-900/10',
    'completado': 'text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-900/10',
    'completada': 'text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-900/10',
    'cancelado': 'text-slate-400 dark:text-slate-500 border-slate-100 dark:border-slate-800 bg-transparent dark:bg-slate-900/50',
    'cancelada': 'text-slate-400 dark:text-slate-500 border-slate-100 dark:border-slate-800 bg-transparent dark:bg-slate-900/50',
    'reprogramado': 'text-purple-600 dark:text-purple-400 border-purple-100 dark:border-purple-900/30 bg-purple-50/50 dark:bg-purple-900/10',
    'reprogramada': 'text-purple-600 dark:text-purple-400 border-purple-100 dark:border-purple-900/30 bg-purple-50/50 dark:bg-purple-900/10'
  }
  return clases[estado] || 'text-slate-400 border-slate-100 bg-transparent'
}

const obtenerEstadoCitaLabel = (citaObj) => {
  if (isAtrasada(citaObj)) return 'CRÍTICO / ATRASADO'
  const estado = citaObj?.estado || 'desconocido'
  const labels = {
    'pendiente': 'PENDIENTE DE ATENCIÓN',
    'pendiente_asignacion': 'SIN ESPECIALISTA',
    'programado': 'PROGRAMADO EN AGENDA',
    'programada': 'PROGRAMADO EN AGENDA',
    'en_proceso': 'OPERACIÓN EN CURSO',
    'completado': 'OPERACIÓN COMPLETADA',
    'completada': 'OPERACIÓN COMPLETADA',
    'cancelado': 'REGISTRO REVOCADO',
    'cancelada': 'REGISTRO REVOCADO',
    'reprogramado': 'CRONOGRAMA REAJUSTADO',
    'reprogramada': 'CRONOGRAMA REAJUSTADO'
  }
  return labels[estado] || 'ESTATUS DESCONOCIDO'
}

const clasePrioridad = (p) => {
  const m = {
    alta: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
    urgente: 'bg-rose-600 text-white animate-pulse',
    baja: 'bg-slate-100 dark:bg-slate-800 text-slate-500',
    media: 'bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400'
  }
  return m[p] || m.media
}

const puedeCancelar = (cita) => {
  if (!cita) return false
  return !['completado', 'completada', 'cancelado', 'cancelada'].includes(cita.estado)
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: translateY(20px) scale(0.98); }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
</style>
