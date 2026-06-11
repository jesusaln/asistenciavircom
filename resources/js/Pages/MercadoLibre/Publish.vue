<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { 
  faStore, faSearch, faSpinner, faArrowLeft, faInfoCircle, 
  faCalculator, faCoins, faTags, faArrowRight, faCheckCircle,
  faChevronRight, faExclamationCircle, faChartLine, faBalanceScale,
  faTag, faRocket, faLightbulb
} from '@fortawesome/free-solid-svg-icons'
import { notyf } from '@/Utils/notyf.js'

library.add(
  faStore, faSearch, faSpinner, faArrowLeft, faInfoCircle, 
  faCalculator, faCoins, faTags, faArrowRight, faCheckCircle,
  faChevronRight, faExclamationCircle, faChartLine, faBalanceScale,
  faTag, faRocket, faLightbulb
)

defineOptions({ layout: AppLayout })

const props = defineProps({
  productos: { type: Object, default: () => ({ data: [], links: [] }) },
  meliUser: { type: Object, default: null },
  error: { type: String, default: null },
  filters: { type: Object, default: () => ({ search: '' }) }
})

// Search query
const searchQuery = ref(props.filters.search || '')
const searching = ref(false)

// Publish Modal State
const isModalOpen = ref(false)
const publishing = ref(false)
const selectedProduct = ref(null)

// Price Suggestion State
const searchingPrices = ref(false)
const priceAnalysis = ref(null)
const priceAnalysisError = ref(null)
const autoSearchDone = ref(false)

// Simulation settings
const listingType = ref('gold_special') // gold_special = Clásica, gold_premium = Premium
const sellPrice = ref(0)
const publishStock = ref(1)

// Simulation breakdown calculation
const simulation = computed(() => {
  if (!selectedProduct.value) return null

  const price = parseFloat(sellPrice.value) || 0
  const cost = parseFloat(selectedProduct.value.precio_compra) * 1.16 // Cost with 16% IVA estimate

  // ML Commission percentage (approximate rates: Gold Special 13%, Gold Premium 17.5%)
  const commRate = listingType.value === 'gold_special' ? 0.13 : 0.175
  const commission = price * commRate

  // Fixed fee for cheap products (< $299 MXN)
  const fixedFee = price < 299 && price > 0 ? 25.00 : 0.00

  // Free shipping cost for products >= $299 MXN (obligatory on ML)
  const shippingFee = price >= 299 ? 59.60 : 0.00

  // Standard tax withholding estimate (8% approx for IVA + ISR)
  const taxWithholding = price * 0.08

  // Net payout
  const netReceived = price - commission - fixedFee - shippingFee - taxWithholding
  
  // Real cash profit
  const netProfit = netReceived - cost
  const profitMargin = price > 0 ? (netProfit / price) * 100 : 0

  return {
    price,
    cost,
    commission,
    fixedFee,
    shippingFee,
    taxWithholding,
    netReceived,
    netProfit,
    profitMargin
  }
})

// Search action
const handleSearch = () => {
  searching.value = true
  router.get(
    route('mercadolibre.listings.publicar-view'),
    { search: searchQuery.value },
    {
      preserveState: true,
      replace: true,
      onFinish: () => {
        searching.value = false
      }
    }
  )
}

// Smart calculation for MercadoLibre suggested price
const getMeliSuggestedPrice = (product, type = 'gold_special') => {
  const cost = parseFloat(product.precio_compra) * 1.16 // Cost with 16% IVA estimate
  const targetMargin = 0.15 // Target net profit margin on cost (15%)
  const target = cost * (1 + targetMargin)

  const commRate = type === 'gold_special' ? 0.13 : 0.175
  const taxRate = 0.08
  const R = 1 - commRate - taxRate

  // Estimate low price (< 299) where fixed fee is $25 and shipping is $0
  const pLow = (target + 25) / R
  if (pLow < 299) {
    return Math.round(pLow)
  }

  // Estimate high price (>= 299) where fixed fee is $0 and shipping is $59.60
  const pHigh = (target + 59.60) / R
  
  // If pLow is >= 299 but we can optimize by selling at $289 (below the shipping limit)
  if (289 * R - 25 >= target) {
    return 289
  }

  return Math.round(pHigh)
}

// Watch listingType to update default selling price dynamically
watch(listingType, (newType) => {
  if (selectedProduct.value) {
    sellPrice.value = getMeliSuggestedPrice(selectedProduct.value, newType)
  }
})

