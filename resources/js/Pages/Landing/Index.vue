<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import SocialProofNotification from '@/Components/SocialProofNotification.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import ClimatizationSimulator from '@/Components/ClimatizationSimulator.vue';
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
    rentas: Array,
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

// Formulario de contacto en Hero
const contactForm = ref({
    nombre: '',
    telefono: '',
    email: '',
    servicio: 'instalacion',
    mensaje: ''
});
const contactFormSending = ref(false);
const contactFormSent = ref(false);
const contactFormError = ref('');

const submitContactForm = async () => {
    if (!contactForm.value.nombre || !contactForm.value.telefono) {
        contactFormError.value = 'Por favor ingresa tu nombre y teléfono';
        return;
    }
    
    // Validar que el teléfono tenga exactamente 10 dígitos
    const telefonoLimpio = contactForm.value.telefono.replace(/\D/g, '');
    if (telefonoLimpio.length !== 10) {
        contactFormError.value = 'El teléfono debe tener exactamente 10 dígitos';
        return;
    }
    
    contactFormError.value = '';
    contactFormSending.value = true;
    
    try {
        // Mapear el servicio al formato que espera el CRM
        const asuntosMap = {
            'instalacion': 'ventas',
            'mantenimiento': 'soporte',
            'reparacion': 'soporte',
            'cotizacion': 'polizas',
            'renta': 'ventas'
        };
        
        // Si no hay email, usar uno genérico para evitar error de validación del servidor
        const emailEnvio = contactForm.value.email || `${telefonoLimpio}@temp.local`;
        
        const response = await fetch(route('public.contacto.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre: contactForm.value.nombre,
                telefono: telefonoLimpio,
                email: emailEnvio,
                asunto: asuntosMap[contactForm.value.servicio] || 'otro',
                mensaje: contactForm.value.mensaje || `Solicitud de ${contactForm.value.servicio}`
            })
        });
        
        const data = await response.json();
        
        if (response.ok || data.success) {
            contactFormSent.value = true;
            contactForm.value = { nombre: '', telefono: '', email: '', servicio: 'instalacion', mensaje: '' };
            setTimeout(() => { contactFormSent.value = false; }, 5000);
        } else if (data.errors) {
            contactFormError.value = Object.values(data.errors)[0] || 'Error al enviar';
        } else {
            contactFormError.value = 'Error al enviar. Intenta por WhatsApp';
        }
    } catch (e) {
        console.error('Error enviando formulario:', e);
        contactFormError.value = 'Error de conexión. Intenta por WhatsApp';
    } finally {
        contactFormSending.value = false;
    }
};

// JSON-LD Structured Data for SEO
const localBusinessSchema = computed(() => ({
    "@context": "https://schema.org",
    "@type": "HVACBusiness",
    "name": empresaData.value?.nombre_empresa || "Climas del Desierto",
    "description": `Distribuidor autorizado Mirage en Sonora. Venta, instalación y mantenimiento de aires acondicionados en ${empresaData.value?.ciudad || 'Hermosillo'}.`,
    "url": "https://climasdeldesierto.com",
    "telephone": empresaData.value?.telefono || "",
    "email": empresaData.value?.email || "",
    "image": empresaData.value?.logo_url || "/images/logo.webp",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": empresaData.value?.direccion || "",
        "addressLocality": empresaData.value?.ciudad || "Hermosillo",
        "addressRegion": "Sonora",
        "postalCode": empresaData.value?.codigo_postal || "83000",
        "addressCountry": "MX"
    },
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": 29.0729,
        "longitude": -110.9559
    },
    "areaServed": [
        { "@type": "City", "name": "Hermosillo" },
        { "@type": "City", "name": "Ciudad Obregón" },
        { "@type": "City", "name": "Guaymas" },
        { "@type": "City", "name": "Nogales" },
        { "@type": "City", "name": "San Luis Río Colorado" },
        { "@type": "State", "name": "Sonora" }
    ],
    "openingHoursSpecification": [
        {
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
            "opens": "08:00",
            "closes": "18:00"
        },
        {
            "@type": "OpeningHoursSpecification",
            "@dayOfWeek": "Saturday",
            "opens": "09:00",
            "closes": "14:00"
        }
    ],
    "priceRange": "$",
    "sameAs": [
        empresaData.value?.facebook || "",
        empresaData.value?.instagram || ""
    ].filter(Boolean)
}));

const faqSchema = computed(() => ({
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "¿Cuánto cuesta instalar un minisplit en Hermosillo?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "El costo de instalación de un minisplit en Hermosillo varía según la capacidad del equipo y la complejidad de la instalación. En Climas del Desierto ofrecemos equipos Mirage Inverter con instalación incluida. Contáctanos para una cotización personalizada."
            }
        },
        {
            "@type": "Question",
            "name": "¿Cada cuánto necesita mantenimiento un aire acondicionado en Sonora?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "En el clima desértico de Sonora, recomendamos un mantenimiento preventivo como mínimo cada 3 meses durante la temporada de calor (abril-octubre). El polvo y las altas temperaturas hacen que los filtros y serpentines se ensucien más rápido que en otras regiones."
            }
        },
        {
            "@type": "Question",
            "name": "¿Qué marca de aire acondicionado es mejor para el calor de Sonora?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Recomendamos equipos Mirage con tecnología Inverter. Sus modelos están diseñados para operar en temperaturas extremas de hasta 52°C, lo que los hace ideales para Sonora. Además, ofrecen un ahorro de hasta 65% en el consumo de energía comparado con equipos convencionales."
            }
        },
        {
            "@type": "Question",
            "name": "¿Cuánto ahorra un minisplit Inverter en el recibo de CFE?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Un minisplit Inverter Mirage puede ahorrar entre 50% y 65% en tu recibo de CFE comparado con un equipo convencional (On/Off). En Hermosillo, donde el aire funciona más de 12 horas al día, esto puede significar un ahorro de más de $2,000 MXN mensuales en la temporada de verano."
            }
        },
        {
            "@type": "Question",
            "name": "¿Ofrecen pólizas de mantenimiento para aires acondicionados?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Sí, en Climas del Desierto ofrecemos pólizas de mantenimiento preventivo mensuales y anuales que incluyen limpieza de filtros, revisión de gas refrigerante, limpieza de serpentines y diagnóstico completo del equipo. Las pólizas anuales incluyen descuento especial."
            }
        }
    ]
}));

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

