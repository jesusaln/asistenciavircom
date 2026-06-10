<template>
    <div class="space-y-6">
        <!-- Alerta solo para admins -->
        <div v-if="!isAdmin" class="bg-brand-50 dark:bg-brand-900/20 border-l-4 border-brand-400 dark:border-brand-600 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-brand-400 dark:text-brand-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300">
                        Solo los administradores pueden gestionar los certificados del SAT.
                    </p>
                </div>
            </div>
        </div>

        <!-- Contenido para admins -->
        <div v-if="isAdmin" class="space-y-6">
            <!-- FIEL (e.firma) -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm">
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        FIEL (e.firma) - Firma Electrónica Avanzada
                    </h3>
                    <p class="text-blue-100 dark:text-blue-200 text-sm mt-1">Para firmar contratos, documentos legales y trámites ante el SAT</p>
                </div>

                <div class="p-6">
                    <!-- Estado actual -->
                    <div v-if="fielInfo.configurado" class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-10 h-10 bg-brand-500 rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">FIEL Configurada</p>
                                <p class="text-sm text-emerald-600 dark:text-slate-400">RFC: {{ fielInfo.rfc }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">No. Serie:</span>
                                <p class="font-mono text-xs text-slate-900 dark:text-slate-100">{{ fielInfo.serial?.substring(0, 20) }}...</p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Válido desde:</span>
                                <p class="text-slate-900 dark:text-slate-100">{{ fielInfo.valid_from }}</p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Válido hasta:</span>
                                <p :class="fielInfo.vigente ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ fielInfo.valid_to }}
                                </p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Estado:</span>
                                <span v-if="fielInfo.vigente" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">
                                    ✓ Vigente
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-rose-50 dark:bg-rose-900/20/40 text-rose-800 dark:text-rose-200 dark:text-rose-300">
                                    ✗ Vencido
                                </span>
                            </div>
                        </div>
                        <button 
                            @click="eliminarFiel" 
                            :disabled="loadingFiel"
                            class="mt-4 text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:text-rose-200 dark:hover:text-rose-300 text-sm font-medium"
                        >
                            🗑️ Eliminar FIEL
                        </button>
                    </div>

                    <!-- Formulario de carga -->
                    <form @submit.prevent="subirFiel" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                    Archivo .cer
                                </label>
                                <input 
                                    type="file" 
                                    @change="e => fielForm.fiel_cer = e.target.files[0]"
                                    accept=".cer"
                                    class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-50 dark:bg-sky-900/20 dark:file:bg-blue-900/40 file:text-sky-800 dark:text-sky-200 dark:file:text-blue-300 hover:file:bg-sky-100 dark:hover:file:bg-blue-900/60"
                                />
                                <p v-if="errors.fiel_cer" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.fiel_cer }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                    Archivo .key
                                </label>
                                <input 
                                    type="file" 
                                    @change="e => fielForm.fiel_key = e.target.files[0]"
                                    accept=".key"
                                    class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-50 dark:bg-sky-900/20 dark:file:bg-blue-900/40 file:text-sky-800 dark:text-sky-200 dark:file:text-blue-300 hover:file:bg-sky-100 dark:hover:file:bg-blue-900/60"
                                />
                                <p v-if="errors.fiel_key" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.fiel_key }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                Contraseña de la llave privada
                            </label>
                            <input 
                                type="password" 
                                v-model="fielForm.fiel_password"
                                class="block w-full rounded-xl border-slate-300 dark:border-slate-700 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                                placeholder="••••••••"
                            />
                            <p v-if="errors.fiel_password" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.fiel_password }}</p>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="loadingFiel"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
                        >
                            <svg v-if="loadingFiel" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ fielInfo.configurado ? 'Actualizar FIEL' : 'Subir FIEL' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- CSD (Certificado de Sello Digital) -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm">
                <div class="bg-gradient-to-r from-purple-600 to-purple-800 px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        CSD - Certificado de Sello Digital
                    </h3>
                    <p class="text-purple-100 dark:text-purple-200 text-sm mt-1">Para sellar facturas electrónicas (CFDI)</p>
                </div>

                <div class="p-6">
                    <!-- Estado actual -->
                    <div v-if="csdInfo.configurado" class="mb-6 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-purple-800 dark:text-purple-300">CSD Configurado</p>
                                <p class="text-sm text-purple-600 dark:text-purple-400">RFC: {{ csdInfo.rfc }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">No. Serie:</span>
                                <p class="font-mono text-xs text-slate-900 dark:text-slate-100">{{ csdInfo.serial?.substring(0, 20) }}...</p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Válido desde:</span>
                                <p class="text-slate-900 dark:text-slate-100">{{ csdInfo.valid_from }}</p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Válido hasta:</span>
                                <p :class="csdInfo.vigente ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ csdInfo.valid_to }}
                                </p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Estado:</span>
                                <span v-if="csdInfo.vigente" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">
                                    ✓ Vigente
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-rose-50 dark:bg-rose-900/20/40 text-rose-800 dark:text-rose-200 dark:text-rose-300">
                                    ✗ Vencido
                                </span>
                            </div>
                        </div>
                        <button 
                            @click="eliminarCsd" 
                            :disabled="loadingCsd"
                            class="mt-4 text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:text-rose-200 dark:hover:text-rose-300 text-sm font-medium"
                        >
                            🗑️ Eliminar CSD
                        </button>
                    </div>

                    <!-- Formulario de carga -->
                    <form @submit.prevent="subirCsd" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                    Archivo .cer
                                </label>
                                <input 
                                    type="file" 
                                    @change="e => csdForm.csd_cer = e.target.files[0]"
                                    accept=".cer"
                                    class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 dark:file:bg-purple-900/40 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 dark:hover:file:bg-purple-900/60"
                                />
                                <p v-if="errors.csd_cer" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.csd_cer }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                    Archivo .key
                                </label>
                                <input 
                                    type="file" 
                                    @change="e => csdForm.csd_key = e.target.files[0]"
                                    accept=".key"
                                    class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 dark:file:bg-purple-900/40 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 dark:hover:file:bg-purple-900/60"
                                />
                                <p v-if="errors.csd_key" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.csd_key }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                Contraseña de la llave privada
                            </label>
                            <input 
                                type="password" 
                                v-model="csdForm.csd_password"
                                class="block w-full rounded-xl border-slate-300 dark:border-slate-700 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                                placeholder="••••••••"
                            />
                            <p v-if="errors.csd_password" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.csd_password }}</p>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="loadingCsd"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
                        >
                            <svg v-if="loadingCsd" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ csdInfo.configurado ? 'Actualizar CSD' : 'Subir CSD' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- PAC (Proveedor de Timbrado) -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-800 px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        PAC - Proveedor Autorizado de Certificación
                    </h3>
                    <p class="text-emerald-100 text-sm mt-1">Credenciales para timbrar facturas electrónicas (CFDI 4.0)</p>
                </div>

                <div class="p-6">
                    <!-- Estado actual -->
                    <div v-if="pacInfo.configurado" class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-10 h-10 bg-brand-500 rounded-full flex items-center justify-center text-white">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-emerald-800 dark:text-emerald-200">PAC Configurado</p>
                                <p class="text-sm text-emerald-600">{{ pacInfo.nombre || 'Sin nombre' }}</p>
                            </div>
                        </div>

                        <!-- Detalle de conexión -->
                        <div class="text-sm space-y-3">
                            <div>
                                <span class="text-slate-500 text-xs uppercase font-semibold">URL del PAC:</span>
                                <p class="font-mono text-xs truncate bg-white p-2 rounded-xl border border-emerald-100 mt-1">{{ pacInfo.base_url }}</p>
                            </div>
                            <div class="flex items-center gap-2 border-t border-emerald-100 pt-3">
                                <span class="text-slate-500 text-xs font-medium">Modo actual:</span>
                                <span v-if="pacInfo.produccion" class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200 shadow-sm">
                                    <span class="mr-1">🚀</span> Producción
                                </span>
                                <span v-else class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-brand-200 shadow-sm">
                                    <span class="mr-1">🧪</span> Pruebas
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form @submit.prevent="guardarPac" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Nombre del Proveedor
                            </label>
                            <input 
                                type="text" 
                                v-model="pacForm.pac_nombre"
                                class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="ej: SW Sapien, Finkok, etc."
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                URL Base del API
                            </label>
                            <input 
                                type="url" 
                                v-model="pacForm.pac_base_url"
                                class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="https://services.test.sw.com.mx"
                            />
                            <p class="mt-1 text-xs text-slate-500">Ejemplo: https://services.test.sw.com.mx</p>
                            <p class="mt-1 text-xs text-brand-600 font-medium">* Si dejas este campo vacío, se usará la URL automática según el modo (Pruebas/Producción).</p>
                        </div>
                        
                        <!-- Toggle Producción -->
                        <div class="bg-white p-4 rounded-xl border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-slate-900">Modo de Operación</span>
                                    <span class="text-xs text-slate-500">Cambia entre el entorno de pruebas y producción del PAC.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="pacForm.pac_produccion" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                                    <span class="ml-3 text-sm font-medium" :class="pacForm.pac_produccion ? 'text-emerald-800 dark:text-emerald-200 dark:text-emerald-200' : 'text-amber-600'">
                                        {{ pacForm.pac_produccion ? '🚀 Producción' : '🧪 Pruebas' }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                API Key / Token
                            </label>
                            <input 
                                type="password" 
                                v-model="pacForm.pac_apikey"
                                class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="••••••••"
                            />
                            <p class="mt-1 text-xs text-slate-500">Tu clave de API proporcionada por el PAC</p>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                type="submit" 
                                :disabled="loadingPac"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
                            >
                                <svg v-if="loadingPac" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Guardar Configuración PAC
                            </button>
                            <button 
                                type="button"
                                @click="probarConexionPac"
                                :disabled="loadingPacTest || !pacForm.pac_base_url || !pacForm.pac_apikey"
                                class="inline-flex items-center px-4 py-2 border border-emerald-300 text-sm font-medium rounded-xl text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
                            >
                                <svg v-if="loadingPacTest" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                🔗 Probar Conexión
                            </button>
                        </div>
                        <div v-if="pacTestResult" :class="pacTestResult.success ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200 dark:text-rose-200'" class="p-3 rounded-xl text-sm">
                            {{ pacTestResult.message }}
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información de seguridad -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                <h4 class="font-medium text-slate-900 dark:text-slate-100 mb-2">🔒 Información de Seguridad</h4>
                <ul class="text-sm text-slate-500 dark:text-slate-200 space-y-1">
                    <li>• Los archivos .key y las contraseñas se almacenan <strong>encriptados</strong> en el servidor.</li>
                    <li>• Los certificados se guardan en un directorio privado no accesible desde internet.</li>
                    <li>• Solo los administradores pueden ver y modificar esta configuración.</li>
                    <li>• Las contraseñas nunca se muestran una vez guardadas.</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();
// Verificar admin por: is_admin flag, o rol admin/super-admin
const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    // Check is_admin flag
    if (user.is_admin) return true;
    // Check roles
    const roles = user.roles || [];
    return roles.some(r => {
        const roleName = typeof r === 'string' ? r : r.name;
        return roleName === 'admin' || roleName === 'super-admin';
    });
});

