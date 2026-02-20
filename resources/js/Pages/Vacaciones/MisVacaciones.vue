<template>
  <Head title="Mis Vacaciones" />
  
  <div class="mis-vacaciones min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500 py-10 px-6 lg:px-12" :style="cssVars">
    <div class="max-w-[1400px] mx-auto">
      
      <!-- Header Premium -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-14 animate-in fade-in slide-in-from-top-4 duration-700">
        <div>
          <div class="flex items-center gap-4 mb-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-500 flex items-center justify-center text-white shadow-2xl shadow-blue-500/20">
              <FontAwesomeIcon icon="user-clock" size="lg" />
            </div>
            <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Mis Vacaciones</h1>
          </div>
          <p class="text-slate-500 dark:text-slate-400 font-medium ml-1">Bitácora personal y estatus de solicitudes laborales</p>
        </div>

        <Link
          :href="route('vacaciones.create')"
          class="group px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-2xl flex items-center"
        >
          <FontAwesomeIcon icon="plane-departure" class="mr-4 text-xs group-hover:rotate-12 transition-transform" />
          Nueva Solicitud
        </Link>
      </div>

      <!-- Métricas Personales Premium -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mb-14">
        <!-- Pendientes -->
        <div class="group bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-6">En Revisión</div>
           <div class="flex items-end justify-between">
              <div class="text-5xl font-black text-slate-900 dark:text-white">{{ vacaciones.data.filter(v => v.estado === 'pendiente').length }}</div>
              <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 group-hover:rotate-12 transition-transform">
                <FontAwesomeIcon icon="hourglass-half" size="xl" />
              </div>
           </div>
        </div>

        <!-- Aprobadas -->
        <div class="group bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-6">Confirmadas</div>
           <div class="flex items-end justify-between">
              <div class="text-5xl font-black text-slate-900 dark:text-white">{{ vacaciones.data.filter(v => v.estado === 'aprobada').length }}</div>
              <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:rotate-12 transition-transform">
                <FontAwesomeIcon icon="check-double" size="xl" />
              </div>
           </div>
        </div>

        <!-- Total -->
        <div class="group bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-6">Historial Total</div>
           <div class="flex items-end justify-between">
              <div class="text-5xl font-black text-slate-900 dark:text-white">{{ vacaciones.total }}</div>
              <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 group-hover:rotate-12 transition-transform">
                <FontAwesomeIcon icon="file-invoice" size="xl" />
              </div>
           </div>
        </div>
      </div>

      <!-- Filtros y Tabla Premium -->
      <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-1000">
        
        <!-- Header de Tabla con Filtro -->
        <div class="px-10 py-8 border-b border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-slate-900/40">
           <div class="flex items-center gap-3">
              <div class="w-2 h-8 bg-blue-500 rounded-full"></div>
              <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Registro Central de Solicitudes</h2>
           </div>
           <div class="w-full sm:w-72">
              <select
                v-model="filters.estado"
                @change="applyFilters"
                class="w-full px-6 py-4 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all appearance-none cursor-pointer"
              >
                <option value="">Visualizar Todos los Estados</option>
                <option value="pendiente">Fase: Pendiente</option>
                <option value="aprobada">Fase: Aprobada</option>
                <option value="rechazada">Fase: Rechazada</option>
              </select>
           </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/40">
            <thead class="bg-slate-50/50 dark:bg-slate-950/50">
              <tr>
                <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Cronograma Definido</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Duración</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Veredicto Corporativo</th>
                <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Justificación</th>
                <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Fecha Registro</th>
                <th class="px-10 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Gestión</th>
              </tr>
            </thead>
            
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/20">
              <template v-if="vacaciones.data.length > 0">
                <tr v-for="vacacion in vacaciones.data" :key="vacacion.id" class="group hover:bg-blue-500/[0.02] transition-colors duration-300">
                  <td class="px-10 py-8">
                    <div class="flex items-center gap-4">
                       <div class="flex flex-col">
                          <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ formatDate(vacacion.fecha_inicio) }}</span>
                          <span class="text-[10px] font-bold text-slate-400">al {{ formatDate(vacacion.fecha_fin) }}</span>
                       </div>
                    </div>
                  </td>
                  
                  <td class="px-10 py-8 text-center">
                    <div class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800/60 text-xs font-black text-slate-700 dark:text-slate-300 border border-slate-200/50 dark:border-slate-700/50">
                      {{ vacacion.dias_solicitados }} Días
                    </div>
                  </td>

                  <td class="px-10 py-8 text-center">
                    <span :class="getEstadoClasses(vacacion.estado)" class="inline-flex items-center px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border">
                      <span class="w-1.5 h-1.5 rounded-full mr-2" :class="getEstadoDotColor(vacacion.estado)"></span>
                      {{ getEstadoLabel(vacacion.estado) }}
                    </span>
                  </td>

                  <td class="px-10 py-8">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 max-w-xs truncate italic" :title="vacacion.motivo">
                      {{ vacacion.motivo || 'Documentación no proporcionada' }}
                    </div>
                  </td>

                  <td class="px-10 py-8">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ formatDate(vacacion.created_at) }}</div>
                  </td>

                  <td class="px-10 py-8 text-right">
                    <Link
                      :href="route('vacaciones.show', vacacion.id)"
                      class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                    >
                      <FontAwesomeIcon icon="eye" class="text-xs" />
                    </Link>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-else>
                <td colspan="6" class="px-10 py-32 text-center">
                   <div class="flex flex-col items-center">
                      <div class="w-20 h-20 rounded-3xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-3xl mb-6 grayscale opacity-30">
                         📋
                      </div>
                      <h3 class="text-sm font-black text-slate-400 uppercase tracking-[0.3em]">No se registran solicitudes históricas</h3>
                      <Link :href="route('vacaciones.create')" class="mt-8 text-blue-500 font-black text-[10px] uppercase tracking-widest border-b-2 border-blue-500/20 hover:border-blue-500 transition-all pb-1">Formalizar primera solicitud →</Link>
                   </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación Premium -->
        <div v-if="vacaciones.last_page > 1" class="px-10 py-8 bg-slate-50/50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800/40 flex justify-center">
           <div class="flex gap-2 p-1.5 bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-100 dark:border-slate-800/60">
              <button
                v-for="page in getPageNumbers()"
                :key="page"
                @click="changePage(page)"
                :class="[
                  'w-10 h-10 flex items-center justify-center text-[11px] font-black rounded-xl transition-all',
                  page === vacaciones.current_page
                    ? 'bg-blue-600 text-white shadow-xl shadow-blue-500/20'
                    : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'
                ]"
              >
                {{ page }}
              </button>
           </div>
        </div>
      </div>
      
      <!-- Pie Informativo -->
      <div class="mt-12 text-center text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 opacity-60">
          Vircom System — Módulo de Gestión de Capital Humano
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  vacaciones: Object,
  filters: Object,
})

