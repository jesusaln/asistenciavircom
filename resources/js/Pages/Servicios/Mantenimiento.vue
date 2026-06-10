<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import PricingSection from '@/Components/Public/PricingSection.vue';
import MirageTrustSection from '@/Components/Public/MirageTrustSection.vue';
import QuickAppointmentForm from '@/Components/QuickAppointmentForm.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    empresa: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa?.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (props.empresa?.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': props.empresa?.color_secundario || '#1E40AF',
}));

const whatsappLink = computed(() => {
    const phone = (props.empresa?.whatsapp || props.empresa?.telefono || '').replace(/\D/g, '');
    return `https://wa.me/${phone}?text=${encodeURIComponent('Hola, me gustaría agendar un mantenimiento preventivo para mi minisplit. ¿Qué disponibilidad tienen?')}`;
});

const beneficios = [
    { icono: 'bolt', titulo: 'Ahorro de Energía', desc: 'Un equipo limpio consume hasta un 30% menos de electricidad al enfriar con mayor eficiencia.' },
    { icono: 'wind', titulo: 'Aire más Limpio', desc: 'Eliminamos hongos, bacterias y polvo acumulado que afectan la salud respiratoria de tu familia.' },
    { icono: 'hourglass-half', titulo: 'Mayor Vida Útil', desc: 'Evita el desgaste prematuro de piezas clave como el compresor y el motor del ventilador.' },
    { icono: 'dollar-sign', titulo: 'Evita Reparaciones', desc: 'Detectamos pequeñas fallas antes de que se conviertan en reparaciones costosas y urgentes.' },
    { icono: 'snowflake', titulo: 'Máximo Enfriamiento', desc: 'Recupera la potencia original de tu minisplit y logra la temperatura deseada en menos tiempo.' },
    { icono: 'shield-halved', titulo: 'Operación Silenciosa', desc: 'La limpieza de la turbina elimina vibraciones y ruidos molestos durante el funcionamiento.' },
];

const proceso = [
    { paso: '01', titulo: 'Desarmado', desc: 'Retiramos cubiertas y filtros para acceder a las áreas críticas del equipo.', icono: 'tools' },
    { paso: '02', titulo: 'Limpieza Profunda', desc: 'Lavado con hidrolavadora y químicos biodegradables en serpentín y turbina.', icono: 'wind' },
    { paso: '03', titulo: 'Desinfección', desc: 'Aplicamos bactericida y fungicida para asegurar un aire libre de patógenos.', icono: 'shield-halved' },
    { paso: '04', titulo: 'Revisión Técnica', desc: 'Verificamos presión de gas, consumo eléctrico y limpieza de condensadora.', icono: 'clipboard-check' },
];

const otrosServicios = [
    { 
        titulo: 'Reparación de Minisplit', 
        desc: 'Diagnóstico profesional y solución a fallas mecánicas o eléctricas.', 
        icono: 'tools',
        link: route('public.reparacion'),
    },
    { 
        titulo: 'Pólizas de Soporte', 
        desc: 'Planes de mantenimiento anual para hogar y oficina con precio preferencial.', 
        icono: 'file-alt',
        link: route('catalogo.polizas'),
    },
    { 
        titulo: 'Venta de Equipos', 
        desc: 'Los mejores modelos Mirage Inverter con instalación incluida.', 
        icono: 'snowflake',
        link: route('catalogo.index'),
    },
    { 
        titulo: 'Renta de Equipos', 
        desc: 'Soluciones temporales de climatización para eventos o proyectos.', 
        icono: 'truck',
        link: route('catalogo.rentas'),
    },
];

const incluyeMantenimiento = [
    'Lavado a presión de evaporador con hidrolavadora profunda.',
    'Lavado a fondo de unidad condensadora exterior.',
    'Limpieza de charola y manguera de desagüe (antiobstrucción).',
    'Desinfección de serpentín con químicos biodegradables.',
    'Limpieza de turbina, filtros y cubiertas plásticas.',
    'Revisión de presiones de gas refrigerante.',
    'Medición de consumo de amperaje y limpieza de terminales de voltaje.',
];