const loadingFiel = ref(false);
const loadingCsd = ref(false);
const errors = ref({});

const fielInfo = ref({
    configurado: false,
    rfc: null,
    serial: null,
    valid_from: null,
    valid_to: null,
    vigente: false,
});

const csdInfo = ref({
    configurado: false,
    rfc: null,
    serial: null,
    valid_from: null,
    valid_to: null,
    vigente: false,
});

const fielForm = reactive({
    fiel_cer: null,
    fiel_key: null,
    fiel_password: '',
});

const csdForm = reactive({
    csd_cer: null,
    csd_key: null,
    csd_password: '',
});

const loadingPac = ref(false);
const loadingPacTest = ref(false);
const pacTestResult = ref(null);

const pacInfo = ref({
    configurado: false,
    nombre: null,
    base_url: null,
    produccion: false,
});

const pacForm = reactive({
    pac_nombre: '',
    pac_base_url: '',
    pac_apikey: '',
    pac_produccion: false,
});

const cargarInfoCertificados = async () => {
    try {
        const response = await fetch(route('empresa-configuracion.certificados.info'));
        if (response.ok) {
            const responseData = await response.json();
            // Los datos vienen envueltos en { success: true, data: {...} }
            const data = responseData.data || responseData;
            fielInfo.value = data.fiel || fielInfo.value;
            csdInfo.value = data.csd || csdInfo.value;
            // Cargar info del PAC
            if (data.pac) {
                pacInfo.value = data.pac;
                pacForm.pac_nombre = data.pac.nombre || '';
                pacForm.pac_base_url = data.pac.base_url || '';
                pacForm.pac_produccion = data.pac.produccion || false;
                // No cargamos el apikey por seguridad
            }
        }
    } catch (error) {
        console.error('Error cargando info de certificados:', error);
    }
};

