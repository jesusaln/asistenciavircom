<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({
    layout: PublicLayout,
    inheritAttrs: false,
});

const props = defineProps({
    servicio: Object,
    productosDestacados: Array,
    empresa: Object
});

const whatsappHref = `https://wa.me/${String(props.empresa?.whatsapp || '').replace(/\D/g, '')}?text=${encodeURIComponent(`Hola, me interesa ${props.servicio.titulo}. Quiero una propuesta para mi empresa.`)}`;
const sectors = props.servicio.sectores || [];
const metrics = props.servicio.metricas || [];
const pains = props.servicio.problemas || [];
const deliverables = props.servicio.entregables || [];
const serviceName = props.servicio.titulo || 'tu proyecto';
const heroPoints = (props.servicio.beneficios || []).slice(0, 3);
const processSteps = (props.servicio.entregables || []).slice(0, 3);

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
    <div class="relative min-h-[78vh] flex items-center overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img :src="servicio.imagen" class="w-full h-full object-cover" alt="Hero Background">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/80 to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 relative z-10 w-full">
            <div class="grid lg:grid-cols-[minmax(0,1.1fr)_420px] gap-10 items-end">
                <div class="max-w-3xl text-white">
                    <span class="inline-block px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 bg-white/10 backdrop-blur-md border border-white/20">
                        {{ servicio.badge || 'Nuestros Servicios' }}
                    </span>
                    <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tighter leading-tight animate-fade-in">
                        {{ servicio.titulo }}
                    </h1>
                    <p class="text-xl text-gray-300 font-medium mb-10 leading-relaxed max-w-2xl">
                        {{ servicio.subtitulo }}
                    </p>

                    <div class="flex flex-wrap gap-4 mb-8">
                        <a :href="whatsappHref" target="_blank" :class="[getBtnColor(servicio.color), 'px-8 py-4 rounded-2xl text-white font-black text-xs uppercase tracking-widest shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center gap-3']">
                            <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="text-xl" />
                            {{ servicio.cta_titulo || 'Solicitar Cotización' }}
                        </a>
                        <Link v-if="servicio.categoria_id" :href="route('catalogo.index', { categoria: servicio.categoria_id })" class="px-8 py-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white font-black text-xs uppercase tracking-widest hover:bg-white/20 transition-all flex items-center gap-3">
                            <FontAwesomeIcon icon="shopping-bag" />
                            Ver Productos
                        </Link>
                    </div>

                    <div v-if="heroPoints.length" class="flex flex-wrap gap-3 mb-8 max-w-4xl">
                        <div v-for="point in heroPoints" :key="point.titulo" class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-md">
                            <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center text-white text-sm">
                                <FontAwesomeIcon :icon="point.icon" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/70">{{ point.titulo }}</p>
                                <p class="text-sm text-white font-semibold leading-tight">{{ point.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="metrics.length" class="grid sm:grid-cols-3 gap-4 max-w-4xl">
                        <div v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-white/15 bg-white/10 backdrop-blur-md px-5 py-4">
                            <div class="text-2xl font-black text-white tracking-tight">{{ metric.valor }}</div>
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-300 mt-1">{{ metric.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-white/15 bg-white/10 backdrop-blur-xl p-7 text-white shadow-2xl">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-sky-300 mb-3">Evaluacion Inicial</p>
                    <h2 class="text-2xl font-black tracking-tight mb-3">{{ servicio.cta_titulo || 'Solicita una propuesta' }}</h2>
                    <p class="text-sm text-gray-300 leading-relaxed mb-6">
                        {{ servicio.cta_subtitulo || 'Cuéntanos tu necesidad y te orientamos con la mejor solución.' }}
                    </p>

                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-3 py-4 text-center">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">Respuesta</p>
                            <p class="text-sm font-black text-white mt-2">Agil</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-3 py-4 text-center">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">Canal</p>
                            <p class="text-sm font-black text-white mt-2">WhatsApp</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-3 py-4 text-center">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">Propuesta</p>
                            <p class="text-sm font-black text-white mt-2">Clara</p>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-4">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400 mb-1">Ideal para</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="sector in sectors" :key="sector" class="px-3 py-1 rounded-full bg-white/10 text-[10px] font-black uppercase tracking-wider text-white">
                                    {{ sector }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-4">
                            <div class="flex items-start gap-3">
                                <div class="w-11 h-11 rounded-xl bg-sky-400/15 text-sky-300 flex items-center justify-center flex-shrink-0">
                                    <FontAwesomeIcon icon="clipboard-check" />
                                </div>
                                <div>
                                    <p class="text-sm font-black uppercase tracking-wider">Analisis y propuesta</p>
                                    <p class="text-sm text-gray-300 mt-1">Recomendacion pensada para tu operacion, tus prioridades y tu presupuesto real.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a :href="whatsappHref" target="_blank" class="w-full px-6 py-4 rounded-2xl bg-white text-slate-900 font-black text-xs uppercase tracking-widest hover:bg-sky-100 transition-all inline-flex items-center justify-center gap-3">
                        <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="text-xl" />
                        Hablar con Ventas
                    </a>
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
                        Una solucion diseñada para <span :class="servicio.color === 'red' ? 'text-red-500' : 'text-blue-500'">mejorar tu operacion</span>
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
                        <img :src="servicio.imagen_detalle || servicio.imagen" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Service Detail">
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

            <div v-if="pains.length" class="mb-32">
                <div class="max-w-3xl mb-12">
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-4">
                        Problemas frecuentes que {{ serviceName.toLowerCase() }} resuelve bien
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                        Cuando esta parte de la operacion falla, el impacto se nota en servicio, control, imagen y cierre comercial. Aqui es donde una solucion bien implementada cambia el resultado.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div v-for="pain in pains" :key="pain.titulo" class="rounded-[2rem] border border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 p-7 shadow-sm hover:shadow-xl transition-all">
                        <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-300 flex items-center justify-center mb-5 text-xl">
                            <FontAwesomeIcon :icon="pain.icon" />
                        </div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight mb-2">{{ pain.titulo }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ pain.desc }}</p>
                    </div>
                </div>
            </div>

            <div v-if="deliverables.length" class="mb-32 grid lg:grid-cols-[1fr_360px] gap-10 items-start">
                <div class="rounded-[2.5rem] border border-gray-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 p-8 md:p-10">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[var(--color-primary)] mb-4">Qué Entregamos</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-8">
                        Alcance claro, implementacion ordenada y siguiente paso definido
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div v-for="item in deliverables" :key="item" class="flex items-start gap-3 rounded-2xl bg-white dark:bg-slate-950 border border-gray-100 dark:border-slate-800 p-5">
                            <div class="w-8 h-8 rounded-xl bg-[var(--color-primary)]/10 text-[var(--color-primary)] flex items-center justify-center flex-shrink-0">
                                <FontAwesomeIcon icon="check" />
                            </div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 leading-relaxed">{{ item }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2.5rem] border border-slate-900 dark:border-slate-700 bg-slate-900 dark:bg-slate-950 text-white p-8 shadow-2xl">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-sky-300 mb-4">Cierre Más Rápido</p>
                    <h3 class="text-2xl font-black tracking-tight mb-4">¿Necesitas avanzar ya?</h3>
                    <p class="text-sm text-gray-300 leading-relaxed mb-6">
                        Compartenos tu necesidad principal y te orientamos con el siguiente paso mas practico para cotizar o arrancar.
                    </p>
                    <a :href="whatsappHref" target="_blank" class="w-full px-6 py-4 rounded-2xl bg-[var(--color-primary)] text-white font-black text-xs uppercase tracking-widest hover:brightness-110 transition-all inline-flex items-center justify-center gap-3">
                        <FontAwesomeIcon :icon="['fab', 'whatsapp']" />
                        Solicitar Diagnóstico
                    </a>
                </div>
            </div>

            <div v-if="processSteps.length" class="mb-32">
                <div class="max-w-3xl mb-12">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[var(--color-primary)] mb-4">Cómo Avanzamos</p>
                    <h3 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-4">
                        Un proceso simple para llegar mas rapido a una solucion que si funcione
                    </h3>
                    <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                        Evitamos cotizaciones improvisadas. Primero entendemos tu necesidad, luego definimos alcance y finalmente aterrizamos la implementacion correcta.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div v-for="(step, index) in processSteps" :key="step" class="rounded-[2rem] border border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 p-7 shadow-sm">
                        <div class="w-12 h-12 rounded-2xl bg-[var(--color-primary)] text-white flex items-center justify-center text-lg font-black mb-5">
                            {{ index + 1 }}
                        </div>
                        <h4 class="text-lg font-black text-gray-900 dark:text-white tracking-tight mb-2">Paso {{ index + 1 }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ step }}</p>
                    </div>
                </div>
            </div>

            <!-- Featured Products (If applicable) -->
            <div v-if="productosDestacados.length > 0" class="border-t border-gray-100 dark:border-slate-800 pt-24">
                <div class="flex justify-between items-end mb-12">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Equipos Recomendados</h3>
                        <p class="text-gray-500 dark:text-gray-400">Opciones alineadas a esta solucion y a lo que mas piden tus clientes</p>
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

            <!-- Portfolio/Work Samples (If applicable) -->
            <div v-if="servicio.portafolio?.length > 0" class="border-t border-gray-100 dark:border-slate-800 pt-24">
                <div class="text-center mb-16">
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-4">Portafolio de Trabajo</h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Explora algunos de nuestros proyectos realizados y plantillas disponibles para tu negocio.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div v-for="item in servicio.portafolio" :key="item.nombre" class="group relative rounded-[2.5rem] overflow-hidden aspect-video shadow-2xl">
                        <img :src="item.imagen" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="Work Example">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent flex flex-col justify-end p-10 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                            <span class="text-[var(--color-primary)] font-black text-xs uppercase tracking-[0.3em] mb-2">{{ item.tipo }}</span>
                            <h4 class="text-2xl font-black text-white mb-4 tracking-tight">{{ item.nombre }}</h4>
                            <div class="flex">
                                <a :href="item.url" class="px-6 py-3 bg-white text-slate-900 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-[var(--color-primary)] hover:text-white transition-all">Ver Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Mini Badge for non-hover state -->
                        <div class="absolute bottom-6 left-6 p-4 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl group-hover:opacity-0 transition-opacity">
                            <h5 class="text-white font-black text-sm tracking-tight">{{ item.nombre }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-24 bg-slate-900 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-blue-600/10 to-transparent"></div>
        <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-8 tracking-tighter">{{ servicio.cta_final_titulo || '¿Listo para dar el siguiente paso con una solucion bien planteada?' }}</h2>
            <p class="text-xl text-gray-400 mb-12 max-w-3xl mx-auto">{{ servicio.cta_final_subtitulo || 'Te ayudamos a definir un alcance claro, una recomendacion realista y una propuesta lista para ejecutar.' }}</p>
            
            <div class="flex flex-center gap-6 flex-wrap">
                <a :href="whatsappHref" target="_blank" class="px-10 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl shadow-blue-900/40 hover:scale-105 transition-all">
                    Hablar por WhatsApp
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
