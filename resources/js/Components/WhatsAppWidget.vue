<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    whatsapp: String,
    empresaNombre: String
});

const isOpen = ref(false);

const cleanPhone = computed(() => {
    return props.whatsapp ? props.whatsapp.replace(/\D/g, '') : '5216622036840'; // Fallback al número de Jesús
});

const options = [
    {
        id: 'sales',
        name: 'Preguntar por Promociones',
        desc: 'Cámaras, Kits y ofertas de Enero',
        icon: '🏷️',
        message: 'Hola Clawdbot! 👋 Me interesa conocer las promociones de cámaras HiLook que tienen este mes.',
        color: 'from-blue-500 to-indigo-600'
    },
    {
        id: 'appointment',
        name: 'Agendar una Cita',
        desc: 'Instalaciones y soporte técnico',
        icon: '📅',
        message: 'Hola! Quiero agendar una cita para un servicio técnico. ¿Me puedes ayudar?',
        color: 'from-purple-500 to-pink-600'
    },
    {
        id: 'general',
        name: 'Consulta General',
        desc: 'Habla directamente con nuestra IA',
        icon: '🤖',
        message: 'Hola! Tengo una duda general sobre los servicios de Vircom.',
        color: 'from-emerald-500 to-teal-600'
    }
];

const openWhatsApp = (option) => {
    const url = `https://wa.me/${cleanPhone.value}?text=${encodeURIComponent(option.message)}`;
    window.open(url, '_blank');
    isOpen.value = false;
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end font-sans">
        <!-- AI Chat Window -->
        <Transition name="pop">
            <div v-if="isOpen" class="mb-4 w-80 bg-white dark:bg-slate-900 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-100 dark:border-slate-800 overflow-hidden">
                <!-- Header: AI themed -->
                <div class="bg-slate-950 p-6 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-purple-600/20 opacity-50"></div>
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-tr from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-2xl shadow-lg border border-white/20 animate-pulse">
                            🤖
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-bold leading-tight">Clawdbot IA</h3>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <p class="text-[10px] uppercase tracking-wider font-semibold opacity-70">En línea ahora</p>
                            </div>
                        </div>
                    </div>
                    <p class="relative z-10 text-xs mt-4 text-slate-300 leading-relaxed">
                        Hola! Soy la IA de <strong>{{ empresaNombre }}</strong>. ¿En qué puedo ayudarte hoy de forma inmediata?
                    </p>
                </div>

                <!-- Options List -->
                <div class="p-4 bg-gray-50/30 dark:bg-slate-900/50 space-y-3">
                    <button 
                        v-for="option in options" 
                        :key="option.id"
                        @click="openWhatsApp(option)"
                        class="w-full flex items-center gap-4 p-3 rounded-2xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 hover:border-blue-500/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group"
                    >
                        <div :class="option.color" class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-inner bg-gradient-to-br text-white">
                            {{ option.icon }}
                        </div>
                        <div class="text-left flex-1">
                            <p class="font-bold text-gray-900 dark:text-white text-sm group-hover:text-blue-500 transition-colors">{{ option.name }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1">{{ option.desc }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Footer -->
                <div class="p-3 text-center bg-white dark:bg-slate-900 border-t border-gray-50 dark:border-slate-800">
                    <div class="flex items-center justify-center gap-1.5">
                        <svg class="w-3 h-3 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        <p class="text-[9px] text-gray-500 font-medium">Asistencia impulsada por Clawdbot</p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Launcher Button -->
        <button 
            @click="isOpen = !isOpen"
            class="group relative flex items-center justify-center w-16 h-16 rounded-full shadow-[0_10px_40px_rgba(37,211,102,0.4)] transition-all duration-500 hover:scale-110 active:scale-95"
            :class="isOpen ? 'bg-slate-900 rotate-90' : 'bg-gradient-to-tr from-[#25D366] to-[#128C7E]'"
        >
            <!-- Badge Notification -->
            <div v-if="!isOpen" class="absolute -top-1 -right-1 flex h-6 w-6">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-6 w-6 bg-blue-500 text-[10px] text-white font-bold items-center justify-center">1</span>
            </div>

            <Transition name="fade" mode="out-in">
                <div v-if="!isOpen" key="open" class="flex items-center justify-center">
                   <span class="text-3xl">🤖</span>
                </div>
                <svg v-else key="close" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </Transition>
        </button>
    </div>
</template>

<style scoped>
.pop-enter-active {
    animation: pop-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.pop-leave-active {
    animation: pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) reverse;
}

@keyframes pop-in {
    0% { transform: scale(0.6) translateY(40px); opacity: 0; filter: blur(10px); }
    100% { transform: scale(1) translateY(0); opacity: 1; filter: blur(0); }
}

.fade-enter-active, .fade-leave-active {
    transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: scale(0.8);
}
</style>
