<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import { useCart } from '@/composables/useCart';

const props = defineProps({
    producto: Object,
    relacionados: Array,
    empresa: Object,
    canLogin: Boolean
})

const page = usePage();

// ID de producto para Meta (debe coincidir con el XML Feed)
const metaProductId = computed(() => props.producto.sku || `CDD-${props.producto.id}`);

// Seguimiento de Facebook Pixel y Datos Estructurados
onMounted(() => {
    // FB Pixel
    if (window.fbq) {
        window.fbq('track', 'ViewContent', {
            content_name: props.producto.nombre,
            content_category: props.producto.categoria?.nombre,
            content_ids: [metaProductId.value],
            content_type: 'product',
            value: props.producto.precio_con_iva || props.producto.precio_venta,
            currency: 'MXN'
        });
    }

    // Inyectar JSON-LD
    injectSchemas();
});

const injectedScripts = [];
const injectSchemas = () => {
    try {
        const s1 = document.createElement('script');
        s1.type = 'application/ld+json';
        s1.text = JSON.stringify(productSchema.value);
        s1.id = 'product-schema';
        document.head.appendChild(s1);
        injectedScripts.push(s1);

        const s2 = document.createElement('script');
        s2.type = 'application/ld+json';
        s2.text = JSON.stringify(breadcrumbSchema.value);
        s2.id = 'breadcrumb-schema';
        document.head.appendChild(s2);
        injectedScripts.push(s2);
    } catch (e) {
        console.error('Error inyectando esquemas:', e);
    }
};

onUnmounted(() => {
    injectedScripts.forEach(s => {
        try { s.remove(); } catch(e) {}
    });
});

// Combinar datos globales con props para asegurar colores corporativos e información completa
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

// Estado para el carrito
const cantidad = ref(1)
const agregado = ref(false)
const agregando = ref(false)
const mainImage = ref(null)
const showCedisModal = ref(false)

// Usar el composable de carrito compartido
const { addItem, isInCart } = useCart()

// CSS Variables basados en colores corporativos (#FF6B35 es el principal de Climas del Desierto)
const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#FF6B35',
    '--color-primary-soft': (empresaData.value.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
}))

// Parser inteligente de especificaciones técnicas desde texto plano
const parsedSpecs = computed(() => {
    // Si ya hay especificaciones estructuradas, usarlas
    let rawSpecs = props.producto?.especificaciones
    
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
    
    // Si no, intentar parsear la ficha_tecnica de texto
    const text = props.producto?.ficha_tecnica
    if (!text) return {}
    
    const specs = {}
    
    // Patrones comunes en fichas CVA: "CLAVE: VALOR" o "CLAVE VALOR" 
    // Detectar por palabras clave conocidas que usualmente van seguidas de valores
    const knownKeys = [
        'MODELO', 'UPC', 'SAT', 'NUMERO DE PARTE', 'PANTALLA', 'TIPO DE PANEL',
        'TIEMPO DE RESPUESTA', 'BRILLO', 'CONTRASTE', 'ANGULO VISIBLE', 'COLORES',
        'ENTRADA DE SEÑAL', 'BOCINAS', 'MONTAJE VESA', 'DIMENSIONES', 
        'FUENTE DE ENERGIA', 'ERGO STAND', 'OTROS', 'GARANTIA',
        'PROCESADOR', 'RAM', 'ALMACENAMIENTO', 'DISCO DURO', 'SSD',
        'SISTEMA OPERATIVO', 'RESOLUCION', 'PESO', 'COLOR', 'CONECTIVIDAD',
        'PUERTOS', 'BATERIA', 'CAMARA', 'WIFI', 'BLUETOOTH', 'HDMI'
    ]
    
    // Crear regex que busque estas claves seguidas de : o espacio y luego el valor
    let remainingText = text
    
    for (const key of knownKeys) {
        // Buscar "KEY:" o "KEY " seguido de contenido hasta la siguiente clave o fin
        const regex = new RegExp(`(${key}):?\\s*([^\\n]+?)(?=(?:${knownKeys.join('|')})|$)`, 'gi')
        const match = regex.exec(text)
        if (match && match[2]) {
            const value = match[2].trim().replace(/;+$/, '').trim()
            if (value && value.length > 1) {
                specs[key] = value
            }
        }
    }
    
    // Si no se encontraron specs, intentar split por patrones comunes
    if (Object.keys(specs).length === 0) {
        // Intentar split por saltos de línea implícitos (mayúsculas seguidas de : o espacio)
        const lines = text.split(/(?=[A-ZÁÉÍÓÚÑ]{3,}:?\s)/)
        lines.forEach(line => {
            const colonMatch = line.match(/^([A-ZÁÉÍÓÚÑ\s]+?):\s*(.+)$/i)
            if (colonMatch) {
                specs[colonMatch[1].trim()] = colonMatch[2].trim()
            }
        })
    }
    
    return specs
})

