<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnicos: { type: Array, default: () => [] },
  herramientas: { type: Array, default: () => [] },
})

const form = useForm({ user_id: '', herramientas: [] })
const searchHerramientas = ref('')

const toggleHerramienta = (id) => {
  const i = form.herramientas.indexOf(id)
  if (i === -1) form.herramientas.push(id)
  else form.herramientas.splice(i, 1)
}

const toggleSeleccionarTodas = () => {
  if (form.herramientas.length === herramientasFiltradas.value.length) {
    form.herramientas = []
  } else {
    form.herramientas = herramientasFiltradas.value.map(h => h.id)
  }
}

const herramientasFiltradas = computed(() => {
  if (!searchHerramientas.value) return props.herramientas
  return props.herramientas.filter(h =>
    h.nombre.toLowerCase().includes(searchHerramientas.value.toLowerCase()) ||
    (h.numero_serie && h.numero_serie.toLowerCase().includes(searchHerramientas.value.toLowerCase()))
  )
})

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

const submit = () => form.post(route('herramientas.gestion.asignar'))
</script>

<template>
  <Head title="Asignar Herramientas" />

  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-black text-white tracking-tight">Asignar Herramientas</h1>
      <p class="text-slate-400 mt-1.5 font-medium">Selecciona un técnico y asigna las herramientas disponibles del inventario</p>
    </div>
    <div class="flex gap-4">
      <Link class="px-5 py-2.5 bg-white/[0.05] text-slate-300 font-semibold rounded-xl hover:bg-white/[0.1] hover:text-white border border-white/10 transition-all duration-200 flex items-center shadow-xl" :href="route('herramientas.gestion.index')">
        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver a Gestión
      </Link>
      <Link class="px-5 py-2.5 bg-white/[0.05] text-slate-300 font-semibold rounded-xl hover:bg-white/[0.1] hover:text-white border border-white/10 transition-all duration-200 flex items-center shadow-xl" :href="route('herramientas.dashboard')">
        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
        Dashboard
      </Link>
    </div>
  </div>

  <form @submit.prevent="submit" class="bg-slate-900/50 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] p-6 lg:p-8 relative overflow-hidden">
    <!-- Efecto ambiental -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/5 rounded-full blur-3xl -mr-48 -mt-48 pointer-events-none"></div>

    <!-- Selección de técnico -->
    <div class="mb-10 relative z-10">
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center border border-blue-500/30">
          <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        </div>
        <label class="block text-xl font-bold text-white tracking-tight">1. Seleccionar Técnico</label>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <label v-for="tecnico in props.tecnicos" :key="tecnico.id" class="relative group">
          <input
            type="radio"
            :value="tecnico.id"
            v-model="form.user_id"
            name="user_id"
            class="sr-only peer"
            required
          />
          <div class="p-4 border border-white/[0.08] rounded-xl cursor-pointer transition-all duration-200 bg-slate-800/50 peer-checked:border-blue-500 peer-checked:bg-brand-500/10 peer-checked:shadow-[0_0_20px_rgba(59,130,246,0.15)] group-hover:border-white/20">
            <div class="flex items-center gap-2">
              <div class="w-10 h-10 rounded-full bg-slate-700 border border-white/5 flex items-center justify-center text-slate-300 font-bold peer-checked:bg-brand-600 peer-checked:text-white transition-colors">
                {{ (tecnico.nombre_completo || tecnico.nombre).charAt(0) }}
              </div>
              <div class="min-w-0">
                <div class="font-bold text-white text-sm truncate">{{ tecnico.nombre_completo || tecnico.nombre }}</div>
                <div class="text-xs text-slate-400 truncate mt-0.5">{{ tecnico.telefono || 'Sin teléfono' }}</div>
              </div>
              
              <div class="ml-auto w-4 h-4 rounded-full border-2 border-slate-600 flex items-center justify-center peer-checked:border-blue-500 transition-colors">
                <div class="w-2 h-2 rounded-full bg-brand-500 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
              </div>
            </div>
          </div>
        </label>
      </div>
      <div v-if="form.errors.user_id" class="text-sm font-bold text-rose-400/90 mt-3 p-3 bg-brand-500/10 border border-rose-500/20 rounded-xl flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
        {{ form.errors.user_id }}
      </div>
    </div>

    <div class="w-full h-px bg-white/[0.05] my-8 relative z-10"></div>

    <!-- Selección de herramientas -->
    <div class="mb-10 relative z-10">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center border border-emerald-500/30">
            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
          </div>
          <h2 class="text-xl font-bold text-white tracking-tight">2. Herramientas Disponibles</h2>
        </div>
        
        <div class="flex items-center gap-2">
          <div class="relative">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input
              v-model="searchHerramientas"
              type="search"
              placeholder="Buscar..."
              class="bg-slate-900 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors w-full md:w-64"
            />
          </div>
          <button
            type="button"
            @click="toggleSeleccionarTodas"
            class="px-4 py-2 text-sm font-semibold bg-white/[0.05] text-slate-300 border border-white/10 rounded-xl hover:bg-white/[0.1] hover:text-white transition-all whitespace-nowrap"
          >
            {{ form.herramientas.length === herramientasFiltradas.length && herramientasFiltradas.length > 0 ? 'Deseleccionar Todas' : 'Seleccionar Todas' }}
          </button>
        </div>
      </div>

      <!-- Resumen de selección -->
      <div class="mb-5 p-4 bg-brand-500/10 border border-emerald-500/20 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div>
          <span class="text-sm font-bold text-emerald-400">
            {{ form.herramientas.length }} seleccionada{{ form.herramientas.length !== 1 ? 's' : '' }}
          </span>
        </div>
        <span class="text-sm font-semibold text-slate-400">
          De {{ herramientasFiltradas.length }} disponible{{ herramientasFiltradas.length !== 1 ? 's' : '' }} en el filtro actual
        </span>
      </div>

      <!-- Grid de herramientas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
        <label v-for="herramienta in herramientasFiltradas" :key="herramienta.id" class="relative group cursor-pointer">
          <input
            type="checkbox"
            :value="herramienta.id"
            v-model="form.herramientas"
            class="sr-only peer"
          />
          <div class="p-3 border border-white/[0.05] rounded-xl transition-all duration-200 bg-slate-800/20 peer-checked:border-emerald-500/50 peer-checked:bg-brand-500/10 peer-checked:shadow-[0_0_15px_rgba(16,185,129,0.1)] hover:bg-slate-800/50 hover:border-white/10 flex items-center gap-2 h-full">
            
            <!-- Checkbox Custom -->
            <div class="min-w-[20px] w-4 h-4 rounded-xl border-2 border-slate-600 flex items-center justify-center peer-checked:bg-brand-500 peer-checked:border-emerald-500 transition-colors">
              <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
            </div>

            <div class="w-10 h-10 rounded-xl border border-white/5 bg-slate-900 flex-shrink-0 flex items-center justify-center overflow-hidden">
               <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
               <svg v-else class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
            </div>
            
            <div class="flex-1 min-w-0 py-1">
              <h3 class="font-bold text-white text-sm leading-tight truncate">{{ herramienta.nombre }}</h3>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[10px] text-slate-500 font-mono bg-slate-900 px-1.5 rounded-xl">{{ herramienta.numero_serie || 'S/N' }}</span>
                <span :class="['text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-xl border', getEstadoClasses(herramienta.estado)]">
                  {{ herramienta.estado }}
                </span>
              </div>
            </div>
          </div>
        </label>
      </div>

      <!-- Sin herramientas disponibles -->
      <div v-if="herramientasFiltradas.length === 0" class="py-12 text-center bg-slate-900/50 rounded-xl border border-dashed border-white/10 mt-2">
        <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
          <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
        </div>
        <p class="text-lg font-bold text-slate-300">Inventario Vacío</p>
        <p class="text-sm text-slate-500 mt-1">No hay herramientas disponibles que coincidan con la búsqueda.</p>
      </div>

      <div v-if="form.errors.herramientas" class="text-sm font-bold text-rose-400/90 mt-4 p-3 bg-brand-500/10 border border-rose-500/20 rounded-xl flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
        {{ form.errors.herramientas }}
      </div>
    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-end gap-3 pt-6 border-t border-white/[0.05] relative z-10">
      <Link :href="route('herramientas.gestion.index')" class="px-5 py-2.5 text-slate-400 font-semibold rounded-xl hover:bg-white/[0.05] hover:text-white transition-all">
        Cancelar
      </Link>
      <button
        :disabled="form.processing || !form.user_id || form.herramientas.length === 0"
        type="submit"
        class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-bold rounded-xl hover:from-emerald-500 hover:to-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_20px_rgba(16,185,129,0.5)] transition-all duration-200 disabled:opacity-50 disabled:from-slate-700 disabled:to-slate-600 disabled:text-slate-400 disabled:shadow-none disabled:cursor-not-allowed flex items-center gap-2"
      >
        <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <svg v-else class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        {{ form.processing ? 'Procesando...' : `Confirmar Asignación (${form.herramientas.length})` }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>


