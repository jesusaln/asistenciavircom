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
    return `https://wa.me/${phone}?text=${encodeURIComponent('Hola, necesito un servicio de reparación de minisplit. ¿Podrían ayudarme?')}`;
});

const problemas = [
    { icono: '🥶', titulo: 'No enfría', desc: 'Tu minisplit enciende pero no baja la temperatura o tarda mucho en enfriar la habitación.' },
    { icono: '💧', titulo: 'Tira agua', desc: 'Goteo interior por drenaje obstruido, manguera rota o filtros saturados de suciedad.' },
    { icono: '🔊', titulo: 'Hace ruido', desc: 'Vibraciones, chirridos o golpes anormales del ventilador o compresor del equipo.' },
    { icono: '⚡', titulo: 'No enciende', desc: 'El equipo no responde al control, se apaga solo o salta el breaker eléctrico.' },
    { icono: '🌬️', titulo: 'Mal olor', desc: 'Olores desagradables por hongos, bacterias o acumulación de suciedad en serpentines.' },
    { icono: '❄️', titulo: 'Se congela', desc: 'Formación de hielo en las tuberías o evaporador por fuga de gas refrigerante.' },
];

const proceso = [
    { paso: '01', titulo: 'Diagnóstico', desc: 'Evaluamos tu equipo para identificar la falla exacta con herramientas especializadas.', icono: '🔍' },
    { paso: '02', titulo: 'Cotización', desc: 'Te damos un presupuesto transparente y sin sorpresas antes de iniciar cualquier trabajo.', icono: '📋' },
    { paso: '03', titulo: 'Reparación', desc: 'Nuestros técnicos certificados realizan la reparación con refacciones de calidad.', icono: '🔧' },
    { paso: '04', titulo: 'Sellado Certificado', desc: 'Pruebas de hermeticidad con nitrógeno para asegurar que no existan fugas al entregar.', icono: '🛡️' },
];

const otrosServicios = [
    { 
        titulo: 'Instalación de Minisplit', 
        desc: 'Instalación profesional con materiales de primera calidad y garantía total.', 
        icono: '🏗️',
        link: '/tienda',
    },
    { 
        titulo: 'Mantenimiento Preventivo', 
        desc: 'Limpieza profunda, revisión de gas y componentes eléctricos cada 6 meses.', 
        icono: '⚙️',
        link: '/polizas',
    },
    { 
        titulo: 'Pólizas de Servicio', 
        desc: 'Planes de mantenimiento con prioridad en visitas y descuentos en refacciones.', 
        icono: '📄',
        link: '/polizas',
    },
    { 
        titulo: 'Venta de Equipos', 
        desc: 'Minisplits Mirage Inverter con la mejor relación precio-calidad del mercado.', 
        icono: '❄️',
        link: '/tienda',
    },
];

const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

const faqs = [
    { id: 1, pregunta: '¿Cuánto cuesta una reparación de minisplit?', respuesta: 'El costo depende del tipo de falla. Un diagnóstico tiene un costo accesible que se descuenta si decides reparar con nosotros. Las reparaciones más comunes van desde $500 hasta $3,500 MXN, incluyendo mano de obra y refacciones.' },
    { id: 2, pregunta: '¿Cuánto tiempo tarda una reparación?', respuesta: 'La mayoría de las reparaciones se completan el mismo día. En casos que requieren refacciones especiales, puede tomar 1-3 días hábiles adicionales.' },
    { id: 3, pregunta: '¿Reparan todas las marcas de minisplit?', respuesta: 'Sí, nuestros técnicos están capacitados para reparar todas las marcas: Mirage, Carrier, LG, Samsung, Mabe, Hisense, Daikin, entre otras. Somos Centro de Servicio Autorizado Mirage.' },
    { id: 4, pregunta: '¿Ofrecen servicio de emergencia?', respuesta: 'Sí, contamos con servicio de emergencia para cuando tu equipo falla en temporada de calor extremo. Contáctanos por WhatsApp para atención prioritaria.' },
    { id: 5, pregunta: '¿Qué es el Sellado Certificado?', respuesta: 'Es nuestro protocolo de seguridad donde probamos la hermeticidad del sistema con nitrógeno a alta presión frente a usted. Esto garantiza que el equipo queda perfectamente sellado al momento de la entrega, protegiendo su inversión.' },
];
</script>

