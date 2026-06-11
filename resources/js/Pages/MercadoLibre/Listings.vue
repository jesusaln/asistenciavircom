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

const deleteListing = (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar esta publicación de MercadoLibre? Esta acción la cerrará en MercadoLibre y la eliminará de tu sitio.')) {
    deletingId.value = id
    router.delete(route('mercadolibre.listings.destroy', id), {
      onSuccess: () => notyf.success('Publicación eliminada correctamente'),
      onError: () => notyf.error('Error al eliminar la publicación'),
      onFinish: () => deletingId.value = null
    })
  }
}
</script>

<template>
  <Head title="Publicaciones de MercadoLibre" />

  <div class="min-h-screen bg-[var(--ui-surface)] pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
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
                <th class="px-6 py-4">Precio</th>
                <th class="px-6 py-4">Stock</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4">Sincronizado</th>
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
                      :src="item.producto?.imagen || item.thumbnail || '/images/placeholder-product.svg'" 
                      alt="Thumbnail" 
                      class="w-10 h-10 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                      @error="(e) => e.target.src = '/images/placeholder-product.svg'"
                    />
                    <div class="max-w-xs md:max-w-md truncate">
                      <span class="font-bold text-slate-900 dark:text-slate-200 block truncate">{{ item.producto?.nombre || item.title || 'Publicación externa' }}</span>
                      <span class="text-xs text-slate-400 block mt-0.5 font-mono">{{ item.producto?.codigo || 'Sin SKU local' }}</span>
                    </div>
                  </div>
                </td>
                
                <!-- Listing ID -->
                <td class="px-6 py-4 font-mono text-xs text-slate-600 dark:text-slate-300">
                  {{ item.listing_id }}
                </td>

                <!-- Price -->
                <td class="px-6 py-4 font-bold text-slate-900 dark:text-slate-200">
                  ${{ parseFloat(item.price).toFixed(2) }} MXN
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

                <!-- Last Sync -->
                <td class="px-6 py-4 text-xs text-slate-400 font-medium">
                  {{ item.last_sync_at ? new Date(item.last_sync_at).toLocaleString() : 'Nunca' }}
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
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
                  <span class="font-bold text-xs text-slate-900 dark:text-slate-200 block truncate">{{ prod.nombre }}</span>
                  <span class="text-[10px] text-slate-400 font-mono block mt-0.5">Clave: {{ prod.codigo || prod.cva_clave || 'N/A' }}</span>
                  <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">Precio: ${{ parseFloat(prod.precio_venta).toFixed(2) }} MXN | Stock: {{ (prod.stock || 0) + (prod.stock_cedis || 0) }}</span>
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