const noIncluyeMantenimiento = [
    'Recarga de gas refrigerante (en caso de detectar baja presión).',
    'Reparaciones mecánicas o eléctricas por fallas existentes.',
    'Cambio de piezas dañadas (capacitores, sensores, motores).',
    'Corrección de fugas de gas en tuberías o componentes del equipo.',
    'Servicios en alturas superiores a un primer piso sin acceso seguro.',
];

const preciosMantenimiento = [
    { capacidad: '1 Tonelada', precio: '$500', icono: 'snowflake' },
    { capacidad: '1.5 Toneladas', precio: '$600', icono: 'snowflake' },
    { capacidad: '2 Toneladas', precio: '$700', icono: 'snowflake' },
    { capacidad: '3 Toneladas', precio: '$850', icono: 'snowflake' },
];

const costosExtrasMantenimiento = [
    { item: 'Recarga de Gas R410a (Primeros 100g)', precio: '$250', icono: 'bolt' },
    { item: 'Recarga de Gas R22 (Primeros 100g)', precio: '$350', icono: 'snowflake' },
    { item: '100g Extra de Gas R410a', precio: '$100', icono: 'plus-circle' },
    { item: '100g Extra de Gas R22', precio: '$150', icono: 'plus-circle' },
    { item: 'Cambio de Capacitor de Compresor', precio: '$650', icono: 'bolt' },
    { item: 'Cambio de Sensor de Temperatura', precio: '$550', icono: 'thermometer-half' },
    { item: 'Peinado de Serpentín de Unidad Exterior', precio: '$350', icono: 'brush' },
    { item: 'Acceso Difícil a Condensadora (+2 metros)', precio: '$250', icono: 'mountain' },
    { item: 'Lavado a Detalle con Desmontaje (Inc. Vacío y Reinstalación)', precio: '$1,350', icono: 'tools' },
    { item: 'Localización de Fuga (Mano de Obra)', precio: '$550', icono: 'search' },
    { item: 'Vacío y Prueba de Hermeticidad (Sellado Certificado)', precio: '$450', icono: 'wind' },
];

const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

const faqs = [
    { id: 1, pregunta: '¿Cada cuánto tiempo se debe dar mantenimiento?', respuesta: 'Para uso doméstico, recomendamos un mantenimiento profundo cada 6 meses (antes de iniciar la temporada de calor y antes del invierno). En oficinas o comercios, se recomienda cada 3 o 4 meses.' },
    { id: 2, pregunta: '¿Qué incluye el mantenimiento preventivo?', respuesta: 'Incluye lavado a presión del evaporador y condensadora, limpieza de filtros y cubiertas, revisión de niveles de gas, verificación de consumo eléctrico, limpieza de drenaje y desinfección total.' },
    { id: 3, pregunta: '¿Qué pasa si mi equipo necesita gas?', respuesta: 'Un minisplit es un sistema sellado; si necesita gas es porque tiene una FUGA. La recarga de gas es una solución inmediata para recuperar el enfriamiento, pero es temporal si no se localiza y repara la fuga físicamente.' },
    { id: 4, pregunta: '¿Ensucian mi casa durante la limpieza?', respuesta: '¡Para nada! Utilizamos bolsas colectoras especiales para hidrolavado que canalizan toda el agua sucia directamente a un recipiente, protegiendo tus paredes y muebles.' },
    { id: 5, pregunta: '¿Cuánto tiempo dura el servicio?', respuesta: 'Un mantenimiento preventivo estándar toma entre 60 y 90 minutos por equipo, dependiendo del nivel de suciedad y accesibilidad.' },
    { id: 6, pregunta: '¿La recarga de gas tiene garantía?', respuesta: 'Utilizamos el protocolo de "Sellado Certificado" con nitrógeno para asegurar que no existan fugas al momento de entregarle su equipo. Sin embargo, no otorgamos garantía de tiempo sobre la duración del gas si no se realiza la reparación física de la fuga, ya que factores externos o movimientos posteriores podrían afectarlo.' },
];
</script>

