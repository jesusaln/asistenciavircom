<template>
  <div class="bg-white dark:bg-slate-950 rounded-2xl shadow-2xl shadow-black/10 border border-gray-100 dark:border-slate-800 overflow-hidden transition-all duration-300">
    <!-- Header con estadísticas -->
    <div class="bg-gradient-to-br from-slate-50 to-white dark:from-slate-900 dark:to-slate-950 px-8 py-8 border-b border-gray-100 dark:border-slate-800/60">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10">
        <div>
          <h1 class="text-3xl font-black text-gray-900 dark:text-slate-100 tracking-tight flex items-center gap-3">
             <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
             </div>
             Cartera de Préstamos
          </h1>
          <p class="text-sm font-bold text-gray-500 dark:text-slate-500 mt-2 uppercase tracking-widest">Control administrativo y financiero de créditos</p>
        </div>
        <button
          @click="onCrearNueva"
          class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 hover:scale-[1.02] shadow-xl shadow-blue-600/20 active:scale-95 transition-all duration-200"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Contrato
        </button>
      </div>

      <!-- Estadísticas Refinadas -->
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <!-- Tarjeta Total -->
        <div class="bg-white dark:bg-slate-900/40 rounded-2xl p-4 border border-gray-100 dark:border-slate-800 group hover:bg-slate-50 dark:hover:bg-slate-900 transition-all duration-300">
           <p class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Total</p>
           <p class="text-2xl font-black text-gray-900 dark:text-slate-100">{{ total }}</p>
        </div>

        <!-- Activos -->
        <div class="bg-emerald-50/30 dark:bg-emerald-500/5 rounded-2xl p-4 border border-emerald-100/50 dark:border-emerald-500/10 group hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
           <p class="text-[10px] font-black text-emerald-600/60 dark:text-emerald-500/60 uppercase tracking-widest mb-1">Activos</p>
           <p class="text-2xl font-black text-emerald-600 dark:text-emerald-500">{{ activos }}</p>
        </div>

        <!-- Completados -->
        <div class="bg-blue-50/30 dark:bg-blue-500/5 rounded-2xl p-4 border border-blue-100/50 dark:border-blue-500/10 transition-all duration-300">
           <p class="text-[10px] font-black text-blue-600/60 dark:text-blue-500/60 uppercase tracking-widest mb-1">Completados</p>
           <p class="text-2xl font-black text-blue-600 dark:text-blue-500">{{ completados }}</p>
        </div>

        <!-- Cancelados -->
        <div class="bg-red-50/30 dark:bg-red-500/5 rounded-2xl p-4 border border-red-100/50 dark:border-red-500/10 transition-all duration-300">
           <p class="text-[10px] font-black text-red-600/60 dark:text-red-500/60 uppercase tracking-widest mb-1">Cancelados</p>
           <p class="text-2xl font-black text-red-600 dark:text-red-500">{{ cancelados }}</p>
        </div>

        <!-- Montos -->
        <div class="bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-800 col-span-1 lg:col-span-1">
           <p class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Colocación</p>
           <p class="text-sm font-black text-gray-900 dark:text-slate-100">${{ formatearMoneda(totalPrestado) }}</p>
        </div>

        <div class="bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-800">
           <p class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Recuperado</p>
           <p class="text-sm font-black text-green-600 dark:text-green-500">${{ formatearMoneda(totalPagado) }}</p>
        </div>

        <div class="bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-800">
           <p class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">En Cartera</p>
           <p class="text-sm font-black text-orange-600 dark:text-orange-500">${{ formatearMoneda(totalPendiente) }}</p>
        </div>
      </div>
    </div>

    <!-- Filtros Inteligentes -->
    <div class="px-8 py-5 bg-gray-50/30 dark:bg-slate-900/40 flex flex-col lg:flex-row lg:items-center justify-between gap-6 transition-all duration-300">
        <!-- Búsqueda Premium -->
        <div class="flex-1 max-w-xl">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Filtro por cliente, folio o monto..."
              class="block w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-sm font-medium placeholder-gray-400 dark:placeholder-slate-600 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
           <!-- Selecciones con estilo Glass -->
           <div class="flex items-center gap-2">
              <select
                v-model="filtroEstado"
                @change="onFiltroEstadoChange"
                class="bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-600 dark:text-slate-400 focus:ring-4 focus:ring-blue-500/10 transition-all cursor-pointer"
              >
                <option value="">TODOS LOS ESTADOS</option>
                <option value="activo">ACTIVOS</option>
                <option value="completado">COMPLETADOS</option>
                <option value="cancelado">CANCELADOS</option>
              </select>

              <select
                v-model="filtroCliente"
                @change="onFiltroClienteChange"
                class="bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-600 dark:text-slate-400 focus:ring-4 focus:ring-blue-500/10 transition-all cursor-pointer max-w-[200px]"
              >
                <option value="">TODOS LOS CLIENTES</option>
                <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                  {{ cliente.nombre_razon_social?.toUpperCase() }}
                </option>
              </select>
           </div>

           <!-- Acciones de Filtro -->
           <button
             @click="onLimpiarFiltros"
             class="p-3 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 rounded-xl hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 transition-all"
             title="Limpiar filtros"
           >
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
           </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  completados: { type: Number, default: 0 },
  cancelados: { type: Number, default: 0 },
  monto_total_prestado: { type: Number, default: 0 },
  monto_total_pagado: { type: Number, default: 0 },
  monto_total_pendiente: { type: Number, default: 0 },
  clientes: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-cliente-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroCliente = defineModel('filtroCliente', { type: [String, Number], default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroClienteChange = () => emit('filtro-cliente-change', filtroCliente.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Función para formatear moneda
const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
}

// Coerción robusta por si el backend envía strings
const toNumber = (v) => {
  if (typeof v === 'number') return v
  if (typeof v === 'string') return parseFloat(v) || 0
  return 0
}

const totalPrestado = computed(() => toNumber(props.monto_total_prestado))
const totalPagado = computed(() => toNumber(props.monto_total_pagado))
const totalPendiente = computed(() => toNumber(props.monto_total_pendiente))

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado, filtroCliente], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de préstamos */
.prestamos-header {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

@media (max-width: 768px) {
  .prestamos-header .grid {
    grid-template-columns: 1fr;
  }

  .prestamos-header h1 {
    font-size: 1.5rem;
  }
}
</style>

