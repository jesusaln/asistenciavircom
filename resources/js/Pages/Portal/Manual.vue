<script setup>
import { Head } from '@inertiajs/vue3';
import ClientLayout from './Layout/ClientLayout.vue';

const props = defineProps({
    manualContent: String,
    empresa: Object,
});
</script>

<template>
    <Head title="Manual de Usuario" />
    <ClientLayout :empresa="empresa">
        <div class="px-4 sm:px-6 lg:px-8 py-10">
            <div class="mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Manual de Usuario del Portal</h1>
                <p class="text-gray-500 font-medium mt-1">Guía de referencia para todas las funciones de su portal de cliente.</p>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                <div class="p-6 sm:p-10">
                    <pre class="whitespace-pre-wrap font-sans text-sm text-gray-700 leading-relaxed">{{ manualContent }}</pre>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
