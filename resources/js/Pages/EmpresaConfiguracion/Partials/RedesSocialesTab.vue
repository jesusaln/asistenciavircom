<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    form: Object,
    processing: Boolean,
});

const emit = defineEmits(['update:form']);

const updateField = (field, value) => {
    emit('update:form', { ...props.form, [field]: value });
};

const socialNetworks = [
    { 
        key: 'facebook_url', 
        label: 'Facebook', 
        placeholder: 'https://facebook.com/tuempresa',
        icon: '📘',
        color: 'bg-blue-600'
    },
    { 
        key: 'instagram_url', 
        label: 'Instagram', 
        placeholder: 'https://instagram.com/tuempresa',
        icon: '📸',
        color: 'bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600'
    },
    { 
        key: 'twitter_url', 
        label: 'X (Twitter)', 
        placeholder: 'https://x.com/tuempresa',
        icon: '🐦',
        color: 'bg-black'
    },
    { 
        key: 'tiktok_url', 
        label: 'TikTok', 
        placeholder: 'https://tiktok.com/@tuempresa',
        icon: '🎵',
        color: 'bg-black'
    },
    { 
        key: 'youtube_url', 
        label: 'YouTube', 
        placeholder: 'https://youtube.com/c/tuempresa',
        icon: '🎬',
        color: 'bg-red-600'
    },
    { 
        key: 'linkedin_url', 
        label: 'LinkedIn', 
        placeholder: 'https://linkedin.com/company/tuempresa',
        icon: '💼',
        color: 'bg-blue-700'
    },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-100 dark:border-purple-700">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center text-2xl">
                    📱
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Redes Sociales</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Configura los enlaces a tus perfiles de redes sociales. Se mostrarán en el footer de tu sitio web.</p>
                </div>
            </div>
        </div>

        <!-- Social Networks Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
                v-for="network in socialNetworks" 
                :key="network.key"
                class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 transition-all"
            >
                <div class="flex items-center gap-3 mb-4">
                    <div :class="[network.color, 'w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg']">
                        {{ network.icon }}
                    </div>
                    <label class="font-semibold text-gray-900 dark:text-gray-100">{{ network.label }}</label>
                </div>
                
                <input
                    type="url"
                    :value="form[network.key]"
                    @input="updateField(network.key, $event.target.value)"
                    :placeholder="network.placeholder"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:border-purple-400 focus:ring-2 focus:ring-purple-100 transition-all outline-none text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                />
                
                <p v-if="form[network.key]" class="mt-2 text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                    <span>✓</span> Configurado
                </p>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="bg-gray-900 rounded-xl p-6">
            <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">Vista previa del Footer</h4>
            <div class="flex items-center gap-3 flex-wrap">
                <a 
                    v-for="network in socialNetworks.filter(n => form[n.key])" 
                    :key="network.key"
                    :href="form[network.key]"
                    target="_blank"
                    :class="[network.color, 'w-10 h-10 rounded-full flex items-center justify-center text-white text-lg hover:scale-110 transition-transform']"
                    :title="network.label"
                >
                    {{ network.icon }}
                </a>
                <span v-if="!socialNetworks.some(n => form[n.key])" class="text-gray-500 dark:text-gray-400 text-sm">
                    No hay redes sociales configuradas
                </span>
            </div>
        </div>

        <!-- Automation Section -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-100 dark:border-blue-700">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-2xl">
                    🤖
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Automatización de Blog (n8n)</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Conecta tu blog con n8n para publicar automáticamente en redes sociales.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">n8n Webhook URL (Blog)</label>
                <div class="flex gap-2">
                    <input
                        type="url"
                        :value="form.n8n_webhook_blog"
                        @input="updateField('n8n_webhook_blog', $event.target.value)"
                        placeholder="https://n8n.tuempresa.com/webhook/..."
                        class="flex-1 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all outline-none text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                    />
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Cuando publiques un artículo en el blog, se enviará una solicitud POST a esta URL con los detalles del post. Puedes usar n8n para formatear esta información y enviarla a Facebook, Instagram, Twitter y TikTok.
                </p>
            </div>
        </div>

        <!-- Tips -->
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-100 dark:border-amber-700">
            <div class="flex items-start gap-3">
                <span class="text-lg">💡</span>
                <div class="text-sm text-amber-800 dark:text-amber-300">
                    <p class="font-semibold mb-1">Consejos:</p>
                    <ul class="list-disc list-inside space-y-1 text-amber-700 dark:text-amber-200">
                        <li>Usa la URL completa incluyendo https://</li>
                        <li>Asegúrate de que los perfiles estén activos y actualizados</li>
                        <li>Las redes sin URL configurada no se mostrarán en el sitio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
