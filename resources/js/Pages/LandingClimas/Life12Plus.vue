<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({ empresa: Object });

const scrollY = ref(0);
const showVideo = ref(false);
const sectionVisibility = ref({
    hero: false,
    features: false,
    details: false,
    gallery: false,
    comparison: false,
    testimonials: false,
    steps: false,
    promo: false,
    guide: false,
    models: false,
    faq: false
});

// Smooth scroll with requestAnimationFrame
let ticking = false;
const handleScroll = () => {
    if (!ticking) {
        window.requestAnimationFrame(() => {
            scrollY.value = window.scrollY;
            ticking = false;
        });
        ticking = true;
    }
};

// Computed parallax transforms
const parallaxRow1 = computed(() => `translateX(${scrollY.value * -0.15}px)`);
const parallaxRow2 = computed(() => `translateX(${scrollY.value * 0.12}px)`);
const heroImageFloat = computed(() => `translateY(${Math.sin(scrollY.value * 0.003) * 8}px)`);
const heroOpacity = computed(() => Math.max(0, 1 - scrollY.value / 600));
const heroScale = computed(() => Math.max(0.85, 1 - scrollY.value * 0.0002));

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Trigger hero immediately
    nextTick(() => { sectionVisibility.value.hero = true; });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                sectionVisibility.value[entry.target.dataset.section] = true;
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('[data-section]').forEach(el => observer.observe(el));
});
onUnmounted(() => { window.removeEventListener('scroll', handleScroll); });

const features = [
    { title: 'Alta Eficiencia', icon: 'bolt', color: 'from-sky-500 to-blue-600', text: 'Optimiza el consumo energético manteniendo el máximo confort en tu hogar.' },
    { title: 'Ultra Silencioso', icon: 'moon', color: 'from-cyan-400 to-sky-500', text: 'Tecnología de bajo ruido para un descanso sin interrupciones.' },
    { title: 'Healthy Clean', icon: 'shield-virus', color: 'from-blue-600 to-indigo-600', text: 'Elimina bacterias y malos olores del serpentín automáticamente.' },
    { title: 'Turbo Cooling', icon: 'wind', color: 'from-sky-400 to-cyan-400', text: 'Enfriamiento ultra rápido para los días más calurosos de Sonora.' },
];

const specs = [
    { label: 'Capacidad', value: '1.0 Ton (12,000 BTU)', icon: 'snowflake' },
    { label: 'Voltaje', value: '220V / 1Ph / 60Hz', icon: 'plug' },
    { label: 'Refrigerante', value: 'R-410A Ecológico', icon: 'leaf' },
    { label: 'Tipo', value: 'Solo Frío (On/Off)', icon: 'thermometer-half' },
    { label: 'Gas Refrigerante', value: 'No tóxico, no flamable', icon: 'check-circle' },
    { label: 'Tecnologías', value: 'Library App* · Ozone Fin · Turbo', icon: 'microchip' },
];

const highlights = [
    { icon: 'mobile-alt', title: 'Library App', desc: 'Control inteligente desde tu smartphone (WiFi Ready*)' },
    { icon: 'shield-alt', title: 'Ozone Fin', desc: 'Anticorrosión para mayor durabilidad' },
    { icon: 'tachometer-alt', title: 'Turbo Mode', desc: 'Alcanza la temperatura ideal en minutos' },
    { icon: 'sync-alt', title: 'Auto Restart', desc: 'Se reinicia automáticamente tras un corte de luz' },
    { icon: 'award', title: 'Garantía Total', desc: '6 años en compresor y 1 en partes' },
];

const whatsappUrl = `https://wa.me/526624317082?text=${encodeURIComponent('Hola, me interesa el Minisplit Mirage Life 12+. ¿Cuál es el precio y disponibilidad?')}`;
</script>