// Minisplit Interactive State
const minisplit1On = ref(false);  // Starts OFF

const toggleMinisplit = (unit) => {
    if (unit === 1) minisplit1On.value = !minisplit1On.value;
};

const getImageUrl = (item) => {
    if (!item) return null
    const imagen = typeof item === 'string' ? item : (item.imagen_url || item.imagen)
    if (!imagen) return null

    let urlStr = String(imagen).trim()
    
    // Si viene de CVA
    if (urlStr.includes('grupocva.com')) {
        try {
            return route('img.proxy', { u: btoa(urlStr) })
        } catch (e) {
            return route('img.proxy', { url: urlStr })
        }
    }

    // Si es otra URL externa o ruta completa local, usarla directora
    if (urlStr.toLowerCase().startsWith('http') || urlStr.startsWith('//')) {
        return urlStr;
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
        // Rentas
        pdv: 'cash-register',
        oficina: 'laptop',
        gaming: 'gamepad',
        laptop: 'mobile-alt',
    };
    return iconos[plan.tipo] || 'shield-halved';
};

const heroTitle = computed(() => empresaData.value?.hero_titulo || 'Aire Acondicionado e Instalaciones de Vanguardia');
const heroSubtitle = computed(() => {
    const subtitle = (empresaData.value?.hero_subtitulo || 'Eficiente').trim();
    const normalizedTitle = heroTitle.value.toLowerCase();
    return subtitle && normalizedTitle.includes(subtitle.toLowerCase()) ? '' : subtitle;
});
const heroTitleFirstLine = computed(() => heroTitle.value.split(' ').slice(0, -2).join(' ') || 'Confort');
const heroTitleLastLine = computed(() => heroTitle.value.split(' ').slice(-2).join(' '));

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
        <title>{{ empresaData?.nombre_empresa || 'Climas del Desierto' }} | Distribuidor Autorizado Mirage en Sonora</title>
        <meta name="description" :content="`Expertos en aire acondicionado Mirage en ${empresaData?.ciudad || 'Hermosillo'}. Venta de minisplits Inverter, mantenimiento preventivo y pólizas de servicio en todo Sonora.`" />
        <meta name="keywords" content="Mirage Sonora, Aire Acondicionado Hermosillo, Minisplit Inverter Mirage, Mantenimiento Climas Mirage, Climas del Desierto, Distribuidor Mirage" />

        <!-- Schema: LocalBusiness (Google Maps / Pack Local) -->
    </Head>

    <div class="min-h-screen bg-[var(--ui-surface)] font-sans text-slate-900 dark:text-slate-100 overflow-x-hidden selection:bg-[var(--color-primary-soft)] selection:text-[var(--color-primary)] relative transition-colors duration-200">
        
        <!-- Progress Bar -->
        <div class="fixed top-0 left-0 h-1 bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)] z-[100] transition-all duration-150" :style="{ width: scrollProgress + '%' }"></div>

        <!-- Custom Cursor Background -->
        <div class="fixed pointer-events-none z-0 opacity-20 transition-transform duration-200 ease-out hidden lg:block" :style="{ transform: `translate(${mouseX - 150}px, ${mouseY - 150}px)` }">
            <div class="w-[300px] h-[300px] bg-[var(--color-primary-soft)] rounded-full blur-[100px]"></div>
        </div>

        <!-- Notificación de Prueba Social (FOMO) - productos destacados -->
        <SocialProofNotification :productos="destacados" />

        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || empresaData?.nombre" />

        <!-- Navigation -->
        <PublicNavbar :empresa="empresaData" activeTab="inicio" />

        <!-- HERO SECTION -->
        <section class="relative pt-24 pb-24 lg:pt-36 lg:pb-36 bg-[var(--ui-surface)] overflow-hidden transition-colors duration-200">
            <!-- Interactivte Background Elements -->
            <div class="absolute inset-0 z-0">
                <!-- Light Mode BG -->
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[var(--color-primary-soft)] to-transparent opacity-70 dark:opacity-0 transition-opacity"></div>
                
                <!-- Dark Mode BG/Lights -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-10 dark:opacity-20 animate-pulse"></div>
                <div class="absolute top-1/2 left-0 w-72 h-72 bg-[var(--color-secondary)] rounded-full blur-[100px] opacity-5 dark:opacity-10"></div>
                
                <!-- Floating geometric shapes -->

            </div>
            
            <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    
                    <div :class="{'translate-x-0 opacity-100': isVisible, '-translate-x-12 opacity-0': !isVisible}" class="transition-all duration-700 ease-out">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white shadow-sm border border-slate-100 mb-8 animate-bounce-subtle relative group cursor-pointer">
                            <span class="absolute inset-0 bg-[var(--color-primary-soft)] rounded-full scale-0 group-hover:scale-100 transition-transform duration-200"></span>
                            <span class="flex h-2 w-2 rounded-full bg-brand-500 relative ring-4 ring-emerald-100"></span>
                            <span class="text-xs font-black uppercase tracking-wide text-slate-500 relative">{{ empresaData?.hero_badge_texto || 'Servicio Disponible hoy' }} en {{ empresaData?.ciudad || 'tu ciudad' }}</span>
                        </div>
                        
                        <h1 class="text-5xl lg:text-7xl font-black text-[var(--ui-text)] leading-[1.1] mb-8 tracking-tighter transition-colors">
                            {{ heroTitleFirstLine }} <br>
                            <template v-if="heroSubtitle">
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">{{ heroSubtitle }}</span> <br>
                            </template>
                            {{ heroTitleLastLine }}
                        </h1>
                        
                        <p class="text-xl text-[var(--ui-text-soft)] mb-10 leading-relaxed max-w-xl transition-colors">
                            {{ empresaData?.hero_descripcion || 'Garantizamos el clima perfecto para tu hogar o empresa con equipos de alta eficiencia y pólizas de mantenimiento profesional.' }}
                        </p>

                        <!-- FORMULARIO DE CONTACTO RÁPIDO -->
                        <div class="mt-10 p-6 bg-[var(--ui-surface)] backdrop-blur-xl rounded-3xl border border-[var(--ui-border)] shadow-xl">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-wide">Solicita tu Cotización</h3>
                                    <p class="text-xs text-[var(--ui-text-soft)] font-medium">Te contactamos en menos de 1 hora</p>
                                </div>
                            </div>

                            <!-- Mensaje de éxito -->
                            <div v-if="contactFormSent" class="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300">¡Mensaje enviado! Te contactaremos pronto.</span>
                            </div>

                            <!-- Error -->
                            <div v-if="contactFormError" class="mb-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/30 dark:border-rose-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs font-bold text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300">{{ contactFormError }}</span>
                            </div>

                            <form @submit.prevent="submitContactForm" class="space-y-3">
                                <!-- Nombre y Teléfono en una fila -->
                                <div class="grid grid-cols-2 gap-3">
                                    <input
                                        v-model="contactForm.nombre"
                                        type="text"
                                        placeholder="Tu nombre"
                                        class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-200 dark:border-[var(--ui-border)] rounded-xl text-sm font-medium text-[var(--ui-text)] placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                                        required
                                    />
                                    <input
                                        v-model="contactForm.telefono"
                                        type="tel"
                                        placeholder="Teléfono (10 dígitos)"
                                        maxlength="10"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-200 dark:border-[var(--ui-border)] rounded-xl text-sm font-medium text-[var(--ui-text)] placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                                        required
                                    />
                                </div>

                                <!-- Email -->
                                <input
                                    v-model="contactForm.email"
                                    type="email"
                                    placeholder="Correo electrónico (opcional)"
                                    class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-200 dark:border-[var(--ui-border)] rounded-xl text-sm font-medium text-[var(--ui-text)] placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                                />

                                <!-- Tipo de servicio -->
                                <select
                                    v-model="contactForm.servicio"
                                    class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-200 dark:border-[var(--ui-border)] rounded-xl text-sm font-medium text-[var(--ui-text)] focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all cursor-pointer"
                                >
                                    <option value="instalacion">Instalación de equipo</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="reparacion">Reparación</option>
                                    <option value="cotizacion">Cotización de póliza</option>
                                    <option value="renta">Renta de equipos</option>
                                </select>

                                <!-- Mensaje opcional -->
                                <textarea
                                    v-model="contactForm.mensaje"
                                    rows="2"
                                    placeholder="¿En qué podemos ayudarte? (opcional)"
                                    class="w-full px-4 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-200 dark:border-[var(--ui-border)] rounded-xl text-sm font-medium text-[var(--ui-text)] placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all resize-none"
                                ></textarea>

                                <!-- Botón enviar -->
                                <button
                                    type="submit"
                                    :disabled="contactFormSending"
                                    class="w-full py-4 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-xl font-black text-xs uppercase tracking-wide shadow-xl hover:shadow-xl hover:shadow-xl hover:shadow-xl.5 transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="contactFormSending" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <span v-if="contactFormSending">Enviando...</span>
                                    <span v-else>Enviar Solicitud Ahora</span>
                                </button>
                            </form>
                        </div>
                        
                        <div class="mt-12 flex items-center gap-6">
                            <div class="flex -space-x-3">
                                <img v-for="i in 4" :key="i" :src="`https://i.pravatar.cc/100?u=${i}`" class="w-10 h-10 rounded-full border-4 border-[var(--ui-surface)] shadow-sm transition-colors" alt="Usuario">
                                <div class="w-10 h-10 rounded-full border-4 border-[var(--ui-surface)] bg-[var(--ui-surface-soft)] flex items-center justify-center text-xs font-bold text-[var(--ui-text-muted)] shadow-sm transition-colors">+99</div>
                            </div>
                            <div class="text-sm">
                                <div class="flex items-center gap-1 text-brand-400 mb-0.5">
                                    <svg v-for="i in 5" :key="i" class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </div>
                                <p class="text-[var(--ui-text-soft)] font-medium">Empresas protegidas confirman <span class="text-[var(--ui-text)] border-b border-slate-200 dark:border-[var(--ui-border)]">nuestra calidad</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div :class="{'translate-y-0 opacity-100': isVisible, 'translate-y-12 opacity-0': !isVisible}" class="relative transition-all duration-700 delay-300 ease-out mt-12 lg:mt-0">
                        <!-- Dynamic Background Image Container -->
                        <div class="relative z-10 rounded-[3rem] overflow-hidden shadow-2xl border-8 border-[var(--ui-surface)] group transition-colors duration-200 h-[400px] lg:h-[500px] bg-slate-100 dark:bg-slate-800">
                            
                            <!-- Cold/Comfortable Image (Visible when ON) -->
                            <div class="absolute inset-0 transition-opacity duration-700 ease-in-out" :class="minisplit1On ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                                <img src="/images/living_room_cold.webp" class="w-full h-full object-cover" alt="Ambiente Fresco y Confortable">
                                <div class="absolute inset-0 bg-brand-500/10 mix-blend-overlay"></div> <!-- Cold tint overlay -->
                            </div>

                            <!-- Hot/Uncomfortable Image (Visible when OFF) -->
                            <div class="absolute inset-0 transition-opacity duration-700 ease-in-out" :class="!minisplit1On ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                                <img src="/images/living_room_hot.webp" class="w-full h-full object-cover" alt="Calor Incomodo">
                                <div class="absolute inset-0 bg-brand-500/10 mix-blend-overlay"></div> <!-- Hot tint overlay -->
                            </div>

                            <!-- Vignette & Gloss -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent pointer-events-none"></div>
                            <div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent opacity-50 pointer-events-none"></div>
                        </div>
                        
                        <!-- Single Centered Minisplit (Interactive) -->
                        <div class="absolute -top-12 left-1/2 -translate-x-1/2 z-20 w-[280px] lg:w-[320px] perspective-2000 cursor-pointer select-none transition-transform duration-200 hover:shadow-xl hover:shadow-xl" @click="toggleMinisplit(1)">
                            <div class="relative group">
                                <!-- Fluid Multi-Layer Air Curtain (Only when ON) -->
                                <div v-if="minisplit1On" class="absolute inset-x-0 -bottom-[160px] h-[160px] overflow-hidden pointer-events-none flex justify-center">
                                    <div class="air-curtain layer-1 w-[90%]"></div>
                                    <div class="air-curtain layer-2 w-[85%]"></div>
                                    <div class="air-curtain layer-3 w-[80%]"></div>
                                </div>
                                
                                <!-- Unit Body: High Realism -->
                                <div class="relative bg-gradient-to-b from-white via-slate-50 to-slate-100 dark:from-slate-800 dark:via-slate-850 dark:to-slate-900 rounded-t-[3rem] rounded-b-2xl h-28 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.4)] border-x border-t border-white/80 dark:border-[var(--ui-border)]/50 overflow-hidden z-10 transition-all duration-200" :class="{ 'opacity-90 grayscale-[0.3]': !minisplit1On }">
                                    <!-- Top Intake Grill -->
                                    <div class="absolute top-2 left-12 right-12 h-5 flex flex-col gap-[3px] opacity-15 dark:opacity-30">
                                        <div v-for="i in 4" :key="i" class="w-full h-[1px] bg-black dark:bg-white"></div>
                                    </div>

                                    <!-- Front Curvature Highlight -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>

                                    <!-- Power Display (ON/OFF) -->
                                    <div class="absolute top-1/2 -translate-y-1/2 left-8 px-3 py-1.5 rounded-xl transition-all duration-200" :class="minisplit1On ? 'bg-brand-500/10 border border-emerald-400/20' : 'bg-slate-500/10 border border-slate-400/20'">
                                        <span class="font-mono text-xs font-black tracking-wider transition-colors duration-200" :class="minisplit1On ? 'text-emerald-500 drop-shadow-[0_0_8px_rgba(34,197,94,0.6)]' : 'text-slate-400'">
                                            {{ minisplit1On ? 'ON' : 'OFF' }}
                                        </span>
                                    </div>

                                    <!-- Status LED -->
                                    <div class="absolute top-5 right-10 flex gap-1.5 items-center">
                                        <div class="w-2 h-2 rounded-full transition-all duration-200" :class="minisplit1On ? 'bg-brand-500 shadow-[0_0_10px_rgba(34,197,94,0.9)] animate-pulse' : 'bg-slate-400 bg-opacity-30'"></div>
                                    </div>

                                    <!-- Temperature Display -->
                                    <div class="absolute bottom-6 right-10 font-mono text-xl font-bold select-none transition-colors duration-200" :class="minisplit1On ? 'text-blue-400/80 drop-shadow-[0_0_5px_rgba(96,165,250,0.5)]' : 'text-slate-300/20'">
                                        {{ minisplit1On ? '22°' : '--' }}
                                    </div>
                                    
                                    <!-- Moving Flap -->
                                    <div class="minisplit-flap-enhanced" :class="{ 'flap-stopped': !minisplit1On }"></div>
                                </div>
                                

                                
                                <!-- Wall Shadow (Depth) -->
                                <div class="absolute -inset-6 top-4 bg-black/20 blur-3xl -z-10 rounded-full h-20 w-[90%] mx-auto"></div>
                            </div>
                        </div>

                                <!-- Interactive CTA Bubble -->
                                <div class="absolute -top-24 -right-12 z-30 w-48 animate-bounce-subtle pointer-events-none lg:pointer-events-auto">
                                    <div class="relative bg-[var(--ui-surface)] p-4 rounded-2xl rounded-bl-none shadow-xl border border-[var(--ui-border)]">
                                        <div class="absolute -bottom-2 left-0 w-4 h-4 bg-[var(--ui-surface)] border-b border-l border-[var(--ui-border)] transform rotate-45"></div>
                                        
                                        <transition mode="out-in" enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                                            <div v-if="!minisplit1On" key="off-msg" class="text-center">
                                                <p class="text-xs font-bold text-slate-800 text-[var(--ui-text-muted)] mb-1">Ambiente cálido</p>
                                                <p class="text-[10px] text-slate-500 leading-tight">Haz click en el equipo para refrescar el ambiente.</p>
                                            </div>
                                            <div v-else key="on-msg" class="text-center">
                                                <p class="text-xs font-bold text-[var(--color-primary)] mb-1">Confort optimizado</p>
                                                <p class="text-[10px] text-slate-500 leading-tight mb-2">¿Quieres este confort en tu hogar?</p>
                                                <Link :href="route('catalogo.index')" class="inline-block px-3 py-1 bg-[var(--color-primary)] text-white text-[10px] font-bold rounded-full hover:bg-brand-600 transition-colors pointer-events-auto">
                                                    Ver Equipos
                                                </Link>
                                            </div>
                                        </transition>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
            
            <!-- Mouse dynamic shadow element -->

        </section>

        <!-- STATS SECTION (MODERN DARK & LIGHT) -->
        <section ref="statsSection" class="py-32 relative overflow-hidden bg-[var(--ui-surface)] transition-colors duration-200">
             <!-- Background Dynamic Gradients (Dark Only) -->
             <div class="absolute inset-0 opacity-30 pointer-events-none hidden dark:block">
                <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-blue-600 blur-[120px] mix-blend-screen animate-pulse"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[radial-gradient(circle,rgba(30,58,138,0.2)_0%,transparent_70%)]"></div>
                <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] rounded-full bg-indigo-600 blur-[120px] mix-blend-screen animate-pulse delay-700"></div>
            </div>

            <div class="w-full px-4 relative z-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-8">
                    <div v-for="(stat, index) in stats" :key="stat.id" 
                         class="group relative p-6 lg:p-8 rounded-[2rem] bg-[var(--ui-surface)] border border-[var(--ui-border)]/50 hover:bg-white dark:hover:bg-slate-700/80 hover:border-[var(--color-primary)]/50 transition-all duration-500 hover:shadow-xl hover:shadow-xl hover:shadow-2xl hover:shadow-[var(--color-primary)]/20 backdrop-blur-md overflow-hidden flex flex-col justify-center items-center h-full min-h-[220px]"
                         :class="{'translate-y-0 opacity-100': statsAnimated, 'translate-y-12 opacity-0': !statsAnimated}"
                         :style="{ transitionDelay: `${index * 150}ms` }"
                    >
                        <!-- Glow interno en hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-[var(--color-primary)]/0 to-[var(--color-primary)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Icono decorativo gigante -->
                        <div class="absolute -right-6 -top-6 text-8xl text-slate-200 dark:text-white/[0.03] group-hover:text-[var(--color-primary)]/10 dark:group-hover:text-[var(--color-primary)]/[0.1] transition-all duration-700 rotate-12 group-hover:rotate-0 group-hover:scale-105 pointer-events-none">
                             <font-awesome-icon :icon="stat.icon || 'star'" />
                        </div>
                        
                        <div class="relative z-10 text-center w-full">
                            <div class="text-[var(--color-primary)] text-3xl mb-3 opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-transform duration-200">
                                <font-awesome-icon :icon="stat.icon || 'star'" />
                            </div>

                            <p class="text-4xl lg:text-5xl xl:text-6xl font-black mb-2 tracking-tight bg-gradient-to-b from-slate-900 to-slate-600 dark:from-white dark:to-slate-400 bg-clip-text text-transparent group-hover:from-[var(--color-primary)] group-hover:to-[var(--color-secondary)] dark:group-hover:from-white dark:group-hover:to-[var(--color-primary)] transition-all duration-500 whitespace-nowrap">
                                {{ stat.prefix }}{{ stat.current }}{{ stat.suffix }}
                            </p>
                            <p class="text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-[var(--ui-text-soft)] group-hover:text-slate-900 dark:group-hover:text-white transition-colors truncate w-full">{{ stat.label }}</p>
                        </div>
                        
                        <!-- Barra inferior -->
                        <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--color-primary)] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-700 ease-out"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SERVICES / PRODUCTS FEATURED -->
        <section class="py-24 bg-[var(--ui-surface)] relative overflow-hidden transition-colors duration-200">
            <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--color-primary-soft)] rounded-full blur-[100px] opacity-20"></div>
            
            <div class="w-full px-4 relative z-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-8">
                    <div class="max-w-2xl">
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">Nuestro Catálogo</h2>
                        <h3 class="text-4xl lg:text-5xl font-black text-[var(--ui-text)] tracking-tighter leading-tight transition-colors">
                            Soluciones de <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Próxima Generación</span>
                        </h3>
                    </div>
                    <Link :href="route('catalogo.index')" class="px-8 py-4 bg-[var(--ui-surface)] rounded-2xl font-black text-xs uppercase tracking-wide text-[var(--ui-text)] shadow-xl shadow-slate-200/50 dark:shadow-none hover:shadow-xl hover:shadow-xl transition-all">
                        Ver Catálogo Completo →
                    </Link>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Producto Destacado Card Premium -->
                    <article v-for="(item, index) in destacados" :key="item.id" 
                        class="group bg-[var(--ui-surface)] rounded-[2.5rem] overflow-hidden border border-[var(--ui-border)] hover:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] hover:shadow-xl hover:shadow-xl transition-all duration-700 relative"
                        :style="{ transitionDelay: `${index * 100}ms` }"
                    >
                        <div class="relative aspect-square bg-white flex items-center justify-center overflow-hidden">
                            <img :src="getImageUrl(item) || 'https://images.unsplash.com/photo-1585338107529-13afc5f02586?q=80&w=2070&auto=format&fit=crop'" @error="handleImageError" class="max-w-[85%] max-h-[85%] object-contain group-hover:scale-105 transition-transform duration-700 ease-in-out" alt="Producto">
                            
                            <!-- Glassmorphism Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>

                            <div class="absolute top-6 left-6 flex flex-col gap-2 z-10 transition-transform duration-500 group-hover:translate-x-1">
                                <span v-if="item.destacado" class="px-4 py-1.5 bg-[var(--color-primary)] animate-pulse rounded-full text-[10px] font-black uppercase tracking-[0.1em] text-white shadow-xl shadow-[var(--color-primary)]/30 border border-white/20">¡OFERTA!</span>
                                <span v-else class="px-4 py-1.5 bg-white/80 dark:bg-slate-800/90 backdrop-blur-xl rounded-full text-[10px] font-black uppercase tracking-[0.1em] text-[var(--color-primary)] dark:text-white shadow-sm border border-[var(--ui-border)]">Top Ventas</span>
                                <span v-if="item.categoria" class="px-4 py-1.5 bg-slate-900/90 backdrop-blur-xl rounded-full text-[10px] font-black uppercase tracking-[0.1em] text-white shadow-sm">{{ item.categoria }}</span>
                            </div>

                            <!-- Quick Action Button -->
                            <div class="absolute bottom-6 right-6 translate-y-20 group-hover:translate-y-0 transition-transform duration-500">
                                <Link :href="route('catalogo.show', item.id)" class="w-10 h-10 bg-[var(--color-primary)] text-white rounded-2xl flex items-center justify-center shadow-xl hover:shadow-[var(--color-primary-soft)] hover:scale-105 transition-all">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                </Link>
                            </div>
                        </div>

                        <div class="p-6">
                            <h4 :title="item.nombre" class="text-sm font-black text-[var(--ui-text)] mb-2 group-hover:text-[var(--color-primary)] transition-colors line-clamp-3 h-[3.2rem] leading-tight text-center">{{ item.nombre }}</h4>
                            
                            <div class="flex flex-col items-center justify-center mt-4">
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] mb-1">Precio Online</p>
                                <p class="text-2xl font-black text-[var(--ui-text)] tracking-tighter transition-colors">
                                    ${{ formatPrice(item.precio) }}
                                    <span v-if="item.unidad_medida && !['PZA', 'PIEZA', 'PZ'].includes(item.unidad_medida.toUpperCase())" class="text-xs font-bold text-slate-400 lowercase ml-1">
                                        / {{ item.unidad_medida }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </article>

                    <!-- Empty State for Featured if none -->
                     <template v-if="!destacados?.length">
                        <article v-for="i in 4" :key="i" class="group bg-white rounded-[2rem] overflow-hidden border border-slate-50 animate-pulse">
                            <div class="aspect-square bg-white"></div>
                            <div class="p-8 space-y-6">
                                <div class="h-6 bg-slate-100 rounded-full w-3/4"></div>
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

        <!-- CLIMATIZATION SIMULATOR -->
        <ClimatizationSimulator :empresa="empresaData" />

        <!-- POLIZAS DE MANTENIMIENTO (CYBER DARK & LIGHT) - OCULTO HASTA TENER PÓLIZAS LISTAS -->
        <section v-if="false" class="py-32 bg-[var(--ui-surface)] relative overflow-hidden transition-colors duration-200">
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
                    <h3 class="text-5xl lg:text-7xl font-black text-[var(--ui-text)] tracking-tighter leading-tight mb-8 transition-colors">Pólizas de <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-blue-600 dark:to-blue-400">Soporte Técnico</span></h3>
                    <p class="text-xl text-[var(--ui-text-soft)] font-medium leading-relaxed w-full transition-colors">Delega los problemas técnicos a los expertos. Mantenimiento preventivo, soporte remoto y respuesta inmediata.</p>
                </div>

                <!-- Toggle billing Moderno -->
                <div class="flex items-center justify-center gap-6 mb-20">
                    <span :class="billingCycle === 'monthly' ? 'text-[var(--ui-text)] font-bold' : 'text-[var(--ui-text-soft)]'" class="text-sm tracking-wide uppercase transition-colors cursor-pointer" @click="billingCycle = 'monthly'">Mensual</span>
                    <button @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'" class="w-20 h-10 bg-[var(--ui-surface-soft)] rounded-full relative p-1 transition-all duration-200 shadow-inner border border-slate-300 dark:border-[var(--ui-border)]/50 group focus:outline-none ring-1 ring-transparent hover:ring-[var(--color-primary)]/50">
                        <div :class="billingCycle === 'yearly' ? 'translate-x-10 bg-[var(--color-primary)] shadow-[0_0_15px_var(--color-primary)]' : 'translate-x-0 bg-slate-400'" class="w-10 h-10 rounded-full transition-all duration-500 shadow-md"></div>
                    </button>
                    <span :class="billingCycle === 'yearly' ? 'text-[var(--color-primary)] font-bold' : 'text-[var(--ui-text-soft)]'" class="text-sm flex items-center gap-2 tracking-wide uppercase transition-colors cursor-pointer" @click="billingCycle = 'yearly'">
                        Anual <span class="px-3 py-1 bg-[var(--color-primary)]/10 text-[var(--color-primary)] border border-[var(--color-primary)]/20 rounded-xl text-[10px] font-black uppercase shadow-sm">-15%</span>
                    </span>
                </div>

                <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                    <div v-for="plan in planesCalculados" :key="plan.id" 
                        :class="plan.destacado ? 'ring-2 ring-[var(--color-primary)] shadow-[0_0_40px_-10px_var(--color-primary)]/50 lg:-translate-y-8 z-20 bg-[var(--ui-surface)]/80' : 'border-slate-200 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-500 bg-[var(--ui-surface)]'" 
                        class="relative backdrop-blur-xl p-8 lg:p-10 rounded-[3rem] border flex flex-col group transition-all duration-700 hover:shadow-xl hover:shadow-xl"
                    >
                        <!-- Etiqueta Destacado Flotante -->
                        <div v-if="plan.destacado" class="absolute -top-6 left-1/2 -translate-x-1/2 w-full text-center">
                             <div class="inline-block relative">
                                 <div class="absolute inset-0 bg-[var(--color-primary)] blur-lg opacity-40"></div>
                                 <div class="relative bg-[var(--color-primary)] text-white px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-wide shadow-xl flex items-center justify-center gap-2">
                                    <font-awesome-icon icon="crown" class="animate-bounce" /> Más Popular
                                 </div>
                             </div>
                        </div>
                        
                        <div class="mb-12 text-center relative mt-4">
                            <!-- Glow detrás del icono -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-[var(--color-primary)] opacity-0 group-hover:opacity-10 blur-[40px] transition-all duration-700 rounded-full"></div>

                            <div 
                                class="w-16 h-16 rounded-3xl flex items-center justify-center text-4xl mb-8 mx-auto transition-all duration-700 group-hover:scale-105 group-hover:rotate-6 relative z-10 border border-white/5 shadow-2xl"
                                :style="{ 
                                    background: plan.destacado ? `linear-gradient(135deg, var(--color-primary), #111827)` : 'linear-gradient(135deg, #1f2937, #111827)',
                                    color: 'white'
                                }"
                            >
                                <font-awesome-icon :icon="getFaIcon(plan)" class="drop-shadow-xl" />
                            </div>
                            <h4 class="text-2xl font-black text-[var(--ui-text)] mb-3 tracking-tight transition-colors">{{ plan.nombre }}</h4>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-[0.3em]">{{ plan.tipo_label }}</p>
                        </div>

                        <div class="mb-12 text-center">
                            <template v-if="plan.precio_mensual > 0">
                                <div class="flex items-baseline justify-center gap-1 mb-4">
                                    <span class="text-2xl text-slate-500 font-bold">$</span>
                                    <span class="text-6xl lg:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-b from-slate-900 to-slate-500 dark:from-white dark:to-slate-400 tracking-tighter transition-all">
                                        {{ billingCycle === 'monthly' ? formatPrice(plan.precio_mensual) : formatPrice(plan.precio_anual / 12) }}
                                    </span>
                                </div>
                                <p class="text-slate-500 text-xs font-black uppercase tracking-wide">pesos por mes</p>
                                <div v-if="billingCycle === 'yearly'" class="mt-6 inline-block">
                                    <span class="text-[10px] font-black text-[var(--color-primary)] bg-[var(--color-primary-soft)] px-4 py-2 rounded-xl border border-[var(--color-primary)]/20">
                                        Ahorras ${{ formatPrice(plan.ahorro_anual) }} / año
                                    </span>
                                </div>
                            </template>
                            <template v-else>
                                <p class="text-4xl font-black text-[var(--ui-text)] tracking-tighter mb-2 uppercase drop-shadow-xl leading-tight transition-colors">Diseño <br>VIP</p>
                                <p class="text-[var(--color-primary)] text-xs font-black uppercase tracking-wide mt-4">Adaptado a su Negocio</p>
                                <div class="h-10 mt-4 opacity-20">
                                    <font-awesome-icon icon="gem" class="text-4xl text-[var(--ui-text)] transition-colors" />
                                </div>
                            </template>
                        </div>

                        <ul class="space-y-5 mb-12 flex-grow px-2">
                             <li v-for="beneficio in plan.beneficios_array" :key="beneficio" class="flex items-start gap-4 text-sm text-[var(--ui-text-muted)] font-medium group/item transition-colors">
                                <span class="w-10 h-10 rounded-full bg-[var(--color-primary-soft)] flex-shrink-0 flex items-center justify-center text-[var(--color-primary)] text-xs group-hover/item:bg-[var(--color-primary)] group-hover/item:text-white transition-all">
                                    <font-awesome-icon icon="check" />
                                </span>
                                <span class="group-hover/item:text-slate-900 dark:group-hover/item:text-white transition-colors">{{ beneficio }}</span>
                            </li>
                        </ul>

                        <Link 
                            v-if="plan.precio_mensual > 0"
                            :href="route('catalogo.polizas', { plan: plan.slug })" 
                            class="w-full py-6 rounded-2xl font-black text-xs uppercase tracking-wide text-center transition-all duration-200 relative overflow-hidden group/btn"
                            :class="plan.destacado ? 'bg-[var(--color-primary)] text-white shadow-xl hover:shadow-[var(--color-primary)]/50' : 'bg-slate-900 dark:bg-slate-800 text-white hover:bg-slate-800 dark:hover:bg-slate-700 border border-slate-700'"
                        >
                            <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-500"></div>
                            <span class="relative z-10">Contratar Plan</span>
                        </Link>
                        <a 
                            v-else
                            :href="whatsappLink"
                            target="_blank"
                            class="w-full py-6 rounded-2xl font-black text-xs uppercase tracking-wide text-center transition-all duration-200 border border-[var(--color-primary)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white hover:shadow-[0_0_30px_var(--color-primary)] flex items-center justify-center gap-3 group/btn"
                        >
                            Contactar Ventas
                            <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-lg group-hover/btn:scale-125 transition-transform" />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- TESTIMONIOS - Carrusel Animado Corregido -->
        <section class="py-24 bg-[var(--ui-surface)] overflow-hidden transition-colors duration-200">
            <div class="w-full px-4">
                <div class="text-center mb-16 w-full">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">Experiencias Reales</h2>
                    <h3 class="text-4xl lg:text-5xl font-black text-[var(--ui-text)] tracking-tighter transition-colors">Voces de nuestros <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Clientes Seguros</span></h3>
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
                    ])" :key="'t-' + testimonio.id + Math.random()" class="flex-shrink-0 w-[400px] bg-gradient-to-br from-[var(--ui-surface-alt)] to-[var(--ui-surface)] p-8 rounded-[2.5rem] border border-[var(--ui-border)] shadow-xl hover:shadow-2xl transition-all duration-500">
                        <div class="flex items-center gap-1 text-brand-400 mb-6">
                            <svg v-for="i in 5" :key="i" class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <p class="text-[var(--ui-text-muted)] font-medium mb-8 leading-relaxed italic line-clamp-4 transition-colors">"{{ testimonio.contenido }}"</p>
                        <div class="flex items-center gap-4 border-t border-[var(--ui-border)] pt-6 transition-colors">
                            <img :src="`https://i.pravatar.cc/100?u=${testimonio.id}`" class="w-10 h-10 rounded-2xl shadow-xl-sm" alt="Autor">
                            <div>
                                <h5 class="font-black text-[var(--ui-text)] text-sm transition-colors">{{ testimonio.nombre }}</h5>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[var(--ui-text-soft)]">{{ testimonio.entidad || 'Hogar' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fade edges -->
                <div class="absolute left-0 top-0 bottom-0 w-32 bg-gradient-to-r from-white dark:from-slate-950 to-transparent z-10 pointer-events-none transition-colors duration-200"></div>
                <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-white dark:from-slate-950 to-transparent z-10 pointer-events-none transition-colors duration-200"></div>
            </div>
        </section>

        <!-- FAQ SECTION -->
        <section class="py-24 bg-[var(--ui-surface)] overflow-hidden transition-colors duration-200">
            <div class="w-full px-4">
                 <div class="text-center mb-16">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-4">¿Dudas?</h2>
                    <h3 class="text-4xl lg:text-5xl font-black text-[var(--ui-text)] tracking-tighter transition-colors">Preguntas Frecuentes</h3>
                </div>

                <div class="space-y-6">
                    <div v-for="(faq, index) in (faqs?.length ? faqs : [
                        {id: 1, icon: 'bolt', pregunta: '¿Qué diferencia hay entre un MiniSplit Inverter y uno Convencional?', respuesta: 'La tecnología Inverter regula la velocidad del compresor para mantener la temperatura constante, ahorrando hasta un 60% de energía comparado con los equipos convencionales que encienden y apagan constantemente.'},
                        {id: 2, icon: 'calendar-check', pregunta: '¿Cada cuánto tiempo debo hacer mantenimiento a mi aire?', respuesta: 'Recomendamos un mantenimiento preventivo profundo cada 6 meses o al menos una vez al año antes de la temporada de calor para evitar fallas, malos olores y consumo excesivo de luz.'},
                        {id: 3, icon: 'shield-alt', pregunta: '¿Qué incluye la garantía de sus equipos?', respuesta: 'Ofrecemos 1 año de garantía en instalación (fugas, drenaje, cableado) y gestionamos directamente la garantía del fabricante, que puede ser de hasta 5 o 10 años en compresor dependiendo la marca.'},
                        {id: 4, icon: 'snowflake', pregunta: '¿Por qué mi MiniSplit tira agua o no enfría bien?', respuesta: 'Si tira agua suele ser por drenaje obstruido o filtros muy sucios. Si no enfría, puede ser falta de gas refrigerante o suciedad en el serpentín. Ambas cosas se solucionan con nuestro servicio de mantenimiento.'},
                        {id: 5, icon: 'ruler-combined', pregunta: '¿De cuántas toneladas necesito mi equipo?', respuesta: 'Como regla general: 1 Tonelada cubre hasta 16m², 1.5 Toneladas hasta 24m² y 2 Toneladas hasta 35m². Sin embargo, factores como ventanas, altura y personas influyen. ¡Nosotros te asesoramos!'},
                        {id: 6, icon: 'credit-card', pregunta: '¿Aceptan tarjetas de crédito o meses sin intereses?', respuesta: 'Sí, aceptamos todas las tarjetas bancarias y contamos con promociones a meses sin intereses. También aceptamos transferencias y efectivo.'}
                    ])" :key="faq.id" 
                        class="bg-[var(--ui-surface)] rounded-[2rem] border border-[var(--ui-border)] overflow-hidden shadow-sm hover:shadow-xl transition-all duration-200 group"
                        :class="{'ring-2 ring-[var(--color-primary-soft)]': activeFaq === faq.id}"
                    >
                        <button 
                            @click="toggleFaq(faq.id)"
                            class="w-full px-8 py-7 flex items-center justify-between text-left"
                        >
                            <div class="flex items-center gap-5">
                                <span class="w-10 h-10 rounded-2xl bg-[var(--color-primary)] flex items-center justify-center text-lg text-white shadow-xl shadow-[var(--color-primary)]/30 group-hover:scale-105 transition-transform duration-200">
                                    <font-awesome-icon :icon="faq.icon || 'question'" />
                                </span>
                                <span class="font-black text-[var(--ui-text)] group-hover:text-[var(--color-primary)] transition-colors text-lg leading-snug">{{ faq.pregunta }}</span>
                            </div>
                            <span class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 flex items-center justify-center text-slate-400 transition-transform duration-500 shrink-0" :class="{'rotate-180 bg-[var(--color-primary)] text-white shadow-xl': activeFaq === faq.id}">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>
                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="transform -translate-y-4 opacity-0"
                            enter-to-class="transform translate-y-0 opacity-100"
                            leave-active-class="transition duration-200 ease-in"
                            leave-from-class="transform translate-y-0 opacity-100"
                            leave-to-class="transform -translate-y-4 opacity-0"
                        >
                            <div v-if="activeFaq === faq.id" class="px-8 pb-8 pt-2">
                                <div class="pl-16 pr-8">
                                    <p class="text-[var(--ui-text-soft)] font-medium leading-relaxed border-t border-slate-50 dark:border-[var(--ui-border)] pt-6 text-[15px] transition-colors">{{ faq.respuesta }}</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <div class="mt-16 bg-gradient-to-br from-slate-900 to-slate-800 p-12 rounded-[3.5rem] relative overflow-hidden text-center text-white shadow-2xl">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[var(--color-primary)] rounded-full blur-[100px] opacity-10"></div>
                    <div class="relative z-10">
                        <h4 class="text-2xl font-black mb-4">¿No encuentras lo que buscas?</h4>
                        <p class="text-slate-400 mb-8 w-full">Nuestro equipo de expertos está listo para asesorarte de forma personalizada y sin compromiso.</p>
                        <a :href="whatsappLink" target="_blank" class="inline-flex items-center gap-2 px-10 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-xs uppercase tracking-wide shadow-xl hover:scale-105 transition-all">
                             <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
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

