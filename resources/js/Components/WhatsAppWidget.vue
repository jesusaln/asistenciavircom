<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    whatsapp: String,
    empresaNombre: String
});

const isOpen = ref(false);

const cleanPhone = computed(() => {
    return props.whatsapp ? props.whatsapp.replace(/\D/g, '') : '';
});

const agents = [
    {
        id: 1,
        name: 'Soporte Técnico',
        role: 'Atención técnica y garantías',
        icon: '👷‍♂️',
        message: 'Hola, necesito soporte técnico para mi equipo. ¿Me podrían ayudar?',
        color: 'bg-orange-500'
    },
    {
        id: 2,
        name: 'Ventas y Cotizaciones',
        role: 'Equipos nuevos y proyectos',
        icon: '💰',
        message: 'Hola, me gustaría recibir una cotización para un equipo nuevo.',
        color: 'bg-green-500'
    },
    {
        id: 3,
        name: 'Agendar Mantenimiento',
        role: 'Servicios preventivos',
        icon: '📅',
        message: 'Hola, quiero agendar un mantenimiento preventivo para mi equipo.',
        color: 'bg-blue-500'
    }
];

const openWhatsApp = (agent) => {
    const url = `https://wa.me/${cleanPhone.value}?text=${encodeURIComponent(agent.message)}`;
    window.open(url, '_blank');
    isOpen.value = false;
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end">
        <!-- Menu -->
        <Transition name="pop">
            <div v-if="isOpen" class="mb-4 w-72 bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-br from-[#25D366] to-[#128C7E] p-6 text-white text-center">
                    <p class="text-xs uppercase tracking-widest font-bold opacity-80 mb-1">Centro de Atención</p>
                    <h3 class="text-xl font-bold">¿Cómo podemos ayudarte?</h3>
                    <p class="text-xs mt-2 opacity-90">Selecciona el departamento adecuado para una atención más rápida.</p>
                </div>

                <!-- Agents List -->
                <div class="p-3 bg-gray-50/50">
                    <button 
                        v-for="agent in agents" 
                        :key="agent.id"
                        @click="openWhatsApp(agent)"
                        class="w-full flex items-center gap-4 p-3 rounded-2xl hover:bg-white dark:bg-slate-900 hover:shadow-md transition-all duration-300 group mb-1 border border-transparent hover:border-gray-100 dark:border-slate-800"
                    >
                        <div :class="agent.color" class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shadow-sm group-hover:scale-110 transition-transform">
                            {{ agent.icon }}
                        </div>
                        <div class="text-left">
                            <p class="font-bold text-gray-900 dark:text-white text-sm">{{ agent.name }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-tight">{{ agent.role }}</p>
                        </div>
                        <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-4 h-4 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0a12.016 12.016 0 00-11.944 12.016c0 2.212.57 4.29 1.575 6.105L.014 24l6.082-1.597a11.946 11.946 0 005.848 1.543c6.645 0 12.036-5.391 12.036-12.036S18.589 0 11.944 0z" />
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Footer -->
                <div class="p-3 text-center border-t border-gray-100 dark:border-slate-800">
                    <p class="text-[10px] text-gray-400">Atención inmediata vía WhatsApp</p>
                </div>
            </div>
        </Transition>

        <!-- Main Button -->
        <button 
            @click="isOpen = !isOpen"
            class="group relative flex items-center justify-center w-16 h-16 rounded-full shadow-2xl transition-all duration-500 hover:scale-110 active:scale-95"
            :class="isOpen ? 'bg-gray-900 rotate-90' : 'bg-[#25D366]'"
        >
            <!-- Badge Notification -->
            <div v-if="!isOpen" class="absolute -top-1 -right-1 flex h-6 w-6">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-6 w-6 bg-red-500 text-[10px] text-white font-bold items-center justify-center">1</span>
            </div>

            <Transition name="fade" mode="out-in">
                <svg v-if="!isOpen" key="open" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <svg v-else key="close" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </Transition>
        </button>
    </div>
</template>

<style scoped>
.pop-enter-active {
    animation: pop-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.pop-leave-active {
    animation: pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) reverse;
}

@keyframes pop-in {
    0% { transform: scale(0.5) translateY(20px); opacity: 0; }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