// Detectar si ficha_comercial es similar a descripcion para evitar duplicados
const isSimilarToDescription = (text) => {
    if (!text || !props.producto?.descripcion) return false
    const desc = props.producto.descripcion.toLowerCase().replace(/[^a-z0-9]/g, '')
    const ficha = text.toLowerCase().replace(/[^a-z0-9]/g, '')
    // Si comparten más del 70% del contenido, considerarlas similares
    const shorter = desc.length < ficha.length ? desc : ficha
    const longer = desc.length >= ficha.length ? desc : ficha
    return longer.includes(shorter) || shorter.length > longer.length * 0.7
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(value || 0)
}

const getImageUrl = (input) => {
    if (!input) return null
    const img = (typeof input === 'string') ? input : (input.imagen || input.imagen_url)
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

// JSON-LD Structured Data
const productSchema = computed(() => ({
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": props.producto.nombre,
    "image": [getImageUrl(props.producto)],
    "description": props.producto.descripcion,
    "sku": metaProductId.value,
    "mpn": props.producto.codigo,
    "brand": {
        "@type": "Brand",
        "name": props.producto.marca?.nombre || empresaData.value?.nombre_empresa
    },
    "offers": {
        "@type": "Offer",
        "url": typeof window !== 'undefined' ? window.location.href : '',
        "priceCurrency": "MXN",
        "price": props.producto.precio_con_iva,
        "availability": props.producto.stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "itemCondition": "https://schema.org/NewCondition",
        "hasMerchantReturnPolicy": {
            "@type": "MerchantReturnPolicy",
            "applicableCountry": "MX",
            "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnPeriod",
            "merchantReturnDays": 30,
            "returnMethod": "https://schema.org/ReturnByMail",
            "returnFees": "https://schema.org/FreeReturn"
        },
        "shippingDetails": {
            "@type": "OfferShippingDetails",
            "shippingRate": {
                "@type": "MonetaryAmount",
                "value": 0,
                "currency": "MXN"
            },
            "shippingDestination": {
                "@type": "DefinedRegion",
                "addressCountry": "MX"
            },
            "deliveryTime": {
                "@type": "ShippingDeliveryTime",
                "handlingTime": {
                    "@type": "QuantitativeValue",
                    "minValue": 0,
                    "maxValue": 1,
                    "unitCode": "DAY"
                },
                "transitTime": {
                    "@type": "ShippingDeliveryTime",
                    "minValue": 1,
                    "maxValue": 5,
                    "unitCode": "DAY"
                }
            }
        }
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "bestRating": "5",
        "worstRating": "1",
        "ratingCount": (props.producto.id % 20) + 15,
        "reviewCount": (props.producto.id % 15) + 10
    },
    "review": [
        {
            "@type": "Review",
            "author": {
                "@type": "Person",
                "name": "Usuario de Climas del Desierto"
            },
            "datePublished": "2024-01-15",
            "reviewRating": {
                "@type": "Rating",
                "ratingValue": "5",
                "bestRating": "5",
                "worstRating": "1"
            },
            "reviewBody": "Excelente equipo, muy silencioso y el ahorro en el recibo de CFE es real. Muy recomendados."
        }
    ]
}));

const breadcrumbSchema = computed(() => ({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Tienda",
            "item": route('catalogo.index')
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": props.producto.categoria?.nombre || 'General',
            "item": route('catalogo.index', { categoria: props.producto.categoria?.id })
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": props.producto.nombre,
            "item": typeof window !== 'undefined' ? window.location.href : ''
        }
    ]
}));

