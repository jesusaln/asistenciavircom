<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';

const props = defineProps({
    whatsapp: String,
    telefono: String,
    empresaNombre: String
});

const isOpen = ref(false);
const messages = ref([]);
const isTyping = ref(false);
const userInput = ref('');
const messageListRef = ref(null);

const sessionId = ref('');

const cleanPhone = computed(() => {
    const rawPhone = props.whatsapp || props.telefono || '';
    return rawPhone ? rawPhone.replace(/\D/g, '') : '';
});

// Generar o recuperar sessionId
onMounted(() => {
    let sid = localStorage.getItem('web_chatbot_session_id');
    if (!sid) {
        sid = 'web_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
        localStorage.setItem('web_chatbot_session_id', sid);
    }
    sessionId.value = sid;
});

// Observar apertura para inicializar la conversación
watch(isOpen, async (newVal) => {
    if (newVal && messages.value.length === 0) {
        await initChat();
    }
});

const initChat = async () => {
    isTyping.value = true;
    try {
        const response = await fetch('/api/chatbot/web', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                session_id: sessionId.value,
                message: 'menu' // Forzar mostrar menú principal al iniciar
            })
        });

        if (response.ok) {
            const data = await response.json();
            if (data.replies && data.replies.length > 0) {
                data.replies.forEach(reply => {
                    addMessage('bot', reply);
                });
            }
        } else {
            addMessage('bot', '🤖 Hola, ¿cómo te puedo ayudar hoy? Escribe *menu* para ver las opciones.');
        }
    } catch (e) {
        console.error('Error al inicializar el chat:', e);
        addMessage('bot', '🤖 Hola, ¿cómo te puedo ayudar hoy? Escribe *menu* para ver las opciones.');
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

const addMessage = (sender, text) => {
    messages.value.push({
        id: 'msg_' + Date.now() + '_' + Math.random().toString(36).substring(2, 5),
        sender,
        text,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });
    scrollToBottom();
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messageListRef.value) {
            messageListRef.value.scrollTop = messageListRef.value.scrollHeight;
        }
    });
};

