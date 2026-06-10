<template>
  <div class="min-h-screen bg-[var(--ui-surface)] text-slate-900 dark:text-white transition-colors duration-200">
    <Head title="Nuevo Traspaso Bancario" />

    <div class="w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center tracking-tight">
            <FontAwesomeIcon :icon="['fas', 'plus']" class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mr-3 shrink-0" />
            Nuevo Traspaso
          </h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">Transfiere fondos entre tus cuentas registradas</p>
        </div>
        <Link
          :href="route('traspasos-bancarios.index')"
          class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700 font-medium self-start sm:self-auto"
        >
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="mr-2" />
          Volver
        </Link>
      </div>

      <!-- Form Card -->
      <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xl p-6 sm:p-10 space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Cuenta Origen -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cuenta Origen *</label>
            <select 
              v-model="form.cuenta_origen_id" 
              class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 focus:border-brand-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-brand-500/10 transition-all outline-none font-medium"
              required
            >
              <option value="" disabled>Selecciona cuenta origen...</option>
              <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                {{ cuenta.nombre }} - {{ cuenta.banco }} (${{ formatMonto(cuenta.saldo_actual) }})
              </option>
            </select>
            <span v-if="form.errors.cuenta_origen_id" class="text-xs text-rose-500 mt-1 block">{{ form.errors.cuenta_origen_id }}</span>
          </div>

          <!-- Cuenta Destino -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cuenta Destino *</label>
            <select 
              v-model="form.cuenta_destino_id" 
              class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 focus:border-brand-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-brand-500/10 transition-all outline-none font-medium"
              required
            >
              <option value="" disabled>Selecciona cuenta destino...</option>
              <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                {{ cuenta.nombre }} - {{ cuenta.banco }} (${{ formatMonto(cuenta.saldo_actual) }})
              </option>
            </select>
            <span v-if="form.errors.cuenta_destino_id" class="text-xs text-rose-500 mt-1 block">{{ form.errors.cuenta_destino_id }}</span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Monto -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Monto a Transferir *</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold">$</span>
              <input
                v-model="form.monto"
                type="number"
                step="0.01"
                min="0.01"
                placeholder="0.00"
                class="w-full pl-8 pr-4 py-3 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 focus:border-brand-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-brand-500/10 transition-all outline-none font-bold tabular-nums"
                required
              />
            </div>
            <span v-if="form.errors.monto" class="text-xs text-rose-500 mt-1 block">{{ form.errors.monto }}</span>
          </div>

          <!-- Fecha -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Fecha *</label>
            <input
              v-model="form.fecha"
              type="date"
              class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 focus:border-brand-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-brand-500/10 transition-all outline-none font-medium"
              required
            />
            <span v-if="form.errors.fecha" class="text-xs text-rose-500 mt-1 block">{{ form.errors.fecha }}</span>
          </div>
        </div>

        <!-- Referencia -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Referencia / Folio</label>
          <input
            v-model="form.referencia"
            type="text"
            placeholder="Ej: SPEI-938201 o VENTANILLA"
            class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 focus:border-brand-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-brand-500/10 transition-all outline-none font-medium"
          />
          <span v-if="form.errors.referencia" class="text-xs text-rose-500 mt-1 block">{{ form.errors.referencia }}</span>
        </div>

        <!-- Notas -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Notas / Observaciones</label>
          <textarea
            v-model="form.notas"
            rows="3"
            placeholder="Detalles opcionales sobre el movimiento bancario..."
            class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 focus:border-brand-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-brand-500/10 transition-all outline-none font-medium"
          ></textarea>
          <span v-if="form.errors.notas" class="text-xs text-rose-500 mt-1 block">{{ form.errors.notas }}</span>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
          <Link
            :href="route('traspasos-bancarios.index')"
            class="px-5 py-3 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-3 bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-400 text-white rounded-xl font-bold text-sm shadow-xl shadow-indigo-600/20 disabled:opacity-50 transition-all flex items-center gap-2"
          >
            <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'spinner']" spin />
            <span>{{ form.processing ? 'Procesando...' : 'Realizar Traspaso' }}</span>
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faPlus, faArrowLeft, faSpinner } from '@fortawesome/free-solid-svg-icons'

library.add(faPlus, faArrowLeft, faSpinner)

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuentas: { type: Array, default: () => [] }
})

const form = useForm({
  cuenta_origen_id: '',
  cuenta_destino_id: '',
  monto: '',
  fecha: new Date().toISOString().split('T')[0],
  referencia: '',
  notas: ''
})

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const submit = () => {
  form.post(route('traspasos-bancarios.store'))
}
</script>
