<template>
  <div class="min-h-screen bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-white transition-colors duration-200">
    <Head title="Traspasos Bancarios" />

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white flex items-center tracking-tight gap-3">
            <div class="h-12 w-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/30">
              <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="h-6 w-6" />
            </div>
            <div>
              Traspasos Bancarios
              <p class="text-sm font-normal text-slate-500 dark:text-slate-400 mt-1 tracking-normal">Historial, auditoría y control de transferencias entre cuentas propias</p>
            </div>
          </h1>
        </div>
        <div class="flex flex-wrap sm:flex-nowrap gap-3">
          <Link
            :href="route('cuentas-bancarias.index')"
            class="px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/80 transition-all flex items-center justify-center border border-slate-200 dark:border-slate-700 font-semibold text-sm active:scale-[0.98]"
          >
            <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="mr-2 opacity-70" />
            Cuentas Bancarias
          </Link>
          <Link
            :href="route('traspasos-bancarios.create')"
            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 text-white rounded-xl transition-all flex items-center justify-center shadow-lg shadow-indigo-600/20 dark:shadow-indigo-500/10 font-bold text-sm active:scale-[0.98]"
          >
            <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
            Nuevo Traspaso
          </Link>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 rounded-2xl shadow-sm p-6 mb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <!-- Fecha Desde -->
          <div>
            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1">
              <FontAwesomeIcon :icon="['fas', 'calendar-alt']" class="opacity-60" />
              Desde
            </label>
            <input
              type="date"
              v-model="filters.fecha_desde"
              class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/60 rounded-xl text-slate-900 dark:text-slate-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10 dark:focus:ring-indigo-400/10 transition-all outline-none text-sm"
            />
          </div>
          <!-- Fecha Hasta -->
          <div>
            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1">
              <FontAwesomeIcon :icon="['fas', 'calendar-alt']" class="opacity-60" />
              Hasta
            </label>
            <input
              type="date"
              v-model="filters.fecha_hasta"
              class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/60 rounded-xl text-slate-900 dark:text-slate-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10 dark:focus:ring-indigo-400/10 transition-all outline-none text-sm"
            />
          </div>
          <!-- Cuenta Origen -->
          <div>
            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Cuenta Origen</label>
            <select v-model="filters.cuenta_origen_id" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/60 rounded-xl text-slate-900 dark:text-slate-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10 dark:focus:ring-indigo-400/10 transition-all outline-none text-sm">
              <option value="">Todas las cuentas</option>
              <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                {{ cuenta.nombre }} ({{ cuenta.banco }})
              </option>
            </select>
          </div>
          <!-- Cuenta Destino -->
          <div>
            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Cuenta Destino</label>
            <select v-model="filters.cuenta_destino_id" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/60 rounded-xl text-slate-900 dark:text-slate-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10 dark:focus:ring-indigo-400/10 transition-all outline-none text-sm">
              <option value="">Todas las cuentas</option>
              <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                {{ cuenta.nombre }} ({{ cuenta.banco }})
              </option>
            </select>
          </div>
        </div>
        <div class="mt-5 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800 pt-4">
          <button @click="limpiarFiltros" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 rounded-xl transition-all border border-slate-200 dark:border-slate-700 text-sm font-semibold active:scale-[0.98]">
            Limpiar Filtros
          </button>
          <button @click="aplicarFiltros" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 text-white rounded-xl transition-all shadow-md shadow-indigo-600/10 font-bold text-sm flex items-center gap-2 active:scale-[0.98]">
            <FontAwesomeIcon :icon="['fas', 'search']" />
            Buscar Traspasos
          </button>
        </div>
      </div>

      <!-- Tabla de Traspasos -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
            <thead class="bg-slate-50/70 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Folio / Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Detalles y Notas</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Ruta del Traspaso</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Monto</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Registrado Por</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900">
              <tr v-for="traspaso in traspasos.data" :key="traspaso.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-150">
                <!-- Folio y Fecha -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-extrabold text-xs border border-indigo-100/50 dark:border-indigo-900/20 shadow-sm shadow-indigo-100/10">
                      #{{ traspaso.id }}
                    </div>
                    <div>
                      <div class="text-sm font-bold text-slate-900 dark:text-white">{{ formatFecha(traspaso.fecha) }}</div>
                      <div class="text-[10px] text-indigo-500 dark:text-indigo-400 font-bold tracking-wider uppercase mt-0.5">
                        {{ getRelativeTime(traspaso.fecha) }}
                      </div>
                    </div>
                  </div>
                </td>
                
                <!-- Detalles y Notas -->
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ traspaso.referencia || 'Traspaso entre cuentas' }}
                  </div>
                  <div v-if="traspaso.notas" class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-start gap-1.5 max-w-xs italic bg-slate-50 dark:bg-slate-800/40 p-2 rounded-lg border border-slate-100 dark:border-slate-800/50 shadow-inner-sm">
                    <FontAwesomeIcon :icon="['fas', 'sticky-note']" class="text-amber-500 mt-0.5 shrink-0" />
                    <span class="line-clamp-2 leading-relaxed">{{ traspaso.notas }}</span>
                  </div>
                </td>

                <!-- Ruta de Traspaso -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3 py-1">
                    <!-- Origen -->
                    <div class="flex flex-col bg-red-50/40 dark:bg-red-950/15 px-3 py-1.5 rounded-xl border border-red-100/30 dark:border-red-900/10 min-w-[150px] shadow-sm">
                      <span class="text-[9px] uppercase font-extrabold text-red-500 dark:text-red-400 tracking-widest mb-0.5">Origen</span>
                      <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[130px]">{{ traspaso.origen?.nombre || 'Cuenta Origen' }}</span>
                      <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-0.5 flex items-center gap-1">
                        <span class="px-1 py-0.2 bg-red-100/60 dark:bg-red-900/30 text-red-600 dark:text-red-300 rounded font-bold text-[9px] uppercase tracking-wider">{{ traspaso.origen?.banco || 'Banco' }}</span>
                        {{ traspaso.origen?.numero_cuenta ? `*${traspaso.origen.numero_cuenta.slice(-4)}` : '' }}
                      </span>
                    </div>

                    <!-- Flecha -->
                    <div class="text-slate-400 dark:text-slate-600 flex items-center justify-center animate-pulse">
                      <FontAwesomeIcon :icon="['fas', 'long-arrow-alt-right']" class="h-5 w-5 text-indigo-500/70" />
                    </div>

                    <!-- Destino -->
                    <div class="flex flex-col bg-emerald-50/40 dark:bg-emerald-950/15 px-3 py-1.5 rounded-xl border border-emerald-100/30 dark:border-emerald-900/10 min-w-[150px] shadow-sm">
                      <span class="text-[9px] uppercase font-extrabold text-emerald-500 dark:text-emerald-400 tracking-widest mb-0.5">Destino</span>
                      <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[130px]">{{ traspaso.destino?.nombre || 'Cuenta Destino' }}</span>
                      <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-0.5 flex items-center gap-1">
                        <span class="px-1 py-0.2 bg-emerald-100/60 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300 rounded font-bold text-[9px] uppercase tracking-wider">{{ traspaso.destino?.banco || 'Banco' }}</span>
                        {{ traspaso.destino?.numero_cuenta ? `*${traspaso.destino.numero_cuenta.slice(-4)}` : '' }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Monto -->
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="text-base font-extrabold text-slate-900 dark:text-white tabular-nums">${{ formatMonto(traspaso.monto) }}</div>
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold tracking-wider uppercase mt-0.5">{{ traspaso.origen?.moneda || 'MXN' }}</div>
                </td>

                <!-- Registrado Por -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/50 dark:border-slate-700/60 flex items-center justify-center text-xs font-black shadow-inner">
                      {{ (traspaso.usuario?.name || 'S').slice(0, 1).toUpperCase() }}
                    </div>
                    <div>
                      <div class="text-xs font-bold text-slate-700 dark:text-slate-300 max-w-[110px] truncate" :title="traspaso.usuario?.name">
                        {{ traspaso.usuario?.name || 'Sistema' }}
                      </div>
                      <div class="text-[9px] text-slate-400 dark:text-slate-500 font-medium">Operador</div>
                    </div>
                  </div>
                </td>

                <!-- Acciones -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2.5">
                    <Link :href="route('traspasos-bancarios.show', traspaso.id)" class="h-8 w-8 rounded-lg bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 dark:bg-slate-800/50 dark:hover:bg-indigo-950/40 dark:text-slate-300 dark:hover:text-indigo-400 border border-slate-200/40 dark:border-slate-700/40 flex items-center justify-center transition-all duration-150" title="Ver Detalle">
                      <FontAwesomeIcon :icon="['fas', 'eye']" class="h-3.5 w-3.5" />
                    </Link>
                    <button 
                      @click="confirmarEliminacion(traspaso)" 
                      class="h-8 w-8 rounded-lg bg-slate-50 hover:bg-rose-50 hover:text-rose-600 dark:bg-slate-800/50 dark:hover:bg-rose-950/40 dark:text-slate-300 dark:hover:text-rose-400 border border-slate-200/40 dark:border-slate-700/40 flex items-center justify-center transition-all duration-150"
                      title="Reversar / Eliminar Traspaso"
                    >
                      <FontAwesomeIcon :icon="['fas', 'undo']" class="h-3.5 w-3.5 animate-reverse-hover" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="traspasos.data.length === 0">
                <td colspan="6" class="px-6 py-20 text-center text-slate-400 dark:text-slate-500 bg-slate-50/20 dark:bg-slate-900/50">
                  <div class="h-16 w-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-slate-200/40 dark:border-slate-700/40 text-slate-400 dark:text-slate-500 shadow-sm animate-pulse">
                    <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="text-2xl" />
                  </div>
                  <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300">No se encontraron traspasos</h3>
                  <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-xs mx-auto">Utiliza el botón de arriba para registrar tu primer traspaso entre cuentas bancarias.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div v-if="traspasos.links && traspasos.links.length > 3" class="px-6 py-4 border-t border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-center gap-2">
          <template v-for="(link, index) in traspasos.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold transition-all border shadow-sm active:scale-[0.98]',
                link.active 
                ? 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600 dark:border-indigo-500 shadow-indigo-600/10' 
                : 'bg-white hover:bg-slate-50 dark:bg-slate-850 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'
              ]"
              v-html="link.label"
              preserve-scroll
            />
            <span
              v-else
              class="px-3.5 py-2 text-xs text-slate-400 dark:text-slate-500 font-bold border border-transparent"
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
import { library } from '@fortawesome/fontawesome-svg-core'
import { faExchangeAlt, faArrowLeft, faPlus, faSearch, faTrash, faEye, faUndo, faLongArrowAltRight, faStickyNote, faUser, faCalendarAlt } from '@fortawesome/free-solid-svg-icons'
import Swal from 'sweetalert2'

