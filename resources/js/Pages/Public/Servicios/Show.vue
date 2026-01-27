<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    servicio: Object,
    productosDestacados: Array,
    empresa: Object
});

const getIconColor = (color) => {
    const colors = {
        blue: 'text-blue-500 bg-blue-50 dark:bg-blue-900/20',
        indigo: 'text-indigo-500 bg-indigo-50 dark:bg-indigo-900/20',
        red: 'text-red-500 bg-red-50 dark:bg-red-900/20',
        emerald: 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20',
        amber: 'text-amber-500 bg-amber-50 dark:bg-amber-900/20',
        sky: 'text-sky-500 bg-sky-50 dark:bg-sky-900/20',
    };
    return colors[color] || colors.blue;
};

const getBtnColor = (color) => {
    const colors = {
        blue: 'bg-blue-600 hover:bg-blue-700',
        indigo: 'bg-indigo-600 hover:bg-indigo-700',
        red: 'bg-red-600 hover:bg-red-700',
        emerald: 'bg-emerald-600 hover:bg-emerald-700',
        amber: 'bg-amber-600 hover:bg-amber-700',
        sky: 'bg-sky-600 hover:bg-sky-700',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <Head :title="servicio.titulo" />

    <!-- Hero Section -->
    <div class="relative min-h-[70vh] flex items-center overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img :src="servicio.imagen" class="w-full h-full object-cover" alt="Hero Background">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/80 to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 relative z-10 w-full">
            <div class="max-w-2xl text-white">
                <span class="inline-block px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 bg-white/10 backdrop-blur-md border border-white/20">
                    Nuestros Servicios
                </span>
                <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tighter leading-tight animate-fade-in">
                    {{ servicio.titulo }}
                </h1>
                <p class="text-xl text-gray-300 font-medium mb-10 leading-relaxed max-w-xl">
                    {{ servicio.subtitulo }}
                </p>
                
                <div class="flex flex-wrap gap-4">
                    <a :href="`https://wa.me/${empresa.whatsapp}?text=Hola, me interesa información sobre ${servicio.titulo}`" target="_blank" :class="[getBtnColor(servicio.color), 'px-8 py-4 rounded-2xl text-white font-black text-xs uppercase tracking-widest shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center gap-3']">
                        <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="text-xl" />
                        Solicitar Cotización
                    </a>
                    <Link v-if="servicio.categoria_id" :href="route('catalogo.index', { categoria: servicio.categoria_id })" class="px-8 py-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white font-black text-xs uppercase tracking-widest hover:bg-white/20 transition-all flex items-center gap-3">
                        <FontAwesomeIcon icon="shopping-bag" />
                        Ver Productos
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Description & Benefits -->
    <div class="py-24 bg-white dark:bg-slate-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center mb-32">
                <div>
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-8 tracking-tight">
                        Soluciones Profesionales para <span :class="servicio.color === 'red' ? 'text-red-500' : 'text-blue-500'">tu Tranquilidad</span>
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed mb-10">
                        {{ servicio.descripcion }}
                    </p>
                    
                    <div class="space-y-6">
                        <div v-for="benefit in servicio.beneficios" :key="benefit.titulo" class="flex gap-4 p-6 rounded-2xl bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 transition-all hover:shadow-lg hover:-translate-y-1">
                            <div :class="[getIconColor(servicio.color), 'w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 text-xl shadow-inner']">
                                <FontAwesomeIcon :icon="benefit.icon" />
                            </div>
                            <div>
                                <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-wider text-sm mb-1">{{ benefit.titulo }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ benefit.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="aspect-[4/5] rounded-[3rem] overflow-hidden shadow-2xl relative group">
                        <img :src="servicio.imagen" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Service Detail">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                        
                        <div class="absolute bottom-10 left-10 right-10 p-8 bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl text-white">
                            <h4 class="text-2xl font-black mb-2">Instalación Certificada</h4>
                            <p class="text-sm text-gray-200">Personal capacitado y con amplia experiencia técnica.</p>
                        </div>
                    </div>
                    
                    <!-- Floating Badge -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-[var(--color-primary)] rounded-full flex flex-center text-center p-6 text-white shadow-2xl border-8 border-white dark:border-slate-950 animate-bounce-slow">
                        <div>
                            <span class="block text-3xl font-black">100%</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest leading-tight">Garantizado</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Products (If applicable) -->
            <div v-if="productosDestacados.length > 0" class="border-t border-gray-100 dark:border-slate-800 pt-24">
                <div class="flex justify-between items-end mb-12">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Equipos Destacados</h3>
                        <p class="text-gray-500 dark:text-gray-400">Los más solicitados por nuestros clientes</p>
                    </div>
                    <Link :href="route('catalogo.index', { categoria: servicio.categoria_id })" class="text-sm font-black text-[var(--color-primary)] uppercase tracking-widest hover:underline">
                        Ver Catálogo Completo →
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <Link v-for="producto in productosDestacados" :key="producto.id" :href="route('catalogo.show', producto.id)" class="group bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-[2.5rem] p-4 transition-all hover:shadow-2xl hover:-translate-y-2">
                        <div class="aspect-square bg-gray-50 dark:bg-slate-800 rounded-[2rem] overflow-hidden mb-6 relative">
                            <img :src="producto.imagen || '/placeholder.png'" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Product">
                            <div class="absolute top-4 right-4 px-3 py-1 bg-white/90 backdrop-blur text-[10px] font-black rounded-full text-slate-900 shadow-sm">
                                DISPONIBLE
                            </div>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 px-2 group-hover:text-[var(--color-primary)] transition-colors">{{ producto.nombre }}</h4>
                        <p class="text-xl font-black text-gray-900 dark:text-white px-2 mt-auto">
                            ${{ producto.precio.toLocaleString('es-MX') }}
                            <span class="text-[10px] text-gray-400 font-medium">IVA INCL.</span>
                        </p>
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-24 bg-slate-900 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-blue-600/10 to-transparent"></div>
        <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-8 tracking-tighter">¿Listo para mejorar tu Seguridad?</h2>
            <p class="text-xl text-gray-400 mb-12 max-w-2xl mx-auto">Nuestro equipo técnico está listo para asesorarte con la mejor solución técnica y económica para tu proyecto.</p>
            
            <div class="flex flex-center gap-6 flex-wrap">
                <a :href="`https://wa.me/${empresa.whatsapp}`" target="_blank" class="px-10 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl shadow-blue-900/40 hover:scale-105 transition-all">
                    Chatear con un Experto
                </a>
                <Link :href="route('public.contacto')" class="px-10 py-5 bg-white/10 backdrop-blur-md border border-white/20 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-white/20 transition-all">
                    Llenar Formulario
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 1s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-rotate {
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-bounce-slow {
    animation: bounce 4s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}
</style>
