<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="money-bill-wave" class="text-gray-700 dark:text-gray-400" />
                Configuración de Cuentas por Pagar
            </h2>

            <!-- Reporte de Pagos por Email -->
            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 mb-6">
                <h3 class="text-md font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-4">Reporte de Pagos a Proveedores por Email</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300 mb-4">
                    Configura un correo electrónico para recibir un reporte diario con las cuentas por pagar pendientes,
                    incluyendo proveedor, monto y días de atraso.
                </p>
                
                <div class="flex items-center gap-4 mb-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.pagos_reporte_automatico" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">Enviar reporte automáticamente</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <FontAwesomeIcon icon="envelope" class="mr-1 text-gray-700 dark:text-gray-300" />
                            Email para Reporte de Pagos
                        </label>
                        <input 
                            type="email" 
                            v-model="form.email_pagos" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" 
                            placeholder="pagos@empresa.com"
                        >
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">El reporte incluirá: proveedor, monto adeudado y días de vencimiento</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <FontAwesomeIcon icon="clock" class="mr-1 text-gray-700 dark:text-gray-300" />
                            Hora de Envío
                        </label>
                        <input 
                            type="time" 
                            v-model="form.pagos_hora_reporte" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        >
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Hora a la que se enviará el reporte diario</p>
                    </div>
                </div>

                <!-- Días de anticipación -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <FontAwesomeIcon icon="calendar-alt" class="mr-1 text-gray-700 dark:text-gray-300" />
                        Días de anticipación
                    </label>
                    <div class="flex items-center gap-3">
                        <input 
                            type="number" 
                            v-model.number="form.pagos_dias_anticipacion" 
                            min="0"
                            max="30"
                            class="w-24 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        >
                        <span class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300 whitespace-nowrap">días antes del vencimiento</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                        <strong>0</strong> = solo pagos vencidos | 
                        <strong>1-30</strong> = incluir pagos próximos a vencer
                    </p>
                </div>

                <!-- Botones para enviar reporte -->
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                    <div class="flex flex-wrap gap-3">
                        <button 
                            @click="enviarReportePrueba"
                            :disabled="!form.email_pagos || enviandoReporte"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <FontAwesomeIcon v-if="enviandoReporte === 'prueba'" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="flask" />
                            <span>{{ enviandoReporte === 'prueba' ? 'Enviando...' : 'Enviar Prueba' }}</span>
                        </button>
                        <button 
                            @click="enviarReporteReal"
                            :disabled="!form.email_pagos || enviandoReporte"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <FontAwesomeIcon v-if="enviandoReporte === 'real'" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="paper-plane" />
                            <span>{{ enviandoReporte === 'real' ? 'Enviando...' : 'Enviar Reporte Real' }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                        <strong>Prueba:</strong> envía datos de ejemplo | 
                        <strong>Real:</strong> envía pagos reales con {{ form.pagos_dias_anticipacion || 0 }} días de anticipación
                    </p>
                </div>
            </div>

            <!-- Información sobre el reporte -->
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-xl border border-orange-200 dark:border-orange-700">
                <h4 class="text-sm font-medium text-orange-900 dark:text-orange-300 mb-2 flex items-center gap-2">
                    <FontAwesomeIcon icon="info-circle" class="dark:text-orange-400" />
                    ¿Qué incluye el reporte?
                </h4>
                <ul class="text-sm text-orange-800 dark:text-orange-200 list-disc list-inside space-y-1">
                    <li>Lista de cuentas por pagar a proveedores vencidas</li>
                    <li>Lista de pagos próximos a vencer (según días configurados)</li>
                    <li>Nombre del proveedor</li>
                    <li>Monto total adeudado</li>
                    <li>Cantidad de días de atraso o para vencer</li>
                    <li>Concepto del pago</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router } from '@inertiajs/vue3'
import { notyf } from '@/Utils/notyf.js'

const props = defineProps({
    form: { type: Object, required: true },
})

const enviandoReporte = ref(null)

const enviarReporte = (testMode, tipo) => {
    if (!props.form.email_pagos) {
        notyf.error('Primero configura un email de pagos')
        return
    }

    enviandoReporte.value = tipo

    router.post(route('empresa-configuracion.enviar-reporte-pagos'), {
        email: props.form.email_pagos,
        test_mode: testMode
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                notyf.success(page.props.flash.success)
            } else {
                notyf.success(testMode ? 'Reporte de prueba enviado' : 'Reporte real enviado')
            }
        },
        onError: (errors) => {
            if (errors.email) {
                notyf.error(errors.email)
            } else {
                notyf.error('Error al enviar el reporte')
            }
        },
        onFinish: () => {
            enviandoReporte.value = null
        }
    })
}

const enviarReportePrueba = () => enviarReporte(true, 'prueba')
const enviarReporteReal = () => enviarReporte(false, 'real')
</script>

