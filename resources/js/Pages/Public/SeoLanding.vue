<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import QuickAppointmentForm from '@/Components/QuickAppointmentForm.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

const props = defineProps({
    page: Object, // SeoLandingPage model
    productos: Array,
    empresa: Object,
});

const isVisible = ref(false);
const scrollProgress = ref(0);

const { isDarkMode } = useDarkMode(props.empresa);

const getImageUrl = (url) => {
    if (!url) return 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop';
    if (url.startsWith('http')) return url;
    return `/storage/${url}`;
};

const formatPrice = (precio) => {
    const num = parseFloat(precio);
    return isNaN(num) ? '0.00' : num.toFixed(2);
};

onMounted(() => {
    isVisible.value = true;
    window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        scrollProgress.value = (winScroll / height) * 100;
    });
});
</script>

<template>
    <Head>
        <title>{{ page.titulo_h1 }} | {{ empresa?.nombre_empresa }}</title>
        <meta name="description" :content="page.meta_description" />
        <link rel="canonical" :href="`https://${$page.props.app_url}/${page.slug}`" />
    </Head>

    <div class="min-h-screen bg-white dark:bg-slate-900 font-sans text-gray-900 dark:text-white transition-colors duration-300">
        
        <!-- Progress Bar -->
        <div class="fixed top-0 left-0 h-1 bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)] z-[100]" :style="{ width: scrollProgress + '%' }"></div>

        <PublicNavbar :empresa="empresa" />
        <WhatsAppWidget :whatsapp="empresa?.whatsapp" :empresaNombre="empresa?.nombre_empresa" />

        <!-- HERO SEO -->
        <section class="relative pt-32 pb-24 overflow-hidden bg-slate-50 dark:bg-slate-900">
            <div class="absolute inset-0 z-0">
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[var(--color-primary-soft)] to-transparent opacity-30"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-10 animate-pulse"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div :class="{'translate-x-0 opacity-100': isVisible, '-translate-x-12 opacity-0': !isVisible}" class="transition-all duration-1000">
                        <span class="inline-block px-4 py-2 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-full text-xs font-black uppercase tracking-widest mb-6">
                            📍 {{ page.location || 'Servicio Local' }}
                        </span>
                        <h1 class="text-4xl lg:text-6xl font-black leading-tight mb-8 tracking-tighter">
                            {{ page.hero_title || page.titulo_h1 }}
                        </h1>
                        <p class="text-xl text-gray-600 dark:text-gray-400 mb-10 leading-relaxed max-w-xl">
                            {{ page.hero_description || 'Ofrecemos soluciones tecnológicas profesionales con garantía y los mejores equipos del mercado.' }}
                        </p>
                        <div class="flex gap-4">
                            <a href="#quick-form" class="px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:scale-105 transition-all">
                                Solicitar Cotización
                            </a>
                        </div>
                    </div>
                    
                    <div :class="{'translate-y-0 opacity-100': isVisible, 'translate-y-12 opacity-0': !isVisible}" class="transition-all duration-1000 delay-300">
                        <div class="rounded-[2.5rem] overflow-hidden shadow-2xl border-8 border-white dark:border-gray-800">
                            <img :src="getImageUrl(page.hero_image_url)" class="w-full h-full object-cover aspect-video" :alt="page.titulo_h1">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES SECCION (JSON) -->
        <section v-if="page.features?.length" class="py-24 bg-white dark:bg-slate-900">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="f in page.features" :key="f.title" class="p-8 bg-slate-50 dark:bg-slate-800 rounded-3xl border border-gray-100 dark:border-gray-700 hover:-translate-y-2 transition-all">
                        <div class="text-4xl mb-6">{{ f.icon }}</div>
                        <h4 class="text-xl font-black mb-4">{{ f.title }}</h4>
                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEO CONTENT BLOCKS -->
        <section v-if="page.content_blocks?.length" class="py-24 bg-slate-50 dark:bg-slate-900/50">
            <div class="max-w-4xl mx-auto px-4">
                <div v-for="block in page.content_blocks" :key="block.title" class="mb-20">
                    <h2 class="text-3xl font-black mb-8 tracking-tight">{{ block.title }}</h2>
                    <div class="prose prose-lg dark:prose-invert max-w-none text-gray-600 dark:text-gray-400" v-html="block.content"></div>
                </div>
            </div>
        </section>

        <!-- PRODUCTOS RELACIONADOS -->
        <section v-if="productos?.length" class="py-24 bg-white dark:bg-slate-900">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h3 class="text-3xl font-black tracking-tighter">Equipos Recomendados</h3>
                    <p class="text-gray-500 mt-2">Tecnología de punta para tus necesidades de {{ page.service_category || 'Seguridad' }}</p>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <article v-for="p in productos" :key="p.id" class="group bg-white dark:bg-slate-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-all">
                        <div class="aspect-square bg-white p-6 overflow-hidden">
                            <img :src="p.imagen_url || '/placeholder.png'" class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-700">
                        </div>
                        <div class="p-6">
                            <h4 class="text-sm font-black mb-2 line-clamp-2 h-10">{{ p.nombre }}</h4>
                            <div class="flex items-center justify-between mt-4">
                                <span class="text-lg font-black">${{ formatPrice(p.precio) }}</span>
                                <Link :href="route('catalogo.show', p.id)" class="text-[var(--color-primary)] font-black text-xs uppercase">Ver más →</Link>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- FORMULARIO DE CITA RÁPIDA (Lead Capture) -->
        <div id="quick-form">
            <QuickAppointmentForm :empresa="empresa" />
        </div>

        <PublicFooter :empresa="empresa" />
    </div>
</template>
