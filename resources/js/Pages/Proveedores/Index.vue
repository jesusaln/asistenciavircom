<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import ProveedoresHeader from '@/Components/IndexComponents/ProveedoresHeader.vue'
import ProveedoresTable from '@/Components/IndexComponents/ProveedoresTable.vue'

defineOptions({ layout: AppLayout })

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

const props = defineProps({
  proveedores: { type: Object, required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
})

const showModal = ref(false)
const modalMode = ref('details')
const selectedProveedor = ref(null)
const selectedId = ref(null)

const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref(props.sorting?.sort_by ? `${props.sorting.sort_by}-${props.sorting.sort_direction}` : 'created_at-desc')
const filtroEstado = ref(props.filters?.activo ?? '')
const filtroTipoPersona = ref(props.filters?.tipo_persona ?? '')
const filtroEstadoMexico = ref(props.filters?.estado ?? '')
const perPage = ref(props.pagination?.per_page || 10)

const estadisticas = computed(() => ({
  total: props.stats?.total || 0,
  activos: props.stats?.activos || 0,
  inactivos: props.stats?.inactivos || 0,
  personas_fisicas: props.stats?.personas_fisicas || 0,
  personas_morales: props.stats?.personas_morales || 0,
  con_email: props.stats?.con_email || 0,
}))

const proveedoresData = computed(() => props.proveedores?.data || [])

function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  updateFilters()
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  updateFilters()
}

function handleTipoPersonaChange(newTipo) {
  filtroTipoPersona.value = newTipo
  updateFilters()
}

function handleEstadoMexicoChange(newEstado) {
  filtroEstadoMexico.value = newEstado
  updateFilters()
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  updateFilters()
}

function handlePerPageChange(newPerPage) {
  perPage.value = newPerPage
  updateFilters()
}

