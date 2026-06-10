<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import useCompanyColors from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnicos: { type: [Array, Object], required: true },
})

const { headerGradientStyle } = useCompanyColors()
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

const rows = computed(() => Array.isArray(props.tecnicos) ? props.tecnicos : [props.tecnicos])

const calcularEstadisticasTecnico = (tecnico) => {
  if (!tecnico.herramientas) return { total: 0, disponibles: 0, mantenimiento: 0, asignadas: 0 }
  const herramientas = tecnico.herramientas || []
  return {
    total: herramientas.length,
    disponibles: herramientas.filter(h => h.estado === 'disponible').length,
    mantenimiento: herramientas.filter(h => h.estado === 'mantenimiento').length,
    asignadas: herramientas.filter(h => h.estado === 'asignada').length,
  }
}

const getEstadoClasses = (estado) => {
  const classes = {
    'disponible': 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
    'asignada': 'bg-brand-500/10 text-blue-400 border-blue-500/20',
    'mantenimiento': 'bg-brand-500/10 text-brand-400 border-brand-500/20',
    'baja': 'bg-brand-500/10 text-rose-400 border-rose-500/20',
    'perdida': 'bg-brand-500/10 text-rose-400 border-rose-500/20',
  }
  return classes[estado] || 'bg-slate-500/10 text-slate-400 border-slate-500/20'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'disponible': 'Disponible',
    'asignada': 'Asignada',
    'mantenimiento': 'En Mant.',
    'baja': 'Baja',
    'perdida': 'Perdida',
  }
  return labels[estado] || estado
}

// Global stats
const globalStats = computed(() => {
  return {
    total: rows.value.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).total, 0),
    disponibles: rows.value.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).disponibles, 0),
    asignadas: rows.value.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).asignadas, 0),
    mantenimiento: rows.value.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).mantenimiento, 0),
  }
})
</script>

