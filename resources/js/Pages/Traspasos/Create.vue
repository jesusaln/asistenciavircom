<template>
  <Head title="Realizar Traspaso" />

  <div class="min-h-screen bg-[#0f172a] p-4 md:p-8">
    <div class="max-w-5xl mx-auto space-y-8">
      
      <!-- Header de Operación -->
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <h1 class="text-3xl font-bold text-white flex items-center gap-3">
            <span class="p-2 bg-indigo-500/10 rounded-xl">
              <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
            </span>
            Nuevo Traspaso
          </h1>
          <p class="text-slate-400">Configuración de movimiento de inventario entre dependencias</p>
        </div>
        <button @click="cancel" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-400 rounded-2xl transition-all border border-slate-700">
           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Columna Izquierda: Configuración General -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 space-y-6">
            <h2 class="text-sm font-bold text-indigo-400 uppercase tracking-widest">Ruta de Logística</h2>
            
            <!-- Origen -->
            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-500 uppercase ml-1">Origen</label>
              <select v-model="form.almacen_origen_id" 
                      class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all">
                <option value="">Seleccionar salida...</option>
                <option v-for="alc in almacenes" :key="alc.id" :value="alc.id">{{ alc.nombre }}</option>
              </select>
            </div>

            <!-- Destino -->
            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-500 uppercase ml-1">Destino</label>
              <select v-model="form.almacen_destino_id" :disabled="!form.almacen_origen_id"
                      class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <option value="">Seleccionar entrada...</option>
                <option v-for="alc in almacenesDestino" :key="alc.id" :value="alc.id">{{ alc.nombre }}</option>
              </select>
            </div>

            <div class="pt-4 border-t border-slate-800">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400">Items en cola</span>
                <span class="text-indigo-400 font-bold">{{ form.items.length }}</span>
              </div>
              <div class="flex items-center justify-between text-sm mt-2">
                <span class="text-slate-400">Unidades totales</span>
                <span class="text-white font-bold">{{ totalUnidades }}</span>
              </div>
            </div>
          </div>

          <!-- Detalles Adicionales -->
          <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 space-y-4">
             <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-500 uppercase">Referencia de Envío</label>
                <input v-model="form.referencia" type="text" placeholder="Ej: GUIA-778"
                       class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none">
             </div>
             <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-500 uppercase">Costo de Traslado</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">$</span>
                  <input v-model.number="form.costo_transporte" type="number" 
                         class="w-full bg-slate-800 border border-slate-700 rounded-2xl pl-8 pr-4 py-3 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none">
                </div>
             </div>
          </div>
        </div>

        <!-- Columna Derecha: Selección de Productos -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800 rounded-3xl overflow-hidden p-8 shadow-2xl">
            <div class="flex flex-col md:flex-row md:items-end gap-4">
              <!-- Buscador Dinámico -->
              <div class="flex-1 relative group">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Agregar Producto</label>
                <div class="relative">
                  <input 
                    type="text" 
                    v-model="searchQuery" 
                    @input="debouncedSearch"
                    :disabled="!form.almacen_origen_id"
                    placeholder="Escribe código o nombre..."
                    class="w-full bg-slate-800/50 border border-slate-700 rounded-2xl px-12 py-4 text-white focus:ring-4 focus:ring-indigo-500/10 placeholder-slate-600 transition-all outline-none"
                  >
                  <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                    <svg v-if="!searching" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <svg v-else class="w-5 h-5 animate-spin text-indigo-400" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                  </div>
                </div>

                <!-- Resultados del Buscador -->
                <div v-if="searchResults.length && searchQuery" class="absolute z-50 w-full mt-2 bg-[#1e293b] border border-slate-700 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                  <div v-for="prod in searchResults" :key="prod.id" 
                       @click="selectProducto(prod)"
                       class="px-6 py-4 hover:bg-indigo-500/10 flex items-center justify-between cursor-pointer border-b border-slate-800/50 last:border-0 transition-colors">
                    <div class="flex flex-col">
                      <span class="text-white font-semibold text-sm">{{ prod.nombre }}</span>
                      <span class="text-slate-500 text-xs">{{ prod.codigo }}</span>
                    </div>
                    <div class="flex flex-col items-end">
                      <span class="text-indigo-400 font-bold text-sm">{{ prod.stock }} disponibles</span>
                      <span v-if="prod.requiere_serie" class="text-[10px] text-amber-500 uppercase font-bold">Serializado</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Selector de Cantidad -->
              <div class="w-full md:w-32" v-if="productoSeleccionado && !requiresSeries">
                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Cant.</label>
                <input v-model.number="tempItem.cantidad" type="number" min="1" :max="productoSeleccionado.stock"
                       class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-4 text-white text-center font-bold focus:ring-2 focus:ring-indigo-500 outline-none">
              </div>

              <button v-if="productoSeleccionado && !requiresSeries" @click="pushItem"
                      class="h-[60px] px-8 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-lg transition-all active:scale-95">
                Añadir
              </button>
            </div>

            <!-- Panel de Series (Solo si aplica) -->
            <div v-if="productoSeleccionado && requiresSeries" class="mt-8 space-y-4 animate-in slide-in-from-bottom-4 duration-500">
               <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                 <h3 class="text-sm font-bold text-white uppercase tracking-widest flex items-center gap-2">
                   <span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
                   Selección de Series (Máx {{ productoSeleccionado.stock }})
                 </h3>
                 <span class="text-xs text-amber-500 font-bold">{{ selectedSeries.length }} Seleccionadas</span>
               </div>

               <div v-if="loadingSeries" class="flex justify-center py-10">
                  <svg class="w-8 h-8 animate-spin text-indigo-400" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
               </div>
               
               <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-[250px] overflow-y-auto px-1">
                 <label v-for="serie in availableSeries" :key="serie.id" 
                        class="relative group cursor-pointer">
                   <input type="checkbox" v-model="selectedSeries" :value="serie" class="hidden peer">
                   <div class="p-3 bg-slate-800/50 border border-slate-700 rounded-xl text-xs text-slate-400 font-mono text-center transition-all peer-checked:bg-amber-500/10 peer-checked:border-amber-500 peer-checked:text-amber-400 hover:border-slate-500">
                     {{ serie.numero_serie }}
                   </div>
                 </label>
               </div>

               <button @click="pushItem" :disabled="!selectedSeries.length"
                       class="w-full py-4 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-2xl shadow-xl transition-all disabled:opacity-30">
                 Registrar {{ selectedSeries.length }} Series
               </button>
            </div>

            <!-- Listado de cola de traspaso -->
            <div class="mt-12 space-y-4">
              <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Cola de Salida</h3>
              <div v-if="!form.items.length" class="flex flex-col items-center justify-center py-20 bg-slate-800/20 border-2 border-dashed border-slate-800 rounded-[2rem]">
                <svg class="w-12 h-12 text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <p class="text-slate-500 text-sm">No hay productos seleccionados para el manifiesto</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="(item, idx) in form.items" :key="idx" 
                     class="flex items-center justify-between p-6 bg-slate-800/30 border border-slate-800 rounded-3xl hover:border-slate-700 transition-all">
                  <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-500/10 rounded-2xl text-indigo-400">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                      <p class="text-white font-bold">{{ item.producto_nombre }}</p>
                      <p class="text-xs text-slate-500">
                        {{ item.cantidad }} Piezas {{ item.requiere_serie ? '• ' + item.series.length + ' Series' : '' }}
                      </p>
                    </div>
                  </div>
                  <button @click="removeItem(idx)" class="p-3 text-slate-500 hover:text-red-400 transition-colors">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-indigo-600/10 border border-indigo-500/20 p-8 rounded-[2.5rem] flex items-center justify-between">
            <div class="space-y-1">
               <h4 class="text-white font-bold opacity-80 uppercase text-[10px] tracking-widest">Resumen Final</h4>
               <p class="text-slate-400 text-sm">Verifica la integridad de los datos antes de confirmar el movimiento.</p>
            </div>
            <button @click="submit" :disabled="loading || !form.items.length || !form.almacen_destino_id"
                    class="px-10 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-2xl shadow-indigo-600/50 transition-all active:scale-95 disabled:opacity-30 flex items-center gap-3">
               <svg v-if="loading" class="w-5 h-5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
               {{ loading ? 'Sincronizando...' : 'Confirmar Traspaso' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import debounce from 'lodash/debounce'

defineOptions({ layout: AppLayout })

const props = defineProps({
  almacenes: { type: Array, default: () => [] }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'bottom' },
  types: [
    { type: 'success', background: '#4f46e5', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

// Form Structure
const form = ref({
  almacen_origen_id: '',
  almacen_destino_id: '',
  items: [],
  referencia: '',
  costo_transporte: 0,
  observaciones: ''
})

// UI State
const loading = ref(false)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
const productoSeleccionado = ref(null)
const tempItem = ref({ cantidad: 1 })
const selectedSeries = ref([])
const availableSeries = ref([])
const loadingSeries = ref(false)

// Computed
const almacenesDestino = computed(() => {
  return props.almacenes.filter(a => a.id != form.value.almacen_origen_id)
})
const totalUnidades = computed(() => form.value.items.reduce((s, i) => s + i.cantidad, 0))
const requiresSeries = computed(() => productoSeleccionado.value?.requiere_serie)

// Search Logic
const debouncedSearch = debounce(async () => {
  if (!searchQuery.value || searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  searching.value = true
  try {
    const res = await fetch(`/api/productos?search=${encodeURIComponent(searchQuery.value)}&almacen_id=${form.value.almacen_origen_id}&estado=activo`)
    const data = await res.json()
    if (data.success) {
      // Filtrar productos que tengan stock positivo en ese almacen
      searchResults.value = data.data.filter(p => {
         const inv = p.stock_por_almacen?.find(i => i.almacen_id == form.value.almacen_origen_id)
         p.stock = inv ? inv.cantidad : 0
         return p.stock > 0
      })
    }
  } catch (e) {
    console.error(e)
  } finally {
    searching.value = false
  }
}, 400)

const selectProducto = (prod) => {
  productoSeleccionado.value = prod
  searchQuery.value = prod.nombre
  searchResults.value = []
  tempItem.value.cantidad = 1
  selectedSeries.value = []
  
  if (prod.requiere_serie) {
    fetchSeries(prod.id)
  }
}

const fetchSeries = async (productoId) => {
  loadingSeries.value = true
  try {
    const res = await fetch(`/api/productos/${productoId}/series?almacen_id=${form.value.almacen_origen_id}`)
    const data = await res.json()
    availableSeries.value = data.series || []
  } catch (e) {
    notyf.error("Error al cargar series")
  } finally {
    loadingSeries.value = false
  }
}

const pushItem = () => {
  if (!productoSeleccionado.value) return

  if (requiresSeries.value && !selectedSeries.value.length) {
    notyf.error("Debes seleccionar al menos una serie")
    return
  }

  if (!requiresSeries.value && tempItem.value.cantidad > productoSeleccionado.value.stock) {
    notyf.error("Stock insuficiente")
    return
  }

  form.value.items.push({
    producto_id: productoSeleccionado.value.id,
    producto_nombre: productoSeleccionado.value.nombre,
    cantidad: requiresSeries.value ? selectedSeries.value.length : tempItem.value.cantidad,
    series: selectedSeries.value.map(s => s.id),
    requiere_serie: requiresSeries.value
  })

  // Reset
  productoSeleccionado.value = null
  searchQuery.value = ''
  selectedSeries.value = []
  notyf.success("Producto enlazado al manifiesto")
}

const removeItem = (idx) => form.value.items.splice(idx, 1)

const submit = () => {
  loading.value = true
  router.post(route('traspasos.store'), form.value, {
    onSuccess: () => notyf.success("Traspaso ejecutado con éxito"),
    onError: (e) => notyf.error(e.error || "Fallo en la sincronización"),
    onFinish: () => loading.value = false
  })
}

const cancel = () => router.visit(route('traspasos.index'))
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(79, 70, 229, 0.2);
  border-radius: 10px;
}
</style>


