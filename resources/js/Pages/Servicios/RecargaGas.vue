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
    return `https://wa.me/${phone}?text=${encodeURIComponent('Hola, mi minisplit dejó de enfriar y creo que necesita una recarga de gas. ¿Me podrían dar información sobre el costo?')}`;
});

const caracteristicas = [
    { icono: '⚖️', titulo: 'Carga por Peso', desc: 'Utilizamos básculas digitales para cargar la cantidad exacta especificada por el fabricante Mirage.' },
    { icono: '🔍', titulo: 'Detección de Fugas', desc: 'Realizamos una inspección visual y con jabón para identificar el origen de la pérdida de gas.' },
    { icono: '🌡️', titulo: 'Gases Ecológicos', desc: 'Trabajamos con R-410A y nuevos refrigerantes que no dañan la capa de ozono.' },
    { icono: '📉', titulo: 'Pruebas de Presión', desc: 'Monitoreamos las presiones de succión y descarga para asegurar un ciclo térmico óptimo.' },
    { icono: '🔌', titulo: 'Revisión Eléctrica', desc: 'Verificamos que el capacitor y el compresor trabajen adecuadamente con la nueva carga.' },
    { icono: '💎', titulo: 'Sellado Certificado', desc: 'Certificamos el sellado con nitrógeno frente a usted, asegurando un sistema libre de fugas al momento de la entrega.' },
];

const proceso = [
    { paso: '01', titulo: 'Vacio Técnico', desc: 'Eliminamos humedad del sistema para prevenir corrosión interna y mal rendimiento.', icono: '🕳️' },
    { paso: '02', titulo: 'Identificación', desc: 'Buscamos rastros de aceite que indiquen fugas en tuercas o soldaduras.', icono: '👁️' },
    { paso: '03', titulo: 'Recarga', desc: 'Inyectamos el refrigerante de forma gradual monitoreando el amperaje del equipo.', icono: '🧪' },
    { paso: '04', titulo: 'Validación', desc: 'Medición de la temperatura de salida en rejilla (Salto Térmico) para confirmar eficiencia.', icono: '🌡️' },
];

const otrosServicios = [
    { 
        titulo: 'Mantenimiento Preventivo', 
        desc: 'Limpieza profunda y revisión técnica para evitar pérdidas de eficiencia.', 
        icono: '⚙️',
        link: route('public.mantenimiento'),
    },
    { 
        titulo: 'Reparación Técnica', 
        desc: 'Solución integral a fallas de motor, tarjetas electrónicas o sensores.', 
        icono: '🔧',
        link: route('public.reparacion'),
    },
    { 
        titulo: 'Instalación Profesional', 
        desc: 'Montaje de equipos nuevos con garantía de fábrica y acabados estéticos.', 
        icono: '🏛️',
        link: route('public.instalacion'),
    },
    { 
        titulo: 'Pólizas de Servicio', 
        desc: 'Cobertura anual para hogares y empresas con visitas de revisión incluidas.', 
        icono: '📄',
        link: route('catalogo.polizas'),
    },
];

const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

const faqs = [
    { id: 1, pregunta: '¿Por qué mi minisplit perdió el gas?', respuesta: 'Un minisplit es un sistema sellado; si falta gas, es porque existe una fuga. Puede ser por vibraciones que aflojan tuercas, corrosión en el serpentín o una mala instalación previa.' },
    { id: 2, pregunta: '¿Pueden solo llegar y echarle gas?', respuesta: 'Podemos hacerlo, pero si no se tapa la fuga, el gas se volverá a salir. Siempre recomendamos identificar y sellar la fuga antes de realizar la recarga para que tu inversión valga la pena.' },
    { id: 3, pregunta: '¿Qué tipo de gas usa mi equipo?', respuesta: 'La mayoría de los minisplits modernos (Inverter) usan R-410A. Equipos de más de 10 años pueden usar R-22. Puedes revisar la etiqueta en la unidad exterior.' },
    { id: 4, pregunta: '¿Es normal que se le acabe el gas cada año?', respuesta: 'No, un minisplit bien instalado no debería necesitar recargas nunca. Si necesitas gas cada año, tienes una fuga que debe ser reparada profesionalmente.' },
    { id: 5, pregunta: '¿Cuánto cuesta el kilo de gas?', respuesta: 'El costo varía según el tipo de gas. Sin embargo, nuestro servicio se cotiza por carga completa o ajuste de presión, incluyendo la mano de obra técnica.' },
];
</script>

