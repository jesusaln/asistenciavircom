<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-slate-800 dark:text-slate-200 transition-colors">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
          <div class="flex items-center gap-6">
            <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
              <div class="relative w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-xl dark:shadow-2xl backdrop-blur-xl">
                <svg class="w-10 h-10 text-indigo-500 dark:text-indigo-400 group-hover:scale-105 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
            </div>
            <div>
              <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter mb-1 uppercase">
                Base de <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500 dark:from-indigo-400 dark:to-purple-400">Conocimiento</span> <span class="text-xs align-top text-brand-500 ml-1">PRO</span>
              </h1>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-wide italic">Documentación técnica y guías de ayuda</p>
            </div>
          </div>

          <Link
            v-if="$can('create kb') || $can('admin') || $can('super-admin')"
            :href="route('soporte.kb.create')"
            class="px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-indigo-500/25 flex items-center gap-2 group"
          >
            <svg class="w-4 h-4 group-hover:rotate-90 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
            Nuevo Artículo
          </Link>
        </div>

        <!-- Search Toolbar -->
        <div class="relative mb-12 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-150">
          <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          </div>
          <input
            type="text"
            v-model="search"
            placeholder="¿En qué podemos ayudarte hoy? Busca soluciones, errores o procesos..."
            class="block w-full pl-14 pr-4 py-6 bg-white dark:bg-black/50 border border-slate-200 dark:border-white/5 rounded-3xl text-lg text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all backdrop-blur-xl shadow-md dark:shadow-2xl"
          />
          <div class="absolute inset-y-0 right-0 py-2 pr-2 hidden md:block">
            <div class="h-full flex items-center px-4 rounded-2xl bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">
              Enter para buscar
            </div>
          </div>
        </div>

        <!-- Articles Grid -->
        <div v-if="articulos.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div 
            v-for="(articulo, index) in articulos.data" 
            :key="articulo.id" 
            class="group relative animate-in fade-in slide-in-from-bottom-6 duration-700"
            :style="{ 'animation-delay': (index * 100) + 'ms' }"
          >
            <div class="absolute -inset-0.5 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
            <Link :href="route('soporte.kb.show', { articulo: articulo.id })" class="relative block h-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-8 backdrop-blur-xl hover:bg-slate-50 dark:hover:bg-slate-900/50 hover:border-brand-500 dark:hover:border-white/10 transition-all duration-200 shadow-sm">
              <div class="flex items-start justify-between mb-6">
                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-500">
                  <svg class="w-10 h-10 text-indigo-500 dark:text-indigo-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-wide rounded-full border border-indigo-500/10 group-hover:border-brand-500/30 transition-colors">
                  {{ articulo.categoria?.nombre || 'General' }}
                </span>
              </div>
              
              <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 leading-tight">
                {{ articulo.titulo }}
              </h3>
              
              <p class="text-slate-500 dark:text-slate-400 text-sm mb-6 line-clamp-3 leading-relaxed font-medium">
                {{ articulo.resumen || 'Explora este artículo detallado para obtener más información y soluciones paso a paso.' }}
              </p>
              
              <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-white/5">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                    <svg class="w-3 h-3 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                  </div>
                  <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                    {{ new Date(articulo.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}
                  </span>
                </div>
                <div class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-wide group-hover:translate-x-1 transition-transform">
                  Leer más
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                </div>
              </div>
            </Link>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="text-center py-24 bg-[var(--ui-surface)] dark:bg-slate-800/30 rounded-[3rem] border border-dashed border-slate-200 dark:border-white/5">
          <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-md dark:shadow-2xl">
             <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          </div>
          <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2 uppercase tracking-wider">No se encontraron artículos</h3>
          <p class="text-slate-400 dark:text-slate-500 max-w-sm mx-auto font-medium">Intenta ajustar tu búsqueda o utiliza palabras clave más generales para encontrar lo que necesitas.</p>
        </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  articulos: Object,
  filtros: Object,
});

const search = ref(props.filtros.buscar || '');

// Debounce search
let timeout;
watch(search, (value) => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    router.get(route('soporte.kb.index'), { buscar: value }, { preserveState: true, replace: true });
  }, 300);
});
</script>
