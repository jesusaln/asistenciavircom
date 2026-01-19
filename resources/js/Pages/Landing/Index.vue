<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import SocialProofNotification from '@/Components/SocialProofNotification.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PosSimulator from '@/Components/PosSimulator.vue';
import QuickAppointmentForm from '@/Components/QuickAppointmentForm.vue';
import BlogPreview from '@/Components/BlogPreview.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import OfertaCountdown from '@/Components/OfertaCountdown.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

const props = defineProps({
    empresa: Object,
    canLogin: Boolean,
    destacados: Array,
    faqs: Array,
    testimonios: Array,
    logosClientes: Array,
    marcas: Array,
    procesos: Array,
    planes: Array,
    oferta: Object,
    laravelVersion: String,
    phpVersion: String,
    articulosBlog: Array,
});

const page = usePage();

// Combinar datos globales con props para asegurar colores corporativos e información completa
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const billingCycle = ref('monthly'); // 'monthly' or 'yearly'

// Integrar modo oscuro centralizado
const { isDarkMode } = useDarkMode(empresaData.value);

// Helper para formatear precios de forma segura
const formatPrice = (precio) => {
    const num = parseFloat(precio);
    return isNaN(num) ? '0.00' : num.toFixed(2);
};

// Scroll y Cursor Tracking
const isVisible = ref(false);
const scrollProgress = ref(0);
const mouseX = ref(0);
const mouseY = ref(0);

const handleScroll = () => {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    scrollProgress.value = (winScroll / height) * 100;
};

const handleMouseMove = (e) => {
    mouseX.value = e.clientX;
    mouseY.value = e.clientY;
};

onMounted(() => {
    isVisible.value = true;
    window.addEventListener('scroll', handleScroll);
    window.addEventListener('mousemove', handleMouseMove);
    
    // Observer for stats
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            animateStats();
        }
    }, { threshold: 0.3 });
    
    if (statsSection.value) {
        observer.observe(statsSection.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('mousemove', handleMouseMove);
});

// WhatsApp link
const whatsappLink = computed(() => {
    if (!empresaData.value?.whatsapp) return null;
    const phone = empresaData.value.whatsapp.replace(/\D/g, '');
    return `https://wa.me/${phone}?text=Hola, me gustaría obtener más información.`;
});

// Stats Animation Logic
const statsSection = ref(null);
const statsAnimated = ref(false);
const stats = ref([
    { id: 'clientes', label: 'Clientes Felices', current: 0, target: 1850, prefix: '+', suffix: '', icon: 'users' },
    { id: 'satisfaccion', label: 'Eficiencia', current: 0, target: 98, prefix: '', suffix: '%', icon: 'check-double' },
    { id: 'servicios', label: 'Instalaciones', current: 0, target: 3500, prefix: '+', suffix: '', icon: 'tools' },
    { id: 'soporte', label: 'Garantía', current: 0, target: 100, prefix: '', suffix: '%', icon: 'shield-halved' },
]);

const animateStats = () => {
    if (statsAnimated.value) return;
    statsAnimated.value = true;
    
    stats.value.forEach(stat => {
        let startValue = 0;
        const endValue = stat.target;
        const duration = 2500;
        const increment = endValue / (duration / 20);
        
        const counter = setInterval(() => {
            startValue += increment;
            if (startValue >= endValue) {
                stat.current = endValue;
                clearInterval(counter);
            } else {
                stat.current = Math.floor(startValue);
            }
        }, 20);
    });
};

// FAQ Accordion
const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