<template>
  <Head title="Gestión por Técnico" />

  <div class="min-h-screen bg-[var(--ui-surface)] pb-12 transition-colors duration-200">
    
    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 transition-colors">
      <div :style="headerGradientStyle" class="absolute inset-0 opacity-10"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Custodia de Equipos</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium italic">Control de Herramientas Asignadas por Técnico</p>
          </div>
          <div class="flex items-center gap-2">
            <Link href="/herramientas/dashboard" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-200 text-xs font-black uppercase tracking-wide rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">
              Dashboard
            </Link>
            <Link href="/herramientas/gestion/create" class="px-6 py-3 bg-blue-600 text-white text-xs font-black uppercase tracking-wide rounded-2xl hover:bg-blue-700 transition-all shadow-xl shadow-sky-200 dark:shadow-none">
              Nueva Asignación
            </Link>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
      
      <!-- Global Stats Bar -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700">
          <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total Asignado</p>
          <p class="text-2xl font-black text-blue-600 dark:text-blue-400 tabular-nums">{{ globalStats.total }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700">
          <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">En Uso</p>
          <p class="text-2xl font-black text-emerald-600 dark:text-slate-400 tabular-nums">{{ globalStats.asignadas }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700">
          <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">En Taller</p>
          <p class="text-2xl font-black text-brand-500 tabular-nums">{{ globalStats.mantenimiento }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700">
          <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Técnicos</p>
          <p class="text-2xl font-black text-purple-600 dark:text-purple-400 tabular-nums">{{ rows.length }}</p>
        </div>
      </div>

      <!-- Tech List -->
      <div class="space-y-6">
        <div v-for="t in rows" :key="t.id" class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden transition-all hover:shadow-2xl">
          
          <!-- Card Header -->
          <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-slate-50/50 dark:bg-slate-800/20">
            <div class="flex items-center gap-5">
              <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-2xl font-black text-white shadow-xl uppercase">
                {{ (t.nombre_completo || t.nombre).charAt(0) }}
              </div>
              <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ t.nombre_completo || t.nombre }}</h2>
                <div class="flex items-center gap-4 mt-1">
                  <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide">{{ t.email || 'SIN EMAIL' }}</span>
                  <span class="text-slate-300 dark:text-slate-500">•</span>
                  <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide">{{ t.telefono || 'SIN TEL' }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-4 bg-[var(--ui-surface)] p-4 rounded-2xl border border-slate-100 dark:border-slate-700">
              <div class="text-right px-4 border-r border-slate-100 dark:border-slate-700">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Items</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ calcularEstadisticasTecnico(t).total }}</p>
              </div>
              <div class="flex gap-2 pl-2">
                <Link :href="`/herramientas/gestion/${t.id}/edit`" class="p-3 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-sky-100 transition-all active:scale-90">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </Link>
                <Link v-if="calcularEstadisticasTecnico(t).total > 0" :href="`/herramientas/gestion/${t.id}/exportar`" class="p-3 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 rounded-xl hover:bg-emerald-100 transition-all active:scale-90">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </Link>
              </div>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-8">
            <div v-if="t.herramientas && t.herramientas.length > 0">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="h in t.herramientas.slice(0, 6)" :key="h.id" class="group p-4 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl overflow-hidden bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex-shrink-0">
                    <img v-if="h.foto" :src="`/storage/${h.foto}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    <div v-else class="w-full h-full flex items-center justify-center text-slate-300 italic font-black text-xl">?</div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-black text-slate-900 dark:text-white truncate uppercase">{{ h.nombre }}</p>
                    <div class="flex items-center justify-between mt-1">
                      <p class="text-[9px] text-slate-500 font-mono tracking-tighter">{{ h.numero_serie || 'SIN SERIE' }}</p>
                      <span :class="getEstadoClasses(h.estado)" class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wide border transition-colors">{{ getEstadoLabel(h.estado) }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="t.herramientas.length > 6" class="mt-6 pt-6 border-t border-slate-50 dark:border-slate-700 flex justify-center">
                <Link :href="`/herramientas/gestion/${t.id}/edit`" class="text-[10px] font-black text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 transition-colors uppercase tracking-wide italic">
                  + Gestionar inventario completo ({{ t.herramientas.length }} items) →
                </Link>
              </div>
            </div>
            <div v-else class="py-12 text-center bg-[var(--ui-surface)]/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
              <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic mb-4">Sin Herramientas Asignadas</p>
              <Link :href="`/herramientas/gestion/${t.id}/edit`" class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-wide rounded-xl hover:scale-105 transition-all">
                Realizar Asignación
              </Link>
            </div>
          </div>

        </div>
      </div>

      <!-- Empty State -->
      <div v-if="rows.length === 0" class="text-center py-20 bg-white dark:bg-slate-800 rounded-[40px] shadow-xl border border-slate-100 dark:border-slate-700">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">No se encontraron técnicos</h2>
        <p class="text-slate-500 mt-2 font-medium italic">Agrega técnicos para comenzar la gestión de equipos</p>
      </div>

      <!-- Quick Actions Footer -->
      <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4">
        <Link href="/herramientas/gestion/create" class="p-8 bg-blue-600 rounded-3xl shadow-xl shadow-blue-100 dark:shadow-none hover:bg-blue-700 transition-all text-center">
          <p class="text-white font-black uppercase tracking-wide text-[10px]">Nueva Asignación</p>
        </Link>
        <Link href="/herramientas" class="p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 transition-all text-center group">
          <p class="text-slate-900 dark:text-white font-black uppercase tracking-wide text-[10px] group-hover:text-blue-600 transition-colors">Catálogo General</p>
        </Link>
        <Link :href="route('herramientas.mantenimiento')" class="p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 transition-all text-center group">
          <p class="text-slate-900 dark:text-white font-black uppercase tracking-wide text-[10px] group-hover:text-brand-500 transition-colors">Mantenimiento</p>
        </Link>
        <Link href="/herramientas/dashboard" class="p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 transition-all text-center group">
          <p class="text-slate-900 dark:text-white font-black uppercase tracking-wide text-[10px] group-hover:text-purple-600 transition-colors">Vista Dashboard</p>
        </Link>
      </div>

    </div>
  </div>
</template>

<style scoped>
* {
  transition-property: background-color, border-color, color, fill, stroke, transform;
  transition-duration: 300ms;
}
</style>
