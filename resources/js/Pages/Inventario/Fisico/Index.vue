<template>
  <AppLayout title="Inventario Físico">
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
          <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">Auditorías de Inventario</h2>
          <p class="text-sm font-bold text-[var(--ui-text-soft)] uppercase tracking-wide mt-1">Conteo físico vs existencias en sistema</p>
        </div>
        <Link 
          :href="route('inventarios-fisicos.create')"
          class="premium-button flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-br from-[var(--ui-accent)] to-brand-600 text-white font-black uppercase tracking-wide text-xs shadow-xl shadow-brand-500/20 hover:scale-105 active:scale-95 transition-all duration-200"
        >
          <font-awesome-icon icon="plus" />
          Nueva Auditoría
        </Link>
      </div>
    </template>

    <div class="px-6 pb-12">
      <div class="glass-panel rounded-[2.5rem] overflow-hidden border border-[var(--ui-border)] shadow-2xl">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead>
              <tr class="bg-[var(--ui-surface-soft)]/50 border-b border-[var(--ui-border)]">
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Nombre / Almacén</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Responsable</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Estado</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Inicio</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="audit in auditorias.data" :key="audit.id" class="group hover:bg-[var(--ui-accent)]/[0.02] transition-colors duration-200">
                <td class="px-8 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-black text-[var(--ui-text)] uppercase tracking-wider group-hover:text-[var(--ui-accent)] transition-colors">{{ audit.nombre }}</span>
                    <span class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-wide mt-1">{{ audit.almacen?.nombre }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] flex items-center justify-center text-[10px] font-black text-[var(--ui-text-muted)] border border-[var(--ui-border)] uppercase">
                      {{ audit.user?.name.charAt(0) }}
                    </div>
                    <span class="text-xs font-bold text-[var(--ui-text-soft)] uppercase">{{ audit.user?.name }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <span 
                    class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wide border"
                    :class="{
                      'bg-brand-500/10 border-brand-500/20 text-brand-500': audit.estado === 'borrador',
                      'bg-brand-500/10 border-emerald-500/20 text-emerald-500': audit.estado === 'procesado',
                      'bg-brand-500/10 border-rose-500/20 text-rose-500': audit.estado === 'cancelado'
                    }"
                  >
                    {{ audit.estado }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <span class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-wide">
                    {{ new Date(audit.fecha_inicio).toLocaleDateString() }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <Link 
                    :href="route('inventarios-fisicos.show', audit.id)"
                    class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] flex items-center justify-center text-[var(--ui-text-muted)] hover:bg-[var(--ui-accent)] hover:text-white hover:border-[var(--ui-accent)] transition-all duration-200 shadow-sm"
                  >
                    <font-awesome-icon icon="arrow-right" class="text-xs" />
                  </Link>
                </td>
              </tr>
              <tr v-if="auditorias.data.length === 0">
                <td colspan="5" class="px-8 py-20 text-center">
                  <div class="flex flex-col items-center opacity-30">
                    <font-awesome-icon icon="warehouse" class="text-5xl mb-4" />
                    <p class="text-xs font-black uppercase tracking-[0.3em]">No hay auditorías registradas</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination -->
      <div v-if="auditorias.links.length > 3" class="mt-8 flex justify-center">
        <nav class="flex items-center gap-2">
          <template v-for="(link, k) in auditorias.links" :key="k">
            <div v-if="link.url === null" class="px-4 py-2 text-[10px] font-black uppercase tracking-wide text-[var(--ui-text-muted)] opacity-50" v-html="link.label" />
            <Link v-else :href="link.url" class="px-4 py-2 text-[10px] font-black uppercase tracking-wide rounded-xl transition-all duration-200" :class="link.active ? 'bg-[var(--ui-accent)] text-white shadow-xl shadow-brand-500/20' : 'text-[var(--ui-text-muted)] hover:bg-[var(--ui-surface-soft)]'" v-html="link.label" />
          </template>
        </nav>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faWarehouse, faArrowRight } from '@fortawesome/free-solid-svg-icons';
import { library } from '@fortawesome/fontawesome-svg-core';

library.add(faPlus, faWarehouse, faArrowRight);

defineProps({
  auditorias: Object
});
</script>
