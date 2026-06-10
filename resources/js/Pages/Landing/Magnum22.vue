<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({ empresa: Object });

const scrollY = ref(0);
const sectionVisibility = ref({
    hero: false,
    features: false,
    details: false,
    gallery: false,
    variations: false,
    specs: false,
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
    { title: 'Inverter 360', icon: 'bolt', color: 'from-blue-600 to-indigo-700', text: 'Tecnología de control y velocidad variable. Categoría PREMIUM con 22 SEER de eficiencia.' },
    { title: 'Power Control', icon: 'leaf', color: 'from-brand-500 to-amber-600', text: 'Establece el nivel de ahorro deseado (25% o 50%) y reduce tu gasto energético.' },
    { title: 'Healthy Clean', icon: 'wind', color: 'from-brand-500 to-amber-600', text: 'Elimina humedad, previene formación de hongos y neutraliza malos olores automáticamente.' },
    { title: 'Test Mode', icon: 'tools', color: 'from-brand-500 to-amber-600', text: 'Diagnóstico en tiempo real desde el control remoto para asegurar el funcionamiento ideal.' },
];

const specs = [
    { label: 'Eficiencia Real', value: 'Hasta 22 SEER (Premium)', icon: 'tachometer-alt' },
    { label: 'Voltaje 1 Ton', value: '115V / 220V disponible', icon: 'plug' },
    { label: 'Voltaje 1.5-2 Ton', value: 'Exclusivo 220V', icon: 'bolt' },
    { label: 'Refrigerante', value: 'R-410A de alta eficiencia', icon: 'leaf' },
    { label: 'Tecnología', value: 'Inverter 360 MQ Smart', icon: 'microchip' },
    { label: 'Diagnóstico', value: 'Smart Test Mode integrado', icon: 'check-circle' },
];

const highlights = [
    { icon: 'shield-alt', title: 'Ozone Fin', desc: 'Capa protectora Coating-Protector contra corrosión' },
    { icon: 'tint-slash', title: 'Leak Detect', desc: 'Safety-Shut Down ante pérdida de refrigerante' },
    { icon: 'snowflake', title: 'LT Sensor', desc: 'Previene congelamiento y derramamiento de agua' },
    { icon: 'users', title: 'Confort Sensor', desc: 'Interpretación lógica de condiciones del clima' },
    { icon: 'hand-holding-usd', title: 'Bajo Costo', desc: 'Mínimo impacto ambiental y operativo' },
];

const prices = [
    { capacity: '1.0 Ton', voltage: '110V', type: 'Solo Frío', code: 'SETCMF120V', price: '7,761' },
    { capacity: '1.0 Ton', voltage: '220V', type: 'Solo Frío', code: 'SETCMF121W', price: '7,747' },
    { capacity: '1.0 Ton', voltage: '220V', type: 'Frío y Calor', code: 'SETCMC121V', price: '8,900' },
    { capacity: '1.5 Ton', voltage: '220V', type: 'Solo Frío', code: 'SETCMF181V', price: '11,517' },
    { capacity: '2.0 Ton', voltage: '220V', type: 'Solo Frío', code: 'SETCMF261V', price: '15,074' },
];

const whatsappUrl = `https://wa.me/526624317082?text=${encodeURIComponent('Hola, me interesa el Minisplit Mirage Magnum 22 Inverter. ¿Cuál es el precio y disponibilidad?')}`;
</script>

<template>
    <Head>
        <title>Mirage Magnum 22 | Minisplit Inverter SEER 22 | {{ empresa?.nombre || 'Climas del Desierto' }}</title>
        <meta name="description" content="Minisplit Mirage Magnum 22 Inverter. Alta eficiencia SEER 22, ahorro de energía extremo, ultra silencioso. El mejor Inverter para Hermosillo." />
    </Head>

    <div class="magnum22-page bg-[var(--ui-surface)] text-slate-900 dark:text-white font-sans selection:bg-blue-600 selection:text-white">
        <PublicNavbar :empresa="empresa" activeTab="magnum22" />

        <!-- ═══════════════════════════════════════════ -->
        <!-- HERO -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="hero" class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
            <div class="absolute inset-0 select-none pointer-events-none overflow-hidden">
                <div class="absolute top-1/4 left-0 text-[15vw] font-black text-slate-200/10 dark:text-white/[0.03] whitespace-nowrap leading-none transition-transform duration-150 ease-out will-change-transform" :style="{ transform: parallaxRow1 }">
                    MAGNUM 22 · INVERTER · SEER 22 · 
                </div>
                <div class="absolute bottom-1/4 right-0 text-[15vw] font-black text-blue-200/10 dark:text-blue-400/[0.03] whitespace-nowrap leading-none transition-transform duration-150 ease-out will-change-transform" :style="{ transform: parallaxRow2 }">
                    ULTRA EFFICIENT · SILENT · SMART ·
                </div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-20 lg:py-0">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div class="order-2 lg:order-1 transition-all duration-700" :class="sectionVisibility.hero ? 'translate-x-0 opacity-100' : '-translate-x-12 opacity-0'">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-sky-100 dark:bg-brand-500/20 text-blue-600 dark:text-blue-300 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
                            <span class="w-2 h-2 bg-brand-500 rounded-full animate-ping"></span>
                            Nivel: Premium Inverter
                        </div>
                        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tighter leading-[0.9] mb-6 uppercase">
                            Mirage<br><span class="bg-gradient-to-r from-blue-600 to-indigo-500 bg-clip-text text-transparent">Magnum 22</span>
                        </h1>
                        <p class="text-lg text-slate-500 dark:text-slate-200 mb-10 max-w-lg leading-relaxed">
                            Ahorro energético sin precedentes. El <span class="text-blue-600 font-bold">Magnum 22</span> redefine la eficiencia con SEER 22, brindando confort absoluto con el mínimo consumo.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a :href="whatsappUrl" target="_blank" class="group inline-flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-black rounded-2xl hover:shadow-2xl hover:shadow-sky-500/30 hover:scale-105 transition-all duration-200 uppercase text-xs tracking-wide">
                                <font-awesome-icon :icon="['fab', 'whatsapp']" /> Consultar Disponibilidad
                            </a>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2 relative" :style="{ opacity: heroOpacity, transform: `scale(${heroScale})` }">
                        <div class="relative p-8 group" :style="{ transform: heroImageFloat }">
                            <img src="/images/products/magnum22/hero.png" class="relative z-10 w-full h-auto drop-shadow-2xl rounded-3xl" alt="Mirage Magnum 22">
                            <div class="absolute top-4 right-4 z-20 bg-white dark:bg-slate-800 border border-blue-500/20 px-6 py-4 rounded-2xl shadow-xl flex items-center gap-4 animate-bounce-slow">
                                <div class="w-10 h-10 bg-brand-500 rounded-xl flex items-center justify-center text-white font-black text-xl">22</div>
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-wide text-blue-500">SEER</span>
                                    <span class="block text-xs font-bold text-slate-400">Eficiencia</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- FEATURES -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="features" class="py-20 lg:py-32 px-4 bg-white dark:bg-slate-950">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-blue-600 mb-4 block">Ingeniería de Vanguardia</span>
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tight">El futuro del ahorro</h2>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(f, i) in features" :key="i" class="p-8 bg-[var(--ui-surface)] dark:bg-white/5 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-xl transition-all duration-500 hover:shadow-xl hover:shadow-xl">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white mb-6 shadow-xl" :class="f.color">
                            <font-awesome-icon :icon="f.icon" class="text-2xl" />
                        </div>
                        <h3 class="text-xl font-black mb-3">{{ f.title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ f.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- DETAILS & HIGHLIGHTS -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="details" class="py-20 lg:py-32 px-4 bg-white dark:bg-slate-950">
            <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
                <div class="relative transition-all duration-700" :class="sectionVisibility.details ? 'opacity-100' : 'opacity-0'">
                    <img src="/images/products/magnum22/hero.png" class="relative z-10 w-full max-w-lg h-auto drop-shadow-2xl rounded-3xl" alt="Magnum 22 Details">
                </div>
                <div>
                    <h2 class="text-4xl lg:text-5xl font-black mb-10 leading-tight">Ahorro extremo <br><span class="text-blue-600">confort total</span></h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-for="h in highlights" :key="h.title" class="flex items-start gap-4 p-4 rounded-2xl bg-[var(--ui-surface)] dark:bg-white/5 border border-slate-100 dark:border-white/5">
                            <div class="w-10 h-10 flex-shrink-0 bg-brand-500/10 rounded-xl flex items-center justify-center text-blue-600">
                                <font-awesome-icon :icon="h.icon" />
                            </div>
                            <div>
                                <h5 class="font-bold text-sm mb-1 uppercase tracking-wider">{{ h.title }}</h5>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ h.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- GALLERY -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="gallery" class="py-20 lg:py-32 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="relative rounded-[3rem] overflow-hidden group h-[500px] lg:h-[700px] shadow-2xl">
                    <img src="/images/products/magnum22/bedroom.png" class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-[10s]" alt="Bedroom Gallery">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-12 left-12 right-12 text-white">
                        <h3 class="text-4xl lg:text-5xl font-black mb-4 uppercase">El Silencio es Lujo</h3>
                        <p class="text-white/70 text-lg max-w-xl">Operación ultra silenciosa para un descanso profundo sin interrupciones.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- VARIATIONS / PRICING -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="variations" class="py-20 lg:py-32 px-4 bg-[var(--ui-surface)] dark:bg-slate-800/30">
            <div class="max-w-7xl mx-auto text-center">
                <div class="inline-block px-6 py-3 bg-brand-500/10 rounded-2xl border border-blue-500/20 mb-8">
                    <p class="text-blue-600 dark:text-blue-400 text-sm font-black uppercase tracking-wide">
                        ¡Alta eficiencia para el clima de Sonora!
                    </p>
                </div>
                <h2 class="text-4xl lg:text-5xl font-black mb-4">Precios Mirage Magnum 22</h2>
                <p class="text-slate-500 dark:text-slate-400 mb-16 max-w-2xl mx-auto uppercase text-xs tracking-[0.2em] font-bold">Inversión inteligente para un ahorro garantizado</p>
                
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="p in prices" :key="p.code" class="group bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-xl hover:shadow-sky-500/10 transition-all duration-200 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <font-awesome-icon icon="snowflake" class="text-6xl text-blue-500" />
                        </div>
                        <div class="text-left">
                            <span class="inline-block px-3 py-1 bg-sky-100 dark:bg-brand-500/20 text-blue-600 dark:text-blue-300 rounded-xl text-[10px] font-black uppercase tracking-wide mb-4">
                                {{ p.capacity }}
                            </span>
                            <h3 class="text-xl font-black mb-1 uppercase tracking-wide">{{ p.type }}</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide mb-6">{{ p.voltage }} · COD: {{ p.code }}</p>
                            
                            <div class="mb-8">
                                <span class="text-2xl font-black text-blue-600 dark:text-blue-400 tracking-tighter">
                                    ${{ p.price }}
                                </span>
                                <span class="text-[10px] text-slate-400 ml-1 font-bold uppercase">MXN</span>
                            </div>

                            <a :href="`https://wa.me/526624317082?text=${encodeURIComponent('Hola, me interesa el Magnum 22 ' + p.capacity + ' (' + p.voltage + ') con código ' + p.code + '. ¿Tienen disponibilidad?')}`" 
                               target="_blank" 
                               class="flex items-center justify-center gap-2 w-full py-4 bg-slate-100 dark:bg-white/5 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 text-slate-900 dark:text-white font-black rounded-2xl uppercase text-[10px] tracking-wide transition-all duration-200">
                                <font-awesome-icon :icon="['fab', 'whatsapp']" /> Comprar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-12 p-8 bg-blue-600/5 dark:bg-brand-500/5 rounded-[2.5rem] border border-blue-500/10 max-w-3xl mx-auto text-left">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="w-16 h-16 bg-brand-500 rounded-2xl flex items-center justify-center text-white text-2xl flex-shrink-0">
                            <font-awesome-icon icon="info-circle" />
                        </div>
                        <div>
                            <h4 class="font-black text-lg mb-1 uppercase tracking-wider">¿Buscas Frío/Calor o Instalación?</h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Contamos con todas las capacidades y servicios de instalación premium. Los precios mostrados son de contado y sujetos a cambios sin previo aviso.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SPECS TABLE -->
        <!-- ═══════════════════════════════════════════ -->
        <section data-section="specs" class="py-20 lg:py-32 px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-16">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-blue-600 mb-4 block">Ficha Técnica</span>
                    <h2 class="text-4xl lg:text-5xl font-black">Especificaciones</h2>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-white/5 overflow-hidden shadow-2xl">
                    <div v-for="(spec, i) in specs" :key="i" class="flex items-center justify-between p-6 border-b border-slate-50 dark:border-white/5 last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-500/5 transition-colors">
                        <div class="flex items-center gap-4">
                            <font-awesome-icon :icon="spec.icon" class="text-blue-600" />
                            <span class="font-bold text-xs text-slate-400 uppercase tracking-wide">{{ spec.label }}</span>
                        </div>
                        <span class="font-black text-slate-900 dark:text-white">{{ spec.value }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20 lg:py-32 px-4 bg-blue-600 text-white text-center">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-4xl lg:text-6xl font-black mb-8 uppercase tracking-wide">Confort Premium a tu alcance</h2>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a :href="whatsappUrl" target="_blank" class="px-10 py-5 bg-white text-blue-600 font-black rounded-2xl uppercase text-xs tracking-wide shadow-2xl hover:scale-105 transition-transform">Hablar con un Experto</a>
                </div>
            </div>
        </section>

        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.magnum22-page { scroll-behavior: smooth; }
@keyframes bounce-slow { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
.animate-bounce-slow { animation: bounce-slow 4s ease-in-out infinite; }
</style>
