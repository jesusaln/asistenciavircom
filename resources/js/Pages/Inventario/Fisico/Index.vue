<template>
  <AppLayout title="Inventario Físico">
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
          <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-tight">Auditorías de Inventario</h2>
          <p class="text-sm font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">Conteo físico vs existencias en sistema</p>
        </div>
        <Link 
          :href="route('inventarios-fisicos.create')"
          class="premium-button flex items-center gap-3 px-6 py-3 rounded-2xl bg-gradient-to-br from-[var(--ui-accent)] to-amber-600 text-white font-black uppercase tracking-widest text-xs shadow-lg shadow-amber-500/20 hover:scale-105 active:scale-95 transition-all duration-300"
        >
          <font-awesome-icon icon="plus" />
          Nueva Auditoría
        </Link>
      </div>
    </template>

    <div class="px-6 pb-12">
      <div class="glass-panel rounded-[2.5rem] overflow-hidden border border-[var(--ui-border)] shadow-2xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-[var(--ui-surface-soft)]/50 border-b border-[var(--ui-border)]">
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Nombre / Almacén</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Responsable</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Estado</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Inicio</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ui-border)]/50">
              <tr v-for="audit in auditorias.data" :key="audit.id" class="group hover:bg-[var(--ui-accent)]/[0.02] transition-colors duration-300">
                <td class="px-8 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-black text-[var(--ui-text)] uppercase tracking-tight group-hover:text-[var(--ui-accent)] transition-colors">{{ audit.nombre }}</span>
                    <span class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">{{ audit.almacen?.nombre }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[var(--ui-surface-soft)] flex items-center justify-center text-[10px] font-black text-[var(--ui-text-muted)] border border-[var(--ui-border)] uppercase">
                      {{ audit.user?.name.charAt(0) }}
                    </div>
                    <span class="text-xs font-bold text-[var(--ui-text-soft)] uppercase">{{ audit.user?.name }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <span 
                    class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border"
                    :class="{
                      'bg-amber-500/10 border-amber-500/20 text-amber-500': audit.estado === 'borrador',
                      'bg-emerald-500/10 border-emerald-500/20 text-emerald-500': audit.estado === 'procesado',
                      'bg-rose-500/10 border-rose-500/20 text-rose-500': audit.estado === 'cancelado'
                    }"
                  >
                    {{ audit.estado }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <span class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-tighter">
                    {{ new Date(audit.fecha_inicio).toLocaleDateString() }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <Link 
                    :href="route('inventarios-fisicos.show', audit.id)"
                    class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] flex items-center justify-center text-[var(--ui-text-muted)] hover:bg-[var(--ui-accent)] hover:text-white hover:border-[var(--ui-accent)] transition-all duration-300 shadow-sm"
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
            <div v-if="link.url === null" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-[var(--ui-text-muted)] opacity-50" v-html="link.label" />
            <Link v-else :href="link.url" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all duration-300" :class="link.active ? 'bg-[var(--ui-accent)] text-white shadow-lg shadow-amber-500/20' : 'text-[var(--ui-text-muted)] hover:bg-[var(--ui-surface-soft)]'" v-html="link.label" />
          </template>
        </nav>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
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