<template>
    <Head title="Mantenimiento Preventivo de Minisplit - Climas del Desierto">
        <meta name="description" :content="`Limpieza profunda y mantenimiento preventivo de minisplit en ${empresa?.ciudad || 'Hermosillo'}. Ahorra energía, mejora la salud y alarga la vida de tu equipo.`" />
    </Head>

    <div :style="cssVars" class="min-h-screen  flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" activeTab="mantenimiento" />

        <main class="flex-grow">
            <!-- HERO SECTION -->
            <section class="relative min-h-[80vh] flex items-center bg-slate-950 overflow-hidden">
                <!-- Background Image -->
                <div class="absolute inset-0">
                    <img 
                        src="/storage/servicios/mantenimiento-preventivo-hero.webp" 
                        alt="Mantenimiento Preventivo" 
                        class="w-full h-full object-cover opacity-30"
                    >
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/80 to-transparent"></div>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute top-20 right-10 w-72 h-72 bg-[var(--color-primary)] rounded-full blur-[150px] opacity-20"></div>
                <div class="absolute bottom-10 left-1/4 w-48 h-48 bg-brand-500 rounded-full blur-[100px] opacity-10"></div>

                <!-- Content -->
                <div class="relative z-10 max-w-7xl mx-auto px-4 py-20 w-full">
                    <div class="max-w-2xl">
                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/10 mb-8">
                            <span class="text-brand-400 text-xs font-bold uppercase tracking-wide">Optimiza tu Consumo Eléctrico</span>
                        </div>

                        <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight leading-[0.9]">
                            Limpieza<br>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Preventiva</span>
                        </h1>

                        <p class="text-xl text-slate-300 mb-10 leading-relaxed max-w-lg">
                            No esperes a que falle. Un mantenimiento profesional mejora la calidad del aire y <strong class="text-white">reduce tu recibo de luz</strong> hasta un 30%.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <a 
                                :href="whatsappLink" 
                                target="_blank"
                                class="group px-8 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-wide shadow-xl shadow-[var(--color-primary)]/30 hover:shadow-2xl hover:shadow-[var(--color-primary)]/40 hover:scale-105 transition-all duration-200 flex items-center justify-center gap-3"
                            >
                                <svg class="w-4 h-4 group-hover:scale-105 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Agendar Mantenimiento
                            </a>
                            <a 
                                href="#agendar-cita"
                                class="px-8 py-5 border-2 border-white/20 text-white rounded-2xl font-black text-sm uppercase tracking-wide hover:bg-white/10 transition-all duration-200 text-center"
                            >
                                Agendar por Web
                            </a>
                        </div>

                        <!-- Highlights -->
                        <div class="flex gap-8 mt-12 pt-8 border-t border-white/10">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 bg-brand-500/20 rounded-full flex items-center justify-center text-emerald-400">
                                    <font-awesome-icon icon="check-circle" />
                                </div>
                                <span class="text-sm font-bold text-slate-300">Sin Manchas en Paredes</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 bg-brand-500/20 rounded-full flex items-center justify-center text-blue-400">
                                    <font-awesome-icon icon="snowflake" />
                                </div>
                                <span class="text-sm font-bold text-slate-300">Químicos Biodegradables</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- BENEFICIOS -->
            <section class="py-24  transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">¿Por qué hacerlo?</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Beneficios de un <span class="text-[var(--color-primary)]">Equipo Limpio</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="(ben, i) in beneficios" 
                            :key="i"
                            class="group p-8 rounded-3xl bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-100 dark:border-slate-700 hover:shadow-xl hover:border-[var(--color-primary)]/30 transition-all duration-500"
                        >
                            <span class="text-4xl mb-6 block text-[var(--color-primary)] group-hover:scale-105 transition-transform">
                                <font-awesome-icon :icon="ben.icono" />
                            </span>
                            <h3 class="text-xl font-black text-slate-100 text-slate-100 mb-3 transition-colors">{{ ben.titulo }}</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed transition-colors">{{ ben.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- DETALLES DEL SERVICIO -->
            <section class="py-24 bg-slate-900/20 border-y border-slate-100 dark:border-slate-800">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="grid lg:grid-cols-2 gap-12">
                        <!-- SÍ INCLUYE -->
                        <div class="bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-slate-800/10 rounded-[3rem] p-8 md:p-12 border border-emerald-100 dark:border-emerald-900/30 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-16 h-16 bg-brand-500 rounded-2xl flex items-center justify-center text-3xl shadow-xl shadow-emerald-500/20 text-white">
                                    <font-awesome-icon icon="circle-check" />
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-slate-100 text-slate-100 tracking-tight">Sí Incluye</h2>
                                    <p class="text-emerald-600 dark:text-slate-400 font-bold uppercase tracking-wide text-xs">Mantenimiento Preventivo Profundo</p>
                                </div>
                            </div>

                            <ul class="space-y-6">
                                <li v-for="(item, i) in incluyeMantenimiento" :key="i" class="flex items-start gap-4 p-4 bg-black/50 dark:bg-black/50 rounded-2xl shadow-xl-sm border border-emerald-100/30 transition-all hover:scale-[1.02]">
                                    <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white text-[10px] shrink-0 mt-0.5">
                                        <font-awesome-icon icon="check" />
                                    </div>
                                    <span class="text-slate-300 dark:text-slate-200 font-black text-[15px] leading-tight">{{ item }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- NO INCLUYE -->
                        <div class="bg-rose-50 dark:bg-rose-900/20/50 dark:bg-rose-900/10 rounded-[3rem] p-8 md:p-12 border border-rose-100 dark:border-rose-900/30 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-16 h-16 bg-brand-500 rounded-2xl flex items-center justify-center text-3xl shadow-xl shadow-rose-500/20 text-white">
                                    <font-awesome-icon icon="circle-xmark" />
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-slate-100 text-slate-100 tracking-tight">No Incluye</h2>
                                    <p class="text-rose-600 dark:text-rose-400 font-bold uppercase tracking-wide text-xs">Cargos Adicionales</p>
                                </div>
                            </div>

                            <ul class="space-y-6">
                                <li v-for="(item, i) in noIncluyeMantenimiento" :key="i" class="flex items-start gap-4 p-4 bg-black/50 dark:bg-black/50 rounded-2xl shadow-xl-sm border border-rose-100/30 transition-all hover:scale-[1.02]">
                                    <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white text-[10px] shrink-0 mt-0.5">
                                        <font-awesome-icon icon="times" />
                                    </div>
                                    <span class="text-slate-300 dark:text-slate-200 font-black text-[15px] leading-tight">{{ item }}</span>
                                </li>
                            </ul>
                            
                            <div class="mt-12 p-6 bg-brand-500/10 rounded-2xl border border-rose-500/20">
                                <p class="text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 text-sm font-bold">
                                    <font-awesome-icon icon="triangle-exclamation" class="mr-2" /> Importante: El mantenimiento preventivo NO repara fallas existentes de tu equipo.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PRECIOS DE MANTENIMIENTO -->
            <section class="py-24  transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Nuestras Tarifas</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Precios de <span class="text-[var(--color-primary)]">Mantenimiento</span>
                        </h2>
                        <p class="mt-4 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide text-sm">
                            Precios Netos • Incluye Limpieza Profunda
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div 
                            v-for="(plan, i) in preciosMantenimiento" 
                            :key="i"
                            class="group relative p-8 rounded-[2.5rem] bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-100 dark:border-slate-700 hover:shadow-2xl hover:border-[var(--color-primary)]/50 transition-all duration-500 hover:shadow-xl hover:shadow-xl text-center"
                        >
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-3xl mb-6 mx-auto group-hover:bg-[var(--color-primary)] transition-all">
                                <font-awesome-icon :icon="plan.icono" class="text-[var(--color-primary)] group-hover:text-white" />
                            </div>
                            <h3 class="text-xl font-black text-slate-100 text-slate-100 mb-2 transition-colors">{{ plan.capacidad }}</h3>
                            <div class="flex items-center justify-center gap-1 mb-4">
                                <span class="text-4xl font-black text-[var(--color-primary)]">{{ plan.precio }}</span>
                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">MXN</span>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wide">Precio Neto</p>
                            
                            <!-- Borde decorativo hover -->
                            <div class="absolute inset-0 border-2 border-[var(--color-primary)]/0 rounded-[2.5rem] group-hover:border-[var(--color-primary)]/20 transition-all pointer-events-none"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TRUST SECTION -->
            <MirageTrustSection />

            <!-- PRICING SECTION -->
            <PricingSection :whatsappLink="whatsappLink" />

            <!-- COSTOS EXTRAS -->
            <section class="py-24 bg-slate-900/20 border-t border-slate-100 dark:border-slate-800 transition-colors">
                <div class="max-w-5xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Transparencia Total</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Posibles <span class="text-[var(--color-primary)] text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-brand-500">Costos Extras</span>
                        </h2>
                    </div>

                    <div class="bg-slate-50 dark:bg-black/50 rounded-[3rem] p-6 md:p-10 shadow-xl border border-slate-100 dark:border-slate-700">
                        <div class="grid sm:grid-cols-2 lg:grid-cols-1 gap-2">
                            <div 
                                v-for="(extra, i) in costosExtrasMantenimiento" 
                                :key="i"
                                class="flex flex-col lg:flex-row lg:items-center justify-between p-6 rounded-2xl  hover:bg-white dark:hover:bg-slate-950 transition-all border border-transparent hover:border-[var(--color-primary)]/50 group gap-4 shadow-sm"
                            >
                                <div class="flex items-center gap-5">
                                    <div class="w-10 h-10 bg-[var(--color-primary-soft)] rounded-xl flex items-center justify-center text-xl group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all">
                                        <font-awesome-icon :icon="extra.icono" class="text-[var(--color-primary)] group-hover:text-white" />
                                    </div>
                                    <span class="font-black text-slate-200 dark:text-slate-200 text-[16px] group-hover:text-[var(--color-primary)] transition-colors">{{ extra.item }}</span>
                                </div>
                                <div class="flex items-center gap-2 self-end sm:self-auto">
                                    <span class="text-2xl font-black text-[var(--color-primary)]">{{ extra.precio }}</span>
                                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">MXN</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 p-8 bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/10 rounded-[2.5rem] border border-brand-200 dark:border-brand-800/30 dark:border-brand-900/30">
                        <div class="flex flex-col md:flex-row gap-6 items-center">
                            <div class="w-16 h-16 bg-brand-500 rounded-2xl flex items-center justify-center text-3xl shadow-xl shadow-brand-500/20 text-white shrink-0">
                                <font-awesome-icon icon="circle-info" />
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-brand-900 dark:text-brand-400 mb-2 hover:underline decoration-brand-500/30 underline-offset-4 decoration-2">Sobre las Recargas de Gas</h4>
                                <p class="text-brand-800 dark:text-brand-200/80 dark:text-brand-400/80 font-medium leading-relaxed mb-4">
                                    Un minisplit es un sistema sellado por tuberías de cobre; si le falta gas es porque existe una <strong>fuga técnica</strong>. La recarga de gas es una solución paliativa para que el equipo enfríe inmediatamente, pero NO arregla la raíz del problema. No otorgamos garantía sobre la duración del gas recargado a menos que se realice la localización y reparación física de la fuga.
                                </p>
                                <div class="bg-white/50 dark:bg-black/20 p-5 rounded-2xl border border-brand-200 dark:border-brand-800/30/50">
                                    <h5 class="font-black text-brand-900 dark:text-brand-300 text-sm uppercase tracking-wide mb-3">La Recarga Completa Profesional incluye:</h5>
                                    <ul class="grid sm:grid-cols-2 gap-3">
                                        <li class="flex items-center gap-2 text-xs font-bold text-brand-800 dark:text-brand-200 dark:text-amber-400"><font-awesome-icon icon="check" class="text-brand-500" /> Extracción de aire y humedad.</li>
                                        <li class="flex items-center gap-2 text-xs font-bold text-brand-800 dark:text-brand-200 dark:text-amber-400"><font-awesome-icon icon="check" class="text-brand-500" /> Vacío profundo con bomba profesional.</li>
                                        <li class="flex items-center gap-2 text-xs font-bold text-brand-800 dark:text-brand-200 dark:text-amber-400"><font-awesome-icon icon="check" class="text-brand-500" /> Carga con báscula digital (gramaje exacto).</li>
                                        <li class="flex items-center gap-2 text-xs font-bold text-brand-800 dark:text-brand-200 dark:text-amber-400"><font-awesome-icon icon="check" class="text-brand-500" /> Ajuste según placa nominal de fabricante.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- NUESTRO PROCESO -->
            <section class="py-24 bg-slate-900/20 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Metodología Vircom</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Así dejamos tu <span class="text-[var(--color-primary)]">Equipo como Nuevo</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div 
                            v-for="(step, i) in proceso" 
                            :key="i"
                            class="group relative bg-black/50 dark:bg-black/50 p-10 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none hover:shadow-xl hover:shadow-xl transition-all duration-500 hover:border-[var(--color-primary)]"
                        >
                            <div class="w-16 h-16 bg-black/50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:bg-[var(--color-primary-soft)] transition-all shadow-sm">
                                <font-awesome-icon :icon="step.icono" class="text-[var(--color-primary)]" />
                            </div>
                            <span class="absolute top-8 right-8 text-4xl font-black text-slate-100 dark:text-slate-200 select-none">{{ step.paso }}</span>
                            <h3 class="text-xl font-black text-slate-100 text-slate-100 mb-4 transition-colors">{{ step.titulo }}</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed transition-colors">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FORMULARIO DE CITA -->
            <section id="agendar-cita">
                <QuickAppointmentForm 
                    :empresa="empresa" 
                    initialService="mantenimiento"
                    :isSimplified="true"
                />
            </section>

            <!-- PREGUNTAS FRECUENTES -->
            <section class="py-24  transition-colors">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Dudas sobre el <span class="text-[var(--color-primary)]">Mantenimiento</span>
                        </h2>
                    </div>

                    <div class="space-y-6">
                        <div 
                            v-for="faq in faqs" 
                            :key="faq.id"
                            class="bg-black/50 dark:bg-black/50 rounded-[2rem] border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-200 group"
                        >
                            <button 
                                @click="toggleFaq(faq.id)"
                                class="w-full px-8 py-7 flex items-center justify-between text-left"
                            >
                                <span class="font-black text-slate-100 text-slate-100 group-hover:text-[var(--color-primary)] transition-colors text-lg line-clamp-1 pr-4">{{ faq.pregunta }}</span>
                                <span class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 transition-all duration-200 shrink-0" :class="{'rotate-180 bg-[var(--color-primary)] text-white': activeFaq === faq.id}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div v-if="activeFaq === faq.id" class="px-8 pb-8 pt-2 animate-fade-in">
                                <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed border-t border-slate-50 dark:border-slate-700 pt-6 text-[15px] transition-colors">{{ faq.respuesta }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- OTROS SERVICIOS RELACIONADOS -->
            <section class="py-24 bg-slate-900/20 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Explora más <span class="text-[var(--color-primary)]">Servicios</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Link 
                            v-for="(servicio, i) in otrosServicios" 
                            :key="i"
                            :href="servicio.link"
                            class="group p-8 bg-black/50 dark:bg-black/50 rounded-3xl border border-slate-100 dark:border-slate-700 hover:shadow-2xl hover:border-[var(--color-primary)]/30 hover:shadow-xl hover:shadow-xl transition-all duration-500"
                        >
                            <span class="text-4xl mb-6 block text-[var(--color-primary)] group-hover:scale-105 transition-transform">
                                <font-awesome-icon :icon="servicio.icono" />
                            </span>
                            <h3 class="text-xl font-black text-slate-100 text-slate-100 mb-3 group-hover:text-[var(--color-primary)] transition-colors">{{ servicio.titulo }}</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed mb-4 transition-colors">{{ servicio.desc }}</p>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <WhatsAppWidget :telefono="empresa?.whatsapp" />
        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
