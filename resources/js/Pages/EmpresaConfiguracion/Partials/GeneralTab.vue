<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="building" class="text-blue-600" />
                Información General
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nombre de la empresa -->
                <div class="md:col-span-2">
                    <label for="nombre_empresa" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre de la Empresa *
                    </label>
                    <input v-model="form.nombre_empresa" id="nombre_empresa" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Nombre de tu empresa" />
                    <p v-if="form.errors.nombre_empresa" class="mt-1 text-sm text-red-600 dark:text-red-400 font-medium">
                        {{ form.errors.nombre_empresa }}
                    </p>
                </div>

                <!-- RFC -->
                <div>
                    <label for="rfc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        RFC
                    </label>
                    <input v-model="form.rfc" id="rfc" type="text" maxlength="13"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="XAXX010101000" />
                    <p v-if="form.errors.rfc" class="mt-1 text-sm text-red-600 dark:text-red-400 font-medium">
                        {{ form.errors.rfc }}
                    </p>
                </div>

                <!-- Razón Social -->
                <div>
                    <label for="razon_social" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Razón Social
                    </label>
                    <input v-model="form.razon_social" id="razon_social" type="text"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Razón social completa" />
                    <p v-if="form.errors.razon_social" class="mt-1 text-sm text-red-600 dark:text-red-400 font-medium">
                        {{ form.errors.razon_social }}
                    </p>
                </div>

                <!-- Régimen Fiscal (CFDI 4.0) -->
                <div class="md:col-span-2">
                    <label for="regimen_fiscal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Régimen Fiscal (SAT) *
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-normal ml-2">Requerido para CFDI 4.0</span>
                    </label>
                    <select v-model="form.regimen_fiscal" id="regimen_fiscal"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
                        <option value="">Selecciona un régimen fiscal</option>
                        <option value="601">601 - General de Ley Personas Morales</option>
                        <option value="603">603 - Personas Morales con Fines no Lucrativos</option>
                        <option value="605">605 - Sueldos y Salarios e Ingresos Asimilados a Salarios</option>
                        <option value="606">606 - Arrendamiento</option>
                        <option value="607">607 - Régimen de Enajenación o Adquisición de Bienes</option>
                        <option value="608">608 - Demás ingresos</option>
                        <option value="610">610 - Residentes en el Extranjero sin Establecimiento Permanente en México</option>
                        <option value="611">611 - Ingresos por Dividendos (socios y accionistas)</option>
                        <option value="612">612 - Personas Físicas con Actividades Empresariales y Profesionales</option>
                        <option value="614">614 - Ingresos por intereses</option>
                        <option value="615">615 - Régimen de los ingresos por obtención de premios</option>
                        <option value="616">616 - Sin obligaciones fiscales</option>
                        <option value="620">620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos</option>
                        <option value="621">621 - Incorporación Fiscal</option>
                        <option value="622">622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras</option>
                        <option value="623">623 - Opcional para Grupos de Sociedades</option>
                        <option value="624">624 - Coordinados</option>
                        <option value="625">625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas</option>
                        <option value="626">626 - Régimen Simplificado de Confianza (RESICO)</option>
                    </select>
                    <p v-if="form.errors.regimen_fiscal" class="mt-1 text-sm text-red-600 dark:text-red-400 font-medium">
                        {{ form.errors.regimen_fiscal }}
                    </p>
                </div>

                <!-- Dirección organizada -->
                <div class="md:col-span-2">
                    <h3 class="text-md font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-4 flex items-center gap-2 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700 pb-2">
                        <FontAwesomeIcon icon="map-marker-alt" class="text-gray-400 dark:text-gray-500 dark:text-gray-400" /> Dirección
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Calle -->
                        <div class="md:col-span-2">
                            <label for="calle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Calle *
                            </label>
                            <input v-model="form.calle" id="calle" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="Nombre de la calle" />
                            <p v-if="form.errors.calle" class="mt-1 text-sm text-red-600 dark:text-red-400 font-medium">
                                {{ form.errors.calle }}
                            </p>
                        </div>

                        <!-- Número Exterior -->
                        <div>
                            <label for="numero_exterior" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Número Exterior *
                            </label>
                            <input v-model="form.numero_exterior" id="numero_exterior" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="123" />
                        </div>

                        <!-- Número Interior -->
                        <div>
                            <label for="numero_interior" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Número Interior
                            </label>
                            <input v-model="form.numero_interior" id="numero_interior" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="Int. 4B" />
                        </div>

                        <!-- Código Postal -->
                        <div class="md:col-span-2">
                            <label for="codigo_postal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Código Postal *
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.codigo_postal" id="codigo_postal" type="text" maxlength="5"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                    placeholder="00000" @blur="validarCodigoPostalEmpresa" />
                                <button type="button" @click="validarCodigoPostalEmpresa"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 transition-colors"
                                    title="Buscar CP">
                                    <FontAwesomeIcon icon="search" />
                                </button>
                            </div>
                        </div>

                        <!-- Colonia -->
                        <div>
                            <label for="colonia" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Colonia *
                            </label>
                            <div v-if="colonias.length > 0">
                                <select v-model="form.colonia" id="colonia"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
                                    <option value="">Selecciona una colonia</option>
                                    <option v-for="col in colonias" :key="col" :value="col">{{ col }}</option>
                                </select>
                            </div>
                            <input v-else v-model="form.colonia" id="colonia" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="Nombre de la colonia" />
                        </div>

                        <!-- Ciudad -->
                        <div>
                            <label for="ciudad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ciudad/Municipio *
                            </label>
                            <input v-model="form.ciudad" id="ciudad" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="Ciudad" />
                        </div>

                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Estado *
                            </label>
                            <input v-model="form.estado" id="estado" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="Estado" />
                        </div>

                        <!-- País -->
                        <div>
                            <label for="pais" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                País *
                            </label>
                            <input v-model="form.pais" id="pais" type="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="País" />
                        </div>
                    </div>
                </div>

                <!-- Contacto -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-md font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-4 flex items-center gap-2 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700 pb-2">
                        <FontAwesomeIcon icon="address-book" class="text-gray-400 dark:text-gray-500 dark:text-gray-400" /> Contacto
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Teléfono *
                            </label>
                            <input v-model="form.telefono" id="telefono" type="tel"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="55 1234 5678" />
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Correo Electrónico *
                            </label>
                            <input v-model="form.email" id="email" type="email"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="contacto@empresa.com" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="sitio_web" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sitio Web
                            </label>
                            <input v-model="form.sitio_web" id="sitio_web" type="url"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                placeholder="https://www.miempresa.com" />
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="md:col-span-2 mt-4">
                    <label for="descripcion_empresa" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descripción de la Empresa
                    </label>
                    <textarea v-model="form.descripcion_empresa" id="descripcion_empresa" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                        placeholder="Breve descripción de tu empresa..."></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { notyf } from '@/Utils/notyf.js';