const { cssVars } = useCompanyColors()

const filters = ref({
  estado: props.filters?.estado || '',
})

const applyFilters = () => {
  router.get(route('vacaciones.mis-vacaciones'), {
    estado: filters.value.estado,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const formatDate = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }).replace('.', '')
  } catch { return 'Err.' }
}

const getEstadoClasses = (estado) => {
  const classes = {
    'pendiente': 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    'aprobada': 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    'rechazada': 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
  }
  return classes[estado] || 'bg-slate-100 text-slate-500'
}

const getEstadoDotColor = (estado) => {
  const dots = {
    'pendiente': 'bg-amber-500',
    'aprobada': 'bg-emerald-500',
    'rechazada': 'bg-rose-500'
  }
  return dots[estado] || 'bg-slate-400'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Revision RH',
    'aprobada': 'Aprobada',
    'rechazada': 'Declinada',
  }
  return labels[estado] || 'No Definido'
}

const getPageNumbers = () => {
  const currentPage = props.vacaciones.current_page
  const lastPage = props.vacaciones.last_page
  const pages = []
  for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
    pages.push(i)
  }
  return pages
}

const changePage = (page) => {
  router.get(route('vacaciones.mis-vacaciones'), {
    ...filters.value,
    page: page
  }, { preserveState: true, preserveScroll: true })
}
</script>

<style scoped>
.mis-vacaciones {
  min-height: 100vh;
}
select {
    background-image: url('data:image/svg+xml,%3csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"%3e%3cpath stroke="%2364748b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4"/%3e%3c/svg%3e');
    background-position: right 1.5rem center;
    background-repeat: no-repeat;
    background-size: 1.25em 1.25em;
}
</style>
