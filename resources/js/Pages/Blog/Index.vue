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
</script>

<template>
    <Head title="Blog - Noticias y Tecnología">
        <meta name="description" :content="`Mantente informado con el Blog de ${props.empresa?.nombre_empresa || 'Asistencia Vircom'}. Noticias sobre seguridad electrónica, tutoriales de tecnología y novedades para tu empresa en ${props.empresa?.ciudad || 'Hermosillo'}.`" />
    </Head>
    
    <div :style="cssVars" class="min-h-screen bg-gray-50 flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" />

        <main class="flex-grow">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 py-16">
                <div class="w-full px-4 text-center">
                    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4">
                        Nuestro <span class="text-[var(--color-primary)]">Blog</span>
                    </h1>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Explora las últimas tendencias en seguridad electrónica, soporte técnico y soluciones de redes para tu negocio.
                    </p>
                </div>
            </header>

            <!-- Articles Grid -->
            <div class="w-full px-4 py-12">
                <div v-if="posts.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <article v-for="post in posts.data" :key="post.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
                        <Link :href="route('public.blog.show', post.slug)" class="block">
                            <!-- Imagen -->
                            <div class="relative h-48 overflow-hidden bg-gray-200">
                                <img v-if="post.imagen_portada" :src="post.imagen_portada" :alt="post.titulo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-gray-400">
                                    <FontAwesomeIcon icon="newspaper" size="3x" />
                                </div>
                                <div class="absolute top-4 left-4">
                                    <span class="bg-[var(--color-primary)] text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full shadow-lg">
                                        {{ post.categoria || 'Tecnología' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="p-6">
                                <div class="flex items-center gap-2 text-xs text-gray-400 mb-3">
                                    <FontAwesomeIcon icon="calendar" />
                                    {{ formatDate(post.publicado_at) }}
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2 group-hover:text-[var(--color-primary)] transition-colors">
                                    {{ post.titulo }}
                                </h3>
                                <p class="text-gray-600 text-sm line-clamp-3 mb-4 leading-relaxed">
                                    {{ post.resumen }}
                                </p>
                                <div class="flex items-center text-[var(--color-primary)] font-bold text-sm">
                                    Leer más
                                    <FontAwesomeIcon icon="arrow-right" class="ml-2 transition-transform group-hover:translate-x-2" />
                                </div>
                            </div>
                        </Link>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-else class="py-24 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300 text-4xl">
                        <FontAwesomeIcon icon="inbox" />
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Aún no hay artículos</h2>
                    <p class="text-gray-500">Vuelve pronto para descubrir nuevo contenido.</p>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links.length > 3" class="mt-12 flex justify-center">
                    <nav class="flex gap-2">
                        <Link v-for="(link, k) in posts.links" :key="k" 
                              :href="link.url || '#'" 
                              v-html="link.label"
                              :class="[
                                  'px-4 py-2 rounded-lg text-sm font-medium transition-colors border',
                                  link.active ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50',
                                  !link.url ? 'opacity-50 cursor-not-allowed' : ''
                              ]"
                        />
                    </nav>
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
