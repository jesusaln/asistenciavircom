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
    
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 flex flex-col" :style="cssVars">
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
                <span :class="['text-2xl', i % 2 === 0 ? 'text-green-400' : 'text-[var(--color-primary)]']">
                    {{ ['🎉', '✨', '⭐', '🎊'][i % 4] }}
                </span>
            </div>
        </div>

        <main class="flex-1 flex items-center justify-center p-4">
            <div class="max-w-md w-full">
                <!-- Success Card -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 text-center text-white">
                        <div class="w-20 h-20 bg-white dark:bg-slate-900/20 backdrop-blur rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce-slow">
                            <span class="text-5xl">✅</span>
                        </div>
                        <h1 class="text-2xl font-black mb-2">¡Solicitud Recibida!</h1>
                        <p class="text-green-100">Te confirmaremos la cita por WhatsApp</p>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 space-y-5">
                        <!-- Folio - DESTACADO -->
                        <div class="text-center p-5 bg-gradient-to-r from-orange-50 to-amber-50 border-2 border-orange-200 rounded-2xl">
                            <p class="text-sm text-orange-700 font-medium mb-1">📌 Tu número de solicitud</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white tracking-wider">{{ cita?.folio || 'CITA-000' }}</p>
                            <div class="mt-3 p-2 bg-orange-100 rounded-lg">
                                <p class="text-xs text-orange-800 font-medium">
                                    ⚠️ ¡IMPORTANTE! Guarda este número para dar seguimiento a tu cita
                                </p>
                            </div>
                        </div>
                        
                        <!-- Mensaje de Email -->
                        <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">📧</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-800">Te hemos enviado un correo</p>
                                <p class="text-xs text-blue-600">
                                    Con tu folio y detalles de la cita. Revisa tu bandeja de entrada.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Resumen -->
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <span class="text-xl">📅</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Días preferidos</p>
                                    <p class="font-semibold text-gray-900 dark:text-white capitalize">
                                        {{ diasFormateados.join(' • ') || 'Por confirmar' }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <span class="text-xl">{{ cita?.horario_info?.emoji || '⏰' }}</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Horario preferido</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ cita?.horario_info?.nombre || 'Por confirmar' }}
                                        <span v-if="cita?.horario_info" class="text-gray-500 dark:text-gray-400 font-normal">
                                            ({{ cita.horario_info.inicio }} - {{ cita.horario_info.fin }})
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Próximos pasos -->
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                            <p class="font-semibold text-amber-800 mb-2">📋 ¿Qué sigue?</p>
                            <ol class="text-sm text-amber-700 space-y-1.5">
                                <li class="flex items-start gap-2">
                                    <span class="bg-amber-200 text-amber-800 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold flex-shrink-0">1</span>
                                    <span>Te enviaremos un mensaje de confirmación por WhatsApp</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="bg-amber-200 text-amber-800 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold flex-shrink-0">2</span>
                                    <span>Confirmaremos la fecha, hora exacta y técnico asignado</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="bg-amber-200 text-amber-800 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold flex-shrink-0">3</span>
                                    <span>El día de la cita, el técnico te llamará 30 minutos antes</span>
                                </li>
                            </ol>
                        </div>
                        
                        <!-- Link de seguimiento -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Consulta el estado de tu cita:</p>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="text" 
                                    :value="cita?.url_seguimiento" 
                                    readonly
                                    class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm text-gray-600 dark:text-gray-300 truncate"
                                />
                                <button 
                                    @click="copiarLink"
                                    class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium transition-colors"
                                >
                                    📋 Copiar
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
                            ✓ Aceptar
                        </a>
                        
                        <a 
                            :href="cita?.url_seguimiento"
                            class="block w-full py-3 bg-gray-100 text-gray-700 text-center font-medium rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            📍 Ver estado de mi cita
                        </a>
                    </div>
                </div>
                
                <!-- Company Info -->
                <div class="text-center mt-6 text-gray-500 dark:text-gray-400 text-sm">
                    <p class="font-medium text-gray-700">{{ empresa?.nombre || 'Asistencia Vircom' }}</p>
                    <p>Gracias por tu confianza 🛠️</p>
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
