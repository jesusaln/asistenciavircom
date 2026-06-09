<template>
  <AppLayout title="Marketing y Campañas">
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Marketing Inteligente</h2>
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Gestiona tus campañas de WhatsApp y SMS con cumplimiento PROFECO.</p>
        </div>
        <Link 
          :href="route('marketing.campanias.create')"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-br from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-amber-500/20 transition-all duration-300 transform hover:scale-[1.02] active:scale-95"
        >
          <FontAwesomeIcon icon="plus" class="mr-2" />
          Nueva Campaña
        </Link>
      </div>
    </template>

    <div class="py-12 px-6">
      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div v-for="(stat, index) in stats" :key="index" class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-6 rounded-[2rem] border border-slate-200/50 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
          <div class="absolute -right-4 -top-4 w-24 h-24 bg-gradient-to-br opacity-[0.03] group-hover:opacity-[0.07] transition-opacity duration-500 rounded-full" :class="stat.color"></div>
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg" :class="stat.bg">
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
      <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
          <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Historial de Campañas</h3>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Filtrar por:</span>
            <select class="bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-amber-500/20">
              <option>Todas</option>
              <option>WhatsApp</option>
              <option>SMS</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
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
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
              <tr v-for="campania in campanias.data" :key="campania.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                <td class="px-8 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight group-hover:text-amber-500 transition-colors">{{ campania.nombre }}</span>
                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1 line-clamp-1">{{ campania.descripcion || 'Sin descripción' }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" :class="campania.tipo === 'whatsapp' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-blue-500/10 text-blue-500'">
                      <FontAwesomeIcon :icon="campania.tipo === 'whatsapp' ? 'fa-brands fa-whatsapp' : 'comment-alt'" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400">{{ campania.tipo }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                   <div :class="getStatusClasses(campania.estado)" class="inline-flex items-center px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border shadow-sm">
                      <span class="w-1.5 h-1.5 rounded-full mr-2 animate-pulse" :class="getStatusDotClass(campania.estado)"></span>
                      {{ getStatusLabel(campania.estado) }}
                   </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex flex-col gap-2 w-32">
                    <div class="flex justify-between text-[8px] font-black uppercase tracking-widest text-slate-400">
                      <span>{{ campania.enviados_count }} / {{ campania.destinatarios_count }}</span>
                      <span>{{ getProgress(campania) }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                      <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full transition-all duration-1000" :style="{ width: getProgress(campania) + '%' }"></div>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                   <div class="flex flex-col">
                     <span class="text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ formatDate(campania.created_at) }}</span>
                     <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter mt-1">Por: {{ campania.creador?.name }}</span>
                   </div>
                </td>
                <td class="px-8 py-6 text-right">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button 
                      @click="eliminar(campania)"
                      class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-sm"
                      title="Eliminar Campaña"
                    >
                      <FontAwesomeIcon icon="trash" />
                    </button>
                    <Link 
                      :href="route('marketing.campanias.show', campania.id)"
                      class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-amber-500 hover:text-white transition-all duration-300 shadow-sm"
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
                       <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800/50 rounded-3xl flex items-center justify-center mb-4 border border-dashed border-slate-200 dark:border-slate-700">
                          <FontAwesomeIcon icon="bullhorn" class="text-slate-300 text-2xl" />
                       </div>
                       <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">No hay campañas registradas</p>
                       <Link :href="route('marketing.campanias.create')" class="text-[10px] font-black text-amber-500 uppercase tracking-widest mt-2 hover:underline">¡Crea tu primera campaña hoy!</Link>
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faPlus, faBullhorn, faChartLine, faUsers, faEye, faCommentAlt, faTrash } from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';

library.add(faPlus, faBullhorn, faChartLine, faUsers, faEye, faCommentAlt, faWhatsapp, faTrash);

const props = defineProps({
  campanias: Object,
});

const form = useForm({});

const eliminar = (campania) => {
  if (confirm(`¿Estás seguro de que deseas eliminar la campaña "${campania.nombre}"? Esta acción se puede deshacer (Soft Delete).`)) {
    form.delete(route('marketing.campanias.destroy', campania.id), {
      preserveScroll: true,
      onSuccess: () => {
        // Notificación opcional o manejo de éxito
      }
    });
  }
};

const stats = [
  { label: 'Campañas Totales', value: props.campanias.total, icon: 'bullhorn', bg: 'bg-indigo-500', color: 'from-indigo-500 to-indigo-600' },
  { label: 'Alcance Mensual', value: '0', icon: 'users', bg: 'bg-emerald-500', color: 'from-emerald-500 to-emerald-600' },
  { label: 'Tasa de Lectura', value: '12%', icon: 'chart-line', bg: 'bg-amber-500', color: 'from-amber-500 to-amber-600' },
  { label: 'Respuestas', value: '0', icon: 'comment-alt', bg: 'bg-rose-500', color: 'from-rose-500 to-rose-600' },
];

const getStatusClasses = (status) => {
  switch (status) {
    case 'completado': return 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400';
    case 'en_proceso': return 'bg-amber-500/10 border-amber-500/20 text-amber-600 dark:text-amber-400';
    case 'borrador': return 'bg-slate-500/10 border-slate-500/20 text-slate-600 dark:text-slate-400';
    case 'programado': return 'bg-blue-500/10 border-blue-500/20 text-blue-600 dark:text-blue-400';
    default: return 'bg-slate-100 border-slate-200 text-slate-500';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'completado': return 'bg-emerald-500';
    case 'en_proceso': return 'bg-amber-500';
    case 'borrador': return 'bg-slate-400';
    case 'programado': return 'bg-blue-500';
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
