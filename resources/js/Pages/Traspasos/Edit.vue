<template>
  <Head title="Modificar Manifiesto" />

  <div class="min-h-screen bg-[#0f172a] p-4 md:p-8 flex items-center justify-center">
    <div class="w-full max-w-4xl space-y-8 animate-in fade-in zoom-in duration-500">
      
      <!-- Header -->
      <div class="flex items-center justify-between px-2">
        <div class="space-y-1">
          <h1 class="text-3xl font-bold text-white flex items-center gap-3">
            <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </span>
            Editar Traspaso #{{ traspaso.id }}
          </h1>
          <p class="text-slate-400 text-sm">Ajuste de metadatos y referencias logísticas</p>
        </div>
        <Link :href="route('traspasos.index')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-400 rounded-2xl transition-all border border-slate-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna Informativa (Read-only) -->
        <div class="space-y-6">
          <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 space-y-4">
            <h2 class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest px-1">Logística de Tránsito</h2>
            
            <div class="flex items-center gap-4 p-4 bg-slate-800/40 rounded-2xl border border-slate-800">
              <div class="w-10 h-10 bg-red-500/10 text-red-400 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase">Origen</p>
                <p class="text-white font-semibold">{{ traspaso.almacen_origen?.nombre }}</p>
              </div>
            </div>

            <div class="flex items-center gap-4 p-4 bg-slate-800/40 rounded-2xl border border-slate-800">
              <div class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase">Destino</p>
                <p class="text-white font-semibold">{{ traspaso.almacen_destino?.nombre }}</p>
              </div>
            </div>
          </div>

          <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-6">
            <h2 class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest px-1 mb-4 text-center">Manifiesto de Carga</h2>
            <div class="space-y-2 max-h-[300px] overflow-y-auto px-2 custom-scrollbar">
              <div v-for="(prod, idx) in traspaso.productos" :key="idx"
                   class="flex items-center justify-between p-4 bg-slate-800/20 rounded-2xl border border-slate-800/50">
                <span class="text-slate-300 text-sm truncate max-w-[200px]">{{ prod.nombre }}</span>
                <span class="text-indigo-400 font-bold text-xs bg-indigo-500/10 px-3 py-1 rounded-lg">x{{ prod.cantidad }}</span>
              </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-800 flex justify-between items-center text-sm">
               <span class="text-slate-500">Unidades Totales</span>
               <span class="text-white font-bold">{{ calcularTotal() }}</span>
            </div>
          </div>
        </div>

        <!-- Columna de Edición -->
        <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] p-8 shadow-2xl flex flex-col justify-between">
          <form @submit.prevent="actualizar" class="space-y-6">
            <h3 class="text-sm font-bold text-indigo-400 uppercase tracking-widest mb-2">Datos Modificables</h3>
            
            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-500 uppercase ml-1">Referencia</label>
              <input v-model="form.referencia" type="text" 
                     class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-4 text-white focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-500 uppercase ml-1">Costo de Transporte</label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">$</span>
                <input v-model.number="form.costo_transporte" type="number" step="0.01" 
                       class="w-full bg-slate-800 border border-slate-700 rounded-2xl pl-10 pr-4 py-4 text-white focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-bold">
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-500 uppercase ml-1">Observaciones Internas</label>
              <textarea v-model="form.observaciones" rows="4" 
                        class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-4 text-white focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none resize-none"></textarea>
            </div>

            <div class="pt-6 border-t border-slate-800 flex flex-col gap-3">
               <button type="submit" :disabled="form.processing"
                       class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-2xl shadow-indigo-600/40 transition-all active:scale-95 disabled:opacity-50">
                 {{ form.processing ? 'Guardando cambios...' : 'Confirmar Edición' }}
               </button>
               <Link :href="route('traspasos.index')" class="w-full py-4 bg-slate-800 text-slate-400 font-semibold rounded-2xl text-center hover:bg-slate-700 transition-colors border border-slate-700">
                 Descartar Cambios
               </Link>
            </div>
          </form>

          <p class="text-[10px] text-slate-600 text-center mt-6">
            Nota: Solo se permiten cambios en campos informativos. El movimiento de inventario ya ha sido procesado sistemáticamente.
          </p>
        </div>
      </div>
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

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'bottom' },
  types: [
    { type: 'success', background: '#4f46e5', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

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
    onSuccess: () => notyf.success('Traspaso sincronizado correctamente'),
    onError: () => notyf.error('Error al actualizar el manifiesto'),
  })
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(79, 70, 229, 0.1);
  border-radius: 10px;
}
</style>


