<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="shopping-cart" class="text-blue-600 dark:text-blue-400" />
                Tienda en Línea
            </h2>
        </div>

        <!-- Toggle Tienda Activa -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-sky-200 dark:border-sky-800/30 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white">Activar Tienda en Línea</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Permite que los clientes compren productos directamente desde el catálogo
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.tienda_online_activa" class="sr-only peer">
                    <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-slate-700 peer-checked:bg-gradient-to-r peer-checked:from-blue-600 peer-checked:to-purple-600"></div>
                </label>
            </div>
        </div>

        <!-- OAuth Section -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 bg-white dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="shield-alt" class="text-blue-500" />
                    Autenticación Social (OAuth)
                </h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Permite a los clientes iniciar sesión con sus cuentas de Google
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Google OAuth -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-10 h-10" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <div>
                            <h5 class="font-bold text-slate-900 dark:text-white">Google OAuth</h5>
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                Obtener credenciales →
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client ID</label>
                            <input type="text" v-model="form.google_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="xxxxx.apps.googleusercontent.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client Secret</label>
                            <input type="password" v-model="form.google_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="GOCSPX-xxxxx" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        URI de redirección: <code class="bg-slate-100 dark:bg-slate-600 px-2 py-1 rounded-xl">{{ baseUrl }}/auth/google/callback</code>
                    </p>
                </div>
            </div>
        </div>

        <!-- MercadoLibre Integration -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 bg-white dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="store" class="text-yellow-500" />
                    MercadoLibre
                </h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Publica productos CVA en MercadoLibre (dropshipping). Stock mínimo: 3 unidades.
                </p>
            </div>

            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="font-bold text-slate-900 dark:text-white">Activar integración</h5>
                        <p class="text-sm text-slate-500">Sincronizar catálogo CVA con MercadoLibre</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.meli_active" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-slate-700 peer-checked:bg-gradient-to-r peer-checked:from-yellow-500 peer-checked:to-yellow-600"></div>
                    </label>
                </div>

                <div v-if="form.meli_active" class="space-y-4 border-t border-slate-100 dark:border-slate-700 pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h5 class="font-bold text-sm text-slate-900 dark:text-white">Credenciales de la App</h5>
                            <p class="text-xs text-slate-500">Crea o usa una app existente en MercadoLibre</p>
                        </div>
                        <a href="https://developers.mercadolibre.com" target="_blank"
                           class="text-xs px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                            Crear App en ML
                        </a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">App ID</label>
                            <input type="text" v-model="form.meli_app_id"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="Ej: 1234567890123456" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client Secret</label>
                            <input type="password" v-model="form.meli_client_secret"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="Secret de tu app de ML" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Site ID</label>
                        <select v-model="form.meli_site_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm">
                            <option value="MLM">México (MLM)</option>
                            <option value="MLA">Argentina (MLA)</option>
                            <option value="MLB">Brasil (MLB)</option>
                            <option value="MLC">Chile (MLC)</option>
                            <option value="MCO">Colombia (MCO)</option>
                            <option value="MPE">Perú (MPE)</option>
                        </select>
                    </div>
                    <div v-if="form.meli_user_id" class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 flex items-center justify-between">
                        <div class="text-sm">
                            ✅ Conectado a MercadoLibre (User ID: {{ form.meli_user_id }})
                        </div>
                        <button @click="disconnectMeli" type="button"
                                class="text-xs px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors">
                            Desconectar
                        </button>
                    </div>
                    <div v-else>
                        <a :href="route('empresa-configuracion.meli.auth')"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white font-bold rounded-xl hover:from-yellow-600 hover:to-yellow-700 transition-all shadow-lg">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            Conectar con MercadoLibre
                        </a>
                        <p class="text-xs text-slate-500 mt-2">
                            Serás redirigido a MercadoLibre para autorizar la conexión.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Gateways -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 bg-white dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="credit-card" class="text-emerald-500" />
                    Pasarelas de Pago
                </h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Configura los métodos de pago para tu tienda
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- MercadoPago -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-sky-900/20/50 rounded-xl flex items-center justify-center">
                                <span class="text-blue-600 font-bold text-sm">MP</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 dark:text-white">MercadoPago</h5>
                                <a href="https://www.mercadopago.com.mx/developers/panel/app" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-slate-500 dark:text-slate-400">Sandbox</span>
                            <input type="checkbox" v-model="form.mercadopago_sandbox" class="rounded-xl border-slate-300 text-blue-600 focus:ring-brand-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Access Token</label>
                            <input type="password" v-model="form.mercadopago_access_token" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="APP_USR-xxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Public Key</label>
                            <input type="text" v-model="form.mercadopago_public_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="APP_USR-xxxxx" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_mercadopago" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-brand-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1 italic">Cada pago recibido por MercadoPago se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>

                <!-- PayPal -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-sky-900/20/50 rounded-xl flex items-center justify-center">
                                <svg class="w-10 h-10 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.641.641 0 0 1 .632-.54h6.012c2.66 0 4.507.523 5.49 1.556.455.477.754 1.02.91 1.665.165.692.153 1.515-.034 2.525l-.013.08v.72l.56.312c.472.239.851.512 1.14.825.481.526.79 1.17.918 1.914.132.76.084 1.66-.14 2.676-.26 1.177-.684 2.195-1.261 3.027a6.094 6.094 0 0 1-1.898 1.832c-.723.45-1.55.79-2.459 1.01-.927.225-1.946.339-3.031.339H10.1a.641.641 0 0 0-.633.54l-.763 4.85a.641.641 0 0 1-.632.54l-1-.002z"/>
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 dark:text-white">PayPal</h5>
                                <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-slate-500 dark:text-slate-400">Sandbox</span>
                            <input type="checkbox" v-model="form.paypal_sandbox" class="rounded-xl border-slate-300 text-blue-600 focus:ring-brand-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client ID</label>
                            <input type="text" v-model="form.paypal_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="AxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxB" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client Secret</label>
                            <input type="password" v-model="form.paypal_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="ExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxC" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_paypal" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-brand-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1 italic">Cada pago recibido por PayPal se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>

                <!-- Stripe -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center">
                                <FontAwesomeIcon icon="credit-card" class="text-purple-600" />
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 dark:text-white">Stripe</h5>
                                <a href="https://dashboard.stripe.com/test/apikeys" target="_blank" class="text-xs text-purple-500 dark:text-purple-400 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-slate-500 dark:text-slate-400">Sandbox</span>
                            <input type="checkbox" v-model="form.stripe_sandbox" class="rounded-xl border-slate-300 text-purple-600 focus:ring-brand-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Public Key (pk_...)</label>
                            <input type="text" v-model="form.stripe_public_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="pk_test_xxxxxxxxxxxxxxxxxxxxxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Secret Key (sk_...)</label>
                            <input type="password" v-model="form.stripe_secret_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                   placeholder="Stripe Secret Key" />
                        </div>
                        <div class="md:col-span-2">
                             <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Webhook Secret (whsec_...)</label>
                             <input type="password" v-model="form.stripe_webhook_secret" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm"
                                    placeholder="whsec_xxxxxxxxxxxxxxxxxxxxxxxx" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_stripe" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-brand-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1 italic">Cada pago recibido por Stripe se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button @click="$emit('save')" 
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all shadow-xl">
                Guardar Configuración
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    cuentas_bancarias: {
        type: Array,
        default: () => []
    }
})

defineEmits(['save'])

const baseUrl = computed(() => {
    return window.location.origin
})

function disconnectMeli() {
    if (confirm('¿Desconectar MercadoLibre?')) {
        router.post(route('empresa-configuracion.meli.disconnect'))
    }
}
</script>
