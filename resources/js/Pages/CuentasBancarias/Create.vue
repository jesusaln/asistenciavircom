<template>
  <Head title="Nueva Cuenta Bancaria" />

  <div class="min-h-screen bg-slate-950 text-slate-200 font-outfit pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      
      <!-- Breadcrumb / Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 animate-fade-in">
        <div class="flex items-center gap-5">
          <Link 
            :href="route('cuentas-bancarias.index')" 
            class="group flex items-center justify-center w-12 h-12 bg-slate-900/50 border border-slate-800 rounded-2xl hover:bg-slate-800 hover:border-slate-700 transition-all duration-300 shadow-xl"
          >
            <FontAwesomeIcon icon="arrow-left" class="text-slate-400 group-hover:text-white transition-colors" />
          </Link>
          <div>
            <h1 class="text-4xl font-extrabold text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">
              Nueva Cuenta Bancaria
            </h1>
            <p class="text-slate-400 mt-1 flex items-center gap-2">
              <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
              Registra una nueva cuenta para gestionar tus finanzas
            </p>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div class="max-w-4xl mx-auto">
        <div class="relative group">
          <!-- Decorative Blur -->
          <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-[2.5rem] blur opacity-75 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
          
          <div class="relative bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden p-8 md:p-12">
            <form @submit.prevent="submit" class="space-y-8">
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Nombre -->
                <div class="space-y-3">
                  <label class="block text-sm font-semibold text-slate-300 ml-1">
                    Nombre de la cuenta <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative group/input">
                    <FontAwesomeIcon icon="tag" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within/input:text-blue-400 transition-colors" />
                    <input
                      type="text"
                      v-model="form.nombre"
                      placeholder="Ej: BBVA Principal"
                      class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl pl-11 pr-4 py-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all duration-300"
                      :class="{ 'border-rose-500/50 ring-rose-500/10': form.errors.nombre }"
                    />
                  </div>
                  <p v-if="form.errors.nombre" class="text-rose-400 text-xs ml-1 font-medium">{{ form.errors.nombre }}</p>
                </div>

                <!-- Banco -->
                <div class="space-y-3">
                  <label class="block text-sm font-semibold text-slate-300 ml-1">
                    Banco <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative group/input">
                    <FontAwesomeIcon icon="university" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within/input:text-blue-400 transition-colors" />
                    <select
                      v-model="form.banco"
                      class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl pl-11 pr-4 py-4 text-white appearance-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all duration-300"
                      :class="{ 'border-rose-500/50 ring-rose-500/10': form.errors.banco }"
                    >
                      <option value="" disabled class="bg-slate-900">Seleccionar banco</option>
                      <option v-for="banco in bancos" :key="banco" :value="banco" class="bg-slate-900">{{ banco }}</option>
                    </select>
                    <FontAwesomeIcon icon="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-600 pointer-events-none text-xs" />
                  </div>
                  <p v-if="form.errors.banco" class="text-rose-400 text-xs ml-1 font-medium">{{ form.errors.banco }}</p>
                </div>

                <!-- Número de cuenta -->
                <div class="space-y-3">
                  <label class="block text-sm font-semibold text-slate-300 ml-1">Número de cuenta</label>
                  <div class="relative group/input">
                    <FontAwesomeIcon icon="credit-card" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within/input:text-blue-400 transition-colors" />
                    <input
                      type="text"
                      v-model="form.numero_cuenta"
                      placeholder="Últimos 4 dígitos"
                      maxlength="20"
                      class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl pl-11 pr-4 py-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all duration-300"
                    />
                  </div>
                </div>

                <!-- CLABE -->
                <div class="space-y-3">
                  <label class="block text-sm font-semibold text-slate-300 ml-1">CLABE Interbancaria</label>
                  <div class="relative group/input">
                    <FontAwesomeIcon icon="shield-alt" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within/input:text-blue-400 transition-colors" />
                    <input
                      type="text"
                      v-model="form.clabe"
                      placeholder="18 dígitos"
                      maxlength="18"
                      class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl pl-11 pr-4 py-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all duration-300"
                      :class="{ 'border-rose-500/50 ring-rose-500/10': form.errors.clabe }"
                    />
                  </div>
                  <p v-if="form.errors.clabe" class="text-rose-400 text-xs ml-1 font-medium">{{ form.errors.clabe }}</p>
                </div>

                <!-- Saldo inicial -->
                <div class="space-y-3">
                  <label class="block text-sm font-semibold text-slate-300 ml-1">
                    Saldo Inicial <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative group/input">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-slate-500 group-focus-within/input:text-emerald-400 transition-colors">$</span>
                    <input
                      type="number"
                      v-model="form.saldo_inicial"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      class="w-full pl-11 pr-4 py-4 bg-slate-950/50 border border-slate-800 rounded-2xl text-white placeholder-slate-600 font-mono focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500/50 transition-all duration-300"
                      :class="{ 'border-rose-500/50 ring-rose-500/10': form.errors.saldo_inicial }"
                    />
                  </div>
                  <p class="text-slate-500 text-[11px] ml-1 uppercase tracking-wider font-bold">Saldo al momento de registrar</p>
                  <p v-if="form.errors.saldo_inicial" class="text-rose-400 text-xs ml-1 font-medium">{{ form.errors.saldo_inicial }}</p>
                </div>

                <!-- Tipo de cuenta -->
                <div class="space-y-3">
                  <label class="block text-sm font-semibold text-slate-300 ml-1">Tipo de cuenta</label>
                  <div class="relative group/input">
                    <FontAwesomeIcon icon="wallet" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within/input:text-blue-400 transition-colors" />
                    <select
                      v-model="form.tipo"
                      class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl pl-11 pr-4 py-4 text-white appearance-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all duration-300"
                    >
                      <option value="corriente" class="bg-slate-900">Corriente / Cheques</option>
                      <option value="ahorro" class="bg-slate-900">Ahorro</option>
                      <option value="credito" class="bg-slate-900">Crédito</option>
                      <option value="inversion" class="bg-slate-900">Inversión</option>
                    </select>
                    <FontAwesomeIcon icon="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-600 pointer-events-none text-xs" />
                  </div>
                </div>
              </div>

              <!-- Notas -->
              <div class="space-y-3">
                <label class="block text-sm font-semibold text-slate-300 ml-1">Notas Adicionales</label>
                <div class="relative group/input">
                  <textarea
                    v-model="form.notas"
                    rows="4"
                    placeholder="Detalles importantes sobre la cuenta..."
                    class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all duration-300 resize-none"
                  ></textarea>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-6 border-t border-slate-800">
                <div class="text-slate-500 text-xs hidden sm:block italic">
                  * Campos obligatorios para el registro
                </div>
                <div class="flex items-center gap-4 w-full sm:w-auto">
                  <Link
                    :href="route('cuentas-bancarias.index')"
                    class="flex-1 sm:flex-none text-center px-8 py-4 border border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white rounded-2xl transition-all duration-300"
                  >
                    Cancelar
                  </Link>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex-1 sm:flex-none px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-2xl transition-all duration-300 shadow-xl shadow-blue-900/20 disabled:opacity-50 flex items-center justify-center gap-3 active:scale-95"
                  >
                    <FontAwesomeIcon v-if="form.processing" icon="spinner" class="animate-spin" />
                    <span>{{ form.processing ? 'Guardando...' : 'Guardar Cuenta' }}</span>
                  </button>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  bancos: { type: Array, default: () => [] },
  tipos: { type: Array, default: () => [] },
})

const form = useForm({
  nombre: '',
  banco: '',
  numero_cuenta: '',
  clabe: '',
  saldo_inicial: 0,
  tipo: 'corriente',
  notas: '',
})

const submit = () => {
  form.post(route('cuentas-bancarias.store'))
}
</script>

