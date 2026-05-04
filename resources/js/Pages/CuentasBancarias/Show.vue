<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <Head :title="`${cuenta.nombre} - Cuenta Bancaria`" />

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-8">
        <div class="flex items-start gap-3 min-w-0">
          <Link
            :href="route('cuentas-bancarias.index')"
            class="shrink-0 p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors"
          >
            <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
          </Link>
          <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight truncate">
              {{ cuenta.nombre }}
            </h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1 text-sm sm:text-base">
              {{ cuenta.banco }} · {{ cuenta.numero_cuenta || '****' }}
            </p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
          <button
            type="button"
            @click="showModal = true"
            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold bg-amber-500 text-white shadow-md shadow-amber-500/20 hover:bg-amber-600 dark:bg-amber-500 dark:hover:bg-amber-400 transition-colors"
          >
            <FontAwesomeIcon :icon="['fas', 'plus-circle']" class="mr-2" />
            Registrar Movimiento
          </button>
          <Link
            :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuenta.id })"
            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 shadow-md shadow-blue-600/15 transition-colors"
          >
            <FontAwesomeIcon :icon="['fas', 'edit']" class="mr-2" />
            Editar
          </Link>
          <Link
            :href="route('cuentas-bancarias.movimientos', { cuentas_bancaria: cuenta.id })"
            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 shadow-md shadow-emerald-600/15 transition-colors"
          >
            <FontAwesomeIcon :icon="['fas', 'list']" class="mr-2" />
            Ver Movimientos
          </Link>
        </div>
      </div>

      <!-- Tarjeta de saldo (premium) -->
      <div
        class="relative rounded-[1.75rem] shadow-2xl mb-8 overflow-hidden border border-white/10 text-white"
        :style="balanceCardStyle"
      >
        <div class="absolute inset-0 bg-gradient-to-tr from-white/10 via-transparent to-white/5 pointer-events-none" />
        <div
          class="absolute top-0 left-0 right-0 h-1 opacity-90"
          :style="{ background: `linear-gradient(90deg, ${accentColor}, transparent)` }"
        />
        <div class="relative p-6 sm:p-8 flex items-center justify-between gap-6">
          <div class="min-w-0">
            <p class="text-white/80 text-xs font-semibold uppercase tracking-[0.2em]">Saldo actual</p>
            <p class="text-3xl sm:text-4xl font-black mt-2 tabular-nums tracking-tight drop-shadow-sm">
              ${{ formatMonto(cuenta.saldo_actual) }}
            </p>
            <p class="text-white/70 text-sm mt-3">
              Saldo inicial:
              <span class="font-medium text-white/90">${{ formatMonto(cuenta.saldo_inicial) }}</span>
            </p>
          </div>
          <div
            class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center shadow-inner border border-white/20 bg-white/10 backdrop-blur-md"
            :style="{ backgroundColor: `${accentColor}cc` }"
          >
            <FontAwesomeIcon :icon="['fas', 'landmark']" class="h-8 w-8 sm:h-10 sm:w-10 text-white drop-shadow" />
          </div>
        </div>
      </div>

      <!-- Info de la cuenta -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div
          class="rounded-2xl p-6 border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
        >
          <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-6 rounded-full bg-blue-500 dark:bg-blue-400" />
            Información de la cuenta
          </h3>
          <dl class="space-y-3 text-sm sm:text-base">
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Banco</dt>
              <dd class="text-slate-900 dark:text-slate-100 font-medium text-right">{{ cuenta.banco }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Número de cuenta</dt>
              <dd class="text-slate-900 dark:text-slate-100 font-medium text-right font-mono text-sm">
                {{ cuenta.numero_cuenta || 'No especificado' }}
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">CLABE</dt>
              <dd class="text-slate-900 dark:text-slate-100 font-medium text-right font-mono text-sm">
                {{ cuenta.clabe || 'No especificada' }}
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Tipo</dt>
              <dd class="text-slate-900 dark:text-slate-100 font-medium text-right capitalize">{{ cuenta.tipo }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Moneda</dt>
              <dd class="text-slate-900 dark:text-slate-100 font-medium text-right">{{ cuenta.moneda }}</dd>
            </div>
            <div class="flex justify-between gap-4 items-center">
              <dt class="text-slate-500 dark:text-slate-400">Estado</dt>
              <dd>
                <span
                  :class="
                    cuenta.activa
                      ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                      : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                  "
                  class="px-2.5 py-1 rounded-full text-xs font-semibold"
                >
                  {{ cuenta.activa ? 'Activa' : 'Inactiva' }}
                </span>
              </dd>
            </div>
          </dl>
        </div>

        <div
          class="rounded-2xl p-6 border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
        >
          <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-6 rounded-full bg-amber-500 dark:bg-amber-400" />
            Notas
          </h3>
          <p class="text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
            {{ cuenta.notas || 'Sin notas' }}
          </p>
        </div>
      </div>

      <!-- Últimos movimientos -->
      <div
        class="rounded-2xl border overflow-hidden bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
      >
        <div
          class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40"
        >
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Últimos movimientos</h3>
        </div>
        <div v-if="movimientos.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/60">
              <tr>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider"
                >
                  Fecha
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider"
                >
                  Concepto
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider"
                >
                  Tipo
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider"
                >
                  Monto
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider"
                >
                  Estado
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr
                v-for="mov in movimientos"
                :key="mov.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
              >
                <td class="px-4 sm:px-6 py-4 text-sm text-slate-900 dark:text-slate-100 whitespace-nowrap">
                  {{ formatFecha(mov.fecha) }}
                </td>
                <td class="px-4 sm:px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  <div>{{ mov.concepto }}</div>
                  <div v-if="mov.folio_venta" class="text-xs text-blue-600 dark:text-blue-400 font-medium mt-0.5">
                    Folio: {{ mov.folio_venta }}
                  </div>
                </td>
                <td class="px-4 sm:px-6 py-4">
                  <span
                    :class="
                      mov.tipo === 'deposito'
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                        : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'
                    "
                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                  >
                    {{ mov.tipo === 'deposito' ? 'Depósito' : 'Retiro' }}
                  </span>
                </td>
                <td
                  class="px-4 sm:px-6 py-4 text-sm text-right font-semibold tabular-nums"
                  :class="
                    mov.tipo === 'deposito'
                      ? 'text-emerald-600 dark:text-emerald-400'
                      : 'text-rose-600 dark:text-rose-400'
                  "
                >
                  {{ mov.tipo === 'deposito' ? '+' : '−' }}${{ formatMonto(Math.abs(Number(mov.monto))) }}
                </td>
                <td class="px-4 sm:px-6 py-4">
                  <span :class="getEstadoClass(mov.estado)" class="px-2.5 py-1 rounded-full text-xs font-semibold">
                    {{ getEstadoLabel(mov.estado) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-12 text-center">
          <FontAwesomeIcon :icon="['fas', 'receipt']" class="h-12 w-12 text-slate-300 dark:text-slate-600 mb-4 mx-auto" />
          <p class="text-slate-500 dark:text-slate-400 font-medium">No hay movimientos registrados</p>
        </div>
      </div>
    </div>

    <!-- Modal registrar movimiento -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
      @click.self="showModal = false"
    >
      <div
        class="rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
      >
        <div
          class="px-6 py-4 border-b border-amber-200/80 dark:border-slate-700 bg-amber-50 dark:bg-slate-800/80"
        >
          <h3 class="text-xl font-bold text-slate-900 dark:text-white">Registrar movimiento manual</h3>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-0.5">Ingresos o egresos fuera del flujo habitual</p>
        </div>
        <form @submit.prevent="registrarMovimiento" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de movimiento</label>
            <div class="flex flex-wrap gap-4">
              <label class="flex items-center cursor-pointer">
                <input v-model="form.tipo" type="radio" value="deposito" class="mr-2 accent-emerald-600" />
                <span class="text-emerald-700 dark:text-emerald-400 font-medium">Depósito (ingreso)</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input v-model="form.tipo" type="radio" value="retiro" class="mr-2 accent-rose-600" />
                <span class="text-rose-700 dark:text-rose-400 font-medium">Retiro (egreso)</span>
              </label>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Monto</label>
            <input
              v-model="form.monto"
              type="number"
              step="0.01"
              min="0.01"
              required
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Concepto / descripción</label>
            <input
              v-model="form.concepto"
              type="text"
              required
              placeholder="Ej. ajuste, préstamo, venta de activo…"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoría (opcional)</label>
            <select
              v-model="form.categoria"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            >
              <option value="otro">Otro</option>
              <option value="prestamo">Préstamo recibido/dado</option>
              <option value="venta">Venta de activo</option>
              <option value="traspaso">Traspaso</option>
              <option value="ajuste">Ajuste contable</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Referencia (opcional)</label>
            <input
              v-model="form.referencia"
              type="text"
              placeholder="Folio, referencia bancaria…"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            />
          </div>
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
            <button
              type="button"
              class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
              @click="showModal = false"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 disabled:opacity-50 dark:bg-amber-500 dark:hover:bg-amber-400 transition-colors"
            >
              {{ form.processing ? 'Guardando…' : 'Registrar movimiento' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuenta: { type: Object, required: true },
  movimientos: { type: Array, default: () => [] },
})

const page = usePage()
const showModal = ref(false)
const isDark = ref(false)
let darkModeObserver = null

const checkDarkMode = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  checkDarkMode()
  darkModeObserver = new MutationObserver(() => checkDarkMode())
  darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onUnmounted(() => {
  darkModeObserver?.disconnect()
})

const accentColor = computed(() => props.cuenta.color || '#3b82f6')

const balanceCardStyle = computed(() => {
  const colors = page.props.empresa_config || {}
  const principal = colors.color_principal || '#2563eb'
  const secundario = colors.color_secundario || '#1d4ed8'
  if (isDark.value) {
    return {
      background: 'linear-gradient(135deg, #0f172a 0%, #020617 55%, #0c1222 100%)',
    }
  }
  return {
    background: `linear-gradient(120deg, ${principal} 0%, ${secundario} 100%)`,
  }
})

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
    pendiente: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    conciliado: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    ignorado: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
  }
  return clases[estado] || 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

const getEstadoLabel = (estado) => {
  const labels = {
    pendiente: 'Pendiente',
    conciliado: 'Conciliado',
    ignorado: 'Ignorado',
  }
  return labels[estado] || estado
}
</script>