function updateFilters() {
  router.get(route('proveedores.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    tipo_persona: filtroTipoPersona.value,
    estado: filtroEstadoMexico.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const crearNuevoProveedor = () => router.visit(route('proveedores.create'))
const editarProveedor = (id) => router.visit(route('proveedores.edit', id))

const verDetalles = (doc) => {
  selectedProveedor.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarProveedor = () => {
  router.delete(route('proveedores.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Proveedor eliminado correctamente')
      showModal.value = false
      selectedId.value = null
    }
  })
}

const toggleProveedor = (id) => {
  router.put(route('proveedores.toggle', id), {
    preserveScroll: true,
    onSuccess: () => notyf.success('Estado actualizado'),
    onError: () => notyf.error('Error al actualizar')
  })
}

const handlePageChange = (url) => {
  if (url) router.visit(url, { preserveScroll: true, preserveState: true })
}

const filterCount = computed(() => {
    let count = 0;
    if (searchTerm.value) count++;
    if (filtroEstado.value !== '') count++;
    if (filtroTipoPersona.value !== '') count++;
    return count;
});

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  filtroTipoPersona.value = ''
  filtroEstadoMexico.value = ''
  router.visit(route('proveedores.index'))
}

const formatearFecha = (date) => {
  if (!date) return '---'
  const d = new Date(date)
  return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<template>
  <Head title="Proveedores" />
  
  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden relative">
    
    <!-- Ambient Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-indigo-600/10 rounded-full blur-[100px] animate-pulse-slow px-2" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-10 animate-fade-in-up">
      
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 pb-2 border-b border-slate-200/50 dark:border-slate-800/50">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Proveedores</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Directorio Maestro</span>
                <div class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ estadisticas.total }} Registros</span>
            </div>
        </div>
      </div>

      <!-- Stats & Filter Section -->
      <ProveedoresHeader
        v-bind="estadisticas"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-tipo-persona="filtroTipoPersona"
        v-model:filtro-estado-mexico="filtroEstadoMexico"
        @crear-nueva="crearNuevoProveedor"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-tipo-persona-change="handleTipoPersonaChange"
        @filtro-estado-mexico-change="handleEstadoMexicoChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Table Section -->
      <div class="space-y-6">
          <div class="flex items-center justify-between px-4">
              <div class="flex items-center gap-3">
                  <div class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></div>
                  <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Vista de Catálogo</h2>
              </div>
              
              <!-- Mini Pagination / Info -->
              <div class="flex items-center gap-6">
                  <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-900/50 px-4 py-2 rounded-xl border border-slate-200/50 dark:border-slate-800/50">
                      <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Mostrar</span>
                      <select 
                        :value="perPage"
                        @change="handlePerPageChange($event.target.value)"
                        class="bg-transparent border-none text-[10px] font-black text-blue-600 focus:ring-0 p-0 cursor-pointer"
                      >
                          <option :value="10">10</option>
                          <option :value="25">25</option>
                          <option :value="50">50</option>
                      </select>
                  </div>
              </div>
          </div>

          <ProveedoresTable
            :items="proveedoresData"
            :sort-by="sortBy"
            @ver-detalles="verDetalles"
            @editar="editarProveedor"
            @eliminar="confirmarEliminacion"
            @toggle="toggleProveedor"
            @sort="handleSortChange"
          />

          <!-- Main Pagination -->
          <div v-if="proveedores.links && proveedores.links.length > 3" class="flex justify-center pt-8 pb-12">
              <div class="flex items-center gap-2 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-2 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-xl">
                  <template v-for="(link, k) in proveedores.links" :key="k">
                      <div v-if="link.url === null" class="w-10 h-10 flex items-center justify-center text-[10px] font-black text-slate-400 opacity-50 select-none" v-html="link.label"></div>
                      <Link
                          v-else
                          :href="link.url"
                          class="w-10 h-10 flex items-center justify-center text-[10px] font-black rounded-xl transition-all duration-300"
                          :class="link.active 
                              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 scale-110' 
                              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                          preserve-scroll
                          v-html="link.label"
                      />
                  </template>
              </div>
          </div>
      </div>
    </div>

    <!-- Modal Premium -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 lg:p-12">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showModal = false"></div>
                
                <div class="relative bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 w-full max-w-2xl overflow-hidden animate-fade-in-up">
                    <div class="p-10">
                        <div class="flex justify-between items-start mb-10">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400 mb-2">
                                    {{ modalMode === 'details' ? 'Expediente del Proveedor' : 'Acción Requerida' }}
                                </h3>
                                <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter truncate max-w-md">
                                    {{ modalMode === 'details' ? (selectedProveedor?.nombre_razon_social || 'Detalle') : '¿Confirmar eliminación?' }}
                                </p>
                            </div>
                            <button @click="showModal = false" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 transition-all">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Content Details -->
                        <div v-if="modalMode === 'details' && selectedProveedor" class="space-y-8">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">RFC Oficial</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white font-mono uppercase">{{ selectedProveedor.rfc || 'XAX010101000' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Contacto Primario</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedProveedor.telefono || 'Sin datos' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Correo Electrónico</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedProveedor.email || 'Sin datos' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fecha Alta</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ formatearFecha(selectedProveedor.created_at) }}</p>
                                </div>
                            </div>

                            <div class="p-6 bg-slate-50 dark:bg-slate-950/50 rounded-3xl border border-slate-200/50 dark:border-slate-800/50">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Ubicación / Domicilio</span>
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed italic">
                                    {{ selectedProveedor.calle }} {{ selectedProveedor.numero_exterior }}, {{ selectedProveedor.colonia }}, {{ selectedProveedor.municipio }}, {{ selectedProveedor.estado }}
                                </p>
                            </div>
                        </div>

                        <!-- Confirm Delete -->
                        <div v-if="modalMode === 'confirm'" class="bg-rose-50 dark:bg-rose-900/10 p-8 rounded-3xl border border-rose-200 dark:border-rose-900/30">
                            <p class="text-sm font-bold text-rose-700 dark:text-rose-400 leading-relaxed">
                                Estas a punto de eliminar este proveedor del sistema. Esta acción es irreversible y podría afectar documentos vinculados.
                            </p>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-12 flex items-center justify-end gap-3">
                            <button @click="showModal = false" class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all">Cancelar</button>
                            <button v-if="modalMode === 'confirm'" @click="eliminarProveedor" class="px-10 py-4 bg-rose-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-rose-600/20 hover:bg-rose-700 transition-all">Eliminar Permanente</button>
                            <button v-else @click="editarProveedor(selectedProveedor.id)" class="px-10 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all">Editar Perfil</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
  </div>
</template>

<style>
.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.15; transform: scale(1.1); }
}

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #1e293b;
}

::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>


<style scoped>
.proveedores-index {
  min-height: 100vh;
}
</style>




