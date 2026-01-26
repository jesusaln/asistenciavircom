<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import hljs from 'highlight.js';
import 'highlight.js/styles/atom-one-dark.css';

const props = defineProps({
    empresa: Object,
    post: Object,
    relacionados: Array
});

const articleContent = ref(null);
const toc = ref([]);
const activeHeadingId = ref(null);
const markerTop = ref(0);
const markerHeight = ref(0);

const generateTOC = () => {
    if (!articleContent.value) return;

    const headings = articleContent.value.querySelectorAll('h2, h3');
    const tocData = [];

    headings.forEach((heading, index) => {
        const id = heading.id || `heading-${index}`;
        heading.id = id;
        
        tocData.push({
            id: id,
            text: heading.innerText,
            level: heading.tagName.toLowerCase(),
            el: heading
        });
    });

    toc.value = tocData;
};

const scrollToHeading = (id) => {
    const el = document.getElementById(id);
    if (el) {
        const y = el.getBoundingClientRect().top + window.pageYOffset - 100; // Offset for sticky header
        window.scrollTo({ top: y, behavior: 'smooth' });
        activeHeadingId.value = id;
    }
};

const updateActiveHeading = () => {
    if (toc.value.length === 0) return;

    // Logic to find which heading is currently in view
    // We check which heading is closest to the top of the viewport (with some offset)
    let currentId = null;
    const offset = 150; 

    for (const item of toc.value) {
        const rect = item.el.getBoundingClientRect();
        if (rect.top <= offset) {
            currentId = item.id;
        } else {
            break; 
        }
    }

    if (!currentId && toc.value.length > 0) {
        // If we are at the top, select first? No, maybe none.
        // Actually if scrollY is 0, none.
        if (window.scrollY > 100) currentId = toc.value[0].id;
    }

    if (currentId !== activeHeadingId.value) {
        activeHeadingId.value = currentId;
        updateMarker();
    }
};

const updateMarker = () => {
    if (!activeHeadingId.value) {
        markerHeight.value = 0;
        return;
    }
    
    // Find the link element in the DOM (not the heading content)
    // We can query selector by data-id
    const link = document.querySelector(`a[data-id="${activeHeadingId.value}"]`);
    if (link) {
        markerTop.value = link.offsetTop;
        markerHeight.value = link.offsetHeight;
    }
};

const copyLink = () => {
    navigator.clipboard.writeText(window.location.href);
    // Could add a toast notification here
};

const ntToken = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('nt_token');
});

const reportInterest = async () => {
    if (!ntToken.value) return;

    try {
        await fetch(route('api.blog.track.interest'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ token: ntToken.value })
        });
    } catch (e) {
        console.error("Error reporting interest", e);
    }
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

const shareToFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank')
}

