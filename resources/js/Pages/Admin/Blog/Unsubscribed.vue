<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  bajas: Array
})

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head title="Bajas del Newsletter" />

  <div class="p-6 min-h-screen bg-slate-50 dark:bg-slate-950">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-10 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="route('admin.blog.index')" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center text-slate-500 shadow-sm border border-slate-100 dark:border-slate-800 hover:text-[var(--color-primary)] transition-all">
            <FontAwesomeIcon icon="arrow-left" />
          </Link>
          <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white leading-tight">Usuarios que abandonaron el boletín</h1>
            <p class="text-slate-500 font-medium text-sm">Historial de bajas automáticas</p>
          </div>
        </div>
      </div>

      <!-- Detail Table -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Cliente</th>
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Fecha de Baja</th>
              <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
            <tr v-for="baja in bajas" :key="baja.email" class="hover:bg-red-50/30 dark:hover:bg-red-950/10 transition-all">
              <td class="px-8 py-5">
                <p class="font-bold text-slate-900 dark:text-white">{{ baja.nombre_razon_social }}</p>
                <p class="text-xs text-slate-400 font-medium">{{ baja.email }}</p>
              </td>
              <td class="px-8 py-5 text-sm font-medium text-slate-500">
                {{ formatDate(baja.newsletter_unsubscribed_at) }}
              </td>
              <td class="px-8 py-5 text-right">
                <span class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-black uppercase border border-red-500/20">
                  Desuscrito
                </span>
              </td>
            </tr>
            <tr v-if="bajas.length === 0">
              <td colspan="3" class="px-8 py-20 text-center">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-300">
                  <FontAwesomeIcon icon="user-check" size="2x" />
                </div>
                <p class="text-slate-500 font-bold uppercase text-xs tracking-widest">¡Nadie se ha ido todavía!</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
