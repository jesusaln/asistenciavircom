<!-- /resources/js/Pages/EntregasDinero/Create.vue -->
<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

// Estado del formulario
const form = ref({
  fecha_entrega: new Date().toISOString().split('T')[0],
  monto_efectivo: 0,
  monto_transferencia: 0,
  monto_cheques: 0,
  monto_tarjetas: 0,
  notas: ''
})

// Computed para el total
const total = computed(() => {
  return parseFloat(form.value.monto_efectivo || 0) +
         parseFloat(form.value.monto_transferencia || 0) +
         parseFloat(form.value.monto_cheques || 0) +
         parseFloat(form.value.monto_tarjetas || 0)
})

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)

// Submit
const submit = () => {
  if (total.value <= 0) {
    notyf.error('El total debe ser mayor a cero')
    return
  }

  router.post(route('entregas-dinero.store'), {
    fecha_entrega: form.value.fecha_entrega,
    monto_efectivo: parseFloat(form.value.monto_efectivo || 0),
    monto_transferencia: parseFloat(form.value.monto_transferencia || 0),
    monto_cheques: parseFloat(form.value.monto_cheques || 0),
    monto_tarjetas: parseFloat(form.value.monto_tarjetas || 0),
    notas: form.value.notas
  }, {
    onSuccess: () => {
      notyf.success('Entrega de dinero registrada correctamente')
      router.visit(route('entregas-dinero.index'))
    },
    onError: (errors) => {
      notyf.error('Error al registrar la entrega')
    }
  })
}
</script>

<template>
  <Head title="Registrar Entrega de Dinero" />

  <div class="entregas-dinero-create min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-6 py-8">
      <!-- Header -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-8 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <button
            @click="router.visit(route('entregas-dinero.index'))"
            class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-2xl font-bold text-slate-900">Registrar Entrega de Dinero</h1>
        </div>

        <p class="text-slate-600 mb-6">
          Registra el dinero que traes para entregarlo. Incluye efectivo, cheques y tarjetas por separado.
        </p>

        <!-- Formulario -->
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Fecha de entrega -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Fecha de Entrega *
            </label>
            <input
              v-model="form.fecha_entrega"
              type="date"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- Montos -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Efectivo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Efectivo
              </label>
              <input
                v-model="form.monto_efectivo"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- Transferencia -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Transferencia
              </label>
              <input
                v-model="form.monto_transferencia"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- Cheques -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Cheques
              </label>
              <input
                v-model="form.monto_cheques"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- Tarjetas -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tarjetas
              </label>
              <input
                v-model="form.monto_tarjetas"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <!-- Total -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-blue-700">Total a entregar:</span>
              <span class="text-xl font-bold text-blue-900">${{ formatNumber(total) }}</span>
            </div>
          </div>

          <!-- Notas -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notas (opcional)
            </label>
            <textarea
              v-model="form.notas"
              rows="3"
              placeholder="Agrega cualquier observación sobre el dinero que traes..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="router.visit(route('entregas-dinero.index'))"
              class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-medium"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="total <= 0"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
            >
              Registrar Entrega
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import "tailwindcss" reference;
.entregas-dinero-create {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>