const shareToTwitter = () => {
    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(props.post.titulo)}`, '_blank')
}

// Reading Progress Bar
const scrollProgress = ref(0);

const updateScroll = () => {
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    if (totalHeight > 0) {
        scrollProgress.value = (window.scrollY / totalHeight) * 100;
    }
}

onMounted(() => {
    window.addEventListener('scroll', updateScroll);
    window.addEventListener('scroll', updateActiveHeading);
    
    nextTick(() => {
        // Syntax Highlighting
        if (articleContent.value) {
            articleContent.value.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        }
        
        // TOC Generation
        generateTOC();
        
        // Initial check for active heading
        updateActiveHeading();

        // Detect interest clicks on dynamically added content (AI posts)
        if (articleContent.value) {
            articleContent.value.addEventListener('click', (e) => {
                const target = e.target.closest('a');
                if (target && (target.href.includes('mailto') || target.innerText.toLowerCase().includes('experto'))) {
                    reportInterest();
                }
            });
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateScroll);
    window.removeEventListener('scroll', updateActiveHeading);
});

</script>

<template>
    <Head :title="post.titulo">
        <meta name="description" :content="post.meta_descripcion || post.resumen">
        <meta property="og:title" :content="post.titulo">
        <meta property="og:description" :content="post.resumen">
        <meta property="og:image" :content="post.imagen_portada_url">
    </Head>
    
    <div :style="cssVars" class="min-h-screen bg-white dark:bg-slate-950 flex flex-col font-sans transition-colors duration-500 relative">
        <!-- Reading Progress Bar -->
        <div class="fixed top-0 left-0 w-full h-1 z-[100] bg-transparent">
            <div 
                class="h-full bg-[var(--color-primary)] transition-all duration-100 ease-out shadow-[0_0_10px_var(--color-primary)]"
                :style="{ width: `${scrollProgress}%` }"
            ></div>
        </div>

        <PublicNavbar :empresa="empresa" activeTab="blog" />

        <main class="flex-grow">
            <!-- Hero / Post Header -->
            <header class="relative pt-20 pb-12 overflow-hidden bg-slate-50 dark:bg-slate-900 transition-colors">
                <!-- Background Decoration -->
                <div class="absolute inset-0 opacity-40 pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-10"></div>
                </div>

                <div class="max-w-4xl mx-auto px-4 relative z-10">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-8 overflow-x-auto whitespace-nowrap pb-2 scrollbar-hide">
                        <Link :href="route('landing')" class="hover:text-[var(--color-primary)] transition-colors">Inicio</Link>
                        <FontAwesomeIcon icon="chevron-right" class="text-[8px]" />
                        <Link :href="route('public.blog.index')" class="hover:text-[var(--color-primary)] transition-colors">Blog</Link>
                        <FontAwesomeIcon icon="chevron-right" class="text-[8px]" />
                        <span class="text-slate-900 dark:text-white">{{ post.titulo }}</span>
                    </nav>

                    <div class="mb-6">
                        <span class="bg-[var(--color-primary)] text-white text-[10px] font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-full shadow-lg shadow-[var(--color-primary)]/20">
                            {{ post.categoria || 'Especializado' }}
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white mb-8 leading-[1.1] tracking-tight">
                        {{ post.titulo }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-8 text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-800 pt-8 mt-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-[var(--color-primary-soft)] flex items-center justify-center text-[var(--color-primary)]">
                                <FontAwesomeIcon icon="calendar-alt" />
                            </div>
                            {{ formatDate(post.publicado_at) }}
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[var(--color-primary)]">
                                <FontAwesomeIcon icon="eye" />
                            </div>
                            {{ post.visitas }} visitas
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[var(--color-primary)]">
                                <FontAwesomeIcon icon="clock" />
                            </div>
                            {{ post.tiempo_lectura }} de lectura
                        </div>
                        
                        <div class="flex items-center gap-4 ml-auto">
                            <span class="opacity-50 hidden sm:inline">Compartir:</span>
                            <button @click="shareToFacebook" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <FontAwesomeIcon :icon="['fab', 'facebook-f']" />
                            </button>
                            <button @click="shareToTwitter" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-black hover:text-white transition-all shadow-sm">
                                <FontAwesomeIcon :icon="['fab', 'x-twitter']" />
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area with Sidebar -->
            <div class="max-w-7xl mx-auto px-4 py-16 grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-12 relative">
                
                <!-- Floating Shares (Desktop Left) -->
                <div class="hidden xl:flex flex-col gap-4 fixed left-8 top-1/3 z-20">
                    <button @click="shareToFacebook" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 text-[#1877F2] hover:scale-110 transition-transform shadow-lg flex items-center justify-center border border-slate-100 dark:border-slate-700" title="Compartir en Facebook">
                        <FontAwesomeIcon :icon="['fab', 'facebook-f']" />
                    </button>
                    <button @click="shareToTwitter" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 text-black dark:text-white hover:scale-110 transition-transform shadow-lg flex items-center justify-center border border-slate-100 dark:border-slate-700" title="Compartir en X">
                        <FontAwesomeIcon :icon="['fab', 'x-twitter']" />
                    </button>
                    <button @click="copyLink" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 text-slate-500 hover:scale-110 transition-transform shadow-lg flex items-center justify-center border border-slate-100 dark:border-slate-700" title="Copiar Enlace">
                        <FontAwesomeIcon icon="link" />
                    </button>
                </div>

                <!-- Article Content -->
                <div class="lg:w-full min-w-0">
                    <!-- Cover Image -->
                    <div v-if="post.imagen_portada_url" class="relative group rounded-[3rem] overflow-hidden shadow-2xl mb-12 aspect-video bg-slate-200 dark:bg-slate-800">
                        <img :src="post.imagen_portada_url" :alt="post.titulo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-[2s]">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                    </div>

                    <!-- Rich Text Content -->
                    <article 
                        ref="articleContent"
                        class="prose prose-lg dark:prose-invert max-w-none 
                               prose-headings:font-black prose-headings:tracking-tight 
                               prose-h2:text-3xl prose-h2:mt-12 prose-h2:mb-6 prose-h2:scroll-mt-24
                               prose-h3:text-2xl prose-h3:mt-8 prose-h3:mb-4 prose-h3:scroll-mt-24
                               prose-p:text-slate-600 dark:prose-p:text-slate-400 prose-p:leading-relaxed prose-p:text-xl
                               prose-a:text-[var(--color-primary)] prose-a:no-underline hover:prose-a:underline
                               prose-img:rounded-3xl prose-img:shadow-xl
                               prose-strong:text-slate-900 dark:prose-strong:text-white
                               prose-pre:bg-slate-900 prose-pre:scrolbar-hide prose-pre:shadow-2xl prose-pre:rounded-2xl
                               transition-colors" 
                        v-html="post.contenido"
                    >
                    </article>

                    <!-- Post Footer / Author -->
                    <footer class="mt-20 pt-16 border-t border-slate-100 dark:border-slate-800">
                        <!-- CTA Card -->
                        <div class="bg-slate-900 dark:bg-slate-800 rounded-[3.5rem] p-10 lg:p-14 text-white relative overflow-hidden group">
                            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-[var(--color-primary)] rounded-full blur-[100px] opacity-20 group-hover:scale-125 transition-transform duration-1000"></div>
                            
                            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-10">
                                <div class="text-center md:text-left max-w-md">
                                    <span class="text-[var(--color-primary)] font-black text-[10px] uppercase tracking-[0.3em] mb-4 block">Asesoría Profesional</span>
                                    <h3 class="text-3xl font-black mb-4 leading-tight">¿Necesitas implementar esto en tu negocio?</h3>
                                    <p class="text-slate-400 font-medium">Contáctanos hoy mismo y uno de nuestros ingenieros expertos diseñará una solución a tu medida.</p>
                                </div>
                                
                                <a :href="`https://wa.me/${empresa.whatsapp}`" 
                                   @click="reportInterest"
                                   class="bg-[var(--color-primary)] text-white px-10 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-[var(--color-primary-dark)] transition-all flex items-center gap-4 shadow-xl shadow-[var(--color-primary)]/20 hover:scale-105 active:scale-95 whitespace-nowrap"
                                >
                                    <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="text-xl" />
                                    Hablar con un Experto
                                </a>
                            </div>
                        </div>
                    </footer>
                </div>

                <!-- Sidebar (Table of Contents) -->
                <aside class="hidden lg:block">
                    <div class="sticky top-32 space-y-8">
                        <!-- TOC Widget -->
                        <div v-if="toc.length > 0" class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none animate-fade-in-up">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2">
                                <FontAwesomeIcon icon="list-ul" />
                                Contenido
                            </h4>
                            <nav class="space-y-1 relative">
                                <!-- Marker -->
                                <div 
                                    class="absolute left-0 w-0.5 bg-[var(--color-primary)] transition-all duration-300 ease-out"
                                    :style="{ top: markerTop + 'px', height: markerHeight + 'px' }"
                                ></div>

                                <a 
                                    v-for="(item, index) in toc" 
                                    :key="item.id"
                                    :href="`#${item.id}`"
                                    @click.prevent="scrollToHeading(item.id)"
                                    class="block pl-4 py-2 text-sm font-medium transition-all duration-200 border-l border-slate-100 dark:border-slate-800 hover:text-[var(--color-primary)] hover:border-slate-300"
                                    :class="[
                                        activeHeadingId === item.id 
                                            ? 'text-[var(--color-primary)]' 
                                            : 'text-slate-600 dark:text-slate-400'
                                    ]"
                                    :data-id="item.id"
                                >
                                    {{ item.text }}
                                </a>
                            </nav>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Related Posts Section (Full Width) -->
            <section v-if="relacionados.length > 0" class="max-w-7xl mx-auto px-4 pb-24 border-t border-slate-100 dark:border-slate-800 pt-20">
                <div class="flex items-center justify-between mb-10">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Artículos Relacionados</h3>
                    <Link :href="route('public.blog.index')" class="text-[var(--color-primary)] text-xs font-black uppercase tracking-widest hover:underline">Ver Todo →</Link>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <article v-for="rel in relacionados" :key="rel.id" class="group">
                        <Link :href="route('public.blog.show', rel.slug)">
                            <div class="relative aspect-video rounded-3xl overflow-hidden bg-slate-100 dark:bg-slate-800 mb-6 shadow-sm group-hover:shadow-xl transition-all duration-500">
                                <img v-if="rel.imagen_portada_url" 
                                     :src="rel.imagen_portada_url" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>
                            <h4 class="font-bold text-lg text-slate-900 dark:text-white group-hover:text-[var(--color-primary)] transition-colors line-clamp-2 leading-tight">
                                {{ rel.titulo }}
                            </h4>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-2">{{ rel.categoria }}</p>
                        </Link>
                    </article>
                </div>
            </section>
        </main>

        <WhatsAppWidget :telefono="empresa.whatsapp" />
        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.rich-text-content h2 {
    font-size: 1.875rem;
    font-weight: 800;
    color: #111827;
    margin-top: 2rem;
    margin-bottom: 1rem;
}
.dark .rich-text-content h2 {
    color: #f3f4f6;
}
.rich-text-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}
.dark .rich-text-content h3 {
    color: #f3f4f6;
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