const props = defineProps({
    form: { type: Object, required: true },
});

const colonias = ref([]);

const validarCodigoPostalEmpresa = async () => {
    try {
        props.form.codigo_postal = (props.form.codigo_postal || '').toString().replace(/\D/g, '');
        colonias.value = [];
        // No limpiar colonia inmediatamente para evitar UX brusca si falla
        
        if (props.form.codigo_postal && props.form.codigo_postal.length === 5) {
            const resp = await fetch(`/api/cp/${props.form.codigo_postal}`);
            if (resp.ok) {
                const data = await resp.json();
                if (data.estado) props.form.estado = data.estado;
                if (data.municipio) props.form.ciudad = data.municipio;
                if (data.ciudad && !props.form.ciudad) props.form.ciudad = data.ciudad;
                if (data.pais) props.form.pais = data.pais;
                
                if (Array.isArray(data.colonias)) {
                    colonias.value = data.colonias;
                    if (colonias.value.length === 1) props.form.colonia = colonias.value[0];
                }
                notyf.success('Código postal encontrado');
            } else if (resp.status === 404) {
                 notyf.error(`El código postal ${props.form.codigo_postal} no se encuentra.`);
                 // Limpiar si no se encuentra
                 props.form.estado = '';
                 props.form.ciudad = '';
                 props.form.colonia = '';
            }
        }
    } catch (e) {
        console.error(e);
        notyf.error('Error al consultar código postal');
    }
};
</script>

