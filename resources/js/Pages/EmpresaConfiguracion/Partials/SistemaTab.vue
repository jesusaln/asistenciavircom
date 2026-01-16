<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="cogs" class="text-gray-700" />
                Configuración del Sistema
            </h2>

            <!-- Modo Mantenimiento -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 mb-6">
                <div class="flex items-center gap-4 mb-4">
                     <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.mantenimiento" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Modo Mantenimiento</span>
                    </label>
                </div>
                <div v-if="form.mantenimiento">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje para usuarios</label>
                    <textarea v-model="form.mensaje_mantenimiento" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="El sistema está en mantenimiento..."></textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Backups -->
                <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-md font-medium text-gray-900 mb-4">Copias de Seguridad (Backups)</h3>
                    
                     <div class="flex items-center gap-4 mb-4">
                         <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.backup_automatico" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900">Backups Automáticos</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">Frecuencia (Horas)</label>
                             <input type="number" v-model="form.frecuencia_backup" class="w-full px-4 py-2 border border-gray-300 rounded-lg" min="1" max="168">
                        </div>
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">Retención (Días)</label>
                             <input type="number" v-model="form.retencion_backups" class="w-full px-4 py-2 border border-gray-300 rounded-lg" min="1" max="365">
                        </div>
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Backup</label>
                             <select v-model="form.backup_tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="sql">Solo SQL (Base de datos)</option>
                                <option value="completo">Completo (BD + Archivos)</option>
                             </select>
                        </div>
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">Hora Backup Diario</label>
                             <input type="time" v-model="form.backup_hora_completo" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <!-- Google Cloud -->
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.backup_cloud_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900">Subir a Google Cloud</span>
                        </label>
                        <span class="text-xs text-gray-500">(Requiere configuración de Google Cloud Storage)</span>
                    </div>
                </div>
                
                 <!-- Otros ajustes -->
                 <div class="md:col-span-2">
                     <div class="flex items-center mb-4">
                        <input type="checkbox" v-model="form.registro_usuarios" id="registro_usuarios" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="registro_usuarios" class="ml-2 block text-sm text-gray-900">Permitir registro de nuevos usuarios</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" v-model="form.notificaciones_email" id="notificaciones_email" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="notificaciones_email" class="ml-2 block text-sm text-gray-900">Enviar notificaciones por email</label>
                    </div>
                 </div>
            </div>
            <!-- Bitácora General del Sistema -->
            <div class="mt-8 bg-white p-6 rounded-xl border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-md font-medium text-gray-900 flex items-center gap-2">
                        <FontAwesomeIcon icon="file-alt" class="text-gray-500" />
                        Bitácora General del Sistema
                    </h3>
                    <div class="flex gap-2">
                        <button @click="fetchLogs" type="button" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center gap-2">
                            <FontAwesomeIcon icon="sync" :class="{'fa-spin': loadingLogs}" />
                            Actualizar
                        </button>
                        <button @click="clearLogs" type="button" class="px-3 py-1 text-sm bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors flex items-center gap-2">
                            <FontAwesomeIcon icon="trash" />
                            Limpiar
                        </button>
                    </div>
                </div>
                
                <div class="relative">
                    <textarea 
                        v-model="systemLogs" 
                        readonly 
                        class="w-full h-96 bg-gray-900 text-green-400 font-mono text-xs p-4 rounded-lg focus:outline-none resize-y"
                    ></textarea>
                    <div v-if="loadingLogs" class="absolute inset-0 bg-white/50 flex items-center justify-center rounded-lg">
                        <FontAwesomeIcon icon="spinner" spin class="text-3xl text-blue-600" />
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Visualizando las últimas 500 líneas del archivo de registro (laravel.log).
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    form: { type: Object, required: true },
});

import { ref, onMounted } from 'vue';
import axios from 'axios';

const systemLogs = ref('');
const loadingLogs = ref(false);

const fetchLogs = async () => {
    loadingLogs.value = true;
    try {
        const response = await axios.get(route('empresa-configuracion.sistema.logs'));
        systemLogs.value = response.data.logs;
    } catch (error) {
        console.error('Error fetching logs:', error);
        systemLogs.value = 'Error al cargar los registros del sistema.';
    } finally {
        loadingLogs.value = false;
    }
};

const clearLogs = async () => {
    if (!confirm('¿Estás seguro de que deseas limpiar la bitácora del sistema? Esta acción no se puede deshacer.')) return;

    loadingLogs.value = true;
    try {
        await axios.post(route('empresa-configuracion.sistema.logs.clear'));
        window.$toast.success('Bitácora limpiada correctamente');
        fetchLogs();
    } catch (error) {
        console.error('Error clearing logs:', error);
        window.$toast.error('Error al limpiar la bitácora.');
        loadingLogs.value = false;
    }
};

onMounted(() => {
    fetchLogs();
});
</script>