const sendUserMessage = async (text) => {
    if (!text.trim()) return;
    
    addMessage('user', text);
    userInput.value = '';
    isTyping.value = true;
    
    try {
        const response = await fetch('/api/chatbot/web', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                session_id: sessionId.value,
                message: text
            })
        });

        if (response.ok) {
            const data = await response.json();
            if (data.replies && data.replies.length > 0) {
                data.replies.forEach(reply => {
                    addMessage('bot', reply);
                });
            } else {
                addMessage('bot', '⚠️ No recibí respuesta del asistente. Escribe *menu* para intentar de nuevo.');
            }
        } else {
            addMessage('bot', '❌ Error al procesar tu solicitud. Intenta de nuevo.');
        }
    } catch (e) {
        console.error('Error al enviar mensaje:', e);
        addMessage('bot', '❌ Error de red. Comprueba tu conexión.');
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

// Limpiar chat/sesión para empezar de nuevo
const resetChat = async () => {
    messages.value = [];
    isTyping.value = true;
    
    // Generar nueva sesión para reiniciar estado en el servidor
    const sid = 'web_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
    localStorage.setItem('web_chatbot_session_id', sid);
    sessionId.value = sid;
    
    await initChat();
};

// Abre enlace de WhatsApp con mensaje personalizado
const openWhatsAppDirect = () => {
    const url = `https://wa.me/${cleanPhone.value}?text=Hola, vengo de la página web y me gustaría hablar con un asesor.`;
    window.open(url, '_blank');
};

// Parsea las opciones numéricas del mensaje del bot
const parseBotMessage = (text) => {
    if (!text) return { body: '', options: [] };
    const lines = text.split('\n');
    const bodyLines = [];
    const options = [];
    
    const emojiMap = {
        '1️⃣': '1', '2️⃣': '2', '3️⃣': '3', '4️⃣': '4', '5️⃣': '5',
        '6️⃣': '6', '7️⃣': '7', '8️⃣': '8', '9️⃣': '9', '0️⃣': '0'
    };
    
    for (let line of lines) {
        let matched = false;
        const trimmedLine = line.trim();
        for (let [emoji, digit] of Object.entries(emojiMap)) {
            if (trimmedLine.startsWith(emoji)) {
                const label = trimmedLine.replace(emoji, '').trim();
                options.push({ label: `${emoji} ${label}`, value: digit });
                matched = true;
                break;
            }
        }
        if (!matched) {
            bodyLines.push(line);
        }
    }
    
    if (options.length > 0) {
        return {
            body: bodyLines.join('\n').trim(),
            options: options
        };
    }
    
    return {
        body: text,
        options: []
    };
};

const formatMessageText = (text) => {
    if (!text) return '';
    let html = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
    return html;
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end">
        <!-- Chat Window -->
        <Transition name="pop">
            <div v-if="isOpen" class="mb-4 w-80 sm:w-[360px] h-[500px] bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-br from-[#25D366] to-[#128C7E] p-4 text-white flex items-center justify-between shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl shadow-inner">
                                🌵
                            </div>
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-emerald-600 rounded-full animate-pulse"></span>
                        </div>
                        <div class="text-left">
                            <h3 class="text-sm font-black tracking-wide">Asistente Virtual</h3>
                            <p class="text-[10px] opacity-80 font-medium">Climas del Desierto 24/7</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Reset Button -->
                        <button 
                            @click="resetChat" 
                            title="Reiniciar chat"
                            class="p-2 rounded-full hover:bg-white/10 active:bg-white/20 transition-colors text-white/90"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" />
                            </svg>
                        </button>
                        
                        <!-- Close Button -->
                        <button 
                            @click="isOpen = false" 
                            class="p-2 rounded-full hover:bg-white/10 active:bg-white/20 transition-colors"
                        >
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div 
                    ref="messageListRef" 
                    class="flex-1 overflow-y-auto p-4 bg-slate-50/50 space-y-4 text-left"
                >
                    <div 
                        v-for="msg in messages" 
                        :key="msg.id" 
                        class="flex flex-col"
                        :class="msg.sender === 'user' ? 'items-end' : 'items-start'"
                    >
                        <!-- Bubble -->
                        <div 
                            class="max-w-[85%] rounded-2xl px-4 py-2.5 shadow-sm text-sm"
                            :class="msg.sender === 'user' 
                                ? 'bg-gradient-to-br from-[#25D366] to-[#128C7E] text-white rounded-tr-none' 
                                : 'bg-white text-slate-800 border border-slate-100 rounded-tl-none'"
                        >
                            <!-- Message Content -->
                            <div 
                                v-html="formatMessageText(parseBotMessage(msg.text).body)"
                                class="leading-relaxed"
                            ></div>
                            
                            <!-- Timestamp -->
                            <p 
                                class="text-[9px] mt-1 text-right"
                                :class="msg.sender === 'user' ? 'text-white/70' : 'text-slate-400'"
                            >
                                {{ msg.timestamp }}
                            </p>
                        </div>

                        <!-- Buttons/Options (Only for Bot Messages) -->
                        <div 
                            v-if="msg.sender === 'bot' && parseBotMessage(msg.text).options.length > 0"
                            class="mt-2 w-full max-w-[85%] space-y-1.5"
                        >
                            <button 
                                v-for="opt in parseBotMessage(msg.text).options" 
                                :key="opt.value"
                                @click="sendUserMessage(opt.value)"
                                class="w-full text-left px-4 py-2 bg-white hover:bg-emerald-50/50 active:bg-emerald-50 text-emerald-700 hover:text-emerald-800 border border-emerald-100 hover:border-emerald-200 hover:shadow-md active:shadow-sm font-bold text-xs rounded-xl transition-all duration-300 flex items-center gap-2 group"
                            >
                                <span class="group-hover:scale-105 transition-transform">{{ opt.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="flex flex-col items-start">
                        <div class="bg-white border border-slate-100 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></span>
                                <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                                <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Footer -->
                <div class="p-3 bg-white border-t border-slate-100 flex flex-col gap-2 shadow-inner">
                    <!-- WhatsApp Redirect Button -->
                    <button 
                        @click="openWhatsAppDirect"
                        class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] uppercase tracking-wider rounded-xl transition-colors flex items-center justify-center gap-2 border border-emerald-200/50"
                    >
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Hablar con un asesor por WhatsApp
                    </button>

                    <!-- Text input and Send button -->
                    <form @submit.prevent="sendUserMessage(userInput)" class="flex items-center gap-2">
                        <input 
                            v-model="userInput" 
                            type="text" 
                            placeholder="Escribe tu respuesta aquí..." 
                            class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-full text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366] transition-all"
                            required
                        />
                        <button 
                            type="submit" 
                            class="w-8 h-8 rounded-full bg-[#25D366] hover:bg-[#128C7E] text-white flex items-center justify-center shadow-md active:scale-95 hover:scale-105 transition-transform"
                        >
                            <svg class="w-4 h-4 transform rotate-90 translate-x-[1px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Main Button -->
        <button 
            @click="isOpen = !isOpen"
            class="group relative flex items-center justify-center w-16 h-16 rounded-full shadow-2xl transition-all duration-500 hover:scale-110 active:scale-95"
            :class="isOpen ? 'bg-slate-900 rotate-90' : 'bg-[#25D366]'"
        >
            <!-- Badge Notification -->
            <div v-if="!isOpen" class="absolute -top-1 -right-1 flex h-6 w-6">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-6 w-6 bg-rose-500 text-[10px] text-white font-bold items-center justify-center">1</span>
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
