<template>
  <div>
    <Head :title="`${cuenta.nombre} - Cuenta Bancaria`" />

    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="flex items-center mb-8">
        <Link :href="route('cuentas-bancarias.index')" class="mr-4 p-2 hover:bg-gray-100 rounded-lg">
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="text-gray-600 dark:text-gray-300" />
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ cuenta.nombre }}</h1>
          <p class="text-gray-600 dark:text-gray-300 mt-1">{{ cuenta.banco }} • {{ cuenta.numero_cuenta || '****' }}</p>
        </div>
        <button
          @click="showModal = true"
          class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 flex items-center"
        >
          <FontAwesomeIcon :icon="['fas', 'plus-circle']" class="mr-2" />
          Registrar Movimiento
        </button>
        <Link
          :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuenta.id })"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center ml-2"
        >
          <FontAwesomeIcon :icon="['fas', 'edit']" class="mr-2" />
          Editar
        </Link>
        <Link
          :href="route('cuentas-bancarias.movimientos', { cuentas_bancaria: cuenta.id })"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center ml-2"
        >
          <FontAwesomeIcon :icon="['fas', 'list']" class="mr-2" />
          Ver Movimientos
        </Link>
      </div>

      <!-- Tarjeta de saldo -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-xl p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-blue-100 text-sm uppercase tracking-wide">Saldo Actual</p>
            <p class="text-4xl font-bold mt-1">${{ formatMonto(cuenta.saldo_actual) }}</p>
            <p class="text-blue-200 text-sm mt-2">Saldo inicial: ${{ formatMonto(cuenta.saldo_inicial) }}</p>
          </div>
          <div class="text-right">
            <div class="w-16 h-16 rounded-full flex items-center justify-center" :style="{ backgroundColor: cuenta.color || '#4B5563' }">
              <FontAwesomeIcon :icon="['fas', 'landmark']" class="h-8 w-8 text-white" />
            </div>
          </div>
        </div>
      </div>

      <!-- Info de la cuenta -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información de la Cuenta</h3>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-gray-500 dark:text-gray-400">Banco</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ cuenta.banco }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500 dark:text-gray-400">Número de cuenta</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ cuenta.numero_cuenta || 'No especificado' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500 dark:text-gray-400">CLABE</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ cuenta.clabe || 'No especificada' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500 dark:text-gray-400">Tipo</dt>
              <dd class="text-gray-900 dark:text-white font-medium capitalize">{{ cuenta.tipo }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500 dark:text-gray-400">Moneda</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ cuenta.moneda }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
              <dd>
                <span :class="cuenta.activa ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600 dark:text-gray-300'" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ cuenta.activa ? 'Activa' : 'Inactiva' }}
                </span>
              </dd>
            </div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notas</h3>
          <p class="text-gray-600 dark:text-gray-300">{{ cuenta.notas || 'Sin notas' }}</p>
        </div>
      </div>

      <!-- Últimos movimientos -->
      <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b bg-white dark:bg-slate-900">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Últimos Movimientos</h3>
        </div>
        <div v-if="movimientos.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
            <thead class="bg-white dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Concepto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Monto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
              <tr v-for="mov in movimientos" :key="mov.id" class="hover:bg-white dark:bg-slate-900">
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ formatFecha(mov.fecha) }}</td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  <div>{{ mov.concepto }}</div>
                  <div v-if="mov.folio_venta" class="text-xs text-blue-600 font-medium">
                    Folio: {{ mov.folio_venta }}
                  </div>
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
          <p class="text-gray-500 dark:text-gray-400">No hay movimientos registrados</p>
        </div>
      </div>
    </div>

    <!-- Modal para registrar movimiento manual -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showModal = false">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b bg-amber-50">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Registrar Movimiento Manual</h3>
          <p class="text-gray-600 dark:text-gray-300 text-sm">Para ingresos o egresos fuera de operaciones normales</p>
        </div>
        <form @submit.prevent="registrarMovimiento" class="p-6 space-y-4">
          <!-- Tipo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento</label>
            <div class="flex gap-4">
              <label class="flex items-center cursor-pointer">
                <input type="radio" v-model="form.tipo" value="deposito" class="mr-2" />
                <span class="text-green-600 font-medium">⬆️ Depósito (Ingreso)</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input type="radio" v-model="form.tipo" value="retiro" class="mr-2" />
                <span class="text-red-600 font-medium">⬇️ Retiro (Egreso)</span>
              </label>
            </div>
          </div>
          <!-- Monto -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
            <input type="number" v-model="form.monto" step="0.01" min="0.01" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" />
          </div>
          <!-- Concepto -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Concepto / Descripción</label>
            <input type="text" v-model="form.concepto" required placeholder="Ej: Venta de carro, Préstamo personal..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" />
          </div>
          <!-- Categoría -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría (opcional)</label>
            <select v-model="form.categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
              <option value="otro">Otro</option>
              <option value="prestamo">Préstamo recibido/dado</option>
              <option value="venta">Venta de activo</option>
              <option value="traspaso">Traspaso</option>
              <option value="ajuste">Ajuste contable</option>
            </select>
          </div>
          <!-- Referencia -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Referencia (opcional)</label>
            <input type="text" v-model="form.referencia" placeholder="Número de referencia, folio, etc."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" />
          </div>
          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
              Cancelar
            </button>
            <button type="submit" :disabled="form.processing"
              class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 disabled:opacity-50">
              {{ form.processing ? 'Guardando...' : 'Registrar Movimiento' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuenta: { type: Object, required: true },
  movimientos: { type: Array, default: () => [] },
})

const showModal = ref(false)

const form = useForm({
  tipo: 'deposito',
  monto: '',
  concepto: '',
  categoria: 'otro',
  referencia: '',
})

const registrarMovimiento = () => {
  form.post(route('cuentas-bancarias.registrar-movimiento', { cuentas_bancaria: props.cuenta.id }), {
    onSuccess: () => {
      showModal.value = false
      form.reset()
    },
  })
}

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
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'conciliado': 'bg-green-100 text-green-700',
    'ignorado': 'bg-gray-100 text-gray-600 dark:text-gray-300',
  }
  return clases[estado] || 'bg-gray-100 text-gray-600 dark:text-gray-300'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'conciliado': 'Conciliado',
    'ignorado': 'Ignorado',
  }
  return labels[estado] || estado
}
</script>

