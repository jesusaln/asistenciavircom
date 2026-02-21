<!-- /resources/js/Pages/Traspasos/Index.vue -->
<template>
  <Head title="Traspasos de Inventario" />

  <div class="min-h-screen bg-[#0f172a] p-4 md:p-8">
    <div class="max-w-[1600px] mx-auto space-y-8">
      
      <!-- Nuevo Header Premium -->
      <TraspasosHeader 
        :stats="stats" 
        :filters="filters" 
        :almacenes="almacenes"
      />

      <!-- Tabla de Traspasos con Estilo Premium -->
      <div class="relative overflow-hidden bg-slate-900/40 backdrop-blur-xl border border-slate-800 rounded-3xl shadow-2xl transition-all duration-500 hover:shadow-indigo-500/5">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-800/50 bg-slate-900/50">
                <th class="px-6 py-5 text-sm font-semibold text-slate-300">Folio & Fecha</th>
                <th class="px-6 py-5 text-sm font-semibold text-slate-300">Ruta de Movimiento</th>
                <th class="px-6 py-5 text-sm font-semibold text-slate-300">Productos & Volumen</th>
                <th class="px-6 py-5 text-sm font-semibold text-slate-300 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/30">
              <tr v-for="traspaso in traspasos.data" :key="traspaso.id" 
                  class="group hover:bg-slate-800/30 transition-all duration-300">
                
                <!-- Folio & Fecha -->
                <td class="px-6 py-5">
                  <div class="flex flex-col">
                    <span class="text-indigo-400 font-bold tracking-wider text-sm">#TR-{{ String(traspaso.id).padStart(5, '0') }}</span>
                    <span class="text-slate-500 text-xs mt-1 flex items-center gap-1">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ formatDate(traspaso.created_at) }}
                    </span>
                  </div>
                </td>

                <!-- Ruta de Movimiento -->
                <td class="px-6 py-5">
                  <div class="flex items-center gap-4">
                    <div class="flex flex-col items-center">
                      <div class="px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-full text-[10px] font-bold text-red-400 uppercase tracking-tighter mb-1">Origen</div>
                      <span class="text-white font-medium text-sm">{{ traspaso.almacen_origen?.nombre || 'N/A' }}</span>
                    </div>
                    
                    <div class="flex flex-col items-center justify-center px-2">
                       <svg class="w-5 h-5 text-slate-600 group-hover:text-indigo-400 animate-pulse transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                       </svg>
                    </div>

                    <div class="flex flex-col items-center">
                      <div class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-[10px] font-bold text-emerald-400 uppercase tracking-tighter mb-1">Destino</div>
                      <span class="text-white font-medium text-sm">{{ traspaso.almacen_destino?.nombre || 'N/A' }}</span>
                    </div>
                  </div>
                </td>

                <!-- Productos & Volumen -->
                <td class="px-6 py-5">
                  <div class="flex items-center gap-3">
                    <div class="flex -space-x-2">
                       <div v-for="i in Math.min(traspaso.productos_count, 3)" :key="i" 
                            class="w-8 h-8 rounded-lg bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-[10px] font-bold text-slate-400 shadow-xl">
                         {{ traspaso.productos?.[i-1]?.nombre?.charAt(0) || 'P' }}
                       </div>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-slate-200 text-sm font-semibold">
                        {{ traspaso.productos_count }} {{ traspaso.productos_count === 1 ? 'Producto' : 'Productos' }}
                      </span>
                      <span class="text-xs text-indigo-400/80 font-medium">
                        Total: {{ traspaso.cantidad_total || traspaso.cantidad }} unidades
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Acciones -->
                <td class="px-6 py-5 text-right">
                  <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <button @click="verTraspaso(traspaso)" 
                            class="p-2.5 bg-slate-800/50 hover:bg-slate-700 text-slate-300 rounded-xl border border-slate-700 transition-all hover:scale-110"
                            title="Ver detalles">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <!-- Link para edición deshabilitado de momento o disponible si es admin -->
                    <Link :href="route('traspasos.edit', traspaso.id)" 
                          class="p-2.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 rounded-xl border border-indigo-500/20 transition-all hover:scale-110"
                          title="Editar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </Link>
                    <button @click="confirmarEliminar(traspaso)" 
                            class="p-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl border border-red-500/20 transition-all hover:scale-110"
                            title="Eliminar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación de Vidrio -->
        <div class="px-6 py-5 border-t border-slate-800/50 flex items-center justify-between">
           <p class="text-sm text-slate-500">
            Mostrando <span class="text-white font-medium">{{ pagination.from }}</span> a 
            <span class="text-white font-medium">{{ pagination.to }}</span> de 
            <span class="text-white font-medium">{{ pagination.total }}</span> resultados
          </p>
          <div class="flex gap-2">
            <Link v-if="traspasos.prev_page_url" :href="traspasos.prev_page_url" 
                  class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-slate-700 transition-all">
              Anterior
            </Link>
            <Link v-if="traspasos.next_page_url" :href="traspasos.next_page_url" 
                  class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all">
              Siguiente
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ver Detalles (Premium) -->
    <div v-if="mostrarModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click.self="cerrarModal">
      <div class="bg-[#1e293b] border border-slate-700 w-full max-w-3xl rounded-3xl overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
        <div class="p-8 space-y-8">
           <div class="flex items-center justify-between">
             <div class="space-y-1">
               <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                 <span class="text-indigo-400">#TR-{{ String(traspasoSeleccionado?.id).padStart(5, '0') }}</span>
                 Detalles del Movimiento
               </h2>
               <p class="text-slate-400 text-sm">Registrado el {{ formatDate(traspasoSeleccionado?.created_at) }}</p>
             </div>
             <button @click="cerrarModal" class="p-2 hover:bg-slate-800 rounded-xl text-slate-400 transition-colors">
               <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
             </button>
           </div>

           <div class="grid grid-cols-2 gap-6">
              <div class="space-y-2">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Origen</p>
                <div class="p-4 bg-slate-800/50 rounded-2xl border border-slate-700 text-white font-semibold">
                  {{ traspasoSeleccionado?.almacen_origen?.nombre }}
                </div>
              </div>
              <div class="space-y-2">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Destino</p>
                <div class="p-4 bg-indigo-500/10 rounded-2xl border border-indigo-500/20 text-indigo-400 font-semibold">
                  {{ traspasoSeleccionado?.almacen_destino?.nombre }}
                </div>
              </div>
           </div>

           <div class="space-y-4">
              <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Desglose de Productos</p>
              <div class="bg-slate-800/30 rounded-2xl border border-slate-700 divide-y divide-slate-700/50 max-h-[300px] overflow-y-auto custom-scrollbar">
                <div v-for="(item, idx) in (traspasoSeleccionado?.productos || [])" :key="idx" 
                     class="px-6 py-4 flex items-center justify-between hover:bg-slate-800/50 transition-colors">
                  <span class="text-slate-200 font-medium">{{ item.nombre }}</span>
                  <div class="flex flex-col items-end">
                    <span class="text-indigo-400 font-bold">{{ item.cantidad }}</span>
                    <span class="text-[10px] text-slate-500 uppercase">Piezas</span>
                  </div>
                </div>
              </div>
           </div>

           <div class="grid grid-cols-2 gap-6">
              <div class="space-y-2">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Referencia</p>
                <p class="text-slate-300 text-sm italic">{{ traspasoSeleccionado?.referencia || '-- Sin referencia --' }}</p>
              </div>
              <div class="space-y-2">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Costo Transporte</p>
                <p class="text-emerald-400 font-bold">${{ (traspasoSeleccionado?.costo_transporte || 0).toLocaleString() }}</p>
              </div>
           </div>

           <div class="space-y-2">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Observaciones Internas</p>
                <div class="p-4 bg-slate-900/50 rounded-2xl text-slate-400 text-sm leading-relaxed border border-slate-800">
                  {{ traspasoSeleccionado?.observaciones || 'No se registraron comentarios adicionales.' }}
                </div>
           </div>

           <div class="flex justify-end pt-4">
              <button @click="cerrarModal" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-lg shadow-indigo-600/20 transition-all">
                Cerrar Expediente
              </button>
           </div>
        </div>
      </div>
    </div>

    <!-- Modal Confirmación Eliminación (Premium) -->
    <div v-if="mostrarModalEliminar" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md" @click.self="cerrarModalEliminar">
      <div class="bg-[#1e293b] border border-red-500/30 w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl animate-in fade-in scale-in duration-300">
        <div class="p-10 space-y-6 text-center">
          <div class="w-24 h-24 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="space-y-2">
            <h3 class="text-2xl font-bold text-white uppercase tracking-tight">¿Confirmar Reversión?</h3>
            <p class="text-slate-400 text-sm">Esta acción es <span class="text-red-400 font-bold uppercase underline">Irreversible</span>. Se regresará el stock físicamente al origen.</p>
          </div>
          
          <div class="p-4 bg-slate-900/50 border border-slate-800 rounded-3xl space-y-2">
            <p class="text-[10px] text-slate-500 font-bold uppercase underline">Folio a eliminar</p>
            <p class="text-white font-bold text-lg">#TR-{{ String(traspasoAEliminar?.id).padStart(5, '0') }}</p>
          </div>

          <div class="flex flex-col gap-3">
            <button @click="eliminarTraspaso" 
                    class="w-full py-4 bg-red-600 hover:bg-red-500 text-white font-bold rounded-2xl shadow-xl shadow-red-600/20 transition-all active:scale-95">
              Confirmar Eliminación
            </button>
            <button @click="cerrarModalEliminar" 
                    class="w-full py-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-2xl transition-all">
              Abortar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue';
