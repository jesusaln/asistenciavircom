<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="money-bill-wave" class="text-green-600 dark:text-green-400" />
                Impuestos y Moneda
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Porcentaje IVA -->
                <div>
                    <label for="iva_porcentaje" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        IVA (%) *
                    </label>
                    <div class="relative rounded-md shadow-sm">
                                                    <input v-model="form.iva_porcentaje" id="iva_porcentaje" type="number" step="0.01"
                                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-10 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                                                        placeholder="16" />
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">%</span>                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Impuesto al Valor Agregado predeterminado.</p>
                </div>

                <!-- Porcentaje ISR -->
                <div>
                    <label for="isr_porcentaje" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ISR Retención (%)
                    </label>
                    <div class="relative rounded-md shadow-sm">
                                                    <input v-model="form.isr_porcentaje" id="isr_porcentaje" type="number" step="0.01"
                                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-10 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                                                        placeholder="1.25" />
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">%</span>                        </div>
                    </div>
                </div>

                <!-- Moneda -->
                <div>
                    <label for="moneda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Moneda Principal
                    </label>
                    <select v-model="form.moneda" id="moneda"
                         class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200">
                        <option value="MXN">Peso Mexicano (MXN)</option>
                        <option value="USD">Dólar Americano (USD)</option>
                        <option value="EUR">Euro (EUR)</option>
                    </select>
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