library.add(faExchangeAlt, faArrowLeft, faPlus, faSearch, faTrash, faEye, faUndo, faLongArrowAltRight, faStickyNote, faUser, faCalendarAlt)

defineOptions({ layout: AppLayout })

const props = defineProps({
  traspasos: { type: Object, required: true },
  cuentas: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
})

const filters = ref({
  fecha_desde: props.filtros.fecha_desde || '',
  fecha_hasta: props.filtros.fecha_hasta || '',
  cuenta_origen_id: props.filtros.cuenta_origen_id || '',
  cuenta_destino_id: props.filtros.cuenta_destino_id || '',
})

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const getRelativeTime = (dateStr) => {
  if (!dateStr) return ''
  
  // Normalizar fecha para evitar problemas de desfases horarios locales
  const d = new Date(dateStr)
  d.setHours(0, 0, 0, 0)
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const diffTime = today - d
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Hoy'
  if (diffDays === 1) return 'Ayer'
  if (diffDays === -1) return 'Mañana'
  if (diffDays > 1 && diffDays < 7) return `Hace ${diffDays} días`
  
  const weeks = Math.floor(diffDays / 7)
  if (weeks === 1) return 'Hace 1 semana'
  if (weeks > 1 && weeks < 4) return `Hace ${weeks} semanas`
  
  const months = Math.floor(diffDays / 30)
  if (months === 1) return 'Hace 1 mes'
  if (months > 1) return `Hace ${months} meses`
  
  return `Hace ${diffDays} días`
}

const aplicarFiltros = () => {
  router.get(route('traspasos-bancarios.index'), {
    fecha_desde: filters.value.fecha_desde || undefined,
    fecha_hasta: filters.value.fecha_hasta || undefined,
    cuenta_origen_id: filters.value.cuenta_origen_id || undefined,
    cuenta_destino_id: filters.value.cuenta_destino_id || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const limpiarFiltros = () => {
  filters.value = {
    fecha_desde: '',
    fecha_hasta: '',
    cuenta_origen_id: '',
    cuenta_destino_id: '',
  }
  aplicarFiltros()
}

const confirmarEliminacion = (traspaso) => {
  Swal.fire({
    title: '¿Reversar Traspaso?',
    text: "Se cancelarán los movimientos en ambas cuentas y se restaurarán los saldos. Esta acción no se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, reversar movimiento',
    cancelButtonText: 'Cancelar',
    customClass: {
      confirmButton: 'px-4 py-2 rounded-xl text-sm font-bold',
      cancelButton: 'px-4 py-2 rounded-xl text-sm font-bold'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('traspasos-bancarios.destroy', traspaso.id))
    }
  })
}
</script>
