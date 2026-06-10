<template>
  <div class="min-h-screen bg-[var(--ui-surface)] transition-colors duration-200">
    <Head title="Cuentas Bancarias" />

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center tracking-tight">
            <FontAwesomeIcon :icon="['fas', 'landmark']" class="h-7 w-7 sm:h-8 sm:w-8 text-blue-600 dark:text-blue-400 mr-3 shrink-0" />
            Cuentas bancarias
          </h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">Gestiona tus cuentas bancarias y saldos</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
          <Link
            :href="route('traspasos-bancarios.index')"
            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 transition-colors shadow-md shadow-indigo-600/20"
          >
            <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="mr-2" />
            Ver traspasos
          </Link>
          <Link
            :href="route('cuentas-bancarias.create')"
            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 dark:bg-brand-500 dark:hover:bg-blue-400 transition-colors shadow-md shadow-blue-600/20"
          >
            <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
            Nueva cuenta
          </Link>
        </div>
      </div>

      <!-- Flash Messages -->
      <div
        v-if="$page.props.flash?.success"
        class="mb-6 p-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200"
      >
        {{ $page.props.flash.success }}
      </div>
      <div
        v-if="$page.props.flash?.error"
        class="mb-6 p-4 rounded-xl border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 dark:text-rose-200"
      >
        {{ $page.props.flash.error }}
      </div>

      <!-- Total General -->
      <div
        class="relative rounded-[1.75rem] shadow-2xl mb-8 overflow-hidden border border-white/10 text-white"
        :style="totalCardStyle"
      >
        <div class="absolute inset-0 bg-gradient-to-tr from-white/10 via-transparent to-white/5 pointer-events-none" />
        <div class="relative p-6 sm:p-8 flex items-center justify-between gap-4">
          <div>
            <p class="text-white/80 text-xs font-semibold uppercase tracking-[0.2em]">Saldo total en cuentas</p>
            <p class="text-3xl sm:text-4xl font-black mt-2 tabular-nums tracking-tight">${{ formatMonto(totales.saldo_total) }}</p>
            <p class="text-white/70 text-sm mt-3">{{ totales.cuentas_activas }} cuenta(s) activa(s)</p>
          </div>
          <FontAwesomeIcon :icon="['fas', 'wallet']" class="h-14 w-14 sm:h-16 sm:w-16 text-white/30 shrink-0" />
        </div>
      </div>

      <!-- Grid de Cuentas -->
      <div v-if="cuentas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="cuenta in cuentas"
          :key="cuenta.id"
          class="rounded-2xl border overflow-hidden transition-shadow hover:shadow-xl bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
          :class="{ 'opacity-70': !cuenta.activa }"
        >
          <div class="h-2" :style="{ backgroundColor: cuenta.color }" />

          <div class="p-5">
            <div class="flex items-start justify-between mb-4 gap-2">
              <div class="min-w-0">
                <h3 class="font-bold text-slate-900 dark:text-white truncate">{{ cuenta.nombre }}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ cuenta.banco }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ cuenta.numero_cuenta_mascarado }}</p>
              </div>
              <span
                v-if="!cuenta.activa"
                class="shrink-0 px-2 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs rounded-full font-medium"
              >
                Inactiva
              </span>
            </div>

            <div class="rounded-xl p-4 mb-4 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/80">
              <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide">Saldo actual</p>
              <p
                class="text-2xl font-bold tabular-nums"
                :class="cuenta.saldo_actual >= 0 ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'"
              >
                ${{ formatMonto(cuenta.saldo_actual) }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Saldo inicial: ${{ formatMonto(cuenta.saldo_inicial) }}
              </p>
            </div>

            <div class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400 mb-4">
              <span class="capitalize">{{ cuenta.tipo }}</span>
              <span>{{ cuenta.movimientos_count }} movimientos</span>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 pt-4 border-t border-slate-300 dark:border-slate-600">
              <Link
                :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })"
                class="p-2 text-emerald-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-emerald-950/40 rounded-xl transition-colors"
                title="Ver cuenta y movimientos"
              >
                <FontAwesomeIcon :icon="['fas', 'list']" />
              </Link>
              <Link
                :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })"
                class="px-3 py-2 bg-emerald-600 dark:bg-brand-500 text-white text-sm rounded-xl hover:bg-emerald-700 dark:hover:bg-emerald-400 transition-colors font-medium"
                title="Ver registros"
              >
                Ver registros
              </Link>
              <button
                type="button"
                class="p-2 text-blue-600 dark:text-blue-400 hover:bg-slate-50 dark:hover:bg-blue-950/40 rounded-xl transition-colors"
                title="Ver detalle rápido"
                @click="verDetalle(cuenta)"
              >
                <FontAwesomeIcon :icon="['fas', 'eye']" />
              </button>
              <Link
                :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuenta.id })"
                class="p-2 text-slate-500 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-colors"
                title="Editar"
              >
                <FontAwesomeIcon :icon="['fas', 'edit']" />
              </Link>
              <button
                v-if="cuenta.movimientos_count === 0"
                type="button"
                class="p-2 text-rose-600 dark:text-rose-400 hover:bg-slate-50 dark:hover:bg-rose-950/40 rounded-xl transition-colors"
                title="Eliminar"
                @click="eliminar(cuenta)"
              >
                <FontAwesomeIcon :icon="['fas', 'trash']" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado vacío -->
      <div
        v-else
        class="rounded-2xl border p-12 text-center bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
      >
        <FontAwesomeIcon :icon="['fas', 'piggy-bank']" class="h-16 w-16 text-slate-300 dark:text-slate-500 mb-4 mx-auto" />
        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">No hay cuentas bancarias</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6">Agrega una cuenta bancaria para comenzar a gestionar tus saldos</p>
        <Link
          :href="route('cuentas-bancarias.create')"
          class="inline-flex px-6 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700 dark:bg-brand-500 dark:hover:bg-blue-400 font-semibold shadow-md"
        >
          Agregar cuenta
        </Link>
      </div>
    </div>

    <!-- Modal de Detalle -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
      @click.self="showModal = false"
    >
      <div
        class="rounded-2xl shadow-xl w-full max-w-lg overflow-hidden border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 max-h-[90vh] overflow-y-auto custom-scrollbar"
      >
        <div class="h-2" :style="{ backgroundColor: cuentaSeleccionada?.color }" />
        <div class="p-6 border-b border-slate-300 dark:border-slate-600">
          <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
              <h3 class="text-xl font-bold text-slate-900 dark:text-white truncate">{{ cuentaSeleccionada?.nombre }}</h3>
              <p class="text-slate-500 dark:text-slate-400">{{ cuentaSeleccionada?.banco }}</p>
            </div>
            <button
              type="button"
              class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 shrink-0"
              @click="showModal = false"
            >
              <FontAwesomeIcon :icon="['fas', 'times']" class="text-slate-400 dark:text-slate-500" />
            </button>
          </div>
        </div>

        <div class="p-6 space-y-6">
          <div
            class="rounded-xl p-4 text-white border border-white/10 overflow-hidden relative"
            :style="modalSaldoStyle"
          >
            <div class="absolute inset-0 bg-gradient-to-tr from-white/10 via-transparent to-transparent pointer-events-none" />
            <div class="relative">
              <p class="text-white/80 text-sm">Saldo actual</p>
              <p class="text-2xl font-black tabular-nums">${{ formatMonto(cuentaSeleccionada?.saldo_actual) }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl p-3 bg-[var(--ui-surface)] dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700">
              <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Número de cuenta</p>
              <p class="font-medium text-slate-900 dark:text-slate-100 text-sm mt-1 break-all">
                {{ cuentaSeleccionada?.numero_cuenta || 'No especificado' }}
              </p>
            </div>
            <div class="rounded-xl p-3 bg-[var(--ui-surface)] dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700">
              <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Tipo</p>
              <p class="font-medium text-slate-900 dark:text-slate-100 capitalize mt-1">{{ cuentaSeleccionada?.tipo }}</p>
            </div>
            <div class="rounded-xl p-3 bg-[var(--ui-surface)] dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700">
              <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Saldo inicial</p>
              <p class="font-medium text-slate-900 dark:text-slate-100 mt-1 tabular-nums">
                ${{ formatMonto(cuentaSeleccionada?.saldo_inicial) }}
              </p>
            </div>
            <div class="rounded-xl p-3 bg-[var(--ui-surface)] dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700">
              <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Movimientos</p>
              <p class="font-medium text-slate-900 dark:text-slate-100 mt-1">{{ cuentaSeleccionada?.movimientos_count }}</p>
            </div>
          </div>

          <div v-if="cuentaSeleccionada?.clabe" class="rounded-xl p-3 bg-[var(--ui-surface)] dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">CLABE interbancaria</p>
            <p class="font-medium text-slate-900 dark:text-slate-100 font-mono text-sm mt-1">{{ cuentaSeleccionada?.clabe }}</p>
          </div>

          <div v-if="cuentaSeleccionada?.notas" class="rounded-xl p-3 bg-[var(--ui-surface)] dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Notas</p>
            <p class="text-slate-700 dark:text-slate-200 text-sm mt-1 whitespace-pre-wrap">{{ cuentaSeleccionada?.notas }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-3 p-4 border-t border-slate-300 dark:border-slate-600 bg-slate-50/80 dark:bg-slate-800/50">
          <button
            type="button"
            class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700"
            @click="showModal = false"
          >
            Cerrar
          </button>
          <Link
            :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuentaSeleccionada?.id })"
            class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 dark:bg-brand-500 dark:hover:bg-blue-400 font-semibold"
          >
            Editar
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Swal from '@/Utils/Swal'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuentas: { type: Array, default: () => [] },
  totales: { type: Object, default: () => ({ saldo_total: 0, cuentas_activas: 0 }) },
})

const page = usePage()
const showModal = ref(false)
const cuentaSeleccionada = ref(null)
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

const totalCardStyle = computed(() => {
  const colors = page.props.empresa_config || {}
  const principal = colors.color_principal || '#2563eb'
  const secundario = colors.color_secundario || '#1d4ed8'
  if (isDark.value) {
    return { background: 'linear-gradient(135deg, #0f172a 0%, #020617 55%, #0c1222 100%)' }
  }
  return { background: `linear-gradient(120deg, ${principal} 0%, ${secundario} 100%)` }
})

const modalSaldoStyle = computed(() => {
  const colors = page.props.empresa_config || {}
  const principal = colors.color_principal || '#2563eb'
  const secundario = colors.color_secundario || '#1d4ed8'
  if (isDark.value) {
    return { background: 'linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%)' }
  }
  return { background: `linear-gradient(120deg, ${principal} 0%, ${secundario} 100%)` }
})

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
      text: 'No se puede eliminar una cuenta con movimientos. Desactívela en su lugar.',
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
    cancelButtonText: 'Cancelar',
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('cuentas-bancarias.destroy', { cuentas_bancaria: cuenta.id }))
    }
  })
}
</script>
