<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object
});

const page = usePage();
const isOpen = ref(false);
const isMinimized = ref(false); // Para el botón flotante
const messages = ref([]);
const inputMessage = ref('');
const isLoading = ref(false);
const chatContainer = ref(null);
const sessionId = ref(`session_${Date.now()}`);

// Configuración visual (colores de la empresa)
const primaryColor = ref(page.props.empresa_config?.color_principal || '#F59E0B');

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        nextTick(() => scrollToBottom());
        if (messages.value.length === 0) {
            // Mensaje de bienvenida inicial
            messages.value.push({
                role: 'assistant',
                content: '¡Hola! Soy VircomBot 🤖. ¿En qué puedo ayudarte hoy? Puedo agendar citas, consultar precios o verificar el estado de tus reparaciones.'
            });
        }
    }
};

const scrollToBottom = () => {
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const sendMessage = async () => {
    if (!inputMessage.value.trim() || isLoading.value) return;

    const userMsg = inputMessage.value;
    messages.value.push({ role: 'user', content: userMsg });
    inputMessage.value = '';
    isLoading.value = true;
    
    nextTick(() => scrollToBottom());

    try {
        const response = await axios.post('/api/webhooks/chat', {
            message: userMsg,
            session_id: sessionId.value
        });

        const botResponse = response.data.message;
        
        // Si la IA ejecutó una acción (ej. agendar cita), mostrar un mensaje especial o toast (opcional)
        // if (response.data.action_taken === 'agendar_cita') { ... }

        messages.value.push({ role: 'assistant', content: botResponse });

    } catch (error) {
        console.error('Error enviando mensaje:', error);
        messages.value.push({ 
            role: 'assistant', 
            content: 'Lo siento, tuve un problema conexión. Por favor intenta de nuevo más tarde.' 
        });
    } finally {
        isLoading.value = false;
        nextTick(() => scrollToBottom());
    }
};

// Formato de hora para los mensajes
const formatTime = () => {
    return new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 font-sans flex flex-col items-end">
        
        <!-- Ventana de Chat -->
        <transition 
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="opacity-0 translate-y-10 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-10 scale-95"
        >
            <div v-if="isOpen" class="bg-white dark:bg-slate-900 dark:bg-gray-800 w-[350px] sm:w-[380px] h-[500px] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-100 dark:border-slate-800 dark:border-gray-700 mb-4">
                
                <!-- Header -->
                <div class="p-4 flex justify-between items-center shadow-sm" :style="{ backgroundColor: primaryColor }">
                    <div class="flex items-center gap-3">
                        <div class="bg-white dark:bg-slate-900 p-1.5 rounded-full shadow-sm">
                            <!-- Icono de Robot o Logo -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-base leading-none">Asistente Virtual</h3>
                            <span class="text-xs text-white/80 flex items-center gap-1 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                En línea
                            </span>
                        </div>
                    </div>
                    <button @click="toggleChat" class="text-white/80 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Messages Area -->
                <div ref="chatContainer" class="flex-grow overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900/50 scroll-smooth">
                    <div v-for="(msg, idx) in messages" :key="idx" 
                        class="flex w-full" 
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div 
                            class="max-w-[85%] rounded-2xl p-3 text-sm shadow-sm relative group"
                            :class="[
                                msg.role === 'user' 
                                    ? 'bg-indigo-600 text-white rounded-br-none' 
                                    : 'bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-bl-none border border-gray-100 dark:border-slate-800 dark:border-gray-600'
                            ]"
                            :style="msg.role === 'user' ? { backgroundColor: primaryColor } : {}"
                        >
                            <p class="whitespace-pre-line leading-relaxed">{{ msg.content }}</p>
                            <span class="text-[10px] opacity-70 block text-right mt-1 w-full">{{ formatTime() }}</span>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isLoading" class="flex justify-start w-full">
                        <div class="bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-2xl rounded-bl-none p-4 shadow-sm border border-gray-100 dark:border-slate-800 dark:border-gray-600 flex gap-1 items-center h-10">
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-white dark:bg-slate-900 dark:bg-gray-800 border-t border-gray-100 dark:border-slate-800 dark:border-gray-700">
                    <form @submit.prevent="sendMessage" class="relative items-end w-full flex gap-2">
                        <input 
                            v-model="inputMessage"
                            type="text" 
                            placeholder="Escribe tu mensaje..." 
                            class="w-full pl-4 pr-12 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border-0 focus:ring-2 focus:ring-indigo-100 dark:focus:ring-indigo-900 transition-all text-sm dark:text-white"
                        >
                        <button 
                            type="submit"
                            :disabled="!inputMessage.trim() || isLoading"
                            class="absolute right-2 top-1.5 p-1.5 rounded-lg transition-all"
                            :class="inputMessage.trim() ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md transform hover:scale-105' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                            :style="inputMessage.trim() ? { backgroundColor: primaryColor } : {}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </form>
                    <div class="text-center mt-2">
                        <p class="text-[10px] text-gray-400">Powered by <b>Vircom AI</b></p>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Botón Flotante -->
        <button 
            @click="toggleChat"
            class="group relative flex items-center justify-center w-14 h-14 rounded-full shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-110 z-50 text-white"
            :style="{ backgroundColor: primaryColor }"
        >
            <span v-if="!isOpen" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
            
            <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>

            <!-- Tooltip -->
            <span class="absolute right-16 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                ¿Necesitas ayuda?
            </span>
        </button>

    </div>
</template>
