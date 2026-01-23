<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    empresa: Object,
    articulos: {
        type: Array,
        default: () => [
            {
                id: 1,
                titulo: '5 Claves para asegurar tu empresa con Videovigilancia IP',
                extracto: 'Descubre cómo los sistemas de cámaras IP de alta resolución pueden transformar la seguridad y el control operativo de tu negocio.',
                imagen: null,
                categoria: 'Seguridad',
                icono: '🛡️',
                fecha: '14 Ene 2026',
                tiempo_lectura: '5 min',
                destacado: true,
            },
            {
                id: 2,
                titulo: 'Soporte Técnico Preventivo: Evita paros operativos',
                extracto: 'No esperes a que tu equipo falle. Conoce las ventajas de un mantenimiento proactivo en la infraestructura de TI.',
                imagen: null,
                categoria: 'Tecnología',
                icono: '💻',
                fecha: '10 Ene 2026',
                tiempo_lectura: '4 min',
                destacado: false,
            },
            {
                id: 3,
                titulo: 'Cómo elegir la mejor red WiFi para tu oficina u hogar',
                extracto: 'Analizamos las diferencias entre los estándares WiFi y cómo asegurar una conexión estable en todos tus dispositivos.',
                imagen: null,
                categoria: 'Redes',
                icono: '📶',
                fecha: '5 Ene 2026',
                tiempo_lectura: '6 min',
                destacado: false,
            },
        ]
    }
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3B82F6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3B82F6') + '15',
}));

const articuloDestacado = computed(() => props.articulos.find(a => a.destacado) || props.articulos[0]);
const articulosSecundarios = computed(() => props.articulos.filter(a => a.id !== articuloDestacado.value?.id).slice(0, 2));
</script>

<template>
    <section id="blog" class="py-24 bg-white dark:bg-slate-900 dark:bg-gray-900 relative overflow-hidden transition-colors duration-300" :style="cssVars">
        <!-- Decorative Pattern -->
        <div class="absolute inset-0 opacity-[0.02] dark:opacity-[0.05] pointer-events-none transition-opacity">
            <svg width="100%" height="100%">
                <pattern id="blogGrid" width="60" height="60" patternUnits="userSpaceOnUse">
                    <circle cx="30" cy="30" r="1" class="text-gray-900 dark:text-white dark:text-white" fill="currentColor" />
                </pattern>
                <rect width="100%" height="100%" fill="url(#blogGrid)" />
            </svg>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-16">
                <div>
                    <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">📰 Blog & Consejos</span>
                    <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white dark:text-white tracking-tight transition-colors">
                        Aprende sobre <span class="text-[var(--color-primary)]">Tecnología y Seguridad</span>
                    </h2>
                    <p class="mt-4 text-gray-500 dark:text-gray-400 dark:text-gray-400 max-w-xl transition-colors">
                        Tips, guías y tendencias de expertos para potenciar y proteger tu empresa u hogar.
                    </p>
                </div>
                
                <Link 
                    href="/blog" 
                    class="mt-6 md:mt-0 inline-flex items-center gap-2 text-[var(--color-primary)] font-bold hover:underline group"
                >
                    Ver todos los artículos
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
            </div>
            
            <!-- Articles Grid -->
            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Featured Article -->
                <article v-if="articuloDestacado" class="group relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl overflow-hidden min-h-[500px] flex flex-col justify-end">
                    <!-- Background Image/Gradient -->
                    <div class="absolute inset-0">
                        <img 
                            v-if="articuloDestacado.imagen" 
                            :src="articuloDestacado.imagen" 
                            :alt="articuloDestacado.titulo"
                            class="w-full h-full object-cover opacity-40 group-hover:scale-105 transition-transform duration-700"
                        >
                        <div v-else class="absolute inset-0 bg-gradient-to-br from-[var(--color-primary)]/20 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/50 to-transparent"></div>
                    </div>
                    
                    <!-- Content -->
                    <div class="relative z-10 p-8 lg:p-10">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-3 py-1 bg-[var(--color-primary)] text-white text-[10px] font-black uppercase tracking-widest rounded-full">
                                {{ articuloDestacado.categoria }}
                            </span>
                            <span class="text-gray-400 text-sm">{{ articuloDestacado.fecha }}</span>
                            <span class="text-gray-400 text-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ articuloDestacado.tiempo_lectura }}
                            </span>
                        </div>
                        
                        <h3 class="text-2xl lg:text-3xl font-black text-white mb-4 leading-tight group-hover:text-[var(--color-primary)] transition-colors">
                            {{ articuloDestacado.titulo }}
                        </h3>
                        
                        <p class="text-gray-300 mb-6 leading-relaxed line-clamp-2">
                            {{ articuloDestacado.extracto }}
                        </p>
                        
                        <Link 
                            :href="'/blog/' + articuloDestacado.slug" 
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl font-bold text-sm hover:bg-[var(--color-primary)] hover:text-white transition-all duration-300"
                        >
                            Leer artículo
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </article>
                
                <!-- Secondary Articles -->
                <div class="flex flex-col gap-6">
                    <article 
                        v-for="articulo in articulosSecundarios" 
                        :key="articulo.id"
                        class="group bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:border-[var(--color-primary)]/20 transition-all duration-300"
                    >
                        <div class="flex">
                            <!-- Thumbnail -->
                            <div class="w-32 md:w-48 flex-shrink-0 bg-gray-100 dark:bg-gray-700 flex items-center justify-center transition-colors">
                                <img 
                                    v-if="articulo.imagen"
                                    :src="articulo.imagen"
                                    :alt="articulo.titulo"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                >
                                <span v-else class="text-5xl opacity-50">{{ articulo.icono }}</span>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 p-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-[var(--color-primary)] text-[10px] font-black uppercase tracking-widest">
                                        {{ articulo.categoria }}
                                    </span>
                                    <span class="text-gray-400 text-xs">{{ articulo.tiempo_lectura }}</span>
                                </div>
                                
                                <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2 group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">
                                    {{ articulo.titulo }}
                                </h3>
                                
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 line-clamp-2 mb-4 transition-colors">
                                    {{ articulo.extracto }}
                                </p>
                                
                                <Link :href="'/blog/' + articulo.slug" class="inline-flex items-center text-sm font-bold text-[var(--color-primary)]">
                                    Leer más
                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </article>
                    
                    <!-- Newsletter CTA -->
                    <div class="bg-[var(--color-primary-soft)] rounded-2xl p-6 border border-[var(--color-primary)]/10">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[var(--color-primary)] rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0">
                                📧
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Recibe consejos cada semana</h4>
                                <p class="text-sm text-gray-600 mb-4">Tips exclusivos sobre seguridad, redes y mantenimiento de equipo.</p>
                                <div class="flex gap-2">
                                    <input 
                                        type="email"
                                        placeholder="tu@email.com"
                                        class="flex-1 px-4 py-2 rounded-lg border border-gray-200 dark:border-slate-800 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none text-sm"
                                    >
                                    <button class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-lg font-bold text-sm hover:shadow-lg transition-all">
                                        Suscribir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
