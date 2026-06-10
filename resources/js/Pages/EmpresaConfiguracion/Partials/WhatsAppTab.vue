<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="comments" class="text-emerald-500 dark:text-slate-400" />
                Configuración de WhatsApp API
            </h2>

            <div class="flex items-center mb-6">
                 <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.whatsapp_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    <span class="ml-3 text-sm font-medium text-slate-900 dark:text-slate-100">Habilitar integración con WhatsApp</span>
                </label>
            </div>

            <div v-if="form.whatsapp_enabled" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Business Account ID -->
                     <div>
                        <label for="whatsapp_business_account_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Business Account ID</label>
                        <input v-model="form.whatsapp_business_account_id" id="whatsapp_business_account_id" type="text"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all font-mono text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" />
                    </div>

                    <!-- Phone Number ID -->
                     <div>
                        <label for="whatsapp_phone_number_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Phone Number ID</label>
                        <input v-model="form.whatsapp_phone_number_id" id="whatsapp_phone_number_id" type="text"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all font-mono text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" />
                    </div>

                    <!-- Sender Phone -->
                     <div>
                        <label for="whatsapp_sender_phone" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Número de Teléfono (con lada)</label>
                        <input v-model="form.whatsapp_sender_phone" id="whatsapp_sender_phone" type="text" placeholder="+521..."
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all font-mono text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" />
                    </div>

                    <!-- Access Token -->
                    <div class="md:col-span-2">
                        <label for="whatsapp_access_token" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Access Token (Permanente)</label>
                        <textarea v-model="form.whatsapp_access_token" id="whatsapp_access_token" rows="2"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all font-mono text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="EAAG..."></textarea>
                    </div>

                     <!-- Verify Token -->
                     <div>
                        <label for="whatsapp_webhook_verify_token" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Verify Token (Webhook)</label>
                        <input v-model="form.whatsapp_webhook_verify_token" id="whatsapp_webhook_verify_token" type="text"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all font-mono text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" />
                    </div>
                </div>
                
                <!-- Botón de prueba (Solo UI, lógica simplificada) -->
                 <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700 rounded-xl flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">Prueba de conexión</h4>
                        <p class="text-xs text-emerald-600 dark:text-slate-400">Envía un mensaje de prueba al número configurado.</p>
                    </div>
                    <button type="button" @click="probarWhatsApp"
                        class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-xl hover:bg-emerald-700 transition-colors">
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
        const response = await fetch(route('whatsapp.test'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                telefono: props.form.whatsapp_sender_phone,
                mensaje: 'Mensaje de prueba - Configuración de WhatsApp funcionando',
                template_name: props.form.whatsapp_template_payment_reminder || 'hello_world'
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
