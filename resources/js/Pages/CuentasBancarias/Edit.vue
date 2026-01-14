<template>
  <div>
    <Head :title="`Editar ${cuenta.nombre}`" />

    <div class="container mx-auto px-6 py-8">
      <!-- Header -->
      <div class="flex items-center mb-8">
        <Link :href="route('cuentas-bancarias.index')" class="mr-4 p-2 hover:bg-gray-100 rounded-lg">
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="text-gray-600" />
        </Link>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Editar Cuenta Bancaria</h1>
          <p class="text-gray-600 mt-1">{{ cuenta.nombre }} - {{ cuenta.banco }}</p>
        </div>
      </div>

      <!-- Formulario -->
      <div class="bg-white rounded-xl shadow-md p-6 max-w-2xl">
        <form @submit.prevent="submit">
          <!-- Nombre -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nombre de la cuenta <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              v-model="form.nombre"
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
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                <input
                  type="number"
                  v-model="form.saldo_inicial"
                  step="0.01"
                  min="0"
                  class="w-full pl-8 rounded-lg border-gray-300"
                  :class="{ 'border-red-500': form.errors.saldo_inicial }"
                />
              </div>
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

          <!-- Activa -->
          <div class="mb-6">
            <label class="flex items-center">
              <input type="checkbox" v-model="form.activa" class="rounded border-gray-300 text-blue-600" />
              <span class="ml-2 text-sm text-gray-700">Cuenta activa</span>
            </label>
          </div>

          <!-- Notas -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
            <textarea
              v-model="form.notas"
              rows="3"
              class="w-full rounded-lg border-gray-300"
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Link
              :href="route('cuentas-bancarias.index')"
              class="px-4 py-2 border rounded-lg hover:bg-gray-50"
            >
              Cancelar
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center"
            >
              <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
              Guardar Cambios
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
  cuenta: { type: Object, required: true },
  bancos: { type: Array, default: () => [] },
  tipos: { type: Array, default: () => [] },
})

const form = useForm({
  nombre: props.cuenta.nombre,
  banco: props.cuenta.banco,
  numero_cuenta: props.cuenta.numero_cuenta || '',
  clabe: props.cuenta.clabe || '',
  saldo_inicial: props.cuenta.saldo_inicial,
  tipo: props.cuenta.tipo || 'corriente',
  activa: props.cuenta.activa,
  notas: props.cuenta.notas || '',
})

const submit = () => {
  form.put(route('cuentas-bancarias.update', props.cuenta.id))
}
</script>

