<!-- /resources/js/Components/UI/Pagination.vue -->
<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  paginationData: {
    type: Object,
    required: false,
    default: () => ({})
  },
  links: {
    type: Array,
    required: false,
    default: () => []
  },
  variant: {
    type: String,
    default: 'default' // 'default' | 'premium'
  }
})

const emit = defineEmits(['page-change', 'per-page-change'])

// Computed para extraer datos de paginación
const pagination = computed(() => {
  // Si se pasaron links pero no paginationData, intentar extraer información básica
  if (props.links && props.links.length > 0 && Object.keys(props.paginationData).length === 0) {
    const activeLink = props.links.find(l => l.active);
    
    return {
      currentPage: activeLink ? parseInt(activeLink.label) || 1 : 1,
      lastPage: Math.max(...props.links.map(l => parseInt(l.label)).filter(n => !isNaN(n))) || 1,
      perPage: 15,
      from: 0,
      to: 0,
      total: 0,
      prevPageUrl: props.links.find(l => l.label.includes('Anterior'))?.url,
      nextPageUrl: props.links.find(l => l.label.includes('Siguiente'))?.url,
      links: props.links
    }
  }

  const data = props.paginationData
  return {
    currentPage: data.current_page || 1,
    lastPage: data.last_page || 1,
    perPage: data.per_page || 15,
    from: data.from || 0,
    to: data.to || 0,
    total: data.total || 0,
    prevPageUrl: data.prev_page_url,
    nextPageUrl: data.next_page_url,
    links: data.links || []
  }
})

// Páginas visibles alrededor de la actual
const visiblePages = computed(() => {
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else {
    rangeWithDots.push(last)
  }

  return rangeWithDots.filter((v, i, arr) => arr.indexOf(v) === i && v !== 1 || i === 0)
})

const perPageOptions = [10, 15, 25, 50]

const handlePerPageChange = (newPerPage) => {
  emit('per-page-change', newPerPage)
}
</script>

<template>
  <div v-if="pagination.lastPage > 1">
    <!-- Premium Variant -->
    <div v-if="variant === 'premium'" class="mt-10 mb-6 px-4 animate-fade-in-up">
      <div class="flex flex-col lg:flex-row items-center justify-between gap-6 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-6 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-900/5">
        
        <!-- Info & Per Page -->
        <div class="flex items-center gap-8">
            <div class="flex flex-col">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-tight">Mostrando Segmento</span>
                <span class="text-xs font-black text-slate-700 dark:text-slate-200 tracking-tight">
                    {{ pagination.from }} - {{ pagination.to }} <span class="text-slate-400 font-bold mx-1">DE</span> {{ pagination.total }}
                </span>
            </div>
            <div class="h-8 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
            <div class="flex items-center gap-3">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Registros:</span>
                <select
                    :value="pagination.perPage"
                    @change="handlePerPageChange(parseInt($event.target.value))"
                    class="bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-[10px] font-black py-1.5 px-3 focus:ring-2 focus:ring-blue-500/20 text-slate-600 dark:text-slate-400 appearance-none cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
                >
                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                </select>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <nav class="flex items-center gap-2" aria-label="Pagination">
          <!-- Botón Anterior -->
          <button
            v-if="pagination.prevPageUrl"
            @click="emit('page-change', pagination.currentPage - 1)"
            class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/50 dark:border-slate-700/50 text-slate-400 hover:text-blue-600 hover:border-blue-600/30 hover:scale-110 active:scale-95 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
          </button>
          <div v-else class="w-10 h-10 flex items-center justify-center rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800/50 text-slate-300 dark:text-slate-700 opacity-50 cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
          </div>

          <!-- Numbers -->
          <div class="flex items-center gap-1.5 px-2">
            <template v-for="(page, index) in visiblePages" :key="index">
              <span v-if="page === '...'" class="text-[9px] font-black text-slate-400 tracking-widest px-2">...</span>
              <button
                v-else
                @click="emit('page-change', page)"
                class="min-w-[40px] h-10 flex items-center justify-center rounded-2xl text-[10px] font-black tracking-widest transition-all active:scale-95 px-3"
                :class="page === pagination.currentPage 
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' 
                    : 'text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
              >
                {{ page }}
              </button>
            </template>
          </div>

          <!-- Botón Siguiente -->
          <button
            v-if="pagination.nextPageUrl"
            @click="emit('page-change', pagination.currentPage + 1)"
            class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/50 dark:border-slate-700/50 text-slate-400 hover:text-blue-600 hover:border-blue-600/30 hover:scale-110 active:scale-95 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
          </button>
          <div v-else class="w-10 h-10 flex items-center justify-center rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800/50 text-slate-300 dark:text-slate-700 opacity-50 cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
          </div>
        </nav>
      </div>
    </div>

    <!-- Default Variant (Preserved Layout) -->
    <div v-else class="bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 px-4 py-3 sm:px-6">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Info de registros -->
        <div class="flex-1 flex justify-between sm:hidden">
          <p class="text-sm text-gray-700">
            Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }} resultados
          </p>
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <p class="text-sm text-gray-700">
              Mostrando
              <span class="font-medium">{{ pagination.from }}</span>
              a
              <span class="font-medium">{{ pagination.to }}</span>
              de
              <span class="font-medium">{{ pagination.total }}</span>
              resultados
            </p>

            <!-- Selector de elementos por página -->
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-700">Mostrar:</label>
              <select
                :value="pagination.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 rounded-md text-sm py-1 px-2 bg-white dark:bg-slate-900"
              >
                <option v-for="option in perPageOptions" :key="option" :value="option">
                  {{ option }}
                </option>
              </select>
            </div>
          </div>

          <!-- Navegación de páginas -->
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <!-- Botón Anterior -->
            <button
              v-if="pagination.prevPageUrl"
              @click="emit('page-change', pagination.currentPage - 1)"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white dark:bg-slate-900 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 transition-colors cursor-pointer"
            >
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span class="sr-only">Anterior</span>
            </button>

            <span
              v-else
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
            >
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </span>

            <!-- Números de página -->
            <template v-for="(page, index) in visiblePages" :key="index">
              <span
                v-if="page === '...'"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white dark:bg-slate-900 text-sm font-medium text-gray-700"
              >
                ...
              </span>
              <button
                v-else-if="page !== pagination.currentPage"
                @click="emit('page-change', page)"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white dark:bg-slate-900 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                {{ page }}
              </button>
              <span
                v-else
                class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600"
              >
                {{ page }}
              </span>
            </template>

            <!-- Botón Siguiente -->
            <button
              v-if="pagination.nextPageUrl"
              @click="emit('page-change', pagination.currentPage + 1)"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white dark:bg-slate-900 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 transition-colors cursor-pointer"
            >
              <span class="sr-only">Siguiente</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>

            <span
              v-else
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
            >
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </span>
          </nav>
        </div>

        <!-- Navegación móvil simplificada -->
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            v-if="pagination.prevPageUrl"
            @click="emit('page-change', pagination.currentPage - 1)"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white dark:bg-slate-900 hover:bg-gray-50 cursor-pointer"
          >
            Anterior
          </button>
          <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
            Anterior
          </span>

          <button
            v-if="pagination.nextPageUrl"
            @click="emit('page-change', pagination.currentPage + 1)"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white dark:bg-slate-900 hover:bg-gray-50 cursor-pointer"
          >
            Siguiente
          </button>
          <span v-else class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
            Siguiente
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

