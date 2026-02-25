<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleados: {
    type: Object,
    default: () => ({ data: [] })
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total: 0,
      activos: 0,
      inactivos: 0,
      por_departamento: {}
    })
  },
  departamentos: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({ sort_by: 'created_at', sort_direction: 'desc' })
  }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Filtros
const searchTerm = ref(props.filters.search || '')
const filtroDepartamento = ref(props.filters.departamento || '')
const filtroTipoContrato = ref(props.filters.tipo_contrato || '')
const filtroActivo = ref(props.filters.activo || '')
const sortBy = ref(`${props.sorting.sort_by}-${props.sorting.sort_direction}`)

const tiposContrato = [
  { value: 'tiempo_completo', label: 'Tiempo Completo' },
  { value: 'medio_tiempo', label: 'Medio Tiempo' },
  { value: 'temporal', label: 'Temporal' },
  { value: 'honorarios', label: 'Honorarios' },
  { value: 'indefinido', label: 'Tiempo Indefinido' },
]

const imprimirContrato = (id) => {
  window.open(`/empleados/${id}/imprimir-contrato`, '_blank')
}

const descargarContrato = (id) => {
  window.open(`/empleados/${id}/descargar-contrato`, '_blank')
}

const handleSearch = () => {
  aplicarFiltros()
}

const aplicarFiltros = () => {
  const [sort_by, sort_direction] = sortBy.value.split('-')
  router.visit('/empleados', {
    data: {
      search: searchTerm.value,
      departamento: filtroDepartamento.value,
      tipo_contrato: filtroTipoContrato.value,
      activo: filtroActivo.value,
      sort_by,
      sort_direction
    }
  })
}

const limpiarFiltros = () => {
  searchTerm.value = ''
  filtroDepartamento.value = ''
  filtroTipoContrato.value = ''
  filtroActivo.value = ''
  sortBy.value = 'created_at-desc'
  router.visit('/empleados')
}

const crearEmpleado = () => {
  router.visit('/usuarios/create?es_empleado=1')
}

const verEmpleado = (id) => {
  router.visit(`/empleados/${id}`)
}

const editarEmpleado = (id) => {
  router.visit(`/empleados/${id}/edit`)
}

