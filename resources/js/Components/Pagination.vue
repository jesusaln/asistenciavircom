<!-- /resources/js/Components/UI/Pagination.vue -->
<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  paginationData: {
    type: Object,
    required: true
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
  <div v-if="pagination.lastPage > 1" class="bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
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
              class="border border-gray-300 rounded-md text-sm py-1 px-2 bg-white"
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
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors cursor-pointer"
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
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
            >
              ...
            </span>
            <button
              v-else-if="page !== pagination.currentPage"
              @click="emit('page-change', page)"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
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
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors cursor-pointer"
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
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 cursor-pointer"
        >
          Anterior
        </button>
        <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
          Anterior
        </span>

        <button
          v-if="pagination.nextPageUrl"
          @click="emit('page-change', pagination.currentPage + 1)"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 cursor-pointer"
        >
          Siguiente
        </button>
        <span v-else class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
          Siguiente
        </span>
      </div>
    </div>
  </div>
</template>

