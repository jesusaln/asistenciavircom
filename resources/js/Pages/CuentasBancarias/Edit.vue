<template>
  <div class="min-h-screen bg-[var(--ui-surface)] transition-colors duration-200">
    <Head :title="`Editar ${cuenta.nombre}`" />

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-start gap-3 mb-8">
        <Link
          :href="route('cuentas-bancarias.index')"
          class="shrink-0 p-2 rounded-xl text-slate-500 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 border border-transparent hover:border-brand-500 dark:hover:border-brand-500 transition-colors"
        >
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
        </Link>
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Editar cuenta bancaria</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">{{ cuenta.nombre }} · {{ cuenta.banco }}</p>
        </div>
      </div>

      <div
        class="rounded-2xl border p-6 max-w-2xl bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
      >
        <form @submit.prevent="submit">
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
              Nombre de la cuenta <span class="text-rose-500 dark:text-rose-400">*</span>
            </label>
            <input
              v-model="form.nombre"
              type="text"
              class="input-cb"
              :class="{ 'ring-2 ring-rose-500 dark:ring-rose-400 border-transparent': form.errors.nombre }"
            />
            <p v-if="form.errors.nombre" class="text-rose-600 dark:text-rose-400 text-sm mt-1">{{ form.errors.nombre }}</p>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
              Banco <span class="text-rose-500 dark:text-rose-400">*</span>
            </label>
            <select
              v-model="form.banco"
              class="input-cb"
              :class="{ 'ring-2 ring-rose-500 dark:ring-rose-400 border-transparent': form.errors.banco }"
            >
              <option value="">Seleccionar banco</option>
              <option v-for="banco in bancos" :key="banco" :value="banco">{{ banco }}</option>
            </select>
            <p v-if="form.errors.banco" class="text-rose-600 dark:text-rose-400 text-sm mt-1">{{ form.errors.banco }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Número de cuenta</label>
              <input v-model="form.numero_cuenta" type="text" maxlength="20" class="input-cb" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">CLABE interbancaria</label>
              <input
                v-model="form.clabe"
                type="text"
                maxlength="18"
                class="input-cb"
                :class="{ 'ring-2 ring-rose-500 dark:ring-rose-400 border-transparent': form.errors.clabe }"
              />
              <p v-if="form.errors.clabe" class="text-rose-600 dark:text-rose-400 text-sm mt-1">{{ form.errors.clabe }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Saldo inicial <span class="text-rose-500 dark:text-rose-400">*</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">$</span>
                <input
                  v-model="form.saldo_inicial"
                  type="number"
                  step="0.01"
                  min="0"
                  class="input-cb pl-8"
                  :class="{ 'ring-2 ring-rose-500 dark:ring-rose-400 border-transparent': form.errors.saldo_inicial }"
                />
              </div>
              <p v-if="form.errors.saldo_inicial" class="text-rose-600 dark:text-rose-400 text-sm mt-1">{{ form.errors.saldo_inicial }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Tipo de cuenta</label>
              <select v-model="form.tipo" class="input-cb">
                <option value="corriente">Corriente / Cheques</option>
                <option value="ahorro">Ahorro</option>
                <option value="credito">Crédito</option>
                <option value="inversion">Inversión</option>
              </select>
            </div>
          </div>

          <div class="mb-6">
            <label class="flex items-center cursor-pointer">
              <input v-model="form.activa" type="checkbox" class="rounded-xl border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-brand-500 bg-white dark:bg-slate-800" />
              <span class="ml-2 text-sm text-slate-700 dark:text-slate-200">Cuenta activa</span>
            </label>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="col-span-1 md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Responsable / Titular de la tarjeta
              </label>
              <select v-model="form.responsable_id" class="input-cb">
                <option :value="null">Seleccionar responsable (Opcional)</option>
                <option v-for="user in usuarios" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
              <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">Si es una tarjeta a cargo de un técnico, selecciónalo aquí para que solo él pueda verla en la App.</p>
            </div>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Notas</label>
            <textarea v-model="form.notas" rows="3" class="input-cb" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-300 dark:border-slate-600">
            <Link
              :href="route('cuentas-bancarias.index')"
              class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium transition-colors"
            >
              Cancelar
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center px-6 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 dark:bg-brand-500 dark:hover:bg-blue-400 disabled:opacity-50 font-semibold shadow-md transition-colors"
            >
              <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
              Guardar cambios
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
  usuarios: { type: Array, default: () => [] },
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
  responsable_id: props.cuenta.responsable_id || null,
})

const submit = () => {
  form.put(route('cuentas-bancarias.update', props.cuenta.id))
}
</script>

