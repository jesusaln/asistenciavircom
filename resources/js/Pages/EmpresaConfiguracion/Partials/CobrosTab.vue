<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="file-invoice-dollar" class="text-gray-700" />
                Configuración de Cobranza
            </h2>

            <!-- Reporte de Cobranza por Email -->
            <div class="bg-white p-6 rounded-xl border border-gray-200 mb-6">
                <h3 class="text-md font-medium text-gray-900 mb-4">Reporte de Deudores por Email</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Configura un correo electrónico para recibir un reporte diario con los clientes que tienen deudas pendientes, 
                    incluyendo nombre y teléfono para contactarlos.
                </p>
                
                <div class="flex items-center gap-4 mb-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.cobros_reporte_automatico" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Enviar reporte automáticamente</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <FontAwesomeIcon icon="envelope" class="mr-1" />
                            Email para Reporte de Cobros
                        </label>
                        <input 
                            type="email" 
                            v-model="form.email_cobros" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                            placeholder="cobranza@empresa.com"
                        >
                        <p class="text-xs text-gray-500 mt-1">El reporte incluirá: nombre del cliente, teléfono, monto adeudado y días de atraso</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <FontAwesomeIcon icon="clock" class="mr-1" />
                            Hora de Envío
                        </label>
                        <input 
                            type="time" 
                            v-model="form.cobros_hora_reporte" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-xs text-gray-500 mt-1">Hora a la que se enviará el reporte diario</p>
                    </div>
                </div>

                <!-- Días de anticipación -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <FontAwesomeIcon icon="calendar-alt" class="mr-1" />
                        Días de anticipación
                    </label>
                    <div class="flex items-center gap-3">
                        <input 
                            type="number" 
                            v-model.number="form.cobros_dias_anticipacion" 
                            min="0"
                            max="30"
                            class="w-24 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                        <span class="text-sm text-gray-600">días antes del vencimiento</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>0</strong> = solo deudas vencidas | 
                        <strong>1-30</strong> = incluir deudas próximas a vencer
                    </p>
                </div>

                <!-- Botones para enviar reporte -->
                <div class="mt-6 pt-4 border-t border-gray-100 space-y-3">
                    <div class="flex flex-wrap gap-3">
                        <button 
                            @click="enviarReportePrueba"
                            :disabled="!form.email_cobros || enviandoReporte"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <FontAwesomeIcon v-if="enviandoReporte === 'prueba'" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="flask" />
                            <span>{{ enviandoReporte === 'prueba' ? 'Enviando...' : 'Enviar Prueba' }}</span>
                        </button>
                        <button 
                            @click="enviarReporteReal"
                            :disabled="!form.email_cobros || enviandoReporte"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <FontAwesomeIcon v-if="enviandoReporte === 'real'" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="paper-plane" />
                            <span>{{ enviandoReporte === 'real' ? 'Enviando...' : 'Enviar Reporte Real' }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">
                        <strong>Prueba:</strong> envía datos de ejemplo | 
                        <strong>Real:</strong> envía deudas reales con {{ form.cobros_dias_anticipacion || 0 }} días de anticipación
                    </p>
                </div>
            </div>

            <!-- Información sobre el reporte -->
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-200">
                <h4 class="text-sm font-medium text-blue-900 mb-2 flex items-center gap-2">
                    <FontAwesomeIcon icon="info-circle" />
                    ¿Qué incluye el reporte?
                </h4>
                <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                    <li>Lista de clientes con cuentas por cobrar vencidas</li>
                    <li>Lista de clientes con pagos de préstamos atrasados</li>
                    <li>Nombre completo del cliente</li>
                    <li>Número de teléfono para llamar</li>
                    <li>Monto total adeudado</li>
                    <li>Cantidad de días de atraso</li>
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

const enviandoReporte = ref(null) // null, 'prueba', o 'real'

const enviarReporte = (testMode, tipo) => {
    if (!props.form.email_cobros) {
        notyf.error('Primero configura un email de cobros')
        return
    }

    enviandoReporte.value = tipo

    router.post(route('empresa-configuracion.enviar-reporte-cobros'), {
        email: props.form.email_cobros,
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


