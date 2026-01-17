<template>
  <AppLayout title="Soporte Remoto">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Soporte Remoto (RustDesk)
      </h2>
    </template>

    <div class="py-6">
      <div class="w-full sm:px-6 lg:px-8">
        
        <!-- Instrucciones para Clientes -->
        <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 mb-6 rounded-r shadow-sm">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-indigo-800">Instrucciones para Clientes</h3>
                <div class="mt-2 text-sm text-indigo-700">
                  <p class="mb-2">Comparte estos datos con tus clientes para que configuren su RustDesk:</p>
                  <ul class="list-disc list-inside space-y-1 font-mono">
                    <li><strong>ID/Relay Server:</strong> {{ serverConfig.id_server }}</li>
                    <li><strong>Key:</strong> {{ serverConfig.key }}</li>
                  </ul>
                  <div class="mt-3">
                    <button @click="copiarConfig" class="text-indigo-600 hover:text-indigo-900 font-bold underline">
                        Copiar mensaje para WhatsApp
                    </button>
                    <span v-if="copiado" class="ml-2 text-green-600 font-bold">¡Copiado!</span>
                  </div>
                </div>
            </div>
          </div>
        </div>

        <!-- IFrame del Panel RustDesk -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg h-[800px] border border-gray-200 relative">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-gray-50 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                <span class="ml-3 text-gray-600">Cargando panel remoto...</span>
            </div>
            <iframe 
                :src="remoteUrl" 
                class="w-full h-full border-0"
                allow="clipboard-write"
                @load="loading = false"
            ></iframe>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    remoteUrl: String,
    serverConfig: Object
});

const loading = ref(true);
const copiado = ref(false);

const copiarConfig = () => {
    const texto = `*Configuración Soporte Remoto Vircom*\n\n` +
                  `1. Descarga RustDesk: https://rustdesk.com/download\n` +
                  `2. Ve a Configuración -> Red\n` +
                  `3. ID Server: remoto.asistenciavircom.com\n` +
                  `4. Relay Server: remoto.asistenciavircom.com\n` +
                  `5. Key: ${props.serverConfig.key}\n\n` +
                  `Avísame cuando esté listo.`;
    
    navigator.clipboard.writeText(texto).then(() => {
        copiado.value = true;
        setTimeout(() => copiado.value = false, 2000);
    });
};
</script>
