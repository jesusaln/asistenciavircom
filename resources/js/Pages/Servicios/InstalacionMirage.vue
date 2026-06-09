<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import PricingSection from '@/Components/Public/PricingSection.vue';
import MirageTrustSection from '@/Components/Public/MirageTrustSection.vue';
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
    return `https://wa.me/${phone}?text=${encodeURIComponent('Hola, tengo una instalación sin costo de Mirage y quiero agendar mi cita.')}`;
});

const incluye = [
    { icono: 'circle-check', titulo: 'Mano de Obra', desc: 'Instalación básica de unidad interior (evaporador) y unidad exterior (condensador).' },
    { icono: 'ruler-combined', titulo: 'Kit de Instalación', desc: 'Hasta 4 metros de tubería de cobre y aislamiento térmico (incluidos en la caja del equipo).' },
    { icono: 'plug', titulo: 'Interconexión', desc: 'Conexión de señales entre unidades (solo con el cable incluido en el kit de fábrica).' },
    { icono: 'hammer', titulo: 'Perforación Estándar', desc: 'Un orificio de hasta 4" en pared de ladrillo o block para el paso de tuberías.' },
    { icono: 'droplet', titulo: 'Drenaje', desc: 'Manguera de desagüe básica para la unidad interior.' },
    { icono: 'rocket', titulo: 'Puesta en Marcha', desc: 'Pruebas de funcionamiento, medición de presiones y demostración del control remoto.' },
];

const noIncluye = [
    { icono: 'circle-xmark', titulo: 'Soportes / Ménsulas', desc: 'Bases metálicas para fijar la unidad exterior a la pared.' },
    { icono: 'bolt', titulo: 'Alimentación Eléctrica', desc: 'No se incluye cable de alimentación, centros de carga ni pastillas térmicas.' },
    { icono: 'hard-hat', titulo: 'Perforaciones Especiales', desc: 'Perforaciones en concreto armado, piedra, mármol, cristales o piso techo.' },
    { icono: 'building', titulo: 'Desinstalación de equipo anterior en Segunda Planta', desc: 'La desinstalación de un equipo anterior ubicado en segunda planta genera un cargo adicional.' },
    { icono: 'circle-plus', titulo: 'Extensión de Líneas', desc: 'Metros adicionales de tubería o cable más allá de los 4 metros incluidos en tu kit.' },
    { icono: 'tools', titulo: 'Remociones', desc: 'Desinstalación de equipos anteriores para preparar el área.' },
];

const costosAdicionales = [
    { item: 'Juego de Ménsulas (Soportes + Instalación)', precio: '$950', icono: 'drafting-compass' },
    { item: 'Instalación de Ménsulas 1 Ton (Cliente ya las tiene)', precio: '$350', icono: 'wrench' },
    { item: 'Instalación de Ménsulas 1.5 Ton (Cliente ya las tiene)', precio: '$450', icono: 'wrench' },
    { item: 'Instalación de Ménsulas 2 Ton (Cliente ya las tiene)', precio: '$550', icono: 'wrench' },
    { item: 'Instalación de Ménsulas 3 Ton (Cliente ya las tiene)', precio: '$650', icono: 'wrench' },
    { item: 'Instalación Eléctrica (Cliente ya tiene material)', precio: '$350', icono: 'charging-station' },
    { item: 'Base de Piso Mirage Basic (Neopreno) - Ya Instalada', precio: '$500', icono: 'layer-group' },
    { item: 'Instalación de Líneas Pre-instaladas (Inc. Nipples)', precio: '$950', icono: 'link' },
    { item: 'Bomba de Condensado (Instalación Incluida)', precio: '$2,500', icono: 'water' },
    { item: 'Ranurado para Líneas Ocultas (Inc. Resane)', precio: '$1,650', icono: 'trowel' },
    { item: 'Cable Eléctrico 3x12 100% Cobre (por Metro)', precio: '$70', icono: 'plug' },
    { item: 'Pastilla Térmica (15, 20 o 30 Amp)', precio: '$500', icono: 'bolt' },
    { item: 'Centro de Carga (2 Polos + Instalación)', precio: '$300', icono: 'box' },
    { item: 'Desinstalación de Equipo Anterior', precio: '$500', icono: 'trash-alt' },
    { item: 'Desinstalación de equipo anterior en Segunda Planta', precio: '$650', icono: 'building' },
    { item: 'Instalación de Varilla Tierra Física (Mano de Obra)', precio: '$350', icono: 'plug' },
    { item: 'Varilla Tierra Física 1.5 metros incluye conector', precio: '$300', icono: 'hammer' },
    { item: 'Cable Tierra Calibre 8 Color Verde (metro) - Incluye Grapado', precio: '$65', icono: 'plug' },
    { item: 'Cable Tierra Calibre 12 (Directo para un solo equipo) - Metro - Incluye Grapado', precio: '$30', icono: 'plug' },
    { item: 'Tubería de PVC de 3/4" para desague (por metro) - Incluye Abrazaderas', precio: '$50', icono: 'droplet' },
    { item: 'Subir equipo condensador a segundo piso 1 ton', precio: '$150', icono: 'building' },
    { item: 'Subir equipo condensador a segundo piso 1.5 ton', precio: '$200', icono: 'building' },
    { item: 'Subir equipo condensador a segundo piso 2 ton', precio: '$250', icono: 'building' },
    { item: 'Subir equipo condensador a segundo piso 3 ton', precio: '$350', icono: 'building' },
];

