<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="envelope" class="text-amber-600 dark:text-amber-400" />
                Configuración de Correo (SMTP)
            </h2>

            <!-- Selector de Proveedor -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cargar configuración predefinida</label>
                <div class="flex flex-wrap gap-2">
                    <button v-for="(prov, key) in proveedoresSMTP" :key="key" type="button" @click="aplicarConfiguracionProveedor(key)"
                        class="px-3 py-2 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                        {{ prov.nombre }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Host -->
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Host SMTP</label>
                    <input v-model="form.smtp_host" id="smtp_host" type="text" placeholder="smtp.ejemplo.com"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                </div>

                <!-- Puerto -->
                <div>
                    <label for="smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Puerto</label>
                    <input v-model="form.smtp_port" id="smtp_port" type="number" placeholder="587"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                </div>

                <!-- Usuario -->
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Usuario SMTP</label>
                    <input v-model="form.smtp_username" id="smtp_username" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contraseña</label>
                    <input v-model="form.smtp_password" id="smtp_password" type="password" placeholder="••••••••"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                </div>

                <!-- Encriptación -->
                <div>
                    <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Encriptación</label>
                    <select v-model="form.smtp_encryption" id="smtp_encryption"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="">Ninguna</option>
                    </select>
                </div>
            </div>

            <!-- Remitente -->
            <div class="mt-8 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 pt-6">
                <h3 class="text-md font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-4">Configuración de Remitente</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Correo Remitente</label>
                        <input v-model="form.email_from_address" id="email_from_address" type="email"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                    </div>
                    <div>
                        <label for="email_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre Remitente</label>
                        <input v-model="form.email_from_name" id="email_from_name" type="text"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                    </div>
                </div>
            </div>

            <!-- Prueba de Correo -->
            <div class="mt-8 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-700">
                <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200 mb-3">Probar configuración</h4>
                <div class="flex gap-2">
                    <input v-model="emailPrueba" type="email" placeholder="correo@prueba.com"
                        class="flex-1 px-4 py-2 border border-indigo-200 dark:border-indigo-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" />
                    <button type="button" @click="probarCorreo"
                        class="px-6 py-2 bg-amber-500 text-white font-medium rounded-lg hover:bg-amber-600 transition-colors">
                        Enviar Prueba
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import { notyf } from '@/Utils/notyf.js';

const props = defineProps({
    form: { type: Object, required: true },
});

const emailPrueba = ref('');

const proveedoresSMTP = {
  hostinger: { nombre: 'Hostinger', smtp_host: 'smtp.hostinger.com', smtp_port: 587, smtp_encryption: 'tls' },
  gmail: { nombre: 'Gmail', smtp_host: 'smtp.gmail.com', smtp_port: 587, smtp_encryption: 'tls' },
  outlook: { nombre: 'Outlook', smtp_host: 'smtp-mail.outlook.com', smtp_port: 587, smtp_encryption: 'tls' },
  yahoo: { nombre: 'Yahoo', smtp_host: 'smtp.mail.yahoo.com', smtp_port: 587, smtp_encryption: 'tls' },
  icloud: { nombre: 'iCloud', smtp_host: 'smtp.mail.me.com', smtp_port: 587, smtp_encryption: 'tls' },
  zoho: { nombre: 'Zoho Mail', smtp_host: 'smtp.zoho.com', smtp_port: 587, smtp_encryption: 'tls' },
  personalizado: { nombre: 'Personalizado', smtp_host: '', smtp_port: '', smtp_encryption: '' }
};

const aplicarConfiguracionProveedor = (proveedor) => {
    const config = proveedoresSMTP[proveedor];
    if (config) {
        props.form.smtp_host = config.smtp_host;
        props.form.smtp_port = config.smtp_port;
        props.form.smtp_encryption = config.smtp_encryption;
        if (proveedor === 'personalizado') {
            props.form.smtp_username = '';
            props.form.smtp_password = '';
        }
        notyf.success(`Configuración de ${config.nombre} aplicada`);
    }
};

const probarCorreo = () => {
    if (!emailPrueba.value) {
        notyf.error('Ingresa un correo para la prueba');
        return;
    }
    
    // Check config validity roughly
    if (!props.form.smtp_host || !props.form.smtp_username) {
         notyf.error('Configuración SMTP incompleta');
         return;
    }

    router.post(route('empresa-configuracion.test-email'), {
        email_destino: emailPrueba.value
    }, {
        onSuccess: () => {
             notyf.success('Correo de prueba enviado.');
             emailPrueba.value = '';
        },
        onError: (err) => {
            const msg = err.smtp || err.email_error || err.error || 'Error desconocido';
            notyf.error(msg);
        }
    });
};
</script>

