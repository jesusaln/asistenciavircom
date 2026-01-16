<script setup>
import { Link, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

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

const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#F59E0B',
    '--color-primary-soft': (empresaData.value.color_principal || '#F59E0B') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#F59E0B') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#B45309',
    '--color-terciary-soft': (empresaData.value.color_terciario || '#B45309') + '15',
}));
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans" :style="cssVars">
        <!-- Reutilizamos el Navbar Público para consistencia total -->
        <PublicNavbar :empresa="empresaData" activeTab="soporte" />

        <!-- Sub-Header del Portal -->
        <div class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex gap-8">
                    <Link 
                        :href="route('portal.dashboard')" 
                        :class="[
                            'text-sm font-bold uppercase tracking-widest pb-5 mt-5 transition-all outline-none',
                            ($page.component === 'Portal/Dashboard' || $page.component.startsWith('Portal/Polizas/'))
                                ? 'text-[var(--color-primary)] border-b-2 border-[var(--color-primary)]' 
                                : 'text-gray-400 hover:text-gray-600'
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
                                : 'text-gray-400 hover:text-gray-600'
                        ]"
                    >
                        Solicitar Soporte
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
            <div class="max-w-7xl mx-auto">
                <slot />
            </div>
        </main>

        <!-- Footer con mapa y datos de empresa -->
        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
/* Eliminar outline azul de accesibilidad para un look mas limpio en este portal */
:deep(.transition-all) {
    -webkit-tap-highlight-color: transparent;
}
</style>