const preparacion = [
    { titulo: 'Equipo en Sitio', desc: 'Asegúrate de tener las cajas del equipo en el lugar donde será la instalación.' },
    { titulo: 'Área Despejada', desc: 'Remueve muebles u objetos que puedan estorbar o dañarse durante la instalación.' },
    { titulo: 'Luz de 220V Lista', desc: 'Para probar tu equipo, es necesario contar con el centro de carga ya instalado.' },
    { titulo: 'Persona Responsable', desc: 'Debe estar presente un adulto para firmar la orden de servicio y validar el funcionamiento.' },
];

const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

const faqs = [
    { id: 1, pregunta: '¿Qué significa que sea "Sin Costo"?', respuesta: 'Significa que la mano de obra básica y el kit incluido en la caja son gratuitos. Mirage cubre estos conceptos a través de sus centros de servicio autorizados como nosotros.' },
    { id: 2, pregunta: '¿Por qué me cobran las ménsulas o la base?', respuesta: 'Las bases metálicas (ménsulas) no vienen dentro de la caja del equipo; son accesorios adicionales que dependen de dónde elijas colocar el equipo (pared o piso).' },
    { id: 3, pregunta: '¿Pueden dejar el equipo funcionando si no tengo la luz lista?', respuesta: 'Podemos instalar el equipo físicamente, pero no podemos realizar la puesta en marcha inicial si no hay energía eléctrica de 220V (o 110V según el modelo) en el punto de instalación.' },
    { id: 4, pregunta: '¿Qué necesito tener listo antes de que lleguen?', respuesta: 'Tener el equipo en el lugar (cajas cerradas), el área despejada y, de preferencia, la alimentación eléctrica ya instalada cerca de donde irá la unidad exterior.' },
    { id: 5, pregunta: '¿Siguen válidas las garantías?', respuesta: '¡Sí! Al ser Centro de Servicio Autorizado, tu garantía de fábrica queda totalmente protegida. Te entregamos un comprobante de instalación oficial.' },
    { id: 6, pregunta: '¿Por qué recomiendan varilla de tierra física para equipos Inverter?', respuesta: 'Los equipos con tecnología Inverter tienen tarjetas electrónicas muy sensibles. La tierra física ayuda a drenar excedentes de energía y picos de voltaje, protegiendo la vida de tu equipo y asegurando la garantía.' },
];
</script>

