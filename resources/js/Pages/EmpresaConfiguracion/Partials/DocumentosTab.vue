<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="file-contract" class="text-gray-600" />
                Documentos y Textos Legales
            </h2>

            <div class="grid grid-cols-1 gap-6">
                <!-- Términos y Condiciones -->
                <div>
                    <label for="terminos_condiciones" class="block text-sm font-medium text-gray-700 mb-2">
                        Términos y Condiciones
                    </label>
                    <textarea v-model="form.terminos_condiciones" id="terminos_condiciones" rows="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Términos y condiciones generales del servicio..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Se mostrarán en cotizaciones y contratos.</p>
                </div>

                <!-- Política de Privacidad -->
                <div>
                    <label for="politica_privacidad" class="block text-sm font-medium text-gray-700 mb-2">
                        Política de Privacidad
                    </label>
                    <textarea v-model="form.politica_privacidad" id="politica_privacidad" rows="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Política de tratamiento de datos personales..."></textarea>
                </div>

                <!-- Pies de Página -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                    <div class="md:col-span-2">
                         <h3 class="text-md font-medium text-gray-900 mb-3">Pies de Página en PDF</h3>
                    </div>

                    <div>
                        <label for="pie_pagina_cotizaciones" class="block text-sm font-medium text-gray-700 mb-2">
                            Cotizaciones
                        </label>
                        <textarea v-model="form.pie_pagina_cotizaciones" id="pie_pagina_cotizaciones" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Texto al pie de las cotizaciones..."></textarea>
                    </div>

                    <div>
                        <label for="pie_pagina_ventas" class="block text-sm font-medium text-gray-700 mb-2">
                            Ventas / Recibos
                        </label>
                        <textarea v-model="form.pie_pagina_ventas" id="pie_pagina_ventas" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Texto al pie de los recibos de venta..."></textarea>
                    </div>

                     <div>
                        <label for="pie_pagina_facturas" class="block text-sm font-medium text-gray-700 mb-2">
                            Facturas
                        </label>
                        <textarea v-model="form.pie_pagina_facturas" id="pie_pagina_facturas" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Texto al pie de las facturas fiscales..."></textarea>
                    </div>
                </div>

                <!-- Logo para Reportes -->
                 <div class="pt-6 border-t">
                    <h3 class="text-md font-medium text-gray-900 mb-4">Logo para Documentos PDF</h3>
                    <div class="flex items-center gap-6">
                        <div class="w-32 h-16 border rounded-lg flex items-center justify-center bg-gray-50 overflow-hidden">
                             <img v-if="logoReportesPreview || form.logo_reportes_url" :src="logoReportesPreview || form.logo_reportes_url" class="max-w-full max-h-full object-contain" />
                             <FontAwesomeIcon v-else icon="file-pdf" class="text-gray-300 text-2xl" />
                        </div>
                        <div class="flex-1 max-w-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subir Logo Alternativo</label>
                            <input type="file" @change="handleLogoReportesChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            <p class="text-xs text-gray-500 mt-1">Si se deja vacío, se usará el logo principal. Útil si necesitas un logo diferente (ej. blanco y negro) para impresiones.</p>
                             <div class="mt-2 flex gap-2">
                                <button v-if="logoReportesForm.logo_reportes" @click="subirLogoReportes" type="button" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Subir</button>
                                <button v-if="form.logo_reportes_url" @click="eliminarLogoReportes" type="button" class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded hover:bg-red-200">Borrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { notyf } from '@/Utils/notyf.js';

const props = defineProps({
    form: { type: Object, required: true },
});

const logoReportesPreview = ref(null);
const logoReportesForm = useForm({ logo_reportes: null });

const handleLogoReportesChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoReportesForm.logo_reportes = file;
        logoReportesPreview.value = URL.createObjectURL(file);
    }
};

const subirLogoReportes = () => {
    logoReportesForm.post(route('empresa-configuracion.subir-logo-reportes'), {
        onSuccess: () => {
            notyf.success('Logo de reportes actualizado');
            logoReportesForm.logo_reportes = null;
            window.location.reload();
        },
        onError: () => notyf.error('Error al subir logo')
    });
};

const eliminarLogoReportes = () => {
    if (confirm('¿Eliminar logo de reportes?')) {
        router.delete(route('empresa-configuracion.eliminar-logo-reportes'), {
             onSuccess: () => {
                notyf.success('Logo eliminado');
                window.location.reload();
            }
        });
    }
};
</script>

