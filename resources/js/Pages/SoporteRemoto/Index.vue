<template>
  <AppLayout title="Soporte Remoto">
    <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 py-12 px-4 sm:px-6 lg:px-8 transition-colors">
      <div class="max-w-7xl mx-auto">
          
          <!-- Header Section -->
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
            <div class="flex items-center gap-6">
              <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                <div class="relative w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl">
                  <svg class="w-10 h-10 text-indigo-500 dark:text-indigo-400 group-hover:scale-105 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter mb-1 uppercase">
                  Soporte <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500 dark:from-indigo-400 dark:to-purple-400">Remoto</span>
                </h1>
                <p class="text-slate-400 dark:text-slate-500 text-sm font-bold uppercase tracking-wide italic">Panel de administración MeshCentral</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
               <a :href="remoteUrl" target="_blank" class="px-6 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-md dark:shadow-xl flex items-center gap-2 group">
                   <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                   Abrir en nueva pestaña
               </a>
            </div>
          </div>

          <!-- Instrucciones para Clientes Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-100">
              <!-- Server Config Card Removed -->            <!-- Action Card -->
              <div class="lg:col-span-3 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-8 backdrop-blur-xl flex flex-col justify-center items-center text-center shadow-md dark:shadow-none">
                   <div class="w-16 h-16 bg-gradient-to-br from-brand-500 to-brand-600 rounded-2xl flex items-center justify-center shadow-xl shadow-emerald-500/20 mb-4 animate-pulse">
                      <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                   </div>
                   <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Enviar por WhatsApp</h3>
                   <p class="text-slate-400 dark:text-slate-500 text-xs mb-6">Copia las instrucciones formateadas listas para enviar.</p>
                   
                   <button 
                      @click="copiarConfig"
                      class="w-full max-w-md py-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all border border-slate-200 dark:border-white/5 flex items-center justify-center gap-2 group"
                      :class="{'!bg-emerald-600 !text-white border-emerald-500': copiado}"
                  >
                      <span v-if="!copiado">Copiar Todo</span>
                      <span v-else>¡Copiado!</span>
                      <svg v-if="!copiado" class="w-4 h-4 text-slate-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                      <svg v-else class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                   </button>
              </div>
          </div>

          <!-- Portal de Acceso Directo -->
          <div class="relative w-full p-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-[3rem] overflow-hidden shadow-2xl animate-in fade-in slide-in-from-bottom-8 duration-700 delay-200 flex flex-col items-center justify-center text-center">
              <div class="absolute -inset-2 bg-gradient-to-r from-indigo-500/10 to-purple-600/10 blur-3xl opacity-30 pointer-events-none"></div>
              
              <div class="relative group cursor-pointer mb-8">
                  <div class="absolute -inset-4 bg-gradient-to-r from-brand-500 to-brand-600 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                  <div class="relative w-16 h-16 rounded-full bg-[var(--ui-surface)] border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-2xl text-indigo-500 dark:text-indigo-400">
                      <svg class="w-10 h-10 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                  </div>
              </div>

              <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase mb-4">Plataforma de Control Externo</h2>
              <p class="text-slate-400 dark:text-slate-500 font-medium max-w-lg mx-auto text-sm leading-relaxed mb-8">
                  Debido a directivas de seguridad industrial <span class="text-indigo-500 font-bold">SAMEORIGIN</span>, el panel de asistencia remota debe ejecutarse de forma independiente.
              </p>

              <a 
                  :href="remoteUrl" 
                  target="_blank" 
                  class="px-12 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-black uppercase tracking-[0.2em] rounded-2xl transition-all shadow-xl shadow-indigo-600/30 flex items-center gap-2 group active:scale-95"
              >
                  <svg class="w-4 h-4 group-hover:translate-x-1 group-hover:shadow-xl hover:shadow-xl transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                  Iniciar Consola MeshCentral
              </a>
          </div>
          
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    remoteUrl: String
});

const loading = ref(true);
const copiado = ref(false);

const copiarConfig = () => {
    const texto = `*Soporte Remoto Climas del Desierto*\n\n` +
                  `Por favor, ingresa al siguiente enlace para que un técnico pueda conectarse a tu equipo:\n` +
                  `${props.remoteUrl}\n\n` +
                  `Avísame cuando estés en la página.`;
    
    navigator.clipboard.writeText(texto).then(() => {
        copiado.value = true;
        setTimeout(() => copiado.value = false, 2000);
    });
};
</script>
