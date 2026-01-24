<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="shopping-cart" class="text-blue-600 dark:text-blue-400" />
                Tienda en Línea
            </h2>
        </div>

        <!-- Toggle Tienda Activa -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white dark:text-white">Activar Tienda en Línea</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                        Permite que los clientes compren productos directamente desde el catálogo
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.tienda_online_activa" class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-blue-600 peer-checked:to-purple-600"></div>
                </label>
            </div>
        </div>

        <!-- OAuth Section -->
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-white dark:bg-slate-900 dark:bg-gray-700/50 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600">
                <h4 class="font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="shield-alt" class="text-blue-500" />
                    Autenticación Social (OAuth)
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                    Permite a los clientes iniciar sesión con sus cuentas de Google o Microsoft
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Google OAuth -->
                <div class="border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <div>
                            <h5 class="font-bold text-gray-900 dark:text-white dark:text-white">Google OAuth</h5>
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                Obtener credenciales →
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                            <input type="text" v-model="form.google_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="xxxxx.apps.googleusercontent.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                            <input type="password" v-model="form.google_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="GOCSPX-xxxxx" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        URI de redirección: <code class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded">{{ baseUrl }}/auth/google/callback</code>
                    </p>
                </div>

                <!-- Microsoft OAuth -->
                <div class="border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8" viewBox="0 0 24 24">
                            <path fill="#F25022" d="M1 1h10v10H1z"/>
                            <path fill="#00A4EF" d="M1 13h10v10H1z"/>
                            <path fill="#7FBA00" d="M13 1h10v10H13z"/>
                            <path fill="#FFB900" d="M13 13h10v10H13z"/>
                        </svg>
                        <div>
                            <h5 class="font-bold text-gray-900 dark:text-white dark:text-white">Microsoft / Outlook</h5>
                            <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                Obtener credenciales →
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                            <input type="text" v-model="form.microsoft_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                            <input type="password" v-model="form.microsoft_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="xxxxx~xxxxx" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        URI de redirección: <code class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded">{{ baseUrl }}/auth/microsoft/callback</code>
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Gateways -->
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-white dark:bg-slate-900 dark:bg-gray-700/50 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600">
                <h4 class="font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="credit-card" class="text-green-500" />
                    Pasarelas de Pago
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                    Configura los métodos de pago para tu tienda
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- MercadoPago -->
                <div class="border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center">
                                <span class="text-blue-600 font-bold text-sm">MP</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900 dark:text-white dark:text-white">MercadoPago</h5>
                                <a href="https://www.mercadopago.com.mx/developers/panel/app" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Sandbox</span>
                            <input type="checkbox" v-model="form.mercadopago_sandbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Access Token</label>
                            <input type="password" v-model="form.mercadopago_access_token" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="APP_USR-xxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Public Key</label>
                            <input type="text" v-model="form.mercadopago_public_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="APP_USR-xxxxx" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_mercadopago" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Cada pago recibido por MercadoPago se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>

                <!-- PayPal -->
                <div class="border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.641.641 0 0 1 .632-.54h6.012c2.66 0 4.507.523 5.49 1.556.455.477.754 1.02.91 1.665.165.692.153 1.515-.034 2.525l-.013.08v.72l.56.312c.472.239.851.512 1.14.825.481.526.79 1.17.918 1.914.132.76.084 1.66-.14 2.676-.26 1.177-.684 2.195-1.261 3.027a6.094 6.094 0 0 1-1.898 1.832c-.723.45-1.55.79-2.459 1.01-.927.225-1.946.339-3.031.339H10.1a.641.641 0 0 0-.633.54l-.763 4.85a.641.641 0 0 1-.632.54l-1-.002z"/>
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900 dark:text-white dark:text-white">PayPal</h5>
                                <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank" class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Sandbox</span>
                            <input type="checkbox" v-model="form.paypal_sandbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                            <input type="text" v-model="form.paypal_client_id" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="AxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxB" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                            <input type="password" v-model="form.paypal_client_secret" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="ExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxC" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_paypal" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Seleccionar Banco para Depósitos --</option>
                            <option v-for="cuenta in cuentas_bancarias" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.nombre }} ({{ cuenta.banco }}) - {{ cuenta.moneda }}
                            </option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Cada pago recibido por PayPal se registrará automáticamente como depósito en esta cuenta.</p>
                    </div>
                </div>

                <!-- Stripe -->
                <div class="border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center">
                                <FontAwesomeIcon icon="credit-card" class="text-purple-600" />
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900 dark:text-white dark:text-white">Stripe</h5>
                                <a href="https://dashboard.stripe.com/test/apikeys" target="_blank" class="text-xs text-purple-500 dark:text-purple-400 hover:underline">
                                    Obtener credenciales →
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Sandbox</span>
                            <input type="checkbox" v-model="form.stripe_sandbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Public Key (pk_...)</label>
                            <input type="text" v-model="form.stripe_public_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="pk_test_xxxxxxxxxxxxxxxxxxxxxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secret Key (sk_...)</label>
                            <input type="password" v-model="form.stripe_secret_key" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                   placeholder="Stripe Secret Key" />
                        </div>
                        <div class="md:col-span-2">
                             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook Secret (whsec_...)</label>
                             <input type="password" v-model="form.stripe_webhook_secret" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm"
                                    placeholder="whsec_xxxxxxxxxxxxxxxxxxxxxxxx" />
                        </div>
                    </div>
                    <!-- Banco Automático -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                            <FontAwesomeIcon icon="university" class="text-blue-500 text-xs" />
                            Cuenta de Destino Automática
                        </label>
                        <select v-model="form.cuenta_id_stripe" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
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
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50/30 dark:from-gray-700/50 dark:to-blue-900/10 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                            <FontAwesomeIcon icon="box-open" class="text-blue-500" />
                            Integración Grupo CVA
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                            Conecta tu inventario con el catálogo mayorista de Grupo CVA
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.cva_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
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
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all"
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
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all"
                                   placeholder="••••••••" />
                        </div>
                    </div>
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Sucursal de Envío Predeterminada
                            </label>
                            <select v-model="form.cva_codigo_sucursal" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
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
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                                <option :value="4">Estafeta</option>
                                <option :value="5">Paquetexpress</option>
                                <option :value="18">FedEx</option>
                                <option :value="1">Sin Flete (Recoge en Sucursal)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Motor de Tipo de Cambio (USD/MXN) -->
                    <div class="md:col-span-2 mb-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Tipo de Cambio Card -->
                            <div class="p-6 bg-blue-600 rounded-[2rem] text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
                                <div class="absolute -right-4 -bottom-4 opacity-10">
                                    <FontAwesomeIcon icon="coins" class="w-32 h-32" />
                                </div>
                                
                                <div class="relative z-10">
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-white/20 rounded-xl">
                                                <FontAwesomeIcon icon="dollar-sign" />
                                            </div>
                                            <h3 class="text-sm font-black uppercase tracking-tight">Tipo de Cambio</h3>
                                        </div>
                                        <label class="flex items-center gap-2 cursor-pointer bg-white/10 px-3 py-1 rounded-full hover:bg-white/20 transition-colors">
                                            <span class="text-[9px] font-black uppercase">Auto</span>
                                            <input type="checkbox" v-model="form.cva_tipo_cambio_auto" class="rounded border-none text-blue-500 focus:ring-0">
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-white/10 p-3 rounded-2xl border border-white/10">
                                            <p class="text-[9px] font-black uppercase text-blue-100 mb-1">Base (CVA)</p>
                                            <div class="flex items-center gap-1">
                                                <span class="text-lg font-black">$</span>
                                                <input type="number" v-model.number="form.cva_tipo_cambio" step="0.0001" 
                                                       class="w-full bg-transparent border-none p-0 text-lg font-black focus:ring-0 placeholder-blue-300"
                                                       :disabled="form.cva_tipo_cambio_auto" />
                                            </div>
                                        </div>

                                        <div class="bg-white p-3 rounded-2xl shadow-inner flex flex-col justify-center">
                                            <p class="text-[9px] font-black uppercase text-blue-600 mb-1">Con Buffer</p>
                                            <p class="text-xl font-black text-blue-900 line-clamp-1">
                                                ${{ ((form.cva_tipo_cambio || 0) * (1 + (form.cva_tipo_cambio_buffer || 0)/100)).toFixed(3) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between">
                                        <p class="text-[8px] text-blue-200 uppercase" v-if="form.cva_tipo_cambio_last_update">
                                            Actualizado: {{ new Date(form.cva_tipo_cambio_last_update).toLocaleTimeString() }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black uppercase text-blue-100">Buffer</span>
                                            <input type="number" v-model.number="form.cva_tipo_cambio_buffer" step="0.1" 
                                                   class="w-12 bg-white/10 border-none rounded-lg py-0.5 text-center text-xs font-black focus:ring-0" />
                                            <span class="text-xs font-black">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pago Automático / Monedero Card -->
                            <div class="p-6 bg-slate-900 rounded-[2rem] text-white shadow-xl shadow-slate-900/20 relative overflow-hidden border border-slate-800">
                                <div class="absolute -right-4 -bottom-4 opacity-10">
                                    <FontAwesomeIcon icon="wallet" class="w-32 h-32 text-slate-400" />
                                </div>
                                
                                <div class="relative z-10 h-full flex flex-col">
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-slate-800 rounded-xl">
                                                <FontAwesomeIcon icon="robot" class="text-cyan-400" />
                                            </div>
                                            <h3 class="text-sm font-black uppercase tracking-tight text-white">Pagos Automáticos</h3>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="form.cva_auto_pago" class="sr-only peer">
                                            <div class="w-10 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 flex-1">
                                        <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/50 flex items-center justify-between group">
                                            <div>
                                                <p class="text-[9px] font-black uppercase text-slate-400 mb-1">Saldo Monedero CVA</p>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-2xl font-black text-cyan-400">${{ parseFloat(form.cva_monedero_balance || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                                                    <span class="text-xs font-bold text-slate-500 uppercase">MXN</span>
                                                </div>
                                            </div>
                                            <button type="button" @click="syncMonedero" 
                                                    :disabled="syncingMonedero"
                                                    class="p-3 bg-slate-700 rounded-xl hover:bg-slate-600 transition-all active:scale-95 disabled:opacity-50 group-hover:shadow-[0_0_15px_-3px_rgba(34,211,238,0.3)]">
                                                <FontAwesomeIcon icon="sync-alt" :pulse="syncingMonedero" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-[8px] text-slate-500 uppercase flex items-center gap-1">
                                            <FontAwesomeIcon icon="clock" class="text-[7px]" />
                                            Última sincronización: {{ form.cva_monedero_last_update ? new Date(form.cva_monedero_last_update).toLocaleString() : 'Nunca' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Motor de Precios Inteligente -->
                    <div class="md:col-span-2 mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-900 dark:text-white dark:text-gray-100">
                                    📊 Motor de Precios Inteligente
                                </label>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Define cuánto quieres ganar según el costo del equipo</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="resetToDefaultTiers" 
                                        class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-200 transition-colors">
                                    Restablecer
                                </button>
                                <button type="button" @click="addTier" 
                                        class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold flex items-center gap-1">
                                    <FontAwesomeIcon icon="plus" class="text-[10px]" />
                                    Nuevo Rango
                                </button>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-gray-100 dark:border-gray-800">
                            <!-- Header de tabla manual -->
                            <div class="grid grid-cols-12 gap-4 mb-3 px-4">
                                <div class="col-span-5 text-[10px] font-black uppercase text-gray-400">Precio de Costo Hasta</div>
                                <div class="col-span-4 text-[10px] font-black uppercase text-gray-400 text-center">Utilidad (%)</div>
                                <div class="col-span-3"></div>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="(tier, index) in utilityTiers" :key="index" 
                                     class="grid grid-cols-12 gap-3 items-center group animate-in fade-in slide-in-from-right-2" 
                                     :style="{ animationDelay: (index * 50) + 'ms' }">
                                    
                                    <!-- Input de Monto Máximo -->
                                    <div class="col-span-5 relative">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">$</span>
                                        <input type="number" v-model.number="tier.max" 
                                               class="w-full pl-7 pr-3 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 transition-all" />
                                    </div>

                                    <!-- Input de Porcentaje -->
                                    <div class="col-span-4 relative">
                                        <input type="number" v-model.number="tier.percent" 
                                               class="w-full pr-8 py-2.5 text-sm font-black text-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 dark:bg-gray-800 text-blue-600 dark:text-blue-400 focus:ring-2 focus:ring-blue-500 transition-all" />
                                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 text-sm">%</span>
                                    </div>

                                    <!-- Botón Eliminar -->
                                    <div class="col-span-3 flex justify-end">
                                        <button type="button" @click="removeTier(index)" 
                                                class="w-10 h-10 flex items-center justify-center rounded-xl text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                            <FontAwesomeIcon icon="trash" class="text-xs" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="utilityTiers.length === 0" class="text-center py-8">
                                <p class="text-sm text-gray-400 italic">No hay rangos definidos. Se usará el 15% global.</p>
                            </div>

                            <div class="mt-6 pt-6 border-t border-gray-200/50 dark:border-gray-800/50">
                                <p class="text-[10px] text-gray-400 leading-relaxed">
                                    <FontAwesomeIcon icon="lightbulb" class="text-amber-500 mr-1" />
                                    <b>Tip:</b> Ordena tus rangos de menor a mayor precio. El sistema buscará el primer rango que cubra el costo del producto. Si un producto supera el último rango, se usará el margen del último nivel.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-xl border border-blue-100 dark:border-blue-800">
                            <FontAwesomeIcon icon="info-circle" class="text-blue-500 mr-1" />
                            Los márgenes se aplican automáticamente según el precio de costo del producto CVA.
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="!form.cva_active" class="p-8 text-center bg-white dark:bg-slate-900/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
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
const syncingMonedero = ref(false)

const syncMonedero = async () => {
    syncingMonedero.value = true
    try {
        const response = await axios.post(route('empresa-configuracion.tienda.sync-monedero'))
        if (response.data.success) {
            props.form.cva_monedero_balance = response.data.balance
            props.form.cva_monedero_last_update = response.data.last_update
        }
    } catch (error) {
        console.error('Error syncing monedero:', error)
    } finally {
        syncingMonedero.value = false
    }
}

// Cargar tiers desde form si existen, o inicializar con defaults
onMounted(() => {
    if (props.form.cva_utility_tiers && Array.isArray(props.form.cva_utility_tiers) && props.form.cva_utility_tiers.length > 0) {
        utilityTiers.value = [...props.form.cva_utility_tiers]
    } else {
        // Inicializar con defaults para que se guarden
        utilityTiers.value = [...defaultTiers]
        props.form.cva_utility_tiers = [...defaultTiers]
    }
})

// Sincronizar cambios de vuelta al form
watch(utilityTiers, (newTiers) => {
    props.form.cva_utility_tiers = [...newTiers]
}, { deep: true })

// Resetear a valores recomendados
const resetToDefaultTiers = () => {
    utilityTiers.value = [...defaultTiers.map(t => ({...t}))]
}

// Añadir un nuevo nivel
const addTier = () => {
    const lastTier = utilityTiers.value[utilityTiers.value.length - 1]
    const nextMax = lastTier ? lastTier.max * 2 : 1000
    utilityTiers.value.push({ max: nextMax, percent: 10 })
    // Ordenar automáticamente por precio máximo
    sortTiers()
}

// Eliminar un nivel
const removeTier = (index) => {
    utilityTiers.value.splice(index, 1)
}

// Ordenar niveles
const sortTiers = () => {
    utilityTiers.value.sort((a, b) => a.max - b.max)
}

// Monitorear cambios en los valores para ordenar
watch(utilityTiers, () => {
    // Solo ordenar si el último cambio fue en un campo 'max'
    // Para simplificar, ordenamos siempre que algo cambie profundamente
    sortTiers()
}, { deep: true })
</script>
