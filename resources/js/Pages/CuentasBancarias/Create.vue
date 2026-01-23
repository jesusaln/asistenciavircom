<template>
  <div>
    <Head title="Nueva Cuenta Bancaria" />

    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="flex items-center mb-8">
        <Link :href="route('cuentas-bancarias.index')" class="mr-4 p-2 hover:bg-gray-100 rounded-lg">
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="text-gray-600 dark:text-gray-300" />
        </Link>
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nueva Cuenta Bancaria</h1>
          <p class="text-gray-600 dark:text-gray-300 mt-1">Registra una nueva cuenta para gestionar saldos</p>
        </div>
      </div>

      <!-- Formulario -->
      <div class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-6 max-w-2xl">
        <form @submit.prevent="submit">
          <!-- Nombre -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nombre de la cuenta <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              v-model="form.nombre"
              placeholder="Ej: BBVA Principal, Banorte Nómina"
              class="w-full rounded-lg border-gray-300"
              :class="{ 'border-red-500': form.errors.nombre }"
            />
            <p v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">{{ form.errors.nombre }}</p>
          </div>

          <!-- Banco -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Banco <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.banco"
              class="w-full rounded-lg border-gray-300"
              :class="{ 'border-red-500': form.errors.banco }"
            >
              <option value="">Seleccionar banco</option>
              <option v-for="banco in bancos" :key="banco" :value="banco">{{ banco }}</option>
            </select>
            <p v-if="form.errors.banco" class="text-red-500 text-sm mt-1">{{ form.errors.banco }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Número de cuenta -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Número de cuenta</label>
              <input
                type="text"
                v-model="form.numero_cuenta"
                placeholder="Últimos 4 dígitos"
                maxlength="20"
                class="w-full rounded-lg border-gray-300"
              />
            </div>

            <!-- CLABE -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">CLABE Interbancaria</label>
              <input
                type="text"
                v-model="form.clabe"
                placeholder="18 dígitos"
                maxlength="18"
                class="w-full rounded-lg border-gray-300"
                :class="{ 'border-red-500': form.errors.clabe }"
              />
              <p v-if="form.errors.clabe" class="text-red-500 text-sm mt-1">{{ form.errors.clabe }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Saldo inicial -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Saldo Inicial <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">$</span>
                <input
                  type="number"
                  v-model="form.saldo_inicial"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="w-full pl-8 rounded-lg border-gray-300"
                  :class="{ 'border-red-500': form.errors.saldo_inicial }"
                />
              </div>
              <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Saldo actual de la cuenta al momento de registrarla</p>
              <p v-if="form.errors.saldo_inicial" class="text-red-500 text-sm mt-1">{{ form.errors.saldo_inicial }}</p>
            </div>

            <!-- Tipo de cuenta -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de cuenta</label>
              <select v-model="form.tipo" class="w-full rounded-lg border-gray-300">
                <option value="corriente">Corriente / Cheques</option>
                <option value="ahorro">Ahorro</option>
                <option value="credito">Crédito</option>
                <option value="inversion">Inversión</option>
              </select>
            </div>
          </div>

          <!-- Notas -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
            <textarea
              v-model="form.notas"
              rows="3"
              placeholder="Información adicional sobre esta cuenta"
              class="w-full rounded-lg border-gray-300"
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Link
              :href="route('cuentas-bancarias.index')"
              class="px-4 py-2 border rounded-lg hover:bg-white dark:bg-slate-900"
            >
              Cancelar
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center"
            >
              <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
              Guardar Cuenta
            </button>
          </div>
        </form>
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

