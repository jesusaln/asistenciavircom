<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import KitsHeader from '@/Components/IndexComponents/KitsHeader.vue'
import KitsTable from '@/Components/IndexComponents/KitsTable.vue'
import Pagination from '@/Components/Pagination.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

// State
const kits = ref([])
const loading = ref(false)
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  links: []
})
const stats = ref({
  totalKits: 0,
  kitsActivos: 0,
  valorTotal: 0
})

const showModal = ref(false)
const selectedKit = ref(null)
const loadingDetails = ref(false)
const costoActual = ref(null)
const loadingCosto = ref(false)

// Methods
const fetchKits = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page,
      length: 10,
      search: searchQuery.value
    })

    const response = await fetch(`/kits/api/data?${params}`)
    const data = await response.json()

    kits.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      from: data.from,
      to: data.to,
      total: data.recordsFiltered,
      links: data.links || []
    }

    if (data.stats) {
      stats.value.totalKits = data.stats.totalKits
      stats.value.kitsActivos = data.stats.kitsActivos
      stats.value.valorTotal = data.stats.valorTotal
    }
  } catch (error) {
    notyf.error('Error al sincronizar catálogo')
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page) => fetchKits(page)

const handleSearchChange = (val) => {
    searchQuery.value = val
    fetchKits(1)
}

const limpiarFiltros = () => {
  searchQuery.value = ''
  fetchKits(1)
  notyf.success('Filtros liquidados')
}

const viewKitDetails = async (id) => {
  showModal.value = true
  loadingDetails.value = true
  selectedKit.value = null
  costoActual.value = null

  try {
    const response = await fetch(`/kits/${id}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    selectedKit.value = await response.json()
    calcularCostoActual()
  } catch (error) {
    notyf.error('Error al cargar especificaciones')
    showModal.value = false
  } finally {
    loadingDetails.value = false
  }
}

const deleteKit = async (id) => {
  if (!confirm('¿CONFIRMAR BAJA DEL KIT COOPERATIVO?')) return

  try {
    const response = await fetch(`/kits/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })
    const data = await response.json()
    if (data.success) {
      notyf.success('Expediente eliminado')
      fetchKits(pagination.value.current_page)
    } else {
      notyf.error(data.message || 'Error en operación')
    }
  } catch (error) {
    notyf.error('Fallo en la comunicación con el servidor')
  }
}

const calcularCostoActual = async () => {
  if (!selectedKit.value?.kit_items?.length) return
  loadingCosto.value = true
  try {
    const componentes = selectedKit.value.kit_items.map(item => ({
        item_type: item.item_type?.includes('Producto') ? 'producto' : 'servicio',
        item_id: Number(item.item_id),
        cantidad: Number(item.cantidad),
        precio_unitario: item.precio_unitario
    }))
    const response = await fetch('/kits/api/calcular-costo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
      body: JSON.stringify({ componentes: componentes, almacen_id: 1 })
    })
    const data = await response.json()
    if (data.success) costoActual.value = data.costo_total
  } finally { loadingCosto.value = false }
}

const formatCurrency = (value) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0)

const margen = computed(() => {
  if (!selectedKit.value || !costoActual.value) return 0
  const pv = selectedKit.value.precio_venta / 1.16
  return ((pv - costoActual.value) / costoActual.value * 100).toFixed(1)
})

