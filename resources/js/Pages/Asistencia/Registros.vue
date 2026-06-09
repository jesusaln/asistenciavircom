<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

defineProps({
  registros: { type: Array, default: () => [] },
})

const { cssVars, colors } = useCompanyColors()
</script>

<template>
  <Head title="Bitácora de Asistencia" />

  <div class="min-h-screen bg-white transition-colors dark:bg-gray-900" :style="cssVars">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
      <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Bitácora de Asistencia</h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Historial de checadas del personal (en desarrollo).
          </p>
        </div>
        <Link
          href="/asistencia"
          class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700/60"
        >
          Volver al reloj
        </Link>
      </div>

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
      >
        <div
          class="border-b border-gray-200 px-6 py-4 dark:border-gray-700"
          :style="{ background: `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }"
        >
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Registros</h2>
        </div>
        <div class="px-6 py-16 text-center">
          <p class="text-gray-600 dark:text-gray-400">
            Aún no hay datos de asistencia en el sistema. Cuando exista el modelo y la API de checador, aparecerán aquí.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