const getImageUrl = (item) => {
    if (!item) return null
    const imagen = typeof item === 'string' ? item : (item.imagen_url || item.imagen)
    if (!imagen) return null

    let urlStr = String(imagen).trim()
    
    // Si viene de CVA o es URL externa
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
const getFaIcon = (plan) => {
    if (plan.icono && plan.icono.includes('-')) return plan.icono;
    
    const iconos = {
        mantenimiento: 'wrench',
        soporte: 'headset',
        garantia: 'shield-halved',
        premium: 'crown',
        personalizado: 'building-shield',
    };
    return iconos[plan.tipo] || 'shield-halved';
};

// Procesar planes para asegurar cálculo de descuento del 15%
const planesCalculados = computed(() => {
    return (props.planes || []).map(plan => {
        if (parseFloat(plan.precio_mensual) > 0) {
             const mensual = parseFloat(plan.precio_mensual);
             const anualSinDescuento = mensual * 12;
             const descuento = 0.15; // 15% solicitado
             const precioAnual = anualSinDescuento * (1 - descuento);
             const ahorro = anualSinDescuento - precioAnual;
             
             return {
                 ...plan,
                 precio_mensual: mensual,
                 precio_anual: precioAnual,
                 ahorro_anual: ahorro
             };
        }
        return plan;
    });
});

</script>

<template>
    <Head>
        <title>{{ empresaData?.nombre_empresa || 'Asistencia Vircom' }} - Redes, CCTV y Seguridad Electrónica</title>
        <meta name="description" :content="`Proveemos Soluciones Integrales en: Redes, Cámaras de Vigilancia (CCTV), Control de Acceso, Alarmas y GPS Vehicular. Expertos en Seguridad y Tecnología en ${empresaData?.ciudad || 'Hermosillo'}.`" />
    </Head>

    <div class="min-h-screen bg-white dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 overflow-x-hidden selection:bg-[var(--color-primary-soft)] selection:text-[var(--color-primary)] relative transition-colors duration-300">
        
        <!-- Progress Bar -->
        <div class="fixed top-0 left-0 h-1 bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)] z-[100] transition-all duration-150" :style="{ width: scrollProgress + '%' }"></div>

        <!-- Custom Cursor Background -->
        <div class="fixed pointer-events-none z-0 opacity-20 transition-transform duration-300 ease-out hidden lg:block" :style="{ transform: `translate(${mouseX - 150}px, ${mouseY - 150}px)` }">
            <div class="w-[300px] h-[300px] bg-[var(--color-primary-soft)] rounded-full blur-[100px]"></div>
        </div>

        <!-- Notificación de Prueba Social (FOMO) - productos destacados -->
        <SocialProofNotification :productos="destacados" />

        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || empresaData?.nombre" />

        <!-- Navigation -->
        <PublicNavbar :empresa="empresaData" activeTab="inicio" />

        <!-- HERO SECTION -->
        <section class="relative pt-24 pb-24 lg:pt-36 lg:pb-36 bg-white dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            <!-- Interactivte Background Elements -->
            <div class="absolute inset-0 z-0">
                <!-- Light Mode BG -->
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[var(--color-primary-soft)] to-transparent opacity-70 dark:opacity-0 transition-opacity"></div>
                
                <!-- Dark Mode BG/Lights -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-10 dark:opacity-20 animate-pulse"></div>
                <div class="absolute top-1/2 left-0 w-72 h-72 bg-[var(--color-secondary)] rounded-full blur-[100px] opacity-5 dark:opacity-10"></div>
                
                <!-- Floating geometric shapes -->
                <div class="absolute bottom-20 right-1/4 w-32 h-32 border-2 border-[var(--color-secondary-soft)] rounded-3xl animate-float-delayed opacity-20 border-dashed dark:border-white/10"></div>
            </div>
            
            <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    
                    <div :class="{'translate-x-0 opacity-100': isVisible, '-translate-x-12 opacity-0': !isVisible}" class="transition-all duration-1000 ease-out">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white shadow-sm border border-gray-100 mb-8 animate-bounce-subtle relative group cursor-pointer">
                            <span class="absolute inset-0 bg-[var(--color-primary-soft)] rounded-full scale-0 group-hover:scale-100 transition-transform duration-300"></span>
                            <span class="flex h-2 w-2 rounded-full bg-green-500 relative ring-4 ring-green-100"></span>
                            <span class="text-xs font-black uppercase tracking-widest text-gray-500 relative">{{ empresaData?.hero_badge_texto || 'Servicio Disponible hoy' }} en {{ empresaData?.ciudad || 'tu ciudad' }}</span>
                        </div>
                        
                        <h1 class="text-5xl lg:text-7xl font-black text-gray-900 dark:text-white leading-[1.1] mb-8 tracking-tighter transition-colors">
                            {{ (empresaData?.hero_titulo || 'Seguridad y Tecnología para tu Negocio').split(' ').slice(0, -3).join(' ') || 'Soluciones' }} <br>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">{{ empresaData?.hero_subtitulo || 'Inteligente' }}</span> <br>
                            {{ (empresaData?.hero_titulo || 'para tu Negocio').split(' ').slice(-3).join(' ') }}
                        </h1>
                        
                        <p class="text-xl text-gray-600 dark:text-gray-400 mb-10 leading-relaxed max-w-xl transition-colors">
                            {{ empresaData?.hero_descripcion || 'Protegemos lo que más te importa con sistemas de videovigilancia, alarmas y soporte TI de clase mundial.' }}
                        </p>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <Link 
                                :href="route('catalogo.polizas')"
                                class="px-8 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:scale-105 hover:shadow-2xl transition-all flex items-center justify-center gap-3"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                {{ empresaData?.hero_cta_primario || 'Nuestras Pólizas' }}
                            </Link>
                            <Link 
                                :href="route('catalogo.index')"
                                class="px-8 py-5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-2 border-gray-100 dark:border-gray-700 rounded-2xl font-black text-sm uppercase tracking-widest hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:hover:border-[var(--color-primary)] dark:hover:text-[var(--color-primary)] transition-all flex items-center justify-center gap-3"
                            >
                                {{ empresaData?.hero_cta_secundario || 'Nuestros Productos' }}
                            </Link>
                        </div>
                        
                        <div class="mt-12 flex items-center gap-6">
                            <div class="flex -space-x-3">
                                <img v-for="i in 4" :key="i" :src="`https://i.pravatar.cc/100?u=${i}`" class="w-12 h-12 rounded-full border-4 border-white dark:border-gray-800 shadow-sm transition-colors" alt="Usuario">
                                <div class="w-12 h-12 rounded-full border-4 border-white dark:border-gray-800 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-500 dark:text-gray-300 shadow-sm transition-colors">+99</div>
                            </div>
                            <div class="text-sm">
                                <div class="flex items-center gap-1 text-amber-400 mb-0.5">
                                    <svg v-for="i in 5" :key="i" class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">Empresas protegidas confirman <span class="text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700">nuestra calidad</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div :class="{'translate-y-0 opacity-100': isVisible, 'translate-y-12 opacity-0': !isVisible}" class="relative transition-all duration-1000 delay-300 ease-out">
                        <!-- Imagen Principal con borde estilizado -->
                        <div class="relative z-10 rounded-[3rem] overflow-hidden shadow-2xl border-8 border-white dark:border-gray-800 group transition-colors duration-300">
                            <img :src="empresaData?.hero_imagen_url || 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" alt="Seguridad Tecnológica">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        </div>
                        
                        <!-- Floating Cards -->
                        <div class="absolute -bottom-6 -left-6 z-20 max-w-[200px] animate-float">
                            <div class="relative w-full h-full">
                                <!-- Glow Effect -->
                                <div class="absolute -inset-1 rounded-3xl bg-[conic-gradient(from_0deg,transparent_0_300deg,var(--color-primary)_360deg)] animate-[spin_3s_linear_infinite] blur-lg opacity-60"></div>
                                <!-- Sharp Border -->
                                <div class="absolute inset-0 rounded-3xl bg-[conic-gradient(from_0deg,transparent_0_300deg,var(--color-primary)_360deg)] animate-[spin_3s_linear_infinite]"></div>
                                <!-- Content -->
                                <div class="relative m-[2px] bg-white rounded-[22px] p-6 shadow-xl flex flex-col justify-center h-full">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-[var(--color-primary-soft)] rounded-xl flex items-center justify-center text-lg text-[var(--color-primary)]">
                                            <font-awesome-icon icon="laptop" />
                                        </div>
                                        <span class="text-xs font-black uppercase tracking-widest text-gray-400">Tecnología</span>
                                    </div>
                                    <p class="text-lg font-bold text-gray-900 leading-tight">Soporte TI 24/7 Empresarial</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="absolute -top-6 -right-6 z-20 max-w-[220px] animate-float-delayed">
                            <div class="relative w-full h-full">
                                <!-- Glow Effect -->
                                <div class="absolute -inset-1 rounded-3xl bg-[conic-gradient(from_0deg,transparent_0_300deg,var(--color-primary)_360deg)] animate-[spin_3s_linear_infinite] blur-lg opacity-60"></div>
                                <!-- Sharp Border -->
                                <div class="absolute inset-0 rounded-3xl bg-[conic-gradient(from_0deg,transparent_0_300deg,var(--color-primary)_360deg)] animate-[spin_3s_linear_infinite]"></div>
                                <!-- Content -->
                                <div class="relative m-[2px] bg-white rounded-[22px] p-6 shadow-xl flex flex-col justify-center h-full">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-[var(--color-primary-soft)] rounded-xl flex items-center justify-center text-lg text-[var(--color-primary)]">
                                            <font-awesome-icon icon="shield-alt" />
                                        </div>
                                        <span class="text-xs font-black uppercase tracking-widest text-gray-400">Seguridad</span>
                                    </div>
                                    <p class="text-lg font-bold text-gray-900 leading-tight">CCTV con IA y Acceso Remoto</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Mouse dynamic shadow element -->
            <div class="absolute inset-0 pointer-events-none opacity-30 mix-blend-soft-light hidden lg:block" 
                :style="{ background: `radial-gradient(1000px circle at ${mouseX}px ${mouseY}px, var(--color-primary-soft), transparent 80%)` }">
            </div>
        </section>

        <!-- TRUST LOGOS -->
        <section class="py-12 bg-white dark:bg-gray-900 border-y border-gray-100 dark:border-gray-900 transition-colors duration-300">
            <div class="w-full px-4">
                <p class="text-center text-xs font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-500 mb-10 transition-colors">Marcas Líderes que Confián en Nosotros</p>
                <div class="flex flex-wrap justify-between items-center gap-8 opacity-50 grayscale hover:grayscale-0 dark:grayscale-0 dark:opacity-100 dark:brightness-110 transition-all duration-500">
                     <img v-for="marca in marcas" :key="marca.id" :src="marca.logo_url || `https://placehold.co/200x80?text=${marca.nombre}`" class="h-8 lg:h-12 w-auto object-contain" :alt="marca.nombre">
                     <template v-if="!marcas?.length">
                        <img v-for="i in 5" :key="i" :src="`https://placehold.co/200x80?text=Marca+${i}`" class="h-8 lg:h-12 w-auto object-contain">
                     </template>
                </div>
            </div>
        </section>

        <!-- STATS SECTION (MODERN DARK & LIGHT) -->
        <section ref="statsSection" class="py-32 relative overflow-hidden bg-white dark:bg-gray-900 transition-colors duration-300">
             <!-- Background Dynamic Gradients (Dark Only) -->
             <div class="absolute inset-0 opacity-30 pointer-events-none hidden dark:block">
                <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-blue-600 blur-[120px] mix-blend-screen animate-pulse"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[radial-gradient(circle,rgba(30,58,138,0.2)_0%,transparent_70%)]"></div>
                <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] rounded-full bg-indigo-600 blur-[120px] mix-blend-screen animate-pulse delay-700"></div>
            </div>

            <div class="w-full px-4 relative z-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-8">
                    <div v-for="(stat, index) in stats" :key="stat.id" 
                         class="group relative p-6 lg:p-8 rounded-[2rem] bg-white dark:bg-gray-800/40 border border-gray-100 dark:border-gray-700/50 hover:bg-white dark:hover:bg-gray-800/80 hover:border-[var(--color-primary)]/50 transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-[var(--color-primary)]/20 backdrop-blur-md overflow-hidden flex flex-col justify-center items-center h-full min-h-[220px]"
                         :class="{'translate-y-0 opacity-100': statsAnimated, 'translate-y-12 opacity-0': !statsAnimated}"
                         :style="{ transitionDelay: `${index * 150}ms` }"
                    >
                        <!-- Glow interno en hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-[var(--color-primary)]/0 to-[var(--color-primary)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Icono decorativo gigante -->
                        <div class="absolute -right-6 -top-6 text-8xl text-gray-200 dark:text-white/[0.03] group-hover:text-[var(--color-primary)]/10 dark:group-hover:text-[var(--color-primary)]/[0.1] transition-all duration-700 rotate-12 group-hover:rotate-0 group-hover:scale-105 pointer-events-none">
                             <font-awesome-icon :icon="stat.icon || 'star'" />
                        </div>
                        
                        <div class="relative z-10 text-center w-full">
                            <div class="text-[var(--color-primary)] text-3xl mb-3 opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-transform duration-300">
                                <font-awesome-icon :icon="stat.icon || 'star'" />
                            </div>

                            <p class="text-4xl lg:text-5xl xl:text-6xl font-black mb-2 tracking-tight bg-gradient-to-b from-gray-900 to-gray-600 dark:from-white dark:to-gray-400 bg-clip-text text-transparent group-hover:from-[var(--color-primary)] group-hover:to-[var(--color-secondary)] dark:group-hover:from-white dark:group-hover:to-[var(--color-primary)] transition-all duration-500 whitespace-nowrap">
                                {{ stat.prefix }}{{ stat.current }}{{ stat.suffix }}
                            </p>
                            <p class="text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors truncate w-full">{{ stat.label }}</p>
                        </div>
                        
                        <!-- Barra inferior -->
                        <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--color-primary)] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-700 ease-out"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SERVICES / PRODUCTS FEATURED -->
        <section class="py-24 bg-white dark:bg-gray-900 relative overflow-hidden transition-colors duration-300">
            <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--color-primary-soft)] rounded-full blur-[100px] opacity-20"></div>
            
            <div class="w-full px-4 relative z-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-8">
                    <div class="max-w-2xl">
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">Nuestro Catálogo</h2>
                        <h3 class="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight transition-colors">
                            Soluciones de <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Próxima Generación</span>
                        </h3>
                    </div>
                    <Link :href="route('catalogo.index')" class="px-8 py-4 bg-white dark:bg-gray-800 rounded-2xl font-black text-xs uppercase tracking-widest text-gray-900 dark:text-white shadow-xl shadow-gray-200/50 dark:shadow-none hover:-translate-y-1 transition-all">
                        Ver Catálogo Completo →
                    </Link>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Producto Destacado Card Premium -->
                    <article v-for="(item, index) in destacados" :key="item.id" 
                        class="group bg-white dark:bg-gray-800 rounded-[2.5rem] overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] hover:-translate-y-3 transition-all duration-700 relative"
                        :style="{ transitionDelay: `${index * 100}ms` }"
                    >
                        <div class="relative aspect-[4/5] bg-white overflow-hidden">
                            <img :src="getImageUrl(item) || 'https://images.unsplash.com/photo-1585338107529-13afc5f02586?q=80&w=2070&auto=format&fit=crop'" class="w-full h-full object-contain p-8 group-hover:scale-110 transition-transform duration-1000 ease-in-out" alt="Producto">
                            
                            <!-- Glassmorphism Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>

                            <div class="absolute top-6 left-6 flex flex-col gap-2 z-10 transition-transform duration-500 group-hover:translate-x-1">
                                <span class="px-4 py-1.5 bg-white/95 dark:bg-gray-800/90 backdrop-blur-xl rounded-full text-[10px] font-black uppercase tracking-[0.1em] text-[var(--color-primary)] dark:text-white shadow-sm border border-gray-100 dark:border-gray-700">🔥 Top Ventas</span>
                                <span v-if="item.categoria" class="px-4 py-1.5 bg-gray-900/90 backdrop-blur-xl rounded-full text-[10px] font-black uppercase tracking-[0.1em] text-white shadow-sm">{{ item.categoria }}</span>
                            </div>

                            <!-- Quick Action Button -->
                            <div class="absolute bottom-6 right-6 translate-y-20 group-hover:translate-y-0 transition-transform duration-500">
                                <Link :href="route('catalogo.show', item.id)" class="w-12 h-12 bg-[var(--color-primary)] text-white rounded-2xl flex items-center justify-center shadow-lg hover:shadow-[var(--color-primary-soft)] hover:scale-110 transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                </Link>
                            </div>
                        </div>

                        <div class="p-6">
                            <h4 :title="item.nombre" class="text-sm font-black text-gray-900 dark:text-white mb-2 group-hover:text-[var(--color-primary)] transition-colors line-clamp-3 h-[3.2rem] leading-tight text-center">{{ item.nombre }}</h4>
                            
                            <div class="flex flex-col items-center justify-center mt-4">
                                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-1">Precio Online</p>
                                <p class="text-xl font-black text-gray-900 dark:text-white tracking-tighter transition-colors">${{ formatPrice(item.precio) }}</p>
                            </div>
                        </div>
                    </article>

                    <!-- Empty State for Featured if none -->
                     <template v-if="!destacados?.length">
                        <article v-for="i in 4" :key="i" class="group bg-white rounded-[2rem] overflow-hidden border border-gray-50 animate-pulse">
                            <div class="aspect-square bg-white"></div>
                            <div class="p-8 space-y-4">
                                <div class="h-6 bg-gray-100 rounded-full w-3/4"></div>
                                <div class="h-4 bg-white rounded-full w-full"></div>
                                <div class="h-12 bg-white rounded-2xl w-full pt-6"></div>
                            </div>
                        </article>
                     </template>
                </div>
            </div>
        </section>

        <!-- OFERTA COUNTDOWN BANNER - después de productos -->
        <OfertaCountdown :empresa="empresaData" :oferta="oferta" />

        <!-- POS SIMULATOR -->
        <PosSimulator :empresa="empresaData" />

        <!-- POLIZAS DE MANTENIMIENTO (CYBER DARK & LIGHT) -->
        <section class="py-32 bg-white dark:bg-gray-900 relative overflow-hidden transition-colors duration-300">
             <!-- Background FX (Dark Only) -->
             <div class="absolute inset-0 pointer-events-none hidden dark:block">
                <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-[var(--color-primary)] opacity-[0.05] blur-[100px] rounded-full mix-blend-screen animate-pulse"></div>
                <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-purple-900 opacity-[0.1] blur-[120px] rounded-full mix-blend-screen"></div>
                
                <!-- Pattern Overlay -->
                <svg class="absolute inset-0 w-full h-full opacity-[0.03]">
                    <pattern id="darkGrid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="white" />
                    </pattern>
                    <rect width="100%" height="100%" fill="url(#darkGrid)" />
                </svg>
             </div>

            <div class="w-full px-4 relative z-10">
                <div class="text-center mb-24 w-full">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-6 drop-shadow-md">Tranquilidad Total</h2>
                    <h3 class="text-5xl lg:text-7xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight mb-8 transition-colors">Pólizas de <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-blue-600 dark:to-blue-400">Soporte Técnico</span></h3>
                    <p class="text-xl text-gray-600 dark:text-gray-400 font-medium leading-relaxed w-full transition-colors">Delega los problemas técnicos a los expertos. Mantenimiento preventivo, soporte remoto y respuesta inmediata.</p>
                </div>

                <!-- Toggle billing Moderno -->
                <div class="flex items-center justify-center gap-6 mb-20">
                    <span :class="billingCycle === 'monthly' ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-400 dark:text-gray-500'" class="text-sm tracking-widest uppercase transition-colors cursor-pointer" @click="billingCycle = 'monthly'">Mensual</span>
                    <button @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'" class="w-20 h-10 bg-gray-200 dark:bg-gray-800 rounded-full relative p-1 transition-all duration-300 shadow-inner border border-gray-300 dark:border-gray-700/50 group focus:outline-none ring-1 ring-transparent hover:ring-[var(--color-primary)]/50">
                        <div :class="billingCycle === 'yearly' ? 'translate-x-10 bg-[var(--color-primary)] shadow-[0_0_15px_var(--color-primary)]' : 'translate-x-0 bg-gray-400'" class="w-8 h-8 rounded-full transition-all duration-500 shadow-md"></div>
                    </button>
                    <span :class="billingCycle === 'yearly' ? 'text-[var(--color-primary)] font-bold' : 'text-gray-400 dark:text-gray-500'" class="text-sm flex items-center gap-3 tracking-widest uppercase transition-colors cursor-pointer" @click="billingCycle = 'yearly'">
                        Anual <span class="px-3 py-1 bg-[var(--color-primary)]/10 text-[var(--color-primary)] border border-[var(--color-primary)]/20 rounded-lg text-[10px] font-black uppercase shadow-sm">-15%</span>
                    </span>
                </div>

                <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                    <div v-for="plan in planesCalculados" :key="plan.id" 
                        :class="plan.destacado ? 'ring-2 ring-[var(--color-primary)] shadow-[0_0_40px_-10px_var(--color-primary)]/50 lg:-translate-y-8 z-20 bg-white dark:bg-gray-900/80' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 bg-white dark:bg-gray-900/40'" 
                        class="relative backdrop-blur-xl p-8 lg:p-10 rounded-[3rem] border flex flex-col group transition-all duration-700 hover:-translate-y-4"
                    >
                        <!-- Etiqueta Destacado Flotante -->
                        <div v-if="plan.destacado" class="absolute -top-6 left-1/2 -translate-x-1/2 w-full text-center">
                             <div class="inline-block relative">
                                 <div class="absolute inset-0 bg-[var(--color-primary)] blur-lg opacity-40"></div>
                                 <div class="relative bg-[var(--color-primary)] text-white px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl flex items-center justify-center gap-2">
                                    <font-awesome-icon icon="crown" class="animate-bounce" /> Más Popular
                                 </div>
                             </div>
                        </div>
                        
                        <div class="mb-12 text-center relative mt-4">
                            <!-- Glow detrás del icono -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-[var(--color-primary)] opacity-0 group-hover:opacity-10 blur-[40px] transition-all duration-700 rounded-full"></div>

                            <div 
                                class="w-24 h-24 rounded-3xl flex items-center justify-center text-4xl mb-8 mx-auto transition-all duration-700 group-hover:scale-110 group-hover:rotate-6 relative z-10 border border-white/5 shadow-2xl"
                                :style="{ 
                                    background: plan.destacado ? `linear-gradient(135deg, var(--color-primary), #111827)` : 'linear-gradient(135deg, #1f2937, #111827)',
                                    color: 'white'
                                }"
                            >
                                <font-awesome-icon :icon="getFaIcon(plan)" class="drop-shadow-lg" />
                            </div>
                            <h4 class="text-3xl font-black text-gray-900 dark:text-white mb-3 tracking-tight transition-colors">{{ plan.nombre }}</h4>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-[0.3em]">{{ plan.tipo_label }}</p>
                        </div>

                        <div class="mb-12 text-center">
                            <template v-if="plan.precio_mensual > 0">
                                <div class="flex items-baseline justify-center gap-1 mb-4">
                                    <span class="text-2xl text-gray-500 font-bold">$</span>
                                    <span class="text-6xl lg:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-b from-gray-900 to-gray-500 dark:from-white dark:to-gray-400 tracking-tighter transition-all">
                                        {{ billingCycle === 'monthly' ? formatPrice(plan.precio_mensual) : formatPrice(plan.precio_anual / 12) }}
                                    </span>
                                </div>
                                <p class="text-gray-500 text-xs font-black uppercase tracking-widest">pesos por mes</p>
                                <div v-if="billingCycle === 'yearly'" class="mt-6 inline-block">
                                    <span class="text-[10px] font-black text-[var(--color-primary)] bg-[var(--color-primary-soft)] px-4 py-2 rounded-xl border border-[var(--color-primary)]/20">
                                        Ahorras ${{ formatPrice(plan.ahorro_anual) }} / año
                                    </span>
                                </div>
                            </template>
                            <template v-else>
                                <p class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter mb-2 uppercase drop-shadow-lg leading-tight transition-colors">Diseño <br>VIP</p>
                                <p class="text-[var(--color-primary)] text-xs font-black uppercase tracking-widest mt-4">Adaptado a su Negocio</p>
                                <div class="h-10 mt-4 opacity-20">
                                    <font-awesome-icon icon="gem" class="text-4xl text-gray-900 dark:text-white transition-colors" />
                                </div>
                            </template>
                        </div>

                        <ul class="space-y-5 mb-12 flex-grow px-2">
                             <li v-for="beneficio in plan.beneficios_array" :key="beneficio" class="flex items-start gap-4 text-sm text-gray-600 dark:text-gray-300 font-medium group/item transition-colors">
                                <span class="w-6 h-6 rounded-full bg-[var(--color-primary-soft)] flex-shrink-0 flex items-center justify-center text-[var(--color-primary)] text-xs group-hover/item:bg-[var(--color-primary)] group-hover/item:text-white transition-all">✓</span> 
                                <span class="group-hover/item:text-gray-900 dark:group-hover/item:text-white transition-colors">{{ beneficio }}</span>
                            </li>
                        </ul>

                        <Link 
                            v-if="plan.precio_mensual > 0"
                            :href="route('catalogo.polizas', { plan: plan.slug })" 
                            class="w-full py-6 rounded-2xl font-black text-xs uppercase tracking-widest text-center transition-all duration-300 relative overflow-hidden group/btn"
                            :class="plan.destacado ? 'bg-[var(--color-primary)] text-white shadow-lg hover:shadow-[var(--color-primary)]/50' : 'bg-gray-900 dark:bg-gray-800 text-white hover:bg-gray-800 dark:hover:bg-gray-700 border border-gray-700'"
                        >
                            <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-500"></div>
                            <span class="relative z-10">Contratar Plan</span>
                        </Link>
                        <a 
                            v-else
                            :href="whatsappLink"
                            target="_blank"
                            class="w-full py-6 rounded-2xl font-black text-xs uppercase tracking-widest text-center transition-all duration-300 border border-[var(--color-primary)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white hover:shadow-[0_0_30px_var(--color-primary)] flex items-center justify-center gap-3 group/btn"
                        >
                            Contactar Ventas
                            <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-lg group-hover/btn:scale-125 transition-transform" />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- PROCESS SECTION - Rediseñado -->
        <section class="py-24 bg-white dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            <div class="w-full px-4">
                <div class="text-center mb-20 w-full">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">Nuestro Método</h2>
                    <h3 class="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight mb-6 transition-colors">
                        Implementación <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Impecable</span>
                    </h3>
                    <p class="text-lg text-gray-500 dark:text-gray-400 font-medium leading-relaxed transition-colors">Desde el diseño del proyecto hasta la puesta en marcha, garantizamos funcionalidad total.</p>
                </div>

                <!-- Pasos Proceso -->
                <div class="relative">
                    <!-- Línea conector (Desktop) -->
                    <div class="hidden lg:block absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 dark:bg-gray-700 -translate-y-1/2 z-0 transition-colors"></div>
                    
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 relative z-10">
                        <div v-for="(p, index) in [
                            {icon: '🔍', title: 'Levantamiento', desc: 'Analizamos puntos ciegos y requerimientos de red.'},
                            {icon: '📋', title: 'Diseño', desc: 'Planos de ubicación y selección de tecnología óptima.'},
                            {icon: '🛠️', title: 'Instalación', desc: 'Cableado estructurado peinado y equipos configurados.'},
                            {icon: '⭐', title: 'Capacitación', desc: 'Te enseñamos a usar tu sistema al 100% y damos soporte.'}
                        ]" :key="index" class="group bg-white dark:bg-gray-800 p-10 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none hover:-translate-y-2 transition-all duration-500 hover:border-[var(--color-primary)] dark:hover:bg-gray-750">
                            <div class="w-20 h-20 bg-white dark:bg-gray-700 rounded-3xl flex items-center justify-center text-4xl mb-8 group-hover:scale-110 group-hover:bg-[var(--color-primary-soft)] transition-all">
                                {{ p.icon }}
                            </div>
                            <div class="relative">
                                <span class="absolute -top-16 -right-2 text-7xl font-black text-gray-50 dark:text-gray-700 opacity-0 group-hover:opacity-100 transition-opacity">0{{ index + 1 }}</span>
                                <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-4 transition-colors">{{ p.title }}</h4>
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed font-medium transition-colors">{{ p.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- TESTIMONIOS - Carrusel Animado Corregido -->
        <section class="py-24 bg-white dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            <div class="w-full px-4">
                <div class="text-center mb-16 w-full">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">Experiencias Reales</h2>
                    <h3 class="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white tracking-tighter transition-colors">Voces de nuestros <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Clientes Seguros</span></h3>
                </div>
            </div>
            
            <!-- Carrusel con efecto infinito -->
            <div class="relative group">
                <div class="testimonials-track flex gap-8 animate-scroll group-hover:[animation-play-state:paused]">
                    <!-- Mapeo de testimonios con fallback -->
                    <div v-for="testimonio in (testimonios?.length ? [...testimonios, ...testimonios] : [
                        {id: 1, nombre: 'Javier Montiel', contenido: 'Instalaron 16 cámaras en mi bodega. La calidad de imagen es increíble y puedo ver todo desde mi celular.', entidad: 'Almacén'},
                        {id: 2, nombre: 'Dra. Elena Ruiz', contenido: 'El sistema de control de acceso para el consultorio funciona perfecto. Ya no tenemos problemas con llaves.', entidad: 'Clínica'},
                        {id: 3, nombre: 'Ing. Marcos Díaz', contenido: 'La póliza de soporte nos salvó cuando el servidor falló. Llegaron en menos de 2 horas.', entidad: 'Despacho'},
                        {id: 4, nombre: 'Restaurante El Fogón', contenido: 'Configuraron todo el punto de venta y las impresoras de cocina. El servicio fluye sin errores.', entidad: 'Restaurante'},
                        {id: 1, nombre: 'Javier Montiel', contenido: 'Instalaron 16 cámaras en mi bodega. La calidad de imagen es increíble y puedo ver todo desde mi celular.', entidad: 'Almacén'},
                        {id: 2, nombre: 'Dra. Elena Ruiz', contenido: 'El sistema de control de acceso para el consultorio funciona perfecto. Ya no tenemos problemas con llaves.', entidad: 'Clínica'},
                        {id: 3, nombre: 'Ing. Marcos Díaz', contenido: 'La póliza de soporte nos salvó cuando el servidor falló. Llegaron en menos de 2 horas.', entidad: 'Despacho'},
                        {id: 4, nombre: 'Restaurante El Fogón', contenido: 'Configuraron todo el punto de venta y las impresoras de cocina. El servicio fluye sin errores.', entidad: 'Restaurante'}
                    ])" :key="'t-' + testimonio.id + Math.random()" class="flex-shrink-0 w-[400px] bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-lg hover:shadow-2xl transition-all duration-500">
                        <div class="flex items-center gap-1 text-amber-400 mb-6">
                            <svg v-for="i in 5" :key="i" class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 font-medium mb-8 leading-relaxed italic line-clamp-4 transition-colors">"{{ testimonio.contenido }}"</p>
                        <div class="flex items-center gap-4 border-t border-gray-100 dark:border-gray-700 pt-6 transition-colors">
                            <img :src="`https://i.pravatar.cc/100?u=${testimonio.id}`" class="w-12 h-12 rounded-2xl shadow-sm" alt="Autor">
                            <div>
                                <h5 class="font-black text-gray-900 dark:text-white text-sm transition-colors">{{ testimonio.nombre }}</h5>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ testimonio.entidad || 'Hogar' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fade edges -->
                <div class="absolute left-0 top-0 bottom-0 w-32 bg-gradient-to-r from-white dark:from-gray-950 to-transparent z-10 pointer-events-none transition-colors duration-300"></div>
                <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-white dark:from-gray-950 to-transparent z-10 pointer-events-none transition-colors duration-300"></div>
            </div>
        </section>

        <!-- QUICK APPOINTMENT FORM -->
        <QuickAppointmentForm :empresa="empresaData" />

        <!-- FAQ SECTION -->
        <section class="py-24 bg-white dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            <div class="w-full px-4">
                 <div class="text-center mb-16">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">¿Dudas?</h2>
                    <h3 class="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white tracking-tighter transition-colors">Preguntas Frecuentes</h3>
                </div>

                <div class="space-y-4">
                    <div v-for="(faq, index) in (faqs?.length ? faqs : [
                        {id: 1, icon: 'shield-alt', pregunta: '¿Qué servicios ofrecen?', respuesta: 'Ofrecemos soluciones integrales en seguridad electrónica (alarmas, cámaras CCTV), soporte técnico computacional, redes y pólizas de mantenimiento preventivo para empresas y hogares.'},
                        {id: 2, icon: 'truck', pregunta: '¿Hacen servicio a domicilio?', respuesta: 'Sí, contamos con técnicos certificados que se desplazan a su domicilio o empresa en Hermosillo y alrededores para instalaciones y soporte técnico.'},
                        {id: 3, icon: 'clock', pregunta: '¿Cuánto tiempo tarda una reparación?', respuesta: 'El tiempo depende de la complejidad, pero la mayoría de los servicios de soporte técnico y reparaciones menores se resuelven en un plazo de 24 a 48 horas.'},
                        {id: 4, icon: 'medal', pregunta: '¿Ofrecen garantía en sus servicios?', respuesta: 'Absolutamente. Todos nuestros trabajos de instalación y reparación cuentan con garantía por escrito para su total tranquilidad y respaldo.'},
                        {id: 5, icon: 'camera', pregunta: '¿Qué marcas de cámaras manejas?', respuesta: 'Trabajamos con las mejores marcas del mercado como Hikvision, Dahua y Axis, asegurando la más alta resolución y durabilidad en cada sistema de videovigilancia.'},
                        {id: 6, icon: 'mobile-alt', pregunta: '¿Puedo monitorear mis cámaras de forma remota?', respuesta: 'Sí, configuramos todos nuestros sistemas para que pueda visualizar sus cámaras en tiempo real desde su celular o computadora, desde cualquier parte del mundo.'}
                    ])" :key="faq.id" 
                        class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group"
                        :class="{'ring-2 ring-[var(--color-primary-soft)]': activeFaq === faq.id}"
                    >
                        <button 
                            @click="toggleFaq(faq.id)"
                            class="w-full px-8 py-7 flex items-center justify-between text-left"
                        >
                            <div class="flex items-center gap-5">
                                <span class="w-12 h-12 rounded-2xl bg-[var(--color-primary-soft)] dark:bg-gray-700 flex items-center justify-center text-lg text-[var(--color-primary)] transition-colors">
                                    <font-awesome-icon :icon="faq.icon || 'question'" />
                                </span>
                                <span class="font-black text-gray-900 dark:text-white group-hover:text-[var(--color-primary)] transition-colors text-lg leading-snug">{{ faq.pregunta }}</span>
                            </div>
                            <span class="w-10 h-10 rounded-xl bg-white dark:bg-gray-700 flex items-center justify-center text-gray-400 transition-transform duration-500 shrink-0" :class="{'rotate-180 bg-[var(--color-primary)] text-white shadow-lg': activeFaq === faq.id}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>
                        <transition
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="transform -translate-y-4 opacity-0"
                            enter-to-class="transform translate-y-0 opacity-100"
                            leave-active-class="transition duration-200 ease-in"
                            leave-from-class="transform translate-y-0 opacity-100"
                            leave-to-class="transform -translate-y-4 opacity-0"
                        >
                            <div v-if="activeFaq === faq.id" class="px-8 pb-8 pt-2">
                                <div class="pl-16 pr-8">
                                    <p class="text-gray-500 dark:text-gray-400 font-medium leading-relaxed border-t border-gray-50 dark:border-gray-700 pt-6 text-[15px] transition-colors">{{ faq.respuesta }}</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <div class="mt-16 bg-gradient-to-br from-gray-900 to-gray-800 p-12 rounded-[3.5rem] relative overflow-hidden text-center text-white shadow-2xl">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[var(--color-primary)] rounded-full blur-[100px] opacity-10"></div>
                    <div class="relative z-10">
                        <h4 class="text-3xl font-black mb-4">¿No encuentras lo que buscas?</h4>
                        <p class="text-gray-400 mb-8 w-full">Nuestro equipo de expertos está listo para asesorarte de forma personalizada y sin compromiso.</p>
                        <a :href="whatsappLink" target="_blank" class="inline-flex items-center gap-3 px-10 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:scale-105 transition-all">
                             <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                             Hablar con un Experto
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- BLOG PREVIEW -->
        <BlogPreview :empresa="empresaData" :articulos="articulosBlog" />

        <!-- PUBLIC FOOTER -->
        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 5s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-bounce-subtle {
    animation: bounce-subtle 4s ease-in-out infinite;
}

.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-4 {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animación del carrusel de testimonios */
@keyframes scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.animate-scroll {
    animation: scroll 30s linear infinite;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: var(--color-primary);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: var(--color-primary-dark);
}

.perspective-1000 {
    perspective: 1000px;
}

.backface-hidden {
    backface-visibility: hidden;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.15; transform: scale(1.1); }
}

.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}
</style>
