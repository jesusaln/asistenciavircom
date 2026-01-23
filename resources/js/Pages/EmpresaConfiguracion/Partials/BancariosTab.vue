<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="university" class="text-blue-800 dark:text-blue-400" />
                Datos Bancarios
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-6">Esta información aparecerá en las cotizaciones y facturas para que los clientes realicen pagos.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700">
                <!-- Banco -->
                <div>
                    <label for="banco" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre del Banco
                    </label>
                    <input v-model="form.banco" id="banco" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Ej. BBVA, Santander" />
                </div>

                <!-- Titular -->
                <div>
                    <label for="titular" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Titular de la Cuenta
                    </label>
                    <input v-model="form.titular" id="titular" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Nombre del beneficiario" />
                </div>

                <!-- Número de Cuenta -->
                <div>
                    <label for="numero_cuenta" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Número de Cuenta
                    </label>
                    <input v-model="form.numero_cuenta" id="numero_cuenta" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="10 dígitos" />
                </div>

                <!-- CLABE -->
                <div>
                    <label for="clabe" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        CLABE Interbancaria
                    </label>
                    <input v-model="form.clabe" id="clabe" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="18 dígitos" />
                </div>

                <!-- Tarjeta -->
                <div>
                    <label for="numero_tarjeta" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Número de Tarjeta (Opcional)
                    </label>
                    <input v-model="form.numero_tarjeta" id="numero_tarjeta" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="16 dígitos" />
                </div>

                 <!-- Sucursal -->
                <div>
                    <label for="sucursal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Sucursal (Opcional)
                    </label>
                    <input v-model="form.sucursal" id="sucursal" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Nombre o número de sucursal" />
                </div>

                 <!-- Info Adicional -->
                <div class="md:col-span-2">
                    <label for="informacion_adicional_bancaria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Información Adicional
                    </label>
                    <textarea v-model="form.informacion_adicional_bancaria" id="informacion_adicional_bancaria" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Ej. Enviar comprobante al correo..."></textarea>
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

