<template>
  <AppLayout title="Marketing y Campañas">
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Marketing Inteligente</h2>
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Gestiona tus campañas de WhatsApp y SMS con cumplimiento PROFECO.</p>
        </div>
        <Link 
          :href="route('marketing.campanias.create')"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-br from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white text-xs font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-brand-500/20 transition-all duration-200 transform hover:scale-[1.02] active:scale-95"
        >
          <FontAwesomeIcon icon="plus" class="mr-2" />
          Nueva Campaña
        </Link>
      </div>
    </template>

    <div class="py-12 px-6">
      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div v-for="(stat, index) in stats" :key="index" class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl p-6 rounded-[2rem] border border-slate-200/50 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
          <div class="absolute -right-4 -top-4 w-16 h-16 bg-gradient-to-br opacity-[0.03] group-hover:opacity-[0.07] transition-opacity duration-500 rounded-full" :class="stat.color"></div>
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-xl" :class="stat.bg">
              <FontAwesomeIcon :icon="stat.icon" class="text-white text-lg" />
            </div>
            <div>
              <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">{{ stat.label }}</p>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Campaigns Table -->
      <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
          <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Historial de Campañas</h3>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Filtrar por:</span>
            <select class="bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-[10px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 focus:ring-2 focus:ring-brand-500/20">
              <option>Todas</option>
              <option>WhatsApp</option>
              <option>SMS</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-950/20">
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Campaña</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Tipo</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estado</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Rendimiento</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Fecha</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="campania in campanias.data" :key="campania.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                <td class="px-8 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider group-hover:text-brand-500 transition-colors">{{ campania.nombre }}</span>
                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1 line-clamp-1">{{ campania.descripcion || 'Sin descripción' }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="campania.tipo === 'whatsapp' ? 'bg-brand-500/10 text-emerald-500' : 'bg-brand-500/10 text-blue-500'">
                      <FontAwesomeIcon :icon="campania.tipo === 'whatsapp' ? 'fa-brands fa-whatsapp' : 'comment-alt'" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ campania.tipo }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                   <div :class="getStatusClasses(campania.estado)" class="inline-flex items-center px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wide border shadow-sm">
                      <span class="w-1.5 h-1.5 rounded-full mr-2 animate-pulse" :class="getStatusDotClass(campania.estado)"></span>
                      {{ getStatusLabel(campania.estado) }}
                   </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex flex-col gap-2 w-32">
                    <div class="flex justify-between text-[8px] font-black uppercase tracking-wide text-slate-400">
                      <span>{{ campania.enviados_count }} / {{ campania.destinatarios_count }}</span>
                      <span>{{ getProgress(campania) }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                      <div class="h-full bg-gradient-to-r from-brand-400 to-brand-600 rounded-full transition-all duration-700" :style="{ width: getProgress(campania) + '%' }"></div>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                   <div class="flex flex-col">
                     <span class="text-[10px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wide">{{ formatDate(campania.created_at) }}</span>
                     <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-1">Por: {{ campania.creador?.name }}</span>
                   </div>
                </td>
                <td class="px-8 py-6 text-right">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button 
                      @click="eliminar(campania)"
                      class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-500 hover:text-white transition-all duration-200 shadow-sm"
                      title="Eliminar Campaña"
                    >
                      <FontAwesomeIcon icon="trash" />
                    </button>
                    <Link 
                      :href="route('marketing.campanias.show', campania.id)"
                      class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-500 hover:text-white transition-all duration-200 shadow-sm"
                      title="Ver Detalles"
                    >
                      <FontAwesomeIcon icon="eye" />
                    </Link>
                  </div>
                </td>
              </tr>
              <tr v-if="campanias.data.length === 0">
                 <td colspan="6" class="px-8 py-20 text-center">
                    <div class="flex flex-col items-center">
                       <div class="w-16 h-16 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-3xl flex items-center justify-center mb-4 border border-dashed border-slate-300 dark:border-slate-600">
                          <FontAwesomeIcon icon="bullhorn" class="text-slate-300 text-2xl" />
                       </div>
                       <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">No hay campañas registradas</p>
                       <Link :href="route('marketing.campanias.create')" class="text-[10px] font-black text-brand-500 uppercase tracking-wide mt-2 hover:underline">¡Crea tu primera campaña hoy!</Link>
                    </div>
                 </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faPlus, faBullhorn, faChartLine, faUsers, faEye, faCommentAlt, faTrash } from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';
import Swal from '@/Utils/Swal';

library.add(faPlus, faBullhorn, faChartLine, faUsers, faEye, faCommentAlt, faWhatsapp, faTrash);

const props = defineProps({
  campanias: Object,
});

const form = useForm({});

const eliminar = async (campania) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Eliminar campaña',
    text: `¿Estás seguro de que deseas eliminar la campaña "${campania.nombre}"? Esta acción se puede deshacer (Soft Delete).`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!isConfirmed) return;

  form.delete(route('marketing.campanias.destroy', campania.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Notificación opcional o manejo de éxito
    }
  });
};

const stats = [
  { label: 'Campañas Totales', value: props.campanias.total, icon: 'bullhorn', bg: 'bg-indigo-500', color: 'from-brand-500 to-amber-600' },
  { label: 'Alcance Mensual', value: '0', icon: 'users', bg: 'bg-brand-500', color: 'from-brand-500 to-amber-600' },
  { label: 'Tasa de Lectura', value: '12%', icon: 'chart-line', bg: 'bg-brand-500', color: 'from-brand-500 to-amber-600' },
  { label: 'Respuestas', value: '0', icon: 'comment-alt', bg: 'bg-brand-500', color: 'from-brand-500 to-amber-600' },
];

const getStatusClasses = (status) => {
  switch (status) {
    case 'completado': return 'bg-brand-500/10 border-emerald-500/20 text-emerald-600 dark:text-slate-400';
    case 'en_proceso': return 'bg-brand-500/10 border-brand-500/20 text-brand-600 dark:text-amber-400';
    case 'borrador': return 'bg-slate-500/10 border-slate-500/20 text-slate-500 dark:text-slate-400';
    case 'programado': return 'bg-brand-500/10 border-blue-500/20 text-blue-600 dark:text-blue-400';
    default: return 'bg-slate-100 border-slate-200 text-slate-500';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'completado': return 'bg-brand-500';
    case 'en_proceso': return 'bg-brand-500';
    case 'borrador': return 'bg-slate-400';
    case 'programado': return 'bg-brand-500';
    default: return 'bg-slate-300';
  }
};

const getStatusLabel = (status) => {
  const labels = {
    'completado': 'Enviado',
    'en_proceso': 'Ejecutando',
    'borrador': 'Borrador',
    'programado': 'Programado',
    'fallido': 'Fallido'
  };
  return labels[status] || status;
};

const getProgress = (campania) => {
  if (campania.destinatarios_count === 0) return 0;
  return Math.round((campania.enviados_count / campania.destinatarios_count) * 100);
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};
</script>