/* Animación de Aire Frío (Minisplit) */
@keyframes air-curtain-flow {
    from { background-position-y: 0; }
    to { background-position-y: 400px; }
}

.air-curtain {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: repeating-linear-gradient(
        to bottom,
        transparent 0px,
        rgba(255, 255, 255, 0.4) 80px,
        rgba(56, 189, 248, 0.3) 160px,
        transparent 320px
    );
    background-size: 100% 320px;
    filter: blur(8px);
    opacity: 1;
    mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent), linear-gradient(to bottom, black 60%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent), linear-gradient(to bottom, black 60%, transparent 100%);
    mask-composite: intersect;
    -webkit-mask-composite: source-in;
}

.layer-1 { animation: air-curtain-flow 4s linear infinite; opacity: 0.9; }
.layer-2 { animation: air-curtain-flow 6s linear infinite; opacity: 0.7; filter: blur(12px); animation-delay: -2s; }
.layer-3 { animation: air-curtain-flow 5s linear infinite; opacity: 0.6; filter: blur(10px); animation-delay: -1s; }

.dark .air-curtain {
    background-image: repeating-linear-gradient(
        to bottom,
        transparent 0px,
        rgba(255, 255, 255, 0.5) 80px,
        rgba(56, 189, 248, 0.45) 160px,
        transparent 320px
    );
}