const guardarPac = () => {
    loadingPac.value = true;
    pacTestResult.value = null;
    router.post(route('empresa-configuracion.pac.guardar'), {
        pac_nombre: pacForm.pac_nombre,
        pac_base_url: pacForm.pac_base_url,
        pac_apikey: pacForm.pac_apikey,
        pac_produccion: pacForm.pac_produccion,
    }, {
        onSuccess: () => {
            pacInfo.value = {
                configurado: true,
                nombre: pacForm.pac_nombre,
                base_url: pacForm.pac_base_url,
                produccion: pacForm.pac_produccion,
            };
            pacForm.pac_apikey = ''; // Limpiar por seguridad
        },
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => {
            loadingPac.value = false;
        },
    });
};

const probarConexionPac = async () => {
    loadingPacTest.value = true;
    pacTestResult.value = null;

    try {
        const response = await fetch(route('empresa-configuracion.pac.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                pac_base_url: pacForm.pac_base_url,
                pac_apikey: pacForm.pac_apikey,
            }),
        });
        const data = await response.json();
        pacTestResult.value = data;
    } catch (error) {
        pacTestResult.value = { success: false, message: 'Error de conexión: ' + error.message };
    } finally {
        loadingPacTest.value = false;
    }
};

const subirFiel = () => {
    if (!fielForm.fiel_cer || !fielForm.fiel_key || !fielForm.fiel_password) {
        errors.value = { error: 'Todos los campos son requeridos' };
        return;
    }

    loadingFiel.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('fiel_cer', fielForm.fiel_cer);
    formData.append('fiel_key', fielForm.fiel_key);
    formData.append('fiel_password', fielForm.fiel_password);

    router.post(route('empresa-configuracion.fiel.subir'), formData, {
        forceFormData: true,
        onSuccess: () => {
            fielForm.fiel_password = '';
            cargarInfoCertificados();
        },
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => {
            loadingFiel.value = false;
        },
    });
};

