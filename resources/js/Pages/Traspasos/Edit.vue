<!-- /resources/js/Pages/Traspasos/Edit.vue -->
<template>
  <Head title="Editar Traspaso" />
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-4xl mx-auto bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Editar Traspaso #{{ traspaso.id }}</h1>
          <p class="text-sm text-gray-600">Solo se editan campos informativos; el inventario no cambia.</p>
        </div>
        <Link :href="route('traspasos.index')" class="text-blue-600 hover:underline text-sm">Volver</Link>
      </div>

      <!-- Info de Almacenes (solo lectura) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-xs text-red-600 uppercase font-semibold mb-1">Almacén Origen</p>
          <p class="text-lg font-medium text-gray-900">{{ traspaso.almacen_origen?.nombre || 'N/D' }}</p>
        </div>
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
          <p class="text-xs text-green-600 uppercase font-semibold mb-1">Almacén Destino</p>
          <p class="text-lg font-medium text-gray-900">{{ traspaso.almacen_destino?.nombre || 'N/D' }}</p>
        </div>
      </div>

      <!-- Lista de Productos (solo lectura) -->
      <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-100 px-4 py-3 border-b flex justify-between items-center">
          <p class="text-sm font-semibold text-gray-700">
            Productos Traspasados ({{ traspaso.productos_count || traspaso.productos?.length || 1 }})
          </p>
          <span class="text-sm text-blue-600 font-bold">
            Total: {{ traspaso.cantidad_total || calcularTotal() }} unidades
          </span>
        </div>
        <div class="divide-y divide-gray-200 max-h-64 overflow-y-auto">
          <div
            v-if="traspaso.productos && traspaso.productos.length"
            v-for="(prod, idx) in traspaso.productos"
            :key="idx"
            class="px-4 py-3 flex justify-between items-center"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
              </div>
              <span class="text-sm text-gray-900">{{ prod.nombre }}</span>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
              {{ prod.cantidad }} unid.
            </span>
          </div>
        </div>
      </div>

      <!-- Formulario Editable -->
      <form @submit.prevent="actualizar" class="space-y-4 pt-4 border-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Información Editable</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
            <input 
              v-model="form.referencia" 
              type="text" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
              placeholder="Ej: TRSP-001"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Costo de Transporte</label>
            <input 
              v-model.number="form.costo_transporte" 
              type="number" 
              min="0" 
              step="0.01" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
            />
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <textarea 
            v-model="form.observaciones" 
            rows="3" 
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Notas adicionales..."
          ></textarea>
        </div>
        
        <div class="flex justify-end gap-3 pt-4">
          <Link :href="route('traspasos.index')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancelar
          </Link>
          <button 
            type="submit" 
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50" 
            :disabled="form.processing"
          >
            {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  traspaso: { type: Object, required: true },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const form = useForm({
  referencia: props.traspaso.referencia || '',
  costo_transporte: props.traspaso.costo_transporte ?? 0,
  observaciones: props.traspaso.observaciones || '',
})

const calcularTotal = () => {
  if (props.traspaso.productos && props.traspaso.productos.length) {
    return props.traspaso.productos.reduce((sum, p) => sum + (p.cantidad || 0), 0)
  }
  return props.traspaso.cantidad_total || 0
}

const actualizar = () => {
  form.put(route('traspasos.update', props.traspaso.id), {
    onSuccess: () => notyf.success('Traspaso actualizado'),
    onError: () => notyf.error('No se pudo actualizar el traspaso'),
  })
}
</script>

