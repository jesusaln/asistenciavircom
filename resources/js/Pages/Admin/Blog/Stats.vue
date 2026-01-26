<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  post: Object,
  tracks: Array,
})

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head :title="`Estadísticas: ${post.titulo}`" />

  <div class="p-6 min-h-screen bg-slate-50 dark:bg-slate-950">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-10 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="route('admin.blog.index')" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center text-slate-500 shadow-sm border border-slate-100 dark:border-slate-800 hover:text-[var(--color-primary)] transition-all">
            <FontAwesomeIcon icon="arrow-left" />
          </Link>
          <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white leading-tight">Estadísticas de Newsletter</h1>
            <p class="text-slate-500 font-medium text-sm">{{ post.titulo }}</p>
          </div>
        </div>
      </div>

      <!-- Stats Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div v-for="stat in [
          { label: 'Total Enviados', value: tracks.length, icon: 'paper-plane', color: 'blue' },
          { label: 'Abiertos', value: tracks.filter(t => t.abierto_at).length, icon: 'envelope-open', color: 'indigo' },
          { label: 'Clics en Enlace', value: tracks.filter(t => t.clic_at).length, icon: 'link', color: 'emerald' },
          { label: 'Clics Interés (WhatsApp)', value: tracks.filter(t => t.interes_at).length, icon: 'fire', color: 'orange' }
        ]" :key="stat.label" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
          <div :class="`w-10 h-10 rounded-xl bg-${stat.color}-500/10 text-${stat.color}-500 flex items-center justify-center mb-4`">
            <FontAwesomeIcon :icon="stat.icon" />
          </div>
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ stat.label }}</p>
          <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</p>
        </div>
      </div>

      <!-- Detail Table -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Cliente</th>
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Enviado</th>
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Abierto</th>
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Clic</th>
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">INTERÉS</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
            <tr v-for="track in tracks" :key="track.cliente_email" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all">
              <td class="px-8 py-5">
                <p class="font-bold text-slate-900 dark:text-white">{{ track.cliente_nombre }}</p>
                <p class="text-xs text-slate-400 font-medium">{{ track.cliente_email }}</p>
              </td>
              <td class="px-8 py-5 text-center text-xs font-medium text-slate-500">
                {{ formatDate(track.enviado_at) }}
              </td>
              <td class="px-8 py-5 text-center">
                <span v-if="track.abierto_at" class="bg-indigo-500/10 text-indigo-500 px-3 py-1 rounded-full text-[10px] font-black uppercase">
                  {{ formatDate(track.abierto_at) }}
                </span>
                <span v-else class="text-slate-300">—</span>
              </td>
              <td class="px-8 py-5 text-center">
                <span v-if="track.clic_at" class="bg-emerald-500/10 text-emerald-500 px-3 py-1 rounded-full text-[10px] font-black uppercase">
                  {{ formatDate(track.clic_at) }}
                </span>
                <span v-else class="text-slate-300">—</span>
              </td>
              <td class="px-8 py-5 text-center">
                <div v-if="track.interes_at" class="flex flex-col items-center gap-1">
                  <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-[10px] font-black uppercase shadow-lg shadow-orange-500/20 flex items-center gap-2">
                    <FontAwesomeIcon icon="fire" />
                    LO QUIERE
                  </span>
                  <p class="text-[9px] font-bold text-orange-500 uppercase mt-1">{{ formatDate(track.interes_at) }}</p>
                </div>
                <span v-else class="text-slate-300">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
