<template>
  <AppLayout title="Marketing - Plantillas">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Plantillas de WhatsApp</h2>
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Gestiona tus mensajes aprobados por Meta Business</p>
        </div>
        <div class="flex items-center gap-3">
          <button 
             @click="refresh"
             class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 text-slate-400 hover:text-amber-500 transition-all duration-300 shadow-sm"
          >
             <FontAwesomeIcon icon="rotate" :class="{'animate-spin': refreshing}" />
          </button>
        </div>
      </div>
    </template>

    <div class="py-12 px-6 max-w-7xl mx-auto">
      <!-- Error State -->
      <div v-if="error" class="mb-8 p-6 rounded-[2rem] bg-rose-500/10 border-2 border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-start gap-4 shadow-xl shadow-rose-500/5 animate-fade-in">
        <div class="p-3 rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
          <FontAwesomeIcon icon="triangle-exclamation" />
        </div>
        <div class="flex-1">
          <h4 class="text-xs font-black uppercase tracking-widest mb-1">Error al sincronizar con Meta</h4>
          <p class="text-[11px] font-medium opacity-80 leading-relaxed">{{ error }}</p>
          <div class="mt-4 flex gap-4">
             <button @click="refresh" class="text-[9px] font-black uppercase tracking-widest px-4 py-2 bg-rose-500 text-white rounded-lg shadow-sm hover:translate-y-[-1px] transition-all">Reintentar</button>
             <Link href="/empresa/configuracion" class="text-[9px] font-black uppercase tracking-widest px-4 py-2 bg-white/50 dark:bg-slate-800 text-rose-500 rounded-lg hover:bg-white transition-all">Ver Configuración</Link>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
         <div v-for="stat in stats" :key="stat.label" class="p-6 rounded-[2rem] bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 shadow-xl shadow-black/[0.02]">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">{{ stat.label }}</p>
            <div class="flex items-center gap-3">
               <span class="text-3xl font-black text-slate-900 dark:text-white">{{ stat.value }}</span>
               <span class="text-[9px] font-bold px-2 py-0.5 rounded-full" :class="stat.trendClass">
                  {{ stat.trend }}
               </span>
            </div>
         </div>
      </div>

      <!-- Templates Grid -->
      <div v-if="templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="template in templates" 
          :key="template.id"
          class="group relative bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl transition-all duration-500 hover:scale-[1.02] hover:shadow-amber-500/10 hover:border-amber-500/30 overflow-hidden flex flex-col"
        >
          <!-- Card Header -->
          <div class="p-8 pb-4">
             <div class="flex items-center justify-between mb-6">
                <span class="text-[9px] font-black uppercase tracking-[0.2em] px-3 py-1.5 rounded-xl transition-colors" 
                  :class="template.status === 'APPROVED' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600'"
                >
                   {{ template.status === 'APPROVED' ? 'Aprobada' : template.status }}
                </span>
                <div class="w-8 h-8 rounded-xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-400">
                   <FontAwesomeIcon icon="fa-brands fa-whatsapp" />
                </div>
             </div>
             
             <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.15em] mb-1 truncate">{{ template.name.replace(/_/g, ' ') }}</h3>
             <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ template.category }} · {{ template.language }}</p>
          </div>

          <!-- Body Content -->
          <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-950/20 flex-1 border-y border-slate-100 dark:border-slate-800/50">
             <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] shadow-inner relative overflow-hidden group/message shadow-sm border border-slate-200/30 dark:border-white/[0.02]">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/[0.02] to-transparent opacity-0 group-hover/message:opacity-100 transition-opacity"></div>
                <p class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-400 whitespace-pre-wrap italic relative z-10">
                   {{ getTemplateBody(template) }}
                </p>
             </div>
             
             <!-- Buttons if exist -->
             <div v-if="hasButtons(template)" class="mt-6 flex flex-wrap gap-2">
                <div 
                  v-for="btn in getButtons(template)" 
                  :key="btn.text"
                  class="px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800/50 text-[9px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 flex items-center gap-2"
                >
                   <FontAwesomeIcon :icon="btn.type === 'PHONE_NUMBER' ? 'phone' : 'up-right-from-square'" class="text-[8px]" />
                   {{ btn.text }}
                </div>
             </div>
          </div>

          <!-- Card Footer -->
          <div class="p-8 pt-4 flex items-center justify-between">
             <div class="flex items-center gap-2">
                <div class="w-1.5 h-1.5 rounded-full" :class="template.status === 'APPROVED' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></div>
                <span class="text-[9px] font-black uppercase tracking-tighter text-slate-400">ID: {{ template.id }}</span>
             </div>
             <Link 
               :href="route('marketing.campanias.create', { plantilla: template.name })"
               class="text-[9px] font-black uppercase tracking-[0.2em] text-amber-500 hover:text-amber-600 flex items-center gap-2 group/link"
             >
                Usar en Campaña
                <FontAwesomeIcon icon="arrow-right" class="transition-transform group-hover/link:translate-x-1" />
             </Link>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!error" class="text-center py-32 bg-white/50 dark:bg-slate-900/50 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-slate-800 animate-fade-in shadow-sm shadow-black/[0.01]">
         <div class="w-24 h-24 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-8 shadow-xl">
            <FontAwesomeIcon icon="file-signature" class="text-3xl text-slate-300 dark:text-slate-700" />
         </div>
         <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">No hay plantillas sincronizadas</h3>
         <p class="text-sm font-medium text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-10">Conecta tu cuenta de Meta Business o asegúrate de que tus plantillas estén en estado "Aprobado".</p>
         <button @click="refresh" class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-2xl transition-all active:scale-95 group">
            <FontAwesomeIcon icon="sync" class="mr-3 group-hover:rotate-180 transition-transform duration-500" />
            Sincronizar ahora
         </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { 
  faArrowRight, faRotate, faTriangleExclamation, 
  faUpRightFromSquare, faPhone, faFileSignature, faSync
} from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';

library.add(faArrowRight, faRotate, faTriangleExclamation, faUpRightFromSquare, faPhone, faFileSignature, faSync, faWhatsapp);

const props = defineProps({
  templates: { type: Array, default: () => [] },
  error: { type: String, default: null }
});

const refreshing = ref(false);

const stats = computed(() => [
   { label: 'Plantillas Totales', value: props.templates.length, trend: 'Sincronizado', trendClass: 'bg-emerald-500/10 text-emerald-600' },
   { label: 'Aprobadas', value: props.templates.filter(t => t.status === 'APPROVED').length, trend: 'Listo', trendClass: 'bg-blue-500/10 text-blue-600' },
   { label: 'En Revisión', value: props.templates.filter(t => t.status !== 'APPROVED').length, trend: 'Pendiente', trendClass: 'bg-amber-500/10 text-amber-600' },
   { label: 'Canal Activo', value: 'WhatsApp', trend: 'Global', trendClass: 'bg-purple-500/10 text-purple-600' },
]);

const getTemplateBody = (template) => {
  if (!template.components) return '';
  const body = template.components.find(c => c.type === 'BODY');
  return body ? body.text : '';
};

const hasButtons = (template) => {
   if (!template.components) return false;
   return template.components.some(c => c.type === 'BUTTONS');
};

const getButtons = (template) => {
   const comp = template.components.find(c => c.type === 'BUTTONS');
   return comp ? comp.buttons : [];
};

const refresh = () => {
    refreshing.value = true;
    router.reload({ 
        onFinish: () => refreshing.value = false 
    });
};
</script>

<style>
.animate-fade-in {
  animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
