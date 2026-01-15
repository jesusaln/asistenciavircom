<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    empresa: Object,
    post: Object,
    relacionados: Array
});

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

const shareToFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank')
}

const shareToTwitter = () => {
    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(props.post.titulo)}`, '_blank')
}
</script>

<template>
    <Head :title="post.titulo">
        <meta name="description" :content="post.meta_descripcion || post.resumen">
        <meta property="og:title" :content="post.titulo">
        <meta property="og:description" :content="post.resumen">
        <meta property="og:image" :content="post.imagen_portada">
    </Head>
    
    <div :style="cssVars" class="min-h-screen bg-gray-50 flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" />

        <main class="flex-grow">
            <!-- Article Content -->
            <article class="max-w-4xl mx-auto px-4 py-12 md:py-20">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8">
                    <Link :href="route('landing')" class="hover:text-[var(--color-primary)] transition-colors">Inicio</Link>
                    <FontAwesomeIcon icon="chevron-right" class="text-[10px]" />
                    <Link :href="route('public.blog.index')" class="hover:text-[var(--color-primary)] transition-colors">Blog</Link>
                    <FontAwesomeIcon icon="chevron-right" class="text-[10px]" />
                    <span class="text-gray-600 truncate max-w-[200px]">{{ post.titulo }}</span>
                </nav>

                <!-- Post Header -->
                <header class="mb-10">
                    <div class="mb-4">
                        <span class="bg-[var(--color-primary-soft)] text-[var(--color-primary)] text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                            {{ post.categoria || 'Tecnología' }}
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 mb-6 leading-tight">
                        {{ post.titulo }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500 border-y border-gray-100 py-4 font-medium">
                        <div class="flex items-center gap-2">
                            <FontAwesomeIcon icon="calendar-alt" class="text-[var(--color-primary)]" />
                            {{ formatDate(post.publicado_at) }}
                        </div>
                        <div class="flex items-center gap-2">
                            <FontAwesomeIcon icon="eye" class="text-[var(--color-primary)]" />
                            {{ post.visitas }} vistas
                        </div>
                        <div class="flex items-center gap-4 ml-auto">
                            <span class="text-xs uppercase tracking-widest text-gray-400">Compartir:</span>
                            <button @click="shareToFacebook" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <FontAwesomeIcon :icon="['fab', 'facebook']" size="lg" />
                            </button>
                            <button @click="shareToTwitter" class="text-gray-400 hover:text-black transition-colors">
                                <FontAwesomeIcon :icon="['fab', 'twitter']" size="lg" />
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Cover Image -->
                <div v-if="post.imagen_portada" class="rounded-3xl overflow-hidden shadow-2xl mb-12 aspect-video">
                    <img :src="post.imagen_portada" :alt="post.titulo" class="w-full h-full object-cover">
                </div>

                <!-- Body Content -->
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed rich-text-content" v-html="post.contenido">
                </div>

                <!-- Social Footer -->
                <footer class="mt-16 pt-12 border-t border-gray-100">
                    <div class="bg-[var(--color-primary)] rounded-3xl p-8 md:p-12 text-white overflow-hidden relative group">
                        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                            <div class="text-center md:text-left">
                                <h3 class="text-2xl font-bold mb-2">¿Necesitas asesoría técnica?</h3>
                                <p class="text-blue-100 opacity-90">Contáctanos hoy mismo y uno de nuestros expertos te ayudará.</p>
                            </div>
                            <a :href="`https://wa.me/${empresa.whatsapp}`" class="bg-white text-[var(--color-primary)] px-8 py-4 rounded-2xl font-bold hover:bg-blue-50 transition-all flex items-center gap-3 shadow-xl transform active:scale-95">
                                <FontAwesomeIcon :icon="['fab', 'whatsapp']" size="lg" />
                                Hablar con un Experto
                            </a>
                        </div>
                        <!-- Background Shapes -->
                        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-3xl transition-transform duration-700 group-hover:scale-150"></div>
                        <div class="absolute -left-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    </div>
                </footer>

                <!-- Related Posts -->
                <section v-if="relacionados.length > 0" class="mt-24">
                    <h3 class="text-2xl font-bold text-gray-900 mb-8 border-l-4 border-[var(--color-primary)] pl-4">También te puede interesar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <article v-for="rel in relacionados" :key="rel.id" class="group">
                            <Link :href="route('public.blog.show', rel.slug)">
                                <div class="relative h-40 rounded-2xl overflow-hidden bg-gray-200 mb-4">
                                    <img v-if="rel.imagen_portada" :src="rel.imagen_portada" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                </div>
                                <h4 class="font-bold text-gray-900 group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">
                                    {{ rel.titulo }}
                                </h4>
                            </Link>
                        </article>
                    </div>
                </section>
            </article>
        </main>

        <WhatsAppWidget :telefono="empresa.whatsapp" />
        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style>
.rich-text-content h2 {
    font-size: 1.875rem;
    font-weight: 800;
    color: #111827;
    margin-top: 2rem;
    margin-bottom: 1rem;
}
.rich-text-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}
.rich-text-content p {
    margin-bottom: 1.5rem;
}
.rich-text-content ul {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-bottom: 1.5rem;
}
.rich-text-content li {
    margin-bottom: 0.5rem;
}
</style>
