<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    whatsapp: String,
    empresaNombre: String
});

const isOpen = ref(false);
const messages = ref([
    { id: 1, text: '¡Hola! Soy Vircom Bot. 🤖 Estoy listo para ayudarte. ¿En qué puedo apoyarte hoy?', isBot: true }
]);
const newMessage = ref('');
const isLoading = ref(false);
const messagesContainer = ref(null);
const sessionId = ref('web-' + Math.random().toString(36).substring(2, 9));

const cleanPhone = computed(() => {
    return props.whatsapp ? props.whatsapp.replace(/\D/g, '') : '5216622036840';
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || isLoading.value) return;

    const userText = newMessage.value;
    messages.value.push({ id: Date.now(), text: userText, isBot: false });
    newMessage.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        const response = await fetch('/chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: userText,
                session_id: sessionId.value
            })
        });

        const data = await response.json();

        if (data.success) {
            messages.value.push({ id: Date.now() + 1, text: data.message, isBot: true });
        } else {
            messages.value.push({ id: Date.now() + 1, text: 'Lo siento, tuve un problema de conexión. ¿Puedes intentar de nuevo?', isBot: true });
        }
    } catch (error) {
        console.error('Error sending message:', error);
        messages.value.push({ id: Date.now() + 1, text: 'Error de red. Por favor verifica tu conexión.', isBot: true });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};

const quickActions = [
    { text: 'Ver Promociones', icon: '🏷️' },
    { text: 'Agendar Cita', icon: '📅' },
    { text: 'Soporte Técnico', icon: '🛠️' }
];

const useQuickAction = (text) => {
    newMessage.value = text;
    sendMessage();
};

onMounted(() => {
    if (isOpen.value) scrollToBottom();
});
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end font-sans">
        <!-- Chat Area -->
        <Transition name="pop">
            <div v-if="isOpen" class="mb-4 w-80 md:w-96 bg-white dark:bg-slate-950 rounded-[2rem] shadow-[0_20px_60px_rgba(0,0,0,0.4)] border border-gray-100 dark:border-slate-800 overflow-hidden flex flex-col h-[500px]">
                
                <!-- Chat Header -->
                <div class="bg-slate-900 p-5 text-white relative overflow-hidden flex-shrink-0">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/30 to-purple-600/30 opacity-50"></div>
                    <div class="relative z-10 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-tr from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-xl shadow-lg border border-white/20">
                            🤖
                        </div>
                        <div>
                            <h3 class="text-base font-bold">Vircom Bot</h3>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span class="text-[9px] uppercase tracking-widest font-bold opacity-70">Soporte Inteligente</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50 dark:bg-slate-900 shadow-inner">
                    <div v-for="msg in messages" :key="msg.id" :class="['flex w-full', msg.isBot ? 'justify-start' : 'justify-end']">
                        <div 
                            :class="[
                                'max-w-[85%] p-3 rounded-2xl text-sm leading-relaxed shadow-sm',
                                msg.isBot 
                                    ? 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-bl-none border border-gray-100 dark:border-slate-700' 
                                    : 'bg-blue-600 text-white rounded-br-none font-medium'
                            ]"
                        >
                            {{ msg.text }}
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isLoading" class="flex justify-start">
                        <div class="bg-white dark:bg-slate-800 p-3 rounded-2xl rounded-bl-none shadow-sm flex gap-1">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div v-if="!isLoading" class="px-4 py-2 bg-gray-50/50 dark:bg-slate-900 overflow-x-auto whitespace-nowrap scrollbar-hide flex gap-2 border-t border-gray-100 dark:border-slate-800">
                    <button 
                        v-for="action in quickActions" 
                        :key="action.text"
                        @click="useQuickAction(action.text)"
                        class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-300 hover:border-blue-500 dark:hover:border-blue-500 transition-all flex items-center gap-1.5 active:scale-95 shadow-sm"
                    >
                        <span>{{ action.icon }}</span>
                        {{ action.text }}
                    </button>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-white dark:bg-slate-950 border-t border-gray-100 dark:border-slate-800 flex-shrink-0">
                    <form @submit.prevent="sendMessage" class="flex gap-2 relative">
                        <input 
                            v-model="newMessage"
                            type="text" 
                            placeholder="Escribe un mensaje..."
                            class="flex-1 bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white"
                            :disabled="isLoading"
                        >
                        <button 
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white w-10 h-10 rounded-full flex items-center justify-center transition-all active:scale-90 shadow-lg disabled:opacity-50"
                            :disabled="isLoading || !newMessage.trim()"
                        >
                            <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Launcher Button -->
        <button 
            @click="isOpen = !isOpen; scrollToBottom()"
            class="group relative flex items-center justify-center w-16 h-16 rounded-full shadow-[0_10px_40px_rgba(37,211,102,0.4)] transition-all duration-500 hover:scale-110 active:scale-95"
            :class="isOpen ? 'bg-slate-900 rotate-90' : 'bg-gradient-to-tr from-[#25D366] to-[#128C7E]'"
        >
            <Transition name="fade" mode="out-in">
                <div v-if="!isOpen" class="flex items-center justify-center">
                   <span class="text-3xl">🤖</span>
                </div>
                <svg v-else class="w-8 h-8 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
