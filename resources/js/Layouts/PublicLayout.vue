<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

const page = usePage();
const empresaData = computed(() => page.props.empresa_config || {});

// Integrar modo oscuro centralizado
useDarkMode(empresaData.value);

</script>

<template>
    <div class="min-h-screen bg-white dark:bg-slate-950 font-sans text-gray-900 dark:text-white transition-colors duration-300">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || 'Vircom'" />

        <!-- Navigation -->
        <PublicNavbar :empresa="empresaData" />

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <PublicFooter :empresa="empresaData" />
    </div>
</template>
