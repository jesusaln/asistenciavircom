<template>
  <div>
    <Head title="Conciliación Bancaria" />

    <div class="w-full px-6 py-8 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <FontAwesomeIcon :icon="['fas', 'university']" class="h-8 w-8 text-blue-600 mr-3" />
            Conciliación Bancaria
          </h1>
          <p class="text-gray-600 dark:text-gray-300 mt-1">Concilia movimientos bancarios con cuentas por pagar y cobrar</p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
          <button
            @click="showImportModal = true"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center"
          >
            <FontAwesomeIcon :icon="['fas', 'file-upload']" class="mr-2" />
            Importar CSV
          </button>
          <button
            v-if="resumen.total > 0"
            @click="conciliacionAutomatica"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center"
          >
            <FontAwesomeIcon :icon="['fas', 'magic']" class="mr-2" />
            Conciliar Automático
          </button>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r">
        {{ $page.props.flash.error }}
      </div>

      <!-- Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Pendientes</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ resumen.total }}</p>
            </div>
            <FontAwesomeIcon :icon="['fas', 'clock']" class="h-10 w-10 text-blue-400" />
          </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Depósitos</p>
              <p class="text-2xl font-bold text-green-600">${{ formatMonto(resumen.monto_depositos) }}</p>
            </div>
            <FontAwesomeIcon :icon="['fas', 'arrow-down']" class="h-10 w-10 text-green-400" />
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ resumen.depositos }} movimientos</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6 border-l-4 border-red-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Retiros</p>
              <p class="text-2xl font-bold text-red-600">${{ formatMonto(resumen.monto_retiros) }}</p>
            </div>
            <FontAwesomeIcon :icon="['fas', 'arrow-up']" class="h-10 w-10 text-red-400" />
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ resumen.retiros }} movimientos</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6 border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Diferencia</p>
              <p class="text-2xl font-bold" :class="diferencia >= 0 ? 'text-green-600' : 'text-red-600'">
                ${{ formatMonto(diferencia) }}
              </p>
            </div>
            <FontAwesomeIcon :icon="['fas', 'balance-scale']" class="h-10 w-10 text-purple-400" />
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <select v-model="form.estado" @change="aplicarFiltros" class="rounded-lg border-gray-300">
            <option value="pendiente">Pendientes</option>
            <option value="conciliado">Conciliados</option>
            <option value="ignorado">Ignorados</option>
            <option value="todos">Todos</option>
          </select>
          <select v-model="form.tipo" @change="aplicarFiltros" class="rounded-lg border-gray-300">
            <option value="">Todos los tipos</option>
            <option value="deposito">Depósitos</option>
            <option value="retiro">Retiros</option>
          </select>
          <select v-model="form.banco" @change="aplicarFiltros" class="rounded-lg border-gray-300">
            <option value="">Todos los bancos</option>
            <option v-for="banco in bancos" :key="banco" :value="banco">{{ banco }}</option>
          </select>
          <input type="date" v-model="form.fecha_desde" @change="aplicarFiltros" class="rounded-lg border-gray-300" placeholder="Desde" />
          <input type="date" v-model="form.fecha_hasta" @change="aplicarFiltros" class="rounded-lg border-gray-300" placeholder="Hasta" />
        </div>
      </div>

      <!-- Tabla de movimientos -->
      <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
          <thead class="bg-white dark:bg-slate-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Concepto</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Monto</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Banco</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
            <tr v-for="mov in movimientos.data" :key="mov.id" class="hover:bg-white dark:bg-slate-900">
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ formatFecha(mov.fecha) }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">
                <div class="max-w-xs truncate" :title="mov.concepto">{{ mov.concepto || '-' }}</div>
                <div v-if="mov.referencia" class="text-xs text-gray-400">Ref: {{ mov.referencia }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="mov.tipo === 'deposito' ? 'text-green-600' : 'text-red-600'">
                {{ mov.tipo === 'deposito' ? '+' : '-' }}${{ formatMonto(Math.abs(mov.monto)) }}
              </td>
              <td class="px-4 py-3 text-sm text-center">
                <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ mov.banco }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="estadoClass(mov.estado)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ estadoLabel(mov.estado) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="flex items-center justify-center gap-2">
                  <button
                    v-if="mov.estado === 'pendiente'"
                    @click="abrirConciliar(mov)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Conciliar"
                  >
                    <FontAwesomeIcon :icon="['fas', 'link']" />
                  </button>
                  <button
                    v-if="mov.estado === 'pendiente'"
                    @click="ignorar(mov.id)"
                    class="p-2 text-gray-600 dark:text-gray-300 hover:bg-white dark:bg-slate-900 rounded-lg transition-colors"
                    title="Ignorar"
                  >
                    <FontAwesomeIcon :icon="['fas', 'eye-slash']" />
                  </button>
                  <button
                    v-if="mov.estado === 'conciliado'"
                    @click="revertir(mov.id)"
                    class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                    title="Revertir"
                  >
                    <FontAwesomeIcon :icon="['fas', 'undo']" />
                  </button>
                  <button
                    v-if="mov.estado !== 'conciliado'"
                    @click="eliminar(mov.id)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Eliminar"
                  >
                    <FontAwesomeIcon :icon="['fas', 'trash']" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="movimientos.data.length === 0">
              <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-12 w-12 text-gray-300 mb-3" />
                <p class="font-medium">No hay movimientos</p>
                <p class="text-sm">Importa un estado de cuenta para comenzar</p>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginación -->
        <div v-if="movimientos.last_page > 1" class="px-4 py-3 bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Mostrando {{ movimientos.from }} a {{ movimientos.to }} de {{ movimientos.total }}
          </div>
          <div class="flex gap-2">
            <Link
              v-for="link in movimientos.links"
              :key="link.label"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-3 py-1 rounded text-sm',
                link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-900 border hover:bg-white dark:bg-slate-900',
                !link.url ? 'opacity-50 cursor-not-allowed' : ''
              ]"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Importar CSV -->
    <ImportarCsvModal
      v-if="showImportModal"
      :bancos-soportados="bancos_soportados"
      @close="showImportModal = false"
    />

    <!-- Modal Conciliar -->
    <ConciliarModal
      v-if="showConciliarModal"
      :movimiento="movimientoSeleccionado"
      @close="showConciliarModal = false"
      @conciliado="onConciliado"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import ImportarCsvModal from '@/Components/ConciliacionBancaria/ImportarCsvModal.vue'
