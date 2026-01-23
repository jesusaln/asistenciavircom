<!-- /resources/js/Pages/Traspasos/Show.vue -->
<template>
  <Head title="Detalle de Traspaso" />
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Traspaso #{{ traspaso.id }}</h1>
          <p class="text-sm text-gray-600 dark:text-gray-300">Fecha: {{ formatDate(traspaso.created_at) }}</p>
        </div>
        <Link :href="route('traspasos.index')" class="text-blue-600 hover:underline text-sm">Volver</Link>
      </div>

      <!-- Información de Almacenes -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-xs text-red-600 uppercase font-semibold mb-1">Almacén Origen</p>
          <p class="text-lg font-medium text-gray-900 dark:text-white">{{ traspaso.almacen_origen?.nombre || 'N/D' }}</p>
        </div>
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
          <p class="text-xs text-green-600 uppercase font-semibold mb-1">Almacén Destino</p>
          <p class="text-lg font-medium text-gray-900 dark:text-white">{{ traspaso.almacen_destino?.nombre || 'N/D' }}</p>
        </div>
      </div>

      <!-- Lista de Productos Traspasados -->
      <div class="border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden">
        <div class="bg-gray-100 px-4 py-3 border-b flex justify-between items-center">
          <p class="text-sm font-semibold text-gray-700">
            Productos Traspasados ({{ traspaso.productos_count || traspaso.productos?.length || 1 }})
          </p>
          <span class="text-sm text-blue-600 font-bold">
            Total: {{ traspaso.cantidad_total || calcularTotal() }} unidades
          </span>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-slate-800">
          <div
            v-if="traspaso.productos && traspaso.productos.length"
            v-for="(prod, idx) in traspaso.productos"
            :key="idx"
            class="px-4 py-3 flex justify-between items-center hover:bg-white dark:bg-slate-900"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
              </div>
              <span class="text-sm text-gray-900 dark:text-white">{{ prod.nombre }}</span>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
              {{ prod.cantidad }} unid.
            </span>
          </div>
          <div v-else class="px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
              </div>
              <span class="text-sm text-gray-900 dark:text-white">{{ traspaso.producto?.nombre || 'N/D' }}</span>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
              {{ traspaso.cantidad_total || traspaso.cantidad }} unid.
            </span>
          </div>
        </div>
      </div>

      <!-- Información Adicional -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-white dark:bg-slate-900 rounded-lg">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Referencia</p>
          <p class="text-sm text-gray-800 dark:text-gray-100">{{ traspaso.referencia || 'Sin referencia' }}</p>
        </div>
        <div class="p-4 bg-white dark:bg-slate-900 rounded-lg">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Costo de Transporte</p>
          <p class="text-sm text-gray-800 dark:text-gray-100">${{ traspaso.costo_transporte ?? 0 }}</p>
        </div>
      </div>

      <div class="p-4 bg-white dark:bg-slate-900 rounded-lg">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Observaciones</p>
        <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ traspaso.observaciones || 'Sin observaciones' }}</p>
      </div>

      <!-- Estado -->
      <div class="flex items-center justify-between pt-4 border-t">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
          :class="{
            'bg-green-100 text-green-800': traspaso.estado === 'completado',
            'bg-yellow-100 text-yellow-800': traspaso.estado === 'pendiente',
            'bg-blue-100 text-blue-800': traspaso.estado === 'en_transito',
            'bg-red-100 text-red-800': traspaso.estado === 'cancelado',
          }"
        >
          {{ traspaso.estado || 'completado' }}
        </span>
        <Link :href="route('traspasos.index')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Volver al listado
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  traspaso: { type: Object, required: true },
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const calcularTotal = () => {
  if (props.traspaso.productos && props.traspaso.productos.length) {
    return props.traspaso.productos.reduce((sum, p) => sum + (p.cantidad || 0), 0)
  }
  return props.traspaso.cantidad || 0
}
</script>

