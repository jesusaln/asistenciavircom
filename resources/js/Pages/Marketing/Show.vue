<template>
  <AppLayout title="Detalle de Campaña">
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link 
            :href="route('marketing.campanias.index')"
            class="p-3 rounded-2xl bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 text-slate-400 hover:text-brand-500 transition-all duration-200 shadow-sm shadow-black/5"
          >
            <FontAwesomeIcon icon="arrow-left" />
          </Link>
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ campania.nombre }}</h2>
            <div class="flex items-center gap-2 mt-1">
               <span class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ campania.tipo }}</span>
               <span class="w-1 h-1 rounded-full bg-slate-300"></span>
               <span class="text-[10px] font-black uppercase tracking-wide text-brand-500">{{ campania.plantilla_id }}</span>
            </div>
          </div>
        </div>
        
        <div class="flex items-center gap-4">
           <button 
             @click="eliminarCampaign"
             class="p-4 rounded-2xl bg-brand-500/10 hover:bg-slate-500 text-rose-500 hover:text-white border border-rose-500/20 transition-all duration-200 shadow-sm"
             title="Eliminar Campaña"
           >
             <FontAwesomeIcon icon="trash" />
           </button>

           <button 
             v-if="campania.estado === 'borrador'"
             @click="executeCampaign"
             :disabled="executing"
             class="px-8 py-4 bg-gradient-to-br from-brand-500 to-brand-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-xs font-black uppercase tracking-wide rounded-3xl shadow-2xl shadow-emerald-500/20 transition-all duration-200 active:scale-95 flex items-center gap-2"
           >
             <FontAwesomeIcon :icon="executing ? 'circle-notch' : 'paper-plane'" :class="{'animate-spin': executing}" />
             {{ executing ? 'Ejecutando...' : 'Lanzar Campaña Ahora' }}
           </button>
           <div v-else :class="getStatusClasses(campania.estado)" class="px-6 py-3 rounded-2xl border text-[10px] font-black uppercase tracking-wide shadow-sm flex items-center gap-2">
              <span class="w-2 h-2 rounded-full animate-pulse" :class="getStatusDotClass(campania.estado)"></span>
              {{ getStatusLabel(campania.estado) }}
           </div>
        </div>
      </div>
    </template>

    <div class="py-12 px-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Stats Sidebar -->
        <div class="space-y-6 lg:col-span-1">
          <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl overflow-hidden relative group">
             <div class="absolute top-0 right-0 w-32 h-32 bg-brand-500/[0.03] rounded-bl-[100px] -z-0"></div>
             
             <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide mb-8 relative z-10">Rendimiento en Vivo</h3>
             
             <div class="space-y-6 relative z-10">
                <div v-for="(stat, label) in detailedStats" :key="label" class="flex flex-col gap-3">
                   <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wide">
                      <span class="text-slate-400 dark:text-slate-500">{{ stat.label }}</span>
                      <span :class="stat.textColor">{{ stat.value }}</span>
                   </div>
                   <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                      <div class="h-full rounded-full transition-all duration-700" :class="stat.bgColor" :style="{ width: stat.percentage + '%' }"></div>
                   </div>
                </div>
             </div>

             <div class="mt-12 pt-8 border-t border-slate-100 dark:border-slate-800/50 flex flex-col gap-6 relative z-10">
                <div class="flex items-center justify-between">
                   <div class="flex flex-col">
                      <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Tasa de Entrega</span>
                      <span class="text-lg font-black text-slate-900 dark:text-white mt-1">{{ deliveryRate }}%</span>
                   </div>
                   <div class="w-px h-8 bg-slate-100 dark:bg-slate-800/50 mx-2"></div>
                   <div class="flex flex-col">
                      <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Tasa de Apertura</span>
                      <span class="text-lg font-black text-slate-900 dark:text-white mt-1">{{ readRate }}%</span>
                   </div>
                </div>
             </div>
          </div>

          <div class="bg-slate-900 dark:bg-slate-950 p-8 rounded-[2.5rem] shadow-2xl overflow-hidden relative group border border-white/[0.05]">
             <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-brand-500/10 blur-[60px] animate-pulse"></div>
             <h3 class="text-[10px] font-black text-brand-500 uppercase tracking-[0.2em] mb-4">Meta Business Gateway</h3>
             <p class="text-xs font-medium text-slate-400 leading-relaxed mb-6 italic">
                Campaña operada bajo cumplimiento PROFECO y normas de Meta. Los mensajes fallidos son registrados para optimización de listas.
             </p>
             <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-brand-500"></div>
                <span class="text-[9px] font-black text-white uppercase tracking-wide">Conexión Segura Activa</span>
             </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
           <!-- Recipient List -->
           <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl overflow-hidden min-h-[500px]">
              <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-slate-50/30 dark:bg-slate-950/20">
                 <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wide">Lista de Destinatarios</h3>
                 <div class="relative group">
                    <FontAwesomeIcon icon="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]" />
                    <input type="text" placeholder="Buscar destinatario..." class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl py-2 pl-9 pr-4 text-[10px] font-black uppercase tracking-wide focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/5 transition-all outline-none">
                 </div>
              </div>

              <div class="overflow-x-auto">
                 <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead>
                       <tr class="bg-slate-50/50 dark:bg-slate-950/10">
                          <th class="px-8 py-4 text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cliente</th>
                          <th class="px-8 py-4 text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Teléfono</th>
                          <th class="px-8 py-4 text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Estado</th>
                          <th class="px-8 py-4 text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Última Actividad</th>
                       </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                       <tr v-for="destinatario in campania.destinatarios" :key="destinatario.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                          <td class="px-8 py-5">
                             <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide shadow-sm">
                                   {{ destinatario.cliente?.nombre_razon_social?.charAt(0) }}
                                </div>
                                <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ destinatario.cliente?.nombre_razon_social }}</span>
                             </div>
                          </td>
                          <td class="px-8 py-5">
                             <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ destinatario.cliente?.telefono }}</span>
                          </td>
                          <td class="px-8 py-5">
                             <div :class="getDestStatusClass(destinatario.estado)" class="inline-flex items-center px-3 py-1.5 rounded-xl text-[8px] font-black uppercase tracking-wide border shadow-sm">
                                <FontAwesomeIcon :icon="getDestStatusIcon(destinatario.estado)" class="mr-2 text-[10px]" />
                                {{ destinatario.estado }}
                             </div>
                          </td>
                          <td class="px-8 py-5">
                             <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ destinatario.sent_at ? formatDate(destinatario.sent_at) : '-' }}</span>
                          </td>
                       </tr>
                    </tbody>
                 </table>
              </div>
           </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { 
  faArrowLeft, faPaperPlane, faCircleNotch, faEye, 
  faCheckCircle, faExclamationTriangle, faClock, faEnvelopeOpen, faSearch, faTrash
} from '@fortawesome/free-solid-svg-icons';

