<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted } from 'vue'
import { useCart } from '@/composables/useCart'
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import SocialProofNotification from '@/Components/SocialProofNotification.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    productos: Object,
    categorias: Array,
    marcas: Array,
    empresa: Object,
    filters: Object,
    cliente: Object,
    canLogin: Boolean
})

const page = usePage();

// Combinar datos globales con props para asegurar colores corporativos
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

// Variables CSS dinámicas con colores corporativos
const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#FF6B35',
    '--color-primary-soft': (empresaData.value.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#B45309',
}));

const { items, itemCount, addItem, isInCart } = useCart()

const search = ref(props.filters?.search || '')
const selectedCategoria = ref(props.filters?.categoria || '')
const selectedMarca = ref(props.filters?.marca || '')
const selectedOrden = ref(props.filters?.orden || 'recientes')
const showMobileFilters = ref(false)
const addedToCart = ref(null)
const searchFocused = ref(false)
const soloExistencia = ref(true) 
const soloLocal = ref(false)
const cvaPage = ref(1)
const hasMoreCva = ref(true)
const suggestions = ref([])

// Suggestions Logic
let suggestionTimeout = null
const fetchSuggestions = async () => {
    if (!search.value || search.value.length < 3) {
        suggestions.value = []
        return
    }
    try {
        const response = await axios.get(route('tienda.cva.sugerencias'), { 
            params: { q: search.value } 
        })
        suggestions.value = response.data || []
    } catch (e) {
        console.error('Error fetching suggestions', e)
        suggestions.value = []
    }
}

const debouncedSuggestions = () => {
    clearTimeout(suggestionTimeout)
    suggestionTimeout = setTimeout(fetchSuggestions, 300)
} 

const fetchCvaProducts = (fresh = true) => {
    if (fresh) {
        cvaPage.value = 1
    }
    applyFilters()
}

// Smart Filters Implementation
const smartFilters = computed(() => {
    if (!allProducts.value.length) return []
    
    const tags = new Map()
    const currentSearch = (search.value || '').toLowerCase()

    const patterns = [
        /\b(core\s*i[3579])\b/gi, /\b(ryzen\s*\d)\b/gi, /\b(celeron)\b/gi, /\b(athlon)\b/gi,
        /\b(\d+GB)\b/gi, /\b(\d+TB)\b/gi, /\b(SSD)\b/gi,
        /\b(HP)\b/gi, /\b(Dell)\b/gi, /\b(Lenovo)\b/gi, /\b(Asus)\b/gi, /\b(Acer)\b/gi, 
        /\b(Epson)\b/gi, /\b(Canon)\b/gi, /\b(Brother)\b/gi,
        /\b(Laptop)\b/gi, /\b(Desktop)\b/gi, /\b(Monitor)\b/gi, /\b(Impresora)\b/gi
    ]

    allProducts.value.forEach(p => {
        patterns.forEach(regex => {
            const matches = p.nombre.match(regex)
            if (matches) {
                matches.forEach(m => {
                    const tag = m.charAt(0).toUpperCase() + m.slice(1).toLowerCase().replace(/\s+/g, ' ')
                    if (!currentSearch.includes(tag.toLowerCase())) {
                        tags.set(tag, (tags.get(tag) || 0) + 1)
                    }
                })
            }
        })
    })

    return Array.from(tags.entries())
        .sort((a, b) => b[1] - a[1])
        .slice(0, 8)
        .map(entry => entry[0])
})

const handleSmartFilter = (tag) => {
    router.visit(route('catalogo.index', { 
        search: search.value ? `${search.value} ${tag}` : tag,
        categoria: selectedCategoria.value,
        marca: selectedMarca.value
    }))
} 

// Al usar el catálogo unificado del backend, allProducts es mucho más simple
const allProducts = computed(() => {
    let list = props.productos?.data || []
    
    if (soloLocal.value) {
        list = list.filter(p => p.origen === 'local' || p.stock > 0)
    }

    if (soloExistencia.value) {
        list = list.filter(p => p.stock > 0 || p.stock_cedis > 0)
    }

    return list
})

const current_page = computed(() => props.productos?.current_page || 1)
const filteredCount = computed(() => props.productos?.total || 0)

const applyFilters = () => {
    router.get(route('catalogo.index'), {
        search: search.value || undefined,
        categoria: selectedCategoria.value || undefined,
        marca: selectedMarca.value || undefined,
        orden: selectedOrden.value !== 'recientes' ? selectedOrden.value : undefined,
        existencia: soloExistencia.value ? 1 : undefined,
        local: soloLocal.value ? 1 : undefined,
    }, {
        preserveState: true,
        preserveScroll: false,
    })
}

onMounted(() => {
    window.scrollTo(0, 0)
})

