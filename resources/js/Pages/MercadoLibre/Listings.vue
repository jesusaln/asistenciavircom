<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { 
  faStore, faSync, faTrash, faExternalLinkAlt, faSpinner, 
  faExclamationTriangle, faBoxOpen, faInfoCircle, faChevronRight,
  faSearch, faLink
} from '@fortawesome/free-solid-svg-icons'
import { notyf } from '@/Utils/notyf.js'

library.add(
  faStore, faSync, faTrash, faExternalLinkAlt, faSpinner, 
  faExclamationTriangle, faBoxOpen, faInfoCircle, faChevronRight,
  faSearch, faLink
)

defineOptions({ layout: AppLayout })

const props = defineProps({
  listings: { type: Array, default: () => [] },
  meliUser: { type: Object, default: null },
  error: { type: String, default: null }
})

const searchQuery = ref('')
const syncing = ref(false)
const deletingId = ref(null)

// Product mapping / linking variables
import axios from 'axios'
const showLinkModal = ref(false)
const selectedListing = ref(null)
const productSearchQuery = ref('')
const searchResults = ref([])
const searchingProducts = ref(false)
const linkingId = ref(null)

let searchTimeout = null
const searchProducts = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  
  if (!productSearchQuery.value.trim()) {
    searchResults.value = []
    return
  }
  
  searchingProducts.value = true
  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get(route('mercadolibre.listings.buscar-productos'), {
        params: { search: productSearchQuery.value }
      })
      searchResults.value = response.data
    } catch (err) {
      console.error(err)
      notyf.error('Error al buscar productos')
    } finally {
      searchingProducts.value = false
    }
  }, 300)
}

const openLinkModal = (listing) => {
  selectedListing.value = listing
  productSearchQuery.value = ''
  searchResults.value = []
  showLinkModal.value = true
}

const linkProduct = (productoId) => {
  linkingId.value = productoId
  router.post(route('mercadolibre.listings.vincular', selectedListing.value.id), {
    producto_id: productoId
  }, {
    onSuccess: () => {
      notyf.success('Publicación vinculada correctamente')
      showLinkModal.value = false
    },
    onError: () => notyf.error('Error al vincular el producto'),
    onFinish: () => {
      linkingId.value = null
    }
  })
}

const filteredListings = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  if (!query) return props.listings
  return props.listings.filter(item => {
    const title = (item.producto?.nombre || item.title || 'Publicación externa').toLowerCase()
    const listingId = (item.listing_id || '').toLowerCase()
    return title.includes(query) || listingId.includes(query)
  })
})

const syncListings = () => {
  syncing.value = true
  router.post(route('mercadolibre.listings.sync'), {}, {
    onSuccess: () => notyf.success('Publicaciones sincronizadas correctamente'),
    onError: () => notyf.error('Error al sincronizar publicaciones'),
    onFinish: () => syncing.value = false
  })
}

// Smart calculation for MercadoLibre suggested price
const getMeliSuggestedPrice = (product, type = 'gold_special') => {
  if (!product || !product.precio_compra) return 0
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
  
  if (289 * R - 25 >= target) {
    return 289
  }

  return Math.round(pHigh)
}

// Smart calculation of profit/loss for a listing at its current price
const getListingAnalysis = (item, useCandidate = false) => {
  const prod = useCandidate ? item.candidate_product : item.producto
  if (!prod || !prod.precio_compra) return null

  const price = parseFloat(item.price) || 0
  const cost = parseFloat(prod.precio_compra) * 1.16 // Cost with 16% IVA estimate

  // ML Commission percentage (approximate rates: Gold Special 13%, Gold Premium 17.5% - default to 13%)
  const commRate = 0.13
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
    cost,
    netReceived,
    netProfit,
    profitMargin
  }
}

// Simulates profit/loss for a catalog product if linked to the selected listing
const getCandidateAnalysis = (prod) => {
  if (!selectedListing.value || !prod || !prod.precio_compra) return null
  
  const price = parseFloat(selectedListing.value.price) || 0
  const cost = parseFloat(prod.precio_compra) * 1.16 // Cost with 16% IVA estimate

  const commRate = 0.13
  const commission = price * commRate
  const fixedFee = price < 299 && price > 0 ? 25.00 : 0.00
  const shippingFee = price >= 299 ? 59.60 : 0.00
  const taxWithholding = price * 0.08

  const netReceived = price - commission - fixedFee - shippingFee - taxWithholding
  const netProfit = netReceived - cost
  const profitMargin = price > 0 ? (netProfit / price) * 100 : 0

  return {
    netProfit,
    profitMargin
  }
}

