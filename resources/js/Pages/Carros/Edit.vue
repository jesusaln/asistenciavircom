<!-- //mejoras en el formulario -->
<template>
    <Head title="Editar Carro" />
    <div class="w-full">
        <!-- Título de la página -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Editar Carro</h1>
            <p class="text-gray-600 dark:text-gray-300 mt-2">Actualiza la información del vehículo</p>
        </div>

        <!-- Formulario para editar un carro -->
        <div class="bg-white dark:bg-gray-800 shadow-lg dark:shadow-none rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Información básica -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Marca <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.marca"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.marca }"
                            placeholder="Ej: Toyota, Ford, BMW"
                            required
                        >
                        <p v-if="errors.marca" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.marca }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Modelo <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.modelo"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.modelo }"
                            placeholder="Ej: Corolla, Focus, X3"
                            required
                        >
                        <p v-if="errors.modelo" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.modelo }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Año <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="form.anio"
                            type="number"
                            :min="1900"
                            :max="new Date().getFullYear() + 1"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.anio }"
                            placeholder="2020"
                            required
                        >
                        <p v-if="errors.anio" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.anio }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Color <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.color"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.color }"
                            required
                        >
                            <option value="">Seleccionar Color</option>
                            <option value="Blanco">Blanco</option>
                            <option value="Negro">Negro</option>
                            <option value="Gris">Gris</option>
                            <option value="Gris Plata">Gris Plata</option>
                            <option value="Rojo">Rojo</option>
                            <option value="Azul">Azul</option>
                            <option value="Azul Marino">Azul Marino</option>
                            <option value="Verde">Verde</option>
                            <option value="Amarillo">Amarillo</option>
                            <option value="Naranja">Naranja</option>
                            <option value="Morado">Morado</option>
                            <option value="Rosa">Rosa</option>
                            <option value="Marrón">Marrón</option>
                            <option value="Beige">Beige</option>
                            <option value="Dorado">Dorado</option>
                            <option value="Plateado">Plateado</option>
                            <option value="Bronce">Bronce</option>
                            <option value="Cobre">Cobre</option>
                            <option value="Perla">Perla</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <p v-if="errors.color" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.color }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Precio <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">$</span>
                            <input
                                v-model.number="form.precio"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                                :class="{ 'border-red-500': errors.precio }"
                                placeholder="15000.00"
                                required
                            >
                        </div>
                        <p v-if="errors.precio" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.precio }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Número de Serie <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.numero_serie"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.numero_serie }"
                            placeholder="Ej: 1HGBH41JXMN109186"
                            required
                        >
                        <p v-if="errors.numero_serie" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.numero_serie }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Combustible <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.combustible"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.combustible }"
                            required
                        >
                            <option value="">Selecciona el tipo de combustible</option>
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diésel">Diésel</option>
                            <option value="Eléctrico">Eléctrico</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="Gas Natural">Gas Natural</option>
                        </select>
                        <p v-if="errors.combustible" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.combustible }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kilometraje</label>
                        <div class="relative">
                            <input
                                v-model.number="form.kilometraje"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 pr-12 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                                :class="{ 'border-red-500': errors.kilometraje }"
                                placeholder="150000"
                            >
                            <span class="absolute right-3 top-2 text-gray-500 dark:text-gray-400 text-sm">km</span>
                        </div>
                        <p v-if="errors.kilometraje" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.kilometraje }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Placa</label>
                        <input
                            v-model="form.placa"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 uppercase bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            :class="{ 'border-red-500': errors.placa }"
                            placeholder="ABC-1234"
                            maxlength="8"
                        >
                        <p v-if="errors.placa" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.placa }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
                        <select
                            v-model="form.activo"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                        >
                            <option :value="true">Activo</option>
                            <option :value="false">Inactivo</option>
                        </select>
                        <p v-if="errors.activo" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.activo }}</p>
                    </div>
                </div>

                <!-- Sección de foto -->
                <div class="border-t dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Foto del Vehículo</h3>

                    <!-- Foto actual -->
                    <div v-if="props.carro.foto && !previewImage" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Foto Actual</label>
                        <div class="relative inline-block">
                            <img
                                :src="props.carro.foto"
                                alt="Foto actual del carro"
                                class="w-48 h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm"
                            >
                        </div>
                    </div>

                    <!-- Input de archivo -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ props.carro.foto ? 'Cambiar Foto' : 'Subir Foto' }}
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">Click para subir</span> o arrastra y suelta
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG o JPEG (MAX. 5MB)</p>
                                </div>
                                <input
                                    type="file"
                                    class="hidden"
                                    @change="onFileChange"
                                    accept="image/*"
                                >
                            </label>
                        </div>
                        <p v-if="errors.foto" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.foto }}</p>
                    </div>

                    <!-- Vista previa de nueva imagen -->
                    <div v-if="previewImage" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vista Previa</label>
                        <div class="relative inline-block">
                            <img
                                :src="previewImage"
                                alt="Vista previa"
                                class="w-48 h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm"
                            >
                            <button
                                type="button"
                                @click="removeImage"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition duration-200"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex items-center justify-between pt-6 border-t dark:border-gray-700">
                    <button
                        type="button"
                        @click="goBack"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-white dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200"
                    >
                        <span v-if="processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Actualizando...
                        </span>
                        <span v-else>Actualizar Carro</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Recibe el carro como prop y posibles errores
