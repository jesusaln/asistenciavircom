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
        </div>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    form: { type: Object, required: true },
});
</script>

