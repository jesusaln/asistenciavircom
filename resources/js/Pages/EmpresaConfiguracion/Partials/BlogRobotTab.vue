<script setup>
import { computed } from 'vue'

const props = defineProps({
    form: Object
})

// Generate a random token on client side for convenience if empty
const generateToken = () => {
    props.form.blog_robot_token = 'vircom_bot_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    
    // Copy to clipboard
    navigator.clipboard.writeText(props.form.blog_robot_token);
    alert('Token generado y copiado al portapapeles');
}

const robotEndpoint = computed(() => {
    return `${window.location.origin}/api/blog/robot/draft`;
});

const copyEndpoint = () => {
    navigator.clipboard.writeText(robotEndpoint.value);
    alert('Endpoint copiado: ' + robotEndpoint.value);
}
</script>

<template>
    <div class="space-y-6">
        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Configuración de Robot de Blog</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Permite que agentes externos (n8n, Make, Scripts) envíen borradores de artículos automáticamente a tu sistema.
            </p>
        </div>

        <!-- Master Switch -->
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                 :class="form.blog_robot_enabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                 @click="form.blog_robot_enabled = !form.blog_robot_enabled"
            >
                <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="form.blog_robot_enabled ? 'translate-x-5' : 'translate-x-0'"
                ></span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-900 dark:text-white">Habilitar Recepción Automática</span>
                <span class="block text-xs text-gray-500 dark:text-gray-400">Si está desactivado, el endpoint rechazará todas las peticiones.</span>
            </div>
        </div>

        <div v-if="form.blog_robot_enabled" class="space-y-6 animate-fade-in">
            <!-- Token -->
            <div class="col-span-6 sm:col-span-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token de Seguridad (Bearer Token)</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="text" v-model="form.blog_robot_token" class="block w-full rounded-none rounded-l-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Genera un token seguro">
                    <button @click="generateToken" type="button" class="relative -ml-px inline-flex items-center space-x-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <span>Generar Nuevo</span>
                    </button>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Este token debe ir en el Header de la petición: <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">Authorization: Bearer TU_TOKEN</code>
                </p>
            </div>

            <!-- Endpoint Info -->
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                <h4 class="text-sm font-bold text-blue-800 dark:text-blue-300 mb-2">Endpoint para tu Robot</h4>
                <div class="flex items-center gap-2">
                    <code class="block w-full bg-white dark:bg-gray-900 p-2 rounded border border-blue-200 dark:border-blue-800 text-xs font-mono text-gray-600 dark:text-gray-300 truncate">
                        {{ robotEndpoint }}
                    </code>
                    <button @click="copyEndpoint" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs font-bold uppercase">copiar</button>
                </div>
                
                <h4 class="text-sm font-bold text-blue-800 dark:text-blue-300 mt-4 mb-2">Formato JSON Esperado (POST)</h4>
                <pre class="bg-slate-900 text-slate-300 p-3 rounded-lg text-xs font-mono overflow-x-auto">
{
  "titulo": "Título del artículo",
  "resumen": "Breve resumen...",
  "contenido": "&lt;p&gt;Contenido en HTML...&lt;/p&gt;",
  "categoria": "Tecnología",
  "imagen_portada": "https://url-imagen.com/foto.jpg"
}</pre>
            </div>
        </div>
    </div>
</template>