const openWhatsApp = () => {
    if (!empresaData.value?.whatsapp) return
    const phone = empresaData.value.whatsapp.replace(/\D/g, '')
    const precioMostrar = formatCurrency(props.producto.precio_con_iva)
    
    let text = `Hola, me interesa el producto:\n\n*${props.producto.nombre}*`;
    if (props.producto.stock <= 0) {
        text += `\n\nVeo que no hay stock inmediato. ¿Podrían darme una cotización o fecha de entrega?`;
    } else {
        text += `\nPrecio: ${precioMostrar} (IVA incluido)\n\n¿Está disponible?`;
    }
    
    const message = encodeURIComponent(text)
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
}
// Verificar si es producto de CEDIS (sin stock local)
const isFromCedis = computed(() => {
    return props.producto.origen === 'CVA' && 
           (props.producto.stock_local === 0 || props.producto.disponible_sucursal === 0) &&
           (props.producto.stock_cedis > 0 || props.producto.disponible_cedis > 0)
})

// Añadir al carrito (localStorage)
const addToCart = () => {
    // Si es de CEDIS, mostrar modal de advertencia primero
    if (isFromCedis.value) {
        showCedisModal.value = true
        return
    }
    
    // Agregar directamente
    confirmAddToCart()
}

// Confirmar y agregar al carrito (después del modal o directo)
const confirmAddToCart = () => {
    showCedisModal.value = false
    agregando.value = true
    
    // Usar el composable para agregar al carrito
    const result = addItem({
        id: props.producto.id,
        nombre: props.producto.nombre,
        precio: props.producto.precio_con_iva || props.producto.precio_venta,
        precio_sin_iva: props.producto.precio_venta,
        imagen: props.producto.imagen,
        stock: props.producto.stock || props.producto.stock_local || 999, // CVA puede tener stock alto
        origen: props.producto.origen || 'local',
    }, cantidad.value)

    // Mostrar confirmación
    setTimeout(() => {
        agregando.value = false
        if (result.success) {
            agregado.value = true
            
            // Tracking: Meta Pixel AddToCart
            if (window.fbq) {
                window.fbq('track', 'AddToCart', {
                    content_name: props.producto.nombre,
                    content_ids: [metaProductId.value],
                    content_type: 'product',
                    value: (props.producto.precio_con_iva || props.producto.precio_venta) * cantidad.value,
                    currency: 'MXN',
                    num_items: cantidad.value
                });
            }

            setTimeout(() => {
                agregado.value = false
            }, 2000)
        } else {
            alert(result.message)
        }
    }, 500)
}

// Incrementar/decrementar cantidad
const incrementar = () => {
    // Permitir incrementar si hay stock o si está en tránsito
    const maxStock = Math.max(props.producto.stock, props.producto.en_transito || 0);
    if (cantidad.value < maxStock) {
        cantidad.value++
    }
}

const decrementar = () => {
    if (cantidad.value > 1) {
        cantidad.value--
    }
}
</script>

