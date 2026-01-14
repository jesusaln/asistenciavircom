<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="comments" class="text-green-500" />
                Configuración de WhatsApp API
            </h2>

            <div class="flex items-center mb-6">
                 <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.whatsapp_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900">Habilitar integración con WhatsApp</span>
                </label>
            </div>

            <div v-if="form.whatsapp_enabled" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Business Account ID -->
                     <div>
                        <label for="whatsapp_business_account_id" class="block text-sm font-medium text-gray-700 mb-2">Business Account ID</label>
                        <input v-model="form.whatsapp_business_account_id" id="whatsapp_business_account_id" type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition-all font-mono text-sm" />
                    </div>

                    <!-- Phone Number ID -->
                     <div>
                        <label for="whatsapp_phone_number_id" class="block text-sm font-medium text-gray-700 mb-2">Phone Number ID</label>
                        <input v-model="form.whatsapp_phone_number_id" id="whatsapp_phone_number_id" type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition-all font-mono text-sm" />
                    </div>

                    <!-- Sender Phone -->
                     <div>
                        <label for="whatsapp_sender_phone" class="block text-sm font-medium text-gray-700 mb-2">Número de Teléfono (con lada)</label>
                        <input v-model="form.whatsapp_sender_phone" id="whatsapp_sender_phone" type="text" placeholder="+521..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition-all font-mono text-sm" />
                    </div>

                    <!-- Access Token -->
                    <div class="md:col-span-2">
                        <label for="whatsapp_access_token" class="block text-sm font-medium text-gray-700 mb-2">Access Token (Permanente)</label>
                        <textarea v-model="form.whatsapp_access_token" id="whatsapp_access_token" rows="2"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition-all font-mono text-sm" placeholder="EAAG..."></textarea>
                    </div>

                     <!-- Verify Token -->
                     <div>
                        <label for="whatsapp_webhook_verify_token" class="block text-sm font-medium text-gray-700 mb-2">Verify Token (Webhook)</label>
                        <input v-model="form.whatsapp_webhook_verify_token" id="whatsapp_webhook_verify_token" type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition-all font-mono text-sm" />
                    </div>
                </div>
                
                <!-- Botón de prueba (Solo UI, lógica simplificada) -->
                 <div class="p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-green-800">Prueba de conexión</h4>
                        <p class="text-xs text-green-600">Envía un mensaje de prueba al número configurado.</p>
                    </div>
                    <button type="button" @click="probarWhatsApp"
                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                        Enviar Prueba
                    </button>
                 </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { notyf } from '@/Utils/notyf.js';

const props = defineProps({
    form: { type: Object, required: true },
});

const probarWhatsApp = async () => {
    if (!props.form.whatsapp_sender_phone) {
        notyf.error('Configura el número de teléfono primero');
        return;
    }
    
    try {
        const response = await fetch('/api/whatsapp/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                telefono: props.form.whatsapp_sender_phone,
                mensaje: 'Mensaje de prueba - Configuración de WhatsApp funcionando'
            })
        });

        const data = await response.json();
        if (response.ok && data.success) {
            notyf.success('Mensaje de prueba enviado');
        } else {
            notyf.error('Error: ' + (data.message || 'Desconocido'));
        }
    } catch (e) {
        notyf.error('Error de conexión');
    }
};
</script>