@keyframes flap-swing {
    0%, 100% { transform: rotateX(-5deg); }
    50% { transform: rotateX(-75deg); }
}

.minisplit-flap-enhanced {
    position: absolute;
    bottom: -2px;
    left: 2%;
    width: 96%;
    height: 8px;
    background: linear-gradient(to bottom, #ffffff 0%, #e2e8f0 50%, #94a3b8 100%);
    transform-origin: top;
    animation: flap-swing 10s ease-in-out infinite;
    border-radius: 2px 2px 12px 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    border: 1px solid rgba(0,0,0,0.05);
    z-index: 30;
}

.dark .minisplit-flap-enhanced {
    background: linear-gradient(to bottom, #cbd5e1 0%, #94a3b8 50%, #475569 100%);
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
}

.minisplit-flap-enhanced.flap-stopped {
    animation: none;
    transform: rotateX(0deg);
}

.perspective-2000 {
    perspective: 2000px;
}

.animate-airflow-1 { animation: airflow 1.5s ease-in infinite; left: 10%; }
.animate-airflow-2 { animation: airflow 2.2s ease-in infinite; left: 30%; animation-delay: 0.3s; }
.animate-airflow-3 { animation: airflow 1.8s ease-in infinite; left: 50%; animation-delay: 0.7s; }
.animate-airflow-4 { animation: airflow 2.5s ease-in infinite; left: 70%; animation-delay: 0.2s; }
.animate-airflow-5 { animation: airflow 2s ease-in infinite; left: 90%; animation-delay: 0.5s; }
</style>
