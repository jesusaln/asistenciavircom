<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

const { colors, cssVars } = useCompanyColors()

const props = defineProps({
  facturas: Object,
  stats: Object,
  filtros: Object
})

const search = ref(props.filtros?.buscar || '')
const estado = ref(props.filtros?.estado || '')

// Debounce search
let timeout = null
watch([search, estado], () => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get(
      route('facturas.index'),
      { 
        buscar: search.value, 
        estado: estado.value 
      },
      { preserveState: true, replace: true }
    )
  }, 300)
})

const getStatusClasses = (status) => {
  const classes = {
    borrador: 'bg-slate-700 text-slate-300 border-slate-600',
    enviada: 'bg-blue-900/40 text-blue-300 border-blue-700/50',
    pagada: 'bg-emerald-900/40 text-emerald-300 border-emerald-700/50',
    vencida: 'bg-red-900/40 text-red-300 border-red-700/50',
    cancelada: 'bg-rose-900/40 text-rose-300 border-rose-700/50',
    anulada: 'bg-gray-800 text-gray-400 border-gray-700'
  }
  return classes[status] || 'bg-slate-700 text-slate-300'
}

const formatearMoneda = (monto) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(monto)
}
</script>

<template>
  <div :style="cssVars" class="min-h-screen bg-slate-900 font-sans text-slate-300">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-3xl font-bold leading-7 text-white sm:truncate bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400">
                    Facturación CFDI 4.0
                </h2>
                <p class="mt-1 text-sm text-slate-400">Gestión de comprobantes fiscales digitales.</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <Link 
                  :href="route('facturas.create')"
                  class="inline-flex items-center px-6 py-3 border border-transparent rounded-xl shadow-lg text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-all duration-200 transform hover:scale-105"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Nueva Factura
                </Link>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="relative overflow-hidden rounded-2xl bg-slate-800 p-6 shadow-xl border border-slate-700/50 group hover:border-indigo-500/50 transition-all duration-300">
             <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 translate-y--8 rounded-full bg-indigo-500/10 blur-3xl group-hover:bg-indigo-500/20 transition-all"></div>
            <div class="relative">
                <div class="text-slate-400 text-sm font-medium tracking-wide">Total Facturas</div>
                <div class="mt-2 text-3xl font-bold text-white">{{ stats.total }}</div>
            </div>
          </div>
          
          <div class="relative overflow-hidden rounded-2xl bg-slate-800 p-6 shadow-xl border border-slate-700/50 group hover:border-blue-500/50 transition-all duration-300">
            <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 translate-y--8 rounded-full bg-blue-500/10 blur-3xl group-hover:bg-blue-500/20 transition-all"></div>
            <div class="relative">
                <div class="text-blue-400 text-sm font-medium tracking-wide">Pendientes / Enviadas</div>
                <div class="mt-2 text-3xl font-bold text-white">{{ stats.pendientes }}</div>
            </div>
          </div>

          <div class="relative overflow-hidden rounded-2xl bg-slate-800 p-6 shadow-xl border border-slate-700/50 group hover:border-emerald-500/50 transition-all duration-300">
             <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 translate-y--8 rounded-full bg-emerald-500/10 blur-3xl group-hover:bg-emerald-500/20 transition-all"></div>
            <div class="relative">
                <div class="text-emerald-400 text-sm font-medium tracking-wide">Pagadas</div>
                <div class="mt-2 text-3xl font-bold text-white">{{ stats.pagadas }}</div>
            </div>
          </div>

           <div class="relative overflow-hidden rounded-2xl bg-slate-800 p-6 shadow-xl border border-slate-700/50 group hover:border-rose-500/50 transition-all duration-300">
             <div class="absolute right-0 top-0 h-32 w-32 translate-x-8 translate-y--8 rounded-full bg-rose-500/10 blur-3xl group-hover:bg-rose-500/20 transition-all"></div>
            <div class="relative">
                <div class="text-rose-400 text-sm font-medium tracking-wide">Canceladas</div>
                <div class="mt-2 text-3xl font-bold text-white">{{ stats.canceladas }}</div>
            </div>
          </div>
        </div>

        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden backdrop-blur-sm">
          <!-- Toolbar -->
          <div class="p-6 border-b border-slate-700/50 bg-slate-800/50 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex-1 w-full flex gap-4">
              <div class="relative w-full md:w-96">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                     <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                     </svg>
                  </div>
                  <input 
                    v-model="search"
                    type="text" 
                    placeholder="Buscar por folio o cliente..." 
                    class="block w-full pl-10 pr-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl leading-5 text-slate-300 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                  >
              </div>
              
              <select 
                v-model="estado"
                class="block pl-3 pr-10 py-2.5 bg-slate-900 border border-slate-700 rounded-xl leading-5 text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
              >
                <option value="">Todos los estados</option>
                <option value="borrador">Borrador</option>
                <option value="enviada">Enviada / Timbrada</option>
                <option value="pagada">Pagada</option>
                <option value="cancelada">Cancelada</option>
              </select>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-700/50">
              <thead class="bg-slate-900/50">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Folio</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Cliente</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Fecha</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Total</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Estado</th>
                  <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-700/50 bg-slate-800">
                <tr v-for="factura in facturas.data" :key="factura.id" class="hover:bg-slate-700/30 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-10 w-10 flex-shrink-0 flex items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-semibold text-white">{{ factura.numero_factura || 'Borrador' }}</div>
                            <div class="text-xs text-slate-500 font-mono" v-if="factura.uuid">{{ factura.uuid.substring(0, 8) }}...</div>
                        </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-white">{{ factura.cliente?.nombre_razon_social }}</div>
                    <div class="text-xs text-slate-500">{{ factura.cliente?.rfc }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                    {{ new Date(factura.fecha_emision).toLocaleDateString() }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-emerald-400">
                        {{ formatearMoneda(factura.total) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                      :class="getStatusClasses(factura.estado)"
                    >
                      {{ factura.estado.charAt(0).toUpperCase() + factura.estado.slice(1) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <Link :href="route('facturas.show', factura.id)" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors">
                        Ver Detalles &rarr;
                    </Link>
                  </td>
                </tr>
                <tr v-if="facturas.data.length === 0">
                  <td colspan="6" class="px-6 py-24 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="h-20 w-20 rounded-full bg-slate-700/50 flex items-center justify-center mb-4">
                            <svg class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-white">No hay facturas</h3>
                        <p class="text-slate-500 text-sm mt-1">No se encontraron registros con los filtros actuales.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="border-t border-slate-700/50 p-4 bg-slate-800/50">
            <Pagination :links="facturas.links" class="dark-pagination" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom overrides for pagination in dark mode if needed, 
   assuming Pagination component handles some or we invoke it via props */
:deep(.dark-pagination button) {
    @apply bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700;
}
:deep(.dark-pagination .active) {
    @apply bg-indigo-600 border-indigo-600 text-white;
}
</style>