<template>
    <Head title="Instalación Mirage Sin Costo - Términos y Condiciones">
        <meta name="description" content="Detalle de lo que incluye y no incluye la promoción de instalación sin costo de Mirage con Climas del Desierto." />
    </Head>

    <div :style="cssVars" class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col font-sans">
        <PublicNavbar :empresa="empresa" activeTab="instalacion" />

        <main class="flex-grow">
            <!-- HERO SECTION - PREMIUM DESIGN -->
            <section class="relative pt-40 pb-32 px-4 overflow-hidden min-h-[90vh] flex items-center justify-center text-center">
                <!-- Background Image with Overlay -->
                <div class="absolute inset-0 z-0">
                    <img 
                        src="/images/servicios/tecnico-mirage.png" 
                        alt="Background" 
                        class="w-full h-full object-cover"
                    >
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-gray-900/30"></div>
                </div>
                
                <div class="max-w-7xl mx-auto relative z-10">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-[var(--color-primary)] rounded-full border border-white/20 mb-8 animate-bounce-slow">
                        <span class="text-white text-xs font-bold uppercase tracking-widest">Centro de Servicio Autorizado Mirage</span>
                    </div>

                    <h1 class="text-5xl md:text-7xl lg:text-8xl font-black text-white mb-6 tracking-tight leading-[1.1]">
                        Instalación <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Sin Costo</span>
                    </h1>

                    <p class="max-w-2xl mx-auto text-xl md:text-2xl text-gray-200 mb-12 leading-relaxed font-medium">
                        ¿Compraste tu equipo Mirage en una tienda departamental? <br>
                        Aquí te detallamos <strong class="text-white underline decoration-[var(--color-primary)] underline-offset-8">exactamente</strong> qué incluye tu beneficio y qué servicios adicionales podrías necesitar.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-6 justify-center">
                        <a 
                            :href="whatsappLink" 
                            target="_blank"
                            class="group px-12 py-6 bg-[var(--color-primary)] text-white rounded-2xl font-black text-xl shadow-2xl shadow-[var(--color-primary)]/40 hover:scale-110 transition-all duration-300 flex items-center justify-center gap-3"
                        >
                            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Agendar mi Instalación
                        </a>
                        <div class="px-8 py-4 bg-white/10 backdrop-blur-md rounded-2xl flex items-center gap-4 border border-white/20">
                            <div class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center text-white font-black">
                                🎖️
                            </div>
                            <div class="text-left">
                                <p class="text-white font-black">Garantía Certificada</p>
                                <p class="text-white/60 text-xs uppercase tracking-widest font-bold">Respaldo Oficial Mirage</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN DE PREPARACIÓN (NUEVA) -->
            <section class="py-20 bg-gray-50 dark:bg-gray-950">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-indigo-500 text-xs font-black uppercase tracking-[0.3em] mb-3 block">Antes de nuestra llegada</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight">
                            ¿Cómo <span class="text-indigo-500">prepararte</span>?
                        </h2>
                    </div>
                    
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div v-for="(step, i) in preparacion" :key="i" class="bg-white dark:bg-gray-900 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all group">
                            <div class="w-12 h-12 bg-indigo-500/10 text-indigo-500 rounded-xl flex items-center justify-center font-black text-xl mb-6 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                                {{ i + 1 }}
                            </div>
                            <h3 class="font-black text-gray-900 dark:text-white mb-3 text-lg leading-tight">{{ step.titulo }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTENIDO DETALLADO - COMPARATIVA -->
            <section class="py-20 bg-white dark:bg-gray-900 border-y border-gray-100 dark:border-gray-800">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="grid lg:grid-cols-2 gap-12">
                        <!-- COLUMNA INCLUYE (VERDE) -->
                        <div class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded-[3rem] p-8 md:p-12 border border-emerald-100 dark:border-emerald-900/30">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-16 h-16 bg-emerald-500 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-emerald-500/20">
                                    ✅
                                </div>
                                <div>
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Sí Incluye</h2>
                                    <p class="text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-widest text-xs">Conceptos Sin Costo</p>
                                </div>
                            </div>

                            <div class="space-y-8">
                                <div v-for="(item, i) in incluye" :key="i" class="flex gap-5 group">
                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-xl shadow-sm border border-emerald-100 dark:border-emerald-800 shrink-0 group-hover:scale-110 transition-transform">
                                        <font-awesome-icon :icon="item.icono" class="text-emerald-500" />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-gray-900 dark:text-white mb-1">{{ item.titulo }}</h3>
                                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ item.desc }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-12 p-6 bg-emerald-500/10 rounded-2xl border border-emerald-500/20">
                                <p class="text-emerald-700 dark:text-emerald-400 text-sm font-bold">
                                    💡 Nota: El kit de instalación de 4 metros es el que viene sellado dentro de la caja de tu equipo nuevo.
                                </p>
                            </div>
                        </div>

                        <!-- COLUMNA NO INCLUYE (ROJO) -->
                        <div class="bg-rose-50/50 dark:bg-rose-900/10 rounded-[3rem] p-8 md:p-12 border border-rose-100 dark:border-rose-900/30">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-16 h-16 bg-rose-500 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-rose-500/20">
                                    ❌
                                </div>
                                <div>
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">No Incluye</h2>
                                    <p class="text-rose-600 dark:text-rose-400 font-bold uppercase tracking-widest text-xs">Cargos Adicionales</p>
                                </div>
                            </div>

                            <div class="space-y-8">
                                <div v-for="(item, i) in noIncluye" :key="i" class="flex gap-5 group">
                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-xl shadow-sm border border-rose-100 dark:border-rose-800 shrink-0 group-hover:scale-110 transition-transform">
                                        <font-awesome-icon :icon="item.icono" class="text-rose-500" />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-gray-900 dark:text-white mb-1">{{ item.titulo }}</h3>
                                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ item.desc }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-12 p-6 bg-rose-500/10 rounded-2xl border border-rose-500/20">
                                <p class="text-rose-700 dark:text-rose-400 text-sm font-bold">
                                    ⚠️ Importante: Si tu instalación requiere metros extra o materiales especiales, el técnico te cotizará antes de iniciar.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TABLA DE PRECIOS ESTIMADOS -->
            <section class="py-24 bg-gray-50 dark:bg-gray-950">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Transparencia Total</span>
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight">
                            Posibles <span class="text-[var(--color-primary)]">Costos Extras</span>
                        </h2>
                        <p class="text-gray-500 dark:text-gray-400 mt-4 max-w-lg mx-auto">Si tu ubicación requiere material adicional o accesorios, estos son los conceptos más comunes:</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-900 text-white">
                                    <th class="px-8 py-6 font-black uppercase tracking-widest text-xs">Servicio / Accesorio</th>
                                    <th class="px-8 py-6 font-black uppercase tracking-widest text-xs text-right">Inversión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(costo, i) in costosAdicionales" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[var(--color-primary)] group-hover:scale-110 group-hover:bg-[var(--color-primary-soft)] transition-all">
                                                <font-awesome-icon :icon="costo.icono" />
                                            </div>
                                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ costo.item }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 text-sm font-black group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all duration-300">
                                            {{ costo.precio }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- TRUST SECTION -->
            <MirageTrustSection />

            <!-- PRICING SECTION -->
            <PricingSection :whatsappLink="whatsappLink" />

            <!-- FAQ SECTION -->
            <section class="py-24 bg-white dark:bg-gray-900">
                <div class="max-w-4xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight">Preguntas <span class="text-[var(--color-primary)]">Frecuentes</span></h2>
                    </div>

                    <div class="space-y-4">
                        <div 
                            v-for="faq in faqs" 
                            :key="faq.id"
                            class="bg-gray-50 dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group"
                        >
                            <button 
                                @click="toggleFaq(faq.id)"
                                class="w-full px-8 py-7 flex items-center justify-between text-left"
                            >
                                <span class="font-black text-gray-900 dark:text-white group-hover:text-[var(--color-primary)] transition-colors text-lg pr-4">{{ faq.pregunta }}</span>
                                <span class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-400 transition-all duration-300 shrink-0" :class="{'rotate-180 bg-[var(--color-primary)] text-white': activeFaq === faq.id}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div v-if="activeFaq === faq.id" class="px-8 pb-8 pt-2 animate-fade-in">
                                <p class="text-gray-500 dark:text-gray-400 font-medium leading-relaxed border-t border-gray-200 dark:border-gray-700 pt-6 text-[15px] transition-colors">{{ faq.respuesta }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA FINAL -->
            <section class="py-20 bg-[var(--color-primary)] relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-[100px]"></div>
                
                <div class="max-w-4xl mx-auto px-4 relative z-10 text-center">
                    <h2 class="text-4xl md:text-5xl font-black text-white mb-8 tracking-tight">¿Listo para estrenar tu Mirage?</h2>
                    <p class="text-white/80 text-xl font-medium mb-12">Agenda hoy mismo y disfruta de la comodidad garantizada por expertos certificados.</p>
                    
                    <a 
                        :href="whatsappLink" 
                        target="_blank"
                        class="inline-flex items-center gap-4 px-12 py-6 bg-white text-[var(--color-primary)] rounded-[2rem] font-black text-xl hover:scale-105 transition-all shadow-2xl shadow-black/20"
                    >
                        Contactar vía WhatsApp 
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
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
.animate-bounce-slow {
    animation: bounce 3s infinite;
}
@keyframes bounce {
    0%, 100% { transform: translateY(-5%); animation-timing-function: cubic-bezier(0.8, 0, 1, 1); }
    50% { transform: translateY(0); animation-timing-function: cubic-bezier(0, 0, 0.2, 1); }
}
.animate-slide-up {
    animation: slideUp 0.6s ease-out both;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