// Open publish drawer
const openPublishModal = (product) => {
  selectedProduct.value = product
  sellPrice.value = getMeliSuggestedPrice(product, listingType.value)
  
  // Stock to publish: minimum of 10 or CVA stock
  const localStock = parseInt(product.stock || 0) + parseInt(product.stock_cedis || 0)
  publishStock.value = Math.min(Math.max(1, localStock - 1), 10) // Leave 1 safety margin, capped at 10

  // Auto-search ML for similar products to suggest competitive price
  autoSearchDone.value = false
  priceAnalysis.value = null
  priceAnalysisError.value = null
  
  searchingPrices.value = true
  
  // Search ML using product name
  const searchQuery = product.nombre.substring(0, 60)
  
  router.post(
    route('mercadolibre.analizar-competencia'),
    { meli_item_id: searchQuery }, // Using name as search query instead of ML ID
    {
      preserveState: true,
      onSuccess: (data) => {
        if (data.props.error) {
          priceAnalysisError.value = data.props.error
        } else if (data.props.source?.id) {
          // Got actual ML item data
          priceAnalysis.value = data.props
          autoSearchDone.value = true
          // Update suggested price based on ML competition
          if (data.props.suggestion?.price) {
            sellPrice.value = Math.round(data.props.suggestion.price)
          }
        } else if (data.props.competitors?.count > 0) {
          // Got search results with competitors
          priceAnalysis.value = data.props
          autoSearchDone.value = true
          if (data.props.suggestion?.price) {
            sellPrice.value = Math.round(data.props.suggestion.price)
          }
        } else {
          priceAnalysisError.value = 'No se encontraron productos similares en MercadoLibre'
        }
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0]
        priceAnalysisError.value = firstError
      },
      onFinish: () => {
        searchingPrices.value = false
      }
    }
  )

  isModalOpen.value = true
}

// Close drawer
const closePublishModal = () => {
  isModalOpen.value = false
  selectedProduct.value = null
}

// Submit publish request to backend
const publishProduct = () => {
  if (sellPrice.value <= 0) {
    notyf.error('El precio de venta debe ser mayor a 0.')
    return
  }

  publishing.value = true
  router.post(
    route('mercadolibre.listings.publicar'),
    {
      producto_id: selectedProduct.value.id,
      precio_venta: sellPrice.value,
      listing_type_id: listingType.value,
      stock_published: publishStock.value
    },
    {
      onSuccess: () => {
        notyf.success('¡Producto publicado correctamente en MercadoLibre!')
        closePublishModal()
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0]
        notyf.error(firstError || 'Error al publicar el producto.')
      },
      onFinish: () => {
        publishing.value = false
      }
    }
  )
}
</script>

