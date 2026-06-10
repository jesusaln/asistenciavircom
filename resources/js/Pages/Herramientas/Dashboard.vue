<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import useCompanyColors from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

const props = defineProps({
  estadisticas: { type: Object, required: true },
  mantenimiento_urgente: { type: Array, default: () => [] },
  vida_util_proxima: { type: Array, default: () => [] },
  por_categoria: { type: Array, default: () => [] },
  mas_utilizadas: { type: Array, default: () => [] },
})

const { colors, headerGradientStyle } = useCompanyColors()

const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => { if (observer) observer.disconnect() })

const stats = computed(() => [
  { label: 'Total', value: props.estadisticas.total_herramientas, color: 'text-blue-600', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', bg: 'bg-sky-100/50 dark:bg-sky-900/30' },
  { label: 'Disponibles', value: props.estadisticas.herramientas_disponibles, color: 'text-emerald-600', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', bg: 'bg-emerald-100/50 dark:bg-slate-800/30' },
  { label: 'Asignadas', value: props.estadisticas.herramientas_asignadas, color: 'text-blue-500', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', bg: 'bg-sky-100/50 dark:bg-sky-900/30' },
  { label: 'En Taller', value: props.estadisticas.herramientas_mantenimiento, color: 'text-brand-500', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', bg: 'bg-brand-100/50 dark:bg-brand-900/30' },
  { label: 'De Baja', value: props.estadisticas.herramientas_baja, color: 'text-rose-500', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', bg: 'bg-rose-100/50 dark:bg-rose-900/30' },
  { label: 'Alertas', value: props.estadisticas.herramientas_requieren_mantenimiento, color: 'text-orange-600', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z', bg: 'bg-brand-100/50 dark:bg-brand-900/30' },
])
</script>

<template>
  <Head title="Dashboard Herramientas" />

  <div class="min-h-screen bg-[var(--ui-surface)] pb-12 transition-colors duration-200">
    
    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 transition-colors">
      <div :style="headerGradientStyle" class="absolute inset-0 opacity-10"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Dashboard Herramientas</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium italic">Inteligencia y Control de Activos</p>
          </div>
          <div class="flex items-center gap-2">
            <Link :href="route('herramientas.index')" class="px-6 py-3 bg-blue-600 text-white text-xs font-black uppercase tracking-wide rounded-2xl hover:bg-blue-700 transition-all shadow-xl shadow-sky-200 dark:shadow-none active:scale-95">
              Catálogo Completo
            </Link>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
      
      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div v-for="stat in stats" :key="stat.label" class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 transition-all hover:scale-105">
          <div :class="stat.bg" class="w-10 h-10 rounded-xl flex items-center justify-center mb-4 transition-colors">
            <svg class="w-4 h-4" :class="stat.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" /></svg>
          </div>
          <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ stat.label }}</p>
          <p :class="stat.color" class="text-2xl font-black tabular-nums mt-1">{{ stat.value || 0 }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Mantenimiento Urgente -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex items-center justify-between bg-rose-50 dark:bg-rose-900/20/30 dark:bg-rose-950/10">
              <div class="flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20/40 flex items-center justify-center text-rose-600 animate-pulse italic font-black">!</span>
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Mantenimiento Urgente</h2>
              </div>
              <span class="px-3 py-1 bg-rose-50 dark:bg-rose-900/20/30 text-rose-600 text-[10px] font-black uppercase tracking-wide rounded-full">{{ mantenimiento_urgente.length }} Críticos</span>
            </div>
            <div class="p-8">
              <div v-if="mantenimiento_urgente.length === 0" class="py-12 text-center">
                <p class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide text-xs italic">— Sin Alertas Críticas —</p>
              </div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link v-for="h in mantenimiento_urgente" :key="h.id" :href="route('herramientas.show', h.id)" class="group p-4 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-brand-400 dark:hover:border-brand-500 transition-all flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl overflow-hidden bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex-shrink-0">
                    <img v-if="h.foto" :src="`/storage/${h.foto}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    <div v-else class="w-full h-full flex items-center justify-center text-slate-300 italic font-black text-xl">?</div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-black text-slate-900 dark:text-white truncate group-hover:text-rose-600 transition-colors">{{ h.nombre }}</p>
                    <p class="text-[10px] text-slate-500 font-mono tracking-tighter">{{ h.numero_serie || 'SIN SERIE' }}</p>
                    <div class="mt-2 flex items-center gap-2">
                      <span class="text-[9px] font-black text-rose-500 uppercase">{{ h.dias_desde_ultimo_mantenimiento }} Días Vencido</span>
                    </div>
                  </div>
                </Link>
              </div>
            </div>
          </div>

          <!-- Próximas a Vencer Vida Útil -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex items-center justify-between bg-brand-50 dark:bg-brand-900/20/30 dark:bg-amber-950/10">
              <div class="flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/20/40 flex items-center justify-center text-brand-600 italic font-black">V</span>
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Vida Útil al Límite</h2>
              </div>
            </div>
            <div class="p-8">
              <div v-if="vida_util_proxima.length === 0" class="py-12 text-center">
                <p class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide text-xs italic">— Estado Óptimo —</p>
              </div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link v-for="h in vida_util_proxima" :key="h.id" :href="route('herramientas.show', h.id)" class="group p-4 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-brand-500 transition-all flex items-center gap-4">
                  <div class="flex-1">
                    <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ h.nombre }}</p>
                    <div class="mt-2 w-full bg-slate-200 dark:bg-slate-800 h-1 rounded-full overflow-hidden">
                      <div class="bg-brand-500 h-full" :style="{ width: `${h.porcentaje_vida_util || 0}%` }"></div>
                    </div>
                    <p class="text-[9px] font-black text-brand-600 mt-1 uppercase tracking-wide">{{ h.porcentaje_vida_util }}% Utilizado</p>
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Categorías y Acciones -->
        <div class="space-y-6">
          
          <!-- Herramientas por Categoría -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <h2 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-6">Distribución por Categoría</h2>
            <div class="space-y-6">
              <div v-for="cat in por_categoria" :key="cat.categoria" class="flex items-center justify-between group">
                <div class="flex items-center gap-2">
                  <div class="w-2 h-2 rounded-full bg-brand-500 group-hover:scale-150 transition-transform"></div>
                  <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ cat.categoria }}</span>
                </div>
                <span class="text-sm font-black text-slate-900 dark:text-white">{{ cat.total }}</span>
              </div>
            </div>
          </div>

          <!-- Acciones Rápidas Modernas -->
          <div class="grid grid-cols-1 gap-4">
            <Link :href="route('herramientas.create')" class="group p-6 bg-blue-600 rounded-3xl shadow-xl shadow-sky-200 dark:shadow-none hover:bg-blue-700 transition-all relative overflow-hidden">
              <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
              <p class="text-white font-black uppercase tracking-wide text-xs relative z-10">Nueva Herramienta</p>
              <p class="text-blue-100 text-[10px] mt-1 relative z-10 font-medium">Registrar nuevo activo</p>
            </Link>

            <Link :href="route('herramientas.gestion.index')" class="group p-6 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 transition-all">
              <p class="text-slate-900 dark:text-white font-black uppercase tracking-wide text-xs">Asignar Equipos</p>
              <p class="text-slate-500 dark:text-slate-500 text-[10px] mt-1 font-medium">Gestionar custodia por técnico</p>
            </Link>

            <Link :href="route('herramientas.mantenimiento')" class="group p-6 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 transition-all">
              <p class="text-slate-900 dark:text-white font-black uppercase tracking-wide text-xs">Mantenimiento</p>
              <p class="text-slate-500 dark:text-slate-500 text-[10px] mt-1 font-medium">Ver historial de servicios</p>
            </Link>
          </div>

          <!-- Más Utilizadas -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <h2 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-6">Equipos con Mayor Rotación</h2>
            <div class="space-y-6">
              <div v-for="h in mas_utilizadas.slice(0, 5)" :key="h.id" class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-black text-slate-900 dark:text-white uppercase truncate max-w-[150px]">{{ h.nombre }}</p>
                  <p class="text-[9px] text-slate-500 font-mono italic">{{ h.numero_serie || 'S/N' }}</p>
                </div>
                <div class="text-right">
                  <span class="text-sm font-black text-blue-600 dark:text-blue-400">{{ h.usos }}</span>
                  <p class="text-[8px] font-black text-slate-400 uppercase tracking-wide">Asignaciones</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Transiciones suaves para modo oscuro */
* {
  transition-property: background-color, border-color, color, fill, stroke;
  transition-duration: 300ms;
}
</style>
