<template>
  <div>
    <Head :title="`Movimientos - ${cuenta.nombre}`" />

    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="flex items-center mb-8">
        <Link :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })" class="mr-4 p-2 hover:bg-gray-100 rounded-lg">
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="text-gray-600" />
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold text-gray-900">Movimientos Bancarios</h1>
          <p class="text-gray-600 mt-1">{{ cuenta.nombre }} • {{ cuenta.banco }}</p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-500">Saldo Actual</p>
          <p class="text-2xl font-bold text-gray-900">${{ formatMonto(cuenta.saldo_actual) }}</p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Fecha Desde -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <input
              type="date"
              v-model="filters.fecha_desde"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <!-- Fecha Hasta -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
            <input
              type="date"
              v-model="filters.fecha_hasta"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <!-- Tipo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <select
              v-model="filters.tipo"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Todos</option>
              <option value="deposito">Depósitos</option>
              <option value="retiro">Retiros</option>
            </select>
          </div>
          <!-- Origen -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Origen</label>
            <select
              v-model="filters.origen_tipo"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Todos</option>
              <option value="venta">Ventas</option>
              <option value="renta">Rentas</option>
              <option value="cobro">Cobros</option>
              <option value="prestamo">Préstamos</option>
              <option value="traspaso">Traspasos</option>
              <option value="pago">Pagos</option>
              <option value="otro">Otros</option>
            </select>
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button
            @click="limpiarFiltros"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
          >
            Limpiar
          </button>
          <button
            @click="aplicarFiltros"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            <FontAwesomeIcon :icon="['fas', 'search']" class="mr-2" />
            Filtrar
          </button>
        </div>
      </div>

      <!-- Estadísticas del período -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 text-white">
          <p class="text-green-100 text-sm">Total Depósitos</p>
          <p class="text-2xl font-bold">${{ formatMonto(stats.total_depositos) }}</p>
        </div>
        <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-4 text-white">
          <p class="text-red-100 text-sm">Total Retiros</p>
          <p class="text-2xl font-bold">${{ formatMonto(stats.total_retiros) }}</p>
        </div>
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 text-white">
          <p class="text-blue-100 text-sm">Movimientos</p>
          <p class="text-2xl font-bold">{{ stats.cantidad_movimientos }}</p>
        </div>
      </div>

      <!-- Tabla de movimientos -->
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b bg-white flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Movimientos</h3>
          <span class="text-sm text-gray-500">
            {{ movimientos.from || 0 }} - {{ movimientos.to || 0 }} de {{ movimientos.total || 0 }}
          </span>
        </div>
        
        <div v-if="movimientos.data && movimientos.data.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="mov in movimientos.data" :key="mov.id" class="hover:bg-white">
                <td class="px-6 py-4 text-sm text-gray-900">{{ formatFecha(mov.fecha) }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  <div>{{ mov.concepto || '-' }}</div>
                  <div v-if="mov.referencia" class="text-xs text-gray-500">Ref: {{ mov.referencia }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="getOrigenClass(mov.origen_tipo)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ getOrigenLabel(mov.origen_tipo) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span :class="mov.tipo === 'deposito' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ mov.tipo === 'deposito' ? 'Depósito' : 'Retiro' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-right font-medium" :class="mov.tipo === 'deposito' ? 'text-green-600' : 'text-red-600'">
                  {{ mov.tipo === 'deposito' ? '+' : '' }}${{ formatMonto(mov.monto) }}
                </td>
                <td class="px-6 py-4">
                  <span :class="getEstadoClass(mov.estado)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ getEstadoLabel(mov.estado) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-else class="p-12 text-center">
          <FontAwesomeIcon :icon="['fas', 'receipt']" class="h-12 w-12 text-gray-300 mb-4" />
          <p class="text-gray-500">No hay movimientos para el período seleccionado</p>
        </div>

        <!-- Paginación -->
        <div v-if="movimientos.links && movimientos.links.length > 3" class="px-6 py-4 border-t bg-white flex items-center justify-center gap-2">
          <template v-for="(link, index) in movimientos.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'px-3 py-1 rounded-lg text-sm',
                link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'
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
    ignorado: 'bg-gray-100 text-gray-600',
  }[estado] || 'bg-gray-100 text-gray-600'
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
    otro: 'bg-gray-100 text-gray-600',
  }[origen] || 'bg-gray-100 text-gray-600'
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

