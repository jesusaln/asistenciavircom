<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    empresa: Object,
    posts: Object, // Paginator object
    categories: Array,
    filters: Object,
});

import { router } from '@inertiajs/vue3';
import {  watch } from 'vue';
import { debounce } from 'lodash';

const search = ref(props.filters.search || '');

const handleSearch = debounce((value) => {
    router.get(route('public.blog.index'), {
        search: value,
        category: props.filters.category
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 500);

watch(search, (value) => {
    handleSearch(value);
});

const selectCategory = (category) => {
    const newCategory = props.filters.category === category ? null : category;
    router.get(route('public.blog.index'), {
        search: props.filters.search,
        category: newCategory
    }, { preserveState: true, preserveScroll: true });
};

const cssVars = computed(() => ({
    '--color-primary': props.empresa.color_principal || '#3B82F6',
    '--color-primary-soft': (props.empresa.color_principal || '#3B82F6') + '15',
    '--color-primary-dark': (props.empresa.color_principal || '#3B82F6') + 'dd',
    '--color-secondary': props.empresa.color_secundario || '#1E40AF',
}));

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}
</script>

<template>
    <Head title="Blog - Noticias y Tecnología">
        <meta name="description" :content="`Mantente informado con el Blog de ${props.empresa?.nombre_empresa || 'Asistencia Vircom'}. Noticias sobre seguridad electrónica, tutoriales de tecnología y novedades para tu empresa en ${props.empresa?.ciudad || 'Hermosillo'}.`" />
    </Head>
    
    <div :style="cssVars" class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col font-sans transition-colors duration-500">
        <PublicNavbar :empresa="empresa" activeTab="blog" />

        <main class="flex-grow">
            <!-- Hero / Header -->
            <header class="relative py-24 lg:py-32 overflow-hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 transition-colors">
                <!-- Background Decorations -->
                <div class="absolute inset-0 opacity-40 pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-10"></div>
                    <div class="absolute top-1/2 -left-24 w-72 h-72 bg-[var(--color-secondary)] rounded-full blur-[100px] opacity-5"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[var(--color-primary-soft)] text-[var(--color-primary)] text-[10px] font-black uppercase tracking-[0.2em] mb-6 animate-fade-in">
                        Insights & Conocimiento
                    </span>
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-slate-900 dark:text-white mb-6 tracking-tight leading-tight">
                        Nuestro <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Blog Corporativo</span>
                    </h1>
                    <p class="text-lg md:text-xl text-slate-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed mb-12">
                        Explora las últimas tendencias en seguridad electrónica, soporte técnico y soluciones de redes para potenciar tu negocio.
                    </p>

                    <!-- Search and Filter Section -->
                    <div class="max-w-xl mx-auto mb-16 space-y-8">
                        <!-- Search Bar -->
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)] rounded-2xl blur opacity-25 group-hover:opacity-40 transition-opacity duration-500"></div>
                            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl flex items-center p-2 border border-slate-100 dark:border-slate-700">
                                <span class="pl-4 text-slate-400">
                                    <FontAwesomeIcon icon="search" />
                                </span>
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="Buscar artículo..." 
                                    class="w-full bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder-slate-400 font-medium h-12"
                                >
                            </div>
                        </div>

                        <!-- Categories -->
                        <div v-if="categories.length > 0" class="flex flex-wrap justify-center gap-3">
                            <button 
                                @click="selectCategory(null)"
                                :class="[
                                    'px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all duration-300 border',
                                    !filters.category 
                                        ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)] shadow-lg shadow-[var(--color-primary)]/30' 
                                        : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]'
                                ]"
                            >
                                Todos
                            </button>
                            <button 
                                v-for="cat in categories" 
                                :key="cat"
                                @click="selectCategory(cat)"
                                :class="[
                                    'px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all duration-300 border',
                                    filters.category === cat
                                        ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)] shadow-lg shadow-[var(--color-primary)]/30' 
                                        : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]'
                                ]"
                            >
                                {{ cat }}
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Articles Grid -->
            <div class="max-w-7xl mx-auto px-4 py-20">
                <div v-if="posts.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <article v-for="(post, index) in posts.data" :key="post.id" 
                        class="group bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-2xl hover:shadow-[var(--color-primary)]/10 transition-all duration-700 hover:-translate-y-3 relative flex flex-col"
                        :style="{ transitionDelay: `${index * 50}ms` }"
                    >
                        <Link :href="route('public.blog.show', post.slug)" class="block h-full flex flex-col">
                            <!-- Imagen de Portada -->
                            <div class="relative aspect-video overflow-hidden bg-slate-200 dark:bg-slate-800">
                                <img v-if="post.imagen_portada_url" 
                                     :src="post.imagen_portada_url" 
                                     :alt="post.titulo" 
                                     class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                                >
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 text-slate-400">
                                    <FontAwesomeIcon icon="newspaper" size="3x" class="opacity-20" />
                                </div>
                                
                                <!-- Category Badge -->
                                <div class="absolute top-6 left-6 z-10">
                                    <span class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md text-slate-900 dark:text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-white/20 shadow-xl group-hover:bg-[var(--color-primary)] group-hover:text-white transition-colors duration-500">
                                        {{ post.categoria || 'General' }}
                                    </span>
                                </div>

                                <!-- Reading Time Badge -->
                                <div class="absolute bottom-6 left-6 z-10 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500">
                                    <span class="bg-slate-900/80 backdrop-blur-md text-white px-3 py-1 rounded-lg text-[10px] font-bold flex items-center gap-2">
                                        <FontAwesomeIcon icon="clock" class="text-[var(--color-primary)]" />
                                        {{ post.tiempo_lectura }} lectura
                                    </span>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="p-8 flex-grow flex flex-col">
                                <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4 transition-colors">
                                    <span class="flex items-center gap-2">
                                        <FontAwesomeIcon icon="calendar" class="text-[var(--color-primary)]" />
                                        {{ formatDate(post.publicado_at) }}
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                                    <span class="flex items-center gap-2">
                                        <FontAwesomeIcon icon="eye" class="text-[var(--color-primary)]" />
                                        {{ post.visitas }} vistas
                                    </span>
                                </div>

                                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 line-clamp-2 leading-tight group-hover:text-[var(--color-primary)] transition-colors duration-500">
                                    {{ post.titulo }}
                                </h3>
                                
                                <p class="text-slate-500 dark:text-slate-400 text-sm line-clamp-3 mb-8 leading-relaxed flex-grow">
                                    {{ post.resumen }}
                                </p>
                                
                                <div class="inline-flex items-center gap-3 text-[var(--color-primary)] font-black text-xs uppercase tracking-widest group/link">
                                    Leer Artículo Completo
                                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary-soft)] flex items-center justify-center group-hover/link:bg-[var(--color-primary)] group-hover/link:text-white transition-all duration-300">
                                        <FontAwesomeIcon icon="arrow-right" class="transition-transform group-hover/link:translate-x-1" />
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-else class="py-32 text-center max-w-md mx-auto">
                    <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-[2.5rem] flex items-center justify-center mx-auto mb-8 text-slate-300 dark:text-slate-600 text-5xl">
                        <FontAwesomeIcon icon="inbox" />
                    </div>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-3">Aún no hay artículos</h2>
                    <p class="text-slate-500 dark:text-slate-400 mb-8 font-medium">Estamos preparando contenido de alto valor para ti. ¡Vuelve pronto!</p>
                    <Link :href="route('landing')" class="text-[var(--color-primary)] font-black uppercase tracking-widest text-xs hover:underline">
                        Ir al Inicio →
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links.length > 3" class="mt-24 flex justify-center">
                    <nav class="flex gap-3 bg-white dark:bg-slate-900 p-3 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                        <Link v-for="(link, k) in posts.links" :key="k" 
                              :href="link.url || '#'" 
                              v-html="link.label"
                              :class="[
                                  'w-10 h-10 flex items-center justify-center rounded-xl text-xs font-black transition-all duration-300 border',
                                  link.active ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)] shadow-lg shadow-[var(--color-primary)]/30' : 'bg-transparent text-slate-500 dark:text-slate-400 border-transparent hover:bg-slate-100 dark:hover:bg-slate-800',
                                  !link.url ? 'opacity-30 cursor-not-allowed' : ''
                              ]"
                        />
                    </nav>
                </div>
            </div>

            <!-- Newsletter Section Integration -->
            <section class="max-w-7xl mx-auto px-4 mb-24">
                <div class="bg-slate-900 dark:bg-slate-800 rounded-[3.5rem] p-10 lg:p-16 relative overflow-hidden text-white shadow-2xl">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-10"></div>
                    
                    <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
                        <div class="max-w-xl text-center lg:text-left">
                            <h4 class="text-3xl md:text-4xl font-black mb-4 tracking-tight">Recibe nuestro contenido <span class="text-[var(--color-primary)]">Premium</span></h4>
                            <p class="text-slate-400 text-lg font-medium leading-relaxed">Suscríbete para recibir noticias, tips de seguridad y ofertas exclusivas directamente en tu bandeja de entrada.</p>
                        </div>
                        
                        <div class="w-full max-w-md bg-white/5 backdrop-blur-xl p-2 rounded-3xl border border-white/10 flex flex-col sm:flex-row gap-2">
                            <input 
                                type="email" 
                                placeholder="tu@email-empresa.com" 
                                class="flex-grow bg-transparent border-none focus:ring-0 px-6 py-4 placeholder:text-slate-500 font-medium text-slate-100"
                            >
                            <button class="bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg hover:shadow-[var(--color-primary)]/50">
                                Suscribirme
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <WhatsAppWidget :telefono="empresa.whatsapp" />
        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  overflow: hidden;
}
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  line-clamp: 3;
  overflow: hidden;
}
</style>
