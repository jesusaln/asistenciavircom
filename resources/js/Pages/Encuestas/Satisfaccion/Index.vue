<template>
  <Head title="Encuestas de satisfacción" />
  <AppLayout>
    <div class="p-6 space-y-6">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--ui-accent)]">{{ brandName }}</p>
        <h1 class="mt-2 text-2xl font-black text-[var(--ui-text-main)]">Encuestas de satisfacción</h1>
        <p class="mt-1 text-sm text-[var(--ui-text-soft)]">Opiniones posteriores a instalaciones completadas y cupones emitidos.</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] p-4">
          <p class="text-xs font-bold uppercase tracking-wide text-[var(--ui-text-soft)]">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-black text-[var(--ui-text-main)]">{{ card.value }}</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)]">
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-[var(--ui-border)] text-xs uppercase tracking-wide text-[var(--ui-text-soft)]">
              <tr><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Cita</th><th class="px-4 py-3">Calificación</th><th class="px-4 py-3">Comentario</th><th class="px-4 py-3">Cupón</th></tr>
            </thead>
            <tbody class="divide-y divide-[var(--ui-border)]">
              <tr v-for="encuesta in encuestas.data" :key="encuesta.id">
                <td class="px-4 py-3 font-bold text-[var(--ui-text-main)]">{{ encuesta.cliente?.nombre_razon_social || 'Sin cliente' }}</td>
                <td class="px-4 py-3 text-[var(--ui-text-soft)]">{{ encuesta.cita?.folio || 'Sin cita' }}</td>
                <td class="px-4 py-3 text-[var(--ui-text-main)]">{{ encuesta.calificacion ? `${encuesta.calificacion}/5` : 'Pendiente' }}</td>
                <td class="max-w-md px-4 py-3 text-[var(--ui-text-soft)]">{{ encuesta.comentario || 'Pendiente' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-[var(--ui-accent)]">{{ encuesta.cupon_codigo || 'Pendiente' }}</td>
              </tr>
              <tr v-if="!encuestas.data.length"><td colspan="5" class="px-4 py-10 text-center text-[var(--ui-text-soft)]">Aún no hay respuestas.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="border-t border-[var(--ui-border)] px-4 py-3 text-xs text-[var(--ui-text-soft)]">{{ encuestas.total }} registros</div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  encuestas: { type: Object, required: true },
  resumen: { type: Object, required: true },
  brandName: { type: String, default: 'Servicio' },
});

const cards = computed(() => [
  { label: 'Total', value: props.resumen.total },
  { label: 'Respondidas', value: props.resumen.respondidas },
  { label: 'Promedio', value: `${props.resumen.promedio}/5` },
  { label: 'Cupones', value: props.resumen.cupones },
]);
</script>
