<template>
  <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 overflow-hidden transition-all duration-500 mb-12" :style="cssVars">
    <!-- Header con estadísticas Premium -->
    <div class="p-10 border-b border-slate-100 dark:border-slate-800/60">
      <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-10 mb-12">
        <div class="animate-in slide-in-from-left-4 duration-700">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                <FontAwesomeIcon icon="shopping-bag" />
            </div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Gestión de Pedidos</h1>
          </div>
          <p class="text-slate-500 dark:text-slate-400 font-medium">Control operativo y seguimiento de suministros</p>
        </div>
        
        <button
          @click="onCrearNueva"
          class="group px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-2xl flex items-center animate-in slide-in-from-right-4 duration-700"
        >
          <span class="mr-3 text-lg">+</span>
          Nuevo Pedido
        </button>
      </div>

      <!-- Estadísticas Premium Grid -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
        <!-- Total Card -->
        <div class="group bg-slate-50/50 dark:bg-slate-950/40 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/40 hover:shadow-xl transition-all duration-300">
           <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Universo Total</div>
           <div class="flex items-end justify-between">
              <div class="text-3xl font-black text-slate-900 dark:text-white">{{ total }}</div>
              <div class="w-10 h-10 rounded-xl bg-slate-200/50 dark:bg-slate-800 flex items-center justify-center text-slate-500 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="layer-group" />
              </div>
           </div>
        </div>

        <!-- Borradores Card -->
        <div class="group bg-slate-50/50 dark:bg-slate-950/40 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/40 hover:shadow-xl transition-all duration-300">
           <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Borradores</div>
           <div class="flex items-end justify-between">
              <div class="text-3xl font-black text-slate-500 dark:text-slate-400">{{ borradores }}</div>
              <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="file-pen" />
              </div>
           </div>
        </div>

        <!-- Pendientes Card -->
        <div class="group bg-amber-500/5 dark:bg-amber-500/10 p-6 rounded-3xl border border-amber-200/30 dark:border-amber-500/20 hover:shadow-xl transition-all duration-300">
           <div class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-[0.2em] mb-4">Por Confirmar</div>
           <div class="flex items-end justify-between">
              <div class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ pendientes }}</div>
              <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="clock" />
              </div>
           </div>
        </div>

        <!-- Confirmados Card -->
        <div class="group bg-indigo-500/5 dark:bg-indigo-500/10 p-6 rounded-3xl border border-indigo-200/30 dark:border-indigo-500/20 hover:shadow-xl transition-all duration-300">
           <div class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em] mb-4">Confirmados</div>
           <div class="flex items-end justify-between">
              <div class="text-3xl font-black text-indigo-700 dark:text-indigo-400">{{ confirmados }}</div>
              <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="check-double" />
              </div>
           </div>
        </div>

        <!-- Enviados Card -->
        <div class="group bg-emerald-500/5 dark:bg-emerald-500/10 p-6 rounded-3xl border border-emerald-200/30 dark:border-emerald-500/20 hover:shadow-xl transition-all duration-300">
           <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] mb-4">En Venta</div>
           <div class="flex items-end justify-between">
              <div class="text-3xl font-black text-emerald-700 dark:text-emerald-400">{{ enviados_venta }}</div>
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="paper-plane" />
              </div>
           </div>
        </div>

        <!-- Cancelados Card -->
        <div class="group bg-rose-500/5 dark:bg-rose-500/10 p-6 rounded-3xl border border-rose-200/30 dark:border-rose-500/20 hover:shadow-xl transition-all duration-300">
           <div class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-[0.2em] mb-4">Cancelados</div>
           <div class="flex items-end justify-between">
              <div class="text-3xl font-black text-rose-700 dark:text-rose-400">{{ cancelados }}</div>
              <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="ban" />
              </div>
           </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda Premium -->
    <div class="px-10 py-8 bg-slate-50/30 dark:bg-slate-900/40">
      <div class="flex flex-col xl:flex-row xl:items-center gap-8">
        <!-- Búsqueda -->
        <div class="flex-1 group relative">
          <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors">
             <FontAwesomeIcon icon="search" />
          </div>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Buscar por cliente, número o referencia..."
            class="block w-full pl-16 pr-8 py-5 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all font-medium shadow-sm shadow-slate-200/50 dark:shadow-none"
            @input="onSearchChange"
          />
        </div>

        <!-- Barra de Filtros -->
        <div class="flex flex-wrap items-center gap-4">
          <!-- Filtro de estado -->
          <div class="relative min-w-[200px]">
            <select
              v-model="filtroEstado"
              @change="onFiltroEstadoChange"
              class="w-full pl-6 pr-12 py-5 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all shadow-sm"
            >
              <option value="">Estados</option>
              <option value="borrador">Borrador</option>
              <option value="pendiente">Pendiente</option>
              <option value="confirmado">Confirmado</option>
              <option value="enviado_venta">Enviado a Venta</option>
              <option value="cancelado">Cancelado</option>
            </select>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                <FontAwesomeIcon icon="chevron-down" size="xs" />
            </div>
          </div>

          <!-- Filtro de cliente -->
          <div class="relative min-w-[200px]">
            <select
              v-model="filtroCliente"
              @change="onFiltroClienteChange"
              class="w-full pl-6 pr-12 py-5 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all shadow-sm"
            >
              <option value="">Clientes</option>
              <option v-for="cliente in filterOptions?.clientes || []" :key="cliente.value" :value="cliente.value">
                {{ cliente.label }}
              </option>
            </select>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                <FontAwesomeIcon icon="chevron-down" size="xs" />
            </div>
          </div>

          <!-- Ordenamiento -->
          <div class="relative min-w-[200px]">
            <select
              v-model="sortBy"
              @change="onSortChange"
              class="w-full pl-6 pr-12 py-5 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all shadow-sm"
            >
              <option value="fecha-desc">Cronológico ↓</option>
              <option value="fecha-asc">Cronológico ↑</option>
              <option value="cliente-asc">Alfabético</option>
              <option value="total-desc">Monto Mayor</option>
              <option value="total-asc">Monto Menor</option>
            </select>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                <FontAwesomeIcon icon="sort" size="xs" />
            </div>
          </div>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-rose-500 transition-all group flex items-center"
          >
            <FontAwesomeIcon icon="times" class="mr-3 group-hover:rotate-90 transition-transform" />
            Reset
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  total: { type: Number, default: 0 },
  borradores: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  confirmados: { type: Number, default: 0 },
  enviados_venta: { type: Number, default: 0 },
  cancelados: { type: Number, default: 0 },
  filterOptions: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-cliente-change', 'sort-change', 'limpiar-filtros'
])

const { colors, cssVars } = useCompanyColors()

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'fecha-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroCliente = defineModel('filtroCliente', { type: String, default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroClienteChange = () => emit('filtro-cliente-change', filtroCliente.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>
