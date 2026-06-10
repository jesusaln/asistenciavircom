<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Swal from 'sweetalert2'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnico: { type: Object, required: true },
  asignadas: { type: Array, default: () => [] },
  disponibles: { type: Array, default: () => [] },
  tecnicos: { type: Array, default: () => [] },
})

const form = useForm({ asignadas: props.asignadas.map(h => h.id) })
const showReasignarForm = ref(false)
const herramientaAReasignar = ref(null)
const nuevoTecnicoId = ref('')
const observacionesReasignacion = ref('')
const searchAsignadas = ref('')
const searchDisponibles = ref('')

const toggle = (id) => {
  const i = form.asignadas.indexOf(id)
  if (i === -1) form.asignadas.push(id)
  else form.asignadas.splice(i, 1)
}

const isChecked = (id) => form.asignadas.includes(id)

const herramientasAsignadasFiltradas = computed(() => {
  if (!searchAsignadas.value) return props.asignadas
  return props.asignadas.filter(h =>
    h.nombre.toLowerCase().includes(searchAsignadas.value.toLowerCase()) ||
    (h.numero_serie && h.numero_serie.toLowerCase().includes(searchAsignadas.value.toLowerCase()))
  )
})

const herramientasDisponiblesFiltradas = computed(() => {
  if (!searchDisponibles.value) return props.disponibles
  return props.disponibles.filter(h =>
    h.nombre.toLowerCase().includes(searchDisponibles.value.toLowerCase()) ||
    (h.numero_serie && h.numero_serie.toLowerCase().includes(searchDisponibles.value.toLowerCase()))
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

const submit = () => form.put(route('herramientas.gestion.update', props.tecnico.id))

const abrirModalReasignacion = (herramienta) => {
  herramientaAReasignar.value = herramienta
  showReasignarForm.value = true
}

const cerrarModalReasignacion = () => {
  showReasignarForm.value = false
  herramientaAReasignar.value = null
  nuevoTecnicoId.value = ''
  observacionesReasignacion.value = ''
}

const reasignarHerramienta = () => {
  if (!nuevoTecnicoId.value) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Selecciona un técnico',
      background: '#0f172a',
      color: '#fff',
      customClass: { popup: 'border border-white/10 rounded-2xl shadow-xl' }
    })
    return
  }

  router.post('/herramientas/reasignar', {
    herramienta_id: herramientaAReasignar.value.id,
    tecnico_anterior_id: props.tecnico.id,
    tecnico_nuevo_id: nuevoTecnicoId.value,
    observaciones: observacionesReasignacion.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cerrarModalReasignacion()
      // Recargar la página para actualizar los datos
      window.location.reload()
    }
  })
}
</script>

