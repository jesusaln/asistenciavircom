<script setup>
import { ref, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    form: { type: Object, required: true }
})

const showGroqKey = ref(false)
const showGeminiKey = ref(false)
const testingConnection = ref(false)
const connectionStatus = ref(null)

const aiProviders = [
    { id: 'gemini', name: 'Google Gemini', description: 'API potente de Google AI (Recomendado)', icon: 'brain' },
    { id: 'groq', name: 'Groq Cloud', description: 'API rápida y gratuita con modelos open source', icon: 'bolt' },
    { id: 'ollama', name: 'Ollama Local', description: 'Ejecuta modelos localmente', icon: 'server' },
]

const groqModels = [
    { id: 'llama-3.3-70b-versatile', name: 'Llama 3.3 70B', description: 'Mejor calidad, ideal para function calling' },
    { id: 'llama-3.1-8b-instant', name: 'Llama 3.1 8B', description: 'Más rápido, menor calidad' },
    { id: 'mixtral-8x7b-32768', name: 'Mixtral 8x7B', description: 'Buen balance calidad/velocidad' },
]

const geminiModels = [
    { id: 'gemini-2.0-flash', name: 'Gemini 2.0 Flash', description: 'Más rápido, ideal para chatbot (Recomendado)' },
    { id: 'gemini-2.0-pro', name: 'Gemini 2.0 Pro', description: 'Mayor calidad, razonamiento avanzado' },
    { id: 'gemini-1.5-flash', name: 'Gemini 1.5 Flash', description: 'Rápido y económico' },
    { id: 'gemini-1.5-pro', name: 'Gemini 1.5 Pro', description: 'Contexto largo (1M tokens)' },
]