const clearFilters = () => {
    search.value = ''
    selectedCategoria.value = ''
    selectedMarca.value = ''
    selectedOrden.value = 'recientes'
    router.get(route('catalogo.index'))
}

// Debounce search
let searchTimeout = null
watch(search, (val) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 500)
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN' 
    }).format(value || 0)
}

// Precio con IVA
const precioConIva = (precio) => {
    return precio * 1.16
}

const getImageUrl = (producto) => {
    // Si es una imagen CVA (http), usar proxy
    if (producto.imagen || producto.imagen_url) {
        const img = producto.imagen || producto.imagen_url
        
        if (img.startsWith('http')) {
            // Usar Proxy local para evitar Mixed Content y aprovechar caché
            return route('img.proxy', { url: img })
        }
        return `/storage/${img}`
    }
    return null
}

const handleAskProduct = (producto) => {
    if (!empresaData.value?.whatsapp) return
    const phone = empresaData.value.whatsapp.replace(/\D/g, '')
    const precio = formatCurrency(producto.precio_con_iva)
    
    let text = `Hola, me interesa el producto:\n\n*${producto.nombre}*`;
    if (producto.origen === 'CVA') {
        text += `\n(Desde Almacén Central)\n\nPrecio aprox: ${precio}\n\n¿Tienen disponibilidad para envío inmediato?`;
    } else if (producto.stock <= 0) {
        text += `\n\nVeo que no hay stock inmediato. ¿Podrían darme una cotización o decirme cuándo tendrán disponibilidad?`;
    } else {
        text += `\nPrecio: ${precio} (IVA incl.)\n\n¿Está disponible?`;
    }
    
    const message = encodeURIComponent(text)
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
}

const handleAddToCart = (producto) => {
    const item = {
        ...producto,
        precio: producto.precio_con_iva
    }
    addItem(item)
    addedToCart.value = producto.id
    setTimeout(() => {
        addedToCart.value = null
    }, 1500)
}

// Cerrar sugerencias al hacer scroll o presionar Esc
if (typeof window !== 'undefined') {
    window.addEventListener('scroll', () => {
        if (searchFocused.value) searchFocused.value = false
    }, { passive: true })
    
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') searchFocused.value = false
    })
}
</script>

