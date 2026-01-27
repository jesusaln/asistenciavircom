<template>
  <Head :title="`Movimientos - ${cuenta.nombre}`" />

  <div class="min-h-screen bg-slate-950 text-slate-200 font-outfit pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      
      <!-- Premium Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 animate-fade-in">
        <div class="flex items-center gap-5">
          <Link 
            :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })" 
            class="group flex items-center justify-center w-12 h-12 bg-slate-900/50 border border-slate-800 rounded-2xl hover:bg-slate-800 hover:border-slate-700 transition-all duration-300 shadow-xl"
          >
            <FontAwesomeIcon icon="arrow-left" class="text-slate-400 group-hover:text-white transition-colors" />
          </Link>
          <div>
            <h1 class="text-4xl font-extrabold text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">
              Movimientos
            </h1>
            <p class="text-slate-400 mt-1 flex items-center gap-2">
              <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
              {{ cuenta.nombre }} • {{ cuenta.banco }}
            </p>
          </div>
        </div>
        
        <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 px-8 py-4 rounded-3xl flex flex-col items-end">
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Saldo Actualizado</span>
          <p class="text-3xl font-black text-white leading-none tracking-tighter transition-all hover:scale-110 origin-right cursor-default">
            ${{ formatMonto(cuenta.saldo_actual) }}
          </p>
        </div>
      </div>

      <!-- Modern Glass Filters -->
      <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800/50 rounded-[2.5rem] p-8 mb-10 animate-fade-in-up" style="animation-delay: 100ms">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="space-y-2">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Periodo Inicial</label>
            <input type="date" v-model="filters.fecha_desde" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/20 transition-all font-medium" />
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Periodo Final</label>
            <input type="date" v-model="filters.fecha_hasta" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/20 transition-all font-medium" />
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Tipo de Flujo</label>
            <select v-model="filters.tipo" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/20 transition-all font-medium">
              <option value="">Cualquier tipo</option>
              <option value="deposito">Ingresos (Depósitos)</option>
              <option value="retiro">Egresos (Retiros)</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Módulo Origen</label>
            <select v-model="filters.origen_tipo" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/20 transition-all font-medium">
              <option value="">Todas las fuentes</option>
              <option value="venta">Ventas Directas</option>
              <option value="renta">Rentas de Equipos</option>
              <option value="cobro">Gestión de Cobros</option>
              <option value="prestamo">Cartera Préstamos</option>
              <option value="traspaso">Transferencias</option>
              <option value="pago">Mis Pagos</option>
              <option value="otro">Misceláneos</option>
            </select>
          </div>
        </div>
        <div class="mt-8 flex justify-end gap-4">
          <button @click="limpiarFiltros" class="px-8 py-3 bg-slate-950 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 rounded-2xl transition-all font-bold text-xs uppercase tracking-widest">
            Restablecer
          </button>
          <button @click="aplicarFiltros" class="px-10 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl transition-all hover:scale-105 active:scale-95 font-bold text-xs uppercase tracking-widest shadow-xl shadow-blue-900/20">
            <FontAwesomeIcon icon="search" class="mr-2" />
            Ejecutar Filtro
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10 animate-fade-in-up" style="animation-delay: 200ms">
        <div class="relative group bg-slate-900/60 p-8 rounded-[2rem] border border-emerald-500/10 hover:border-emerald-500/30 transition-all">
          <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent rounded-[2rem]"></div>
          <div class="relative">
             <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                   <FontAwesomeIcon icon="arrow-up" />
                </div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Ingresos Totales</span>
             </div>
             <p class="text-3xl font-black text-emerald-400 tracking-tighter">${{ formatMonto(stats.total_depositos) }}</p>
          </div>
        </div>
        
        <div class="relative group bg-slate-900/60 p-8 rounded-[2rem] border border-rose-500/10 hover:border-rose-500/30 transition-all">
          <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-transparent rounded-[2rem]"></div>
          <div class="relative">
             <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 flex items-center justify-center text-rose-500">
                   <FontAwesomeIcon icon="arrow-down" />
                </div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Egresos Totales</span>
             </div>
             <p class="text-3xl font-black text-rose-400 tracking-tighter">${{ formatMonto(stats.total_retiros) }}</p>
          </div>
        </div>

        <div class="relative group bg-slate-900/60 p-8 rounded-[2rem] border border-blue-500/10 hover:border-blue-500/30 transition-all">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent rounded-[2rem]"></div>
          <div class="relative">
             <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-500">
                   <FontAwesomeIcon icon="receipt" />
                </div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Actividad</span>
             </div>
             <p class="text-3xl font-black text-white tracking-tighter">{{ stats.cantidad_movimientos }} <span class="text-sm font-medium text-slate-500 ml-1">registros</span></p>
          </div>
        </div>
      </div>

      <!-- Transaction List Premium Table -->
      <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/50 rounded-[2.5rem] overflow-hidden animate-fade-in-up shadow-2xl shadow-black/50" style="animation-delay: 300ms">
        <div class="px-10 py-8 border-b border-slate-800 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white flex items-center gap-3">
             <FontAwesomeIcon icon="list" class="text-blue-500" />
             Libro de Movimientos
          </h3>
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
            {{ movimientos.from || 0 }} - {{ movimientos.to || 0 }} de {{ movimientos.total || 0 }} resultados
          </span>
        </div>
        
        <div v-if="movimientos.data && movimientos.data.length > 0" class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-950/30">
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Fecha</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Concepto & Glosa</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Procedencia</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Naturaleza</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Monto</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Estatus</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
              <tr v-for="mov in movimientos.data" :key="mov.id" class="hover:bg-blue-500/5 transition-colors group/row">
                <td class="px-10 py-6">
                   <span class="text-sm font-bold text-white group-hover/row:text-blue-400 transition-colors">{{ formatFecha(mov.fecha) }}</span>
                </td>
                <td class="px-10 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-200">{{ mov.concepto || 'Sin descripción' }}</span>
                    <span v-if="mov.referencia" class="text-[10px] font-bold text-slate-600 mt-1 uppercase tracking-tighter">REF: {{ mov.referencia }}</span>
                  </div>
                </td>
                <td class="px-10 py-6 text-center">
                  <span :class="getOrigenClass(mov.origen_tipo)" class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">
                    {{ getOrigenLabel(mov.origen_tipo) }}
                  </span>
                </td>
                <td class="px-10 py-6 text-center">
                  <span 
                    class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                    :class="mov.tipo === 'deposito' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'"
                  >
                    {{ mov.tipo === 'deposito' ? 'Ingreso' : 'Egreso' }}
                  </span>
                </td>
                <td class="px-10 py-6 text-right">
                   <span class="text-lg font-black tracking-tight" :class="mov.tipo === 'deposito' ? 'text-emerald-400' : 'text-rose-400'">
                      {{ mov.tipo === 'deposito' ? '+' : '-' }}${{ formatMonto(mov.monto) }}
                   </span>
                </td>
                <td class="px-10 py-6 text-center">
                   <span 
                    class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border shadow-sm"
                    :class="getEstadoClass(mov.estado)"
                   >
                     {{ getEstadoLabel(mov.estado) }}
                   </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-else class="p-40 text-center">
          <div class="w-20 h-20 bg-slate-800 rounded-3xl flex items-center justify-center mx-auto mb-6 opacity-30">
             <FontAwesomeIcon icon="receipt" class="text-4xl text-slate-400" />
          </div>
          <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Sin movimientos para este criterio de búsqueda</p>
        </div>

        <!-- Premium Pagination -->
        <div v-if="movimientos.links && movimientos.links.length > 3" class="px-10 py-10 border-t border-slate-800 flex items-center justify-center gap-3">
          <template v-for="(link, index) in movimientos.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'px-5 py-2.5 rounded-2xl text-xs font-black transition-all duration-300',
                link.active ? 'bg-blue-600 text-white shadow-xl shadow-blue-900/20 scale-110' : 'bg-slate-900 text-slate-500 hover:text-white hover:bg-slate-800 border border-slate-800'
              ]"
              v-html="link.label"
              preserve-scroll
            />
            <span
              v-else
              class="px-5 py-2.5 text-xs font-black text-slate-700 bg-slate-950/20 rounded-2xl cursor-not-allowed uppercase"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuenta: { type: Object, required: true },
  movimientos: { type: Object, default: () => ({ data: [], links: [] }) },
  filtros: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({ total_depositos: 0, total_retiros: 0, cantidad_movimientos: 0 }) },
  origenes_disponibles: { type: Array, default: () => [] },
})

