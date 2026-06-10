<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import HerramientasHeader from '@/Components/IndexComponents/HerramientasHeader.vue'
import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramientas: { type: Object, required: true },
  estadisticas: { type: Object, default: () => ({}) },
  categorias: { type: Array, default: () => [] },
  tecnicos: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  can_manage_all: { type: Boolean, default: false },
})

const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')
const categoria = ref(props.filters?.categoria || '')
const mantenimiento = ref(props.filters?.mantenimiento || '')

const items = computed(() => props.herramientas?.data || [])
const paginationData = computed(() => ({
  from: props.herramientas?.from || 0,
  to: props.herramientas?.to || 0,
  total: props.herramientas?.total || 0,
  currentPage: props.herramientas?.current_page || 1,
  lastPage: props.herramientas?.last_page || 1,
  prevPageUrl: props.herramientas?.prev_page_url,
  nextPageUrl: props.herramientas?.next_page_url,
  perPage: props.herramientas?.per_page || 15,
}))

// Selección masiva
const selectedIds = ref([])
const showReassignModal = ref(false)
const selectedTecnicoId = ref('')

const toggleSelectAll = (e) => {
  if (e.target.checked) {
    selectedIds.value = items.value.map(i => i.id)
  } else {
    selectedIds.value = []
  }
}

const toggleSelection = (id) => {
  const index = selectedIds.value.indexOf(id)
  if (index === -1) {
    selectedIds.value.push(id)
  } else {
    selectedIds.value.splice(index, 1)
  }
}

const openReassignModal = () => {
  if (selectedIds.value.length === 0) return
  showReassignModal.value = true
}

const submitReassignment = () => {
  if (!selectedTecnicoId.value) return
  
  router.post('/herramientas/bulk-reassign', {
    ids: selectedIds.value,
    tecnico_id: selectedTecnicoId.value
  }, {
    onSuccess: () => {
      showReassignModal.value = false
      selectedIds.value = []
      selectedTecnicoId.value = ''
    }
  })
}

// Modal para ver herramienta
const showModal = ref(false)
const selected = ref(null)
const openModal = (h) => { selected.value = h; showModal.value = true }
const closeModal = () => { showModal.value = false; selected.value = null }

const doFilter = () => {
  router.get('/herramientas', {
    search: search.value,
    estado: estado.value,
    categoria: categoria.value,
    mantenimiento: mantenimiento.value,
  }, { preserveState: true, preserveScroll: true })
}

const clearFilters = () => {
  search.value = ''
  estado.value = ''
  categoria.value = ''
  mantenimiento.value = ''
  doFilter()
}

const handlePageChange = (page) => {
  router.get(route('herramientas.index'), {
    page,
    search: search.value,
    estado: estado.value,
    categoria: categoria.value,
    mantenimiento: mantenimiento.value,
  }, { preserveState: true, preserveScroll: true })
}

const getEstadoClase = (estado) => {
  const classes = {
    'disponible': 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'asignada': 'bg-blue-50 text-sky-800 border-sky-200',
    'mantenimiento': 'bg-brand-50 text-brand-800 border-amber-200',
    'baja': 'bg-rose-50 text-rose-800 border-rose-200',
    'perdida': 'bg-rose-50 text-rose-800 border-rose-200',
  }
  return classes[estado] || 'bg-slate-100 text-slate-700 border-slate-200'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark')
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
})
</script>