<template>
    <Head>
        <title>Mirage Life 12+ | Minisplit Premium | {{ empresa?.nombre || 'Climas del Desierto' }}</title>
        <meta name="description" content="Minisplit Mirage Life 12+ de 1 Tonelada. Diseño premium, ultra silencioso, gas R-410A ecológico. Instalación profesional en Hermosillo." />
    </Head>

    <div class="life12-page bg-white dark:bg-slate-950 text-gray-900 dark:text-white font-sans selection:bg-sky-500 selection:text-white">
        <PublicNavbar :empresa="empresa" activeTab="life12plus" />

        <!-- ═══════════════════════════════════════════ -->
        <!-- HERO: FULL SCREEN PRODUCT SHOWCASE -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="hero" class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-slate-50 via-white to-sky-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
            <!-- Multi-Layer Parallax Background Text -->
            <div class="absolute inset-0 select-none pointer-events-none overflow-hidden">
                <div class="absolute top-1/4 left-0 text-[15vw] font-black text-slate-200/10 dark:text-white/[0.03] whitespace-nowrap leading-none transition-transform duration-100 ease-out will-change-transform" :style="{ transform: parallaxRow1 }">
                    LIFE 12 PLUS · MIRAGE · PREMIUM · 
                </div>
                <div class="absolute bottom-1/4 right-0 text-[15vw] font-black text-sky-200/10 dark:text-sky-400/[0.03] whitespace-nowrap leading-none transition-transform duration-100 ease-out will-change-transform" :style="{ transform: parallaxRow2 }">
                    HIGH EFFICIENCY · SILENT · DURABLE ·
                </div>
            </div>

            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute top-1/4 -right-32 w-[600px] h-[600px] bg-sky-500/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 -left-32 w-[400px] h-[400px] bg-cyan-500/5 rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-20 lg:py-0">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Left: Content -->
                    <div class="order-2 lg:order-1 transition-all duration-700" :class="sectionVisibility.hero ? 'translate-x-0 opacity-100' : '-translate-x-12 opacity-0'">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-sky-100 dark:bg-sky-500/20 text-sky-600 dark:text-sky-300 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
                            <span class="w-2 h-2 bg-sky-500 rounded-full animate-ping"></span>
                            Stock Disponible: Hermosillo
                        </div>

                        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tighter leading-[0.9] mb-6">
                            <span class="text-gray-900 dark:text-white">MIRAGE</span><br>
                            <span class="bg-gradient-to-r from-sky-500 to-cyan-400 bg-clip-text text-transparent">LIFE 12</span>
                            <span class="text-sky-500 text-4xl lg:text-5xl align-super">+</span>
                        </h1>

                        <p class="text-lg text-slate-600 dark:text-slate-300 mb-10 max-w-lg leading-relaxed">
                            Diseñado para Sonora. El minisplit <span class="text-sky-500 font-bold">más resistente</span> y eficiente para tu hogar. Tecnología de vanguardia con respaldo total.
                        </p>

                        <div class="flex flex-wrap items-center gap-6 mb-10">
                            <div class="text-center">
                                <span class="block text-3xl font-black text-sky-500">12,000</span>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">BTU</span>
                            </div>
                            <div class="w-px h-10 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="text-center">
                                <span class="block text-3xl font-black text-sky-500">220V</span>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Voltaje</span>
                            </div>
                            <div class="w-px h-10 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="text-center">
                                <span class="block text-3xl font-black text-sky-500">R-410A</span>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Ecológico</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <a :href="whatsappUrl" target="_blank" class="group inline-flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-sky-500 to-cyan-500 text-white font-black rounded-2xl hover:shadow-2xl hover:shadow-sky-500/30 hover:scale-105 transition-all duration-300 uppercase text-xs tracking-widest">
                                <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-lg" />
                                Cotizar por WhatsApp
                            </a>
                            <a href="#video" class="group inline-flex items-center justify-center gap-3 px-8 py-4 bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 font-black rounded-2xl hover:bg-gray-200 dark:hover:bg-white/10 transition-all duration-300 uppercase text-xs tracking-widest border border-gray-200 dark:border-white/10">
                                <font-awesome-icon icon="play-circle" class="text-lg text-sky-500" />
                                Ver Video
                            </a>
                        </div>
                    </div>

                    <!-- Right: Product Image -->
                    <div class="order-1 lg:order-2 relative transition-all duration-1000 delay-300" :class="sectionVisibility.hero ? 'scale-100 opacity-100' : 'scale-90 opacity-0'" :style="{ opacity: heroOpacity, transform: `scale(${heroScale})` }">
                        <div class="absolute inset-0 bg-gradient-to-br from-sky-400/30 to-cyan-400/30 rounded-[3rem] blur-3xl scale-90 opacity-60"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-sky-500/10 via-transparent to-transparent rounded-[3rem]"></div>
                        <!-- Floating Product with scroll-driven float -->
                        <div class="relative p-8 sm:p-12 group" :style="{ transform: heroImageFloat }">
                            <img src="/images/products/life12plus/product-nobg.png" class="relative z-10 w-full h-auto drop-shadow-[0_20px_40px_rgba(14,165,233,0.25)] transform group-hover:scale-105 group-hover:-translate-y-2 transition-all duration-700" alt="Mirage Life 12+ Real">
                            
                            <!-- Glow effect underneath -->
                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-3/4 h-4 bg-sky-500/30 rounded-full blur-xl"></div>

                            <!-- Price Badge - PROMO -->
                            <div class="absolute bottom-0 right-4 z-20 bg-gradient-to-br from-sky-600 to-blue-700 text-white px-6 py-4 rounded-2xl shadow-xl shadow-sky-500/30 animate-pulse-soft">
                                <span class="text-[10px] font-bold uppercase tracking-widest opacity-90 block text-yellow-300"><font-awesome-icon icon="fire" class="mr-1" />Súper Promo</span>
                                <span class="text-sm line-through opacity-60 block">$7,499</span>
                                <span class="text-2xl sm:text-3xl font-black text-white">$4,900</span>
                                <span class="text-xs opacity-80 block">Solo equipo · IVA incl.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- FEATURES: 4 CARDS -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="features" class="py-20 lg:py-32 px-4 relative bg-white dark:bg-slate-950">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Tecnología Premium</span>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight">Lo mejor para tu hogar</h2>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 relative z-10">
                    <div v-for="(f, i) in features" :key="i" 
                        class="group p-8 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-white/50 dark:border-white/5 shadow-xl hover:shadow-sky-500/10 transition-all duration-500 hover:-translate-y-2"
                        :class="sectionVisibility.features ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'"
                        :style="{ transitionDelay: `${i * 100}ms` }">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white mb-6 transform group-hover:rotate-12 transition-transform shadow-lg" :class="f.color">
                            <font-awesome-icon :icon="f.icon" class="text-2xl" />
                        </div>
                        <h3 class="text-xl font-black mb-3 text-slate-900 dark:text-white">{{ f.title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-gray-400 leading-relaxed">{{ f.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- PRODUCT DETAIL: SPLIT SECTION -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="details" class="py-20 lg:py-32 px-4 bg-white dark:bg-slate-950">
            <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
                <div class="relative flex items-center justify-center transition-all duration-1000" :class="sectionVisibility.details ? 'opacity-100' : 'opacity-0'">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-500/20 to-cyan-500/20 rounded-[3rem] blur-2xl"></div>
                    <img src="/images/products/life12plus/product-nobg.png" class="relative z-10 w-full max-w-lg h-auto drop-shadow-[0_20px_40px_rgba(14,165,233,0.3)]" alt="Mirage Life 12+">
                </div>

                <div>
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Tecnologías Incluidas</span>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight mb-10 leading-tight">Diseñado para <br><span class="text-sky-500">el clima de Sonora</span></h2>

                    <div class="grid grid-cols-2 gap-6">
                        <div v-for="h in highlights" :key="h.title" class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/5">
                            <div class="w-10 h-10 flex-shrink-0 bg-sky-500/10 rounded-xl flex items-center justify-center text-sky-500">
                                <font-awesome-icon :icon="h.icon" />
                            </div>
                            <div>
                                <h5 class="font-bold text-sm mb-1">{{ h.title }}</h5>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ h.desc }}</p>
                            </div>
                        </div>
                    </div>
                    <p class="mt-8 text-[10px] text-gray-400">*El control vía WiFi requiere módulo adicional no incluido en el equipo base.</p>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- LIFESTYLE GALLERY: COMFORT IN EVERY SPACE -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="gallery" class="py-20 lg:py-32 px-4 bg-white dark:bg-slate-950 overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-8 mb-8">
                    <!-- Living Room Experience -->
                    <div class="relative rounded-[3rem] overflow-hidden group h-[500px] lg:h-[600px] shadow-2xl transition-all duration-1000" :class="sectionVisibility.gallery ? 'translate-x-0 opacity-100' : '-translate-x-20 opacity-0'">
                        <img src="/images/products/life12plus/real-room.png" class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-[10s] ease-linear" alt="Minisplit en sala real">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-12 left-12 right-12 text-white">
                            <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-400 mb-4 block">En tu Sala</span>
                            <h3 class="text-3xl lg:text-4xl font-black mb-4 tracking-tight">Diseño que complementa tu hogar</h3>
                            <p class="text-white/70 text-sm max-w-md leading-relaxed">Su estética minimalista se adapta a cualquier estilo de decoración, aportando un toque de modernidad y frescura.</p>
                        </div>
                    </div>

                    <!-- Bedroom Experience -->
                    <div class="relative rounded-[3rem] overflow-hidden group h-[500px] lg:h-[600px] shadow-2xl transition-all duration-1000 delay-300" :class="sectionVisibility.gallery ? 'translate-x-0 opacity-100' : 'translate-x-20 opacity-0'">
                        <img src="/images/products/life12plus/bedroom.png" class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-[10s] ease-linear" alt="Minisplit en recámara">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-12 left-12 right-12 text-white">
                            <span class="text-xs font-black uppercase tracking-[0.3em] text-cyan-400 mb-4 block">En tu Descanso</span>
                            <h3 class="text-3xl lg:text-4xl font-black mb-4 tracking-tight">El silencio que mereces</h3>
                            <p class="text-white/70 text-sm max-w-md leading-relaxed">Olvida que está encendido. Su modo nocturno garantiza un descanso reparador sin ruidos ni corrientes directas.</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <p class="text-gray-500 dark:text-gray-400 italic">"Sentirás el confort, no el ruido."</p>
                </div>

                <!-- Warranty & Confidence Badges -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mt-20 border-t border-gray-100 dark:border-white/5 pt-12">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-sky-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <font-awesome-icon icon="shield-alt" class="text-sky-500 text-xl" />
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-widest mb-1">6 Años</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-tighter">Garantía en Compresor</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-sky-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <font-awesome-icon icon="certificate" class="text-sky-500 text-xl" />
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-widest mb-1">Certificado</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-tighter">Técnicos Autorizados</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-sky-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <font-awesome-icon icon="truck" class="text-sky-500 text-xl" />
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-widest mb-1 text-slate-800 dark:text-white">Inmediato</h4>
                        <p class="text-[10px] text-sky-600/60 dark:text-sky-400/60 uppercase tracking-tighter font-bold">Entrega en Hermosillo</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-sky-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <font-awesome-icon icon="credit-card" class="text-sky-500 text-xl" />
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-widest mb-1">MSI</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-tighter">Tarjetas Participantes</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- COMPARISON: WHY LIFE 12+? -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="comparison" class="py-20 lg:py-32 px-4 bg-white dark:bg-slate-950">
            <div class="max-w-4xl mx-auto transition-all duration-1000" :class="sectionVisibility.comparison ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">La Diferencia Mirage</span>
                    <h2 class="text-3xl lg:text-4xl font-black tracking-tight">¿Por qué elegir Life 12+?</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-0 rounded-[3rem] overflow-hidden border border-gray-100 dark:border-white/5 shadow-2xl">
                    <div class="p-8 lg:p-12 bg-gray-50 dark:bg-slate-900/50">
                        <h4 class="text-gray-400 font-black uppercase tracking-widest text-sm mb-8">Minisplit Estándar</h4>
                        <ul class="space-y-6">
                            <li class="flex items-center gap-3 text-gray-400 text-sm italic line-through decoration-gray-300">
                                <font-awesome-icon icon="times-circle" /> Ruido perceptible al encender
                            </li>
                            <li class="flex items-center gap-3 text-gray-400 text-sm italic line-through decoration-gray-300">
                                <font-awesome-icon icon="times-circle" /> Sin protección anticorrosiva
                            </li>
                            <li class="flex items-center gap-3 text-gray-400 text-sm italic line-through decoration-gray-300">
                                <font-awesome-icon icon="times-circle" /> Consumo eléctrico inconsistente
                            </li>
                        </ul>
                    </div>
                    <div class="p-8 lg:p-12 bg-gradient-to-br from-sky-500 to-blue-600 text-white relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                        <h4 class="text-sky-200 font-black uppercase tracking-widest text-sm mb-8">Mirage Life 12+</h4>
                        <ul class="space-y-6">
                            <li class="flex items-center gap-3 font-bold">
                                <font-awesome-icon icon="check-circle" class="text-sky-300" /> Operación ultra silenciosa (25dB)
                            </li>
                            <li class="flex items-center gap-3 font-bold">
                                <font-awesome-icon icon="check-circle" class="text-sky-300" /> Ozone Fin (Anticorrosión)
                            </li>
                            <li class="flex items-center gap-3 font-bold">
                                <font-awesome-icon icon="check-circle" class="text-sky-300" /> Alta Eficiencia Energética
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TESTIMONIALS: REAL PEOPLE -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="testimonials" class="py-20 bg-sky-50/30 dark:bg-slate-900/30">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16 transition-all duration-1000" :class="sectionVisibility.testimonials ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Clientes Satisfechos</span>
                    <h2 class="text-3xl lg:text-4xl font-black tracking-tight">Confianza en Sonora</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(t, i) in [
                        { name: 'Carlos Mendoza', loc: 'Hillo, San Germán', text: 'Excelente servicio. Instalaron el mismo día y el equipo no hace nada de ruido. Muy recomendado.', stars: 5 },
                        { name: 'Ana Sofía Leyva', loc: 'Hillo, Puerta Real', text: 'Buscaba algo económico pero duradero por el calor de aquí. El Life 12+ superó mis expectativas.', stars: 5 },
                        { name: 'Ricardo G.', loc: 'Hillo, La Joya', text: 'La atención por WhatsApp fue súper rápida. Agendamos y en 24 horas ya estaba mi cuarto frío.', stars: 5 }
                    ]" :key="i" class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-gray-100 dark:border-white/5 shadow-xl transition-all hover:-translate-y-2">
                        <div class="flex text-yellow-400 mb-4 gap-1">
                            <font-awesome-icon v-for="s in t.stars" :key="s" icon="star" />
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm italic mb-6 leading-relaxed">"{{ t.text }}"</p>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-sky-500/10 rounded-full flex items-center justify-center text-sky-500 font-black text-xs">{{ t.name[0] }}</div>
                            <div>
                                <h5 class="text-sm font-black">{{ t.name }}</h5>
                                <p class="text-[10px] text-gray-400 uppercase font-bold">{{ t.loc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section data-section="steps" class="py-20 bg-slate-50 dark:bg-slate-900/50">
            <div class="max-w-5xl mx-auto px-4">
                <div class="text-center mb-12 transition-all duration-1000" :class="sectionVisibility.steps ? 'opacity-100' : 'opacity-0'">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Fácil y Rápido</span>
                    <h2 class="text-3xl lg:text-4xl font-black tracking-tight">Tu confort en 3 pasos</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(step, i) in [
                        { n: '1', t: 'Elige tu modelo', d: 'Selecciona la capacidad ideal para tu espacio.' },
                        { n: '2', t: 'Contáctanos', d: 'Envíanos un WhatsApp para agendar tu equipo.' },
                        { n: '3', t: 'Instalamos', d: 'En menos de 24 horas estarás disfrutando del clima ideal.', icon: 'check-double' }
                    ]" :key="i" class="relative text-center group transition-all duration-1000" 
                       :class="sectionVisibility.steps ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'"
                       :style="{ transitionDelay: `${i * 200}ms` }">
                        <div :class="`w-16 h-16 ${step.n === '3' ? 'bg-sky-500 shadow-sky-500/20 text-white' : 'bg-white dark:bg-slate-950 text-sky-500'} rounded-2xl shadow-xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform`">
                            <font-awesome-icon v-if="step.icon" :icon="step.icon" class="text-xl" />
                            <span v-else class="text-2xl font-black">{{ step.n }}</span>
                        </div>
                        <h4 class="font-black mb-2 uppercase tracking-widest text-sm">{{ step.t }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ step.d }}</p>
                        <div v-if="i < 2" class="hidden md:block absolute top-8 -right-4 w-8 h-px bg-gray-200 dark:bg-white/10"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- VIDEO SECTION -->
        <!-- ═══════════════════════════════════════════ -->
        <section id="video" class="py-20 lg:py-32 px-4 bg-slate-950">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-12">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-400 mb-4 block">Conócelo en acción</span>
                    <h2 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Míralo en video</h2>
                </div>

                <div class="relative rounded-[2rem] overflow-hidden shadow-2xl shadow-sky-500/10 border border-white/10 aspect-video">
                    <!-- Thumbnail con botón de play -->
                    <div v-if="!showVideo" class="absolute inset-0 cursor-pointer group" @click="showVideo = true">
                        <img src="https://img.youtube.com/vi/URauTQCgCOs/maxresdefault.jpg" class="w-full h-full object-cover" alt="Video Mirage Life 12+">
                        <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                            <div class="w-20 h-20 bg-sky-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform shadow-2xl shadow-sky-500/40">
                                <font-awesome-icon icon="play" class="text-white text-2xl ml-1" />
                            </div>
                        </div>
                    </div>

                    <!-- YouTube iframe -->
                    <iframe v-else src="https://www.youtube.com/embed/URauTQCgCOs?autoplay=1&rel=0" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SPECS TABLE -->
        <!-- ═══════════════════════════════════════════ -->
        <section class="py-20 lg:py-32 px-4 bg-gray-50 dark:bg-slate-900/50">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Ficha Técnica</span>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight">Especificaciones</h2>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-white/5 overflow-hidden shadow-xl">
                    <div v-for="(spec, i) in specs" :key="i" class="flex items-center justify-between p-6 border-b border-gray-50 dark:border-white/5 last:border-0 hover:bg-sky-50/50 dark:hover:bg-sky-500/5 transition-colors group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-sky-500/10 rounded-xl flex items-center justify-center text-sky-500 group-hover:bg-sky-500 group-hover:text-white transition-all">
                                <font-awesome-icon :icon="spec.icon" />
                            </div>
                            <span class="font-bold text-sm text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ spec.label }}</span>
                        </div>
                        <span class="font-black text-base lg:text-lg text-right">{{ spec.value }}</span>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <a href="https://images.mirage.mx/pdf/specs/minisplit_life12.pdf" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-bold text-gray-600 dark:text-gray-300 hover:border-sky-300 hover:text-sky-500 transition-all">
                        <font-awesome-icon icon="file-pdf" class="text-red-500" />
                        Ficha Técnica PDF
                    </a>
                    <a href="https://images.mirage.mx/pdf/man/Manual_Life12_2022.pdf" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-bold text-gray-600 dark:text-gray-300 hover:border-sky-300 hover:text-sky-500 transition-all">
                        <font-awesome-icon icon="book" class="text-sky-500" />
                        Manual de Uso
                    </a>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SÚPER PROMOCIÓN DESTACADA -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="promo" class="py-20 lg:py-32 px-4 bg-gradient-to-br from-slate-900 via-sky-950 to-blue-950 relative overflow-hidden border-y border-sky-500/20">
            <div class="absolute inset-0">
                <div class="absolute -top-32 -left-32 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>
            <div class="relative z-10 max-w-5xl mx-auto transition-all duration-1000" :class="sectionVisibility.promo ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 px-5 py-2 bg-sky-500/20 border border-sky-500/30 backdrop-blur-sm rounded-full mb-6 animate-pulse-soft">
                        <font-awesome-icon icon="fire" class="text-yellow-400" />
                        <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-200">Oferta por Tiempo Limitado</span>
                        <font-awesome-icon icon="fire" class="text-yellow-400" />
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-black text-white tracking-tight mb-4">Súper Promoción</h2>
                    <p class="text-white/80 text-lg mb-8">Minisplit Life 12+ · 1 Ton · 220V · Solo Frío</p>
                    
                    <!-- Countdown / Scarcity -->
                    <div class="inline-block px-6 py-3 bg-rose-500/10 backdrop-blur-md rounded-2xl border border-rose-500/20 mb-4 shadow-lg shadow-rose-500/5">
                        <p class="text-rose-200 text-sm font-bold">
                            <font-awesome-icon icon="clock" class="mr-2 animate-pulse" />
                            ¡Solo quedan <span class="text-yellow-400 font-black">4 unidades</span> a este precio!
                        </p>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                    <!-- Solo Equipo -->
                    <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 border border-white/10 text-center text-white hover:bg-white/10 transition-all hover:scale-105 duration-300">
                        <div class="w-16 h-16 bg-sky-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <font-awesome-icon icon="snowflake" class="text-3xl text-sky-400" />
                        </div>
                        <h3 class="text-xl font-black uppercase tracking-widest mb-2 text-sky-100">Solo Equipo</h3>
                        <p class="text-sky-200/60 text-sm mb-4">Minisplit Life 12+ <span class="font-bold text-sky-200">1 Ton 220V Solo Frío</span> listo para instalar</p>
                        
                        <div class="bg-white/5 rounded-2xl p-4 mb-6 text-left space-y-2">
                            <div class="flex items-center gap-2 text-[10px] uppercase font-black text-sky-300">
                                <font-awesome-icon icon="check" /> Unidad Interior y Exterior
                            </div>
                            <div class="flex items-center gap-2 text-[10px] uppercase font-black text-sky-300">
                                <font-awesome-icon icon="check" /> Kit de 3m tubería y cableado
                            </div>
                            <div class="flex items-center gap-2 text-[10px] uppercase font-black text-sky-300">
                                <font-awesome-icon icon="check" /> Control Remoto y Baterías
                            </div>
                            <div class="flex items-center gap-2 text-[9px] uppercase font-bold text-amber-400/80 italic mt-2">
                                <font-awesome-icon icon="info-circle" /> No incluye material eléctrico
                            </div>
                        </div>

                        <span class="text-lg line-through text-slate-400 block">$7,499</span>
                        <span class="text-5xl font-black block my-2 text-white">$4,900</span>
                        <span class="text-xs opacity-70 block mb-6">IVA incluido</span>
                        <a :href="whatsappUrl" target="_blank" class="inline-flex items-center gap-2 px-8 py-4 bg-sky-500/20 border border-sky-500/50 text-white font-black rounded-2xl hover:bg-sky-500 hover:shadow-2xl hover:shadow-sky-500/30 transition-all uppercase text-xs tracking-widest">
                            <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-lg" />
                            Lo Quiero
                        </a>
                    </div>

                    <!-- Con Instalación -->
                    <div class="bg-gradient-to-b from-sky-500 to-blue-600 rounded-3xl p-8 border-4 border-sky-400 text-center text-white shadow-2xl shadow-sky-500/20 hover:scale-105 transition-all duration-300 relative">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-yellow-400 text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg border border-yellow-200">
                            <font-awesome-icon icon="star" class="mr-1" /> Recomendado
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-6 backdrop-blur-sm">
                            <font-awesome-icon icon="wrench" class="text-3xl text-white" />
                        </div>
                        <h3 class="text-xl font-black uppercase tracking-widest mb-2">Equipo + Instalación</h3>
                        <p class="text-sky-100 text-sm mb-4">Modelo <span class="font-bold text-white">1 Ton 220V Solo Frío</span> con instalación profesional incluida</p>
                        
                        <div class="bg-black/10 rounded-2xl p-4 mb-6 text-left space-y-2 border border-white/5">
                            <div class="flex items-center gap-2 text-[10px] uppercase font-black text-white">
                                <font-awesome-icon icon="check-double" /> Todo lo del equipo anterior
                            </div>
                            <div class="flex items-center gap-2 text-[10px] uppercase font-black text-white">
                                <font-awesome-icon icon="check-double" /> Instalación profesional
                            </div>
                            <div class="flex items-center gap-2 text-[10px] uppercase font-black text-white">
                                <font-awesome-icon icon="check-double" /> Mano de obra certificada
                            </div>
                            <div class="flex items-center gap-2 text-[9px] uppercase font-bold text-sky-200/80 italic mt-2">
                                <font-awesome-icon icon="info-circle" /> No incluye material eléctrico
                            </div>
                        </div>

                        <span class="text-lg line-through text-sky-200/50 block">$9,999</span>
                        <span class="text-5xl font-black text-yellow-300 block my-2 drop-shadow-sm">$5,900</span>
                        <span class="text-xs text-sky-100/70 block mb-6 uppercase tracking-wider">IVA incluido · Todo incluido</span>
                        <a :href="whatsappUrl" target="_blank" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-blue-700 font-black rounded-2xl hover:bg-sky-50 hover:shadow-2xl hover:shadow-white/40 transition-all uppercase text-xs tracking-widest">
                            <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-lg" />
                            Agendar Instalación
                        </a>
                    </div>
                </div>

                <!-- Trust Badges Bar -->
                <div class="mt-16 flex flex-wrap justify-center items-center gap-10 lg:gap-14 opacity-50 hover:opacity-100 transition-opacity">
                    <font-awesome-icon :icon="['fab', 'cc-visa']" class="text-4xl" title="Visa" />
                    <font-awesome-icon :icon="['fab', 'cc-mastercard']" class="text-4xl" title="Mastercard" />
                    <font-awesome-icon :icon="['fab', 'cc-amex']" class="text-4xl" title="American Express" />
                    <font-awesome-icon :icon="['fab', 'cc-paypal']" class="text-4xl" title="PayPal" />
                    <div class="flex items-center gap-2 text-white font-black text-[10px] uppercase tracking-[0.2em]">
                        <font-awesome-icon icon="shield-alt" class="text-sky-400" />
                        Compra 100% Segura
                    </div>
                    <div class="flex items-center gap-2 text-white font-black text-[10px] uppercase tracking-[0.2em]">
                        <font-awesome-icon icon="award" class="text-yellow-400" />
                        Distribuidor Autorizado
                    </div>
                </div>

                <p class="text-center text-white/50 text-xs mt-12">*Promoción válida hasta agotar existencias. Aplica solo para el modelo ELF120D (1 Ton, 220V, Solo Frío).</p>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- GUÍA DE SELECCIÓN -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="guide" class="py-20 lg:py-32 px-4 bg-slate-950 text-white overflow-hidden relative">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-0 right-0 w-96 h-96 bg-sky-500 rounded-full blur-[120px]"></div>
            </div>
            <div class="max-w-5xl mx-auto relative z-10 transition-all duration-1000" :class="sectionVisibility.guide ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-400 mb-4 block">Guía de Compra</span>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight">¿Qué capacidad necesitas?</h2>
                </div>

                <div class="grid sm:grid-cols-3 gap-8">
                    <div v-for="(g, i) in [
                        { t: '1 Tonelada', area: 'Hasta 16 m²', icon: 'bed', items: ['Recámaras estándar', 'Oficinas pequeñas', 'Estudios'] },
                        { t: '1.5 Toneladas', area: 'Hasta 24 m²', icon: 'tv', items: ['Salas medianas', 'Comedores', 'Recámaras Master'] },
                        { t: '2 Toneladas', area: 'Hasta 35 m²', icon: 'store', items: ['Áreas abiertas', 'Locales comerciales', 'Salas grandes'] }
                    ]" :key="i" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-3xl p-8 text-center hover:bg-white/10 transition-all hover:-translate-y-2">
                        <div class="w-16 h-16 bg-sky-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 text-sky-400">
                            <font-awesome-icon :icon="g.icon" class="text-3xl" />
                        </div>
                        <h4 class="text-2xl font-black mb-1">{{ g.t }}</h4>
                        <p class="text-sky-400 font-bold mb-4">{{ g.area }}</p>
                        <ul class="text-sm text-gray-400 space-y-2">
                            <li v-for="item in g.items" :key="item">{{ item }}</li>
                        </ul>
                    </div>
                </div>
                <p class="text-center text-gray-500 text-xs mt-12 italic">*Las áreas son aproximadas y pueden variar según la carga térmica de la habitación (ventanas, sol, etc.)</p>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- OTROS MODELOS -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="models" class="py-20 lg:py-32 px-4 bg-white dark:bg-slate-950">
            <div class="max-w-5xl mx-auto transition-all duration-1000" :class="sectionVisibility.models ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Línea Completa</span>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight mb-4">Otros Modelos</h2>
                    <p class="text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Disponible en versiones de solo frío y frío/calor, en 110V y 220V</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="m in [
                        { ton: '1 Ton', btu: '12k', volt: '110V', tipo: 'Solo Frío', modelo: 'ELF121D', precio: '$7,899' },
                        { ton: '1 Ton', btu: '12k', volt: '220V', tipo: 'Frío/Calor', modelo: 'ELC120D', precio: '$8,299' },
                        { ton: '1.5 Ton', btu: '18k', volt: '220V', tipo: 'Solo Frío', modelo: 'ELF181D', precio: '$10,999' },
                        { ton: '2 Ton', btu: '26k', volt: '220V', tipo: 'Solo Frío', modelo: 'ELF261D', precio: '$14,499' },
                    ]" :key="m.modelo" class="relative bg-sky-50/20 dark:bg-slate-900 rounded-3xl p-5 border border-sky-100 dark:border-white/5 hover:border-sky-300 transition-all group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-lg font-black text-sky-600 dark:text-sky-400">{{ m.ton }}</span>
                            <span class="text-[8px] font-black text-sky-700/60 bg-sky-100/50 px-2 py-1 rounded-full uppercase tracking-widest">{{ m.volt }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ m.tipo }}</p>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ m.precio }}</span>
                            <a :href="whatsappUrl" target="_blank" class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white text-xs hover:scale-110 transition-all">
                                <font-awesome-icon :icon="['fab', 'whatsapp']" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- FAQ SECTION -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="faq" class="py-20 lg:py-32 px-4 bg-slate-50 dark:bg-slate-900/30">
            <div class="max-w-3xl mx-auto transition-all duration-1000" :class="sectionVisibility.faq ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-sky-500 mb-4 block">Resuelve tus dudas</span>
                    <h2 class="text-4xl font-black tracking-tight mb-4 text-slate-900 dark:text-white">Preguntas Frecuentes</h2>
                </div>

                <div class="space-y-4">
                    <div v-for="(faq, index) in [
                        { q: '¿Qué incluye la instalación profesional?', a: 'Incluye 3 metros de tubería, cable de interconexión, cinta vinílica, soporte de evaporador y mano de obra experta.' },
                        { q: '¿Cuánto tiempo tarda la instalación?', a: 'Nuestro equipo técnico realiza la instalación en un promedio de 2 a 3 horas, garantizando limpieza y orden.' },
                        { q: '¿Tienen cobertura en todo Sonora?', a: 'Actualmente ofrecemos instalación inmediata en Hermosillo. Para otras ciudades, consulta costos de envío y viáticos.' },
                        { q: '¿Es compatible con WiFi?', a: 'El Mirage Life 12+ está preparado para WiFi, permitiéndote controlarlo desde tu smartphone (accesorio se vende por separado).' },
                        { q: '¿A qué número me comunico para servicios?', a: 'Para ventas de equipos nuevos usa el WhatsApp de esta página (Ventas). Para reportes técnicos, servicios o instalaciones, comunícate al número general de taller.' }
                    ]" :key="index" class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-white/5 overflow-hidden shadow-sm">
                        <button @click="toggleFaq(index)" class="w-full p-6 text-left flex justify-between items-center hover:bg-sky-50/50 dark:hover:bg-white/5 transition-colors">
                            <span class="font-black text-slate-900 dark:text-white flex items-start gap-3">
                                <font-awesome-icon icon="question-circle" class="text-sky-500 mt-1" />
                                {{ faq.q }}
                            </span>
                            <font-awesome-icon :icon="activeFaq === index ? 'chevron-up' : 'chevron-down'" class="text-gray-400 text-xs" />
                        </button>
                        <div v-show="activeFaq === index" class="px-6 pb-6 pt-2 text-sm text-gray-500 dark:text-gray-400 border-t border-gray-50 dark:border-white/5 animate-fade-in">
                            <p class="pl-7">{{ faq.a }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- CTA FINAL -->
        <!-- ═══════════════════════════════════════════ -->
        <section class="py-20 lg:py-32 px-4 bg-gradient-to-br from-sky-600 via-sky-500 to-cyan-500 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute -top-32 -right-32 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-cyan-300/10 rounded-full blur-3xl"></div>
            </div>
            <div class="relative z-10 max-w-4xl mx-auto text-center text-white">
                <h2 class="text-4xl lg:text-6xl font-black tracking-tight mb-6">¿Listo para cambiar tu clima?</h2>
                <p class="text-sky-100 text-lg mb-10 max-w-2xl mx-auto">Únete a miles de clientes en Sonora que ya disfrutan la tecnología Mirage. Instalación profesional incluida.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a :href="whatsappUrl" target="_blank" class="group inline-flex items-center justify-center gap-3 px-10 py-5 bg-white text-sky-600 font-black rounded-2xl hover:scale-105 transition-all shadow-2xl uppercase text-xs tracking-widest">
                        <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-xl" />
                        Hablar con un Experto
                    </a>
                    <a href="tel:6624317082" class="inline-flex items-center justify-center gap-3 px-10 py-5 bg-sky-700/50 text-white font-black rounded-2xl hover:bg-sky-700 transition-all backdrop-blur-sm border border-white/20 uppercase text-xs tracking-widest">
                        <font-awesome-icon icon="phone-alt" class="text-lg" />
                        Llamar Ahora
                    </a>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- STICKY MOBILE CTA -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="fixed bottom-6 right-6 z-50 lg:hidden transition-all duration-500" :class="scrollY > 500 ? 'translate-y-0 opacity-100' : 'translate-y-20 opacity-0'">
            <a :href="whatsappUrl" target="_blank" class="flex items-center gap-3 bg-green-500 text-white p-4 rounded-full shadow-2xl hover:scale-110 transition-transform">
                <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-2xl" />
                <span class="font-black text-xs uppercase tracking-widest pr-2">¡Lo quiero!</span>
            </a>
        </div>

        <PublicFooter :empresa="empresa" />
    </div>
</template>

<script>
export default {
    data() {
        return {
            activeFaq: null
        }
    },
    methods: {
        toggleFaq(index) {
            this.activeFaq = this.activeFaq === index ? null : index;
        }
    }
}
</script>

<style scoped>
.life12-page {
    scroll-behavior: smooth;
}

/* Scroll-triggered animation classes */
[data-section] {
    will-change: transform, opacity;
}

/* Floating animation for product */
@keyframes float-gentle {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-12px); }
}

.group:hover img {
    animation: float-gentle 3s ease-in-out infinite;
}

/* Parallax text performance */
.life12-page [style*="translateX"] {
    will-change: transform;
    backface-visibility: hidden;
}

/* Shine sweep on promo badge */
@keyframes shine-sweep {
    0% { left: -100%; }
    100% { left: 200%; }
}

.animate-pulse-soft {
    animation: pulse-soft 2.5s ease-in-out infinite;
}

@keyframes pulse-soft {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.03); }
}

/* Counter badge shimmer */
.life12-page section[data-section="promo"] .animate-pulse-soft::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    animation: shine-sweep 3s ease-in-out infinite;
}
</style>
