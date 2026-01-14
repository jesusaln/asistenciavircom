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
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl">
                    📱
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Redes Sociales</h3>
                    <p class="text-sm text-gray-600">Configura los enlaces a tus perfiles de redes sociales. Se mostrarán en el footer de tu sitio web.</p>
                </div>
            </div>
        </div>

        <!-- Social Networks Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
                v-for="network in socialNetworks" 
                :key="network.key"
                class="bg-white rounded-xl p-6 border border-gray-100 hover:border-gray-200 transition-all"
            >
                <div class="flex items-center gap-3 mb-4">
                    <div :class="[network.color, 'w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg']">
                        {{ network.icon }}
                    </div>
                    <label class="font-semibold text-gray-900">{{ network.label }}</label>
                </div>
                
                <input
                    type="url"
                    :value="form[network.key]"
                    @input="updateField(network.key, $event.target.value)"
                    :placeholder="network.placeholder"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-purple-400 focus:ring-2 focus:ring-purple-100 transition-all outline-none text-sm"
                />
                
                <p v-if="form[network.key]" class="mt-2 text-xs text-green-600 flex items-center gap-1">
                    <span>✓</span> Configurado
                </p>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="bg-gray-900 rounded-xl p-6">
            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Vista previa del Footer</h4>
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
                <span v-if="!socialNetworks.some(n => form[n.key])" class="text-gray-500 text-sm">
                    No hay redes sociales configuradas
                </span>
            </div>
        </div>

        <!-- Tips -->
        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
            <div class="flex items-start gap-3">
                <span class="text-lg">💡</span>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold mb-1">Consejos:</p>
                    <ul class="list-disc list-inside space-y-1 text-amber-700">
                        <li>Usa la URL completa incluyendo https://</li>
                        <li>Asegúrate de que los perfiles estén activos y actualizados</li>
                        <li>Las redes sin URL configurada no se mostrarán en el sitio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
