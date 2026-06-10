<template>
  <div class="min-h-screen bg-[var(--ui-surface)] text-slate-900 dark:text-white transition-colors duration-200">
    <Head :title="`Traspaso #${traspaso.id}`" />

    <div class="w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center tracking-tight">
            <FontAwesomeIcon :icon="['fas', 'exchange-alt']" class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mr-3 shrink-0" />
            Detalle de Traspaso
          </h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">Folio o ID de registro #{{ traspaso.id }}</p>
        </div>
        <Link
          :href="route('traspasos-bancarios.index')"
          class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700 font-medium self-start sm:self-auto"
        >
          <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="mr-2" />
          Volver
        </Link>
      </div>

      <!-- Main Card -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xl p-6 sm:p-10 space-y-6">
        
        <!-- Monto Grande -->
        <div class="text-center py-6 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800/80">
          <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-semibold">Monto Transferido</span>
          <p class="text-4xl sm:text-5xl font-black text-indigo-600 dark:text-indigo-400 mt-2 tabular-nums">${{ formatMonto(traspaso.monto) }}</p>
          <span class="inline-flex mt-4 px-3 py-1 rounded-xl text-xs font-bold border" :class="getEstadoClass(traspaso.estado)">
            {{ getEstadoLabel(traspaso.estado) }}
          </span>
        </div>

        <!-- Flujo de Cuentas -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 p-6 border border-slate-100 dark:border-slate-800 rounded-2xl">
          <!-- Origen -->
          <div class="flex-1 w-full text-center md:text-left bg-rose-50 dark:bg-rose-900/20/40 dark:bg-rose-950/10 p-4 rounded-xl border border-rose-100 dark:border-rose-950/20">
            <span class="text-xs text-rose-500 font-bold uppercase tracking-wider">Origen</span>
            <p class="text-base font-bold text-slate-900 dark:text-white mt-1">{{ traspaso.cuenta_origen?.nombre }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ traspaso.cuenta_origen?.banco }}</p>
          </div>

          <FontAwesomeIcon :icon="['fas', 'long-arrow-alt-right']" class="hidden md:block h-6 w-6 text-slate-400" />
          <FontAwesomeIcon :icon="['fas', 'long-arrow-alt-down']" class="md:hidden h-6 w-6 text-slate-400" />

          <!-- Destino -->
          <div class="flex-1 w-full text-center md:text-right bg-emerald-50 dark:bg-emerald-900/20/40 dark:bg-emerald-950/10 p-4 rounded-xl border border-emerald-100 dark:border-emerald-950/20">
            <span class="text-xs text-emerald-500 font-bold uppercase tracking-wider">Destino</span>
            <p class="text-base font-bold text-slate-900 dark:text-white mt-1">{{ traspaso.cuenta_destino?.nombre }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ traspaso.cuenta_destino?.banco }}</p>
          </div>
        </div>

        <!-- Detalles Secundarios -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-4 bg-[var(--ui-surface)] dark:bg-slate-800/20 border border-slate-100 dark:border-slate-800/50 rounded-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Fecha de Aplicación</span>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1">{{ formatFecha(traspaso.fecha) }}</p>
          </div>
          <div class="p-4 bg-[var(--ui-surface)] dark:bg-slate-800/20 border border-slate-100 dark:border-slate-800/50 rounded-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Referencia</span>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1 font-mono">{{ traspaso.referencia || 'SIN REFERENCIA' }}</p>
          </div>
          <div class="p-4 bg-[var(--ui-surface)] dark:bg-slate-800/20 border border-slate-100 dark:border-slate-800/50 rounded-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registrado por</span>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1">{{ traspaso.usuario?.name || 'Sistema' }}</p>
          </div>
          <div class="p-4 bg-[var(--ui-surface)] dark:bg-slate-800/20 border border-slate-100 dark:border-slate-800/50 rounded-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Concepto</span>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1">{{ traspaso.motivo || 'Traspaso entre cuentas propias' }}</p>
          </div>
        </div>

        <!-- Notas -->
        <div v-if="traspaso.notas" class="p-4 bg-brand-50 dark:bg-brand-900/20/20 dark:bg-amber-950/10 border border-brand-100 dark:border-amber-950/20 rounded-xl">
          <span class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider block mb-1">Notas</span>
          <p class="text-sm text-slate-700 dark:text-slate-200">{{ traspaso.notas }}</p>
        </div>

        <!-- Botón de Eliminación/Reverso -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
          <button 
            @click="confirmarEliminacion" 
            class="px-5 py-3 bg-rose-600 dark:bg-brand-500 text-white font-bold text-sm rounded-xl hover:bg-rose-700 dark:hover:bg-rose-400 shadow-md shadow-rose-600/20 flex items-center gap-2"
          >
            <FontAwesomeIcon :icon="['fas', 'trash']" />
            Reversar Traspaso
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faExchangeAlt, faArrowLeft, faLongArrowAltRight, faLongArrowAltDown, faTrash } from '@fortawesome/free-solid-svg-icons'
import Swal from 'sweetalert2'

library.add(faExchangeAlt, faArrowLeft, faLongArrowAltRight, faLongArrowAltDown, faTrash)

defineOptions({ layout: AppLayout })

const props = defineProps({
  traspaso: { type: Object, required: true }
})

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
    'completado': 'bg-emerald-100/80 border-emerald-300 text-emerald-800 dark:text-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800/50 dark:text-slate-400',
    'pendiente': 'bg-brand-100/80 border-brand-300 text-brand-800 dark:text-brand-200 dark:bg-amber-950/30 dark:border-brand-800/50 dark:text-amber-400',
    'cancelado': 'bg-rose-100/80 border-rose-300 text-rose-800 dark:text-rose-200 dark:bg-rose-950/30 dark:border-rose-800/50 dark:text-rose-400',
  }
  return clases[estado] || 'bg-slate-100 text-slate-800'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'completado': 'Aplicado Correctamente',
    'pendiente': 'Pendiente',
    'cancelado': 'Reversado / Cancelado',
  }
  return labels[estado] || estado
}

const confirmarEliminacion = () => {
    Swal.fire({
        title: '¿Reversar Traspaso?',
        text: "Se cancelarán los movimientos en ambas cuentas y se restaurarán los saldos. Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, reversar movimiento',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('traspasos-bancarios.destroy', props.traspaso.id))
        }
    })
}
</script>
