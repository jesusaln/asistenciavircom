<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    empresa: Object,
});

const page = usePage();

const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#FF6B35',
    '--color-primary-soft': (empresaData.value.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#B45309',
    '--color-terciary-soft': (empresaData.value.color_terciario || '#B45309') + '15',
}));

const activeFAQ = ref(null);
const faqs = [
    {
        pregunta: '¿Cuánto tiempo tardan en responder?',
        respuesta: 'Respondemos en menos de 2 horas en horario laboral. Para urgencias, contáctanos por WhatsApp para respuesta inmediata.',
    },
    {
        pregunta: '¿Hacen visitas a domicilio?',
        respuesta: 'Sí, realizamos visitas a domicilio en Hermosillo y alrededores. El costo de la visita se descuenta si aceptas la cotización.',
    },
    {
        pregunta: '¿Qué garantías ofrecen?',
        respuesta: 'Todos nuestros trabajos incluyen garantía: 1 año en mano de obra y garantía de fábrica en equipos Mirage (hasta 5 años en compresor).',
    },
    {
        pregunta: '¿Manejan servicio de emergencia?',
        respuesta: 'Sí, contamos con servicio de emergencia para empresas con póliza de mantenimiento. Contáctanos por WhatsApp para atención prioritaria.',
    },
];

const toggleFAQ = (index) => {
    activeFAQ.value = activeFAQ.value === index ? null : index;
};

const form = useForm({
    nombre: '',
    email: '',
    telefono: '',
    servicio: '',
    tipo_equipo: '',
    urgencia: 'normal',
    cp: '',
    mensaje: '',
});

