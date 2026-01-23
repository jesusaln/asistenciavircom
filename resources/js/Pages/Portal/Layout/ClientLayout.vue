<script setup>
import { Link, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import ToastContainer from '@/Components/Toast/ToastContainer.vue';
import ChatbotWidget from '@/Components/Chatbot/ChatbotWidget.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

const props = defineProps({
    empresa: Object,
    activeTab: String,
});

const page = usePage();

// Usar empresa_config global para asegurar que los colores siempre estén disponibles
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

// Integrar modo oscuro centralizado
useDarkMode(empresaData.value);
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-slate-900 dark:bg-gray-900 flex flex-col font-sans transition-colors duration-300">
        <!-- Reutilizamos el Navbar Público para consistencia total -->
        <PublicNavbar :empresa="empresaData" activeTab="soporte" />

        <!-- Sub-Header del Portal -->
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shadow-sm transition-colors">
            <div class="w-full px-4 h-16 flex items-center justify-between">
                <div class="flex gap-8">
                    <Link 
                        :href="route('portal.dashboard')" 
                        :class="[
                            'text-sm font-bold uppercase tracking-widest pb-5 mt-5 transition-all outline-none',
                            ($page.component === 'Portal/Dashboard' || $page.component.startsWith('Portal/Polizas/'))
                                ? 'text-[var(--color-primary)] border-b-2 border-[var(--color-primary)]' 
                                : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        Mis Servicios
                    </Link>
                    <Link 
                        :href="route('portal.tickets.create')" 
                        :class="[
                            'text-sm font-bold uppercase tracking-widest pb-5 mt-5 transition-all outline-none',
                            $page.component === 'Portal/CreateTicket' 
                                ? 'text-[var(--color-primary)] border-b-2 border-[var(--color-primary)]' 
                                : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        Solicitar Soporte
                    </Link>
                    <Link 
                        :href="route('portal.credito.index')" 
                        :class="[
                            'text-sm font-bold uppercase tracking-widest pb-5 mt-5 transition-all outline-none',
                            ($page.component === 'Portal/Credito/Index' || $page.component === 'Portal/Credito/FirmarSolicitud')
                                ? 'text-[var(--color-primary)] border-b-2 border-[var(--color-primary)]' 
                                : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        Línea de Crédito
                    </Link>
                    <Link 
                        :href="route('portal.rentas.index')" 
                        :class="[
                            'text-sm font-bold uppercase tracking-widest pb-5 mt-5 transition-all outline-none',
                            ($page.component === 'Portal/Rentas/Index' || $page.component === 'Portal/Rentas/Firmar')
                                ? 'text-[var(--color-primary)] border-b-2 border-[var(--color-primary)]' 
                                : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        Mis Rentas
                    </Link>
                </div>

                <div class="hidden sm:flex items-center gap-3 bg-[var(--color-primary-soft)] px-4 py-2 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-[var(--color-primary)] animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)]">Portal de Cliente Activo</span>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-grow py-8 px-4">
            <div class="w-full">
                <slot />
            </div>
        </main>

        <!-- Footer con mapa y datos de empresa -->
        <PublicFooter :empresa="empresaData" />

        <!-- Asistente Virtual IA -->
        <ChatbotWidget :empresa="empresaData" />

        <!-- Sistema de Notificaciones Premium -->
        <ToastContainer />
    </div>
</template>

<style scoped>
/* Eliminar outline azul de accesibilidad para un look mas limpio en este portal */
:deep(.transition-all) {
    -webkit-tap-highlight-color: transparent;
}
</style>
