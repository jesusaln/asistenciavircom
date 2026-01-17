<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    empresa: Object
});

const form = useForm({
    id: props.empresa?.id || null,
    nombre_razon_social: props.empresa?.nombre_razon_social || '',
    tipo_persona: props.empresa?.tipo_persona || 'moral',
    rfc: props.empresa?.rfc || '',
    regimen_fiscal: props.empresa?.regimen_fiscal || '',
    uso_cfdi: props.empresa?.uso_cfdi || '',
    email: props.empresa?.email || '',
    calle: props.empresa?.calle || '',
    numero_exterior: props.empresa?.numero_exterior || '',
    colonia: props.empresa?.colonia || '',
    codigo_postal: props.empresa?.codigo_postal || '',
    municipio: props.empresa?.municipio || '',
    estado: props.empresa?.estado || '',
    logo: null,
});

const colonias = ref([]);
const loadingCp = ref(false);

watch(() => form.codigo_postal, async (newCp) => {
    if (newCp?.length === 5) {
        loadingCp.value = true;
        colonias.value = [];
        try {
             // Usar API interna
             const res = await fetch(`/api/cp/${newCp}`);
             
             if (res.ok) {
                 const data = await res.json();
                 // API devuelve: { estado: 'Sonora', municipio: 'Hermosillo', colonias: ['A', 'B'], pais: 'México' }
                 
                 form.estado = data.estado;
                 form.municipio = data.municipio;
                 
                 colonias.value = data.colonias || [];
                 
                 if (colonias.value.length === 1) {
                     form.colonia = colonias.value[0];
                 } else {
                     form.colonia = ''; 
                 }
             } else {
                 console.error("CP no encontrado en sistema interno.");
             }

        } catch (e) {
            console.error("Error fetching CP", e);
        } finally {
            loadingCp.value = false;
        }
    }
});

const submit = () => {
    form.post(route('empresas.store'), {
        forceFormData: true,
        onSuccess: () => {
            // Toast handled globally or add success notification
        }
    });
};
</script>