const linkProductDirectly = (listing, productoId) => {
  linkingId.value = productoId
  router.post(route('mercadolibre.listings.vincular', listing.id), {
    producto_id: productoId
  }, {
    onSuccess: () => {
      notyf.success('Publicación vinculada al candidato sugerido correctamente')
    },
    onError: () => notyf.error('Error al vincular el candidato'),
    onFinish: () => {
      linkingId.value = null
    }
  })
}

const deleteListing = (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar esta publicación de MercadoLibre? Esta acción la cerrará en MercadoLibre y la eliminará de tu base de datos local.')) {
    deletingId.value = id
    router.delete(route('mercadolibre.listings.destroy', id), {
      onSuccess: () => notyf.success('Publicación eliminada correctamente'),
      onError: () => notyf.error('Error al eliminar la publicación'),
      onFinish: () => {
        deletingId.value = null
      }
    })
  }
}
</script>

<template>
  <Head title="Publicaciones de MercadoLibre" />

  <div class="min-h-screen bg-[var(--ui-surface)] pb-20">
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 py-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8 bg-white/5 dark:bg-slate-800/50 backdrop-blur-md border border-slate-200/50 dark:border-slate-700/50 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-yellow-500/10 rounded-2xl text-yellow-500">
            <FontAwesomeIcon icon="store" size="xl" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Publicaciones de MercadoLibre</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Administra y sincroniza tus ventas y publicaciones activas en MercadoLibre</p>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <button
            @click="syncListings"
            :disabled="syncing"
            class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-slate-950 font-bold rounded-xl transition-all flex items-center gap-2 shadow-md"
          >
            <FontAwesomeIcon icon="spinner" spin v-if="syncing" />
            <FontAwesomeIcon icon="sync" v-else />
            <span>Sincronizar Publicaciones</span>
          </button>
        </div>
      </div>

      <!-- Warning/Error if not configured -->
      <div v-if="props.error" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 mb-8 flex items-start gap-4">
        <div class="p-2 bg-amber-500/20 rounded-xl text-amber-500 mt-1">
          <FontAwesomeIcon icon="exclamation-triangle" />
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

      <!-- Account Info if connected -->
      <div v-else-if="props.meliUser" class="bg-white/5 dark:bg-slate-800/30 border border-slate-200/50 dark:border-slate-700/50 rounded-2xl p-5 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-yellow-500/20 text-yellow-500 rounded-full flex items-center justify-center font-bold text-lg uppercase shadow-[0_0_15px_rgba(234,179,8,0.15)]">
            {{ props.meliUser.nickname?.substring(0, 2) }}
          </div>
          <div>
            <h4 class="font-bold text-slate-900 dark:text-white">{{ props.meliUser.nickname }}</h4>
            <p class="text-xs text-slate-400 mt-0.5">
              Site: <span class="font-mono text-slate-300">{{ props.meliUser.site_id }}</span> | 
              E-mail: <span class="text-slate-300">{{ props.meliUser.email }}</span>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2 bg-green-500/10 text-green-500 px-3.5 py-1.5 rounded-xl border border-green-500/20 text-xs font-bold self-start sm:self-auto">
          <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse mr-0.5"></span>
          Conexión Activa
        </div>
      </div>

      <!-- Main Content / Table -->
      <div v-if="!props.error" class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-2xl overflow-hidden shadow-xl">
        
        <!-- Filters -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="relative max-w-md w-full">
            <FontAwesomeIcon icon="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar por título o ID de publicación..."
              class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/30 focus:border-yellow-500 transition-all text-slate-800 dark:text-slate-100"
            />
          </div>
          <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Mostrando {{ filteredListings.length }} de {{ props.listings.length }} publicaciones
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="filteredListings.length === 0" class="py-20 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-400 mb-4">
            <FontAwesomeIcon icon="box-open" size="xl" />
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-1">No se encontraron publicaciones</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
            {{ searchQuery ? 'Prueba ajustando tus términos de búsqueda.' : 'Haz clic en "Sincronizar Publicaciones" para traer tus publicaciones activas desde MercadoLibre.' }}
          </p>
        </div>

        <!-- Table View -->
        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-900/40 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">Producto</th>
                <th class="px-6 py-4">ID de Publicación</th>
                <th class="px-6 py-4">Precio ML</th>
                <th class="px-6 py-4">Costo CVA (+IVA)</th>
                <th class="px-6 py-4">Margen Real</th>
                <th class="px-6 py-4">Stock</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr 
                v-for="item in filteredListings" 
                :key="item.id"
                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
              >
                <!-- Product -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="item.producto?.imagen || item.candidate_product?.imagen || item.thumbnail || '/images/placeholder-product.svg'" 
                      alt="Thumbnail" 
                      class="w-10 h-10 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                      @error="(e) => e.target.src = '/images/placeholder-product.svg'"
                    />
                    <div class="max-w-xs md:max-w-md truncate">
                      <span class="font-bold text-slate-900 dark:text-slate-200 block truncate">{{ item.producto?.nombre || item.title || 'Publicación externa' }}</span>
                      <span class="text-xs text-slate-400 block mt-0.5 font-mono">
                        {{ item.producto?.codigo || 'Sin SKU local' }}
                      </span>
                      <div v-if="!item.producto && item.candidate_product" class="mt-1 flex items-center">
                        <span class="px-1.5 py-0.5 bg-yellow-500/10 text-yellow-500 border border-yellow-500/20 rounded text-[9px] font-bold tracking-wide">
                          Candidato Sugerido: {{ item.candidate_product.codigo }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                
                <!-- Listing ID -->
                <td class="px-6 py-4 font-mono text-xs text-slate-600 dark:text-slate-300">
                  {{ item.listing_id }}
                </td>

                <!-- Price -->
                <td class="px-6 py-4">
                  <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-slate-200">
                      ${{ parseFloat(item.price).toFixed(2) }} MXN
                    </span>
                    <span v-if="item.producto" class="text-[10px] text-slate-400 font-semibold mt-0.5">
                      Sugerido: ${{ getMeliSuggestedPrice(item.producto, 'gold_special') }}.00
                    </span>
                    <span v-else-if="item.candidate_product" class="text-[10px] text-yellow-500/80 font-bold mt-0.5">
                      Sugerido: ${{ getMeliSuggestedPrice(item.candidate_product, 'gold_special') }}.00
                    </span>
                  </div>
                </td>

                <!-- Costo CVA (+IVA) -->
                <td class="px-6 py-4">
                  <span v-if="item.producto" class="font-semibold text-slate-600 dark:text-slate-300">
                    ${{ (parseFloat(item.producto.precio_compra) * 1.16).toFixed(2) }} MXN
                  </span>
                  <div v-else-if="item.candidate_product" class="flex flex-col">
                    <span class="font-semibold text-yellow-600 dark:text-yellow-400/80">
                      ${{ (parseFloat(item.candidate_product.precio_compra) * 1.16).toFixed(2) }} MXN
                    </span>
                    <span class="text-[9px] text-slate-400 font-medium">
                      (Sugerido)
                    </span>
                  </div>
                  <span v-else class="text-xs text-slate-400 italic">
                    Sin vincular
                  </span>
                </td>

                <!-- Margen Real -->
                <td class="px-6 py-4">
                  <div v-if="item.producto && getListingAnalysis(item, false)" class="flex flex-col">
                    <span 
                      :class="[
                        'font-bold text-xs',
                        getListingAnalysis(item, false).netProfit > 0 ? 'text-green-500' : 'text-rose-500'
                      ]"
                    >
                      {{ getListingAnalysis(item, false).netProfit > 0 ? '+' : '' }}${{ getListingAnalysis(item, false).netProfit.toFixed(2) }} MXN
                    </span>
                    <span 
                      :class="[
                        'text-[10px] font-bold block mt-0.5',
                        getListingAnalysis(item, false).netProfit > 0 ? 'text-green-500/80' : 'text-rose-500/80'
                      ]"
                    >
                      {{ getListingAnalysis(item, false).netProfit > 0 ? 'Ganancia' : 'Pérdida' }} ({{ getListingAnalysis(item, false).profitMargin.toFixed(1) }}%)
                    </span>
                  </div>
                  <div v-else-if="item.candidate_product && getListingAnalysis(item, true)" class="flex flex-col">
                    <span 
                      :class="[
                        'font-bold text-xs',
                        getListingAnalysis(item, true).netProfit > 0 ? 'text-yellow-500' : 'text-rose-500'
                      ]"
                    >
                      {{ getListingAnalysis(item, true).netProfit > 0 ? '+' : '' }}${{ getListingAnalysis(item, true).netProfit.toFixed(2) }} MXN
                    </span>
                    <span 
                      :class="[
                        'text-[10px] font-bold block mt-0.5',
                        getListingAnalysis(item, true).netProfit > 0 ? 'text-yellow-500/80' : 'text-rose-500/80'
                      ]"
                    >
                      Est. ({{ getListingAnalysis(item, true).profitMargin.toFixed(1) }}%)
                    </span>
                  </div>
                  <span v-else class="text-xs text-slate-400 italic">
                    N/A
                  </span>
                </td>

                <!-- Stock -->
                <td class="px-6 py-4 text-slate-700 dark:text-slate-300 font-medium">
                  {{ item.stock_published }} uds.
                </td>

                <!-- Status -->
                <td class="px-6 py-4">
                  <span 
                    :class="[
                      'px-2.5 py-1 rounded-full text-xs font-bold border',
                      item.status === 'active' 
                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' 
                        : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                    ]"
                  >
                    {{ item.status === 'active' ? 'Activo' : 'Cerrado' }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <button 
                      v-if="!item.producto && item.candidate_product"
                      @click="linkProductDirectly(item, item.candidate_product.id)"
                      :disabled="linkingId === item.candidate_product.id"
                      class="px-2.5 py-1.5 bg-yellow-500/10 hover:bg-yellow-500 text-yellow-500 hover:text-slate-950 border border-yellow-500/20 rounded-xl transition-all flex items-center gap-1 shrink-0"
                      title="Vincular a Candidato Sugerido"
                    >
                      <FontAwesomeIcon icon="spinner" spin v-if="linkingId === item.candidate_product.id" class="text-xs" />
                      <FontAwesomeIcon icon="link" v-else class="text-xs" />
                      <span class="text-[10px] font-bold">Vincular Rápido</span>
                    </button>
                    <a 
                      v-if="item.permalink"
                      :href="item.permalink" 
                      target="_blank"
                      class="p-2 text-slate-500 hover:text-yellow-500 bg-slate-100 dark:bg-slate-800 hover:bg-yellow-500/10 border border-slate-200 dark:border-slate-700 rounded-xl transition-all"
                      title="Ver en MercadoLibre"
                    >
                      <FontAwesomeIcon icon="external-link-alt" class="text-xs" />
                    </a>
                    <button 
                      @click="openLinkModal(item)"
                      class="p-2 text-slate-500 hover:text-emerald-500 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-500/10 border border-slate-200 dark:border-slate-700 rounded-xl transition-all"
                      title="Vincular a Producto Local"
                    >
                      <FontAwesomeIcon icon="link" class="text-xs" />
                    </button>
                    <button 
                      @click="deleteListing(item.id)"
                      :disabled="deletingId === item.id"
                      class="p-2 text-slate-500 hover:text-rose-500 bg-slate-100 dark:bg-slate-800 hover:bg-rose-500/10 border border-slate-200 dark:border-slate-700 rounded-xl transition-all"
                      title="Eliminar Publicación"
                    >
                      <FontAwesomeIcon icon="spinner" spin v-if="deletingId === item.id" class="text-xs" />
                      <FontAwesomeIcon icon="trash" v-else class="text-xs" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
    </div>
  </div>

  <!-- Modal de Vinculación -->
  <div v-if="showLinkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showLinkModal = false"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] z-10">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <FontAwesomeIcon icon="link" class="text-yellow-500" />
            <span>Vincular Producto Local</span>
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Asocia la publicación <span class="font-mono text-slate-700 dark:text-slate-300">{{ selectedListing?.listing_id }}</span> a un producto de tu catálogo.
          </p>
        </div>
        <button 
          @click="showLinkModal = false"
          class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold p-1"
        >
          &times;
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto flex-1 space-y-5">
        <!-- Publication details -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/30 rounded-2xl border border-slate-200/50 dark:border-slate-800 flex items-center gap-3">
          <img 
            :src="selectedListing?.producto?.imagen || selectedListing?.thumbnail || '/images/placeholder-product.svg'" 
            alt="Thumbnail" 
            class="w-12 h-12 object-cover rounded-xl border border-slate-200 dark:border-slate-800"
            @error="(e) => e.target.src = '/images/placeholder-product.svg'"
          />
          <div class="flex-1 min-w-0">
            <span class="text-xs text-slate-400 font-bold block">Publicación en MercadoLibre:</span>
            <span class="font-bold text-sm text-slate-900 dark:text-slate-200 block truncate">{{ selectedListing?.producto?.nombre || selectedListing?.title || 'Publicación externa' }}</span>
            <span class="text-xs font-semibold text-yellow-500 mt-0.5 block">${{ parseFloat(selectedListing?.price).toFixed(2) }} MXN</span>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">Buscar en tu Catálogo</label>
          <div class="relative">
            <FontAwesomeIcon icon="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
            <input
              v-model="productSearchQuery"
              @input="searchProducts"
              type="text"
              placeholder="Buscar por nombre, código CVA o clave..."
              class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500/30 focus:border-yellow-500 transition-all text-slate-800 dark:text-slate-100"
            />
          </div>
        </div>

        <!-- Search Results -->
        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">Resultados</label>
          
          <!-- Loading -->
          <div v-if="searchingProducts" class="py-10 flex flex-col items-center justify-center text-slate-400">
            <FontAwesomeIcon icon="spinner" spin size="lg" class="mb-2" />
            <span class="text-xs">Buscando productos...</span>
          </div>

          <!-- Empty -->
          <div v-else-if="productSearchQuery && searchResults.length === 0" class="py-10 text-center text-slate-500 text-sm">
            No se encontraron productos coincidentes en tu catálogo.
          </div>

          <!-- Prompt -->
          <div v-else-if="!productSearchQuery" class="py-10 text-center text-slate-400 text-sm">
            Escribe arriba para comenzar a buscar productos activos.
          </div>

          <!-- List -->
          <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/60 max-h-60 overflow-y-auto rounded-2xl border border-slate-200/50 dark:border-slate-800/80">
            <div 
              v-for="prod in searchResults" 
              :key="prod.id"
              class="p-3.5 hover:bg-slate-50 dark:hover:bg-slate-950/40 transition-colors flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <img 
                  :src="prod.imagen || '/images/placeholder-product.svg'" 
                  alt="Product" 
                  class="w-10 h-10 object-cover rounded-lg border border-slate-200 dark:border-slate-800"
                  @error="(e) => e.target.src = '/images/placeholder-product.svg'"
                />
                <div class="min-w-0">
                  <span class="font-bold text-xs text-slate-900 dark:text-slate-200 block truncate" :title="prod.nombre">{{ prod.nombre }}</span>
                  <span class="text-[10px] text-slate-400 font-mono block mt-0.5">Clave: {{ prod.codigo || prod.cva_clave || 'N/A' }}</span>
                  <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">
                    Costo (+IVA): ${{ (parseFloat(prod.precio_compra) * 1.16).toFixed(2) }} MXN | Stock: {{ (prod.stock || 0) + (prod.stock_cedis || 0) }}
                  </span>
                  <span class="text-[10px] text-emerald-500 font-bold block mt-0.5">
                    Sugerido ML (Clásica): ${{ getMeliSuggestedPrice(prod, 'gold_special') }}.00 MXN
                  </span>
                  <!-- Simulated margin analysis -->
                  <div v-if="getCandidateAnalysis(prod)" class="mt-1 flex items-center gap-2">
                    <span 
                      :class="[
                        'text-[10px] font-bold px-1.5 py-0.5 rounded border',
                        getCandidateAnalysis(prod).netProfit > 0 
                          ? 'bg-green-500/10 text-green-500 border-green-500/20' 
                          : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                      ]"
                    >
                      Ganancia si vinculas: {{ getCandidateAnalysis(prod).netProfit > 0 ? '+' : '' }}${{ getCandidateAnalysis(prod).netProfit.toFixed(2) }} MXN ({{ getCandidateAnalysis(prod).profitMargin.toFixed(1) }}%)
                    </span>
                  </div>
                </div>
              </div>
              <button
                @click="linkProduct(prod.id)"
                :disabled="linkingId === prod.id"
                class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-slate-950 font-bold text-xs rounded-lg transition-colors flex items-center gap-1.5 shrink-0"
              >
                <FontAwesomeIcon icon="spinner" spin v-if="linkingId === prod.id" />
                <span>Vincular</span>
              </button>
            </div>
          </div>
        </div>

      </div>
      
      <!-- Modal Footer -->
      <div class="p-4 bg-slate-50 dark:bg-slate-950/20 border-t border-slate-200 dark:border-slate-800 flex justify-end">
        <button 
          @click="showLinkModal = false"
          class="px-4 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-bold text-xs rounded-xl"
        >
          Cancelar
        </button>
      </div>

    </div>
  </div>
</template>
