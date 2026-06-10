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
    return `https://wa.me/${phone}?text=${encodeURIComponent('Hola, me interesa agendar la instalación de un minisplit. ¿Qué precio tiene el servicio básico?')}`;
});

const caracteristicas = [
    { icono: '📏', titulo: 'Nivelación Precisa', desc: 'Aseguramos una inclinación perfecta para el drenaje, evitando goteos y ruidos molestos.' },
    { icono: '🔌', titulo: 'Conexión Segura', desc: 'Instalaciones eléctricas bajo norma para proteger tu equipo contra variaciones de voltaje.' },
    { icono: '🛡️', titulo: 'Materiales Premium', desc: 'Utilizamos tubería de cobre de alta calidad y aislamiento térmico de primera.' },
    { icono: '💎', titulo: 'Acabados Estéticos', desc: 'Cuidamos la estética de tu hogar con canalización limpia y sellado profesional de perforaciones.' },
    { icono: '✅', titulo: 'Pruebas de Vacío', desc: 'Realizamos vacío al sistema para eliminar humedad y asegurar el máximo rendimiento del gas.' },
    { icono: '🔧', titulo: 'Herramienta Especializada', desc: 'Contamos con equipo de vanguardia para garantizar una instalación rápida y sin errores.' },
];

const proceso = [
    { paso: '01', titulo: 'Ubicación', desc: 'Asesoramos sobre el mejor lugar para colocar la unidad y optimizar el flujo de aire.', icono: '📍' },
    { paso: '02', titulo: 'Montaje', desc: 'Fijación segura del soporte interior y la unidad condensadora exterior.', icono: '🏗️' },
    { paso: '03', titulo: 'Interconexión', desc: 'Instalación de tuberías, cableado de señal y manguera de drenaje.', icono: '🔗' },
    { paso: '04', titulo: 'Puesta en Marcha', desc: 'Pruebas de presión, carga de gas y demostración de las funciones del equipo.', icono: '🚀' },
];

const otrosServicios = [
    { 
        titulo: 'Mantenimiento Preventivo', 
        desc: 'Limpieza profunda y revisión técnica para mantener tu garantía vigente.', 
        icono: '⚙️',
        link: route('public.mantenimiento'),
    },
    { 
        titulo: 'Reparación Técnica', 
        desc: 'Solución a fallas de enfriamiento, ruidos o problemas eléctricos.', 
        icono: '🔧',
        link: route('public.reparacion'),
    },
    { 
        titulo: 'Venta de Equipos', 
        desc: 'Adquiere tu nuevo Mirage con nosotros y obtén precio especial en instalación.', 
        icono: '❄️',
        link: route('catalogo.index'),
    },
    { 
        titulo: 'Pólizas de Servicio', 
        desc: 'Tranquilidad total con mantenimientos programados y atención prioritaria.', 
        icono: '📄',
        link: route('catalogo.polizas'),
    },
];

const costosAdicionales = [
    { item: 'Cable calibre 12 3x12 uso rudo (por metro) - Ya instalado', precio: '$70', icono: 'plug' },
    { item: 'Metro lineal de cable para tierra física - Ya instalado', precio: '$30', icono: 'plug' },
    { item: 'Térmico doble de 15 o 20 amperes - Ya instalado', precio: '$500', icono: 'bolt' },
    { item: 'Centro de carga de 2 polos con 2 conectores uso rudo (Instalación incluida)', precio: '$300', icono: 'box' },
    { item: 'Varilla de tierra de cobre', precio: '$300', icono: 'hammer' },
    { item: 'Instalación de varilla de tierra', precio: '$350', icono: 'hammer' },
    { item: 'Mano de obra para instalación eléctrica (Térmico, centro de carga y cable)', precio: '$350', icono: 'bolt' },
    { item: 'Retirar un equipo (Mano de obra incluida)', precio: '$500', icono: 'trash-alt' },
    { item: 'Desinstalación de un equipo para instalarse en otra ubicación del mismo domicilio (Mano de obra incluida)', precio: '$1500', icono: 'trash-alt' },
    { item: 'Soporte estabilizador de caucho para condensador a techo (Instalación incluida)', precio: '$500', icono: 'layer-group' },
    { item: 'Base para condensador a techo (Instalación incluida)', precio: '$950', icono: 'drafting-compass' },
    { item: 'Base para condensadora a pared (Instalación incluida)', precio: '$950', icono: 'drafting-compass' },
    { item: 'Instalación de base a pared proporcionada por el cliente (1 Ton)', precio: '$350', icono: 'hammer' },
    { item: 'Instalación de base a pared proporcionada por el cliente (2 Ton)', precio: '$450', icono: 'hammer' },
    { item: 'Instalación de base a pared proporcionada por el cliente (3 Ton)', precio: '$600', icono: 'hammer' },
    
    // Otros servicios
    { item: 'Bomba de Condensado (Instalación Incluida)', precio: '$2,500', icono: 'water' },
    { item: 'Ranurado para Líneas Ocultas (Inc. Resane)', precio: '$1,650', icono: 'trowel' },
    { item: 'Tubería de PVC de 3/4" para desagüe (por metro) - Ya instalada', precio: '$50', icono: 'droplet' },
    { item: 'Subir equipo condensador a segundo piso (Mano de obra)', precio: '$150', icono: 'building' },
];

