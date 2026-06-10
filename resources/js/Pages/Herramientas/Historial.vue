<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faHistory, faToolbox, faUser, faArrowRight, faArrowLeft, faCalendarAlt, faInfoCircle } from '@fortawesome/free-solid-svg-icons'

defineOptions({ layout: AppLayout })

const props = defineProps({
  historial: { type: Object, required: true },
})

const formatDate = (dateString) => {
  if (!dateString) return '---'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getTipoBadgeClass = (item) => {
  if (item.fecha_devolucion) return 'bg-rose-500/10 text-rose-500 border-rose-500/20'
  return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
}
</script>

<template>
  <Head title="Historial de Herramientas" />

  <div class="min-h-screen bg-[var(--ui-surface)] pb-12">
    <!-- Header -->
    <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
       <div class="absolute inset-0 bg-gradient-to-br from-fuchsia-500/5 to-transparent"></div>
       <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
             <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase flex items-center gap-3">
                   <div class="p-2 bg-fuchsia-500/10 rounded-lg">
                      <FontAwesomeIcon :icon="faHistory" class="text-fuchsia-500" />
                   </div>
                   Historial de Movimientos
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium italic">Registro completo de asignaciones y entregas de herramientas</p>
             </div>
             <Link href="/herramientas" class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-wide rounded-2xl hover:scale-105 transition-all shadow-xl">
                Volver al Catálogo
             </Link>
          </div>
       </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
       <!-- Table -->
       <div class="bg-white dark:bg-slate-800 rounded-[40px] shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden">
          <div class="overflow-x-auto">
             <table class="w-full text-left border-collapse">
                <thead>
                   <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                      <th class="px-6 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Herramienta</th>
                      <th class="px-6 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Tipo</th>
                      <th class="px-6 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Técnico</th>
                      <th class="px-6 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Fecha</th>
                      <th class="px-6 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Detalles</th>
                   </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                   <tr v-for="item in historial.data" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors group">
                      <td class="px-6 py-5">
                         <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-600 group-hover:scale-110 transition-transform">
                               <FontAwesomeIcon :icon="faToolbox" />
                            </div>
                            <div>
                               <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">
                                  {{ item.herramienta?.nombre || 'Desconocida' }}
                               </p>
                               <p class="text-[10px] text-slate-400 font-mono tracking-tighter uppercase mt-0.5">
                                  S/N: {{ item.herramienta?.numero_serie || 'N/A' }}
                               </p>
                            </div>
                         </div>
                      </td>
                      <td class="px-6 py-5">
                         <span :class="getTipoBadgeClass(item)" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border">
                            {{ item.fecha_devolucion ? 'ENTREGA' : 'ASIGNACIÓN' }}
                         </span>
                      </td>
                      <td class="px-6 py-5">
                         <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-black">
                               {{ item.tecnico?.name?.charAt(0) || '?' }}
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ item.tecnico?.name || 'Sistema' }}</span>
                         </div>
                      </td>
                      <td class="px-6 py-5">
                         <div class="flex flex-col">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">
                               {{ formatDate(item.fecha_asignacion || item.created_at) }}
                            </span>
                            <span v-if="item.fecha_devolucion" class="text-[9px] text-rose-500 font-bold uppercase mt-1">
                               Devuelto: {{ formatDate(item.fecha_devolucion) }}
                            </span>
                         </div>
                      </td>
                      <td class="px-6 py-5 max-w-xs">
                         <p class="text-[11px] text-slate-500 dark:text-slate-400 italic line-clamp-2 leading-relaxed">
                            {{ item.observaciones_asignacion || item.observaciones_devolucion || 'Sin observaciones' }}
                         </p>
                         <p v-if="item.asignado_por" class="text-[9px] text-slate-400 mt-1 font-bold">
                            Por: {{ item.asignado_por?.name }}
                         </p>
                      </td>
                   </tr>
                </tbody>
             </table>
          </div>
          
          <!-- Pagination -->
          <div class="px-6 py-6 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700">
             <Pagination :links="historial.links" />
          </div>
       </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
