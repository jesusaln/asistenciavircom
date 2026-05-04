<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="shield-alt" class="text-red-600 dark:text-red-400" />
                Seguridad y Acceso
            </h2>

            <!-- Bloqueo de cuentas -->
             <div class="bg-red-50 dark:bg-red-900/20 p-6 rounded-xl border border-red-200 dark:border-red-700 mb-6">
                <h3 class="text-md font-medium text-red-900 dark:text-red-300 mb-4 flex items-center gap-2">
                     <FontAwesomeIcon icon="lock" class="dark:text-red-400" /> Protección contra fuerza bruta
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Intentos fallidos antes de bloqueo</label>
                         <input type="number" v-model="form.intentos_login" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" min="1" max="10">
                    </div>
                    <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tiempo de bloqueo (minutos)</label>
                         <input type="number" v-model="form.tiempo_bloqueo" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" min="1" max="60">
                    </div>
                </div>
            </div>

            <!-- Autenticación de dos factores -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                     <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.requerir_2fa" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Requerir 2FA para administradores</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-14">Obliga a los usuarios con rol de Admin a configurar la autenticación de dos factores.</p>
            </div>
            
             <!-- Configuración DKIM -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 font-mono text-sm">
                <h3 class="text-md font-sans font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                     <FontAwesomeIcon icon="key" class="text-yellow-600 dark:text-yellow-400" /> Claves DKIM (Correo)
                </h3>
                 <div class="space-y-4">
                      <div class="flex items-center gap-4 mb-2">
                         <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.dkim_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Habilitar firma DKIM</span>
                        </label>
                    </div>

                    <div v-if="form.dkim_enabled" class="grid grid-cols-1 gap-4">
                        <div>
                             <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dominio</label>
                             <input type="text" v-model="form.dkim_domain" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" placeholder="midominio.com" />
                        </div>
                        <div>
                             <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Selector</label>
                             <input type="text" v-model="form.dkim_selector" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" placeholder="default" />
                        </div>
                         <div>
                             <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Clave Privada (RSA)</label>
                             <textarea v-model="form.dkim_public_key" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 text-xs" placeholder="-----BEGIN PRIVATE KEY-----..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIN de Auditoría (Inventario Móvil) -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                     <FontAwesomeIcon icon="calculator" class="text-blue-600 dark:text-blue-400" /> PIN de Auditoría (Móvil)
                </h3>
                <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PIN de seguridad (numérico)</label>
                            <input 
                                type="text" 
                                v-model="form.pin_auditoria" 
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 font-mono text-lg tracking-widest text-center" 
                                placeholder="1234"
                                maxlength="6"
                            >
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">PIN necesario para autorizar el reinicio de conteos en la App móvil (Auditoría).</p>
                        </div>
                        <div class="flex items-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                                <FontAwesomeIcon icon="info-circle" class="mr-1" /> Solo el Super-Admin puede ver y modificar este PIN.
                            </div>
                        </div>
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

