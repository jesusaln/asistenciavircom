<template>
  <Head :title="`Kardex de Vacaciones — ${empleado.name}`" />
  
  <div class="registro-empleado-view min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500 py-10 px-6 lg:px-12" :style="cssVars">
    <div class="max-w-[1400px] mx-auto">
      
      <!-- Header Premium -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="flex items-center gap-5">
           <div class="w-16 h-16 rounded-[1.5rem] bg-indigo-500 flex items-center justify-center text-white shadow-2xl shadow-indigo-500/20">
              <FontAwesomeIcon icon="id-card-alt" size="lg" />
           </div>
           <div>
              <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Registro de Vacaciones</h1>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Expediente Individual: <span class="font-black text-indigo-500">{{ empleado.name }}</span></p>
           </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 bg-white dark:bg-slate-900 px-6 py-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Periodo:</label>
            <input
              type="number"
              v-model.number="anioLocal"
              class="w-20 bg-transparent border-none focus:ring-0 text-sm font-black text-slate-900 dark:text-white p-0"
              @change="cambiarAnio"
            />
          </div>
          <Link :href="route('vacaciones.index')" class="px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-slate-200 transition-all flex items-center">
             <FontAwesomeIcon icon="arrow-left" class="mr-3" />
             Volver
          </Link>
        </div>
      </div>

      <!-- Métricas del Periodo Premium -->
      <div v-if="registro" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-12 animate-in fade-in slide-in-from-bottom-4 duration-700">
        <!-- Año -->
        <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 shadow-xl">
           <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Referencia Temporal</div>
           <div class="text-2xl font-black text-slate-900 dark:text-white">Año {{ anio }}</div>
        </div>
        
        <!-- Correspondientes -->
        <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 shadow-xl">
           <div class="text-[9px] font-black text-indigo-500 uppercase tracking-widest mb-4">Días Devengados</div>
           <div class="text-4xl font-black text-slate-900 dark:text-white">{{ registro.dias_correspondientes ?? 0 }} <span class="text-xs text-slate-400">Días</span></div>
        </div>

        <!-- Disponibles -->
        <div class="bg-emerald-500/5 dark:bg-emerald-500/10 p-8 rounded-[2rem] border border-emerald-200/30 dark:border-emerald-500/20 shadow-xl">
           <div class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-4">Saldo Disponible</div>
           <div class="text-4xl font-black text-emerald-700 dark:text-emerald-400">{{ registro.dias_disponibles ?? 0 }} <span class="text-xs opacity-60">Días</span></div>
        </div>

        <!-- Utilizados -->
        <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 shadow-xl">
           <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Días Gozados</div>
           <div class="text-4xl font-black text-slate-900 dark:text-white">{{ registro.dias_utilizados ?? 0 }} <span class="text-xs text-slate-400">Días</span></div>
        </div>

        <!-- Restantes (Visualización de Alerta) -->
        <div class="p-8 rounded-[2rem] border shadow-xl transition-all duration-500" :class="getRestantesBg(registro.dias_disponibles - registro.dias_utilizados)">
           <div class="text-[9px] font-black uppercase tracking-widest mb-4 opacity-60">Balance Remanente</div>
           <div class="text-4xl font-black" :class="getDiasRestantesColor(registro.dias_disponibles - registro.dias_utilizados)">
             {{ (registro.dias_disponibles - registro.dias_utilizados) >= 0 ? (registro.dias_disponibles - registro.dias_utilizados) : 0 }} <span class="text-xs opacity-50">Días</span>
           </div>
        </div>
      </div>

      <!-- Empty State para Registro -->
      <div v-else class="bg-amber-500/5 dark:bg-amber-500/10 border border-amber-200/30 dark:border-amber-500/20 rounded-[2.5rem] p-12 mb-12 flex flex-col items-center">
        <div class="w-16 h-16 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 mb-6">
           <FontAwesomeIcon icon="exclamation-triangle" size="2x" />
        </div>
        <h3 class="text-xl font-black text-amber-700 dark:text-amber-500 uppercase tracking-tight mb-2">Registro de Saldo Inexistente</h3>
        <p class="text-sm font-medium text-amber-600 dark:text-amber-400 max-w-md text-center">No se ha formalizado la carga de días para el periodo {{ anio }}. Contacta al departamento de Capital Humano para la regularización.</p>
      </div>

      <!-- Tablas de Control Premium -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 animate-in fade-in slide-in-from-bottom-10 duration-1000">
        
        <!-- Solicitudes de Vacaciones -->
        <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl overflow-hidden flex flex-col">
           <div class="px-10 py-6 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/30 dark:bg-slate-900/40 flex items-center gap-3">
              <div class="w-2 h-6 bg-indigo-500 rounded-full"></div>
              <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Solicitudes Documentadas</h2>
           </div>
           
           <div class="overflow-x-auto flex-1">
              <table v-if="vacaciones && vacaciones.length" class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/40">
                <thead class="bg-slate-50/50 dark:bg-slate-950/50">
                  <tr>
                    <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Rango de Fecha</th>
                    <th class="px-8 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Impacto</th>
                    <th class="px-8 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Estatus</th>
                    <th class="px-8 py-4 text-right text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Registro</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/20">
                  <tr v-for="vacacion in vacaciones" :key="vacacion.id" class="hover:bg-indigo-500/[0.02] transition-colors duration-300">
                    <td class="px-8 py-6">
                      <div class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ formatDateShort(vacacion.fecha_inicio) }}</div>
                      <div class="text-[9px] font-bold text-slate-400 uppercase">al {{ formatDateShort(vacacion.fecha_fin) }}</div>
                    </td>
                    <td class="px-8 py-6 text-center">
                      <div class="text-xs font-black text-slate-700 dark:text-slate-300">{{ vacacion.dias_solicitados }} Días</div>
                    </td>
                    <td class="px-8 py-6 text-center text-nowrap">
                      <span :class="getEstadoClasses(vacacion.estado)" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-current opacity-70">
                        {{ getEstadoLabel(vacacion.estado) }}
                      </span>
                    </td>
                    <td class="px-8 py-6 text-right">
                       <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ formatDateShort(vacacion.created_at) }}</div>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="p-20 text-center flex flex-col items-center gap-4">
                 <FontAwesomeIcon icon="folder-open" size="3x" class="text-slate-100 dark:text-slate-800" />
                 <p class="text-[10px] font-black text-slate-300 dark:text-slate-600 uppercase tracking-[0.3em]">No hay solicitudes vigentes</p>
              </div>
           </div>
        </div>

        <!-- Ajustes Manuales -->
        <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl overflow-hidden flex flex-col">
           <div class="px-10 py-6 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/30 dark:bg-slate-900/40 flex items-center gap-3">
              <div class="w-2 h-6 bg-orange-500 rounded-full"></div>
              <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Bitácora de Ajustes</h2>
           </div>
           
           <div class="overflow-x-auto flex-1 text-nowrap">
              <table v-if="ajustes && ajustes.length" class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/40">
                <thead class="bg-slate-50/50 dark:bg-slate-950/50">
                  <tr>
                    <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Fecha Auditoría</th>
                    <th class="px-8 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Año</th>
                    <th class="px-8 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Variación</th>
                    <th class="px-8 py-4 text-right text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Ejecutado por</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/20">
                  <tr v-for="a in ajustes" :key="a.id" class="hover:bg-orange-500/[0.02] transition-colors duration-300">
                    <td class="px-8 py-6 text-[10px] font-black text-slate-500 dark:text-slate-400">{{ formatDateFull(a.created_at) }}</td>
                    <td class="px-8 py-6 text-center text-xs font-black text-slate-900 dark:text-white">{{ a.anio }}</td>
                    <td class="px-8 py-6 text-center">
                       <span :class="a.dias >= 0 ? 'text-emerald-500 bg-emerald-500/10' : 'text-rose-500 bg-rose-500/10'" class="px-3 py-1 rounded-lg text-xs font-black border border-current">
                          {{ a.dias >= 0 ? '+' : '' }}{{ a.dias }}
                       </span>
                    </td>
                    <td class="px-8 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ a.creador?.name || 'Automático' }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="p-20 text-center flex flex-col items-center gap-4">
                 <FontAwesomeIcon icon="history" size="3x" class="text-slate-100 dark:text-slate-800" />
                 <p class="text-[10px] font-black text-slate-300 dark:text-slate-600 uppercase tracking-[0.3em]">No se documentan ajustes manuales</p>
              </div>
           </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  empleado: Object,
  anio: Number,
  registro: Object,
  ajustes: Array,
  vacaciones: {
    type: Array,
    default: () => []
  }
})

