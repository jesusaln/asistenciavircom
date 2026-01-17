<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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

const form = useForm({
    nombre: '',
    email: '',
    telefono: '',
    asunto: '',
    mensaje: '',
});

const submit = () => {
    form.post(route('contact.submit'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="`Contacto - ${empresaData?.nombre || empresaData?.nombre_empresa || 'Empresa'}`">
        <meta name="description" :content="`Contáctanos en ${empresaData?.ciudad || 'Hermosillo'}. Servicios de soporte técnico, redes, cámaras y facturación. Teléfono: ${empresaData?.telefono}, Email: ${empresaData?.email}.`" />
    </Head>

    <div class="min-h-screen bg-white dark:bg-gray-950 flex flex-col font-sans transition-colors duration-300" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />
        
        <PublicNavbar :empresa="empresaData" activeTab="contacto" />

        <main class="flex-grow">
            <!-- Hero Header -->
            <section class="relative py-24 bg-gray-900 text-white overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-900 to-[var(--color-primary-dark)] opacity-40"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[150px] opacity-20"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-[var(--color-terciary)] rounded-full blur-[150px] opacity-10"></div>

                <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/10 mb-8">
                         <span class="w-2 h-2 rounded-full bg-[var(--color-terciary)] animate-pulse"></span>
                         <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white">Estamos para Ayudarle</span>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tighter">Hablemos de su <br/><span class="text-[var(--color-terciary)]">Próximo Paso</span></h1>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto font-medium">Resolviendo sus dudas, potenciando sus proyectos e impulsando su crecimiento tecnológico.</p>
                </div>
            </section>

            <!-- Contact Content -->
            <section class="py-24 -mt-16 relative z-20">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="grid lg:grid-cols-3 gap-12">
                        
                        <!-- Contact Info Cards -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 group hover:-translate-y-1 transition-all duration-300">
                                <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] mb-6 text-2xl group-hover:scale-110 transition-transform">📍</div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2 transition-colors">Visítenos</h3>
                                <p class="text-gray-900 dark:text-white font-bold leading-relaxed transition-colors">{{ empresaData?.direccion_completa || empresaData?.direccion || 'Hermosillo, Sonora' }}</p>
                            </div>

                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 group hover:-translate-y-1 transition-all duration-300">
                                <div class="w-16 h-16 bg-[var(--color-terciary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-terciary)] mb-6 text-2xl group-hover:scale-110 transition-transform">📞</div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2 transition-colors">Llámenos</h3>
                                <p class="text-2xl font-black text-gray-900 dark:text-white transition-colors">{{ empresaData?.telefono || '+52 000 000 0000' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium transition-colors">Lunes a Viernes, 9am - 6pm</p>
                            </div>

                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 group hover:-translate-y-1 transition-all duration-300">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center text-gray-900 dark:text-white mb-6 text-2xl group-hover:scale-110 transition-transform">✉️</div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2 transition-colors">Escríbanos</h3>
                                <p class="text-lg font-bold text-gray-900 dark:text-white truncate transition-colors">{{ empresaData?.email || 'contacto@empresa.com' }}</p>
                            </div>
                        </div>

                        <!-- Contact Form -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-900 p-10 md:p-16 rounded-[3rem] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-50 dark:border-gray-800 transition-colors">
                                <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-8 transition-colors">Envíe un Mensaje</h2>
                                <form @submit.prevent="submit" class="grid md:grid-cols-2 gap-8">
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 ml-1 transition-colors">Nombre Completo</label>
                                        <input v-model="form.nombre" type="text" placeholder="Ej. Juan Pérez" class="w-full px-6 py-4 bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium placeholder-gray-400 dark:placeholder-gray-500">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Correo Electrónico</label>
                                        <input v-model="form.email" type="email" placeholder="juan@empresa.com" class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Teléfono</label>
                                        <input v-model="form.telefono" type="tel" placeholder="+52 ..." class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Asunto</label>
                                        <select v-model="form.asunto" class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium text-gray-500">
                                            <option value="">Seleccione una opción</option>
                                            <option value="ventas">Ventas / Cotización</option>
                                            <option value="soporte">Soporte Técnico</option>
                                            <option value="polizas">Pólizas de Servicio</option>
                                            <option value="otro">Otro Motivo</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2 space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-400 ml-1">¿En qué podemos ayudarle?</label>
                                        <textarea v-model="form.mensaje" rows="4" placeholder="Describa su consulta..." class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium resize-none"></textarea>
                                    </div>
                                    <div class="md:col-span-2 pt-4">
                                        <button type="submit" class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/25 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/40 transition-all flex items-center justify-center gap-3">
                                            Enviar Mensaje Directo
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Horarios de Atención -->
            <section class="py-12 bg-gray-50 dark:bg-gray-900 transition-colors">
                <div class="max-w-3xl mx-auto px-4">
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 transition-colors">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3 text-lg transition-colors">
                            <span class="w-10 h-10 bg-[var(--color-primary-soft)] rounded-xl flex items-center justify-center text-xl">🕐</span>
                            Horarios de Atención
                        </h3>
                        <div class="grid sm:grid-cols-3 gap-4 text-center">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-colors">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 transition-colors">Lunes - Viernes</p>
                                <p class="font-bold text-gray-900 dark:text-white transition-colors">9:00 - 18:00</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-sm text-gray-500 mb-1">Sábados</p>
                                <p class="font-bold text-gray-900">9:00 - 14:00</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-sm text-gray-500 mb-1">Domingos</p>
                                <p class="font-bold text-red-500">Cerrado</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Public Footer con datos de empresa -->
        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