<template>
  <Head :title="`Gestión - ${props.tecnico.nombre}`" />

  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div>
      <h1 class="text-2xl font-black text-white tracking-tight">Gestión de Herramientas</h1>
      <p class="text-slate-400 mt-1.5 font-medium">Administrar herramientas de {{ props.tecnico.nombre_completo || props.tecnico.nombre }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
      <Link class="px-4 py-2 bg-white/[0.05] text-slate-300 font-semibold rounded-xl hover:bg-white/[0.1] hover:text-white border border-white/10 transition-all duration-200 flex items-center shadow-xl text-sm" :href="route('herramientas.gestion.index')">
        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Volver
      </Link>
      <Link class="px-4 py-2 bg-white/[0.05] text-slate-300 font-semibold rounded-xl hover:bg-white/[0.1] hover:text-white border border-white/10 transition-all duration-200 flex items-center shadow-xl text-sm" :href="route('herramientas.dashboard')">
        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
        Dashboard
      </Link>
      <Link
        class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-semibold rounded-xl hover:from-emerald-500 hover:to-emerald-400 transition-all duration-200 shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_20px_rgba(16,185,129,0.5)] flex items-center text-sm"
        :href="`/herramientas/gestion/${tecnico.id}/exportar`"
      >
        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        Ver Reporte
      </Link>
    </div>
  </div>

  <!-- Información del técnico -->
  <div class="bg-slate-900/50 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] p-6 mb-8 relative overflow-hidden group">
    <div class="absolute top-0 right-0 w-48 h-48 bg-brand-500/10 rounded-full blur-3xl -mr-20 -mt-20 group-hover:bg-slate-500/20 transition-all duration-700 pointer-events-none"></div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 border border-white/10 flex items-center justify-center text-2xl font-bold text-white shadow-inner">
          {{ (props.tecnico.nombre_completo || props.tecnico.nombre).charAt(0) }}
        </div>
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">{{ props.tecnico.nombre_completo || props.tecnico.nombre }}</h2>
          <div class="flex items-center gap-2 text-sm text-slate-400 mt-1 font-medium">
            <span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> {{ props.tecnico.email || 'Sin email' }}</span>
            <span class="text-slate-500">•</span>
            <span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> {{ props.tecnico.telefono || 'Sin teléfono' }}</span>
          </div>
        </div>
      </div>
      <div class="text-right flex items-center gap-4 md:block">
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wide text-left md:text-right">Herramientas asignadas</div>
        <div class="text-4xl font-black text-blue-400">{{ form.asignadas.length }}</div>
      </div>
    </div>
  </div>

  <form @submit.prevent="submit" class="grid lg:grid-cols-2 gap-6">
    <!-- Herramientas asignadas -->
    <div class="bg-slate-900/50 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] flex flex-col h-full overflow-hidden">
      <div class="p-5 border-b border-white/[0.08] bg-slate-800/30">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-2">
             <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center border border-blue-500/30">
                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
             </div>
             <h3 class="text-lg font-bold text-white tracking-tight">Asignadas Ahora</h3>
          </div>
          <div class="relative w-full sm:w-auto">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input
              v-model="searchAsignadas"
              type="search"
              placeholder="Buscar..."
              class="w-full bg-slate-950/50 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-sm text-white focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors"
            />
          </div>
        </div>
      </div>
      <div class="p-6 overflow-y-auto max-h-[500px] custom-scrollbar space-y-3">
        <div v-if="herramientasAsignadasFiltradas.length === 0" class="text-center py-10 text-slate-500">
          <p>Sin herramientas asignadas</p>
        </div>
        <label v-for="herramienta in herramientasAsignadasFiltradas" :key="`a-${herramienta.id}`" class="flex items-center gap-4 p-3 border border-white/[0.05] rounded-xl transition-all duration-200 bg-slate-800/20 hover:bg-slate-800/80 hover:border-brand-500/30 cursor-pointer group/item">
          <input type="checkbox" :value="herramienta.id" v-model="form.asignadas" class="sr-only peer" />
          <div class="w-4 h-4 rounded-xl border-2 border-slate-600 peer-checked:bg-brand-500 peer-checked:border-blue-500 transition-colors flex items-center justify-center">
            <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
          </div>
          <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl shadow-inner" />
          <div v-else class="w-10 h-10 bg-slate-900 rounded-xl border border-white/10 flex items-center justify-center">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
          </div>
          <div class="flex-1 truncate">
            <h4 class="font-bold text-white text-sm truncate">{{ herramienta.nombre }}</h4>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-[10px] text-zinc-500 font-mono">{{ herramienta.numero_serie || 'S/N' }}</span>
              <span :class="['text-[9px] font-bold uppercase px-1.5 rounded-xl border', getEstadoClasses(herramienta.estado)]">
                {{ herramienta.estado }}
              </span>
            </div>
          </div>
          <button @click.stop.prevent="abrirModalReasignacion(herramienta)" class="px-3 py-1.5 text-[10px] font-black bg-blue-600/10 hover:bg-blue-600 text-blue-400 hover:text-white rounded-xl transition-all border border-blue-500/20">
            MOVILIZAR
          </button>
        </label>
      </div>
    </div>

    <!-- Herramientas disponibles -->
    <div class="bg-slate-900/50 backdrop-blur-md rounded-2xl shadow-xl border border-white/[0.08] flex flex-col h-full overflow-hidden">
      <div class="p-5 border-b border-white/[0.08] bg-slate-800/30">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-2">
             <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center border border-emerald-500/30">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
             </div>
             <h3 class="text-lg font-bold text-white tracking-tight">Disponible Gral.</h3>
          </div>
          <div class="relative w-full sm:w-auto">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input
              v-model="searchDisponibles"
              type="search"
              placeholder="Buscar..."
              class="w-full bg-slate-950/50 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-sm text-white focus:ring-1 focus:ring-brand-500 focus:border-emerald-500 transition-colors"
            />
          </div>
        </div>
      </div>
      <div class="p-6 overflow-y-auto max-h-[500px] custom-scrollbar space-y-3">
        <div v-if="herramientasDisponiblesFiltradas.length === 0" class="text-center py-10 text-slate-500">
          <p>No hay herramientas disponibles en almacén</p>
        </div>
        <label v-for="herramienta in herramientasDisponiblesFiltradas" :key="`d-${herramienta.id}`" class="flex items-center gap-4 p-3 border border-white/[0.05] rounded-xl transition-all duration-200 bg-slate-800/50 hover:bg-slate-800/80 hover:border-brand-500/30 cursor-pointer group/item">
          <input type="checkbox" :value="herramienta.id" v-model="form.asignadas" class="sr-only peer" />
          <div class="w-4 h-4 rounded-xl border-2 border-slate-600 peer-checked:bg-brand-500 peer-checked:border-emerald-500 transition-colors flex items-center justify-center">
            <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
          </div>
          <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl shadow-inner" />
          <div v-else class="w-10 h-10 bg-slate-900 rounded-xl border border-white/10 flex items-center justify-center">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
          </div>
          <div class="flex-1 truncate">
            <h4 class="font-bold text-white text-sm truncate">{{ herramienta.nombre }}</h4>
            <span class="text-[10px] text-zinc-500 font-mono">{{ herramienta.numero_serie || 'S/N' }}</span>
          </div>
          <span class="text-[10px] font-black text-emerald-500/80 bg-brand-500/5 px-2 py-1 rounded-xl border border-emerald-500/10">STOCK</span>
        </label>
      </div>
    </div>

    <!-- Barra de acciones inferior -->
    <div class="lg:col-span-2 bg-slate-900 shadow-2xl p-6 rounded-2xl border border-white/10 relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-emerald-600/5 pointer-events-none"></div>
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 rounded-full bg-blue-600/10 flex items-center justify-center text-blue-400 border border-blue-500/20">
             <span class="font-black text-lg">{{ form.asignadas.length }}</span>
          </div>
          <p class="text-slate-300 font-medium tracking-tight">Equipos seleccionados para este técnico.</p>
        </div>
        <div class="flex items-center gap-4 w-full sm:w-auto">
          <Link :href="route('herramientas.gestion.index')" class="flex-1 sm:flex-none text-center px-6 py-2.5 text-slate-400 font-bold hover:text-white transition-colors">
            Cancelar
          </Link>
          <button
            :disabled="form.processing"
            type="submit"
            class="flex-1 sm:flex-none px-8 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-black rounded-xl hover:from-blue-500 hover:to-blue-400 shadow-xl shadow-sky-500/20 transition-all disabled:opacity-50"
          >
            {{ form.processing ? 'ACTUALIZANDO...' : 'GUARDAR CAMBIOS' }}
          </button>
        </div>
      </div>
    </div>
  </form>

  <!-- Modal Reasignación Dark Premium -->
  <div v-if="showReasignarForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 w-full max-w-md rounded-2xl border border-white/10 shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="p-6 border-b border-white/[0.05] bg-slate-800/30 flex justify-between items-center">
        <h2 class="text-xl font-black text-white tracking-tight leading-none uppercase tracking-wide">Movilizar Equipo</h2>
        <button @click="cerrarModalReasignacion" class="text-slate-500 hover:text-white">✕</button>
      </div>
      <div class="p-6">
        <div v-if="herramientaAReasignar" class="space-y-6">
          <div class="bg-slate-950 px-4 py-3 rounded-xl border border-white/5">
            <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Equipo Seleccionado</p>
            <p class="text-white font-bold leading-none">{{ herramientaAReasignar.nombre }}</p>
          </div>

          <div>
            <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Destinatario:</label>
            <select v-model="nuevoTecnicoId" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-1 focus:ring-brand-500">
              <option value="" disabled>Seleccionar técnico destino...</option>
              <option v-for="tecnico in props.tecnicos" :key="tecnico.id" :value="tecnico.id">
                {{ tecnico.nombre_completo || tecnico.nombre }} {{ tecnico.id === props.tecnico.id ? '(ORIGEN)' : '' }}
              </option>
            </select>
          </div>

          <div>
             <label class="block text-[10px] font-black text-slate-500 uppercase mb-2">Motivo / Notas:</label>
             <textarea v-model="observacionesReasignacion" rows="3" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-1 focus:ring-brand-500 resize-none" placeholder="Opcional..."></textarea>
          </div>

          <div class="flex gap-4 pt-2">
            <button @click="cerrarModalReasignacion" class="flex-1 py-3 bg-white/5 text-slate-400 font-bold rounded-xl hover:bg-white/10 transition-all uppercase text-xs">Atrás</button>
            <button @click="reasignarHerramienta" class="flex-1 py-3 bg-emerald-600 text-white font-black rounded-xl hover:bg-slate-500 shadow-xl shadow-emerald-500/20 transition-all uppercase text-xs tracking-wider">EJECUTAR CAMBIO</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }
</style>