onMounted(() => fetchKits())
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden relative pb-20">
    <Head title="Kits de Productos" />

    <!-- Ambient Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-emerald-600/10 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-10">
        <KitsHeader 
            :totalKits="stats.totalKits"
            :kitsActivos="stats.kitsActivos"
            :valorTotal="stats.valorTotal"
            v-model:searchTerm="searchQuery"
            @search-change="handleSearchChange"
            @limpiar-filtros="limpiarFiltros"
        />

        <KitsTable 
            :items="kits"
            :loading="loading"
            @ver="viewKitDetails"
            @eliminar="deleteKit"
        />

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex justify-center mt-12 mb-10">
            <Pagination 
                :pagination-data="pagination" 
                variant="premium" 
                @page-change="handlePageChange"
            />
        </div>
    </div>

    <!-- Details Modal (Dark Premium) -->
    <Teleport to="body">
        <transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/60 backdrop-blur-sm" @click.self="showModal = false">
            <div class="w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 overflow-hidden max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div v-if="loadingDetails" class="p-20 flex justify-center">
                    <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <div v-else-if="selectedKit">
                    <!-- Modal Header -->
                    <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-600/10 flex items-center justify-center text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Especificación del Kit</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Expediente #{{ selectedKit.id }} | {{ selectedKit.codigo }}</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-10 space-y-10">
                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Denominación del Kit</span>
                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ selectedKit.nombre }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Categorización</span>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">{{ selectedKit.categoria?.nombre || 'General' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Precio de Lista</span>
                                <p class="text-lg font-black text-blue-600 tracking-widest">${{ formatCurrency(selectedKit.precio_venta) }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="selectedKit.descripcion" class="p-6 bg-slate-50 dark:bg-slate-950/50 rounded-3xl border border-slate-100 dark:border-slate-800">
                             <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Resumen Operativo</span>
                             <p class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase leading-relaxed tracking-wide italic">{{ selectedKit.descripcion }}</p>
                        </div>

                        <!-- Components Table -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Estructura de Componentes</h4>
                            <div class="bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800 rounded-[2rem] overflow-hidden">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-100 dark:bg-slate-900/50">
                                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Item</th>
                                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Cant</th>
                                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Unitario</th>
                                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="item in selectedKit.kit_items" :key="item.id" class="hover:bg-white dark:hover:bg-slate-900 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ item.producto?.nombre || item.servicio?.nombre }}</span>
                                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ item.producto?.codigo || item.servicio?.codigo }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-xs font-black text-slate-600 dark:text-slate-300 tracking-widest">{{ item.cantidad }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-xs font-black text-slate-600 dark:text-slate-300 tracking-widest">${{ formatCurrency(item.precio_unitario) }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-xs font-black text-blue-600 tracking-widest">${{ formatCurrency(item.precio_unitario * item.cantidad) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Analysis Footer -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-600 p-8 rounded-[2.5rem] shadow-2xl shadow-blue-600/30 text-white relative overflow-hidden">
                             <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                             <div class="relative z-10 flex items-center justify-between col-span-1 border-r border-white/20 pr-8">
                                 <div>
                                     <span class="text-[9px] font-black uppercase tracking-widest opacity-60">Costo Operativo Real</span>
                                     <div v-if="loadingCosto" class="w-8 h-8 border-2 border-white border-t-transparent rounded-full animate-spin mt-2"></div>
                                     <div v-else class="text-2xl font-black tracking-widest mt-1">${{ formatCurrency(costoActual) }}</div>
                                 </div>
                                 <svg class="w-8 h-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                             </div>
                             <div class="relative z-10 flex items-center justify-between col-span-1 pl-4">
                                 <div>
                                     <span class="text-[9px] font-black uppercase tracking-widest opacity-60">Margen Comercial Bruto</span>
                                     <div v-if="loadingCosto" class="h-8 mt-2"></div>
                                     <div v-else class="text-2xl font-black tracking-widest mt-1 text-emerald-300">{{ margen }}%</div>
                                 </div>
                                 <div class="px-3 py-1 bg-white/20 rounded-full text-[8px] font-black uppercase tracking-widest backdrop-blur-md">Indicador de Rentabilidad</div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </transition>
    </Teleport>
  </div>
</template>

<style>
.animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
@keyframes pulse-slow { 0%, 100% { opacity: 0.1; transform: scale(1); } 50% { opacity: 0.15; transform: scale(1.1); } }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }
</style>
