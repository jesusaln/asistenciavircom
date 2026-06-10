<script setup>
import { Head, Link, usePoll } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

const props = defineProps({
  usuario: { type: Object, default: () => ({}) },
})

usePoll(60000, { only: ['usuario'] })

const { cssVars, colors } = useCompanyColors()

const ahora = ref(new Date())
let timer

onMounted(() => {
  timer = setInterval(() => {
    ahora.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const horaFormateada = computed(() =>
  ahora.value.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
)

const fechaFormateada = computed(() =>
  ahora.value.toLocaleDateString('es-MX', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }),
)
</script>

<template>
  <Head title="Reloj Checador" />

  <div class="min-h-screen bg-[var(--ui-surface)] transition-colors dark:bg-slate-800" :style="cssVars">
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
      <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Reloj Checador</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Hola, <span class="font-semibold text-slate-800 dark:text-slate-200">{{ usuario.name }}</span>
        </p>
      </div>

      <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
      >
        <div
          class="border-b border-slate-200 px-6 py-4 dark:border-slate-700"
          :style="{ background: `linear-gradient(135deg, ${colors.principal}18 0%, ${colors.secundario}12 100%)` }"
        >
          <p class="text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            Hora local
          </p>
          <p class="mt-1 text-center text-4xl font-black tabular-nums text-slate-900 dark:text-white">
            {{ horaFormateada }}
          </p>
          <p class="mt-2 text-center text-sm capitalize text-slate-500 dark:text-slate-400">
            {{ fechaFormateada }}
          </p>
        </div>

        <div class="space-y-6 px-6 py-8">
          <p class="text-center text-sm text-slate-500 dark:text-slate-400">
            El registro de entradas y salidas con checador se integrará aquí. Mientras tanto puedes usar
            <strong class="text-slate-800 dark:text-slate-200">Empleados</strong> y el resto del módulo de personal desde el menú.
          </p>
          <div class="flex flex-wrap justify-center gap-3">
            <Link
              :href="route('empleados.index')"
              class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700/60"
            >
              Ir a Empleados
            </Link>
            <Link
              href="/panel"
              class="inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:brightness-105"
              :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
            >
              Volver al panel
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
