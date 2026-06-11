<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted } from 'vue'
import { useCart } from '@/composables/useCart'
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import SocialProofNotification from '@/Components/SocialProofNotification.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

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

const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

// Integrar modo oscuro centralizado
useDarkMode(empresaData.value);

const { items, itemCount, addItem, isInCart } = useCart()

const search = ref(props.filters?.search || '')
const selectedCategoria = ref(props.filters?.categoria || '')
const selectedMarca = ref(props.filters?.marca || '')
const selectedOrden = ref(props.filters?.orden || 'mas_vendidos')
const precioMin = ref(props.filters?.precio_min || props.priceRange?.min || 0)
const precioMax = ref(props.filters?.precio_max || props.priceRange?.max || 100000)
const showMobileFilters = ref(false)
const addedToCart = ref(null)
const searchFocused = ref(false)
const soloExistencia = ref(props.filters?.existencia ?? false) 
const soloLocal = ref(props.filters?.local ?? false)
const soloSinFoto = ref(props.filters?.sin_foto ?? false)
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
        const response = await axios.get(route('api.tienda.search-suggestions'), { 
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
        /\b(Laptop)\b/gi, /\b(Desktop)\b/gi, /\b(Impresora)\b/gi
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
        orden: selectedOrden.value !== 'mas_vendidos' ? selectedOrden.value : undefined,
        existencia: soloExistencia.value ? 1 : undefined,
        local: soloLocal.value ? 1 : undefined,
        sin_foto: soloSinFoto.value ? 1 : undefined,
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
    selectedOrden.value = 'mas_vendidos'
    soloSinFoto.value = false
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
        if (urlStr.includes('grupocva.com')) {
            try {
                return route('img.proxy', { u: btoa(urlStr) })
            } catch (e) {
                return route('img.proxy', { url: urlStr })
            }
        }
        return urlStr;
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

// Comparador de Productos (Climas / Vircom)
const compareList = ref([])
const showCompareModal = ref(false)

const isComparing = (id) => {
    return compareList.value.some(p => p.id === id)
}

const toggleCompare = (producto) => {
    const idx = compareList.value.findIndex(p => p.id === producto.id)
    if (idx >= 0) {
        compareList.value.splice(idx, 1)
    } else {
        if (compareList.value.length >= 4) {
            alert('Puedes comparar hasta 4 productos a la vez.')
            return
        }
        compareList.value.push(producto)
    }
    localStorage.setItem('asistencia_compare_list', JSON.stringify(compareList.value))
}

const removeCompare = (id) => {
    compareList.value = compareList.value.filter(p => p.id !== id)
    localStorage.setItem('asistencia_compare_list', JSON.stringify(compareList.value))
}

const clearCompare = () => {
    compareList.value = []
    localStorage.setItem('asistencia_compare_list', JSON.stringify([]))
}

const parseProductSpecs = (producto) => {
    let rawSpecs = producto?.especificaciones
    
    if (Array.isArray(rawSpecs)) {
        const unified = {}
        rawSpecs.forEach(s => {
            if (s.nombre) unified[s.nombre] = s.valor || 'N/A'
        });
        return unified
    }

    if (rawSpecs && typeof rawSpecs === 'object' && Object.keys(rawSpecs).length > 0) {
        return rawSpecs
    }
    
    const text = producto?.ficha_tecnica
    if (!text) return {}
    
    const specs = {}
    const knownKeys = [
        'MODELO', 'UPC', 'SAT', 'NUMERO DE PARTE', 'PANTALLA', 'TIPO DE PANEL',
        'TIEMPO DE RESPUESTA', 'BRILLO', 'CONTRASTE', 'ANGULO VISIBLE', 'COLORES',
        'ENTRADA DE SEÑAL', 'BOCINAS', 'MONTAJE VESA', 'DIMENSIONES', 
        'FUENTE DE ENERGIA', 'ERGO STAND', 'OTROS', 'GARANTIA',
        'PROCESADOR', 'RAM', 'ALMACENAMIENTO', 'DISCO DURO', 'SSD',
        'SISTEMA OPERATIVO', 'RESOLUCION', 'PESO', 'COLOR', 'CONECTIVIDAD',
        'PUERTOS', 'BATERIA', 'CAMARA', 'WIFI', 'BLUETOOTH', 'HDMI',
        'CAPACIDAD', 'TIPO', 'VOLTAJE', 'SEER', 'FRIO/CALOR'
    ]
    
    for (const key of knownKeys) {
        const regex = new RegExp(`(${key}):?\\s*([^\\n]+?)(?=(?:${knownKeys.join('|')})|$)`, 'gi')
        const match = regex.exec(text)
        if (match && match[2]) {
            const value = match[2].trim().replace(/;+$/, '').trim()
            if (value && value.length > 1) {
                specs[key] = value
            }
        }
    }
    
    if (Object.keys(specs).length === 0) {
        const lines = text.split(/(?=[A-ZÁÉÍÓÚÑ]{3,}:?\s)/)
        lines.forEach(line => {
            const colonMatch = line.match(/^([A-ZÁÉÍÓÚÑ\s]+?):\s*(.+)$/i)
            if (colonMatch) {
                specs[colonMatch[1].trim()] = colonMatch[2].trim()
            }
        })
    }
    
    return specs
}

const allSpecKeys = computed(() => {
    const keysSet = new Set()
    compareList.value.forEach(p => {
        const specs = parseProductSpecs(p)
        Object.keys(specs).forEach(k => keysSet.add(k))
    })
    return Array.from(keysSet)
})

const openWhatsAppForCompare = (producto) => {
    if (!empresaData.value?.whatsapp) return
    const phone = empresaData.value.whatsapp.replace(/\D/g, '')
    const message = encodeURIComponent(`Hola, me interesa preguntar por disponibilidad del producto: *${producto.nombre}*`)
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
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
// SEO dinámico basado en filtros
const pageTitle = computed(() => {
    let title = 'Tienda de Aires Acondicionados'
    if (selectedMarca.value) {
        const marca = props.marcas.find(m => m.id == selectedMarca.value)
        if (marca) title = `${marca.nombre} - Distribuidor Autorizado`
    } else if (selectedCategoria.value) {
        const cat = props.categorias.find(c => c.id == selectedCategoria.value)
        if (cat) title = `${cat.nombre} en Hermosillo`
    }
    
    if (search.value) title = `Buscar: ${search.value}`
    
    return `${title} | ${empresaData.value?.nombre_empresa || 'Climas del Desierto'}`
})

const metaDescription = computed(() => {
    let desc = `Explora el catálogo de ${empresaData.value?.nombre_empresa || 'Climas del Desierto'}. Envíos a todo México y entrega inmediata en Hermosillo.`
    
    if (selectedMarca.value) {
        const marca = props.marcas.find(m => m.id == selectedMarca.value)
        if (marca) desc = `Venta y distribución de equipos ${marca.nombre} en Sonora. Encuentra los mejores precios en minisplits ${marca.nombre} e Inverters con garantía oficial.`
    }
    
    return desc
})

const faqs = ref([
    {
        question: '¿Cuánto cuesta el envío o la entrega?',
        answer: 'La entrega local o envío nacional tiene un costo de $100 MXN. Sin embargo, si tu compra total es de $1,500 MXN o más, ¡el envío es completamente gratis!',
        open: false
    },
    {
        question: '¿Qué garantía tienen los productos y servicios?',
        answer: 'Todos nuestros productos y equipos (como minisplits Mirage) cuentan con su garantía oficial directa de fábrica. Adicionalmente, todas nuestras instalaciones y servicios técnicos de mano de obra cuentan con garantía por escrito para tu tranquilidad.',
        open: false
    },
    {
        question: '¿Cuáles son los tiempos de entrega?',
        answer: 'Para productos con existencias locales indicados como "Entrega Inmediata", la entrega en Hermosillo es el mismo día o al día siguiente. Para productos "Bajo Pedido" (que se envían desde nuestro CEDIS nacional), el plazo de entrega estimado es de 2 a 5 días hábiles.',
        open: false
    },
    {
        question: '¿Cómo puedo agendar una instalación o mantenimiento?',
        answer: 'Puedes agendar directamente desde nuestro portal, rellenando el formulario en la pestaña de Citas/Contacto, o enviándonos un mensaje directo a través de nuestro widget flotante de WhatsApp. ¡Te atenderemos de inmediato!',
        open: false
    }
])

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open
}
</script>

<template>
    <Head :title="pageTitle">
        <meta name="description" :content="metaDescription" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
    </Head>
    
    <div class="min-h-screen bg-white dark:bg-gray-900 font-sans transition-colors duration-300">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />

        <!-- Notificación de Prueba Social (FOMO) - productos reales (incluyendo CVA) -->
        <SocialProofNotification :productos="allProducts" :duration="5000" :initialDelay="15000" :interval="600000" />

        <!-- Navbar -->
        <PublicNavbar :empresa="empresaData" activeTab="tienda" />

        <!-- Hero con Búsqueda -->
        <section class="py-20 bg-gray-900 relative overflow-hidden transition-colors">
            <!-- Background Image -->
            <div class="absolute inset-0">
                <img 
                    src="/storage/servicios/tienda-hero.webp" 
                    alt="Tienda Mirage" 
                    class="w-full h-full object-cover opacity-25"
                >
                <div class="absolute inset-0 bg-gradient-to-b from-gray-900/60 via-gray-900/50 to-gray-900/90"></div>
            </div>

            <!-- Efecto cristal de fondo -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full opacity-20 blur-3xl" 
                     style="background-color: var(--color-primary);"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full opacity-15 blur-3xl" 
                     style="background-color: var(--color-secondary);"></div>
            </div>
            
            <div class="w-full px-4 sm:px-6 text-center relative z-10">
                <h1 class="text-3xl lg:text-5xl font-black text-white mb-4 tracking-tight">
                    Explora nuestros productos
                </h1>
                <p class="text-gray-300 mb-8 w-full font-medium">
                    Encuentra lo que necesitas con la mejor calidad y precio
                </p>
                
                <!-- Barra de búsqueda con efecto cristal -->
                <div class="relative w-full z-50">
                    <div :class="[
                        'relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300',
                        searchFocused ? 'shadow-xl ring-2 rounded-b-none border-b-0' : 'shadow-md'
                    ]" :style="searchFocused ? { '--tw-ring-color': 'var(--color-primary)' } : {}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input 
                                v-model="search"
                                @focus="handleSearchFocus"
                                @blur="handleSearchBlur"
                                @input="debouncedSuggestions"
                                type="text" 
                                placeholder="Buscar productos por nombre, código o descripción..." 
                                class="w-full h-14 px-4 bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-0"
                            />
                            <button v-if="search" 
                                    @click="clearFilters"
                                    class="mr-3 p-1.5 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Autocomplete Dropdown (Premium Version) -->
                    <div v-show="searchFocused && suggestions.length > 0" 
                         class="absolute w-full bg-white dark:bg-gray-800 rounded-b-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[100] transition-all max-h-[450px] overflow-y-auto ring-1 ring-black/5">
                        <div class="p-2.5 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center bg-white/50 dark:bg-gray-800/50 sticky top-0 z-10">
                            <span class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase ml-2">Sugerencias de productos</span>
                            <button @click="searchFocused = false" class="text-[10px] font-bold text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 px-2 uppercase">Cerrar</button>
                        </div>
                        <ul class="divide-y divide-gray-50 dark:divide-gray-700">
                            <li v-for="sug in suggestions" :key="sug.id">
                                <Link :href="route('catalogo.show', sug.id)" 
                                      class="group flex items-center gap-4 px-4 py-3 hover:bg-white dark:hover:bg-gray-700 transition-all relative">
                                    <div class="w-14 h-14 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-sm group-hover:scale-105 transition-transform duration-300">
                                        <img :src="getImageUrl({ imagen: sug.image })" alt="" class="w-full h-full object-contain p-1" @error="(e) => (e.target.src = '/img/placeholder-product.webp')">
                                    </div>
                                    <div class="flex-1 min-w-0 text-left">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span v-if="sug.origen === 'CVA'" class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded text-[9px] font-black uppercase tracking-tighter">CVA</span>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate leading-tight group-hover:text-[var(--color-primary)] transition-colors">
                                                {{ sug.label }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">{{ sug.category }}</p>
                                            <div class="flex items-center gap-1.5">
                                                <span v-if="sug.origen === 'CVA'" 
                                                      :class="[
                                                          'h-2 w-2 rounded-full',
                                                          sug.stock > 0 ? 'bg-green-500 animate-pulse' : 'bg-amber-400'
                                                      ]"></span>
                                                <span class="text-[11px] font-black uppercase tracking-tight" :class="sug.stock > 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                                                    {{ sug.stock > 0 ? `Entrega Inmediata (${sug.stock})` : 'Bajo pedido' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[14px] font-black text-gray-900 dark:text-white leading-none">
                                            {{ formatCurrency(sug.price) }}
                                        </p>
                                        <p class="text-[9px] text-emerald-500 dark:text-emerald-400 font-black uppercase mt-1 tracking-widest">IVA Incluido</p>
                                    </div>
                                </Link>
                            </li>
                            <li>
                                <button @click="applyFilters" class="w-full py-3 text-center bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-xs font-bold text-gray-500 dark:text-gray-300 transition-colors uppercase tracking-widest">
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
                                : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
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
                                : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                        ]"
                        :style="selectedCategoria == cat.id ? { backgroundColor: 'var(--color-primary)' } : {}">
                        {{ cat.nombre }}
                    </button>
                </div>

                <!-- Smart Tags / Sugerencias de búsqueda -->
                <div v-if="smartFilters.length > 0" class="flex flex-wrap justify-center gap-2 mt-4 animate-fade-in-up">
                    <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider py-1">Quizás buscas:</span>
                    <button v-for="tag in smartFilters" 
                            :key="tag"
                            @click="handleSmartFilter(tag)"
                            class="px-3 py-1 rounded-md text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                        + {{ tag }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Main Content (Unified Search & Filters) -->
        <main class="w-full px-4 sm:px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Sidebar (Filters) -->
                <aside class="w-full lg:w-72 flex-shrink-0 space-y-8">
                    <!-- Móvil: Botón para mostrar filtros -->
                    <button @click="showMobileFilters = !showMobileFilters" 
                            class="lg:hidden w-full flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-200 transition-colors">
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
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 transition-colors">
                            <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-[0.2em] mb-6 flex items-center justify-between transition-colors">
                                Presupuesto
                                <span class="w-2 h-2 rounded-full bg-green-500 dark:bg-green-400"></span>
                            </h3>
                            <div class="space-y-6">
                                <div class="flex justify-between text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                    <span>Desde {{ formatCurrency(precioMin) }}</span>
                                </div>
                                <input type="range" 
                                       v-model="precioMin" 
                                       :min="priceRange?.min" 
                                       :max="priceRange?.max" 
                                       step="100"
                                       @change="applyFilters"
                                       class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-[var(--color-primary)]">
                                <div class="flex justify-between text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                    <span>Hasta {{ formatCurrency(precioMax) }}</span>
                                </div>
                                <input type="range" 
                                       v-model="precioMax" 
                                       :min="priceRange?.min" 
                                       :max="priceRange?.max" 
                                       step="100"
                                       @change="applyFilters"
                                       class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-[var(--color-primary)]">
                            </div>
                        </div>

                        <!-- Categorías Populares -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 transition-colors">
                            <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-[0.2em] mb-4 transition-colors">Categorías</h3>
                            <div class="space-y-1">
                                <button v-for="cat in categorias" :key="cat.id"
                                        @click="selectedCategoria = (selectedCategoria == cat.id ? '' : cat.id); applyFilters()"
                                        :class="[
                                            'w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between group',
                                            selectedCategoria == cat.id 
                                                ? 'bg-gray-900 text-white shadow-lg' 
                                                : 'bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600'
                                        ]">
                                    <span class="truncate">{{ cat.nombre }}</span>
                                    <span :class="selectedCategoria == cat.id ? 'text-gray-400 dark:text-gray-500' : 'text-gray-300 dark:text-gray-600'" class="text-[10px] font-black">{{ cat.productos_count }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Marcas -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 transition-colors">
                            <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-[0.2em] mb-4 transition-colors">Marcas</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="marca in marcas" :key="marca.id"
                                        @click="selectedMarca = (selectedMarca == marca.id ? '' : marca.id); applyFilters()"
                                        :class="[
                                            'px-2 py-2.5 rounded-xl text-[9px] font-black uppercase text-center border-2 transition-all truncate',
                                            selectedMarca == marca.id 
                                                ? 'bg-[var(--color-primary)] border-[var(--color-primary)] text-white shadow-md' 
                                                : 'bg-white dark:bg-gray-700 border-gray-50 dark:border-gray-600 text-gray-400 dark:text-gray-300 hover:border-gray-200 dark:hover:border-gray-500'
                                        ]">
                                    {{ marca.nombre }}
                                </button>
                            </div>
                        </div>

                        <!-- Botón Limpiar -->
                        <button v-if="selectedCategoria || selectedMarca || search || precioMin > priceRange?.min"
                                @click="clearFilters"
                                class="w-full py-4 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-widest hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl transition-all border border-dashed border-red-100 dark:border-red-700">
                            Limpiar todos los filtros
                        </button>
                    </div>
                </aside>

                <!-- Product List Area -->
                <div class="flex-1 min-w-0">
                    <!-- Toolbar Principal -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8 bg-white dark:bg-gray-800 p-4 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm transition-colors">
                        <div class="flex items-center gap-6">
                            <div class="flex flex-col">
                                <p class="text-[10px] font-black text-gray-300 dark:text-gray-600 uppercase tracking-widest leading-none mb-1 transition-colors">Mostrando</p>
                                <p class="text-sm font-black text-gray-900 dark:text-white leading-none transition-colors">
                                    {{ filteredCount }} <span class="text-gray-400 font-bold ml-1 uppercase text-[10px]">Productos</span>
                                </p>
                            </div>
                            
                            <div class="h-8 w-px bg-gray-100 dark:bg-gray-700 hidden md:block"></div>
                            
                            <!-- Toggles rápidos con diseño Premium -->
                            <div class="flex gap-4">
                                <button @click="soloLocal = !soloLocal; applyFilters()"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2',
                                            soloLocal ? 'bg-green-500 text-white shadow-lg shadow-green-500/20' : 'bg-white dark:bg-gray-700 text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                                        ]">
                                    <div class="w-1.5 h-1.5 rounded-full" :class="soloLocal ? 'bg-white animate-pulse' : 'bg-gray-300 dark:bg-gray-500'"></div>
                                    Entrega Inmediata
                                </button>
                                <button @click="soloExistencia = !soloExistencia; applyFilters()"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2',
                                            soloExistencia ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/20' : 'bg-white dark:bg-gray-700 text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                                        ]">
                                    <div class="w-1.5 h-1.5 rounded-full" :class="soloExistencia ? 'bg-white' : 'bg-gray-300 dark:bg-gray-500'"></div>
                                    Con Stock
                                </button>
                                <button @click="soloSinFoto = !soloSinFoto; applyFilters()"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2',
                                            soloSinFoto ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'bg-white dark:bg-gray-700 text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
                                        ]">
                                    <div class="w-1.5 h-1.5 rounded-full" :class="soloSinFoto ? 'bg-white' : 'bg-gray-300 dark:bg-gray-500'"></div>
                                    Sin Foto
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <select v-model="selectedOrden" @change="applyFilters"
                                    class="w-full md:w-auto px-5 py-2.5 bg-white dark:bg-gray-700 border-0 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-300 focus:ring-2 focus:ring-[var(--color-primary-soft)] cursor-pointer transition-colors">
                                <option value="mas_vendidos">Más Vendidos</option>
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
                                 class="group bg-white dark:bg-gray-800 rounded-[2.5rem] overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:border-white dark:hover:border-gray-600 transition-all duration-500 flex flex-col relative">
                            
                            <!-- Badge de Oferta -->
                            <div v-if="producto.destacado" class="absolute top-4 left-4 z-30">
                                <div class="bg-[var(--color-primary)] animate-pulse text-white px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-[var(--color-primary)]/40 border border-white/20">
                                    ¡OFERTA ESPECIAL!
                                </div>
                            </div>

                            <!-- Badge de Origen Premium -->
                            <div v-if="producto.stock_cedis > 0 && !(producto.stock_local > 0)" class="absolute top-4 right-4 z-30">
                                <div class="bg-blue-600 text-white px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-[0.2em] shadow-lg shadow-blue-600/30">
                                    Envío Nacional
                                </div>
                            </div>

                            <!-- Imagen con Contenedor de Diseño -->
                            <Link :href="route('catalogo.show', producto.id)" class="block relative aspect-square bg-white dark:bg-gray-900 overflow-hidden m-2 rounded-[2rem] transition-colors">
                                <template v-if="getImageUrl(producto)">
                                    <img :src="getImageUrl(producto)" 
                                         :alt="producto.nombre"
                                         class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-700 p-2" />
                                </template>
                                <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800/50">
                                    <svg class="w-12 h-12 text-gray-200 dark:text-gray-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Sin Imagen</span>
                                </div>

                                <!-- Badge específico para items sin foto -->
                                <div v-if="!getImageUrl(producto)" class="absolute inset-x-0 bottom-4 flex justify-center px-4">
                                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest backdrop-blur-sm">
                                        Pendiente de Foto
                                    </div>
                                </div>
                            </Link>

                            <!-- Información -->
                            <div class="px-6 py-5 flex-1 flex flex-col">
                                <div class="mb-4">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span v-if="producto.stock_local > 0" class="text-[9px] font-black text-green-600 dark:text-green-300 bg-green-50 dark:bg-green-900/20 px-2 py-0.5 rounded-md uppercase tracking-tighter">
                                            Entrega Inmediata ({{ producto.stock_local }})
                                        </span>
                                        <span v-else-if="producto.stock_cedis > 0" class="text-[9px] font-black text-blue-600 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-md uppercase tracking-tighter">
                                            Bajo Pedido ({{ producto.stock_cedis }})
                                        </span>
                                        <span v-else class="text-[9px] font-black text-amber-600 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded-md uppercase tracking-tighter">
                                            Bajo Pedido
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-[9px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] leading-none">
                                            {{ producto.marca?.nombre || producto.marca }}
                                        </p>
                                        <!-- Checkbox Comparar -->
                                        <label class="flex items-center gap-1 cursor-pointer text-[10px] text-gray-500 hover:text-[var(--color-primary)] font-bold">
                                            <input type="checkbox" 
                                                   :checked="isComparing(producto.id)" 
                                                   @change="toggleCompare(producto)" 
                                                   class="rounded text-[var(--color-primary)] focus:ring-[var(--color-primary)] w-3.5 h-3.5 border-gray-300 dark:bg-gray-750 dark:border-gray-650" />
                                            <span>Comparar</span>
                                        </label>
                                    </div>
                                    <Link :href="route('catalogo.show', producto.id)">
                                        <h3 class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm line-clamp-2 leading-relaxed group-hover:text-[var(--color-primary)] transition-colors min-h-[40px]">
                                            {{ producto.nombre }}
                                        </h3>
                                    </Link>
                                </div>

                                <div class="mt-auto flex items-center justify-between pt-4 border-t border-gray-50 dark:border-gray-700 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-emerald-500 dark:text-emerald-400 uppercase tracking-widest mb-1 transition-colors">Precio IVA Incluido</span>
                                        <span class="text-lg font-black text-gray-900 dark:text-white leading-none transition-colors">
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
                                                    : 'bg-gray-900 dark:bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary)] dark:hover:bg-[var(--color-primary-soft)] hover:shadow-[var(--color-primary)]/40 hover:-translate-y-1'
                                            ]">
                                        <svg v-if="addedToCart === producto.id" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    </button>
                                    <button v-else 
                                            @click="handleAskProduct(producto)"
                                            class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-all flex items-center justify-center group/wa shadow-sm">
                                        <svg class="w-6 h-6 group-hover/wa:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Estado Vacío -->
                    <div v-else class="text-center py-24 bg-white dark:bg-gray-800 rounded-[4rem] border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col items-center transition-colors">
                        <div class="w-32 h-32 mb-8 bg-white dark:bg-gray-900 rounded-full flex items-center justify-center relative">
                            <svg class="w-16 h-16 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <div class="absolute inset-0 border-2 border-dashed border-gray-100 dark:border-gray-700 rounded-full animate-spin-slow"></div>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2 transition-colors">Búsqueda sin resultados</h3>
                        <p class="text-gray-400 dark:text-gray-500 mb-10 w-full font-medium transition-colors">Lamentamos no encontrar lo que buscas. Intenta con una marca general o ajustando el presupuesto.</p>
                        <button @click="clearFilters" 
                                class="px-10 py-4 bg-gray-900 dark:bg-[var(--color-primary)] text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:-translate-y-2 transition-all shadow-xl shadow-gray-200 dark:shadow-[var(--color-primary)]/40">
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
                                            ? 'bg-gray-900 dark:bg-[var(--color-primary)] text-white shadow-xl shadow-gray-900/20 dark:shadow-[var(--color-primary)]/40' 
                                            : 'bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 hover:bg-white dark:hover:bg-gray-700 border border-gray-100 dark:border-gray-700'
                                      ]"
                                      v-html="link.label.replace('Previous', '←').replace('Next', '→')" />
                                <span v-else 
                                      class="w-12 h-12 rounded-2xl text-[10px] font-black bg-white dark:bg-gray-800 text-gray-200 dark:text-gray-600 flex items-center justify-center border border-gray-100 dark:border-gray-700"
                                      v-html="link.label.replace('Previous', '←').replace('Next', '→')" />
                            </template>
                        </div>
                        <div class="px-6 py-2 bg-gray-100 dark:bg-gray-700 rounded-full">
                            <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.4em]">
                                Página {{ productos.current_page }} de {{ productos.last_page }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- FAQ Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16 border-t border-slate-100 dark:border-slate-800 transition-colors duration-300">
            <div class="text-center mb-12">
                <span class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] mb-2 block">Respuestas Rápidas</span>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Preguntas Frecuentes</h2>
                <p class="text-slate-400 dark:text-slate-500 mt-2 text-sm font-medium">Todo lo que necesitas saber sobre envíos, garantías y entregas</p>
            </div>
            
            <div class="max-w-4xl mx-auto space-y-4">
                <div v-for="(faq, idx) in faqs" :key="idx" 
                     class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden transition-all duration-300">
                    <button @click="toggleFaq(idx)" 
                            class="w-full px-6 py-5 flex items-center justify-between text-left font-bold text-slate-900 dark:text-white hover:text-[var(--color-primary)] dark:hover:text-[var(--color-primary)] transition-colors focus:outline-none">
                        <span class="text-sm md:text-base">{{ faq.question }}</span>
                        <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-300" 
                             :class="{ 'rotate-180 text-[var(--color-primary)]': faq.open }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <Transition name="faq-slide">
                        <div v-show="faq.open" class="px-6 pb-6 text-xs md:text-sm text-slate-500 dark:text-slate-400 leading-relaxed border-t border-slate-50 dark:border-slate-700/50 pt-4">
                            {{ faq.answer }}
                        </div>
                    </Transition>
                </div>
            </div>
        </section>

        <!-- Sticky Bottom Compare Bar -->
        <div v-if="compareList.length > 0" 
             class="fixed bottom-6 inset-x-4 max-w-4xl mx-auto bg-slate-900/90 dark:bg-gray-950/90 backdrop-blur-xl border border-white/10 text-white rounded-3xl p-4 shadow-2xl z-40 transition-all duration-300 transform translate-y-0">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-black uppercase tracking-widest bg-[var(--color-primary)] text-white px-2.5 py-1 rounded-lg">
                        Comparador
                    </span>
                    <span class="text-xs font-bold text-gray-300">
                        {{ compareList.length }} {{ compareList.length === 1 ? 'producto' : 'productos' }} para comparar
                    </span>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Thumbnails of compared products -->
                    <div class="flex -space-x-2 mr-4">
                        <div v-for="p in compareList" :key="p.id" class="relative w-10 h-10 rounded-xl bg-white border-2 border-slate-900 overflow-hidden group">
                            <img :src="getImageUrl(p)" class="w-full h-full object-contain p-1" />
                            <button @click="removeCompare(p.id)" class="absolute inset-0 bg-red-600/90 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-xs font-bold">✕</span>
                            </button>
                        </div>
                    </div>
                    
                    <button @click="clearCompare" class="text-xs text-gray-400 hover:text-white transition-colors uppercase tracking-wider font-bold">
                        Limpiar
                    </button>
                    
                    <button @click="showCompareModal = true" 
                            class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider text-white shadow-lg transition-transform hover:-translate-y-0.5 active:translate-y-0"
                            style="background-color: var(--color-primary);">
                        Comparar ahora
                    </button>
                </div>
            </div>
        </div>

        <!-- Compare Modal Overlay -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showCompareModal" 
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-5xl w-full h-[85vh] flex flex-col overflow-hidden animate-bounce-in">
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-150 dark:border-gray-700 bg-slate-50 dark:bg-gray-900/40">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-wider">
                                    Comparación de Productos
                                </h3>
                                <p class="text-xs text-gray-400 dark:text-gray-505 mt-0.5">
                                    Analiza especificaciones lado a lado para tomar la mejor decisión
                                </p>
                            </div>
                            <button @click="showCompareModal = false" class="w-10 h-10 rounded-2xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center justify-center text-gray-500 dark:text-gray-300">
                                ✕
                            </button>
                        </div>
                        
                        <!-- Content (Scrollable Table) -->
                        <div class="flex-1 overflow-auto p-6">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-150 dark:border-gray-700">
                                        <th class="p-3 text-left w-1/4 text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest bg-transparent">
                                            Características
                                        </th>
                                        <th v-for="p in compareList" :key="p.id" class="p-3 text-center min-w-[200px]">
                                            <div class="flex flex-col items-center">
                                                <div class="relative w-24 h-24 rounded-2xl bg-white border border-gray-150 dark:border-gray-700 p-2 overflow-hidden mb-3">
                                                    <img :src="getImageUrl(p)" class="w-full h-full object-contain" />
                                                    <button @click="removeCompare(p.id)" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-red-500 hover:bg-red-650 text-white flex items-center justify-center text-[10px] transition-colors shadow">
                                                        ✕
                                                    </button>
                                                </div>
                                                <span class="text-[9px] font-black text-[var(--color-primary)] uppercase tracking-widest leading-none mb-1">
                                                    {{ p.marca?.nombre || p.marca }}
                                                </span>
                                                <h4 class="font-bold text-gray-900 dark:text-white text-xs line-clamp-2 text-center max-w-[180px] mb-2 min-h-[32px]">
                                                    {{ p.nombre }}
                                                </h4>
                                                <p class="font-black text-sm" style="color: var(--color-primary);">
                                                    {{ formatCurrency(p.precio_con_iva) }}
                                                </p>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- General Info -->
                                    <tr class="border-b border-gray-150 dark:border-gray-700 text-xs">
                                        <td class="p-3 bg-gray-50 dark:bg-gray-800/50 font-bold text-gray-500 dark:text-gray-400">
                                            Disponibilidad
                                        </td>
                                        <td v-for="p in compareList" :key="p.id" class="p-3 text-center">
                                            <span v-if="p.stock_local > 0" class="px-2.5 py-1 bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-300 rounded-lg font-bold text-[10px] uppercase">
                                                Entrega Inmediata
                                            </span>
                                            <span v-else-if="p.stock_cedis > 0" class="px-2.5 py-1 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300 rounded-lg font-bold text-[10px] uppercase">
                                                Bajo Pedido (CEDIS)
                                            </span>
                                            <span v-else class="px-2.5 py-1 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300 rounded-lg font-bold text-[10px] uppercase">
                                                Bajo Pedido
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-150 dark:border-gray-700 text-xs">
                                        <td class="p-3 bg-gray-50 dark:bg-gray-800/50 font-bold text-gray-500 dark:text-gray-400">
                                            Categoría
                                        </td>
                                        <td v-for="p in compareList" :key="p.id" class="p-3 text-center text-gray-700 dark:text-gray-300 font-medium">
                                            {{ p.categoria?.nombre || 'General' }}
                                        </td>
                                    </tr>
                                    
                                    <!-- Dynamic Technical Specifications -->
                                    <tr v-for="key in allSpecKeys" :key="key" class="border-b border-gray-150 dark:border-gray-700 text-xs">
                                        <td class="p-3 bg-gray-50 dark:bg-gray-800/30 font-bold text-gray-500 dark:text-gray-400">
                                            {{ key }}
                                        </td>
                                        <td v-for="p in compareList" :key="p.id" class="p-3 text-center text-gray-600 dark:text-gray-200">
                                            {{ parseProductSpecs(p)[key] || '-' }}
                                        </td>
                                    </tr>
                                    
                                    <!-- Add to Cart Row -->
                                    <tr>
                                        <td class="p-3 bg-gray-50 dark:bg-gray-800/50 font-bold text-gray-500 dark:text-gray-400">
                                            Acción
                                        </td>
                                        <td v-for="p in compareList" :key="p.id" class="p-3 text-center">
                                            <button v-if="p.stock_local > 0 || p.stock_cedis > 0" 
                                                    @click="handleAddToCart(p)"
                                                    :disabled="addedToCart === p.id"
                                                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white transition-all shadow hover:-translate-y-0.5 active:translate-y-0"
                                                    :style="{ backgroundColor: addedToCart === p.id ? '#10B981' : 'var(--color-primary)' }">
                                                {{ addedToCart === p.id ? '✓ Agregado' : 'Comprar' }}
                                            </button>
                                            <button v-else 
                                                    @click="openWhatsAppForCompare(p)"
                                                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider bg-amber-500 hover:bg-amber-600 text-white transition-all shadow">
                                                Preguntar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

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
.faq-slide-enter-active, .faq-slide-leave-active {
    transition: all 0.3s ease-in-out;
    max-height: 250px;
    overflow: hidden;
}
.faq-slide-enter-from, .faq-slide-leave-to {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.animate-bounce-in {
    animation: bounceIn 0.3s ease-out;
}

@keyframes bounceIn {
    0% { transform: scale(0.9); opacity: 0; }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
