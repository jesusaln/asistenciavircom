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
    'disponible': 'bg-green-100 text-green-800',
    'asignada': 'bg-blue-100 text-blue-800',
    'mantenimiento': 'bg-yellow-100 text-yellow-800',
    'baja': 'bg-red-100 text-red-800',
    'perdida': 'bg-red-100 text-red-800',
  }
  return colors[estado] || 'bg-gray-100 text-gray-800'
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

const getEstadoClasses = (estado) => {
  const classes = {
    'disponible': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    'asignada': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'mantenimiento': 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    'baja': 'bg-red-500/10 text-red-400 border-red-500/20',
    'perdida': 'bg-red-500/10 text-red-400 border-red-500/20',
  }
  return classes[estado] || 'bg-slate-500/10 text-slate-400 border-slate-500/20'
}
</script>

<template>
  <Head title="Gestión de Herramientas por Técnico" />

  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-3xl font-black text-white tracking-tight">Gestión de Herramientas</h1>
      <p class="text-slate-400 mt-1.5 font-medium">Administrar herramientas asignadas por técnico</p>
    </div>
    <div class="flex gap-4">
      <Link class="px-5 py-2.5 bg-white/[0.05] text-slate-300 font-semibold rounded-xl hover:bg-white/[0.1] hover:text-white border border-white/10 transition-all duration-300 flex items-center shadow-lg" href="/herramientas-dashboard">
        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
        Dashboard
      </Link>
      <Link class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold rounded-xl hover:from-blue-500 hover:to-blue-400 transition-all duration-300 shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center" href="/herramientas/gestion/create">
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nueva Asignación
      </Link>
    </div>
  </div>

  <!-- Estadísticas generales -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-slate-900/50 backdrop-blur-xl p-5 rounded-2xl shadow-lg border border-white/[0.05] relative overflow-hidden group">
      <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-blue-500/20 transition-all duration-500"></div>
      <div class="text-3xl font-black text-white relative z-10">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).total, 0) }}</div>
      <div class="text-sm font-semibold text-slate-400 mt-1 uppercase tracking-wider relative z-10">Total Herramientas</div>
    </div>
    
    <div class="bg-slate-900/50 backdrop-blur-xl p-5 rounded-2xl shadow-lg border border-white/[0.05] relative overflow-hidden group">
      <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-emerald-500/20 transition-all duration-500"></div>
      <div class="text-3xl font-black text-emerald-400 relative z-10">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).disponibles, 0) }}</div>
      <div class="text-sm font-semibold text-slate-400 mt-1 uppercase tracking-wider relative z-10">Disponibles</div>
    </div>
    
    <div class="bg-slate-900/50 backdrop-blur-xl p-5 rounded-2xl shadow-lg border border-white/[0.05] relative overflow-hidden group">
      <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-blue-500/20 transition-all duration-500"></div>
      <div class="text-3xl font-black text-blue-400 relative z-10">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).asignadas, 0) }}</div>
      <div class="text-sm font-semibold text-slate-400 mt-1 uppercase tracking-wider relative z-10">Asignadas</div>
    </div>
    
    <div class="bg-slate-900/50 backdrop-blur-xl p-5 rounded-2xl shadow-lg border border-white/[0.05] relative overflow-hidden group">
      <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-amber-500/20 transition-all duration-500"></div>
      <div class="text-3xl font-black text-amber-500 relative z-10">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).mantenimiento, 0) }}</div>
      <div class="text-sm font-semibold text-slate-400 mt-1 uppercase tracking-wider relative z-10">En Mantenimiento</div>
    </div>
  </div>

  <!-- Lista de técnicos con herramientas -->
  <div class="space-y-6">
    <div v-for="tecnico in rows" :key="tecnico.id" class="bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] overflow-hidden">
      <!-- Header Técnico -->
      <div class="p-6 border-b border-white/[0.08] bg-slate-800/20 flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500/50 to-emerald-500/50"></div>
        
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 border border-white/10 flex items-center justify-center text-xl font-bold text-white shadow-inner">
            {{ (tecnico.nombre_completo || tecnico.nombre).charAt(0) }}
          </div>
          <div>
            <h2 class="text-xl font-black text-white tracking-tight">{{ tecnico.nombre_completo || tecnico.nombre }}</h2>
            <div class="flex items-center gap-3 text-sm text-slate-400 mt-1">
              <span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> {{ tecnico.email || 'Sin email' }}</span>
              <span class="text-slate-600">•</span>
              <span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> {{ tecnico.telefono || 'Sin teléfono' }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-6 bg-slate-950/40 p-3 rounded-xl border border-white/[0.05]">
          <div class="text-right px-4 border-r border-white/10">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Asignadas</div>
            <div class="text-2xl font-black text-blue-400">{{ calcularEstadisticasTecnico(tecnico).total }}</div>
          </div>
          <div class="flex gap-2">
            <Link
              class="px-4 py-2 bg-white/[0.05] hover:bg-blue-600/20 text-blue-400 font-semibold rounded-lg border border-blue-500/20 transition-all duration-300 flex items-center shadow-sm"
              :href="`/herramientas/gestion/${tecnico.id}/edit`"
            >
              <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              Gestionar
            </Link>
            <Link
              v-if="calcularEstadisticasTecnico(tecnico).total > 0"
              class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-semibold rounded-lg border border-emerald-500/20 transition-all duration-300 flex items-center shadow-sm"
              :href="`/herramientas/gestion/${tecnico.id}/exportar`"
            >
              <svg class="w-4 h-4 mr-2 border-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Reporte
            </Link>
          </div>
        </div>
      </div>

      <!-- Cuerpo del Técnico -->
      <div class="p-6">
        <!-- Mini-stats bar -->
        <div class="flex gap-4 mb-6 pb-6 border-b border-white/[0.05]">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div>
            <span class="text-sm text-slate-300"><span class="font-bold text-white">{{ calcularEstadisticasTecnico(tecnico).disponibles }}</span> Disponibles</span>
          </div>
          <div class="w-px h-4 bg-white/10 my-auto"></div>
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.8)]"></div>
            <span class="text-sm text-slate-300"><span class="font-bold text-white">{{ calcularEstadisticasTecnico(tecnico).asignadas }}</span> Asignadas</span>
          </div>
          <div class="w-px h-4 bg-white/10 my-auto"></div>
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]"></div>
            <span class="text-sm text-slate-300"><span class="font-bold text-white">{{ calcularEstadisticasTecnico(tecnico).mantenimiento }}</span> En Mant.</span>
          </div>
        </div>

        <!-- Lista de herramientas del técnico -->
        <div v-if="tecnico.herramientas && tecnico.herramientas.length > 0">
          <h3 class="font-bold text-slate-300 mb-4 uppercase tracking-wider text-xs">Inventario Asignado</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="herramienta in tecnico.herramientas.slice(0, 6)" :key="herramienta.id" class="p-3 bg-slate-800/40 rounded-xl border border-white/[0.05] hover:border-blue-500/30 transition-colors group/card flex items-center gap-4">
              <div class="w-12 h-12 rounded-lg bg-slate-900 border border-white/10 flex-shrink-0 flex items-center justify-center overflow-hidden">
                <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-full h-full object-cover group-hover/card:scale-110 transition-transform duration-500" />
                <svg v-else class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="font-bold text-white text-sm truncate">{{ herramienta.nombre }}</h4>
                <div class="flex items-center justify-between mt-1.5">
                  <p class="text-[10px] text-slate-500 font-mono bg-slate-900 px-2 py-0.5 rounded border border-white/5">{{ herramienta.numero_serie || 'S/N' }}</p>
                  <span :class="['text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border', getEstadoClasses(herramienta.estado)]">
                    {{ getEstadoLabel(herramienta.estado) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="tecnico.herramientas.length > 6" class="mt-4 pt-4 border-t border-white/[0.05] flex justify-center">
            <Link :href="`/herramientas/gestion/${tecnico.id}/edit`" class="text-xs font-semibold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-wider">
              Ver el inventario completo ({{ tecnico.herramientas.length }} items) →
            </Link>
          </div>
        </div>

        <!-- Sin herramientas asignadas -->
        <div v-else class="text-center py-10 bg-slate-900/30 rounded-xl border border-dashed border-white/10">
          <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-white mb-1">Inventario Vacío</h3>
          <p class="text-slate-400 text-sm mb-4">Este técnico no tiene herramientas asignadas actualmente.</p>
          <Link
            :href="`/herramientas/gestion/${tecnico.id}/edit`"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-lg transition-colors inline-block shadow-lg shadow-blue-500/20"
          >
            Realizar Primera Asignación
          </Link>
        </div>
      </div>
    </div>
  </div>

  <!-- Sin técnicos -->
  <div v-if="rows.length === 0" class="bg-slate-900/50 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] p-16 text-center">
    <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/10">
      <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
      </svg>
    </div>
    <h2 class="text-2xl font-black text-white mb-2">No hay técnicos disponibles</h2>
    <p class="text-slate-400">Agrega técnicos al sistema para gestionar sus herramientas de trabajo.</p>
  </div>

  <!-- Acciones rápidas -->
  <div class="mt-8 bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] p-6 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 pointer-events-none"></div>
    <h2 class="text-lg font-black text-white uppercase tracking-wider mb-5 relative z-10 flex items-center">
      <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      Acciones Rápidas
    </h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 relative z-10">
      <Link href="/herramientas/gestion/create" class="flex flex-col items-center justify-center p-5 bg-slate-800/50 rounded-xl hover:bg-slate-800 border border-white/5 hover:border-blue-500/30 transition-all duration-300 group">
        <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-blue-500/20 transition-all">
          <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
        </div>
        <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-colors">Nueva Asignación</span>
      </Link>
      <Link :href="route('herramientas-mantenimiento')" class="flex flex-col items-center justify-center p-5 bg-slate-800/50 rounded-xl hover:bg-slate-800 border border-white/5 hover:border-amber-500/30 transition-all duration-300 group">
        <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-amber-500/20 transition-all">
          <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </div>
        <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-colors">Mantenimiento</span>
      </Link>
      <Link href="/herramientas-alertas" class="flex flex-col items-center justify-center p-5 bg-slate-800/50 rounded-xl hover:bg-slate-800 border border-white/5 hover:border-red-500/30 transition-all duration-300 group">
        <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-red-500/20 transition-all">
          <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-colors">Ver Alertas</span>
      </Link>
      <Link href="/herramientas" class="flex flex-col items-center justify-center p-5 bg-slate-800/50 rounded-xl hover:bg-slate-800 border border-white/5 hover:border-emerald-500/30 transition-all duration-300 group">
        <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-emerald-500/20 transition-all">
          <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-colors">Catálogo Gral.</span>
      </Link>
    </div>
  </div>
</template>




