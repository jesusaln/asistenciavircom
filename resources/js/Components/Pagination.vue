<!-- /resources/js/Components/Pagination.vue -->
<script setup>
import { computed } from 'vue'

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
  }
})

const emit = defineEmits(['page-change', 'per-page-change'])

// Computed para extraer datos de paginación
const pagination = computed(() => {
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

// Páginas visibles
const visiblePages = computed(() => {
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage
  const delta = 1
  const pages = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    pages.push(i)
  }

  const result = [1]
  if (current - delta > 2) result.push('...')
  result.push(...pages)
  if (current + delta < last - 1) result.push('...')
  if (last > 1) result.push(last)

  return result.filter((v, i, a) => a.indexOf(v) === i)
})

const handlePerPageChange = (event) => {
  emit('per-page-change', parseInt(event.target.value))
}
</script>

<template>
  <div v-if="pagination.lastPage > 1" class="mt-8 flex flex-col md:flex-row items-center justify-between gap-6 px-2">
    <!-- Información de resultados -->
    <div class="flex flex-col md:flex-row items-center gap-4">
      <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">
        Mostrando <span class="text-slate-900 dark:text-white">{{ pagination.from }} - {{ pagination.to }}</span> de <span class="text-slate-900 dark:text-white">{{ pagination.total }}</span> registros
      </p>

      <div class="flex items-center gap-2">
        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Ver:</span>
        <select
          :value="pagination.perPage"
          @change="handlePerPageChange"
          class="bg-white dark:bg-slate-950 border-2 border-slate-100 dark:border-slate-800 rounded-xl text-[10px] font-black py-1 px-3 text-slate-900 dark:text-white focus:ring-0 focus:border-slate-900 dark:focus:border-slate-700 transition-all"
        >
          <option :value="10">10</option>
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <!-- Navegación -->
    <div class="flex items-center gap-2">
      <!-- Anterior -->
      <button
        @click="pagination.prevPageUrl ? emit('page-change', pagination.currentPage - 1) : null"
        :disabled="!pagination.prevPageUrl"
        class="w-10 h-10 rounded-xl flex items-center justify-center border-2 border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-500 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- Números -->
      <div class="flex items-center gap-1.5">
        <template v-for="(page, index) in visiblePages" :key="index">
          <span
            v-if="page === '...'"
            class="w-10 h-10 flex items-center justify-center text-[10px] font-black text-slate-400"
          >
            ...
          </span>
          <button
            v-else
            @click="emit('page-change', page)"
            class="w-10 h-10 rounded-xl flex items-center justify-center text-[10px] font-black uppercase tracking-wide transition-all shadow-sm"
            :class="page === pagination.currentPage 
              ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl' 
              : 'bg-white dark:bg-slate-950 text-slate-500 dark:text-slate-400 border-2 border-slate-100 dark:border-slate-800 hover:border-slate-900 dark:hover:border-slate-700 hover:text-slate-900 dark:hover:text-white'"
          >
            {{ page }}
          </button>
        </template>
      </div>

      <!-- Siguiente -->
      <button
        @click="pagination.nextPageUrl ? emit('page-change', pagination.currentPage + 1) : null"
        :disabled="!pagination.nextPageUrl"
        class="w-10 h-10 rounded-xl flex items-center justify-center border-2 border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-500 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </div>
</template>
