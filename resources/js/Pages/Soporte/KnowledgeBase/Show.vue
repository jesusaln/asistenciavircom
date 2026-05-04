<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-950 text-slate-200">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Breadcrumbs / Top Actions -->
        <div class="flex items-center justify-between mb-8 animate-in fade-in slide-in-from-top-4 duration-700">
          <Link :href="route('soporte.kb.index')" class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-500 hover:text-indigo-400 transition-colors group">
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" /></svg>
            Volver a la Base
          </Link>

          <Link
            v-if="$can('edit kb') || $can('admin') || $can('super-admin')"
            :href="route('soporte.kb.edit', { articulo: articulo.id })"
            class="px-4 py-2 bg-slate-900 border border-white/5 hover:border-indigo-500/50 text-indigo-400 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all flex items-center gap-2 shadow-xl"
          >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            Editar Artículo
          </Link>
        </div>

        <!-- Main Card -->
        <div class="bg-slate-900/40 border border-white/5 rounded-[3rem] overflow-hidden backdrop-blur-xl shadow-2xl animate-in fade-in zoom-in-95 duration-700">
          <!-- Article Header -->
          <div class="p-8 md:p-12 border-b border-white/5 bg-gradient-to-b from-white/5 to-transparent">
            <div class="flex items-center gap-3 mb-6">
              <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase tracking-widest rounded-full border border-indigo-500/20">
                {{ articulo.categoria?.nombre || 'General' }}
              </span>
              <div class="w-1 h-1 rounded-full bg-slate-700"></div>
              <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                {{ articulo.vistas || 0 }} Lecturas
              </span>
            </div>
            
            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter leading-tight mb-8">
              {{ articulo.titulo }}
            </h1>

            <div class="flex flex-wrap items-center gap-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-800 border border-white/5 p-0.5">
                  <img :src="articulo.autor?.profile_photo_url || 'https://ui-avatars.com/api/?name=' + articulo.autor?.name" class="w-full h-full rounded-[0.5rem] object-cover">
                </div>
                <div>
                  <p class="text-[10px] font-black text-white uppercase tracking-widest leading-none">{{ articulo.autor?.name }}</p>
                  <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1">Autor del Artículo</p>
                </div>
              </div>
              <div class="h-8 w-px bg-white/5 hidden sm:block"></div>
              <div class="flex items-center gap-3 text-slate-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span class="text-[10px] font-bold uppercase tracking-widest">Actualizado {{ new Date(articulo.updated_at).toLocaleDateString() }}</span>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="p-8 md:p-12">
            <div class="prose prose-invert max-w-none prose-indigo prose-lg font-medium text-slate-300 leading-relaxed" v-html="articulo.contenido"></div>
          </div>

          <!-- Footer / Feedback -->
          <div class="p-8 md:p-12 bg-slate-900/50 border-t border-white/5">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
              <div>
                <h3 class="text-lg font-bold text-white mb-2 uppercase tracking-tight">¿Te fue útil esta información?</h3>
                <p class="text-slate-500 text-sm font-medium italic">Tu opinión nos ayuda a mejorar nuestra documentación técnica.</p>
              </div>
              <div class="flex items-center gap-4">
                <button 
                  @click="votar(true)" 
                  class="flex items-center gap-3 px-6 py-3 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white border border-emerald-500/20 rounded-2xl transition-all duration-300 active:scale-95 group"
                >
                  <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" /></svg>
                  <span class="text-xs font-black uppercase tracking-widest">Sí, gracias</span>
                </button>
                <button 
                  @click="votar(false)" 
                  class="flex items-center gap-3 px-6 py-3 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 rounded-2xl transition-all duration-300 active:scale-95 group"
                >
                  <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M18 9.5a1.5 1.5 0 11-3 0v-6a1.5 1.5 0 013 0v6zM14 9.667v-5.43a2 2 0 00-1.106-1.79l-.05-.025A4 4 0 0011.057 2H5.64a2 2 0 00-1.962 1.608l-1.2 6A2 2 0 004.44 12H8v4a2 2 0 002 2 1 1 0 001-1v-.667a4 4 0 01.8-2.4l1.4-1.866a4 4 0 00.8-2.4z" /></svg>
                  <span class="text-xs font-black uppercase tracking-widest">No mucho</span>
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  articulo: Object,
});

const votar = (esPositivo) => {
    router.post(route('soporte.kb.votar', { articulo: props.articulo.id }), { es_positivo: esPositivo }, {
        preserveScroll: true
    });
};
</script>
