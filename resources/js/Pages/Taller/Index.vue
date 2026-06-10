<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { 
  faSearch, faPlus, faWrench, faCheckCircle, 
  faClock, faUser, faMobileAlt, faChevronRight, faPrint
} from '@fortawesome/free-solid-svg-icons'
import debounce from 'lodash/debounce'

defineOptions({ layout: AppLayout })

const props = defineProps({
  ordenes: Object,
  filters: Object,
  pendientes_count: Number,
})

const search = ref(props.filters.search || '')
const estado = ref(props.filters.estado || '')
const cliente_id = ref(props.filters.cliente_id || '')

watch([search, estado], debounce(() => {
  router.get(route('taller.index'), { 
    search: search.value, 
    estado: estado.value,
    cliente_id: cliente_id.value || undefined
  }, { 
    preserveState: true, 
    replace: true 
  })
}, 300))

const getStatusBadgeClass = (status) => {
  const classes = {
    'recepcionado': 'bg-brand-500/10 text-blue-400 border-blue-500/20',
    'en_revision': 'bg-purple-500/10 text-purple-400 border-purple-500/20',
    'reparando': 'bg-brand-500/10 text-brand-400 border-brand-500/20',
    'listo': 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
    'entregado': 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    'sin_reparacion': 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    'cancelado': 'bg-rose-500/10 text-rose-400 border-rose-500/20',
  }
  return classes[status] || 'bg-slate-500/10 text-slate-400'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const isOverdue = (date) => {
  if (!date) return false
  return new Date(date) < new Date()
}

</script>

<template>
  <Head title="Módulo de Taller" />

  <div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-brand-500/20 flex items-center justify-center border border-brand-500/30 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
              <FontAwesomeIcon :icon="faWrench" class="text-brand-500 text-xl" />
            </div>
            Gestión de Taller
          </h1>
          <p class="mt-2 text-slate-400 font-medium">Control de reparaciones y garantías de equipos.</p>
        </div>
        
        <Link 
          :href="route('taller.create')" 
          class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-bold rounded-2xl shadow-[0_8px_20px_rgba(245,158,11,0.3)] transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] group"
        >
          <FontAwesomeIcon :icon="faPlus" class="mr-2 group-hover:rotate-90 transition-transform duration-500" />
          Nueva Orden
        </Link>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5 backdrop-blur-xl">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-brand-500/10 flex items-center justify-center text-brand-500">
              <FontAwesomeIcon :icon="faClock" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Pendientes</p>
              <p class="text-2xl font-black text-white">{{ pendientes_count ?? '--' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters & Table Section -->
      <div class="bg-white/[0.02] border border-white/[0.06] rounded-[2.5rem] overflow-hidden backdrop-blur-3xl shadow-2xl">
        
        <!-- Filters Bar -->
        <div class="p-6 border-b border-white/[0.06] flex flex-col sm:flex-row gap-4 items-center bg-white/[0.01]">
          <div class="relative w-full max-w-md group">
            <FontAwesomeIcon :icon="faSearch" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-brand-500 transition-colors" />
            <input 
              v-model="search"
              type="text" 
              placeholder="Buscar por folio, cliente o equipo..." 
              class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 pl-12 pr-4 text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500/50 transition-all"
            >
          </div>
          
          <select 
            v-model="estado"
            class="bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 px-4 text-white focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500/50 transition-all min-w-[180px]"
          >
            <option value="">Todos los estados</option>
            <option value="recepcionado">Recepcionado</option>
            <option value="en_revision">En Revisión</option>
            <option value="reparando">Reparando</option>
            <option value="listo">Listo para Entrega</option>
            <option value="entregado">Entregado</option>
            <option value="sin_reparacion">Sin Reparación</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead>
              <tr class="bg-white/[0.03] text-slate-400 text-[11px] font-black uppercase tracking-[0.2em]">
                <th class="px-6 py-5">Folio</th>
                <th class="px-6 py-5">Cliente</th>
                <th class="px-6 py-5">Equipo</th>
                <th class="px-6 py-5 text-center">Estado</th>
                <th class="px-6 py-5">Fecha Entrega</th>
                <th class="px-6 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr 
                v-for="orden in ordenes.data" 
                :key="orden.id"
                @click="router.visit(route('taller.show', orden.id))"
                class="hover:bg-white/[0.03] transition-colors cursor-pointer group"
              >
                <td class="px-6 py-6">
                  <span class="text-brand-500 font-black tracking-wider">{{ orden.folio }}</span>
                </td>
                <td class="px-6 py-6">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-[10px] font-bold text-slate-400">
                      {{ (orden.cliente?.nombre_razon_social || orden.nombre_cliente || '?')[0].toUpperCase() }}
                    </div>
                    <div>
                      <p class="text-sm font-bold text-white leading-none">{{ orden.cliente?.nombre_razon_social || orden.nombre_cliente }}</p>
                      <p class="text-[10px] text-slate-500 mt-1 font-medium">{{ orden.telefono_cliente || 'Sin teléfono' }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-6">
                  <p class="text-sm font-bold text-slate-200">{{ orden.equipo_marca }}</p>
                  <p class="text-xs text-slate-500">{{ orden.equipo_modelo }}</p>
                </td>
                <td class="px-6 py-6">
                  <div class="flex justify-center">
                    <span 
                      :class="getStatusBadgeClass(orden.estado)"
                      class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wide border"
                    >
                      {{ orden.estado.replace('_', ' ') }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-6">
                  <div :class="{'text-rose-500': isOverdue(orden.fecha_compromiso) && orden.estado !== 'entregado' && orden.estado !== 'cancelado'}">
                    <p class="text-xs font-bold">{{ formatDate(orden.fecha_compromiso) }}</p>
                    <p v-if="isOverdue(orden.fecha_compromiso) && orden.estado !== 'entregado' && orden.estado !== 'cancelado'" class="text-[9px] font-black uppercase tracking-wide">ATRASADO</p>
                  </div>
                </td>
                <td class="px-6 py-6 text-right">
                  <div class="flex justify-end items-center gap-2">
                    <a 
                      :href="route('taller.reporte', orden.id)" 
                      target="_blank"
                      @click.stop
                      class="w-10 h-10 rounded-xl bg-white/[0.05] border border-white/[0.1] flex items-center justify-center text-slate-400 hover:text-brand-500 hover:border-brand-500/30 transition-all duration-200"
                      title="Imprimir Recepción"
                    >
                      <FontAwesomeIcon :icon="faPrint" />
                    </a>
                    <button class="w-10 h-10 rounded-xl bg-white/[0.05] border border-white/[0.1] flex items-center justify-center text-slate-400 group-hover:text-brand-500 group-hover:border-brand-500/30 transition-all duration-200">
                      <FontAwesomeIcon :icon="faChevronRight" />
                    </button>
                  </div>
                </td>
              </tr>
              
              <tr v-if="ordenes.data.length === 0">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="max-w-xs mx-auto">
                    <div class="w-20 h-20 rounded-full bg-slate-900 border border-white/5 flex items-center justify-center mx-auto mb-4">
                      <FontAwesomeIcon :icon="faWrench" class="text-slate-700 text-3xl" />
                    </div>
                    <h3 class="text-white font-bold">No hay órdenes</h3>
                    <p class="text-slate-500 text-xs mt-2">No se encontraron registros que coincidan con tu búsqueda.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-6 border-t border-white/[0.06]">
          <Pagination :links="ordenes.links" />
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
</style>
