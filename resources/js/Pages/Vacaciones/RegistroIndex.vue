<template>
  <Head title="Registro de Vacaciones" />
  
  <div class="registro-vacaciones-index min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500 py-10 px-6 lg:px-12" :style="cssVars">
    <div class="max-w-[1600px] mx-auto">
      
      <!-- Header Premium -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="flex items-center gap-4">
           <div class="w-14 h-14 rounded-2xl bg-indigo-500 flex items-center justify-center text-white shadow-2xl shadow-indigo-500/20">
              <FontAwesomeIcon icon="clipboard-list" size="lg" />
           </div>
           <div>
              <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Kardex de Vacaciones</h1>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Control maestro de saldos y devengos por periodo</p>
           </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
          <Link :href="route('registro-vacaciones.export', { anio: anioLocal, search: searchLocal })" 
                class="px-8 py-5 bg-emerald-600 text-white rounded-[2rem] font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-emerald-500/20 flex items-center">
            <FontAwesomeIcon icon="file-excel" class="mr-3" />
            Descargar Reporte (CSV)
          </Link>
        </div>
      </div>

      <!-- Barra de Filtros Inteligente Premium -->
      <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-100 dark:border-slate-800/60 p-8 mb-12 shadow-lg animate-in fade-in slide-in-from-bottom-4 duration-1000">
        <div class="flex flex-wrap items-end gap-8">
          <div class="flex-1 min-w-[300px] space-y-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Búsqueda Predictiva de Colaboradores</label>
            <div class="relative group">
              <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors pointer-events-none">
                 <FontAwesomeIcon icon="search" />
              </div>
              <input 
                type="text" 
                v-model="searchLocal" 
                @keyup.enter="applyFilters"
                placeholder="Nombre, departamento o puesto..." 
                class="w-full pl-16 pr-8 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-sm font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm"
              />
            </div>
          </div>

          <div class="w-full sm:w-48 space-y-3">
             <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Periodo/Año</label>
             <div class="relative group">
                <input 
                  type="number" 
                  v-model.number="anioLocal" 
                  class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-sm font-black text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm"
                />
             </div>
          </div>

          <button
            @click="applyFilters"
            class="px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-[0.98] transition-all shadow-2xl flex items-center"
          >
            <FontAwesomeIcon icon="filter" class="mr-3 text-xs" />
            Aplicar Filtros
          </button>
        </div>
      </div>

      <!-- Tabla Maestra de Registros Dark Premium -->
      <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/40">
            <thead class="bg-slate-50/50 dark:bg-slate-950/50">
              <tr>
                <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Cédula Colaborador</th>
                <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Área Corporativa</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Año</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Provision</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Saldo Activo</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Gozado</th>
                <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">En Proceso</th>
                <th class="px-10 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Última Mod.</th>
              </tr>
            </thead>
            
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/20 text-nowrap">
              <template v-if="registros.data.length > 0">
                <tr v-for="r in registros.data" :key="r.id" class="group hover:bg-white dark:hover:bg-indigo-500/[0.02] transition-colors duration-300">
                  <td class="px-10 py-8">
                    <div class="flex items-center gap-4">
                       <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 flex items-center justify-center text-xs font-black text-indigo-500">
                          {{ r.empleado?.name?.charAt(0) }}
                       </div>
                       <Link :href="route('registro-vacaciones.por-empleado', r.user_id)" class="group/name">
                          <div class="text-sm font-black text-slate-900 dark:text-white group-hover/name:text-indigo-500 transition-colors uppercase tracking-tight">{{ r.empleado?.name || '—' }}</div>
                          <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ r.empleado?.puesto || 'Cargo no definido' }}</div>
                       </Link>
                    </div>
                  </td>
                  
                  <td class="px-10 py-8 text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest italic opacity-80">
                    {{ r.empleado?.departamento || 'Institución General' }}
                  </td>

                  <td class="px-10 py-8 text-center text-sm font-black text-slate-900 dark:text-white">
                    {{ r.anio }}
                  </td>

                  <td class="px-10 py-8 text-center">
                    <div class="text-sm font-black text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ r.dias_correspondientes }} D</div>
                  </td>

                  <td class="px-10 py-8 text-center">
                    <div class="inline-flex items-center justify-center min-w-[60px] py-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm font-black border border-emerald-500/20">
                      {{ r.dias_disponibles }} D
                    </div>
                  </td>

                  <td class="px-10 py-8 text-center text-sm font-bold text-slate-500 opacity-60">
                    {{ r.dias_utilizados }} D
                  </td>

                  <td class="px-10 py-8 text-center text-sm font-bold text-amber-500 opacity-80">
                     {{ r.dias_pendientes }} D
                  </td>

                  <td class="px-10 py-8 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    {{ formatDateShort(r.updated_at) }}
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-else>
                <td colspan="8" class="px-10 py-32 text-center">
                   <div class="flex flex-col items-center">
                      <div class="w-20 h-20 rounded-[2rem] bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-3xl mb-6 grayscale opacity-30">
                         📂
                      </div>
                      <h3 class="text-sm font-black text-slate-400 uppercase tracking-[0.3em]">No se documentan saldos para el criterio seleccionado</h3>
                   </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación Premium -->
        <div v-if="registros.last_page > 1" class="px-10 py-8 bg-slate-50/50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800/40 flex justify-between items-center">
           <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
              Mostrando {{ registros.from }} - {{ registros.to }} de {{ registros.total }} registros auditados
           </div>
           
           <div class="flex gap-3">
             <button @click="go(registros.prev_page_url)" :disabled="!registros.prev_page_url" 
                     class="px-6 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-xl border border-slate-100 dark:border-slate-800 font-black text-[10px] uppercase tracking-widest disabled:opacity-30 hover:bg-slate-50 transition-all shadow-sm">
                Anterior
             </button>
             <button @click="go(registros.next_page_url)" :disabled="!registros.next_page_url" 
                     class="px-6 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-xl border border-slate-100 dark:border-slate-800 font-black text-[10px] uppercase tracking-widest disabled:opacity-30 hover:bg-slate-50 transition-all shadow-sm">
                Siguiente
             </button>
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
  anio: Number,
  search: String,
  sorting: Object,
  registros: Object,
})

const { cssVars } = useCompanyColors()
const anioLocal = ref(props.anio)
const searchLocal = ref(props.search || '')

const applyFilters = () => {
  router.get(route('registro-vacaciones.index'), { anio: anioLocal.value, search: searchLocal.value, page: 1 }, { preserveState: true, preserveScroll: true })
}

const formatDateShort = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  } catch { return date }
}

const go = (url) => { if (url) router.visit(url, { preserveScroll: true }) }
</script>

<style scoped>
.registro-vacaciones-index {
  min-height: 100vh;
}
</style>