const eliminarEmpleado = (id) => {
  if (confirm('¿Está seguro de dar de baja a este empleado?')) {
    router.delete(`/empleados/${id}`, {
      onSuccess: () => notyf.success('Empleado dado de baja'),
      onError: () => notyf.error('Error al dar de baja al empleado')
    })
  }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

const formatearFecha = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Paginación
const paginationData = computed(() => ({
  current_page: props.empleados?.current_page || 1,
  last_page: props.empleados?.last_page || 1,
  from: props.empleados?.from || 0,
  to: props.empleados?.to || 0,
  total: props.empleados?.total || 0,
}))

const goToPage = (page) => {
  const [sort_by, sort_direction] = sortBy.value.split('-')
  router.visit('/empleados', {
    data: {
      page,
      search: searchTerm.value,
      departamento: filtroDepartamento.value,
      tipo_contrato: filtroTipoContrato.value,
      sort_by,
      sort_direction
    }
  })
}
</script>

<template>
  <Head title="Staff - Black Premium" />

  <div class="min-h-screen bg-neutral-950 text-white font-sans selection:bg-blue-500/30 selection:text-blue-200 pb-20">
    <!-- Fondo con gradientes sutiles -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] -left-[10%] w-[40%] h-[40%] bg-emerald-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 w-full max-w-7xl mx-auto py-12 px-6">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-8">
        <div class="space-y-4">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-[0.2em]">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
            RECURSOS HUMANOS
          </div>
          <h1 class="text-5xl md:text-6xl font-black tracking-tighter text-white leading-none">Plantilla Laboral</h1>
          <p class="text-neutral-400 text-sm font-medium max-w-md">Gestione el capital humano, datos de contratación y expedientes digitales de su organización.</p>
        </div>
        
        <button
          @click="crearEmpleado"
          class="group relative inline-flex items-center px-8 py-4 bg-white text-black font-black text-[11px] uppercase tracking-widest rounded-2xl transition-all duration-300 hover:scale-105 active:scale-95 shadow-[0_10px_30px_rgba(255,255,255,0.1)]"
        >
          <svg class="w-4 h-4 mr-3 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Registrar Colaborador
        </button>
      </div>

      <!-- Estadísticas Premium -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div v-for="(stat, idx) in [
          { label: 'Total Staff', value: estadisticas.total, color: 'blue', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
          { label: 'Colaboradores Activos', value: estadisticas.activos, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
          { label: 'Bajas / Inactivos', value: estadisticas.inactivos, color: 'red', icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636' },
          { label: 'Unidades de Negocio', value: Object.keys(estadisticas.por_departamento || {}).length, color: 'purple', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16' }
        ]" :key="idx" class="bg-white/5 border border-white/10 rounded-[2rem] p-8 backdrop-blur-xl relative overflow-hidden group">
            <div :class="`absolute -top-10 -right-10 w-32 h-32 bg-${stat.color}-500/10 blur-3xl rounded-full transition-all group-hover:scale-150`" />
            <div class="relative z-10 flex flex-col gap-6">
                <div :class="`p-3 w-fit rounded-2xl bg-${stat.color}-500/10 text-${stat.color}-400`">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" /></svg>
                </div>
                <div>
                    <div class="text-4xl font-black tracking-tighter">{{ stat.value }}</div>
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] text-neutral-500 mt-1">{{ stat.label }}</div>
                </div>
            </div>
        </div>
      </div>

      <!-- Filtros Glassmorphism -->
      <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-6 backdrop-blur-md mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
          <div class="lg:col-span-5 relative group">
            <svg class="absolute left-6 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-600 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchTerm"
              @keyup.enter="handleSearch"
              type="text"
              placeholder="Buscar por nombre, número o RFC..."
              class="w-full pl-16 pr-8 py-5 bg-black/40 border border-white/5 rounded-2xl text-sm font-bold text-white placeholder:text-neutral-700 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
            />
          </div>

          <div class="lg:col-span-3">
              <select
                v-model="filtroDepartamento"
                @change="aplicarFiltros"
                class="w-full px-6 py-5 bg-black/40 border border-white/5 rounded-2xl text-sm font-bold text-neutral-400 focus:text-white focus:ring-2 focus:ring-blue-500/50 [color-scheme:dark] transition-all"
              >
                <option value="">Todos los Departamentos</option>
                <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
              </select>
          </div>

          <div class="lg:col-span-2">
              <select
                v-model="filtroTipoContrato"
                @change="aplicarFiltros"
                class="w-full px-6 py-5 bg-black/40 border border-white/5 rounded-2xl text-sm font-bold text-neutral-400 focus:text-white focus:ring-2 focus:ring-blue-500/50 [color-scheme:dark] transition-all"
              >
                <option value="">Tipo de Contrato</option>
                <option v-for="tipo in tiposContrato" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
              </select>
          </div>

          <div class="lg:col-span-2">
              <button
                @click="limpiarFiltros"
                class="w-full h-full py-5 bg-white/5 border border-white/5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-neutral-500 hover:text-white hover:bg-white/10 transition-all"
              >
                Limpiar
              </button>
          </div>
        </div>
      </div>

      <!-- Tabla Premium -->
      <div class="bg-white/5 border border-white/10 rounded-[2.5rem] backdrop-blur-xl overflow-hidden shadow-2xl">
        <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
            <div>
                <h2 class="text-xl font-black tracking-tight">Registro Maestro</h2>
                <p class="text-[10px] font-black uppercase tracking-widest text-neutral-500 mt-1">Directorio Centralizado</p>
            </div>
            <div class="px-4 py-2 bg-black/40 border border-white/5 rounded-full text-[10px] font-black uppercase tracking-widest text-neutral-400">
              {{ paginationData.from }} - {{ paginationData.to }} <span class="mx-1 opacity-30">of</span> {{ paginationData.total }}
            </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="text-left border-b border-white/5">
                <th class="px-10 py-6 text-[10px] font-black text-neutral-500 uppercase tracking-[0.2em]">Colaborador</th>
                <th class="px-6 py-6 text-[10px] font-black text-neutral-500 uppercase tracking-[0.2em]">Puesto / Régimen</th>
                <th class="px-6 py-6 text-[10px] font-black text-neutral-500 uppercase tracking-[0.2em]">Unidad</th>
                <th class="px-6 py-6 text-[10px] font-black text-neutral-500 uppercase tracking-[0.2em]">Antigüedad</th>
                <th class="px-6 py-6 text-[10px] font-black text-neutral-500 uppercase tracking-[0.2em]">Salario</th>
                <th class="px-10 py-6 text-[10px] font-black text-neutral-500 uppercase tracking-[0.2em] text-right">Acciones</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-white/5">
              <tr
                v-for="empleado in empleados.data"
                :key="empleado.id"
                class="group transition-all duration-300 hover:bg-white/[0.03]"
              >
                <td class="px-10 py-6">
                  <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-lg shadow-lg group-hover:scale-110 transition-transform">
                          {{ empleado.name?.charAt(0) || '?' }}
                        </div>
                        <div v-if="empleado.activo" class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-4 border-neutral-900 rounded-full"></div>
                    </div>
                    <div>
                      <div class="text-sm font-black text-white group-hover:text-blue-400 transition-colors">{{ empleado.name || 'Sin nombre' }}</div>
                      <div class="text-[10px] font-bold text-neutral-600 uppercase tracking-widest">{{ empleado.numero_empleado || 'S/N' }}</div>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-6">
                  <div class="text-sm font-bold text-neutral-200">{{ empleado.puesto || '—' }}</div>
                  <div class="text-[10px] font-black text-neutral-600 uppercase tracking-widest mt-0.5">{{ empleado.tipo_contrato_formateado || empleado.tipo_contrato }}</div>
                </td>

                <td class="px-6 py-6">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    {{ empleado.departamento || 'No Asig.' }}
                  </span>
                </td>

                <td class="px-6 py-6">
                  <div class="text-sm font-bold text-neutral-200">{{ formatearFecha(empleado.fecha_contratacion) }}</div>
                  <div v-if="empleado.antiguedad_formateada" class="text-[10px] font-black text-neutral-600 uppercase tracking-widest mt-0.5">{{ empleado.antiguedad_formateada }}</div>
                </td>

                <td class="px-6 py-6">
                  <div class="text-sm font-black text-emerald-400">{{ formatearMoneda(empleado.salario_base) }}</div>
                  <div class="text-[10px] font-black text-neutral-600 uppercase tracking-widest mt-0.5">Mensual Bruto</div>
                </td>

                <td class="px-10 py-6">
                  <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0">
                    <button @click="verEmpleado(empleado.id)" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-neutral-400 hover:text-white hover:bg-white/10 transition-all shadow-sm" title="Expediente"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                    <button @click="editarEmpleado(empleado.id)" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-neutral-400 hover:text-amber-400 hover:bg-amber-400/10 transition-all shadow-sm" title="Editar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                    <Link :href="`/nominas/create?empleado_id=${empleado.id}`" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-neutral-400 hover:text-emerald-400 hover:bg-emerald-400/10 transition-all shadow-sm" title="Nómina"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg></Link>
                    <button v-if="empleado.puede_imprimir_contrato" @click="imprimirContrato(empleado.id)" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-neutral-400 hover:text-purple-400 hover:bg-purple-400/10 transition-all shadow-sm" title="Contrato"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg></button>
                    <button @click="eliminarEmpleado(empleado.id)" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-neutral-400 hover:text-red-500 hover:bg-red-500/10 transition-all shadow-sm" title="Baja"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                  </div>
                </td>
              </tr>

              <tr v-if="!empleados.data?.length">
                <td colspan="6" class="px-10 py-32 text-center">
                  <div class="flex flex-col items-center max-w-sm mx-auto">
                    <div class="w-24 h-24 bg-white/5 rounded-3xl flex items-center justify-center text-neutral-600 mb-8 border border-white/5 rotate-12 group hover:rotate-0 transition-transform duration-500">
                      <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-white mb-2 uppercase tracking-tight">Sin registros detectados</h3>
                    <p class="text-xs text-neutral-500 font-bold uppercase tracking-widest leading-relaxed">Inicie la expansión de su equipo registrando al primer colaborador en la plataforma corporativa.</p>
                    <button @click="crearEmpleado" class="mt-10 px-10 py-4 bg-white text-black font-black text-[10px] uppercase tracking-widest rounded-2xl shadow-xl hover:scale-105 active:scale-95 transition-all">Crear Registro</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación Premium -->
        <div v-if="paginationData.last_page > 1" class="px-10 py-10 border-t border-white/5 bg-white/[0.01]">
          <div class="flex items-center justify-center gap-2">
            <button
              v-for="p in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(x => x > 0 && x <= paginationData.last_page)"
              :key="p"
              @click="goToPage(p)"
              :class="[
                'w-12 h-12 flex items-center justify-center rounded-2xl text-[11px] font-black transition-all border-2',
                p === paginationData.current_page
                  ? 'bg-blue-600 border-blue-400 text-white shadow-lg shadow-blue-900/40 translate-y--1'
                  : 'bg-white/5 border-white/5 text-neutral-500 hover:text-white hover:border-white/10'
              ]"
            >
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }

/* Custom Scrollbar */
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }

/* Prevent text selection on interaction items */
button, a { -webkit-tap-highlight-color: transparent; }
</style>
