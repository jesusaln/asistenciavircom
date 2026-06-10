<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        class="bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-hidden outline-none border border-slate-100 dark:border-slate-800 transition-all duration-300"
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >
        <!-- Header con gradiente Premium -->
        <div class="relative px-10 py-10 border-b border-slate-100 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}08 0%, ${colors.secundario}05 100%)` }">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                <div class="flex items-center space-x-6">
                    <div class="w-20 h-20 rounded-[2rem] flex items-center justify-center shadow-2xl transform transition-transform hover:scale-105" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <h2 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ selected?.nombre_razon_social }}</h2>
                            <span :class="selected?.activo ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600'" class="px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] border border-current">
                                {{ selected?.activo ? 'Operativo' : 'Suspendido' }}
                            </span>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">
                            {{ selected?.rfc || 'Sin Identificación Fiscal' }} • Socio desde {{ formatearFecha(selected?.created_at) }}
                        </p>
                    </div>
                </div>

                <button @click="onClose" class="p-4 rounded-2xl bg-transparent dark:bg-slate-900 text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all shadow-inner border border-slate-100 dark:border-slate-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto max-h-[calc(92vh-160px)] custom-scrollbar">
            <div class="p-10 grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Columna Principal: Datos Generales -->
                <div class="lg:col-span-8 space-y-10">
                    <!-- Grid de Información Estratégica -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contacto Directo -->
                        <div class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 group hover:bg-white dark:hover:bg-slate-900 transition-all shadow-sm hover:shadow-xl">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] block mb-6">Contacto Directo</span>
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-blue-600 shadow-sm border border-slate-100 dark:border-slate-700">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Correo Electrónico</p>
                                        <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ selected?.email || 'No registrado' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-emerald-600 shadow-sm border border-slate-100 dark:border-slate-700">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Teléfono Móvil</p>
                                        <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ selected?.telefono || 'No registrado' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Perfil Fiscal -->
                        <div class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 group hover:bg-white dark:hover:bg-slate-900 transition-all shadow-sm hover:shadow-xl">
                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] block mb-6">Perfil Fiscal</span>
                            <div class="space-y-6">
                                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800/50">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Régimen</span>
                                    <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ selected?.tipo_persona === 'moral' ? 'Persona Moral' : 'Persona Física' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800/50">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">RFC</span>
                                    <span class="text-xs font-mono font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected?.rfc || '---' }}</span>
                                </div>
                                <div v-if="selected?.curp" class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800/50">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">CURP</span>
                                    <span class="text-xs font-mono font-black text-slate-900 dark:text-white uppercase">{{ selected.curp }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ubicación y Domicilio -->
                    <div class="p-10 bg-transparent/30 dark:bg-slate-900/50 rounded-[3rem] border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center text-rose-600 shadow-sm border border-slate-100 dark:border-slate-800">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Domicilio Fiscal</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Ubicación oficial para facturación</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-6">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Calle y Número</p>
                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ selected?.calle }} {{ selected?.numero_exterior }}{{ selected?.numero_interior ? ` - ${selected.numero_interior}` : '' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Colonia / Asentamiento</p>
                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ selected?.colonia || '---' }}</p>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Municipio y Estado</p>
                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ selected?.municipio }}, {{ selected?.estado }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Código Postal</p>
                                    <p class="text-sm font-black text-blue-600 dark:text-blue-400 uppercase">{{ selected?.codigo_postal || '---' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Lateral: Resumen y Acciones -->
                <div class="lg:col-span-4 space-y-8">
                    <!-- Card de Estatus Operativo -->
                    <div class="p-8 bg-slate-900 dark:bg-white rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 dark:bg-slate-900/5 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                        
                        <h3 class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-10">Socio Comercial</h3>
                        
                        <div class="space-y-8">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 rounded-[1.5rem] bg-white/10 dark:bg-slate-100 flex items-center justify-center font-black text-2xl text-white dark:text-slate-900 shadow-xl border border-white/10 dark:border-slate-200">
                                    {{ (selected?.nombre_razon_social || '?').substring(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-white dark:text-slate-900 uppercase leading-tight mb-1">{{ selected?.nombre_razon_social }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <p class="text-[9px] font-bold text-white/50 dark:text-slate-400 uppercase tracking-wide">Activo en Sistema</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-white/10 dark:border-slate-100">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-wide mb-2">RFC Registrado</p>
                                <p class="text-2xl font-black text-white dark:text-slate-900 tracking-tighter">{{ selected?.rfc || 'XAXX010101000' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Auditoría y Registro -->
                    <div class="p-8 bg-transparent dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <h4 class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-6">Auditoría del Perfil</h4>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase">Registro Inicial</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">{{ formatearFechaCompleta(selected?.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase">Última Actualización</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">{{ formatearFechaCompleta(selected?.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Estratégicas -->
                    <div class="grid grid-cols-1 gap-4 pt-4">
                        <button @click="$emit('editar', selected?.id)" class="w-full py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-[1.5rem] font-black uppercase text-[10px] tracking-[0.2em] shadow-2xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Editar Perfil
                        </button>
                        
                        <button @click="$emit('toggle', selected?.id)" class="w-full py-5 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-[1.5rem] font-black uppercase text-[10px] tracking-[0.2em] text-slate-600 hover:text-emerald-600 dark:hover:text-emerald-400 hover:border-emerald-600 dark:hover:border-emerald-900 transition-all flex items-center justify-center gap-3 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Alternar Estatus
                        </button>

                        <button @click="onClose" class="w-full py-4 font-black text-slate-400 hover:text-slate-900 dark:hover:text-white uppercase text-[10px] tracking-wide transition-colors">
                            Cerrar Expediente
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
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

const { colors } = useCompanyColors()

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null }
})

const emit = defineEmits(['close', 'editar', 'toggle'])

const modalRef = ref(null)
const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

const onClose = () => emit('close')
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const formatearFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
}

const formatearFechaCompleta = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}
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
