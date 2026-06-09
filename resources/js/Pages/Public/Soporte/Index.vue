<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    empresa: Object,
});

const page = usePage();

// Combinar datos globales con props para asegurar colores corporativos
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

const faqs = [
    {
        pregunta: "¿Qué servicios ofrecen?",
        respuesta: "Brindamos soluciones integrales en Seguridad Electrónica (CCTV, Alarmas), Soporte Técnico TI, Redes de Datos y Sistemas de Control de Acceso para empresas y hogares.",
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
    },
    {
        pregunta: "¿Hacen servicio a domicilio?",
        respuesta: "Sí, contamos con unidades móviles equipadas para brindar atención técnica directamente en su ubicación, garantizando comodidad y eficiencia sin que tenga que trasladar sus equipos.",
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
    },
    {
        pregunta: "¿Cuánto tiempo tarda una reparación?",
        respuesta: "El tiempo de respuesta es nuestra prioridad. La mayoría de los diagnósticos se realizan el mismo día y las reparaciones suelen completarse en un rango de 24 a 48 horas.",
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    {
        pregunta: "¿Ofrecen garantía en sus servicios?",
        respuesta: "Absolutamente. Todos nuestros trabajos están respaldados por una garantía escrita que cubre tanto la mano de obra como las refacciones utilizadas, brindándole total tranquilidad.",
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-7.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
    },
    {
        pregunta: "¿Qué marcas de cámaras manejan?",
        respuesta: "Seleccionamos solo tecnología de vanguardia. Somos integradores autorizados de marcas líderes como Hikvision, Dahua, Ezviz y Provision-ISR, asegurando la mejor calidad de imagen.",
        icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
    },
    {
        pregunta: "¿Pueden monitorear mis cámaras de forma remota?",
        respuesta: "Sí, es una de nuestras especialidades. Configuramos el acceso remoto para que pueda vigilar sus propiedades desde su celular, tablet o PC con total seguridad y en tiempo real.",
        icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'
    }
];
</script>

<template>
    <Head :title="`Soporte Técnico - ${empresaData?.nombre || empresaData?.nombre_empresa || 'Empresa'}`" />

    <div class="min-h-screen bg-white dark:bg-slate-900 flex flex-col font-sans" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />
        
        <PublicNavbar :empresa="empresaData" activeTab="soporte" />

        <main class="flex-grow">
            <!-- Hero Header -->
            <section class="relative py-24 bg-gray-900 text-white overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-900 to-[var(--color-primary-dark)] opacity-40"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[150px] opacity-20"></div>
                
                <div class="w-full px-4 relative z-10 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white dark:bg-slate-900/10 backdrop-blur-md border border-white/10 mb-8">
                         <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                         <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white">Sistemas Operativos</span>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tighter">Centro de <br/><span class="text-[var(--color-primary)]">Soporte Técnico</span></h1>
                    <p class="text-xl text-gray-400 w-full font-medium">Estamos aquí para resolver sus problemas técnicos y asegurar la continuidad de su negocio.</p>
                </div>
            </section>

            <!-- Opciones de Soporte -->
            <section class="py-24 -mt-16 relative z-20">
                <div class="w-full px-4">
                    <div class="grid md:grid-cols-2 gap-8">
                        
                        <!-- Opción 1: Clientes Registrados -->
                        <div class="bg-white dark:bg-slate-900 p-10 rounded-[3rem] shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col items-center text-center group hover:-translate-y-2 transition-all duration-300">
                            <div class="w-20 h-20 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-8 text-4xl group-hover:scale-110 transition-transform">
                                🛡️
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">¿Ya es Cliente?</h2>
                            <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm">Ingrese a su portal de clientes para levantar tickets, ver el estado de sus servicios y descargar facturas.</p>
                            <a :href="route('login')" class="px-8 py-4 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition w-full max-w-xs shadow-lg shadow-blue-200">
                                Acceder al Portal
                            </a>
                        </div>

                        <!-- Opción 2: Soporte Remoto -->
                        <div class="bg-white dark:bg-slate-900 p-10 rounded-[3rem] shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col items-center text-center group hover:-translate-y-2 transition-all duration-300">
                            <div class="w-20 h-20 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 mb-8 text-4xl group-hover:scale-110 transition-transform">
                                💻
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">Soporte Remoto</h2>
                            <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm">Si un técnico se lo indica, descargue nuestra herramienta de soporte remoto para asistencia inmediata.</p>
                            <div class="flex gap-4 w-full max-w-xs justify-center">
                                <a href="https://anydesk.com/es/downloads" target="_blank" class="flex-1 px-4 py-4 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition border border-red-100">
                                    AnyDesk
                                </a>
                                <a href="https://www.teamviewer.com/es-mx/download/" target="_blank" class="flex-1 px-4 py-4 bg-blue-50 text-blue-600 rounded-xl font-bold hover:bg-blue-100 transition border border-blue-100">
                                    TeamViewer
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Preguntas Frecuentes Section -->
            <section class="py-24 bg-white dark:bg-slate-900 overflow-hidden relative">
                <div class="w-full px-4 relative z-10">
                    <div class="text-center mb-16 px-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[var(--color-primary-soft)] border border-[var(--color-primary-soft)] mb-4">
                             <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-primary)]">Soporte Directo</span>
                        </div>
                        <h2 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tighter mb-4">Preguntas Frecuentes</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium w-full">Todo lo que necesita saber sobre nuestros servicios y procesos técnicos para su tranquilidad.</p>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(faq, index) in faqs" :key="index" 
                             class="p-8 rounded-[2.5rem] bg-white dark:bg-slate-900 border border-gray-100 hover:bg-white dark:bg-slate-900 hover:shadow-2xl hover:shadow-gray-200/50 transition-all duration-500 group flex flex-col">
                            <div class="w-14 h-14 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center text-[var(--color-primary)] mb-6 group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all duration-500 transform group-hover:rotate-6">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="faq.icon"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-4 tracking-tight leading-tight">{{ faq.pregunta }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 leading-relaxed font-medium text-sm">{{ faq.respuesta }}</p>
                        </div>
                    </div>
                </div>

                <!-- Accent Shapes -->
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-[var(--color-primary-soft)] rounded-full blur-[150px] opacity-40"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-gray-100 rounded-full blur-[150px] opacity-50"></div>
            </section>
            
            <!-- Asistencia Inmediata -->
            <section class="py-20 bg-gray-900 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-900 to-[var(--color-primary-dark)] opacity-20"></div>
                <div class="w-full px-4 relative z-10 text-center">
                    <h2 class="text-3xl font-black text-white mb-8 tracking-tight">¿Necesita asistencia inmediata?</h2>
                    <div class="flex flex-col md:flex-row justify-center gap-6">
                        <a :href="`tel:${empresaData?.telefono}`" class="flex-1 w-full md:mx-0 flex items-center gap-5 bg-white dark:bg-slate-900/5 backdrop-blur-xl border border-white/10 p-8 rounded-3xl hover:bg-white dark:bg-slate-900/10 transition group">
                            <span class="w-14 h-14 bg-[var(--color-primary)] rounded-2xl flex items-center justify-center text-white text-2xl group-hover:scale-110 transition-transform">📞</span>
                            <div class="text-left">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Llámenos Directo</div>
                                <div class="text-xl font-black text-white whitespace-nowrap">{{ empresaData?.telefono || 'Consultar' }}</div>
                            </div>
                        </a>
                        <a :href="`mailto:${empresaData?.email}`" class="flex-1 w-full md:mx-0 flex items-center gap-5 bg-white dark:bg-slate-900/5 backdrop-blur-xl border border-white/10 p-8 rounded-3xl hover:bg-white dark:bg-slate-900/10 transition group">
                            <span class="w-14 h-14 bg-white dark:bg-slate-900/10 rounded-2xl flex items-center justify-center text-white text-2xl group-hover:scale-110 transition-transform">✉️</span>
                            <div class="text-left">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Envíenos un Email</div>
                                <div class="text-xl font-black text-white truncate max-w-[200px]">{{ empresaData?.email || 'soporte@empresa.com' }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <PublicFooter :empresa="empresaData" />
    </div>
</template>
