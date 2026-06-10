<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="shield-alt" class="text-rose-600 dark:text-rose-400" />
                Seguridad y Acceso
            </h2>

            <!-- Bloqueo de cuentas -->
             <div class="bg-rose-50 dark:bg-rose-900/20 p-6 rounded-xl border border-rose-200 dark:border-rose-800/30 dark:border-rose-700 mb-6">
                <h3 class="text-md font-medium text-rose-900 dark:text-rose-300 mb-4 flex items-center gap-2">
                     <FontAwesomeIcon icon="lock" class="dark:text-rose-400" /> Protección contra fuerza bruta
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                         <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Intentos fallidos antes de bloqueo</label>
                         <input type="number" v-model="form.intentos_login" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" min="1" max="10">
                    </div>
                    <div>
                         <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Tiempo de bloqueo (minutos)</label>
                         <input type="number" v-model="form.tiempo_bloqueo" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" min="1" max="60">
                    </div>
                </div>
            </div>

            <!-- Autenticación de dos factores -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4">
                     <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.requerir_2fa" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        <span class="ml-3 text-sm font-medium text-slate-900 dark:text-slate-100">Requerir 2FA para administradores</span>
                    </label>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 ml-14">Obliga a los usuarios con rol de Admin a configurar la autenticación de dos factores.</p>
            </div>
            
             <!-- Configuración DKIM -->
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 font-mono text-sm">
                <h3 class="text-md font-sans font-medium text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
                     <FontAwesomeIcon icon="key" class="text-brand-600 dark:text-amber-400" /> Claves DKIM (Correo)
                </h3>
                 <div class="space-y-6">
                      <div class="flex items-center gap-4 mb-2">
                         <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.dkim_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                            <span class="ml-3 text-sm font-medium text-slate-900 dark:text-slate-100">Habilitar firma DKIM</span>
                        </label>
                    </div>

                    <div v-if="form.dkim_enabled" class="grid grid-cols-1 gap-4">
                        <div>
                             <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Dominio</label>
                             <input type="text" v-model="form.dkim_domain" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="midominio.com" />
                        </div>
                        <div>
                             <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Selector</label>
                             <input type="text" v-model="form.dkim_selector" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="default" />
                        </div>
                         <div>
                             <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Clave Privada (RSA)</label>
                             <textarea v-model="form.dkim_public_key" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 text-xs" placeholder="-----BEGIN PRIVATE KEY-----..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIN de Auditoría (Inventario Móvil) -->
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
                <h3 class="text-md font-medium text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
                     <FontAwesomeIcon icon="calculator" class="text-blue-600 dark:text-blue-400" /> PIN de Auditoría (Móvil)
                </h3>
                <div class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 p-6 rounded-xl border border-sky-200 dark:border-sky-800/30 dark:border-blue-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">PIN de seguridad (numérico)</label>
                            <input 
                                type="text" 
                                v-model="form.pin_auditoria" 
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 font-mono text-lg tracking-wide text-center" 
                                placeholder="1234"
                                maxlength="6"
                            >
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">PIN necesario para autorizar el reinicio de conteos en la App móvil (Auditoría).</p>
                        </div>
                        <div class="flex items-center">
                            <div class="text-xs text-slate-500 dark:text-slate-400 italic">
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