const filters = ref({
  fecha_desde: props.filtros.fecha_desde || '',
  fecha_hasta: props.filtros.fecha_hasta || '',
  tipo: props.filtros.tipo || '',
  origen_tipo: props.filtros.origen_tipo || '',
})

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const getEstadoClass = (estado) => {
  return {
    pendiente: 'bg-yellow-100 text-yellow-700',
    conciliado: 'bg-green-100 text-green-700',
    ignorado: 'bg-gray-100 text-gray-600 dark:text-gray-300',
  }[estado] || 'bg-gray-100 text-gray-600 dark:text-gray-300'
}

const getEstadoLabel = (estado) => {
  return {
    pendiente: 'Pendiente',
    conciliado: 'Conciliado',
    ignorado: 'Ignorado',
  }[estado] || estado
}

const getOrigenClass = (origen) => {
  return {
    venta: 'bg-blue-100 text-blue-700',
    renta: 'bg-purple-100 text-purple-700',
    cobro: 'bg-indigo-100 text-indigo-700',
    prestamo: 'bg-emerald-100 text-emerald-700',
    traspaso: 'bg-orange-100 text-orange-700',
    pago: 'bg-pink-100 text-pink-700',
    otro: 'bg-gray-100 text-gray-600 dark:text-gray-300',
  }[origen] || 'bg-gray-100 text-gray-600 dark:text-gray-300'
}

const getOrigenLabel = (origen) => {
  return {
    venta: 'Venta',
    renta: 'Renta',
    cobro: 'Cobro',
    prestamo: 'Préstamo',
    traspaso: 'Traspaso',
    pago: 'Pago',
    otro: 'Otro',
  }[origen] || 'Sin origen'
}

const aplicarFiltros = () => {
  router.get(route('cuentas-bancarias.movimientos', { cuentas_bancaria: props.cuenta.id }), {
    fecha_desde: filters.value.fecha_desde || undefined,
    fecha_hasta: filters.value.fecha_hasta || undefined,
    tipo: filters.value.tipo || undefined,
    origen_tipo: filters.value.origen_tipo || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const limpiarFiltros = () => {
  const now = new Date()
  filters.value = {
    fecha_desde: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0],
    fecha_hasta: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0],
    tipo: '',
    origen_tipo: '',
  }
  aplicarFiltros()
}
</script>

