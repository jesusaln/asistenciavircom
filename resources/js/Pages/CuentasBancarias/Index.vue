<template>
  <div>
    <Head title="Cuentas Bancarias" />

    <div class="w-full px-6 py-8 animate-fade-in">
      <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <FontAwesomeIcon :icon="['fas', 'landmark']" class="h-8 w-8 text-blue-600 mr-3" />
                Cuentas Bancarias
              </h1>
              <p class="text-gray-600 mt-1">Gestiona tus cuentas bancarias y saldos</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <Link
                :href="route('traspasos-bancarios.index')"
                class="mt-4 md:mt-0 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center shadow-md"
                >
                <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="mr-2" />
                Ver Traspasos
                </Link>
                <Link
                :href="route('cuentas-bancarias.create')"
                class="mt-4 md:mt-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center shadow-md"
                >
                <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                Nueva Cuenta
                </Link>
            </div>
        </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r">
        {{ $page.props.flash.error }}
      </div>

      <!-- Total General -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-xl p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-blue-100 text-sm uppercase tracking-wide">Saldo Total en Cuentas</p>
            <p class="text-4xl font-bold mt-1">${{ formatMonto(totales.saldo_total) }}</p>
            <p class="text-blue-200 text-sm mt-2">{{ totales.cuentas_activas }} cuenta(s) activa(s)</p>
          </div>
          <FontAwesomeIcon :icon="['fas', 'wallet']" class="h-16 w-16 text-blue-400/50" />
        </div>
      </div>

      <!-- Grid de Cuentas -->
      <div v-if="cuentas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="cuenta in cuentas"
          :key="cuenta.id"
          class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow"
          :class="{ 'opacity-60': !cuenta.activa }"
        >
          <!-- Header de la tarjeta con color del banco -->
          <div class="h-2" :style="{ backgroundColor: cuenta.color }"></div>
          
          <div class="p-5">
            <!-- Info del banco -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="font-bold text-gray-900">{{ cuenta.nombre }}</h3>
                <p class="text-sm text-gray-500">{{ cuenta.banco }}</p>
                <p class="text-xs text-gray-400">{{ cuenta.numero_cuenta_mascarado }}</p>
              </div>
              <span
                v-if="!cuenta.activa"
                class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
              >
                Inactiva
              </span>
            </div>

            <!-- Saldo -->
            <div class="bg-white rounded-lg p-4 mb-4">
              <p class="text-xs text-gray-500 uppercase">Saldo Actual</p>
              <p class="text-2xl font-bold" :class="cuenta.saldo_actual >= 0 ? 'text-green-600' : 'text-red-600'">
                ${{ formatMonto(cuenta.saldo_actual) }}
              </p>
              <p class="text-xs text-gray-400 mt-1">Saldo inicial: ${{ formatMonto(cuenta.saldo_inicial) }}</p>
            </div>

            <!-- Info adicional -->
            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
              <span>{{ cuenta.tipo }}</span>
              <span>{{ cuenta.movimientos_count }} movimientos</span>
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-end gap-2 pt-4 border-t">
              <Link
                :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })"
                class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                title="Ver cuenta y movimientos"
              >
                <FontAwesomeIcon :icon="['fas', 'list']" />
              </Link>
              <Link
                :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })"
                class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                title="Ver registros"
              >
                Ver registros
              </Link>
              <button
                @click="verDetalle(cuenta)"
                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="Ver detalle rápido"
              >
                <FontAwesomeIcon :icon="['fas', 'eye']" />
              </button>
              <Link
                :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuenta.id })"
                class="p-2 text-gray-600 hover:bg-white rounded-lg transition-colors"
                title="Editar"
              >
                <FontAwesomeIcon :icon="['fas', 'edit']" />
              </Link>
              <button
                v-if="cuenta.movimientos_count === 0"
                @click="eliminar(cuenta)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Eliminar"
              >
                <FontAwesomeIcon :icon="['fas', 'trash']" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado vacío -->
      <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
        <FontAwesomeIcon :icon="['fas', 'piggy-bank']" class="h-16 w-16 text-gray-300 mb-4" />
        <h3 class="text-xl font-medium text-gray-900 mb-2">No hay cuentas bancarias</h3>
        <p class="text-gray-500 mb-6">Agrega una cuenta bancaria para comenzar a gestionar tus saldos</p>
        <Link
          :href="route('cuentas-bancarias.create')"
          class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Agregar Cuenta
        </Link>
      </div>
    </div>

    <!-- Modal de Detalle -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <!-- Header con color del banco -->
        <div class="h-2" :style="{ backgroundColor: cuentaSeleccionada?.color }"></div>
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xl font-bold text-gray-900">{{ cuentaSeleccionada?.nombre }}</h3>
              <p class="text-gray-500">{{ cuentaSeleccionada?.banco }}</p>
            </div>
            <button @click="showModal = false" class="p-2 hover:bg-gray-100 rounded-lg">
              <FontAwesomeIcon :icon="['fas', 'times']" class="text-gray-400" />
            </button>
          </div>
        </div>

        <!-- Contenido -->
        <div class="p-6 space-y-4">
          <!-- Saldo -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-4 text-white">
            <p class="text-blue-100 text-sm">Saldo Actual</p>
            <p class="text-3xl font-bold">${{ formatMonto(cuentaSeleccionada?.saldo_actual) }}</p>
          </div>

          <!-- Info -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase">Número de cuenta</p>
              <p class="font-medium text-gray-900">{{ cuentaSeleccionada?.numero_cuenta || 'No especificado' }}</p>
            </div>
            <div class="bg-white rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase">Tipo</p>
              <p class="font-medium text-gray-900 capitalize">{{ cuentaSeleccionada?.tipo }}</p>
            </div>
            <div class="bg-white rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase">Saldo inicial</p>
              <p class="font-medium text-gray-900">${{ formatMonto(cuentaSeleccionada?.saldo_inicial) }}</p>
            </div>
            <div class="bg-white rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase">Movimientos</p>
              <p class="font-medium text-gray-900">{{ cuentaSeleccionada?.movimientos_count }}</p>
            </div>
          </div>

          <!-- CLABE -->
          <div v-if="cuentaSeleccionada?.clabe" class="bg-white rounded-lg p-3">
            <p class="text-xs text-gray-500 uppercase">CLABE Interbancaria</p>
            <p class="font-medium text-gray-900 font-mono">{{ cuentaSeleccionada?.clabe }}</p>
          </div>

          <!-- Notas -->
          <div v-if="cuentaSeleccionada?.notas" class="bg-white rounded-lg p-3">
            <p class="text-xs text-gray-500 uppercase">Notas</p>
            <p class="text-gray-700">{{ cuentaSeleccionada?.notas }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-3 p-4 border-t bg-white">
          <button @click="showModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
            Cerrar
          </button>
          <Link
            :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuentaSeleccionada?.id })"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Editar
          </Link>
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
  cuentas: { type: Array, default: () => [] },
  totales: { type: Object, default: () => ({ saldo_total: 0, cuentas_activas: 0 }) },
})

const showModal = ref(false)
const cuentaSeleccionada = ref(null)

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const verDetalle = (cuenta) => {
  cuentaSeleccionada.value = cuenta
  showModal.value = true
}

const eliminar = (cuenta) => {
  if (cuenta.movimientos_count > 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se puede eliminar una cuenta con movimientos. Desactívela en su lugar.'
    })
    return
  }
  Swal.fire({
    title: '¿Eliminar cuenta?',
    text: `¿Eliminar la cuenta "${cuenta.nombre}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('cuentas-bancarias.destroy', { cuentas_bancaria: cuenta.id }))
    }
  })
}
</script>




