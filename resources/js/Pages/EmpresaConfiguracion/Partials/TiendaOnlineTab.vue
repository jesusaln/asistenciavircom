<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="shopping-cart" class="text-blue-600" />
                Tienda en Línea
            </h2>
        </div>

        <!-- Toggle Tienda Activa -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white">Activar Tienda en Línea</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Permite que los clientes compren productos directamente desde el catálogo
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.tienda_online_activa" class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-blue-600 peer-checked:to-purple-600"></div>
                </label>
            </div>
        </div>

        <!-- OAuth Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="shield-alt" class="text-blue-500" />
                    Autenticación Social (OAuth)
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Permite a los clientes iniciar sesión con sus cuentas de Google o Microsoft
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Google OAuth -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <div>
                            <h5 class="font-bold text-gray-900 dark:text-white">Google OAuth</h5>
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-xs text-blue-500 hover:underline">
                                Obtener credenciales →
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                            <input type="text" v-model="form.google_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="xxxxx.apps.googleusercontent.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                            <input type="password" v-model="form.google_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="GOCSPX-xxxxx" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        URI de redirección: <code class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded">{{ baseUrl }}/auth/google/callback</code>
                    </p>
                </div>

                <!-- Microsoft OAuth -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8" viewBox="0 0 24 24">
                            <path fill="#F25022" d="M1 1h10v10H1z"/>
                            <path fill="#00A4EF" d="M1 13h10v10H1z"/>
                            <path fill="#7FBA00" d="M13 1h10v10H13z"/>
                            <path fill="#FFB900" d="M13 13h10v10H13z"/>
                        </svg>
                        <div>
                            <h5 class="font-bold text-gray-900 dark:text-white">Microsoft / Outlook</h5>
                            <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" class="text-xs text-blue-500 hover:underline">
                                Obtener credenciales →
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                            <input type="text" v-model="form.microsoft_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                            <input type="password" v-model="form.microsoft_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="xxxxx~xxxxx" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        URI de redirección: <code class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded">{{ baseUrl }}/auth/microsoft/callback</code>
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Gateways -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="credit-card" class="text-green-500" />
                    Pasarelas de Pago
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Configura los métodos de pago para tu tienda
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- MercadoPago -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center">
                                <span class="text-blue-600 font-bold text-sm">MP</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900 dark:text-white">MercadoPago</h5>
                                <a href="https://www.mercadopago.com.mx/developers/panel/app" target="_blank" class="text-xs text-blue-500 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-gray-500">Sandbox</span>
                            <input type="checkbox" v-model="form.mercadopago_sandbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Access Token</label>
                            <input type="password" v-model="form.mercadopago_access_token" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="APP_USR-xxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Public Key</label>
                            <input type="text" v-model="form.mercadopago_public_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="APP_USR-xxxxx" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_mercadopago" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Cada pago recibido por MercadoPago se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>

                <!-- PayPal -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.641.641 0 0 1 .632-.54h6.012c2.66 0 4.507.523 5.49 1.556.455.477.754 1.02.91 1.665.165.692.153 1.515-.034 2.525l-.013.08v.72l.56.312c.472.239.851.512 1.14.825.481.526.79 1.17.918 1.914.132.76.084 1.66-.14 2.676-.26 1.177-.684 2.195-1.261 3.027a6.094 6.094 0 0 1-1.898 1.832c-.723.45-1.55.79-2.459 1.01-.927.225-1.946.339-3.031.339H10.1a.641.641 0 0 0-.633.54l-.763 4.85a.641.641 0 0 1-.632.54l-1-.002z"/>
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900 dark:text-white">PayPal</h5>
                                <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank" class="text-xs text-blue-500 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-gray-500">Sandbox</span>
                            <input type="checkbox" v-model="form.paypal_sandbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                            <input type="text" v-model="form.paypal_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="AxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxB" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                            <input type="password" v-model="form.paypal_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="ExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxC" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_paypal" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Cada pago recibido por PayPal se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>

                <!-- Stripe -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center">
                                <FontAwesomeIcon icon="credit-card" class="text-purple-600" />
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900 dark:text-white">Stripe</h5>
                                <a href="https://dashboard.stripe.com/test/apikeys" target="_blank" class="text-xs text-purple-500 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-gray-500">Sandbox</span>
                            <input type="checkbox" v-model="form.stripe_sandbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Public Key (pk_...)</label>
                            <input type="text" v-model="form.stripe_public_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="pk_test_xxxxxxxxxxxxxxxxxxxxxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secret Key (sk_...)</label>
                            <input type="password" v-model="form.stripe_secret_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                   placeholder="Stripe Secret Key" />
                        </div>
                        <div class="md:col-span-2">
                             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook Secret (whsec_...)</label>
                             <input type="password" v-model="form.stripe_webhook_secret" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                                    placeholder="whsec_xxxxxxxxxxxxxxxxxxxxxxxx" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_stripe" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Cada pago recibido por Stripe se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grupo CVA API Integration -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50/30 dark:from-gray-700/50 dark:to-blue-900/10 border-b border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <FontAwesomeIcon icon="box-open" class="text-blue-500" />
                            Integración Grupo CVA
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Conecta tu inventario con el catálogo mayorista de Grupo CVA
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.cva_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
            
            <div v-show="form.cva_active" class="p-6 space-y-6 transition-all duration-300">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Usuario CVA</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <FontAwesomeIcon icon="user" class="text-xs" />
                            </span>
                            <input type="text" v-model="form.cva_user" 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all"
                                   placeholder="Tu usuario de CVA" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contraseña CVA</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <FontAwesomeIcon icon="lock" class="text-xs" />
                            </span>
                            <input type="password" v-model="form.cva_password" 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all"
                                   placeholder="••••••••" />
                        </div>
                    </div>
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Sucursal de Envío Predeterminada
                            </label>
                            <select v-model="form.cva_codigo_sucursal" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                                <option :value="1">Guadalajara (Matriz)</option>
                                <option :value="2">México (CDMX)</option>
                                <option :value="3">Monterrey</option>
                                <option :value="4">Puebla</option>
                                <option :value="9">Hermosillo</option>
                                <option :value="23">Culiacán</option>
                                <option :value="26">Mérida</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Paquetería Predeterminada
                            </label>
                            <select v-model="form.cva_paqueteria_envio" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                                <option :value="4">Estafeta</option>
                                <option :value="5">Paquetexpress</option>
                                <option :value="18">FedEx</option>
                                <option :value="1">Sin Flete (Recoge en Sucursal)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Márgenes de Utilidad Escalonados -->
                    <div class="md:col-span-2 mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                                📊 Márgenes de Utilidad por Rango de Precio
                            </label>
                            <button type="button" @click="resetToDefaultTiers" 
                                    class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                Usar Recomendados
                            </button>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                            <div class="space-y-3">
                                <div v-for="(tier, index) in utilityTiers" :key="index" 
                                     class="grid grid-cols-3 gap-3 items-center">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Hasta ${{ tier.max.toLocaleString() }}
                                    </div>
                                    <div class="relative">
                                        <input type="number" v-model.number="tier.percent" 
                                               min="0" max="200" step="1"
                                               class="w-full pr-8 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-center font-semibold" />
                                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 text-sm">%</span>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Precio $100 → ${{ Math.round(100 * (1 + tier.percent/100)) }}
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-3 italic">
                                💡 Márgenes recomendados: 40% para accesorios, 18% para laptops, 10% para equipos enterprise.
                            </p>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <div class="text-xs text-gray-500 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-xl border border-blue-100 dark:border-blue-800">
                            <FontAwesomeIcon icon="info-circle" class="text-blue-500 mr-1" />
                            Los márgenes se aplican automáticamente según el precio de costo del producto CVA.
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="!form.cva_active" class="p-8 text-center bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
                <FontAwesomeIcon icon="link-slash" class="text-3xl text-gray-300 mb-3" />
                <p class="text-sm text-gray-400 font-medium">La integración con CVA está desactivada.</p>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button @click="$emit('save')" 
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg">
                Guardar Configuración
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
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

// Valores por defecto recomendados para la industria
const defaultTiers = [
    { max: 100, percent: 40 },
    { max: 200, percent: 35 },
    { max: 500, percent: 28 },
    { max: 1000, percent: 22 },
    { max: 5000, percent: 18 },
    { max: 10000, percent: 14 },
    { max: 100000, percent: 10 }
]

// Estado reactivo para los tiers
const utilityTiers = ref([...defaultTiers])

// Cargar tiers desde form si existen
onMounted(() => {
    if (props.form.cva_utility_tiers && Array.isArray(props.form.cva_utility_tiers) && props.form.cva_utility_tiers.length > 0) {
        utilityTiers.value = [...props.form.cva_utility_tiers]
    }
})

// Sincronizar cambios de vuelta al form
watch(utilityTiers, (newTiers) => {
    props.form.cva_utility_tiers = [...newTiers]
}, { deep: true })

// Resetear a valores recomendados
const resetToDefaultTiers = () => {
    utilityTiers.value = [...defaultTiers]
}
</script>