const subirCsd = () => {
    if (!csdForm.csd_cer || !csdForm.csd_key || !csdForm.csd_password) {
        errors.value = { error: 'Todos los campos son requeridos' };
        return;
    }

    loadingCsd.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('csd_cer', csdForm.csd_cer);
    formData.append('csd_key', csdForm.csd_key);
    formData.append('csd_password', csdForm.csd_password);

    router.post(route('empresa-configuracion.csd.subir'), formData, {
        forceFormData: true,
        onSuccess: () => {
            csdForm.csd_password = '';
            cargarInfoCertificados();
        },
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => {
            loadingCsd.value = false;
        },
    });
};

const eliminarFiel = async () => {
    const { value: password } = await Swal.fire({
        title: 'Confirmar eliminación',
        text: 'Para eliminar la FIEL, ingresa la contraseña de TU CUENTA de usuario (no la del certificado):',
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: async (password) => {
            if (!password) {
                Swal.showValidationMessage('La contraseña es requerida');
                return false;
            }
            return password;
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
    
    if (!password) return;
    
    loadingFiel.value = true;
    
    try {
        const response = await fetch(route('empresa-configuracion.fiel.eliminar'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ password }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            fielInfo.value = { configurado: false };
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado!',
                text: data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    } finally {
        loadingFiel.value = false;
    }
};

const eliminarCsd = async () => {
    const { value: password } = await Swal.fire({
        title: 'Confirmar eliminación',
        text: 'Para eliminar el CSD, ingresa la contraseña de TU CUENTA de usuario (no la del certificado):',
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: async (password) => {
            if (!password) {
                Swal.showValidationMessage('La contraseña es requerida');
                return false;
            }
            return password;
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
    
    if (!password) return;
    
    loadingCsd.value = true;
    
    try {
        const response = await fetch(route('empresa-configuracion.csd.eliminar'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ password }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            csdInfo.value = { configurado: false };
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado!',
                text: data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    } finally {
        loadingCsd.value = false;
    }
};

onMounted(() => {
    if (isAdmin.value) {
        cargarInfoCertificados();
    }
});
</script>
