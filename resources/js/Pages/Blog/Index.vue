<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    empresa: Object,
    posts: Object,
    categorias: Array,
    postsPopulares: Array,
    filtros: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (props.empresa.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': props.empresa.color_secundario || '#1E40AF',
}));

const searchQuery = ref(props.filtros?.q || '');
const selectedCategoria = ref(props.filtros?.categoria || '');

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const calcularTiempoLectura = (post) => {
    // Promedio: 200 palabras por minuto
    const palabras = (post.resumen?.length || 100) / 5;
    const minutos = Math.max(1, Math.ceil(palabras / 200));
    return `${minutos} min lectura`;
};

const aplicarFiltros = () => {
    router.get(route('public.blog.index'), {
        q: searchQuery.value || undefined,
        categoria: selectedCategoria.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const limpiarFiltros = () => {
    searchQuery.value = '';
    selectedCategoria.value = '';
    router.get(route('public.blog.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Aplicar filtros automáticamente cuando cambia categoría
watch(selectedCategoria, () => {
    aplicarFiltros();
});

const whatsappLink = computed(() => {
    const phone = props.empresa.whatsapp?.replace(/\D/g, '') || '';
    return `https://wa.me/${phone}?text=Hola, vi su blog y me gustaría obtener más información.`;
});

const iconoCategoria = (cat) => {
    const iconos = {
        'Mantenimiento': 'wrench',
        'Instalación': 'tools',
        'Ahorro Energético': 'bolt',
        'Tecnología': 'microchip',
        'Consejos': 'lightbulb',
        'Noticias': 'newspaper',
    };
    return iconos[cat] || 'file-alt';
};
</script>

<template>
    <Head title="Blog - Noticias y Tecnología">
        <meta name="description" :content="`Mantente informado con el Blog de ${props.empresa?.nombre || 'Climas del Desierto'}. Noticias sobre climatización, consejos de mantenimiento y soluciones para el calor de Sonora.`" />
    </Head>

    <div :style="cssVars" class="min-h-screen bg-[var(--ui-surface)] flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" />

        <main class="flex-grow">
            <!-- Header Mejorado con Barra Integrada -->
            <header class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 pb-12 overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-10 right-20 w-64 h-64 bg-[var(--color-primary)] rounded-full blur-[120px] animate-pulse"></div>
                    <div class="absolute bottom-10 left-20 w-48 h-48 bg-brand-500 rounded-full blur-[100px]"></div>
                </div>

                <div class="w-full px-4 text-center relative z-10">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full mb-6 border border-white/20">
                        <font-awesome-icon icon="newspaper" class="text-[var(--color-primary)]" />
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Blog de Climatización</span>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black text-white mb-4 tracking-tight">
                        Nuestro <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Blog</span>
                    </h1>
                    <p class="text-lg text-slate-300 max-w-2xl mx-auto font-medium mb-8">
                        Explora las últimas tendencias en climatización, consejos de mantenimiento y soluciones para el calor de Sonora.
                    </p>
                </div>

                <!-- Barra de Búsqueda Integrada en Header -->
                <div class="w-full px-4 relative z-10">
                    <div class="max-w-6xl mx-auto bg-white/80 dark:bg-slate-800/95 backdrop-blur-md rounded-2xl border border-white/10 shadow-2xl p-4">
                        <div class="flex flex-col md:flex-row gap-3">
                            <!-- Buscador -->
                            <div class="flex-1 relative">
                                <font-awesome-icon icon="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input
                                    v-model="searchQuery"
                                    @keyup.enter="aplicarFiltros"
                                    type="text"
                                    placeholder="Buscar artículos..."
                                    class="w-full pl-11 pr-4 py-2.5 bg-[var(--ui-surface)] dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Filtro Categoría -->
                            <select
                                v-model="selectedCategoria"
                                class="px-4 py-2.5 bg-[var(--ui-surface)] dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent cursor-pointer"
                            >
                                <option value="">Todas las categorías</option>
                                <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
                            </select>

                            <!-- Botón Buscar -->
                            <button
                                @click="aplicarFiltros"
                                class="px-6 py-2.5 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-xl transition-all"
                            >
                                <font-awesome-icon icon="filter" class="mr-2" />
                                Filtrar
                            </button>

                            <!-- Limpiar (solo si hay filtros) -->
                            <button
                                v-if="searchQuery || selectedCategoria"
                                @click="limpiarFiltros"
                                class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-xl font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
                            >
                                <font-awesome-icon icon="times" />
                            </button>
                        </div>

                        <!-- Tags de Categorías -->
                        <div v-if="categorias?.length" class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-300 dark:border-slate-600">
                            <button
                                @click="selectedCategoria = cat"
                                v-for="cat in categorias"
                                :key="cat"
                                :class="selectedCategoria === cat ? 'bg-[var(--color-primary)] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all"
                            >
                                {{ cat }}
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido Principal + Sidebar -->
            <div class="w-full px-4 py-8">
                <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Grid de Artículos (2/3) -->
                    <div class="lg:col-span-2">
                        <div v-if="posts.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <article v-for="post in posts.data" :key="post.id" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl-sm border border-slate-100 dark:border-slate-700 overflow-hidden hover:shadow-xl hover:shadow-xl hover:shadow-xl transition-all duration-200 group">
                                <Link :href="route('public.blog.show', post.slug)" class="block">
                                    <!-- Imagen -->
                                    <div class="relative h-48 overflow-hidden bg-slate-200 dark:bg-slate-700">
                                        <img v-if="post.imagen_portada_url" :src="post.imagen_portada_url" :alt="post.titulo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 text-slate-400 dark:text-slate-500">
                                            <font-awesome-icon :icon="iconoCategoria(post.categoria)" size="3x" />
                                        </div>
                                        
                                        <!-- Badge Categoría -->
                                        <div class="absolute top-4 left-4">
                                            <span class="bg-[var(--color-primary)] text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full shadow-xl flex items-center gap-1.5">
                                                <font-awesome-icon :icon="iconoCategoria(post.categoria)" />
                                                {{ post.categoria || 'Tecnología' }}
                                            </span>
                                        </div>

                                        <!-- Tiempo Lectura -->
                                        <div class="absolute bottom-4 right-4">
                                            <span class="bg-black/50 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-xl flex items-center gap-1.5">
                                                <font-awesome-icon icon="clock" />
                                                {{ calcularTiempoLectura(post) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Contenido -->
                                    <div class="p-5">
                                        <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500 mb-3">
                                            <span class="flex items-center gap-1.5">
                                                <font-awesome-icon icon="calendar" />
                                                {{ formatDate(post.publicado_at) }}
                                            </span>
                                            <span class="flex items-center gap-1.5" v-if="post.visitas">
                                                <font-awesome-icon icon="eye" />
                                                {{ post.visitas }}
                                            </span>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2 line-clamp-2 group-hover:text-[var(--color-primary)] transition-colors leading-tight">
                                            {{ post.titulo }}
                                        </h3>
                                        <p class="text-slate-500 dark:text-slate-200 text-sm line-clamp-3 mb-4 leading-relaxed">
                                            {{ post.resumen }}
                                        </p>
                                        <div class="flex items-center text-[var(--color-primary)] font-bold text-sm">
                                            Leer más
                                            <font-awesome-icon icon="arrow-right" class="ml-2 transition-transform group-hover:translate-x-2" />
                                        </div>
                                    </div>
                                </Link>
                            </article>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="py-24 text-center bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300 dark:text-slate-500 text-4xl">
                                <font-awesome-icon icon="inbox" />
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-2">No se encontraron artículos</h2>
                            <p class="text-slate-500 dark:text-slate-400 mb-6">Intenta con otra búsqueda o categoría.</p>
                            <button @click="limpiarFiltros" class="px-6 py-3 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-xl transition-all">
                                Ver todos los artículos
                            </button>
                        </div>

                        <!-- Paginación -->
                        <div v-if="posts.links.length > 3" class="mt-12 flex justify-center">
                            <nav class="flex gap-2">
                                <Link v-for="(link, k) in posts.links" :key="k"
                                      :href="link.url || '#'"
                                      v-html="link.label"
                                      :class="[
                                          'px-4 py-2 rounded-xl text-sm font-medium transition-colors border',
                                          link.active ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-200 border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700',
                                          !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                      ]"
                                />
                            </nav>
                        </div>

                        <!-- CTA de Conversión -->
                        <div v-if="posts.data.length > 0" class="mt-12 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 text-center relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-48 h-48 bg-[var(--color-primary)] rounded-full blur-[100px] opacity-10"></div>
                            <div class="relative z-10">
                                <font-awesome-icon icon="comments" class="text-5xl text-[var(--color-primary)] mb-4" />
                                <h3 class="text-2xl font-black text-white mb-3">¿Necesitas asesoría personalizada?</h3>
                                <p class="text-slate-400 mb-6">Nuestros expertos te ayudan a elegir la mejor solución de climatización.</p>
                                <a :href="whatsappLink" target="_blank" class="inline-flex items-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-black text-sm uppercase tracking-wide shadow-xl transition-all hover:scale-105">
                                    <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-xl" />
                                    Hablar con un Experto
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar (1/3) -->
                    <aside class="lg:col-span-1 space-y-6">
                        <!-- Artículos Populares -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-6 shadow-sm">
                            <h3 class="text-sm font-black uppercase tracking-wide text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                                <font-awesome-icon icon="eye" class="text-orange-500" />
                                Más Leídos
                            </h3>
                            <div class="space-y-6">
                                <Link v-for="(post, idx) in postsPopulares" :key="post.id"
                                      :href="route('public.blog.show', post.slug)"
                                      class="flex gap-4 group hover:bg-slate-50 dark:hover:bg-slate-700/50 p-3 rounded-xl -mx-3 transition-colors"
                                >
                                    <span class="text-2xl font-black text-slate-200 dark:text-slate-700 group-hover:text-[var(--color-primary)] transition-colors min-w-[2.5rem]">0{{ idx + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2 group-hover:text-[var(--color-primary)] transition-colors leading-tight">{{ post.titulo }}</h4>
                                        <div class="flex items-center gap-2 mt-2 text-xs text-slate-400">
                                            <span class="flex items-center gap-1">
                                                <font-awesome-icon icon="eye" />
                                                {{ post.visitas || 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Categorías -->
                        <div v-if="categorias?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-6 shadow-sm">
                            <h3 class="text-sm font-black uppercase tracking-wide text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                                <font-awesome-icon icon="list" class="text-[var(--color-primary)]" />
                                Categorías
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="selectedCategoria = cat"
                                    v-for="cat in categorias"
                                    :key="cat"
                                    :class="selectedCategoria === cat ? 'bg-[var(--color-primary)] text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                    class="px-3 py-2 rounded-xl text-xs font-bold transition-all"
                                >
                                    {{ cat }}
                                </button>
                            </div>
                        </div>

                        <!-- CTA WhatsApp -->
                        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-2xl p-6 text-center text-white shadow-xl">
                            <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-5xl mb-4" />
                            <h3 class="text-lg font-black mb-2">¿Dudas sobre climatización?</h3>
                            <p class="text-emerald-100 text-sm mb-6">Te asesoramos sin compromiso. Respuesta inmediata.</p>
                            <a :href="whatsappLink" target="_blank" class="block w-full py-3 bg-white text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 rounded-xl font-black text-xs uppercase tracking-wide hover:bg-slate-50 transition-all">
                                Escríbenos Ahora
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
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
    overflow: hidden;
}
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