import TraspasosHeader from '@/Components/IndexComponents/TraspasosHeader.vue';

// Configuration de Notyf adaptable al Dark Mode
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'bottom' },
  types: [
    { type: 'success', background: '#4f46e5', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

defineOptions({ layout: AppLayout });

const props = defineProps({
  traspasos: { type: Object, required: true },
  stats: { type: Object, required: true },
  filters: { type: Object, required: true },
  almacenes: { type: Array, default: () => [] }
})

// Paginación extraída de traspasos para claridad
const pagination = ref({
  total: props.traspasos.total,
  from: props.traspasos.from,
  to: props.traspasos.to
})

const mostrarModal = ref(false)
const traspasoSeleccionado = ref(null)
const mostrarModalEliminar = ref(false)
const traspasoAEliminar = ref(null)

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const verTraspaso = (traspaso) => {
  traspasoSeleccionado.value = traspaso
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
  traspasoSeleccionado.value = null
}

const confirmarEliminar = (traspaso) => {
  traspasoAEliminar.value = traspaso
  mostrarModalEliminar.value = true
}

const cerrarModalEliminar = () => {
  mostrarModalEliminar.value = false
  traspasoAEliminar.value = null
}

const eliminarTraspaso = () => {
  if (!traspasoAEliminar.value) return
  router.delete(route('traspasos.destroy', traspasoAEliminar.value.id), {
    preserveScroll: true,
    onSuccess: (page) => {
      cerrarModalEliminar()
      const flash = page?.props?.flash
      if (flash?.error) notyf.error(flash.error)
      else notyf.success('Traspaso revertido y eliminado correctamente')
    },
    onError: () => notyf.error('Error crítico al procesar la reversión')
  })
}

const page = usePage()
onMounted(() => {
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.1);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(79, 70, 229, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(79, 70, 229, 0.4);
}
</style>