library.add(faArrowLeft, faPaperPlane, faCircleNotch, faEye, faCheckCircle, faExclamationTriangle, faClock, faEnvelopeOpen, faSearch, faTrash);
import Swal from '@/Utils/Swal';

const props = defineProps({
  campania: Object,
  stats: Object,
});

const executing = ref(false);

const detailedStats = computed(() => {
  const total = props.stats.total || 1;
  return {
    pendiente: { 
      label: 'Pendientes de Envío', 
      value: props.stats.pendiente, 
      percentage: (props.stats.pendiente / total) * 100,
      bgColor: 'bg-slate-400/50',
      textColor: 'text-slate-400'
    },
    enviado: { 
      label: 'Enviados Exitosos', 
      value: props.stats.enviado + props.stats.entregado + props.stats.leido, 
      percentage: ((props.stats.enviado + props.stats.entregado + props.stats.leido) / total) * 100,
      bgColor: 'bg-emerald-400',
      textColor: 'text-emerald-500'
    },
    fallido: { 
      label: 'Fallidos / Bloqueados', 
      value: props.stats.fallido, 
      percentage: (props.stats.fallido / total) * 100,
      bgColor: 'bg-rose-400',
      textColor: 'text-rose-500'
    }
  };
});

const deliveryRate = computed(() => {
  if (props.stats.total === 0) return 0;
  return Math.round(((props.stats.entregado + props.stats.leido) / props.stats.total) * 100);
});

const readRate = computed(() => {
  if (props.stats.total === 0) return 0;
  return Math.round((props.stats.leido / props.stats.total) * 100);
});

const getStatusClasses = (status) => {
  switch (status) {
    case 'completado': return 'bg-brand-500/10 border-emerald-500/20 text-emerald-600 dark:text-slate-400';
    case 'en_proceso': return 'bg-brand-500/10 border-brand-500/20 text-brand-600 dark:text-amber-400';
    case 'borrador': return 'bg-slate-500/10 border-slate-500/20 text-slate-500 dark:text-slate-400';
    default: return 'bg-slate-100 border-slate-200 text-slate-500';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'completado': return 'bg-brand-500';
    case 'en_proceso': return 'bg-brand-500';
    default: return 'bg-slate-400';
  }
};

const getStatusLabel = (status) => {
  const labels = {
    'completado': 'Campaña Finalizada',
    'en_proceso': 'Ejecución en Progreso',
    'borrador': 'Estado: Borrador',
  };
  return labels[status] || status;
};

const getDestStatusClass = (status) => {
  switch (status) {
    case 'leido': return 'bg-brand-500/10 border-emerald-500/20 text-emerald-600';
    case 'entregado': return 'bg-brand-500/10 border-blue-500/20 text-blue-600';
    case 'enviado': return 'bg-sky-500/10 border-sky-500/20 text-sky-600';
    case 'fallido': return 'bg-brand-500/10 border-rose-500/20 text-rose-600';
    default: return 'bg-slate-100 border-slate-200 text-slate-500';
  }
};

const getDestStatusIcon = (status) => {
  switch (status) {
    case 'leido': return 'envelope-open';
    case 'entregado': return 'check-circle';
    case 'enviado': return 'paper-plane';
    case 'fallido': return 'exclamation-triangle';
    default: return 'clock';
  }
};

const executeCampaign = async () => {
  const { isConfirmed } = await Swal.fire({
    title: 'Lanzar campaña',
    text: '¿Estás seguro de lanzar esta campaña ahora? Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, lanzar',
    cancelButtonText: 'Cancelar',
  });
  if (!isConfirmed) return;

  executing.value = true;
  // Usamos ruta relativa para evitar problemas de Mixed Content (HTTP/HTTPS) en el VPS
  const url = `/marketing/campanias/${props.campania.id}/ejecutar`;
  
  router.post(url, {}, {
     onFinish: () => {
        executing.value = false;
     },
     onError: (errors) => {
        console.error('Error al ejecutar campaña:', errors);
        Swal.fire({
          title: 'Error',
          text: 'Hubo un error al procesar la solicitud.',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
     }
  });
};

const eliminarCampaign = async () => {
  const { isConfirmed } = await Swal.fire({
    title: 'Eliminar campaña',
    text: `¿Estás seguro de que deseas eliminar la campaña "${props.campania.nombre}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!isConfirmed) return;

  router.delete(route('marketing.campanias.destroy', props.campania.id));
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