const { cssVars } = useCompanyColors()
const anioLocal = ref(props.anio)

const cambiarAnio = () => {
  router.get(route('registro-vacaciones.por-empleado', props.empleado.id), {
    anio: anioLocal.value
  }, { preserveState: true, preserveScroll: true })
}

const formatDateShort = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit', month: 'short', year: 'numeric'
    }).replace('.', '')
  } catch { return date }
}

const formatDateFull = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit', month: '2-digit', year: 'numeric'
    })
  } catch { return date }
}

const getEstadoClasses = (estado) => {
  const classes = {
    'pendiente': 'text-amber-500',
    'aprobada': 'text-emerald-500',
    'rechazada': 'text-rose-500',
  }
  return classes[estado] || 'text-slate-400'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'aprobada': 'Ratificada',
    'rechazada': 'Declinada',
  }
  return labels[estado] || 'N/D'
}

const getRestantesBg = (d) => {
  if (d > 10) return 'bg-emerald-500/5 dark:bg-emerald-500/10 border-emerald-200/30'
  if (d > 5) return 'bg-amber-500/5 dark:bg-amber-500/10 border-amber-200/30'
  return 'bg-rose-500/5 dark:bg-rose-500/10 border-rose-200/30'
}

const getDiasRestantesColor = (diasRestantes) => {
  if (diasRestantes > 10) return 'text-emerald-600'
  if (diasRestantes > 5) return 'text-amber-600'
  return 'text-rose-600'
}
</script>

<style scoped>
.registro-empleado-view {
  min-height: 100vh;
}
</style>