<template>
    <Head title="Mi Empresa" />

    <div class="py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">
                        {{ form.id ? 'Editar Datos de Empresa' : 'Registrar Nueva Empresa' }}
                    </h2>
                    <p class="text-gray-600 text-sm mt-1">
                        Utiliza este formulario para {{ form.id ? 'actualizar' : 'capturar' }} la información fiscal y general de tu empresa.
                    </p>
                </div>

                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Columna Izquierda -->
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="nombre_razon_social" value="Razón Social / Nombre" />
                            <TextInput id="nombre_razon_social" v-model="form.nombre_razon_social" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.nombre_razon_social" class="mt-2" />
                        </div>
                        
                        <div>
                            <InputLabel for="rfc" value="RFC" />
                            <TextInput id="rfc" v-model="form.rfc" type="text" class="mt-1 block w-full uppercase" required maxlength="13" />
                            <InputError :message="form.errors.rfc" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="tipo_persona" value="Tipo de Persona" />
                            <select id="tipo_persona" v-model="form.tipo_persona" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm">
                                <option value="fisica">Persona Física</option>
                                <option value="moral">Persona Moral</option>
                            </select>
                            <InputError :message="form.errors.tipo_persona" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="regimen_fiscal" value="Régimen Fiscal" />
                             <select id="regimen_fiscal" v-model="form.regimen_fiscal" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm" required>
                                <option value="601">601 - General de Ley Personas Morales</option>
                                <option value="603">603 - Personas Morales con Fines no Lucrativos</option>
                                <option value="605">605 - Sueldos y Salarios e Ingresos Asimilados a Salarios</option>
                                <option value="606">606 - Arrendamiento</option>
                                <option value="612">612 - Personas Físicas con Actividades Empresariales y Profesionales</option>
                                <option value="621">621 - Incorporación Fiscal</option>
                                <option value="626">626 - Régimen Simplificado de Confianza</option>
                             </select>
                            <InputError :message="form.errors.regimen_fiscal" class="mt-2" />
                        </div>
                        
                        <div>
                            <InputLabel for="uso_cfdi" value="Uso de CFDI (Default)" />
                             <select id="uso_cfdi" v-model="form.uso_cfdi" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm">
                                <option value="G01">G01 - Adquisición de mercancías</option>
                                <option value="G03">G03 - Gastos en general</option>
                                <option value="P01">P01 - Por definir</option>
                                <option value="S01">S01 - Sin efectos fiscales</option>
                                 <option value="CP01">CP01 - Pagos</option>
                                 <option value="CN01">CN01 - Nómina</option>
                            </select>
                            <InputError :message="form.errors.uso_cfdi" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Correo Electrónico de Contacto" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="calle" value="Calle" />
                            <TextInput id="calle" v-model="form.calle" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.calle" class="mt-2" />
                        </div>

                         <div class="flex gap-4">
                            <div class="w-1/3">
                                <InputLabel for="numero_exterior" value="No. Ext" />
                                <TextInput id="numero_exterior" v-model="form.numero_exterior" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.numero_exterior" class="mt-2" />
                            </div>
                            <div class="w-2/3">
                                <InputLabel for="colonia" value="Colonia" />
                                <select v-if="colonias.length > 0" id="colonia" v-model="form.colonia" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm">
                                   <option v-for="col in colonias" :key="col" :value="col">{{ col }}</option>
                                </select>
                                <TextInput v-else id="colonia" v-model="form.colonia" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.colonia" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex gap-4">
                             <div class="w-1/3">
                                <InputLabel for="codigo_postal" value="C.P." />
                                <TextInput id="codigo_postal" v-model="form.codigo_postal" type="text" class="mt-1 block w-full" required maxlength="5" />
                                <InputError :message="form.errors.codigo_postal" class="mt-2" />
                            </div>
                            <div class="w-2/3">
                                <InputLabel for="municipio" value="Municipio/Alcaldía" />
                                <TextInput id="municipio" v-model="form.municipio" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.municipio" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="estado" value="Estado" />
                             <select id="estado" v-model="form.estado" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm" required>
                                <option value="Aguascalientes">Aguascalientes</option>
                                <option value="Baja California">Baja California</option>
                                <option value="Baja California Sur">Baja California Sur</option>
                                <option value="Campeche">Campeche</option>
                                <option value="Chiapas">Chiapas</option>
                                <option value="Chihuahua">Chihuahua</option>
                                <option value="Ciudad de México">Ciudad de México</option>
                                <option value="Coahuila">Coahuila</option>
                                <option value="Colima">Colima</option>
                                <option value="Durango">Durango</option>
                                <option value="Estado de México">Estado de México</option>
                                <option value="Guanajuato">Guanajuato</option>
                                <option value="Guerrero">Guerrero</option>
                                <option value="Hidalgo">Hidalgo</option>
                                <option value="Jalisco">Jalisco</option>
                                <option value="Michoacán">Michoacán</option>
                                <option value="Morelos">Morelos</option>
                                <option value="Nayarit">Nayarit</option>
                                <option value="Nuevo León">Nuevo León</option>
                                <option value="Oaxaca">Oaxaca</option>
                                <option value="Puebla">Puebla</option>
                                <option value="Querétaro">Querétaro</option>
                                <option value="Quintana Roo">Quintana Roo</option>
                                <option value="San Luis Potosí">San Luis Potosí</option>
                                <option value="Sinaloa">Sinaloa</option>
                                <option value="Sonora">Sonora</option>
                                <option value="Tabasco">Tabasco</option>
                                <option value="Tamaulipas">Tamaulipas</option>
                                <option value="Tlaxcala">Tlaxcala</option>
                                <option value="Veracruz">Veracruz</option>
                                <option value="Yucatán">Yucatán</option>
                                <option value="Zacatecas">Zacatecas</option>
                             </select>
                            <InputError :message="form.errors.estado" class="mt-2" />
                        </div>

                         <div class="mt-4 pt-4 border-t border-gray-100">
                            <InputLabel for="logo" value="Logotipo (Opcional)" />
                            <input type="file" @input="form.logo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-amber-50 file:text-amber-700
                                hover:file:bg-amber-100
                              "/>
                             <p v-if="props.empresa?.logo_path" class="text-xs text-green-600 mt-1">Logo actual cargado.</p>
                             <InputError :message="form.errors.logo" class="mt-2" />
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2 flex justify-end mt-6 pt-6 border-t border-gray-200">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ form.id ? 'Actualizar Empresa' : 'Guardar Empresa' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>