<template>
    <Head :title="`Tienda - ${empresaData?.nombre || empresaData?.nombre_empresa || 'Catálogo'}`" />
    
    <div class="min-h-screen bg-gray-50 font-sans" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />

        <!-- Notificación de Prueba Social (FOMO) - productos reales (incluyendo CVA) -->
        <SocialProofNotification :productos="allProducts" :duration="5000" :initialDelay="15000" :interval="600000" />

        <!-- Navbar -->
        <PublicNavbar :empresa="empresaData" activeTab="tienda" />

        <!-- Hero con Búsqueda -->
        <section class="py-16 bg-gray-50 relative overflow-hidden">
            <!-- Efecto cristal de fondo -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full opacity-30 blur-3xl" 
                     style="background-color: var(--color-primary);"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full opacity-20 blur-3xl" 
                     style="background-color: var(--color-secondary);"></div>
            </div>
            
            <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center relative">
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Explora nuestros productos
                </h1>
                <p class="text-gray-500 mb-8 max-w-xl mx-auto">
                    Encuentra lo que necesitas con la mejor calidad y precio
                </p>
                
                <!-- Barra de búsqueda con efecto cristal -->
                <div class="relative max-w-2xl mx-auto z-50">
                    <div :class="[
                        'relative bg-white rounded-2xl transition-all duration-300',
                        searchFocused ? 'shadow-xl ring-2 rounded-b-none border-b-0' : 'shadow-md'
                    ]" :style="searchFocused ? { '--tw-ring-color': 'var(--color-primary)' } : {}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input 
                                v-model="search"
                                @focus="searchFocused = true"
                                @blur="setTimeout(() => searchFocused = false, 200)"
                                @input="debouncedSuggestions"
                                type="text" 
                                placeholder="Buscar productos por nombre, código o descripción..." 
                                class="w-full h-14 px-4 bg-transparent border-0 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
                            />
                            <button v-if="search" 
                                    @click="clearFilters"
                                    class="mr-3 p-1.5 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Autocomplete Dropdown (Premium Version) -->
                    <div v-show="searchFocused && suggestions.length > 0" 
                         class="absolute w-full bg-white rounded-b-2xl shadow-2xl border border-gray-100 overflow-hidden z-[100] transition-all max-h-[450px] overflow-y-auto ring-1 ring-black/5">
                        <div class="p-2.5 border-b border-gray-50 flex justify-between items-center bg-gray-50/50 sticky top-0 z-10">
                            <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase ml-2">Sugerencias de productos</span>
                            <button @click="searchFocused = false" class="text-[10px] font-bold text-gray-400 hover:text-gray-600 px-2 uppercase">Cerrar</button>
                        </div>
                        <ul class="divide-y divide-gray-50">
                            <li v-for="sug in suggestions" :key="sug.id">
                                <Link :href="route('catalogo.show', sug.id)" 
                                      class="group flex items-center gap-4 px-4 py-3 hover:bg-gray-50 transition-all relative">
                                    <div class="w-14 h-14 rounded-xl bg-white border border-gray-100 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-sm group-hover:scale-105 transition-transform duration-300">
                                        <img :src="getImageUrl({ imagen: sug.image })" alt="" class="w-full h-full object-contain p-1" @error="(e) => (e.target.src = '/img/placeholder-product.png')">
                                    </div>
                                    <div class="flex-1 min-w-0 text-left">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span v-if="sug.origen === 'CVA'" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-[9px] font-black uppercase tracking-tighter">CVA</span>
                                            <p class="text-sm font-bold text-gray-900 truncate leading-tight group-hover:text-[var(--color-primary)] transition-colors">
                                                {{ sug.label }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <p class="text-[11px] text-gray-500 font-medium">{{ sug.category }}</p>
                                            <div class="flex items-center gap-1.5">
                                                <span v-if="sug.origen === 'CVA'" 
                                                      :class="[
                                                          'h-2 w-2 rounded-full',
                                                          sug.stock > 0 ? 'bg-green-500 animate-pulse' : 'bg-amber-400'
                                                      ]"></span>
                                                <span class="text-[11px] font-black uppercase tracking-tight" :class="sug.stock > 0 ? 'text-green-600' : 'text-amber-600'">
                                                    {{ sug.stock > 0 ? 'En Hermosillo' : 'Bajo pedido' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[14px] font-black text-gray-900 leading-none">
                                            {{ formatCurrency(sug.price) }}
                                        </p>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase mt-1">IVA Incl.</p>
                                    </div>
                                </Link>
                            </li>
                            <li>
                                <button @click="applyFilters" class="w-full py-3 text-center bg-gray-50 hover:bg-gray-100 text-xs font-bold text-gray-500 transition-colors uppercase tracking-widest">
                                    Ver todos los resultados para "{{ search }}"
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Filtros rápidos -->
                <div class="flex flex-wrap justify-center gap-2 mt-6">
                    <button 
                        @click="selectedCategoria = ''; applyFilters()"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium transition-all',
                            !selectedCategoria 
                                ? 'text-white' 
                                : 'bg-white text-gray-600 hover:bg-gray-100'
                        ]"
                        :style="!selectedCategoria ? { backgroundColor: 'var(--color-primary)' } : {}">
                        Todos
                    </button>
                    <button 
                        v-for="cat in categorias?.slice(0, 5)" 
                        :key="cat.id"
                        @click="selectedCategoria = cat.id; applyFilters()"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium transition-all',
                            selectedCategoria == cat.id 
                                ? 'text-white' 
                                : 'bg-white text-gray-600 hover:bg-gray-100'
                        ]"
                        :style="selectedCategoria == cat.id ? { backgroundColor: 'var(--color-primary)' } : {}">
                        {{ cat.nombre }}
                    </button>
                </div>

                <!-- Smart Tags / Sugerencias de búsqueda -->
                <div v-if="smartFilters.length > 0" class="flex flex-wrap justify-center gap-2 mt-4 animate-fade-in-up">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider py-1">Quizás buscas:</span>
                    <button v-for="tag in smartFilters" 
                            :key="tag"
                            @click="handleSmartFilter(tag)"
                            class="px-3 py-1 rounded-md text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 hover:bg-blue-100 transition-colors">
                        + {{ tag }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
            <!-- Toolbar -->
            <div class="flex items-center justify-between mb-8">
                <p class="text-sm text-gray-500">
                    <span class="font-semibold text-gray-900">{{ filteredCount }}</span> productos encontrados
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Toggle Existencia (CVA) -->
                    <div class="flex flex-col gap-2">
                        <label v-if="empresaData?.cva_active" class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" v-model="soloExistencia" @change="fetchCvaProducts(false)" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider group-hover:text-blue-600 transition-colors">Con Stock (Todo)</span>
                        </label>
                        <label v-if="empresaData?.cva_active" class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" v-model="soloLocal" @change="fetchCvaProducts(false)" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-600"></div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider group-hover:text-green-600 transition-colors">Entrega Inmediata (Local)</span>
                        </label>
                    </div>

                    <select v-model="selectedOrden" @change="applyFilters()"
                            class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="recientes">Más recientes</option>
                        <option value="precio_asc">Menor precio</option>
                        <option value="precio_desc">Mayor precio</option>
                        <option value="nombre">Nombre A-Z</option>
                    </select>
                    <button v-if="selectedCategoria || selectedMarca || search"
                            @click="clearFilters"
                            class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Products Grid -->
            <div v-if="allProducts.length" class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <!-- Unified Grid for both Local and CVA -->
                <article v-for="producto in allProducts" :key="producto.id"
                         class="group bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                    <!-- Image con efecto cristal -->
                    <Link :href="route('catalogo.show', producto.id)" class="block relative aspect-square bg-gray-50 overflow-hidden">
                        <!-- Efecto cristal -->
                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent pointer-events-none z-10"></div>
                        
                        <img v-if="getImageUrl(producto)" 
                             :src="getImageUrl(producto)" 
                             :alt="producto.nombre"
                             class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300 p-2" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        
                        <!-- Stock Badge -->
                        <div class="absolute top-2 right-2 z-20 flex flex-col items-end gap-1">
                            <span v-if="producto.stock_local > 0" 
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700 shadow-sm border border-green-200">
                                Disponible en Sucursal
                            </span>
                            <span v-else-if="producto.stock_cedis > 0" 
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 shadow-sm border border-blue-200">
                                Almacén Central (2-4 días)
                            </span>
                            <span v-else-if="producto.stock > 0" 
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700">
                                {{ producto.stock }} disponibles
                            </span>
                            <span v-else 
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700">
                                Bajo Pedido
                            </span>
                        </div>
                    </Link>

                    <!-- Content -->
                    <div class="p-4">
                        <Link :href="route('catalogo.show', producto.id)">
                            <h3 class="font-medium text-gray-900 text-sm line-clamp-2 group-hover:text-[var(--color-primary)] transition-colors mb-1 min-h-[40px]">
                                {{ producto.nombre }}
                            </h3>
                        </Link>
                        
                        <p class="text-xs text-gray-400 mb-2 uppercase font-semibold">
                            {{ producto.marca?.nombre || producto.marca }}
                        </p>

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-bold" :class="producto.origen === 'CVA' ? 'text-blue-600' : ''" :style="producto.origen !== 'CVA' ? { color: 'var(--color-primary)' } : {}">
                                    {{ formatCurrency(producto.precio_con_iva) }}
                                </p>
                                <p class="text-[10px] text-gray-400">
                                    {{ producto.origen === 'CVA' ? 'Entrega 2 a 4 días' : 'IVA incl.' }}
                                </p>
                            </div>
                            
                            <!-- Botón agregar al carrito o preguntar -->
                            <button v-if="producto.stock > 0" 
                                    @click="handleAddToCart(producto)"
                                    :class="[
                                        'p-2 rounded-lg transition-all',
                                        addedToCart === producto.id ? 'bg-green-500 text-white' : (producto.origen === 'CVA' ? 'bg-blue-50 text-blue-600 hover:bg-blue-100' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                                    ]">
                                <svg v-if="addedToCart === producto.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <button v-else 
                                    @click="handleAskProduct(producto)"
                                    class="p-2 bg-amber-100 text-amber-600 rounded-lg hover:bg-amber-200 transition-all"
                                    title="Preguntar por disponibilidad">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Loading Skeleton while fetching CVA -->
            <div v-else-if="loadingCva" class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="i in 8" :key="i" class="animate-pulse bg-white rounded-xl p-4 border border-gray-100 h-64">
                    <div class="bg-gray-200 rounded-lg aspect-square mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20">
                <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No encontramos productos</h3>
                <p class="text-gray-500 mb-6">Intenta con otros términos de búsqueda</p>
                <button @click="clearFilters" 
                        class="px-6 py-2 text-white rounded-lg font-medium hover:opacity-90 transition-all"
                        style="background-color: var(--color-primary);">
                    Ver todos los productos
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="productos?.last_page > 1" class="mt-12 flex flex-col items-center gap-4">
                <div class="text-sm text-gray-500">
                    Página {{ productos.current_page }} de {{ productos.last_page }} 
                    <span class="mx-2">•</span>
                    {{ productos.total }} productos
                </div>
                <div class="flex justify-center gap-2 flex-wrap">
                    <template v-for="link in productos.links" :key="link.label">
                        <Link v-if="link.url" 
                              :href="link.url"
                              :class="[
                                  'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                                  link.active ? 'text-white shadow-lg' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                              ]"
                              :style="link.active ? { backgroundColor: 'var(--color-primary)' } : {}"
                              v-html="link.label" />
                        <span v-else 
                              class="px-4 py-2 rounded-lg text-sm font-medium text-gray-300"
                              v-html="link.label" />
                    </template>
                </div>
            </div>
        </main>

        <!-- Public Footer removed by user request -->
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