const testConnection = async () => {
    testingConnection.value = true
    connectionStatus.value = null
    
    try {
        const response = await fetch('/api/webhooks/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                message: 'Hola, responde solo: OK',
                session_id: 'test_connection_' + Date.now()
            })
        })
        
        const data = await response.json()
        
        if (data.message) {
            connectionStatus.value = { success: true, message: 'Conexión exitosa con el servicio de IA' }
        } else {
            connectionStatus.value = { success: false, message: data.error || 'Error desconocido' }
        }
    } catch (error) {
        connectionStatus.value = { success: false, message: 'Error de conexión: ' + error.message }
    } finally {
        testingConnection.value = false
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="border-b border-slate-100 dark:border-slate-700 pb-6">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/40 rounded-xl">
                    <FontAwesomeIcon icon="key" class="text-purple-600 dark:text-purple-400" />
                </div>
                API Keys & Integraciones
            </h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400">Administra las llaves de acceso para servicios externos y marketing</p>
        </div>

        <!-- Meta / WhatsApp Section -->
        <div class="bg-sky-50 dark:bg-sky-900/20/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/50 rounded-xl p-6 transition-all">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 bg-blue-50 dark:bg-sky-900/20/40 rounded-2xl shadow-sm">
                    <FontAwesomeIcon icon="comments" class="text-blue-600 dark:text-blue-400 text-xl" />
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">WhatsApp Business Platform (Meta)</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Requerido para el envío de campañas de marketing y notificaciones</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">WhatsApp Business Account ID</label>
                    <input 
                        type="text"
                        v-model="form.whatsapp_business_account_id"
                        placeholder="123456789012345"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                    >
                    <p class="mt-1 text-xs text-slate-500">Usa este ID para listar plantillas de Meta</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Phone Number ID</label>
                    <input 
                        type="text"
                        v-model="form.whatsapp_phone_number_id"
                        placeholder="123456789012345"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                    >
                    <p class="mt-1 text-xs text-slate-500">ID del número desde el que se envían mensajes</p>
                </div>
            </div>

            <!-- Warning about sensitive keys -->
            <div class="mt-4 flex items-center gap-2 text-xs text-brand-600 dark:text-amber-400">
                <FontAwesomeIcon icon="exclamation-triangle" />
                <span>Configura el token completo y configuración avanzada en el tab de <b>WhatsApp Business</b></span>
            </div>
        </div>

        <!-- Chatbot Toggle -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-purple-100 dark:border-purple-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white dark:bg-slate-700 rounded-2xl shadow-sm">
                        <FontAwesomeIcon icon="comments" class="text-purple-600 dark:text-purple-400 text-xl" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">VircomBot</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Asistente virtual en el portal de clientes</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.chatbot_enabled" class="sr-only peer">
                    <div class="w-14 h-7 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-amber-600"></div>
                </label>
            </div>
        </div>

        <!-- AI Provider Selection -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-3">Proveedor de IA</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button
                    v-for="provider in aiProviders"
                    :key="provider.id"
                    type="button"
                    @click="form.ai_provider = provider.id"
                    :class="[
                        'relative p-4 rounded-xl border-2 text-left transition-all duration-200',
                        form.ai_provider === provider.id 
                            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/40 ring-2 ring-purple-100 dark:ring-purple-800/40' 
                            : 'border-slate-200 dark:border-slate-700 hover:border-brand-200 dark:border-brand-800/30 dark:hover:border-brand-500 hover:bg-slate-50 dark:hover:bg-slate-700'
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <div :class="[
                            'p-2 rounded-xl',
                            form.ai_provider === provider.id ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                        ]">
                            <FontAwesomeIcon :icon="provider.icon" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ provider.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ provider.description }}</p>
                        </div>
                    </div>
                    <div v-if="form.ai_provider === provider.id" class="absolute top-2 right-2">
                        <FontAwesomeIcon icon="check-circle" class="text-purple-600 dark:text-purple-400" />
                    </div>
                </button>
            </div>
        </div>

        <!-- Gemini Configuration -->
        <div v-if="form.ai_provider === 'gemini'" class="space-y-6 bg-[var(--ui-surface)] dark:bg-slate-800 rounded-xl p-6">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <FontAwesomeIcon icon="brain" class="text-blue-500 dark:text-blue-400" />
                Configuración de Google Gemini
            </h3>

            <!-- API Key -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    API Key de Gemini
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline ml-2 text-xs">
                        <FontAwesomeIcon icon="external-link-alt" /> Obtener API Key
                    </a>
                </label>
                <div class="relative">
                    <input 
                        :type="showGeminiKey ? 'text' : 'password'"
                        v-model="form.gemini_api_key"
                        placeholder="AIzaSyXXXXXXXXXXXXXXXXXXXXXXX"
                        class="w-full px-4 py-3 pr-12 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                    >
                    <button 
                        type="button" 
                        @click="showGeminiKey = !showGeminiKey"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-slate-400"
                    >
                        <FontAwesomeIcon :icon="showGeminiKey ? 'eye-slash' : 'eye'" />
                    </button>
                </div>
            </div>

            <!-- Model Selection -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Modelo</label>
                <select 
                    v-model="form.gemini_model"
                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                >
                    <option v-for="model in geminiModels" :key="model.id" :value="model.id">
                        {{ model.name }} - {{ model.description }}
                    </option>
                </select>
            </div>

            <!-- Temperature -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    Temperatura: {{ form.gemini_temperature }}
                </label>
                <input 
                    type="range" 
                    v-model="form.gemini_temperature" 
                    min="0" 
                    max="1" 
                    step="0.1"
                    class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-xl appearance-none cursor-pointer accent-blue-600 dark:accent-blue-500"
                >
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mt-1">
                    <span>Preciso (0)</span>
                    <span>Creativo (1)</span>
                </div>
            </div>

            <!-- Gemini Info -->
            <div class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border border-blue-100 dark:border-blue-700 rounded-xl p-4">
                <div class="flex gap-3 text-sm text-sky-800 dark:text-sky-200 dark:text-blue-300">
                    <FontAwesomeIcon icon="info-circle" class="mt-0.5" />
                    <div>
                        <p class="font-medium">Google Gemini</p>
                        <p class="mt-1">Gemini 2.0 Flash es ideal para chatbot con WhatsApp: rápido, económico y con soporte nativo de Function Calling para agendar citas y consultar precios.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Groq Configuration -->
        <div v-if="form.ai_provider === 'groq'" class="space-y-6 bg-[var(--ui-surface)] dark:bg-slate-800 rounded-xl p-6">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <FontAwesomeIcon icon="bolt" class="text-brand-500 dark:text-amber-400" />
                Configuración de Groq
            </h3>

            <!-- API Key -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    API Key de Groq
                    <a href="https://console.groq.com/keys" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline ml-2 text-xs">
                        <FontAwesomeIcon icon="external-link-alt" /> Obtener API Key
                    </a>
                </label>
                <div class="relative">
                    <input 
                        :type="showGroqKey ? 'text' : 'password'"
                        v-model="form.groq_api_key"
                        placeholder="gsk_xxxxxxxxxxxxxxxxxxxxxxxx"
                        class="w-full px-4 py-3 pr-12 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                    >
                    <button 
                        type="button" 
                        @click="showGroqKey = !showGroqKey"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-slate-400"
                    >
                        <FontAwesomeIcon :icon="showGroqKey ? 'eye-slash' : 'eye'" />
                    </button>
                </div>
            </div>

            <!-- Model Selection -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Modelo</label>
                <select 
                    v-model="form.groq_model"
                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                >
                    <option v-for="model in groqModels" :key="model.id" :value="model.id">
                        {{ model.name }} - {{ model.description }}
                    </option>
                </select>
            </div>

            <!-- Temperature -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    Temperatura: {{ form.groq_temperature }}
                </label>
                <input 
                    type="range" 
                    v-model="form.groq_temperature" 
                    min="0" 
                    max="1" 
                    step="0.1"
                    class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-xl appearance-none cursor-pointer accent-purple-600 dark:accent-purple-500"
                >
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mt-1">
                    <span>Preciso (0)</span>
                    <span>Creativo (1)</span>
                </div>
            </div>
        </div>

        <!-- Ollama Configuration -->
        <div v-if="form.ai_provider === 'ollama'" class="space-y-6 bg-[var(--ui-surface)] dark:bg-slate-800 rounded-xl p-6">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <FontAwesomeIcon icon="server" class="text-blue-500 dark:text-blue-400" />
                Configuración de Ollama
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">URL Base</label>
                    <input 
                        type="text"
                        v-model="form.ollama_base_url"
                        placeholder="http://localhost:11434"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Modelo</label>
                    <input 
                        type="text"
                        v-model="form.ollama_model"
                        placeholder="llama3.1:8b"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                    >
                </div>
            </div>
        </div>

        <!-- Chatbot Personality -->
        <div class="space-y-6">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100">Personalidad del Bot</h3>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Nombre del Bot</label>
                <input 
                    type="text"
                    v-model="form.chatbot_name"
                    placeholder="VircomBot"
                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    Prompt del Sistema (Opcional)
                    <span class="text-slate-400 dark:text-slate-500 font-normal">- Define la personalidad del bot</span>
                </label>
                <textarea 
                    v-model="form.chatbot_system_prompt"
                    rows="4"
                    placeholder="Eres un asistente profesional y amable. Tu objetivo es ayudar a los clientes a agendar citas y resolver sus dudas..."
                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                ></textarea>
            </div>
        </div>

        <!-- Test Connection -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">Probar Conexión</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Verifica que el servicio de IA esté funcionando correctamente</p>
                </div>
                <button
                    type="button"
                    @click="testConnection"
                    :disabled="testingConnection"
                    class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 disabled:opacity-50 transition-all flex items-center gap-2"
                >
                    <FontAwesomeIcon v-if="testingConnection" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="flask" />
                    {{ testingConnection ? 'Probando...' : 'Probar' }}
                </button>
            </div>

            <!-- Connection Status -->
            <div v-if="connectionStatus" class="mt-4">
                <div 
                    :class="[
                        'p-4 rounded-xl flex items-center gap-2',
                        connectionStatus.success ? 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300'
                    ]"
                >
                    <FontAwesomeIcon :icon="connectionStatus.success ? 'check-circle' : 'exclamation-circle'" />
                    {{ connectionStatus.message }}
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border border-blue-100 dark:border-blue-700 rounded-xl p-6">
            <div class="flex gap-4">
                <div class="text-blue-600 dark:text-blue-400">
                    <FontAwesomeIcon icon="info-circle" size="lg" />
                </div>
                <div class="text-sm text-sky-800 dark:text-sky-200 dark:text-blue-300">
                    <p class="font-semibold mb-2">Capacidades del Bot:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Agendar citas automáticamente</li>
                        <li>Consultar disponibilidad de horarios</li>
                        <li>Buscar precios de servicios</li>
                        <li>Verificar estado de reparaciones por folio</li>
                        <li>Consultar saldo de pólizas de servicio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
