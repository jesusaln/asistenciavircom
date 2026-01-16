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
    priceRange: Object,
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
const precioMin = ref(props.filters?.precio_min || props.priceRange?.min || 0)
const precioMax = ref(props.filters?.precio_max || props.priceRange?.max || 100000)
const showMobileFilters = ref(false)
const addedToCart = ref(null)
const searchFocused = ref(false)
const soloExistencia = ref(props.filters?.existencia ?? true) 
const soloLocal = ref(props.filters?.local ?? false)
const cvaPage = ref(1)
const hasMoreCva = ref(true)
const suggestions = ref([])
const isFiltering = ref(false)

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

// El catálogo unificado ya viene filtrado desde el backend
const allProducts = computed(() => {
    return props.productos?.data || []
})

const current_page = computed(() => props.productos?.current_page || 1)
const filteredCount = computed(() => props.productos?.total || 0)

const applyFilters = () => {
    isFiltering.value = true
    router.get(route('catalogo.index'), {
        search: search.value || undefined,
        categoria: selectedCategoria.value || undefined,
        marca: selectedMarca.value || undefined,
        orden: selectedOrden.value !== 'recientes' ? selectedOrden.value : undefined,
        existencia: soloExistencia.value ? 1 : undefined,
        local: soloLocal.value ? 1 : undefined,
        precio_min: precioMin.value != props.priceRange?.min ? precioMin.value : undefined,
        precio_max: precioMax.value != props.priceRange?.max ? precioMax.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: false,
        onSuccess: () => {
            isFiltering.value = false
        }
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
    precioMin.value = props.priceRange?.min || 0
    precioMax.value = props.priceRange?.max || 100000
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
    if (!producto) return null
    const img = (typeof producto === 'string') ? producto : (producto.imagen || producto.imagen_url)
    if (!img) return null
    
    let urlStr = String(img).trim()
    
    // Si el backend nos mandó /storage/http... por error, lo limpiamos
    if (urlStr.startsWith('/storage/http')) {
        urlStr = urlStr.replace('/storage/', '')
    }
    
    // Si ya es una URL absoluta o relativa al protocolo
    if (urlStr.toLowerCase().startsWith('http') || urlStr.startsWith('//')) {
        try {
            return route('img.proxy', { u: btoa(urlStr) })
        } catch (e) {
            return route('img.proxy', { url: urlStr })
        }
    }
    
    // Si ya tiene el prefijo storage o empieza con /
    if (urlStr.startsWith('/storage/') || urlStr.startsWith('/')) {
        return urlStr
    }
    
    return `/storage/${urlStr}`
}

const handleAskProduct = (producto) => {
    if (!empresaData.value?.whatsapp) return
    const phone = empresaData.value.whatsapp.replace(/\D/g, '')
    const precio = formatCurrency(producto.precio_con_iva)
    
    let text = `Hola, me interesa el producto:\n\n*${producto.nombre}*`;
    if (producto.origen === 'CVA') {
        text += `\n\nPrecio aprox: ${precio}\n\n¿Tienen disponibilidad para envío inmediato?`;
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

const handleSearchFocus = () => {
    searchFocused.value = true
}

const handleSearchBlur = () => {
    // Pequeño delay para permitir que el click en una sugerencia ocurra antes de cerrar
    setTimeout(() => {
        searchFocused.value = false
    }, 200)
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
    <Head :title="`Tienda - ${empresaData?.nombre || empresaData?.nombre_empresa || 'Catálogo'}`">
        <meta name="description" :content="`Explora nuestro extenso catálogo de ${empresaData?.nombre_empresa || 'Asistencia Vircom'}. Encuentra computadoras, cámaras de seguridad, redes y accesorios en ${empresaData?.ciudad || 'Hermosillo'}. Envíos a todo México.`" />
    </Head>
    
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
                                @focus="handleSearchFocus"
                                @blur="handleSearchBlur"
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
                                                    {{ sug.stock > 0 ? `Entrega Inmediata (${sug.stock})` : 'Bajo pedido' }}
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

        <!-- Main Content (Unified Search & Filters) -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Sidebar (Filters) -->
                <aside class="w-full lg:w-72 flex-shrink-0 space-y-8">
                    <!-- Móvil: Botón para mostrar filtros -->
                    <button @click="showMobileFilters = !showMobileFilters" 
                            class="lg:hidden w-full flex items-center justify-between px-6 py-4 bg-white rounded-2xl shadow-sm border border-gray-100 font-bold text-gray-700">
                        <span class="flex items-center gap-2">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                             Filtros y Categorías
                        </span>
                        <svg class="w-5 h-5 transition-transform" :class="{'rotate-180': showMobileFilters}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div :class="[
                        'lg:block space-y-8',
                        showMobileFilters ? 'block animate-fade-in' : 'hidden'
                    ]">
                        <!-- Rango de Precio -->
                        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                            <h3 class="text-xs font-black text-gray-900 uppercase tracking-[0.2em] mb-6 flex items-center justify-between">
                                Presupuesto
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            </h3>
                            <div class="space-y-6">
                                <div class="flex justify-between text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    <span>Desde {{ formatCurrency(precioMin) }}</span>
                                </div>
                                <input type="range" 
                                       v-model="precioMin" 
                                       :min="priceRange?.min" 
                                       :max="priceRange?.max" 
                                       step="100"
                                       @change="applyFilters"
                                       class="w-full h-1.5 bg-gray-100 rounded-lg appearance-none cursor-pointer accent-[var(--color-primary)]">
                                <div class="flex justify-between text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    <span>Hasta {{ formatCurrency(precioMax) }}</span>
                                </div>
                                <input type="range" 
                                       v-model="precioMax" 
                                       :min="priceRange?.min" 
                                       :max="priceRange?.max" 
                                       step="100"
                                       @change="applyFilters"
                                       class="w-full h-1.5 bg-gray-100 rounded-lg appearance-none cursor-pointer accent-[var(--color-primary)]">
                            </div>
                        </div>

                        <!-- Categorías Populares -->
                        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                            <h3 class="text-xs font-black text-gray-900 uppercase tracking-[0.2em] mb-4">Categorías</h3>
                            <div class="space-y-1">
                                <button v-for="cat in categorias" :key="cat.id"
                                        @click="selectedCategoria = (selectedCategoria == cat.id ? '' : cat.id); applyFilters()"
                                        :class="[
                                            'w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between group',
                                            selectedCategoria == cat.id 
                                                ? 'bg-gray-900 text-white shadow-lg' 
                                                : 'text-gray-500 hover:bg-gray-50'
                                        ]">
                                    <span class="truncate">{{ cat.nombre }}</span>
                                    <span :class="selectedCategoria == cat.id ? 'text-gray-400' : 'text-gray-300'" class="text-[10px] font-black">{{ cat.productos_count }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Marcas -->
                        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                            <h3 class="text-xs font-black text-gray-900 uppercase tracking-[0.2em] mb-4">Marcas</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="marca in marcas" :key="marca.id"
                                        @click="selectedMarca = (selectedMarca == marca.id ? '' : marca.id); applyFilters()"
                                        :class="[
                                            'px-2 py-2.5 rounded-xl text-[9px] font-black uppercase text-center border-2 transition-all truncate',
                                            selectedMarca == marca.id 
                                                ? 'bg-[var(--color-primary)] border-[var(--color-primary)] text-white shadow-md' 
                                                : 'bg-white border-gray-50 text-gray-400 hover:border-gray-200'
                                        ]">
                                    {{ marca.nombre }}
                                </button>
                            </div>
                        </div>

                        <!-- Botón Limpiar -->
                        <button v-if="selectedCategoria || selectedMarca || search || precioMin > priceRange?.min"
                                @click="clearFilters"
                                class="w-full py-4 text-[10px] font-black text-red-500 uppercase tracking-widest hover:bg-red-50 rounded-2xl transition-all border border-dashed border-red-100">
                            Limpiar todos los filtros
                        </button>
                    </div>
                </aside>

                <!-- Product List Area -->
                <div class="flex-1 min-w-0">
                    <!-- Toolbar Principal -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8 bg-white p-4 rounded-[2rem] border border-gray-100 shadow-sm">
                        <div class="flex items-center gap-6">
                            <div class="flex flex-col">
                                <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest leading-none mb-1">Mostrando</p>
                                <p class="text-sm font-black text-gray-900 leading-none">
                                    {{ filteredCount }} <span class="text-gray-400 font-bold ml-1 uppercase text-[10px]">Productos</span>
                                </p>
                            </div>
                            
                            <div class="h-8 w-px bg-gray-100 hidden md:block"></div>
                            
                            <!-- Toggles rápidos con diseño Premium -->
                            <div class="flex gap-4">
                                <button @click="soloLocal = !soloLocal; applyFilters()"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2',
                                            soloLocal ? 'bg-green-500 text-white shadow-lg shadow-green-500/20' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'
                                        ]">
                                    <div class="w-1.5 h-1.5 rounded-full" :class="soloLocal ? 'bg-white animate-pulse' : 'bg-gray-300'"></div>
                                    Entrega Inmediata
                                </button>
                                <button @click="soloExistencia = !soloExistencia; applyFilters()"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2',
                                            soloExistencia ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/20' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'
                                        ]">
                                    <div class="w-1.5 h-1.5 rounded-full" :class="soloExistencia ? 'bg-white' : 'bg-gray-300'"></div>
                                    Con Stock
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <select v-model="selectedOrden" @change="applyFilters"
                                    class="w-full md:w-auto px-5 py-2.5 bg-gray-50 border-0 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-500 focus:ring-2 focus:ring-[var(--color-primary-soft)] cursor-pointer">
                                <option value="recientes">Novedades</option>
                                <option value="precio_asc">Precio: Bajo a Alto</option>
                                <option value="precio_desc">Precio: Alto a Bajo</option>
                                <option value="nombre">Nombre A-Z</option>
                            </select>
                        </div>
                    </div>

                    <!-- Grid de Productos -->
                    <div v-if="allProducts.length" class="grid grid-cols-2 lg:grid-cols-3 gap-6">
                        <article v-for="producto in allProducts" :key="producto.id"
                                 class="group bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 hover:shadow-2xl hover:border-white transition-all duration-500 flex flex-col relative">
                            
                            <!-- Badge de Origen Premium -->
                            <div v-if="producto.stock_cedis > 0 && !(producto.stock_local > 0)" class="absolute top-4 right-4 z-30">
                                <div class="bg-blue-600 text-white px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-[0.2em] shadow-lg shadow-blue-600/30">
                                    Envío Nacional
                                </div>
                            </div>

                            <!-- Imagen con Contenedor de Diseño -->
                            <Link :href="route('catalogo.show', producto.id)" class="block relative aspect-square bg-gray-50 overflow-hidden m-2 rounded-[2rem]">
                                <img v-if="getImageUrl(producto)" 
                                     :src="getImageUrl(producto)" 
                                     :alt="producto.nombre"
                                     class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-700 p-8" />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                            </Link>

                            <!-- Información -->
                            <div class="px-6 py-5 flex-1 flex flex-col">
                                <div class="mb-4">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span v-if="producto.stock_local > 0" class="text-[9px] font-black text-green-600 bg-green-50 px-2 py-0.5 rounded-md uppercase tracking-tighter">
                                            Entrega Inmediata ({{ producto.stock_local }})
                                        </span>
                                        <span v-else-if="producto.stock_cedis > 0" class="text-[9px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md uppercase tracking-tighter">
                                            Bajo Pedido ({{ producto.stock_cedis }})
                                        </span>
                                        <span v-else class="text-[9px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md uppercase tracking-tighter">
                                            Bajo Pedido
                                        </span>
                                    </div>
                                    <p class="text-[9px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] mb-1 leading-none">
                                        {{ producto.marca?.nombre || producto.marca }}
                                    </p>
                                    <Link :href="route('catalogo.show', producto.id)">
                                        <h3 class="font-bold text-gray-900 text-xs sm:text-sm line-clamp-2 leading-relaxed group-hover:text-[var(--color-primary)] transition-colors min-h-[40px]">
                                            {{ producto.nombre }}
                                        </h3>
                                    </Link>
                                </div>

                                <div class="mt-auto flex items-center justify-between pt-4 border-t border-gray-50">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Inversión</span>
                                        <span class="text-lg font-black text-gray-900 leading-none">
                                            {{ formatCurrency(producto.precio_con_iva) }}
                                        </span>
                                    </div>
                                    
                                    <button v-if="producto.stock_local > 0 || producto.stock_cedis > 0" 
                                            @click="handleAddToCart(producto)"
                                            :disabled="addedToCart === producto.id"
                                            :class="[
                                                'w-11 h-11 rounded-2xl transition-all duration-500 flex items-center justify-center shadow-lg',
                                                addedToCart === producto.id 
                                                    ? 'bg-green-500 text-white shadow-green-500/30' 
                                                    : 'bg-gray-900 text-white hover:bg-[var(--color-primary)] hover:shadow-[var(--color-primary)]/40 hover:-translate-y-1'
                                            ]">
                                        <svg v-if="addedToCart === producto.id" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    </button>
                                    <button v-else 
                                            @click="handleAskProduct(producto)"
                                            class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 hover:bg-amber-100 transition-all flex items-center justify-center group/wa shadow-sm">
                                        <svg class="w-6 h-6 group-hover/wa:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Estado Vacío -->
                    <div v-else class="text-center py-24 bg-white rounded-[4rem] border border-gray-100 shadow-sm flex flex-col items-center">
                        <div class="w-32 h-32 mb-8 bg-gray-50 rounded-full flex items-center justify-center relative">
                            <svg class="w-16 h-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <div class="absolute inset-0 border-2 border-dashed border-gray-100 rounded-full animate-spin-slow"></div>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 mb-2">Búsqueda sin resultados</h3>
                        <p class="text-gray-400 mb-10 max-w-xs mx-auto font-medium">Lamentamos no encontrar lo que buscas. Intenta con una marca general o ajustando el presupuesto.</p>
                        <button @click="clearFilters" 
                                class="px-10 py-4 bg-gray-900 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:-translate-y-2 transition-all shadow-xl shadow-gray-200">
                            Reiniciar Búsqueda
                        </button>
                    </div>

                    <!-- Paginación Premium -->
                    <div v-if="productos?.last_page > 1" class="mt-20 flex flex-col items-center gap-8">
                        <div class="flex justify-center gap-3 flex-wrap">
                            <template v-for="link in productos.links" :key="link.label">
                                <Link v-if="link.url" 
                                      :href="link.url"
                                      :class="[
                                          'w-12 h-12 rounded-2xl text-[10px] font-black transition-all flex items-center justify-center shadow-sm',
                                          link.active 
                                            ? 'bg-gray-900 text-white shadow-xl shadow-gray-900/20' 
                                            : 'bg-white text-gray-400 hover:bg-gray-50 border border-gray-100'
                                      ]"
                                      v-html="link.label.replace('Previous', '←').replace('Next', '→')" />
                                <span v-else 
                                      class="w-12 h-12 rounded-2xl text-[10px] font-black bg-gray-50 text-gray-200 flex items-center justify-center border border-gray-100"
                                      v-html="link.label.replace('Previous', '←').replace('Next', '→')" />
                            </template>
                        </div>
                        <div class="px-6 py-2 bg-gray-100 rounded-full">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.4em]">
                                Página {{ productos.current_page }} de {{ productos.last_page }}
                            </p>
                        </div>
                    </div>
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
