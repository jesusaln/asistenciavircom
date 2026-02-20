<template>
  <Head title="Detalles de Vacaciones" />
  
  <div class="vacaciones-show min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500 py-10 px-6 lg:px-12" :style="cssVars">
    <div class="max-w-6xl mx-auto">
      
      <!-- Header Premium -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="flex items-center gap-5">
           <div class="w-14 h-14 rounded-2xl bg-indigo-500 flex items-center justify-center text-white shadow-2xl shadow-indigo-500/20">
              <FontAwesomeIcon icon="file-signature" size="lg" />
           </div>
           <div>
              <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Expediente de Vacaciones</h1>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Auditoría detallada y validación de periodo solicitado</p>
           </div>
        </div>

        <Link
          :href="route('vacaciones.index')"
          class="px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center shadow-sm"
        >
          <FontAwesomeIcon icon="arrow-left" class="mr-3" />
          Regresar al Panel
        </Link>
      </div>

      <!-- Grid de Información Principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 animate-in fade-in slide-in-from-bottom-6 duration-1000">
        
        <!-- Columna Lateral: Perfil y Estado -->
        <div class="lg:col-span-1 space-y-8">
           <!-- Card de Usuario -->
           <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl text-center">
              <div class="relative inline-block mb-6">
                 <div class="w-24 h-24 rounded-[2.5rem] bg-indigo-500/10 flex items-center justify-center border-4 border-white dark:border-slate-800 overflow-hidden shadow-lg mx-auto">
                    <img v-if="vacacion.empleado?.profile_photo_url" :src="vacacion.empleado.profile_photo_url" class="w-full h-full object-cover">
                    <span v-else class="text-3xl font-black text-indigo-500">{{ vacacion.empleado?.name?.charAt(0) }}</span>
                 </div>
                 <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl border-4 border-white dark:border-slate-900 flex items-center justify-center shadow-lg" :class="getEstadoDotColor(vacacion.estado || 'pendiente')">
                    <FontAwesomeIcon :icon="getEstadoIcon(vacacion.estado || 'pendiente')" class="text-white text-sm" />
                 </div>
              </div>
              <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight mb-1">{{ vacacion.empleado?.name || 'Incompleto' }}</h2>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ vacacion.empleado?.puesto || 'Puesto no Definido' }}</p>
              
              <div class="mt-8 pt-6 border-t border-slate-50 dark:border-slate-800/60">
                 <div :class="getEstadoClasses(vacacion.estado || 'pendiente')" class="w-full py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border">
                   {{ getEstadoLabel(vacacion.estado || 'pendiente') }}
                 </div>
              </div>
           </div>

           <!-- Card de Registro Anual -->
           <div v-if="props.registroVacaciones" class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-2xl text-white relative overflow-hidden group">
              <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
              <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60 mb-6">Status Anual {{ props.registroVacaciones.anio }}</h3>
              
              <div class="space-y-4">
                 <div class="flex justify-between items-center">
                    <span class="text-xs font-bold opacity-80 uppercase tracking-widest">Saldo Inicial</span>
                    <span class="text-lg font-black">{{ props.registroVacaciones.dias_correspondientes }} Días</span>
                 </div>
                 <div class="flex justify-between items-center py-4 border-y border-white/10">
                    <span class="text-xs font-bold opacity-80 uppercase tracking-widest">Utilizados</span>
                    <span class="text-lg font-black">{{ props.registroVacaciones.dias_utilizados }} Días</span>
                 </div>
                 <div class="flex justify-between items-center mt-4">
                    <span class="text-xs font-black uppercase tracking-widest">Disponibilidad Actual</span>
                    <div class="bg-white/20 px-4 py-2 rounded-xl text-xl font-black">
                       {{ props.registroVacaciones.dias_disponibles }}
                    </div>
                 </div>
              </div>
           </div>
        </div>

        <!-- Columna Principal: Detalles Operativos -->
        <div class="lg:col-span-2 space-y-8">
           <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl overflow-hidden">
              <div class="px-10 py-8 border-b border-slate-50 dark:border-slate-800/60 flex items-center gap-4 bg-slate-50/30 dark:bg-slate-900/40">
                 <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                    <FontAwesomeIcon icon="calendar-check" />
                 </div>
                 <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Especificaciones Técnicas</h2>
              </div>

              <div class="p-10 divide-y divide-slate-50 dark:divide-slate-800/60">
                 <div class="grid grid-cols-2 py-6">
                    <div class="space-y-1">
                       <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Periodo de Ausencia</p>
                       <p class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight">
                          {{ formatDateShort(vacacion.fecha_inicio) }} — {{ formatDateShort(vacacion.fecha_fin) }}
                       </p>
                    </div>
                    <div class="text-right space-y-1">
                       <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Impacto en Nómina</p>
                       <p class="text-2xl font-black text-emerald-500 uppercase tracking-tighter">{{ vacacion.dias_solicitados }} Días Calendario</p>
                    </div>
                 </div>

                 <div class="py-10 space-y-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Declaración de Motivo</p>
                    <div class="p-6 bg-slate-50 dark:bg-slate-950/50 rounded-2xl border border-slate-100 dark:border-slate-800 font-medium text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                       {{ vacacion.motivo || 'No se documentó una justificación oficial para este requerimiento.' }}
                    </div>
                 </div>

                 <div v-if="vacacion.observaciones" class="py-10 space-y-4">
                    <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">Resolución del Dictaminador</p>
                    <div class="p-6 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-2xl border border-indigo-200/30 dark:border-indigo-500/20 font-bold text-indigo-600 dark:text-indigo-400 text-sm italic">
                       "{{ vacacion.observaciones }}"
                    </div>
                 </div>

                 <div v-if="vacacion.aprobador" class="grid grid-cols-2 pt-10">
                    <div class="space-y-1">
                       <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Validado Por</p>
                       <p class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ vacacion.aprobador.name }}</p>
                    </div>
                    <div class="text-right space-y-1">
                       <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha Resolución</p>
                       <p class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ formatDateWithTime(vacacion.fecha_aprobacion) }}</p>
                    </div>
                 </div>
              </div>
           </div>

           <!-- Tabla de Ajustes Históricos -->
           <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl overflow-hidden">
              <div class="px-10 py-8 border-b border-slate-50 dark:border-slate-800/60 flex items-center justify-between bg-slate-50/30 dark:bg-slate-900/40">
                 <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center text-orange-500">
                       <FontAwesomeIcon icon="history" />
                    </div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Correcciones de Saldo Anual</h2>
                 </div>
              </div>

              <div class="overflow-x-auto">
                 <table v-if="(ajustesVacaciones && ajustesVacaciones.length)" class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/40">
                    <thead class="bg-slate-50/50 dark:bg-slate-950/50">
                       <tr>
                          <th class="px-10 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Fecha</th>
                          <th class="px-10 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Ajuste</th>
                          <th class="px-10 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Justificación</th>
                          <th class="px-10 py-4 text-right text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Auditoría</th>
                       </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/20 text-nowrap">
                       <tr v-for="a in ajustesVacaciones" :key="a.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                          <td class="px-10 py-4 text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ formatDateShort(a.created_at) }}</td>
                          <td class="px-10 py-4 text-center">
                             <span :class="a.dias >= 0 ? 'text-emerald-500' : 'text-rose-500'" class="text-sm font-black">
                                {{ a.dias >= 0 ? '+' : '' }}{{ a.dias }} Días
                             </span>
                          </td>
                          <td class="px-10 py-4 text-[11px] font-medium text-slate-600 dark:text-slate-400 italic">{{ a.motivo || '—' }}</td>
                          <td class="px-10 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ a.creador?.name || 'Sistema' }}</td>
                       </tr>
                    </tbody>
                 </table>
                 <div v-else class="p-16 text-center">
                    <p class="text-[11px] font-black text-slate-300 dark:text-slate-600 uppercase tracking-[0.3em]">No se documentan ajustes para este periodo</p>
                 </div>
              </div>
           </div>
        </div>
      </div>

      <!-- Acciones de Gestión Corporativa (Admin) -->
      <div v-if="vacacion.estado === 'pendiente' && isAdmin" class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-white dark:bg-slate-900 p-3 rounded-[2.5rem] shadow-[0_20px_60px_rgba(0,0,0,0.4)] border border-slate-100 dark:border-slate-800/60 animate-in slide-in-from-bottom-10 duration-700 z-50">
        <div class="flex items-center gap-4">
           <button
             @click="rechazarVacacion"
             class="px-10 py-5 bg-rose-600 text-white rounded-[2rem] font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-rose-500/20 flex items-center"
           >
             <FontAwesomeIcon icon="times-circle" class="mr-3" />
             Declinar Solicitud
           </button>
           
           <button
             @click="aprobarVacacion"
             class="px-10 py-5 bg-emerald-600 text-white rounded-[2rem] font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-emerald-500/20 flex items-center"
           >
             <FontAwesomeIcon icon="check-circle" class="mr-3" />
             Ratificar Comisión
           </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, router, Link, usePage } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  vacacion: Object,
  registroVacaciones: Object,
  ajustesVacaciones: { type: Array, default: () => [] }
})