import ConciliarModal from '@/Components/ConciliacionBancaria/ConciliarModal.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  movimientos: { type: Object, required: true },
  resumen: { type: Object, default: () => ({ total: 0, depositos: 0, retiros: 0, monto_depositos: 0, monto_retiros: 0 }) },
  filtros: { type: Object, default: () => ({}) },
  bancos: { type: Array, default: () => [] },
  bancos_soportados: { type: Array, default: () => ['BBVA', 'BANORTE', 'SANTANDER'] },
})

const showImportModal = ref(false)
const showConciliarModal = ref(false)
const movimientoSeleccionado = ref(null)

const form = useForm({
  estado: props.filtros.estado || 'pendiente',
  tipo: props.filtros.tipo || '',
  banco: props.filtros.banco || '',
  fecha_desde: props.filtros.fecha_desde || '',
  fecha_hasta: props.filtros.fecha_hasta || '',
})

const diferencia = computed(() => props.resumen.monto_depositos - props.resumen.monto_retiros)

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (fecha) => {
  if (!fecha) return '-'
  return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const estadoClass = (estado) => {
  const clases = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    conciliado: 'bg-green-100 text-green-800',
    ignorado: 'bg-gray-100 text-gray-600 dark:text-gray-300',
  }
  return clases[estado] || 'bg-gray-100 text-gray-600 dark:text-gray-300'
}

const estadoLabel = (estado) => {
  const labels = {
    pendiente: 'Pendiente',
    conciliado: 'Conciliado',
    ignorado: 'Ignorado',
  }
  return labels[estado] || estado
}

const aplicarFiltros = () => {
  router.get(route('conciliacion.index'), {
    estado: form.estado,
    tipo: form.tipo || undefined,
    banco: form.banco || undefined,
    fecha_desde: form.fecha_desde || undefined,
    fecha_hasta: form.fecha_hasta || undefined,
  }, { preserveState: true })
}

const abrirConciliar = (mov) => {
  movimientoSeleccionado.value = mov
  showConciliarModal.value = true
}

const onConciliado = () => {
  showConciliarModal.value = false
  router.reload()
}

const ignorar = (id) => {
  if (confirm('¿Ignorar este movimiento?')) {
    router.post(route('conciliacion.ignorar', id))
  }
}

const revertir = (id) => {
  if (confirm('¿Revertir la conciliación de este movimiento?')) {
    router.post(route('conciliacion.revertir', id))
  }
}

const eliminar = (id) => {
  if (confirm('¿Eliminar este movimiento? Esta acción no se puede deshacer.')) {
    router.delete(route('conciliacion.destroy', id))
  }
}

const conciliacionAutomatica = () => {
  if (confirm('¿Ejecutar conciliación automática? Solo se conciliarán movimientos con match exacto de monto.')) {
    router.post(route('conciliacion.automatica'))
  }
}
</script>