const submit = () => {
    form.post(route('public.contacto.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const whatsappLink = computed(() => {
    const phone = empresaData.value.whatsapp?.replace(/\D/g, '') || '';
    return `https://wa.me/${phone}?text=Hola, necesito información sobre sus servicios de climatización.`;
});
</script>

<template>
    <Head :title="`Contacto - ${empresaData?.nombre || 'Climas del Desierto'}`">
        <meta name="description" :content="`Contáctanos en ${empresaData?.ciudad || 'Hermosillo'}. Servicios de climatización, mantenimiento e instalación. Teléfono: ${empresaData?.telefono}.`" />
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col font-sans transition-colors duration-300" :style="cssVars">
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre" />
        <PublicNavbar :empresa="empresaData" />

        <main class="flex-grow">
            <!-- Hero Compacto -->
            <section class="relative py-20 bg-gray-900 text-white overflow-hidden">
                <!-- Background Image -->
                <div class="absolute inset-0">
                    <img
                        src="/storage/servicios/contacto-hero.webp"
                        alt="Contacto"
                        class="w-full h-full object-cover opacity-20"
                    >
                    <div class="absolute inset-0 bg-gradient-to-b from-gray-900/80 via-gray-900/70 to-gray-900/95"></div>
                </div>

                <div class="absolute -top-24 -right-24 w-64 h-64 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-20"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-500 rounded-full blur-[100px] opacity-10"></div>

                <div class="w-full px-4 relative z-10 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/10 mb-6">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em]">Disponibles Ahora</span>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight">
                        Contáctanos <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Hoy</span>
                    </h1>
                    <p class="text-lg text-gray-300 max-w-2xl mx-auto font-medium">
                        Respondemos en menos de 2 horas. Para urgencias, escríbenos por WhatsApp.
                    </p>
                </div>
            </section>

            <!-- Contenido Principal -->
            <section class="py-12 -mt-8 relative z-20">
                <div class="w-full px-4">
                    <div class="max-w-7xl mx-auto grid lg:grid-cols-3 gap-8">
                        
                        <!-- Info de Contacto (1/3) -->
                        <div class="lg:col-span-1 space-y-4">
                            <!-- Dirección -->
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-md transition-all">
                                <div class="w-12 h-12 bg-[var(--color-primary-soft)] rounded-xl flex items-center justify-center text-[var(--color-primary)] mb-4 text-xl">
                                    <font-awesome-icon icon="map-marker-alt" />
                                </div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Visítanos</h3>
                                <p class="text-gray-900 dark:text-white font-bold text-sm leading-relaxed">{{ empresaData?.direccion_completa || empresaData?.direccion || 'Hermosillo, Sonora' }}</p>
                            </div>

                            <!-- Teléfono -->
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-md transition-all">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-green-600 dark:text-green-400 mb-4 text-xl">
                                    <font-awesome-icon icon="phone" />
                                </div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Llámanos</h3>
                                <a :href="'tel:' + empresaData?.telefono" class="text-xl font-black text-gray-900 dark:text-white hover:text-[var(--color-primary)] transition-colors">
                                    {{ empresaData?.telefono || '+52 000 000 0000' }}
                                </a>
                                <p class="text-xs text-gray-500 mt-1">Lun-Vie 9:00-18:00, Sáb 9:00-14:00</p>
                            </div>

                            <!-- Email -->
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-md transition-all">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 mb-4 text-xl">
                                    <font-awesome-icon icon="envelope" />
                                </div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Email</h3>
                                <a :href="'mailto:' + empresaData?.email" class="text-sm font-bold text-gray-900 dark:text-white hover:text-[var(--color-primary)] transition-colors break-all">
                                    {{ empresaData?.email || 'contacto@empresa.com' }}
                                </a>
                            </div>

                            <!-- WhatsApp Directo -->
                            <a :href="whatsappLink" target="_blank" class="block bg-gradient-to-br from-green-600 to-green-700 rounded-2xl p-6 text-center text-white shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all">
                                <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-4xl mb-3" />
                                <h3 class="text-base font-black mb-1">¿Prefieres respuesta inmediata?</h3>
                                <p class="text-green-100 text-xs mb-4">Escríbenos por WhatsApp y te respondemos al instante.</p>
                                <span class="inline-block px-6 py-2 bg-white text-green-700 rounded-lg font-black text-xs uppercase tracking-wider">
                                    Abrir WhatsApp →
                                </span>
                            </a>

                            <!-- Badges de Confianza -->
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">¿Por qué elegirnos?</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="w-8 h-8 bg-[var(--color-primary-soft)] rounded-lg flex items-center justify-center text-[var(--color-primary)] text-xs flex-shrink-0">
                                            <font-awesome-icon icon="medal" />
                                        </span>
                                        <span class="font-medium">Distribuidor autorizado Mirage</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs flex-shrink-0">
                                            <font-awesome-icon icon="certificate" />
                                        </span>
                                        <span class="font-medium">Técnicos certificados</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xs flex-shrink-0">
                                            <font-awesome-icon icon="shield-halved" />
                                        </span>
                                        <span class="font-medium">Garantía 1 año en mano de obra</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center text-purple-600 dark:text-purple-400 text-xs flex-shrink-0">
                                            <font-awesome-icon icon="clock" />
                                        </span>
                                        <span class="font-medium">+15 años de experiencia</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario Específico HVAC (2/3) -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-900 p-8 md:p-12 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                                <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Solicita tu Cotización</h2>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">Completa el formulario y te contactaremos con una propuesta personalizada.</p>
                                
                                <form @submit.prevent="submit" class="space-y-6">
                                    <!-- Nombre y Email -->
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Nombre Completo *</label>
                                            <input v-model="form.nombre" type="text" required placeholder="Ej. Juan Pérez" class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Email *</label>
                                            <input v-model="form.email" type="email" required placeholder="juan@empresa.com" class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        </div>
                                    </div>

                                    <!-- Teléfono y CP -->
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Teléfono *</label>
                                            <input v-model="form.telefono" type="tel" required placeholder="+52 662 000 0000" class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Código Postal</label>
                                            <input v-model="form.cp" type="text" placeholder="Ej. 83000" maxlength="5" class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        </div>
                                    </div>

                                    <!-- Tipo de Servicio -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">¿Qué servicio necesitas? *</label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <label v-for="servicio in [
                                                { value: 'instalacion', icon: 'tools', label: 'Instalación' },
                                                { value: 'mantenimiento', icon: 'wrench', label: 'Mantenimiento' },
                                                { value: 'reparacion', icon: 'tools', label: 'Reparación' },
                                                { value: 'cotizacion', icon: 'dollar-sign', label: 'Cotización' },
                                            ]" :key="servicio.value"
                                                   class="relative">
                                                <input v-model="form.servicio" type="radio" :value="servicio.value" class="peer sr-only" />
                                                <div class="p-4 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-center cursor-pointer hover:border-[var(--color-primary)] transition-all peer-checked:border-[var(--color-primary)] peer-checked:bg-[var(--color-primary-soft)]">
                                                    <font-awesome-icon :icon="servicio.icon" class="text-2xl mb-2 text-gray-400 peer-checked:text-[var(--color-primary)]" />
                                                    <p class="text-xs font-bold text-gray-600 dark:text-gray-400 peer-checked:text-gray-900 dark:text-white">{{ servicio.label }}</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Tipo de Equipo y Urgencia -->
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Tipo de Equipo</label>
                                            <select v-model="form.tipo_equipo" class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm text-gray-600 dark:text-gray-400">
                                                <option value="">Seleccionar...</option>
                                                <option value="minisplit">Minisplit</option>
                                                <option value="central">Aire Central</option>
                                                <option value="paquete">Equipo Paquete</option>
                                                <option value="comercial">Sistema Comercial</option>
                                                <option value="otro">Otro / No sé</option>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Nivel de Urgencia</label>
                                            <div class="grid grid-cols-3 gap-3">
                                                <label v-for="nivel in [
                                                    { value: 'normal', label: 'Normal', color: 'blue' },
                                                    { value: 'urgente', label: 'Urgente', color: 'orange' },
                                                    { value: 'emergencia', label: 'Emergencia', color: 'red' },
                                                ]" :key="nivel.value"
                                                       class="relative">
                                                    <input v-model="form.urgencia" type="radio" :value="nivel.value" class="peer sr-only" />
                                                    <div :class="{
                                                        'border-blue-200 dark:border-blue-800 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30': nivel.color === 'blue',
                                                        'border-orange-200 dark:border-orange-800 peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/30': nivel.color === 'orange',
                                                        'border-red-200 dark:border-red-800 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/30': nivel.color === 'red',
                                                    }" class="p-3 bg-gray-50 dark:bg-gray-800 border-2 rounded-xl text-center cursor-pointer transition-all">
                                                        <p class="text-xs font-bold text-gray-600 dark:text-gray-400">{{ nivel.label }}</p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mensaje -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-gray-600 dark:text-gray-400 ml-1">Describe tu necesidad *</label>
                                        <textarea v-model="form.mensaje" rows="4" required placeholder="Ej. Necesito instalar un minisplit en mi recámara de 15m²..." class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm resize-none"></textarea>
                                    </div>

                                    <!-- Botón Enviar -->
                                    <button type="submit" :disabled="form.processing" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                                        <font-awesome-icon icon="paper-plane" v-if="!form.processing" />
                                        <font-awesome-icon icon="spinner" spin v-else />
                                        {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Mapa + FAQ -->
            <section class="py-12 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
                <div class="w-full px-4">
                    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-8">
                        
                        <!-- Mapa -->
                        <div v-if="empresaData?.google_maps_embed_url" class="rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm h-80">
                            <iframe :src="empresaData.google_maps_embed_url" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                        <div v-else class="rounded-2xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center h-80">
                            <div class="text-center">
                                <font-awesome-icon icon="map-marker-alt" class="text-4xl text-gray-300 dark:text-gray-600 mb-3" />
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ empresaData?.direccion_completa || 'Hermosillo, Sonora' }}</p>
                            </div>
                        </div>

                        <!-- FAQ -->
                        <div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                                <font-awesome-icon icon="circle-question" class="text-[var(--color-primary)]" />
                                Preguntas Frecuentes
                            </h3>
                            <div class="space-y-3">
                                <div v-for="(faq, idx) in faqs" :key="idx" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                    <button @click="toggleFAQ(idx)" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white pr-4">{{ faq.pregunta }}</span>
                                        <font-awesome-icon :icon="activeFAQ === idx ? 'chevron-up' : 'chevron-down'" class="text-gray-400 flex-shrink-0 transition-transform" />
                                    </button>
                                    <div v-show="activeFAQ === idx" class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed border-t border-gray-100 dark:border-gray-700 pt-4">
                                        {{ faq.respuesta }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <PublicFooter :empresa="empresaData" />
    </div>
</template>
