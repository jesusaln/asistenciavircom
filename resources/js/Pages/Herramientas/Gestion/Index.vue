<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnicos: { type: [Array, Object], required: true },
})

const rows = computed(() => Array.isArray(props.tecnicos) ? props.tecnicos : [props.tecnicos])

const getEstadoColor = (estado) => {
  const colors = {
    'disponible': 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
    'asignada': 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
    'mantenimiento': 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
    'baja': 'bg-red-500/20 text-red-400 border border-red-500/30',
    'perdida': 'bg-red-500/20 text-red-400 border border-red-500/30',
  }
  return colors[estado] || 'bg-slate-600/20 text-slate-400 border border-slate-500/30'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'disponible': 'Disponible',
    'asignada': 'Asignada',
    'mantenimiento': 'En Mant.',
    'baja': 'De Baja',
    'perdida': 'Perdida',
  }
  return labels[estado] || estado
}

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
</script>

<template>
  <Head title="Gestión de Herramientas por Técnico" />

  <!-- Header con gradiente -->
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-3xl font-bold bg-gradient-to-r from-white via-blue-100 to-blue-300 bg-clip-text text-transparent">
        Gestión de Herramientas
      </h1>
      <p class="text-slate-400 mt-1">Administrar herramientas asignadas por técnico</p>
    </div>
    <div class="flex gap-3">
      <Link 
        class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white rounded-xl hover:from-emerald-500 hover:to-emerald-400 transition-all duration-300 shadow-lg shadow-emerald-500/25 font-medium" 
        href="/herramientas/gestion/create"
      >
        <span class="flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Nueva Asignación
        </span>
      </Link>
      <Link 
        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-xl hover:from-blue-500 hover:to-blue-400 transition-all duration-300 shadow-lg shadow-blue-500/25 font-medium" 
        href="/herramientas-dashboard"
      >
        <span class="flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          Dashboard General
        </span>
      </Link>
    </div>
  </div>

  <!-- Estadísticas generales con glassmorphism -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="relative overflow-hidden bg-slate-800/50 backdrop-blur-xl p-5 rounded-2xl border border-slate-700/50 shadow-xl group hover:border-blue-500/50 transition-all duration-300">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
      <div class="relative">
        <div class="text-3xl font-bold text-blue-400">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).total, 0) }}</div>
        <div class="text-sm text-slate-400 mt-1">Total Herramientas</div>
      </div>
      <div class="absolute -right-4 -top-4 w-20 h-20 bg-blue-500/10 rounded-full blur-2xl"></div>
    </div>
    <div class="relative overflow-hidden bg-slate-800/50 backdrop-blur-xl p-5 rounded-2xl border border-slate-700/50 shadow-xl group hover:border-emerald-500/50 transition-all duration-300">
      <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
      <div class="relative">
        <div class="text-3xl font-bold text-emerald-400">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).disponibles, 0) }}</div>
        <div class="text-sm text-slate-400 mt-1">Disponibles</div>
      </div>
      <div class="absolute -right-4 -top-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-2xl"></div>
    </div>
    <div class="relative overflow-hidden bg-slate-800/50 backdrop-blur-xl p-5 rounded-2xl border border-slate-700/50 shadow-xl group hover:border-cyan-500/50 transition-all duration-300">
      <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
      <div class="relative">
        <div class="text-3xl font-bold text-cyan-400">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).asignadas, 0) }}</div>
        <div class="text-sm text-slate-400 mt-1">Asignadas</div>
      </div>
      <div class="absolute -right-4 -top-4 w-20 h-20 bg-cyan-500/10 rounded-full blur-2xl"></div>
    </div>
    <div class="relative overflow-hidden bg-slate-800/50 backdrop-blur-xl p-5 rounded-2xl border border-slate-700/50 shadow-xl group hover:border-amber-500/50 transition-all duration-300">
      <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
      <div class="relative">
        <div class="text-3xl font-bold text-amber-400">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).mantenimiento, 0) }}</div>
        <div class="text-sm text-slate-400 mt-1">En Mantenimiento</div>
      </div>
      <div class="absolute -right-4 -top-4 w-20 h-20 bg-amber-500/10 rounded-full blur-2xl"></div>
    </div>
  </div>

  <!-- Lista de técnicos con herramientas -->
  <div class="space-y-6">
    <div v-for="tecnico in rows" :key="tecnico.id" class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 shadow-xl overflow-hidden hover:border-slate-600/50 transition-all duration-300">
      <!-- Header del técnico -->
      <div class="p-6 border-b border-slate-700/50 bg-gradient-to-r from-slate-800/80 to-slate-900/80">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-blue-500/25">
              {{ (tecnico.nombre_completo || tecnico.nombre || 'T').charAt(0).toUpperCase() }}
            </div>
            <div>
              <h2 class="text-xl font-semibold text-white">{{ tecnico.nombre_completo || tecnico.nombre }}</h2>
              <p class="text-slate-400 text-sm">{{ tecnico.email || 'Sin email' }} • {{ tecnico.telefono || 'Sin teléfono' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-6">
            <div class="text-right">
              <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Herramientas</div>
              <div class="text-3xl font-bold text-blue-400">{{ calcularEstadisticasTecnico(tecnico).total }}</div>
            </div>
            <div class="flex gap-2">
              <Link
                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-lg shadow-blue-600/25"
                :href="`/herramientas/gestion/${tecnico.id}/edit`"
              >
                Gestionar
              </Link>
              <Link
                v-if="calcularEstadisticasTecnico(tecnico).total > 0"
                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-lg shadow-emerald-600/25"
                :href="`/herramientas/gestion/${tecnico.id}/exportar`"
              >
                Reporte
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas del técnico -->
      <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="text-center p-4 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
            <div class="text-2xl font-bold text-emerald-400">{{ calcularEstadisticasTecnico(tecnico).disponibles }}</div>
            <div class="text-xs text-slate-400 mt-1">Disponibles</div>
          </div>
          <div class="text-center p-4 bg-blue-500/10 rounded-xl border border-blue-500/20">
            <div class="text-2xl font-bold text-blue-400">{{ calcularEstadisticasTecnico(tecnico).asignadas }}</div>
            <div class="text-xs text-slate-400 mt-1">Asignadas</div>
          </div>
          <div class="text-center p-4 bg-amber-500/10 rounded-xl border border-amber-500/20">
            <div class="text-2xl font-bold text-amber-400">{{ calcularEstadisticasTecnico(tecnico).mantenimiento }}</div>
            <div class="text-xs text-slate-400 mt-1">En Mantenimiento</div>
          </div>
          <div class="text-center p-4 bg-slate-700/30 rounded-xl border border-slate-600/30">
            <div class="text-lg font-medium text-slate-300 truncate">{{ tecnico.email || 'Sin email' }}</div>
            <div class="text-xs text-slate-400 mt-1">Contacto</div>
          </div>
        </div>

        <!-- Lista de herramientas del técnico (si las tiene) -->
        <div v-if="tecnico.herramientas && tecnico.herramientas.length > 0" class="mt-4">
          <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Herramientas Asignadas</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
              v-for="herramienta in tecnico.herramientas.slice(0, 6)" 
              :key="herramienta.id" 
              class="p-4 bg-slate-900/50 rounded-xl border border-slate-700/50 hover:border-slate-600/50 transition-all duration-200 group"
            >
              <div class="flex items-center gap-4">
                <img 
                  v-if="herramienta.foto" 
                  :src="`/storage/${herramienta.foto}`" 
                  alt="Foto" 
                  class="w-12 h-12 object-cover rounded-xl ring-2 ring-slate-700 group-hover:ring-blue-500/50 transition-all duration-200" 
                />
                <div v-else class="w-12 h-12 bg-slate-700/50 rounded-xl flex items-center justify-center ring-2 ring-slate-700 group-hover:ring-blue-500/50 transition-all duration-200">
                  <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-medium text-white truncate">{{ herramienta.nombre }}</h4>
                  <p class="text-xs text-slate-500 truncate">{{ herramienta.numero_serie || 'N/A' }}</p>
                  <span :class="['text-xs px-2.5 py-1 rounded-full inline-block mt-1', getEstadoColor(herramienta.estado)]">
                    {{ getEstadoLabel(herramienta.estado) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="tecnico.herramientas.length > 6" class="mt-4 text-center">
            <span class="text-sm text-slate-500">Y {{ tecnico.herramientas.length - 6 }} herramientas más...</span>
          </div>
        </div>

        <!-- Sin herramientas asignadas -->
        <div v-else class="text-center py-12">
          <div class="w-20 h-20 mx-auto mb-4 bg-slate-700/30 rounded-2xl flex items-center justify-center">
            <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
          </div>
          <p class="text-slate-500 mb-3">No tiene herramientas asignadas</p>
          <Link
            :href="`/herramientas/gestion/${tecnico.id}/edit`"
            class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors"
          >
            Asignar herramientas →
          </Link>
        </div>
      </div>
    </div>
  </div>

  <!-- Sin técnicos -->
  <div v-if="rows.length === 0" class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-16 text-center">
    <div class="w-24 h-24 mx-auto mb-6 bg-slate-700/30 rounded-2xl flex items-center justify-center">
      <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
      </svg>
    </div>
    <h2 class="text-2xl font-semibold text-white mb-2">No hay técnicos disponibles</h2>
    <p class="text-slate-400">Agrega técnicos al sistema para gestionar sus herramientas.</p>
  </div>

  <!-- Acciones rápidas -->
  <div class="mt-10 bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6 shadow-xl">
    <h2 class="text-lg font-semibold text-white mb-6">Acciones Rápidas</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Link 
        href="/herramientas/gestion/create" 
        class="flex flex-col items-center p-5 bg-blue-500/10 rounded-xl border border-blue-500/20 hover:bg-blue-500/20 hover:border-blue-500/40 transition-all duration-300 group"
      >
        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
        </div>
        <span class="text-sm font-medium text-blue-400">Nueva Asignación</span>
      </Link>
      <Link 
        :href="route('herramientas-mantenimiento')" 
        class="flex flex-col items-center p-5 bg-amber-500/10 rounded-xl border border-amber-500/20 hover:bg-amber-500/20 hover:border-amber-500/40 transition-all duration-300 group"
      >
        <div class="w-14 h-14 bg-amber-500/20 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </div>
        <span class="text-sm font-medium text-amber-400">Gestionar Mantenimiento</span>
      </Link>
      <Link 
        href="/herramientas-alertas" 
        class="flex flex-col items-center p-5 bg-red-500/10 rounded-xl border border-red-500/20 hover:bg-red-500/20 hover:border-red-500/40 transition-all duration-300 group"
      >
        <div class="w-14 h-14 bg-red-500/20 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <span class="text-sm font-medium text-red-400">Ver Alertas</span>
      </Link>
      <Link 
        href="/herramientas" 
        class="flex flex-col items-center p-5 bg-emerald-500/10 rounded-xl border border-emerald-500/20 hover:bg-emerald-500/20 hover:border-emerald-500/40 transition-all duration-300 group"
      >
        <div class="w-14 h-14 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <span class="text-sm font-medium text-emerald-400">Catálogo General</span>
      </Link>
    </div>
  </div>
</template>