const { cssVars } = useCompanyColors()
const page = usePage()

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

const isAdmin = computed(() => {
    return page.props.auth.user && 
           (page.props.auth.user.is_admin || 
           (page.props.auth.user.roles && page.props.auth.user.roles.some(role => ['admin', 'super-admin'].includes(role.name))))
})

const aprobarVacacion = () => {
  if (confirm('¿Ratificar formalmente este periodo vacacional en los registros corporativos?')) {
    router.post(route('vacaciones.aprobar', props.vacacion.id), { observaciones: '' }, {
      onSuccess: () => notyf.success('Expediente ratificado exitosamente'),
      onError: () => notyf.error('Error crítico al procesar la aprobación')
    })
  }
}

const rechazarVacacion = () => {
  const observaciones = prompt('Justificación institucional del rechazo operacional:')
  if (observaciones !== null && confirm('¿Confirmar el declive oficial de esta solicitud?')) {
    router.post(route('vacaciones.rechazar', props.vacacion.id), { observaciones: observaciones || '' }, {
      onSuccess: () => notyf.success('Solicitud declinada y documentada'),
      onError: () => notyf.error('Error al procesar la declinación')
    })
  }
}

const formatDateShort = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit', month: 'short', year: 'numeric'
    }).replace('.', '')
  } catch { return 'Err.' }
}

const formatDateWithTime = (date) => {
  if (!date) return 'Sin Registro'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    }).replace('.', '')
  } catch { return 'Err.' }
}

const getEstadoClasses = (estado) => {
  const classes = {
    'pendiente': 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    'aprobada': 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    'rechazada': 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
  }
  return classes[estado] || 'bg-slate-100 text-slate-500 border-slate-200'
}

const getEstadoDotColor = (estado) => {
  const dots = {
    'pendiente': 'bg-amber-500',
    'aprobada': 'bg-emerald-500',
    'rechazada': 'bg-rose-500'
  }
  return dots[estado] || 'bg-slate-400'
}

const getEstadoIcon = (estado) => {
  const icons = {
    'pendiente': 'hourglass-half',
    'aprobada': 'check',
    'rechazada': 'times'
  }
  return icons[estado] || 'question'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Estatus: En Revisión',
    'aprobada': 'Estatus: Ratificada',
    'rechazada': 'Estatus: Declinada'
  }
  return labels[estado] || 'Indefinido'
}
</script>

<style scoped>
.vacaciones-show {
  min-height: 100vh;
}
</style>
