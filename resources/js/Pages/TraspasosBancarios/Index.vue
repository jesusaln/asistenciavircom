<template>
  <div>
    <Head title="Traspasos Bancarios" />

    <div class="w-full px-6 py-8">
      <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="h-8 w-8 text-indigo-600 mr-3" />
                Traspasos Bancarios
              </h1>
              <p class="text-gray-600 mt-1">Registra y administra transferencias entre cuentas propias</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <Link
                :href="route('cuentas-bancarias.index')"
                class="mt-4 md:mt-0 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center"
                >
                <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="mr-2" />
                Volver a Cuentas
                </Link>
                <Link
                :href="route('traspasos-bancarios.create')"
                class="mt-4 md:mt-0 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center shadow-md"
                >
                <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                Nuevo Traspaso
                </Link>
            </div>
        </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl shadow-md p-6 mb-6 filters-card">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Fecha Desde -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <input
              type="date"
              v-model="filters.fecha_desde"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <!-- Fecha Hasta -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
            <input
              type="date"
              v-model="filters.fecha_hasta"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <!-- Cuenta Origen -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Origen</label>
            <select v-model="filters.cuenta_origen_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">Todas</option>
                <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                    {{ cuenta.nombre }} ({{ cuenta.banco }})
                </option>
            </select>
          </div>
          <!-- Cuenta Destino -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Destino</label>
            <select v-model="filters.cuenta_destino_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">Todas</option>
                <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                    {{ cuenta.nombre }} ({{ cuenta.banco }})
                </option>
            </select>
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
           <button @click="limpiarFiltros" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
            Limpiar
          </button>
          <button @click="aplicarFiltros" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <FontAwesomeIcon :icon="['fas', 'search']" class="mr-2" />
            Buscar
          </button>
        </div>
      </div>

      <!-- Tabla de Traspasos -->
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-indigo-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 uppercase tracking-wider">Fecha / Folio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 uppercase tracking-wider">Concepto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 uppercase tracking-wider">Origen ➡️ Destino</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-indigo-800 uppercase tracking-wider">Monto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-indigo-800 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="traspaso in traspasos.data" :key="traspaso.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ formatFecha(traspaso.fecha) }}</div>
                  <div class="text-xs text-gray-500">{{ traspaso.folio || 'S/F' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ traspaso.motivo || 'Traspaso entre cuentas' }}</div>
                  <div class="text-xs text-gray-500 truncate max-w-xs">{{ traspaso.referencia }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col gap-1">
                    <div class="text-xs text-red-600 font-medium flex items-center">
                        <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                        {{ traspaso.cuenta_origen?.nombre }}
                    </div>
                    <div class="text-xs text-green-600 font-medium flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        {{ traspaso.cuenta_destino?.nombre }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="text-sm font-bold text-gray-900">${{ formatMonto(traspaso.monto) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span :class="getEstadoClass(traspaso.estado)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ getEstadoLabel(traspaso.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <Link :href="route('traspasos-bancarios.show', traspaso.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                    <FontAwesomeIcon :icon="['fas', 'eye']" />
                  </Link>
                  <!-- Solo permitir eliminar si es reciente o tiene permisos -->
                  <button 
                    @click="confirmarEliminacion(traspaso)" 
                    class="text-red-600 hover:text-red-900"
                    title="Reversar Traspaso"
                  >
                    <FontAwesomeIcon :icon="['fas', 'undo']" />
                  </button>
                </td>
              </tr>
              <tr v-if="traspasos.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="text-4xl text-gray-300 mb-3 block mx-auto" />
                  No se encontraron traspasos registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
         <div v-if="traspasos.links && traspasos.links.length > 3" class="px-6 py-4 border-t bg-white flex items-center justify-center gap-2">
            <template v-for="(link, index) in traspasos.links" :key="index">
                <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                    'px-3 py-1 rounded-lg text-sm transition-colors',
                    link.active 
                    ? 'bg-indigo-600 text-white' 
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                ]"
                v-html="link.label"
                preserve-scroll
                />
                <span
                v-else
                class="px-3 py-1 text-sm text-gray-400"
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
import Swal from 'sweetalert2'

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

const getEstadoClass = (estado) => {
  const clases = {
    'completado': 'bg-green-100 text-green-800',
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'cancelado': 'bg-red-100 text-red-800',
  }
  return clases[estado] || 'bg-gray-100 text-gray-800'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'completado': 'Completado',
    'pendiente': 'Pendiente',
    'cancelado': 'Reversado',
  }
  return labels[estado] || estado
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
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, reversar movimiento',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('traspasos-bancarios.destroy', traspaso.id))
        }
    })
}
</script>
