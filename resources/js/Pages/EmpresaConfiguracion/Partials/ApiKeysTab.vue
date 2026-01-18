<script setup>
import { ref, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    form: { type: Object, required: true }
})

const showGroqKey = ref(false)
const testingConnection = ref(false)
const connectionStatus = ref(null)

const aiProviders = [
    { id: 'groq', name: 'Groq Cloud', description: 'API rápida y gratuita (Recomendado)', icon: 'bolt' },
    { id: 'ollama', name: 'Ollama Local', description: 'Ejecuta modelos localmente', icon: 'server' },
]

const groqModels = [
    { id: 'llama-3.3-70b-versatile', name: 'Llama 3.3 70B', description: 'Mejor calidad, ideal para function calling' },
    { id: 'llama-3.1-8b-instant', name: 'Llama 3.1 8B', description: 'Más rápido, menor calidad' },
    { id: 'mixtral-8x7b-32768', name: 'Mixtral 8x7B', description: 'Buen balance calidad/velocidad' },
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
    <div class="space-y-8">
        <!-- Header -->
        <div class="border-b border-gray-100 pb-6">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <FontAwesomeIcon icon="robot" class="text-purple-600" />
                </div>
                Inteligencia Artificial (VircomBot)
            </h2>
            <p class="mt-2 text-gray-500">Configura el asistente virtual impulsado por IA para atención a clientes</p>
        </div>

        <!-- Chatbot Toggle -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6 border border-purple-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white rounded-xl shadow-sm">
                        <FontAwesomeIcon icon="comments" class="text-purple-600 text-xl" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">VircomBot</h3>
                        <p class="text-sm text-gray-500">Asistente virtual en el portal de clientes</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.chatbot_enabled" class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
            </div>
        </div>

        <!-- AI Provider Selection -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-3">Proveedor de IA</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button
                    v-for="provider in aiProviders"
                    :key="provider.id"
                    type="button"
                    @click="form.ai_provider = provider.id"
                    :class="[
                        'relative p-4 rounded-xl border-2 text-left transition-all duration-200',
                        form.ai_provider === provider.id 
                            ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-100' 
                            : 'border-gray-200 hover:border-purple-200 hover:bg-gray-50'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div :class="[
                            'p-2 rounded-lg',
                            form.ai_provider === provider.id ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-500'
                        ]">
                            <FontAwesomeIcon :icon="provider.icon" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ provider.name }}</p>
                            <p class="text-xs text-gray-500">{{ provider.description }}</p>
                        </div>
                    </div>
                    <div v-if="form.ai_provider === provider.id" class="absolute top-2 right-2">
                        <FontAwesomeIcon icon="check-circle" class="text-purple-600" />
                    </div>
                </button>
            </div>
        </div>

        <!-- Groq Configuration -->
        <div v-if="form.ai_provider === 'groq'" class="space-y-6 bg-gray-50 rounded-xl p-6">
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <FontAwesomeIcon icon="bolt" class="text-yellow-500" />
                Configuración de Groq
            </h3>

            <!-- API Key -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    API Key de Groq
                    <a href="https://console.groq.com/keys" target="_blank" class="text-purple-600 hover:underline ml-2 text-xs">
                        <FontAwesomeIcon icon="external-link-alt" /> Obtener API Key
                    </a>
                </label>
                <div class="relative">
                    <input 
                        :type="showGroqKey ? 'text' : 'password'"
                        v-model="form.groq_api_key"
                        placeholder="gsk_xxxxxxxxxxxxxxxxxxxxxxxx"
                        class="w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-100 focus:border-purple-500 transition-all"
                    >
                    <button 
                        type="button" 
                        @click="showGroqKey = !showGroqKey"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    >
                        <FontAwesomeIcon :icon="showGroqKey ? 'eye-slash' : 'eye'" />
                    </button>
                </div>
            </div>

            <!-- Model Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                <select 
                    v-model="form.groq_model"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-100 focus:border-purple-500 transition-all"
                >
                    <option v-for="model in groqModels" :key="model.id" :value="model.id">
                        {{ model.name }} - {{ model.description }}
                    </option>
                </select>
            </div>

            <!-- Temperature -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Temperatura: {{ form.groq_temperature }}
                </label>
                <input 
                    type="range" 
                    v-model="form.groq_temperature" 
                    min="0" 
                    max="1" 
                    step="0.1"
                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600"
                >
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Preciso (0)</span>
                    <span>Creativo (1)</span>
                </div>
            </div>
        </div>

        <!-- Ollama Configuration -->
        <div v-if="form.ai_provider === 'ollama'" class="space-y-6 bg-gray-50 rounded-xl p-6">
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <FontAwesomeIcon icon="server" class="text-blue-500" />
                Configuración de Ollama
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Base</label>
                    <input 
                        type="text"
                        v-model="form.ollama_base_url"
                        placeholder="http://localhost:11434"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-100 focus:border-purple-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                    <input 
                        type="text"
                        v-model="form.ollama_model"
                        placeholder="llama3.1:8b"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-100 focus:border-purple-500"
                    >
                </div>
            </div>
        </div>

        <!-- Chatbot Personality -->
        <div class="space-y-4">
            <h3 class="font-semibold text-gray-900">Personalidad del Bot</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Bot</label>
                <input 
                    type="text"
                    v-model="form.chatbot_name"
                    placeholder="VircomBot"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-100 focus:border-purple-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Prompt del Sistema (Opcional)
                    <span class="text-gray-400 font-normal">- Define la personalidad del bot</span>
                </label>
                <textarea 
                    v-model="form.chatbot_system_prompt"
                    rows="4"
                    placeholder="Eres un asistente profesional y amable. Tu objetivo es ayudar a los clientes a agendar citas y resolver sus dudas..."
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-100 focus:border-purple-500"
                ></textarea>
            </div>
        </div>

        <!-- Test Connection -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">Probar Conexión</h3>
                    <p class="text-sm text-gray-500">Verifica que el servicio de IA esté funcionando correctamente</p>
                </div>
                <button
                    type="button"
                    @click="testConnection"
                    :disabled="testingConnection"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-all flex items-center gap-2"
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
                        'p-4 rounded-lg flex items-center gap-3',
                        connectionStatus.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
                    ]"
                >
                    <FontAwesomeIcon :icon="connectionStatus.success ? 'check-circle' : 'exclamation-circle'" />
                    {{ connectionStatus.message }}
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-6">
            <div class="flex gap-4">
                <div class="text-blue-600">
                    <FontAwesomeIcon icon="info-circle" size="lg" />
                </div>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-2">Capacidades del Bot:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Agendar citas automáticamente</li>
                        <li>Consultar disponibilidad de horarios</li>
                        <li>Buscar precios de servicios</li>
                        <li>Verificar estado de reparaciones por folio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