const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

const faqs = [
    { id: 1, pregunta: '¿Qué incluye la instalación básica?', respuesta: 'Incluye montaje de ambas unidades, hasta 3 metros de tubería de cobre y cableado, perforación de pared estándar, material de fijación y puesta en marcha.' },
    { id: 2, pregunta: '¿Pueden instalar un equipo que compré en otra tienda?', respuesta: '¡Claro! Instalamos todas las marcas sin importar dónde las hayas adquirido, garantizando siempre el correcto funcionamiento.' },
    { id: 3, pregunta: '¿Pierdo la garantía si no lo instalan ustedes?', respuesta: 'Muchos fabricantes como Mirage exigen que la instalación sea realizada por personal certificado para validar la garantía de fábrica. Nosotros somos Centro de Servicio Autorizado.' },
    { id: 4, pregunta: '¿Cuánto tiempo tardan en instalar?', respuesta: 'Una instalación estándar en casa habitación toma entre 2 y 4 horas aproximadamente.' },
    { id: 5, pregunta: '¿Instalan en segundos pisos o lugares altos?', respuesta: 'Sí, contamos con equipo de seguridad y escaleras para instalaciones en lugares de difícil acceso. Solo infórmanos al agendar para llevar el equipo necesario.' },
];
</script>

<template>
    <Head title="Instalación Profesional de Minisplit - Climas del Desierto">
        <meta name="description" :content="`Instalación de minisplit en ${empresa?.ciudad || 'Hermosillo'} por técnicos certificados. Garantizamos eficiencia, estética y protección de tu inversión.`" />
    </Head>

    <div :style="cssVars" class="min-h-screen  flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" activeTab="instalacion" />

        <main class="flex-grow">
            <!-- HERO SECTION -->
            <section class="relative min-h-[80vh] flex items-center bg-slate-950 overflow-hidden">
                <!-- Background Image -->
                <div class="absolute inset-0">
                    <img 
                        src="/storage/servicios/instalacion-minisplit-hero.webp" 
                        alt="Instalación de Minisplit" 
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
                            <span class="text-brand-400 text-xs font-bold uppercase tracking-wide">Técnicos Certificados Mirage</span>
                        </div>

                        <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight leading-[0.9]">
                            Instalación<br>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Profesional</span>
                        </h1>

                        <p class="text-xl text-slate-300 mb-10 leading-relaxed max-w-lg">
                            Asegura el rendimiento de tu aire acondicionado desde el primer día. Instalaciones estéticas, <strong class="text-white">seguras y bajo norma</strong> de fábrica.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <a 
                                :href="whatsappLink" 
                                target="_blank"
                                class="group px-8 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-wide shadow-xl shadow-[var(--color-primary)]/30 hover:shadow-2xl hover:shadow-[var(--color-primary)]/40 hover:scale-105 transition-all duration-200 flex items-center justify-center gap-3"
                            >
                                <svg class="w-4 h-4 group-hover:scale-105 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Cotizar Instalación
                            </a>
                            <a 
                                href="#agendar-cita"
                                class="px-8 py-5 border-2 border-white/20 text-white rounded-2xl font-black text-sm uppercase tracking-wide hover:bg-white/10 transition-all duration-200 text-center"
                            >
                                Agendar Visita
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TRUST SECTION -->
            <MirageTrustSection />

            <!-- PRICING SECTION -->
            <PricingSection :whatsappLink="whatsappLink" />

            <!-- TABLA DE PRECIOS ESTIMADOS -->
            <section class="py-24 bg-slate-900/20">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Transparencia Total</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight">
                            Posibles <span class="text-[var(--color-primary)]">Costos Extras</span>
                        </h2>
                        <p class="text-slate-500 dark:text-slate-400 mt-4 max-w-lg mx-auto">
                            Todos los precios mostrados <strong>YA INCLUYEN la mano de obra de instalación</strong>. No tendrás que pagar nada adicional a otro técnico. Si tu ubicación requiere material adicional, estos son los conceptos más comunes:
                        </p>
                    </div>

                    <div class="bg-black/50 dark:bg-black/50 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr class="bg-slate-950 text-white">
                                    <th class="px-8 py-6 font-black uppercase tracking-wide text-xs">Servicio / Accesorio</th>
                                    <th class="px-8 py-6 font-black uppercase tracking-wide text-xs text-right">Inversión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr v-for="(costo, i) in costosAdicionales" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[var(--color-primary)] group-hover:scale-105 group-hover:bg-[var(--color-primary-soft)] transition-all">
                                                <font-awesome-icon :icon="costo.icono" />
                                            </div>
                                            <span class="font-bold text-slate-300 dark:text-slate-200">{{ costo.item }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span class="px-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-500 dark:text-slate-400 text-sm font-black group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all duration-200">
                                            {{ costo.precio }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-4 px-4 uppercase tracking-widest font-bold">
                        * Precios base en planta baja. En segundo nivel se agregan $200 por tonelada por el nivel de riesgo.
                    </p>
                </div>
            </section>

            <!-- ¿POR QUÉ NOSOTROS? -->
            <section class="py-24  transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Calidad Vircom</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Una Instalación que <span class="text-[var(--color-primary)]">Marca la Diferencia</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="(feat, i) in caracteristicas" 
                            :key="i"
                            class="group p-10 rounded-3xl bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-100 dark:border-slate-700 hover:shadow-xl hover:border-[var(--color-primary)]/30 transition-all duration-500"
                        >
                            <span class="text-4xl mb-6 block group-hover:scale-105 transition-transform">{{ feat.icono }}</span>
                            <h3 class="text-xl font-black text-slate-100 text-slate-100 mb-3 transition-colors">{{ feat.titulo }}</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed transition-colors">{{ feat.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- NUESTRO PROCESO -->
            <section class="py-24 bg-slate-900/20 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Pasos</span>
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Proceso de <span class="text-[var(--color-primary)]">Instalación</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div 
                            v-for="(step, i) in proceso" 
                            :key="i"
                            class="group relative bg-black/50 dark:bg-black/50 p-10 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none hover:shadow-xl hover:shadow-xl transition-all duration-500 hover:border-[var(--color-primary)]"
                        >
                            <div class="w-16 h-16 bg-black/50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:bg-[var(--color-primary-soft)] transition-all shadow-sm">
                                {{ step.icono }}
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
                    initialService="instalacion"
                    :isSimplified="true"
                />
            </section>

            <!-- PREGUNTAS FRECUENTES -->
            <section class="py-24  transition-colors">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-black text-slate-100 text-slate-100 tracking-tight transition-colors">
                            Dudas sobre <span class="text-[var(--color-primary)]">Instalaciones</span>
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
                            Más <span class="text-[var(--color-primary)]">Servicios</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Link 
                            v-for="(servicio, i) in otrosServicios" 
                            :key="i"
                            :href="servicio.link"
                            class="group p-8 bg-black/50 dark:bg-black/50 rounded-3xl border border-slate-100 dark:border-slate-700 hover:shadow-2xl hover:border-[var(--color-primary)]/30 hover:shadow-xl hover:shadow-xl transition-all duration-500"
                        >
                            <span class="text-4xl mb-6 block group-hover:scale-105 transition-transform">{{ servicio.icono }}</span>
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