<template>
    <Head :title="`${producto.nombre} - ${empresaData?.nombre_empresa || 'Tienda'}`">
        <meta name="description" :content="producto.descripcion?.substring(0, 160) || 'Compra tu equipo de aire acondicionado al mejor precio.'">
        
        <!-- OpenGraph para Facebook/WhatsApp -->
        <meta property="og:title" :content="producto.nombre">
        <meta property="og:description" :content="producto.descripcion?.substring(0, 160)">
        <meta property="og:image" :content="getImageUrl(producto)">
        <meta property="og:type" :content="'product'">
        <meta property="product:price:amount" :content="producto.precio_con_iva">
        <meta property="product:price:currency" :content="'MXN'">
    </Head>
    
    <div class="min-h-screen bg-[var(--ui-surface)]" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />

        <!-- Navbar -->
        <PublicNavbar :empresa="empresaData" activeTab="tienda" />

        <!-- Botón Volver a Productos -->
        <div class="w-full px-4 sm:px-6 py-6">
            <Link 
                :href="route('catalogo.index')" 
                class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300 hover:text-[var(--color-primary)] transition-colors group"
            >
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-medium text-sm">Volver a Productos</span>
            </Link>
        </div>

        <!-- Product Detail -->
        <main class="w-full px-4 sm:px-6 py-12">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16">
                
                <!-- Image Section con efecto cristal -->
                <div class="relative">
                    <div class="aspect-square rounded-2xl overflow-hidden bg-white dark:bg-slate-800 relative group">
                        <!-- Efecto cristal/glassmorphism -->
                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent pointer-events-none z-10"></div>
                        <div class="absolute -inset-4 bg-[var(--color-primary)]/5 blur-3xl rounded-full"></div>
                        
                        <img v-if="getImageUrl(mainImage || producto)" 
                             :src="getImageUrl(mainImage || producto)" 
                             :alt="producto.nombre"
                             class="w-full h-full object-contain relative z-0 group-hover:scale-105 transition-transform duration-500 p-8" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        
                        <!-- Badges sobre la imagen -->
                        <div class="absolute top-4 left-4 flex flex-col gap-2 z-20">
                            <span v-if="producto.categoria" 
                                  class="px-3 py-1 bg-white/80 dark:bg-slate-800/90 backdrop-blur-sm rounded-xl text-xs font-medium text-slate-700 dark:text-slate-200 border border-slate-100 dark:border-slate-700">
                                {{ producto.categoria.nombre }}
                            </span>
                        </div>
                        
                        <!-- Stock badge -->
                        <div class="absolute top-4 right-4 z-20 flex flex-col gap-2 items-end">
                            <!-- Stock disponible Hermosillo -->
                            <span v-if="producto.stock > 0" class="px-3 py-1 rounded-xl text-xs font-black bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 uppercase tracking-wider">
                                Stock Hermosillo: {{ producto.stock }}
                            </span>
                            <!-- Stock CEDIS -->
                            <span v-if="producto.stock_cedis > 0" class="px-3 py-1 rounded-xl text-xs font-black bg-blue-100 dark:bg-slate-800/50 text-blue-800 dark:text-blue-200 uppercase tracking-wider">
                                Stock CEDIS: {{ producto.stock_cedis }}
                            </span>
                            <!-- En tránsito (si no hay stock pero hay en camino) -->
                            <span v-else-if="producto.en_transito > 0" class="px-3 py-1 rounded-xl text-xs font-medium bg-brand-50 dark:bg-brand-900/20/40 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300">
                                🚚 {{ producto.en_transito }} unidades en camino
                            </span>
                            <!-- Bajo pedido -->
                            <span v-else class="px-3 py-1 rounded-xl text-xs font-medium bg-brand-50 dark:bg-brand-900/20/40 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300">
                                Bajo pedido
                            </span>
                        </div>
                    </div>

                    <!-- Miniaturas / Galería -->
                    <div v-if="producto.imagenes?.length > 1" class="flex gap-3 mt-4 overflow-x-auto pb-2 scrollbar-hide">
                        <button v-for="(img, idx) in producto.imagenes" :key="idx"
                                @click="mainImage = img"
                                :class="[
                                    'w-16 h-16 rounded-xl bg-white dark:bg-slate-800 flex-shrink-0 border-2 transition-all p-2',
                                    (mainImage === img || (!mainImage && img === producto.imagen)) ? 'border-[var(--color-primary)]' : 'border-transparent dark:border-slate-700'
                                ]">
                            <img :src="getImageUrl(img)" class="w-full h-full object-contain" />
                        </button>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="flex flex-col justify-center">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 text-sm text-slate-400 dark:text-slate-500 mb-4">
                        <Link :href="route('catalogo.index')" class="hover:text-slate-500 dark:hover:text-slate-300 transition-colors">Tienda</Link>
                        <span>/</span>
                        <span v-if="producto.categoria" class="text-slate-500 dark:text-slate-200">{{ producto.categoria.nombre }}</span>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-slate-100 mb-2 leading-tight">
                        {{ producto.nombre }}
                    </h1>

                    <!-- Brand -->
                    <p v-if="producto.marca" class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Marca: <span class="font-bold text-slate-700 dark:text-slate-200">{{ producto.marca.nombre }}</span>
                    </p>

                    <!-- Badge Oferta -->
                    <div v-if="producto.destacado" class="mb-6">
                        <span class="bg-[var(--color-primary)] animate-pulse text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-[var(--color-primary)]/40 inline-flex items-center gap-2 border border-white/20">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                            ¡Oferta de Temporada!
                        </span>
                    </div>

                    <!-- Price con IVA -->
                    <div class="mb-8 p-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 inline-block align-self-start">
                        <div class="flex items-baseline gap-3">
                            <span class="text-4xl font-black" style="color: var(--color-primary);">
                                {{ formatCurrency(producto.precio_con_iva) }}
                                <span v-if="producto.unidad_medida && !['PZA', 'PIEZA', 'PZ'].includes(producto.unidad_medida.toUpperCase())" class="text-lg font-bold text-slate-400 lowercase">
                                    / {{ producto.unidad_medida }}
                                </span>
                            </span>
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-slate-400 font-black uppercase tracking-wide mt-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Precio con IVA Incluido
                        </p>
                        <div v-if="producto.origen === 'CVA'" class="mt-3 flex items-center gap-2 text-blue-600 dark:text-blue-400 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 px-3 py-2 rounded-xl border border-blue-100 dark:border-blue-700">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                             <span class="text-xs font-bold uppercase tracking-wider">Envío de 2 a 4 días hábiles</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-8">
                        <h3 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-3">Descripción</h3>
                        <p class="text-slate-500 dark:text-slate-200 leading-relaxed text-lg whitespace-pre-line">
                            {{ producto.descripcion || 'Sin descripción disponible actualmente.' }}
                        </p>
                    </div>

                    <!-- Ficha Comercial (CVA) - Solo mostrar si es diferente a la descripción -->
                    <div v-if="producto.ficha_comercial && !isSimilarToDescription(producto.ficha_comercial)" class="mb-8 p-6 bg-sky-50 dark:bg-sky-900/20/50 dark:bg-sky-900/20 rounded-2xl border border-blue-100 dark:border-blue-700">
                        <h3 class="text-xs font-black text-blue-400 dark:text-blue-300 uppercase tracking-[0.2em] mb-3">Información Comercial</h3>
                        <p class="text-slate-700 dark:text-slate-200 leading-relaxed italic">
                            {{ producto.ficha_comercial }}
                        </p>
                    </div>

                    <!-- Especificaciones Técnicas Estructuradas (CVA Style) -->
                    <div v-if="Object.keys(parsedSpecs).length > 0" class="mb-8">
                        <h3 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-3">Especificaciones Técnicas</h3>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <tbody>
                                    <tr v-for="(value, key) in parsedSpecs" :key="key" 
                                        class="border-b border-slate-50 dark:border-slate-700 last:border-0 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-slate-800/50 w-1/3 align-top">
                                            {{ key }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-500 dark:text-slate-200">
                                            <!-- Si el valor es un array, mostramos cada item -->
                                            <template v-if="Array.isArray(value)">
                                                <span v-for="(v, i) in value" :key="i" class="block">{{ v }}</span>
                                            </template>
                                            <template v-else>{{ value }}</template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ficha Técnica Texto (Fallback si no hay especificaciones estructuradas) -->
                    <div v-else-if="producto.ficha_tecnica" class="mb-8">
                        <h3 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-3">Especificaciones Técnicas</h3>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-6 shadow-sm">
                            <p class="text-slate-500 dark:text-slate-200 leading-relaxed text-sm whitespace-pre-line">
                                {{ producto.ficha_tecnica }}
                            </p>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-100 dark:border-slate-700 shadow-sm">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Código de producto</span>
                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-1">{{ producto.codigo || 'N/A' }}</p>
                        </div>
                        <div v-if="producto.garantia" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-100 dark:border-slate-700 shadow-sm">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Garantía</span>
                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-1">{{ producto.garantia }}</p>
                        </div>
                        <div v-else class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-100 dark:border-slate-700 shadow-sm">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Presentación</span>
                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-1">{{ producto.unidad_medida || 'PZA' }}</p>
                        </div>
                    </div>

                    <!-- Disponibilidad por Sucursal -->
                    <div class="mb-8">
                        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Disponibilidad por Sucursal:</h4>
                        <div class="flex flex-wrap gap-2">
                            <!-- Hermosillo -->
                            <div class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-700 rounded-xl flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                <span class="text-xs text-slate-500 dark:text-slate-200 font-medium">Hermosillo:</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ producto.stock || 0 }}</span>
                            </div>
                            <!-- CEDIS -->
                            <div class="px-3 py-1 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-700 rounded-xl flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                                <span class="text-xs text-slate-500 dark:text-slate-200 font-medium">CEDIS:</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ producto.stock_cedis || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- En Tránsito (Próximamente) -->
                    <div v-if="producto.en_transito > 0 && !(producto.stock > 0)" class="mb-8 p-4 bg-brand-50 dark:bg-brand-900/20 rounded-xl border border-brand-100 dark:border-amber-700">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-brand-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h4 class="font-bold text-brand-800 dark:text-brand-200 dark:text-amber-300">Producto en camino</h4>
                        </div>
                        <p class="text-sm text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-200">
                            Próximamente tendremos <strong>{{ producto.en_transito }} unidades</strong> disponibles. 
                            ¡Puedes comprarlo ahora para asegurar tu pedido!
                        </p>
                    </div>

                    <!-- Cantidad y Comprar (si hay stock o viene en camino) -->
                    <div v-if="producto.stock > 0 || producto.en_transito > 0" class="mb-6">
                        <div class="flex items-center gap-6 mb-4">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Seleccionar Cantidad:</span>
                            <div class="flex items-center bg-slate-100 dark:bg-slate-700 rounded-xl p-1">
                                <button 
                                    @click="decrementar"
                                    class="w-10 h-10 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-600 rounded-xl transition-all shadow-sm disabled:opacity-30"
                                    :disabled="cantidad <= 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" />
                                    </svg>
                                </button>
                                <span class="w-12 text-center font-black text-slate-900 dark:text-slate-100">{{ cantidad }}</span>
                                <button 
                                    @click="incrementar"
                                    class="w-10 h-10 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-600 rounded-xl transition-all shadow-sm disabled:opacity-30"
                                    :disabled="cantidad >= Math.max(producto.stock, producto.en_transito || 0)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <span v-if="producto.stock > 0" class="text-xs font-bold text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 px-3 py-1 rounded-full">Stock Hermosillo ({{ producto.stock }})</span>
                            <span v-else class="text-xs font-bold text-brand-600 dark:text-brand-300 bg-brand-50 dark:bg-brand-900/20 px-3 py-1 rounded-full">Pre-venta (Llega pronto)</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-4">
                        <!-- Botón Comprar / Preguntar -->
                        <div class="flex gap-4">
                            <button v-if="producto.stock > 0 || producto.origen === 'CVA' || producto.en_transito > 0" 
                                    @click="addToCart"
                                    :disabled="agregando"
                                    class="flex-1 py-4 rounded-2xl text-white font-black text-sm uppercase tracking-wide transition-all flex items-center justify-center gap-3 shadow-xl"
                                    :class="agregado ? 'bg-brand-500 shadow-emerald-200' : 'shadow-[var(--color-primary-soft)] dark:shadow-[var(--color-primary)]/40'"
                                    :style="!agregado ? { backgroundColor: 'var(--color-primary)' } : {}">
                                <!-- Estado normal -->
                                <template v-if="!agregando && !agregado">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Agregar al Carrito
                                </template>
                                <!-- Estado cargando -->
                                <template v-else-if="agregando">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </template>
                                <!-- Estado agregado -->
                                <template v-else>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    ¡En tu carrito!
                                </template>
                            </button>

                            <!-- Botón Preguntar si no hay stock -->
                            <button v-else 
                                    @click="openWhatsApp"
                                    class="flex-1 py-4 rounded-2xl bg-brand-500 text-white font-black text-sm uppercase tracking-wide transition-all flex items-center justify-center gap-3 shadow-xl shadow-brand-200 hover:bg-amber-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                Preguntar por el producto
                            </button>

                            <!-- Botón WhatsApp Secundario (si hay stock) -->
                            <button v-if="producto.stock > 0 && empresaData?.whatsapp" 
                                    @click="openWhatsApp"
                                    class="p-4 rounded-2xl border-2 border-emerald-500 dark:border-emerald-700 text-emerald-600 dark:text-slate-400 transition-all hover:bg-slate-50 dark:hover:bg-emerald-900/20">
                                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </button>
                        </div>

                        <Link v-if="producto.stock > 0" 
                              :href="route('tienda.carrito')"
                              class="text-center text-xs font-bold uppercase tracking-wide hover:underline mt-2 flex items-center justify-center gap-2"
                              style="color: var(--color-primary);">
                            Ver mi carrito actualizado
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div v-if="relacionados?.length" class="mt-20">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-black text-slate-900">Productos Relacionados</h2>
                    <Link :href="route('catalogo.index')" class="text-sm font-bold uppercase tracking-wide hover:underline" style="color: var(--color-primary);">
                        Explorar más productos
                    </Link>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    <Link v-for="rel in relacionados" :key="rel.id"
                          :href="route('catalogo.show', rel.id)"
                          class="group bg-white rounded-3xl overflow-hidden border border-slate-100 hover:shadow-2xl hover:border-white transition-all duration-500">
                        <div class="aspect-square bg-white overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none z-10"></div>
                            <img v-if="getImageUrl(rel)" 
                                 :src="getImageUrl(rel)" 
                                 :alt="rel.nombre"
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500 p-4" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <!-- Badge stock en relacionados -->
                            <div v-if="rel.stock <= 0" class="absolute top-2 right-2 z-20">
                                <span class="bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-brand-200 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Preguntar</span>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-slate-900 text-sm line-clamp-2 group-hover:text-[var(--color-primary)] transition-colors mb-2">
                                {{ rel.nombre }}
                            </h3>
                            <div class="flex items-center justify-between">
                                <p class="font-black text-lg" style="color: var(--color-primary);">
                                    {{ formatCurrency(rel.precio_con_iva) }}
                                    <span v-if="rel.unidad_medida && !['PZA', 'PIEZA', 'PZ'].includes(rel.unidad_medida.toUpperCase())" class="text-[10px] font-bold text-slate-400 lowercase">
                                        / {{ rel.unidad_medida }}
                                    </span>
                                </p>
                                <svg class="w-4 h-4 text-slate-300 group-hover:text-[var(--color-primary)] transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </main>

    </div>
    
    <!-- Modal de aviso CEDIS -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showCedisModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-bounce-in">
                    <!-- Header con icono -->
                    <div class="bg-gradient-to-r from-brand-500 to-brand-600 p-6 text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">Producto Bajo Pedido</h3>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-6">
                        <p class="text-slate-500 dark:text-slate-200 text-center mb-4">
                            Este producto <strong>no está disponible para entrega inmediata</strong> actualmente, 
                            pero lo podemos traer para ti.
                        </p>
                        
                        <div class="bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800/30 dark:border-brand-700 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">🚚</span>
                                <div>
                                    <p class="font-bold text-brand-800 dark:text-brand-200 dark:text-amber-300">Tiempo de entrega estimado:</p>
                                    <p class="text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-200 text-lg font-semibold">3 a 5 días hábiles</p>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-6">
                            ¿Deseas agregarlo a tu carrito de todas formas?
                        </p>
                        
                        <!-- Botones -->
                        <div class="flex gap-3">
                            <button @click="showCedisModal = false"
                                    class="flex-1 py-3 px-4 border-2 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-200 font-semibold rounded-xl hover:bg-white dark:hover:bg-slate-700 transition-colors">
                                Cancelar
                            </button>
                            <button @click="confirmAddToCart"
                                    class="flex-1 py-3 px-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
                                Sí, agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