<template>
  <Head title="Herramientas" />

  <div class="herramientas-index min-h-screen bg-[var(--ui-surface)] transition-colors duration-200 pb-24">
    <div class="w-full px-4 lg:px-8 py-8">
      
      <!-- Header con Estadísticas y Filtros Integrados -->
      <HerramientasHeader
        :total="estadisticas.total"
        :disponibles="estadisticas.disponibles"
        :asignadas="estadisticas.asignadas"
        :mantenimientoCount="estadisticas.mantenimiento"
        :baja="estadisticas.baja"
        :perdida="estadisticas.perdida"
        :requieren_mantenimiento="estadisticas.requieren_mantenimiento"
        :categorias="categorias"
        v-model:search="search"
        v-model:estado="estado"
        v-model:categoria="categoria"
        v-model:mantenimiento="mantenimiento"
        @filter-change="doFilter"
        @search-change="doFilter"
        @limpiar-filtros="clearFilters"
      />

      <!-- Tabla de Herramientas -->
      <div class="mt-8 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden transition-all duration-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left">
                  <input 
                    type="checkbox" 
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-5 h-5 cursor-pointer"
                    :checked="selectedIds.length === items.length && items.length > 0"
                    @change="toggleSelectAll"
                  />
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Herramienta</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Identificación</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Asignación</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="h in items" :key="h.id" class="hover:bg-slate-50/30 dark:hover:bg-blue-900/10 transition-colors duration-150 group">
                <td class="px-6 py-4">
                  <input 
                    type="checkbox" 
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-5 h-5 cursor-pointer"
                    :checked="selectedIds.includes(h.id)"
                    @change="toggleSelection(h.id)"
                  />
                </td>
                <td class="px-6 py-4" @click="toggleSelection(h.id)">
                  <div class="flex items-center gap-3 cursor-pointer">
                    <img v-if="h.foto" :src="`/storage/${h.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl shadow-sm" />
                    <div v-else class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center text-slate-400">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div>
                      <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ h.nombre }}</div>
                      <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">{{ h.categoria_herramienta?.nombre || 'General' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-xs font-mono font-semibold text-slate-500">Serie: {{ h.numero_serie || '—' }}</div>
                  <div class="text-[10px] text-slate-400 mt-1">ID: {{ h.codigo_barras || h.id }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="[getEstadoClase(h.estado), 'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border']">
                    {{ h.estado }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div v-if="h.tecnico" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-[10px] border border-blue-200">
                      {{ h.tecnico.nombre.charAt(0) }}
                    </div>
                    <span class="text-sm text-slate-700 dark:text-slate-200">{{ h.tecnico.nombre }}</span>
                  </div>
                  <div v-else class="text-xs text-slate-400 italic">Disponible</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button @click="openModal(h)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                    <Link v-if="can_manage_all" :href="`/herramientas/${h.id}/edit`" class="p-2 text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-slate-50 dark:bg-slate-800/30 px-6 py-4 border-t border-slate-100 dark:border-slate-700">
           <div class="flex justify-between items-center text-sm text-slate-500">
              <span>Página {{ paginationData.currentPage }} de {{ paginationData.lastPage }}</span>
              <div class="flex gap-2">
                 <button v-if="paginationData.prevPageUrl" @click="handlePageChange(paginationData.currentPage - 1)" class="px-3 py-1 bg-white border rounded-lg shadow-sm">Ant.</button>
                 <button v-if="paginationData.nextPageUrl" @click="handlePageChange(paginationData.currentPage + 1)" class="px-3 py-1 bg-white border rounded-lg shadow-sm">Sig.</button>
              </div>
           </div>
        </div>
      </div>
    </div>

    <!-- Barra de Acciones Masivas FIJA ABAJO -->
    <Transition name="slide-up">
      <div v-if="selectedIds.length > 0" class="fixed bottom-0 left-0 right-0 z-40 p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-[0_-4px_15px_rgba(0,0,0,0.1)] transition-all">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600">
              <span class="text-sm font-black">{{ selectedIds.length }}</span>
            </div>
            <div>
              <p class="text-sm font-bold text-slate-900 dark:text-white">Herramientas seleccionadas</p>
              <p class="text-xs text-slate-500">Haz clic en el botón para reasignar el lote</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button 
              @click="selectedIds = []"
              class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors"
            >
              Deseleccionar
            </button>
            <button 
              @click="openReassignModal"
              class="px-8 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 dark:shadow-none active:scale-95 flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              Traspasar Responsabilidad
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>

  <!-- Modal de Reasignación Masiva -->
  <Modal :show="showReassignModal" @close="showReassignModal = false" max-width="lg">
    <div class="p-6">
      <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Reasignar Herramientas Seleccionadas</h3>
      <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">Se enviará una solicitud de traspaso al técnico seleccionado para las {{ selectedIds.length }} herramientas.</p>
      
      <div class="mb-6">
        <label class="block text-xs font-black text-slate-400 uppercase tracking-wide mb-2">Seleccionar Técnico Receptor</label>
        <select 
          v-model="selectedTecnicoId" 
          class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm font-bold focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">Seleccione un técnico...</option>
          <option v-for="t in tecnicos" :key="t.id" :value="t.id">{{ t.nombre }}</option>
        </select>
      </div>

      <div class="flex justify-end gap-3">
        <SecondaryButton @click="showReassignModal = false">Cancelar</SecondaryButton>
        <PrimaryButton :disabled="!selectedTecnicoId" @click="submitReassignment">Enviar Solicitud</PrimaryButton>
      </div>
    </div>
  </Modal>

  <!-- Modal Detalle (Existente) -->
  <Transition name="modal">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeModal">
      <div class="bg-white dark:bg-slate-800 w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden border border-slate-100 dark:border-slate-700 transition-colors">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-black/50">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">Detalle de Herramienta</h2>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <div class="p-6 grid md:grid-cols-2 gap-6">
          <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Nombre</label>
            <div class="text-base font-bold text-slate-900 dark:text-white">{{ selected?.nombre }}</div>
            <div class="mt-4">
               <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Técnico Actual</label>
               <div class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ selected?.tecnico?.nombre || 'Disponible' }}</div>
            </div>
          </div>
          <div v-if="selected?.foto">
             <img :src="`/storage/${selected.foto}`" class="rounded-xl w-full aspect-square object-cover" />
          </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 dark:bg-black/20 flex justify-end">
          <SecondaryButton @click="closeModal">Cerrar</SecondaryButton>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); opacity: 0; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
