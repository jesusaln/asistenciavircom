<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    poliza: Object,
    empresa: Object,
    message: String,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3b82f6') + '15',
}));
</script>

<template>
    <Head title="Pago Cancelado" />

    <div class="min-h-screen bg-white flex flex-col" :style="cssVars">
        <PublicNavbar :empresa="empresa" />
        
        <div class="flex-grow flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-xl max-w-lg w-full overflow-hidden text-center p-8 md:p-12">
                
                <!-- Icono de Cancelación -->
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <!-- Título y Mensaje -->
                <h1 class="text-2xl font-black text-slate-900 mb-4">
                    Pago Cancelado
                </h1>
                
                <p class="text-slate-500 mb-8 text-lg">
                    {{ message || 'El proceso de pago fue cancelado. No te preocupes, puedes intentar de nuevo cuando quieras.' }}
                </p>

                <!-- Información de la Póliza pendiente -->
                <div v-if="poliza" class="bg-brand-50 dark:bg-brand-900/20 rounded-2xl p-6 mb-6 border border-brand-200 dark:border-brand-800/30 text-left">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-black text-brand-800 dark:text-brand-200 dark:text-brand-200 uppercase tracking-wide text-xs">Póliza Pendiente</h3>
                        <span class="bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-brand-200 text-[10px] font-black px-2 py-1 rounded-xl uppercase">
                            {{ poliza.estado?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Plan:</span>
                            <span class="font-bold text-slate-900">{{ poliza.nombre }}</span>
                        </div>
                        <p class="text-xs text-brand-600 mt-3 italic">
                            Tu póliza está reservada y esperando el pago.
                        </p>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="space-y-3">
                    <Link v-if="poliza?.planPoliza?.slug" :href="route('contratacion.show', poliza.planPoliza.slug)" 
                        class="block w-full py-4 bg-[var(--color-primary)] text-white font-black text-sm uppercase tracking-wide rounded-2xl shadow-xl hover:opacity-90 transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Intentar de Nuevo
                    </Link>
                    
                    <Link :href="route('catalogo.polizas')" class="block w-full py-4 border-2 border-slate-200 text-slate-700 font-black text-sm uppercase tracking-wide rounded-xl hover:bg-white transition">
                        Ver Otros Planes
                    </Link>
                    
                    <Link href="/" class="block w-full py-3 text-slate-400 hover:text-brand-600 font-bold text-xs">
                        Volver al Inicio
                    </Link>
                </div>
                
                <!-- Mensaje de Ayuda -->
                <div class="mt-8 pt-6 border-t border-slate-100">
                    <p class="text-xs text-slate-400">
                        ¿Tienes problemas con el pago? 
                        <Link :href="route('public.contacto')" class="text-[var(--color-primary)] font-bold hover:underline">
                            Contáctanos
                        </Link>
                    </p>
                </div>
            </div>
        </div>

        <PublicFooter :empresa="empresa" />
    </div>
</template>