<template>
  <Head title="Publicar en MercadoLibre" />

  <div class="min-h-screen bg-[var(--ui-surface)] pb-20">
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 py-8">
      
      <!-- Back Header -->
      <div class="mb-6">
        <Link 
          :href="route('mercadolibre.listings.index')"
          class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 font-bold transition-all text-sm"
        >
          <FontAwesomeIcon icon="arrow-left" />
          <span>Volver a Publicaciones</span>
        </Link>
      </div>

      <!-- Main Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8 bg-white/5 dark:bg-slate-800/50 backdrop-blur-md border border-slate-200/50 dark:border-slate-700/50 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-yellow-500/10 rounded-2xl text-yellow-500">
            <FontAwesomeIcon icon="store" size="xl" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Publicar Productos</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Selecciona y publica productos de CVA directamente a MercadoLibre con simulador de márgenes</p>
          </div>
        </div>

        <div v-if="props.meliUser" class="flex items-center gap-2 bg-green-500/10 text-green-500 px-3.5 py-1.5 rounded-xl border border-green-500/20 text-xs font-bold">
          <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse mr-0.5"></span>
          Conectado como: {{ props.meliUser.nickname }}
        </div>
      </div>

      <!-- Warning if not configured -->
      <div v-if="props.error" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 mb-8 flex items-start gap-4">
        <div class="p-2 bg-amber-500/20 rounded-xl text-amber-500 mt-1">
          <FontAwesomeIcon icon="exclamation-circle" />
        </div>
        <div>
          <h4 class="font-bold text-slate-900 dark:text-white mb-1">Integración Incompleta</h4>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ props.error }}</p>
          <Link 
            href="/empresa/configuracion#tienda"
            class="px-4 py-2 bg-amber-500 text-slate-950 font-bold text-xs rounded-xl hover:bg-amber-600 transition-colors inline-block"
          >
            Configurar Credenciales
          </Link>
        </div>
      </div>

      <!-- Search & Catalog -->
      <div v-else class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-2xl overflow-hidden shadow-xl">
        
        <!-- Filters -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <form @submit.prevent="handleSearch" class="relative max-w-md w-full flex items-center gap-2">
            <div class="relative w-full">
              <FontAwesomeIcon icon="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar por nombre, SKU o clave CVA..."
                class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/30 focus:border-yellow-500 transition-all text-slate-800 dark:text-slate-100"
              />
            </div>
            <button
              type="submit"
              :disabled="searching"
              class="px-4 py-2.5 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-slate-950 font-bold rounded-xl transition-all flex items-center gap-2"
            >
              <FontAwesomeIcon icon="spinner" spin v-if="searching" />
              <span v-else>Buscar</span>
            </button>
          </form>

          <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Total de productos disponibles: {{ props.productos.total || 0 }}
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="props.productos.data.length === 0" class="py-20 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-400 mb-4">
            <FontAwesomeIcon icon="store" size="xl" />
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-1">No hay productos disponibles</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
            Todos tus productos de CVA activos ya están en MercadoLibre o no se encontraron coincidencias con tu término de búsqueda.
          </p>
        </div>

        <!-- Catalog Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-900/40 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">Producto</th>
                <th class="px-6 py-4">Categoría/Marca</th>
                <th class="px-6 py-4">Stock CVA</th>
                <th class="px-6 py-4">Costo CVA (+IVA)</th>
                <th class="px-6 py-4">Sugerido (Clásica)</th>
                <th class="px-6 py-4 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr 
                v-for="item in props.productos.data" 
                :key="item.id"
                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
              >
                <!-- Image & Name -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="item.imagen || '/images/placeholder-product.svg'" 
                      alt="Thumbnail" 
                      class="w-10 h-10 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                      @error="(e) => e.target.src = '/images/placeholder-product.svg'"
                    />
                    <div class="max-w-xs md:max-w-md truncate">
                      <span class="font-bold text-slate-900 dark:text-slate-200 block truncate" :title="item.nombre">{{ item.nombre }}</span>
                      <span class="text-xs text-slate-400 block mt-0.5 font-mono">
                        SKU: {{ item.codigo || 'S/N' }} <span class="mx-1 text-slate-600">|</span> CVA Clave: {{ item.cva_clave || 'S/N' }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Category / Brand -->
                <td class="px-6 py-4">
                  <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 block">{{ item.categoria?.nombre || 'General' }}</span>
                  <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold tracking-wider uppercase block mt-0.5">{{ item.marca?.nombre || 'Sin Marca' }}</span>
                </td>

                <!-- Stock -->
                <td class="px-6 py-4">
                  <div class="flex flex-col">
                    <span class="font-bold text-slate-800 dark:text-slate-200">
                      {{ (parseInt(item.stock || 0) + parseInt(item.stock_cedis || 0)) }} uds.
                    </span>
                    <span class="text-[10px] text-slate-400">
                      L: {{ item.stock || 0 }} | CEDIS: {{ item.stock_cedis || 0 }}
                    </span>
                  </div>
                </td>

                <!-- CVA Purchase Cost (with estimated VAT) -->
                <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">
                  ${{ (parseFloat(item.precio_compra) * 1.16).toFixed(2) }} MXN
                </td>

                <!-- Price Suggestion stay below limit -->
                <td class="px-6 py-4">
                  <div class="flex flex-col">
                    <span class="font-bold text-emerald-500">
                      ${{ getMeliSuggestedPrice(item, 'gold_special') }}.00 MXN
                    </span>
                    <span class="text-[9px] text-slate-400 font-bold block mt-0.5">
                      {{ getMeliSuggestedPrice(item, 'gold_special') === 289 ? 'Optimizado < $299' : 'Margen Neto 15%' }}
                    </span>
                  </div>
                </td>

                <!-- Action button -->
                <td class="px-6 py-4 text-right">
                  <button 
                    @click="openPublishModal(item)"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 ml-auto"
                  >
                    <FontAwesomeIcon icon="store" />
                    <span>Publicar</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
            Mostrando del {{ props.productos.from || 0 }} al {{ props.productos.to || 0 }} de {{ props.productos.total || 0 }} productos disponibles
          </div>
          <div class="flex flex-wrap items-center gap-1.5">
            <Link
              v-for="link in props.productos.links"
              :key="link.label"
              :href="link.url || '#'"
              v-html="link.label"
              class="px-3 py-1.5 border rounded-lg text-xs font-bold transition-all"
              :class="[
                link.active 
                  ? 'bg-yellow-500 border-yellow-500 text-slate-950 shadow-md' 
                  : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800',
                !link.url ? 'opacity-30 cursor-not-allowed pointer-events-none' : ''
              ]"
            />
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Publish Drawer / Modal Panel -->
  <div v-if="isModalOpen && selectedProduct" class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" @click="closePublishModal"></div>

    <div class="absolute inset-y-0 right-0 max-w-full pl-10 flex">
      <div class="w-screen max-w-lg bg-[var(--ui-surface)] border-l border-slate-200/50 dark:border-slate-700/50 shadow-2xl flex flex-col h-full transform transition-all duration-300">
        
        <!-- Drawer Header -->
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-white/5">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-yellow-500/10 text-yellow-500 flex items-center justify-center">
              <FontAwesomeIcon icon="calculator" />
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white" id="modal-title">Simular Publicación</h2>
          </div>
          <button @click="closePublishModal" class="text-slate-400 hover:text-white transition-colors text-sm font-bold p-1 bg-white/5 hover:bg-white/10 rounded-lg">
            ✕
          </button>
        </div>

        <!-- Drawer Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          
          <!-- Product Summary -->
          <div class="p-4 bg-white/5 dark:bg-slate-800/20 border border-slate-200/20 dark:border-slate-700/20 rounded-2xl flex gap-3">
            <img 
              :src="selectedProduct.imagen || '/images/placeholder-product.svg'" 
              alt="Preview" 
              class="w-14 h-14 object-cover rounded-xl border border-slate-200 dark:border-slate-700 flex-shrink-0"
              @error="(e) => e.target.src = '/images/placeholder-product.svg'"
            />
            <div class="min-w-0">
              <h4 class="font-bold text-slate-900 dark:text-white text-sm truncate" :title="selectedProduct.nombre">{{ selectedProduct.nombre }}</h4>
              <span class="text-xs text-slate-400 block mt-0.5 font-mono">SKU: {{ selectedProduct.codigo || 'S/N' }}</span>
              <span class="text-xs text-slate-400 block font-bold text-yellow-500/80 mt-1">{{ selectedProduct.categoria?.nombre || 'General' }}</span>
            </div>
          </div>

          <!-- Configuration Fields -->
          <div class="space-y-4">
            
            <!-- Listing Type Select -->
            <div>
              <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider mb-2">Tipo de Publicación</label>
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="listingType = 'gold_special'"
                  class="p-3.5 rounded-xl border text-left flex flex-col justify-between transition-all"
                  :class="[
                    listingType === 'gold_special' 
                      ? 'border-yellow-500 bg-yellow-500/5 text-slate-900 dark:text-white ring-2 ring-yellow-500/30' 
                      : 'border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900/50 hover:bg-white/10'
                  ]"
                >
                  <span class="font-bold text-sm block">Clásica</span>
                  <span class="text-[10px] text-slate-400 block mt-1">Exposición media. Comisión regular (~13%). Sin meses sin intereses.</span>
                </button>
                <button
                  type="button"
                  @click="listingType = 'gold_premium'"
                  class="p-3.5 rounded-xl border text-left flex flex-col justify-between transition-all"
                  :class="[
                    listingType === 'gold_premium' 
                      ? 'border-yellow-500 bg-yellow-500/5 text-slate-900 dark:text-white ring-2 ring-yellow-500/30' 
                      : 'border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900/50 hover:bg-white/10'
                  ]"
                >
                  <span class="font-bold text-sm block">Premium</span>
                  <span class="text-[10px] text-slate-400 block mt-1">Exposición máxima. Comisión más alta (~17.5%). Ofrece meses sin intereses.</span>
                </button>
              </div>
            </div>

            <!-- Price input & Stock slider -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider mb-1.5">Precio de Venta</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-sm">$</span>
                  <input
                    v-model="sellPrice"
                    type="number"
                    step="1"
                    min="1"
                    class="w-full pl-7 pr-12 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-yellow-500/30"
                  />
                  <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-[10px] uppercase">MXN</span>
                </div>
              </div>

              <div>
                <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider mb-1.5">Stock a Publicar</label>
                <input
                  v-model="publishStock"
                  type="number"
                  min="1"
                  :max="(parseInt(selectedProduct.stock || 0) + parseInt(selectedProduct.stock_cedis || 0))"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-yellow-500/30"
                />
              </div>
            </div>

          </div>

          <!-- ML Competition Analysis (Auto-loaded) -->
          <div class="p-4 bg-blue-500/5 border border-blue-500/20 rounded-2xl space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <FontAwesomeIcon icon="balance-scale" class="text-blue-500 text-sm" />
                <span class="text-xs font-bold uppercase text-blue-500 tracking-wider">Análisis de Competencia en ML</span>
              </div>
              <FontAwesomeIcon v-if="searchingPrices" icon="spinner" spin class="text-blue-500 text-sm" />
            </div>

            <!-- Loading State -->
            <div v-if="searchingPrices" class="text-center py-4">
              <FontAwesomeIcon icon="spinner" spin class="text-blue-500 text-xl mb-2" />
              <p class="text-xs text-slate-500">Buscando productos similares en MercadoLibre...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="priceAnalysisError" class="text-center py-3">
              <p class="text-xs text-slate-500 mb-2">{{ priceAnalysisError }}</p>
              <p class="text-[10px] text-slate-400">Usando precio sugerido por margen</p>
            </div>

            <!-- Results State -->
            <div v-else-if="priceAnalysis && priceAnalysis.competitors" class="space-y-3">
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2 bg-white/5 rounded-xl">
                  <span class="text-[10px] text-slate-400 block uppercase">Mín</span>
                  <span class="font-bold text-emerald-400 text-sm">${{ priceAnalysis.competitors.min_price?.toFixed(0) }}</span>
                </div>
                <div class="p-2 bg-white/5 rounded-xl">
                  <span class="text-[10px] text-slate-400 block uppercase">Promedio</span>
                  <span class="font-bold text-blue-400 text-sm">${{ priceAnalysis.competitors.avg_price?.toFixed(0) }}</span>
                </div>
                <div class="p-2 bg-white/5 rounded-xl">
                  <span class="text-[10px] text-slate-400 block uppercase">Máx</span>
                  <span class="font-bold text-rose-400 text-sm">${{ priceAnalysis.competitors.max_price?.toFixed(0) }}</span>
                </div>
              </div>

              <!-- Most Sold Product -->
              <div v-if="priceAnalysis.most_sold" class="p-3 bg-white/5 rounded-xl text-xs">
                <div class="flex items-center gap-2 mb-1.5">
                  <FontAwesomeIcon icon="rocket" class="text-yellow-500 text-[10px]" />
                  <span class="font-bold text-yellow-500 uppercase tracking-wider">Más Vendido</span>
                </div>
                <p class="text-slate-300 font-medium truncate">{{ priceAnalysis.most_sold.title }}</p>
                <div class="flex items-center justify-between mt-1">
                  <span class="text-slate-500">${{ priceAnalysis.most_sold.price?.toFixed(2) }} MXN</span>
                  <span class="text-slate-500">{{ priceAnalysis.most_sold.sold_quantity }} vendidos</span>
                </div>
              </div>

              <!-- Suggested Price -->
              <div v-if="priceAnalysis.suggestion" class="p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <FontAwesomeIcon icon="lightbulb" class="text-yellow-500 text-sm" />
                    <span class="text-xs font-bold text-yellow-500 uppercase tracking-wider">Precio Sugerido (ML)</span>
                  </div>
                  <span class="font-black text-yellow-500 text-lg">${{ Math.round(priceAnalysis.suggestion.price) }}</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-1">{{ priceAnalysis.suggestion.label || 'Basado en promedio de productos competitivos' }}</p>
              </div>

              <!-- Product Found on ML -->
              <div v-if="priceAnalysis.source && priceAnalysis.source.id" class="text-center">
                <a 
                  :href="'https://articulo.mercadolibre.com.mx/' + priceAnalysis.source.id" 
                  target="_blank"
                  class="text-[10px] text-blue-400 hover:underline"
                >
                  Ver producto de referencia en ML →
                </a>
              </div>
            </div>

            <!-- No Analysis Available -->
            <div v-else class="text-center py-3">
              <p class="text-xs text-slate-500">No se encontró análisis de competencia</p>
            </div>
          </div>

          <!-- Alert / Warning if price >= 299 (obligation shipping free) -->
          <div v-if="sellPrice >= 299" class="p-4 bg-yellow-500/10 border border-yellow-500/25 rounded-2xl flex gap-3 text-xs text-yellow-600 dark:text-yellow-400 leading-normal">
            <FontAwesomeIcon icon="info-circle" class="mt-0.5 flex-shrink-0" />
            <div>
              <span class="font-bold">Envío Gratis Obligatorio:</span> Al vender a un precio mayor o igual a **$299.00 MXN**, MercadoLibre te cobra un costo de envío estimado de **$59.60 MXN**. 
              <button 
                @click="sellPrice = 289"
                class="block underline text-left font-bold text-slate-900 dark:text-slate-100 mt-1 hover:no-underline"
              >
                Ajustar precio a $289.00 MXN para no pagar envío.
              </button>
            </div>
          </div>

          <!-- Profit Simulator Breakdown -->
          <div v-if="simulation" class="p-5 bg-white/5 dark:bg-slate-800/10 border border-slate-200/40 dark:border-slate-700/40 rounded-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/50 pb-2">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Concepto de Ganancias</span>
              <span class="text-xs text-slate-500 font-bold">Por Unidad</span>
            </div>

            <!-- Pricing items -->
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500 dark:text-slate-400">Precio de Publicación</span>
                <span class="font-bold text-slate-900 dark:text-white">${{ simulation.price.toFixed(2) }} MXN</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500 dark:text-slate-400">Costo CVA (+IVA estimado)</span>
                <span class="font-bold text-slate-600 dark:text-slate-300">-${{ simulation.cost.toFixed(2) }} MXN</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500 dark:text-slate-400">Comisión ML ({{ listingType === 'gold_special' ? '13%' : '17.5%' }})</span>
                <span class="font-medium text-rose-500">-${{ simulation.commission.toFixed(2) }} MXN</span>
              </div>
              <div v-if="simulation.fixedFee > 0" class="flex justify-between">
                <span class="text-slate-500 dark:text-slate-400">Cargo Fijo (< $299 MXN)</span>
                <span class="font-medium text-rose-500">-${{ simulation.fixedFee.toFixed(2) }} MXN</span>
              </div>
              <div v-if="simulation.shippingFee > 0" class="flex justify-between">
                <span class="text-slate-500 dark:text-slate-400">Cargo por Envío Gratis</span>
                <span class="font-medium text-rose-500">-${{ simulation.shippingFee.toFixed(2) }} MXN</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500 dark:text-slate-400">Retenciones Estimadas (8% IVA/ISR)</span>
                <span class="font-medium text-rose-500">-${{ simulation.taxWithholding.toFixed(2) }} MXN</span>
              </div>
            </div>

            <!-- Total Result breakdown -->
            <div class="border-t border-slate-200 dark:border-slate-700/50 pt-4 space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Monto Neto Recibido</span>
                <span class="font-bold text-slate-900 dark:text-white text-base">${{ simulation.netReceived.toFixed(2) }} MXN</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-xl" :class="[simulation.netProfit > 0 ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20']">
                <div class="flex items-center gap-1.5">
                  <FontAwesomeIcon :icon="simulation.netProfit > 0 ? 'chart-line' : 'exclamation-circle'" />
                  <span class="text-xs font-bold uppercase tracking-wider">Ganancia Neta</span>
                </div>
                <div class="text-right">
                  <span class="font-black text-lg block">${{ simulation.netProfit.toFixed(2) }} MXN</span>
                  <span class="text-[10px] font-bold block">{{ simulation.profitMargin.toFixed(1) }}% de Margen</span>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Drawer Footer -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-white/5 flex items-center justify-end gap-3 flex-shrink-0">
          <button
            type="button"
            @click="closePublishModal"
            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl hover:bg-white/5 transition-colors"
          >
            Cancelar
          </button>
          <button
            type="button"
            @click="publishProduct"
            :disabled="publishing || !simulation || simulation.netProfit <= 0"
            class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed text-slate-950 font-black text-xs rounded-xl shadow-md transition-all flex items-center gap-2"
          >
            <FontAwesomeIcon icon="spinner" spin v-if="publishing" />
            <FontAwesomeIcon icon="check-circle" v-else />
            <span>Confirmar Publicación</span>
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
