<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import PosInfoSection from '@/Components/PosInfoSection.vue';

const props = defineProps({
    empresa: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3B82F6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3B82F6') + '15',
    '--color-secondary': props.empresa?.color_secundario || '#64748B',
}));
</script>

<template>
    <Head title="Puntos de Venta - Asesoría y Venta" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="" />

        <PosInfoSection :empresa="empresa" />

        <PublicFooter :empresa="empresa" />
    </div>
</template>
