<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    cita: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
}));

// Formatear días preferidos
const diasFormateados = computed(() => {
    return props.cita?.dias_preferidos?.map(fecha => {
        const d = new Date(fecha + 'T12:00:00');
        return d.toLocaleDateString('es-MX', { 
            weekday: 'long', 
            day: 'numeric', 
            month: 'long' 
        });
    }) || [];
});

// Copiar link al portapapeles
const copiarLink = () => {
    navigator.clipboard.writeText(props.cita?.url_seguimiento || '');
    // TODO: Mostrar toast de confirmación
};
</script>

<template>
    <Head title="¡Solicitud Recibida!" />
    
    <div class="min-h-screen bg-[var(--ui-surface)] flex flex-col" :style="cssVars">
        <!-- Confetti Animation Background -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden">
            <div v-for="i in 20" :key="i" 
                class="absolute animate-confetti"
                :style="{
                    left: `${Math.random() * 100}%`,
                    animationDelay: `${Math.random() * 2}s`,
                    animationDuration: `${3 + Math.random() * 2}s`
                }"
            >
                <span :class="['text-2xl', i % 2 === 0 ? 'text-emerald-400' : 'text-[var(--color-primary)]']">
                    <font-awesome-icon :icon="['check-circle', 'wand-magic-sparkles', 'gem', 'crown'][i % 4]" />
                </span>
            </div>
        </div>

        <main class="flex-1 flex items-center justify-center p-4">
            <div class="max-w-md w-full">
                <!-- Success Card -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-brand-500 to-brand-600 p-8 text-center text-white">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce-slow">
                            <span class="text-5xl"><font-awesome-icon icon="check-circle" /></span>
                        </div>
                        <h1 class="text-2xl font-black mb-2">¡Solicitud Recibida!</h1>
                        <p class="text-emerald-100">Te confirmaremos la cita por WhatsApp</p>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 space-y-5">
                        <!-- Folio - DESTACADO -->
                        <div class="text-center p-5 bg-gradient-to-r from-orange-50 to-brand-50 border-2 border-orange-200 rounded-2xl">
                            <p class="text-sm text-brand-700 font-medium mb-1"><font-awesome-icon icon="id-card" class="mr-1" /> Tu número de solicitud</p>
                            <p class="text-2xl font-black text-slate-900 tracking-wider">{{ cita?.folio || 'CITA-000' }}</p>
                            <div class="mt-3 p-2 bg-brand-100 rounded-xl">
                                <p class="text-xs text-orange-800 font-medium">
                                    <font-awesome-icon icon="triangle-exclamation" class="mr-1" /> ¡IMPORTANTE! Guarda este número para dar seguimiento a tu cita
                                </p>
                            </div>
                        </div>
                        
                        <!-- Mensaje de WhatsApp -->
                        <div class="flex items-center gap-2 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-xl">
                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center flex-shrink-0 text-emerald-600 dark:text-emerald-400">
                                <span class="text-xl">💬</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-emerald-850 dark:text-emerald-200">Te hemos enviado un WhatsApp</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                    Con tu número de folio y el enlace para dar seguimiento a tu servicio.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Resumen -->
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <span class="text-xl"><font-awesome-icon icon="calendar-alt" /></span>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-500">Días preferidos</p>
                                    <p class="font-semibold text-slate-900 capitalize">
                                        {{ diasFormateados.join(' • ') || 'Por confirmar' }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-brand-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <span class="text-xl"><font-awesome-icon icon="clock" /></span>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-500">Horario preferido</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ cita?.horario_info?.nombre || 'Por confirmar' }}
                                        <span v-if="cita?.horario_info" class="text-slate-500 font-normal">
                                            ({{ cita.horario_info.inicio }} - {{ cita.horario_info.fin }})
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Próximos pasos -->
                        <div class="bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800/30 rounded-2xl p-4">
                            <p class="font-semibold text-brand-800 dark:text-brand-200 mb-2"><font-awesome-icon icon="clipboard-list" class="mr-1" /> ¿Qué sigue?</p>
                            <ol class="text-sm text-brand-800 dark:text-brand-200 dark:text-brand-200 space-y-1.5">
                                <li class="flex items-start gap-2">
                                    <span class="bg-brand-200 text-brand-800 dark:text-brand-200 rounded-full w-4 h-4 flex items-center justify-center text-xs font-bold flex-shrink-0">1</span>
                                    <span>Te enviaremos un mensaje de confirmación por WhatsApp</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="bg-brand-200 text-brand-800 dark:text-brand-200 rounded-full w-4 h-4 flex items-center justify-center text-xs font-bold flex-shrink-0">2</span>
                                    <span>Confirmaremos la fecha, hora exacta y técnico asignado</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="bg-brand-200 text-brand-800 dark:text-brand-200 rounded-full w-4 h-4 flex items-center justify-center text-xs font-bold flex-shrink-0">3</span>
                                    <span>El día de la cita, el técnico te llamará 30 minutos antes</span>
                                </li>
                            </ol>
                        </div>
                        
                        <!-- Link de seguimiento -->
                        <div class="bg-white rounded-2xl p-4">
                            <p class="text-sm text-slate-500 mb-2">Consulta el estado de tu cita:</p>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="text" 
                                    :value="cita?.url_seguimiento" 
                                    readonly
                                    class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-500 truncate"
                                />
                                <button 
                                    @click="copiarLink"
                                    class="px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded-xl text-sm font-medium transition-colors"
                                >
                                    <font-awesome-icon icon="clipboard-list" class="mr-1" /> Copiar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="p-6 pt-0 space-y-3">
                        <!-- Botón principal: Aceptar -->
                        <a 
                            href="/"
                            class="block w-full py-4 bg-[var(--color-primary)] text-white text-center font-bold text-lg rounded-xl hover:opacity-90 transition-opacity"
                        >
                            <font-awesome-icon icon="check" class="mr-1" /> Aceptar
                        </a>
                        
                        <a 
                            :href="cita?.url_seguimiento"
                            class="block w-full py-3 bg-slate-100 text-slate-700 text-center font-medium rounded-xl hover:bg-slate-200 transition-colors"
                        >
                            <font-awesome-icon icon="map-marker-alt" class="mr-1" /> Ver estado de mi cita
                        </a>
                    </div>
                </div>
                
                <!-- Company Info -->
                <div class="text-center mt-6 text-slate-500 text-sm">
                    <p class="font-medium text-slate-700">{{ empresa?.nombre || 'Empresa' }}</p>
                    <p>Gracias por tu confianza <font-awesome-icon icon="tools" class="ml-1" /></p>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
@keyframes confetti {
    0% {
        transform: translateY(-100px) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
    }
}

.animate-confetti {
    animation: confetti linear forwards;
}

@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}
</style>