const props = defineProps({
    carro: Object,
    errors: {
        type: Object,
        default: () => ({})
    }
});

// Configuración personalizada de Notyf
const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        {
            type: 'success',
            background: '#10b981',
            icon: { className: 'fas fa-check-circle', tagName: 'i', color: '#fff' }
        },
        {
            type: 'error',
            background: '#ef4444',
            icon: { className: 'fas fa-times-circle', tagName: 'i', color: '#fff' }
        },
    ],
});

// Variables reactivas
const form = reactive({
    marca: props.carro.marca || '',
    modelo: props.carro.modelo || '',
    anio: props.carro.anio || new Date().getFullYear(),
    color: props.carro.color || '',
    precio: props.carro.precio || 0,
    numero_serie: props.carro.numero_serie || '',
    combustible: props.carro.combustible || '',
    kilometraje: props.carro.kilometraje || 0,
    placa: props.carro.placa || '',
    foto: null,
    activo: props.carro.activo !== undefined ? props.carro.activo : true,
});

const previewImage = ref(null);
const processing = ref(false);
const errors = computed(() => props.errors || {});

// Manejar la selección de archivos
const onFileChange = (event) => {
    const file = event.target.files[0];

    if (!file) {
        form.foto = null;
        previewImage.value = null;
        return;
    }

    // Validar tipo de archivo
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        notyf.error('Solo se permiten archivos JPG, JPEG y PNG');
        event.target.value = '';
        return;
    }

    // Validar tamaño de archivo (5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if (file.size > maxSize) {
        notyf.error('El archivo no puede ser mayor a 5MB');
        event.target.value = '';
        return;
    }

    form.foto = file;
    previewImage.value = URL.createObjectURL(file);
};

// Remover imagen seleccionada
const removeImage = () => {
    form.foto = null;
    previewImage.value = null;
    // Limpiar el input file
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) fileInput.value = '';
};

// Función para regresar
const goBack = () => {
    router.visit('/carros');
};

// Función para enviar el formulario
const submit = async () => {
    if (processing.value) return;

    processing.value = true;

    try {
        // Crear FormData para manejar archivos
        const formData = new FormData();

        // Agregar todos los campos del formulario
        Object.keys(form).forEach(key => {
            if (key === 'foto' && form[key]) {
                formData.append(key, form[key]);
            } else if (key === 'activo') {
                formData.append(key, form[key] ? '1' : '0');
            } else if (key !== 'foto') {
                formData.append(key, form[key] || '');
            }
        });

        // Agregar método PUT para Laravel
        formData.append('_method', 'PUT');

        await router.post(`/carros/${props.carro.id}`, formData, {
            forceFormData: true,
            onSuccess: (page) => {
                notyf.success('¡El carro ha sido actualizado exitosamente!');
                // Limpiar preview si existe
                if (previewImage.value) {
                    URL.revokeObjectURL(previewImage.value);
                    previewImage.value = null;
                }
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);

                // Mostrar errores específicos
                const errorMessages = Object.values(errors).flat();
                if (errorMessages.length > 0) {
                    errorMessages.forEach(message => {
                        notyf.error(message);
                    });
                } else {
                    notyf.error('Hubo errores en el formulario. Por favor revisa los campos.');
                }
            },
            onFinish: () => {
                processing.value = false;
            }
        });
    } catch (error) {
        console.error('Error inesperado:', error);
        notyf.error('Ocurrió un error inesperado. Por favor intenta de nuevo.');
        processing.value = false;
    }
};
</script>