<template>
    <Head title="Reparación de Minisplit - Servicio Técnico Profesional">
        <meta name="description" :content="`Servicio de reparación de minisplit en ${empresa?.ciudad || 'Hermosillo'}, Sonora. Diagnóstico profesional, reparación de todas las marcas. Centro de Servicio Autorizado Mirage. Garantía por escrito.`" />
        <meta property="og:title" content="Reparación de Minisplit - Climas del Desierto" />
        <meta property="og:description" content="¿Tu minisplit no enfría, tira agua o hace ruido? Somos expertos en reparación de aires acondicionados en Hermosillo, Sonora." />
        <meta property="og:type" content="website" />
    </Head>

    <div :style="cssVars" class="min-h-screen bg-white dark:bg-gray-900 flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" activeTab="reparacion" />

        <main class="flex-grow">
            <!-- HERO SECTION -->
            <section class="relative min-h-[80vh] flex items-center bg-gray-900 overflow-hidden">
                <!-- Background Image -->
                <div class="absolute inset-0">
                    <img 
                        src="/storage/servicios/reparacion-minisplit-hero.webp" 
                        alt="Reparación de Minisplit" 
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
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-green-400 text-xs font-bold uppercase tracking-widest">Servicio Disponible Hoy</span>
                        </div>

                        <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight leading-[0.9]">
                            Reparación de
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Minisplit</span>
                        </h1>

                        <p class="text-xl text-gray-300 mb-10 leading-relaxed max-w-lg">
                            ¿Tu aire no enfría, tira agua o hace ruido? Nuestros técnicos certificados reparan <strong class="text-white">todas las marcas</strong> con garantía por escrito.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <a 
                                :href="whatsappLink" 
                                target="_blank"
                                class="group px-8 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/30 hover:shadow-2xl hover:shadow-[var(--color-primary)]/40 hover:scale-105 transition-all duration-300 flex items-center justify-center gap-3"
                            >
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Solicitar Reparación
                            </a>
                            <a 
                                href="#agendar-cita"
                                class="px-8 py-5 border-2 border-white/20 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-white/10 transition-all duration-300 text-center"
                            >
                                Agendar Visita Técnica
                            </a>
                        </div>

                        <!-- Stats -->
                        <div class="flex gap-8 mt-12 pt-8 border-t border-white/10">
                            <div>
                                <p class="text-3xl font-black text-[var(--color-primary)]">500+</p>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Reparaciones / Año</p>
                            </div>
                            <div>
                                <p class="text-3xl font-black text-white">98%</p>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Clientes Satisfechos</p>
                            </div>
                            <div>
                                <p class="text-3xl font-black text-white">90 días</p>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Garantía Mínima</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TRUST SECTION -->
            <MirageTrustSection />

            <!-- PRICING SECTION -->
            <PricingSection :whatsappLink="whatsappLink" />

            <!-- PROBLEMAS COMUNES -->
            <section class="py-24 bg-white dark:bg-gray-900 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Problemas Comunes</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            ¿Tu minisplit presenta <span class="text-[var(--color-primary)]">alguno de estos síntomas</span>?
                        </h2>
                        <p class="mt-4 text-gray-500 dark:text-gray-400 max-w-2xl mx-auto transition-colors">
                            No te preocupes, todos tienen solución. Nuestros técnicos los resuelven a diario.
                        </p>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="(problema, i) in problemas" 
                            :key="i"
                            class="group p-8 rounded-3xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-[var(--color-primary)]/30 hover:-translate-y-1 transition-all duration-500"
                        >
                            <span class="text-5xl mb-6 block group-hover:scale-110 transition-transform">{{ problema.icono }}</span>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3 transition-colors">{{ problema.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed transition-colors">{{ problema.desc }}</p>
                        </div>
                    </div>

                    <div class="text-center mt-12">
                        <a 
                            :href="whatsappLink" 
                            target="_blank"
                            class="inline-flex items-center gap-3 px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-bold text-sm uppercase tracking-widest shadow-lg hover:shadow-xl hover:scale-105 transition-all"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            ¿Tienes otro problema? Escríbenos
                        </a>
                    </div>
                </div>
            </section>

            <!-- NUESTRO PROCESO -->
            <section class="py-24 bg-gray-50 dark:bg-gray-950 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Así Trabajamos</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Proceso de <span class="text-[var(--color-primary)]">Reparación</span>
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div 
                            v-for="(step, i) in proceso" 
                            :key="i"
                            class="group relative bg-white dark:bg-gray-800 p-10 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none hover:-translate-y-2 transition-all duration-500 hover:border-[var(--color-primary)]"
                        >
                            <div class="w-20 h-20 bg-white dark:bg-gray-700 rounded-3xl flex items-center justify-center text-4xl mb-8 group-hover:scale-110 group-hover:bg-[var(--color-primary-soft)] transition-all shadow-sm">
                                {{ step.icono }}
                            </div>
                            <span class="absolute -top-4 -right-2 text-7xl font-black text-gray-50 dark:text-gray-700/50 opacity-0 group-hover:opacity-100 transition-opacity select-none">{{ step.paso }}</span>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-4 transition-colors">{{ step.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed transition-colors">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- POR QUÉ ELEGIRNOS -->
            <section class="py-24 bg-gray-900 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[200px] opacity-10"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500 rounded-full blur-[150px] opacity-10"></div>

                <div class="max-w-7xl mx-auto px-4 relative z-10">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Ventajas</span>
                        <h2 class="text-3xl md:text-5xl font-black tracking-tight">
                            ¿Por qué <span class="text-[var(--color-primary)]">Climas del Desierto</span>?
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="(v, i) in [
                            { icono: '🎓', titulo: 'Técnicos Certificados', desc: 'Personal capacitado en todas las marcas del mercado. Centro de Servicio Autorizado Mirage.' },
                            { icono: '⏱️', titulo: 'Respuesta Rápida', desc: 'Agenda tu cita hoy y recibe atención en menos de 24 horas. Servicio de emergencia disponible.' },
                            { icono: '🛡️', titulo: 'Garantía por Escrito', desc: '90 días de garantía en mano de obra y refacciones. Respaldamos nuestro trabajo al 100%.' },
                            { icono: '💰', titulo: 'Precios Transparentes', desc: 'Cotización sin compromiso antes de iniciar. Sin costos ocultos ni sorpresas en tu factura.' },
                            { icono: '🔧', titulo: 'Refacciones Originales', desc: 'Utilizamos repuestos de calidad original para asegurar la durabilidad de cada reparación.' },
                            { icono: '📍', titulo: 'Cobertura Local', desc: 'Servicio a domicilio en Hermosillo y zonas aledañas. Conocemos el clima extremo de Sonora.' },
                        ]" :key="i" class="bg-white/5 backdrop-blur-sm p-8 rounded-3xl border border-white/10 hover:border-[var(--color-primary)]/40 hover:bg-white/10 transition-all duration-500 group">
                            <span class="text-4xl mb-6 block group-hover:scale-110 transition-transform">{{ v.icono }}</span>
                            <h3 class="text-xl font-black mb-3">{{ v.titulo }}</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">{{ v.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FORMULARIO DE CITA -->
            <section id="agendar-cita">
                <QuickAppointmentForm 
                    :empresa="empresa" 
                    initialService="reparacion"
                    :isSimplified="true"
                />
            </section>

            <!-- PREGUNTAS FRECUENTES -->
            <section class="py-24 bg-white dark:bg-gray-900 transition-colors">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Dudas Frecuentes</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Preguntas sobre <span class="text-[var(--color-primary)]">Reparaciones</span>
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <div 
                            v-for="faq in faqs" 
                            :key="faq.id"
                            class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group"
                            :class="{'ring-2 ring-[var(--color-primary-soft)]': activeFaq === faq.id}"
                        >
                            <button 
                                @click="toggleFaq(faq.id)"
                                class="w-full px-8 py-7 flex items-center justify-between text-left"
                            >
                                <span class="font-black text-gray-900 dark:text-white group-hover:text-[var(--color-primary)] transition-colors text-lg leading-snug pr-4">{{ faq.pregunta }}</span>
                                <span class="w-10 h-10 rounded-xl bg-white dark:bg-gray-700 flex items-center justify-center text-gray-400 transition-all duration-500 shrink-0" :class="{'rotate-180 !bg-[var(--color-primary)] !text-white shadow-lg': activeFaq === faq.id}">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <Transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="transform -translate-y-4 opacity-0"
                                enter-to-class="transform translate-y-0 opacity-100"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="transform translate-y-0 opacity-100"
                                leave-to-class="transform -translate-y-4 opacity-0"
                            >
                                <div v-if="activeFaq === faq.id" class="px-8 pb-8 pt-2">
                                    <div class="pr-8">
                                        <p class="text-gray-500 dark:text-gray-400 font-medium leading-relaxed border-t border-gray-50 dark:border-gray-700 pt-6 text-[15px] transition-colors">{{ faq.respuesta }}</p>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </section>

            <!-- OTROS SERVICIOS RELACIONADOS -->
            <section class="py-24 bg-gray-50 dark:bg-gray-950 transition-colors">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Más Servicios</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">
                            Servicios <span class="text-[var(--color-primary)]">Relacionados</span>
                        </h2>
                        <p class="mt-4 text-gray-500 dark:text-gray-400 max-w-2xl mx-auto transition-colors">
                            Complementa tu reparación con nuestros servicios adicionales y mantén tu equipo en óptimas condiciones.
                        </p>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Link 
                            v-for="(servicio, i) in otrosServicios" 
                            :key="i"
                            :href="servicio.link"
                            class="group p-8 bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:border-[var(--color-primary)]/30 hover:-translate-y-2 transition-all duration-500"
                        >
                            <span class="text-5xl mb-6 block group-hover:scale-110 transition-transform">{{ servicio.icono }}</span>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3 group-hover:text-[var(--color-primary)] transition-colors">{{ servicio.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-4 transition-colors">{{ servicio.desc }}</p>
                            <span class="inline-flex items-center text-[var(--color-primary)] text-sm font-bold">
                                Ver más
                                <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- CTA FINAL -->
            <section class="py-20 bg-gradient-to-br from-[var(--color-primary)] to-amber-600 text-white relative overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-0 left-1/4 w-96 h-96 bg-white rounded-full blur-[150px]"></div>
                    <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-white rounded-full blur-[100px]"></div>
                </div>

                <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
                    <h2 class="text-3xl md:text-5xl font-black mb-6 tracking-tight">
                        ¿Tu minisplit necesita reparación?
                    </h2>
                    <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
                        Contáctanos ahora y recibe atención el mismo día. No dejes que el calor de Sonora te gane.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a 
                            :href="whatsappLink" 
                            target="_blank"
                            class="px-10 py-5 bg-white text-gray-900 rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-2xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-3"
                        >
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp Directo
                        </a>
                        <a 
                            href="tel:+526621234567"
                            class="px-10 py-5 border-2 border-white/30 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-white/10 transition-all duration-300 flex items-center justify-center gap-3"
                        >
                            📞 Llamar Ahora
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <WhatsAppWidget :telefono="empresa?.whatsapp" />
        <PublicFooter :empresa="empresa" />
    </div>
</template>