<template>
    <Head title="Recarga de Gas Refrigerante - Aire Acondicionado Hermosillo">
        <meta name="description" :content="`Recarga de gas refrigerante para minisplit Mirage y todas las marcas en ${empresa?.ciudad || 'Hermosillo'}. Detección de fugas y diagnóstico de enfriamiento.`" />
    </Head>

    <div :style="cssVars" class="min-h-screen bg-white dark:bg-gray-900 flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" activeTab="gas" />

        <main class="flex-grow">
            <!-- HERO SECTION -->
            <section class="relative min-h-[80vh] flex items-center bg-gray-900 overflow-hidden">
                <!-- Background Image -->
                <div class="absolute inset-0">
                    <img 
                        src="/storage/servicios/recarga-gas-hero.webp" 
                        alt="Recarga de Gas Refrigerante" 
                        class="w-full h-full object-cover opacity-30"
                    >
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-transparent"></div>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute top-20 right-10 w-72 h-72 bg-[var(--color-primary)] rounded-full blur-[150px] opacity-20"></div>
                <div class="absolute bottom-10 left-1/4 w-48 h-48 bg-blue-500 rounded-full blur-[100px] opacity-10"></div>

                <!-- Content -->
                <div class="relative z-10 max-w-7xl mx-auto px-4 py-20 w-full">
                    <div class="max-w-2xl">
                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/10 mb-8">
                            <span class="text-blue-400 text-xs font-bold uppercase tracking-widest">Recupera el Frío Original</span>
                        </div>

                        <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight leading-[0.9]">
                            Recarga de<br>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Gas Refri</span>
                        </h1>

                        <p class="text-xl text-gray-300 mb-10 leading-relaxed max-w-lg">
                            ¿Tu aire tira aire pero no enfría? Probablemente le falta gas. <strong class="text-white">Detectamos fugas</strong> y recargamos con precisión milimétrica.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <a 
                                :href="whatsappLink" 
                                target="_blank"
                                class="group px-8 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/30 hover:shadow-2xl hover:shadow-[var(--color-primary)]/40 hover:scale-105 transition-all duration-300 flex items-center justify-center gap-3"
                            >
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Solicitar Revisión de Gas
                            </a>
                            <a 
                                href="#agendar-cita"
                                class="px-8 py-5 border-2 border-white/20 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-white/10 transition-all duration-300 text-center"
                            >
                                Agendar Visita Hoy
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            <!-- TRUST SECTION -->
            <MirageTrustSection />

            <!-- PRICING SECTION -->
            <PricingSection :whatsappLink="whatsappLink" />

             <!-- SERVICIO TÉCNICO GAS -->
             <section class="py-24 bg-white dark:bg-gray-900 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Expertos en Refrigeración</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Carga de Gas con <span class="text-[var(--color-primary)]">Precisión</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="(feat, i) in caracteristicas" 
                            :key="i"
                            class="group p-10 rounded-3xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-[var(--color-primary)]/30 transition-all duration-500"
                        >
                            <span class="text-4xl mb-6 block group-hover:scale-110 transition-transform">{{ feat.icono }}</span>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3 transition-colors">{{ feat.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed transition-colors">{{ feat.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

             <!-- NUESTRO PROCESO -->
             <section class="py-24 bg-gray-50 dark:bg-gray-950 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Pasos</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Protocolo de <span class="text-[var(--color-primary)]">Recarga Segura</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div 
                            v-for="(step, i) in proceso" 
                            :key="i"
                            class="group relative bg-white dark:bg-gray-800 p-10 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none hover:-translate-y-2 transition-all duration-500 hover:border-[var(--color-primary)]"
                        >
                            <div class="w-16 h-16 bg-white dark:bg-gray-700 rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:bg-[var(--color-primary-soft)] transition-all shadow-sm">
                                {{ step.icono }}
                            </div>
                            <span class="absolute top-8 right-8 text-4xl font-black text-gray-100 dark:text-gray-700 select-none">{{ step.paso }}</span>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-4 transition-colors">{{ step.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed transition-colors">{{ step.desc }}</p>
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
            <section class="py-24 bg-white dark:bg-gray-900 transition-colors">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Preguntas sobre <span class="text-[var(--color-primary)]">Cargas de Gas</span>
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <div 
                            v-for="faq in faqs" 
                            :key="faq.id"
                            class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group"
                        >
                            <button 
                                @click="toggleFaq(faq.id)"
                                class="w-full px-8 py-7 flex items-center justify-between text-left"
                            >
                                <span class="font-black text-gray-900 dark:text-white group-hover:text-[var(--color-primary)] transition-colors text-lg line-clamp-1 pr-4">{{ faq.pregunta }}</span>
                                <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 transition-all duration-300 shrink-0" :class="{'rotate-180 bg-[var(--color-primary)] text-white': activeFaq === faq.id}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div v-if="activeFaq === faq.id" class="px-8 pb-8 pt-2 animate-fade-in">
                                <p class="text-gray-500 dark:text-gray-400 font-medium leading-relaxed border-t border-gray-50 dark:border-gray-700 pt-6 text-[15px] transition-colors">{{ faq.respuesta }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- OTROS SERVICIOS RELACIONADOS -->
            <section class="py-24 bg-gray-50 dark:bg-gray-950 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Servicios <span class="text-[var(--color-primary)]">Complementarios</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Link 
                            v-for="(servicio, i) in otrosServicios" 
                            :key="i"
                            :href="servicio.link"
                            class="group p-8 bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:border-[var(--color-primary)]/30 hover:-translate-y-2 transition-all duration-500"
                        >
                            <span class="text-4xl mb-6 block group-hover:scale-110 transition-transform">{{ servicio.icono }}</span>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3 group-hover:text-[var(--color-primary)] transition-colors">{{ servicio.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-4 transition-colors">{{ servicio.desc }}</p>
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
