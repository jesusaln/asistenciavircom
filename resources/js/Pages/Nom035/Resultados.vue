<script setup>
import { ref } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  respondent: Object,
  results: [Object, Array],
})

const r = ref(props.results)
</script>

<template>
  <Head title="Resultados NOM-035" />
  <div class="py-6 px-4 sm:px-6 max-w-3xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('nom035.periodos.show', respondent.evaluation_period_id)" class="text-sm text-brand-600 hover:text-amber-700">&larr; Volver</Link>
      <h1 class="text-xl font-bold text-slate-900 dark:text-white">Evaluación - {{ respondent.empleado?.nombre || 'Empleado' }}</h1>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 shadow-sm mb-8 text-center">
      <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-2">Nivel de Riesgo</p>
      <p class="text-3xl font-black"
        :class="r?.total_level === 'Muy alto' || r?.total_level?.includes('Requiere') ? 'text-rose-600' 
          : r?.total_level === 'Alto' ? 'text-orange-600' 
          : r?.total_level === 'Medio' ? 'text-amber-600' 
          : 'text-emerald-600'">
        {{ r?.total_level || 'N/A' }}
      </p>
      <p v-if="r?.total !== undefined" class="text-sm text-slate-400 mt-1">Puntaje: {{ r.total }}</p>
    </div>

    <div v-if="r?.domains" class="space-y-3 mb-8">
      <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide px-1">Resultados por dominio</h3>
      <div v-for="(dom, name) in r.domains" :key="name"
        class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex justify-between items-center">
        <span class="text-sm text-slate-700 dark:text-slate-300">{{ name }}</span>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg"
          :class="dom.level === 'Nulo' ? 'bg-emerald-100 text-emerald-700' 
            : dom.level === 'Bajo' ? 'bg-sky-100 text-sky-700'
            : dom.level === 'Medio' ? 'bg-brand-100 text-amber-700'
            : 'bg-rose-100 text-rose-700'">
          {{ dom.level }} ({{ dom.score }})
        </span>
      </div>
    </div>

    <div v-if="r?.categories" class="space-y-3">
      <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide px-1">Por categoría</h3>
      <div v-for="(cat, name) in r.categories" :key="name"
        class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex justify-between items-center">
        <span class="text-sm text-slate-700 dark:text-slate-300">{{ name }}</span>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg"
          :class="cat.level === 'Nulo' ? 'bg-emerald-100 text-emerald-700' 
            : cat.level === 'Bajo' ? 'bg-sky-100 text-sky-700'
            : cat.level === 'Medio' ? 'bg-brand-100 text-amber-700'
            : 'bg-rose-100 text-rose-700'">
          {{ cat.level }} ({{ cat.score }})
        </span>
      </div>
    </div>
  </div>
</template>
