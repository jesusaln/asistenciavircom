<template>
    <Head title="Crear Carro" />
    <div class="w-full p-6">
        <!-- Título de la página -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Crear Nuevo Carro</h1>
            <p class="text-gray-600">Completa los siguientes campos para registrar un nuevo vehículo</p>
        </div>

        <!-- Formulario para crear un nuevo carro -->
        <form @submit.prevent="submit" class="bg-white shadow-lg rounded-lg p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Marca -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Marca <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.marca"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="Ej: Toyota, Honda, Ford"
                        :class="{ 'border-red-500': errors.marca }"
                        required
                    >
                    <p v-if="errors.marca" class="text-red-500 text-xs mt-1">{{ errors.marca }}</p>
                </div>

                <!-- Modelo -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Modelo <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.modelo"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="Ej: Corolla, Civic, Focus"
                        :class="{ 'border-red-500': errors.modelo }"
                        required
                    >
                    <p v-if="errors.modelo" class="text-red-500 text-xs mt-1">{{ errors.modelo }}</p>
                </div>

                <!-- Año -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Año <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model.number="form.anio"
                        type="number"
                        :min="1900"
                        :max="new Date().getFullYear() + 1"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="2024"
                        :class="{ 'border-red-500': errors.anio }"
                        required
                    >
                    <p v-if="errors.anio" class="text-red-500 text-xs mt-1">{{ errors.anio }}</p>
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Color <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.color"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
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
                    <p v-if="errors.color" class="text-red-500 text-xs mt-1">{{ errors.color }}</p>
                </div>

                <!-- Precio -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Precio <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-500">$</span>
                        <input
                            v-model.number="form.precio"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="25000.00"
                            :class="{ 'border-red-500': errors.precio }"
                            required
                        >
                    </div>
                    <p v-if="errors.precio" class="text-red-500 text-xs mt-1">{{ errors.precio }}</p>
                </div>

                <!-- Número de Serie -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Número de Serie <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.numero_serie"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="Ej: 1HGBH41JXMN109186"
                        :class="{ 'border-red-500': errors.numero_serie }"
                        required
                    >
                    <p v-if="errors.numero_serie" class="text-red-500 text-xs mt-1">{{ errors.numero_serie }}</p>
                </div>

                <!-- Combustible -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Tipo de Combustible <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.combustible"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        :class="{ 'border-red-500': errors.combustible }"
                        required
                    >
                        <option value="Gasolina">Gasolina</option>
                        <option value="Diésel">Diésel</option>
                        <option value="Eléctrico">Eléctrico</option>
                        <option value="Híbrido">Híbrido</option>
                        <option value="Gas Natural">Gas Natural</option>
                    </select>
                    <p v-if="errors.combustible" class="text-red-500 text-xs mt-1">{{ errors.combustible }}</p>
                </div>

                <!-- Kilometraje -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Kilometraje
                    </label>
                    <div class="relative">
                        <input
                            v-model.number="form.kilometraje"
                            type="number"
                            min="0"
                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="50000"
                            :class="{ 'border-red-500': errors.kilometraje }"
                        >
                        <span class="absolute right-3 top-3 text-gray-500 text-sm">km</span>
                    </div>
                    <p v-if="errors.kilometraje" class="text-red-500 text-xs mt-1">{{ errors.kilometraje }}</p>
                </div>

                <!-- Placa -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Placa
                    </label>
                    <input
                        v-model="form.placa"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 uppercase"
                        placeholder="ABC-123"
                        :class="{ 'border-red-500': errors.placa }"
                    >
                    <p v-if="errors.placa" class="text-red-500 text-xs mt-1">{{ errors.placa }}</p>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        Estado
                    </label>
                    <select
                        v-model="form.activo"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    >
                        <option :value="true">Activo</option>
                        <option :value="false">Inactivo</option>
                    </select>
                    <p v-if="errors.activo" class="text-red-500 text-xs mt-1">{{ errors.activo }}</p>
                </div>
            </div>

            <!-- Foto -->
            <div class="mt-6">
                <label class="block text-gray-700 text-sm font-semibold mb-2">
                    Foto del Vehículo
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition duration-200">
                    <input
                        type="file"
                        @change="onFileChange"
                        accept="image/*"
                        class="hidden"
                        ref="fileInput"
                    >
                    <div v-if="!previewImage" class="cursor-pointer" @click="$refs.fileInput.click()">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">
                            <span class="font-medium text-blue-600 hover:text-blue-500">Haz clic para subir</span> o arrastra y suelta
                        </p>
                        <p class="text-xs text-gray-500">PNG, JPG, JPEG hasta 10MB</p>
                    </div>

                    <div v-if="previewImage" class="relative">
                        <img :src="previewImage" alt="Vista previa" class="w-full rounded-lg shadow-md">
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p v-if="errors.foto" class="text-red-500 text-xs mt-1">{{ errors.foto }}</p>
            </div>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row justify-between items-center mt-8 space-y-4 sm:space-y-0">
                <button
                    type="button"
                    @click="resetForm"
                    class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-white transition duration-200"
                >
                    Limpiar Formulario
                </button>

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full sm:w-auto px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 font-semibold"
                >
                    <span v-if="processing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creando...
                    </span>
                    <span v-else>Crear Carro</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Obtener errores de validación
const page = usePage();
const errors = computed(() => page.props.errors || {});

// Configuración personalizada de Notyf
const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        {
            type: 'success',
            background: '#10B981',
            icon: { className: 'fas fa-check-circle', tagName: 'i', color: '#fff' }
        },
        {
            type: 'error',
            background: '#EF4444',
            icon: { className: 'fas fa-times-circle', tagName: 'i', color: '#fff' }
        },
    ],
});

// Variables reactivas
const form = reactive({
    marca: '',
    modelo: '',
    anio: new Date().getFullYear(),
    color: '',
    precio: '',
    numero_serie: '',
    combustible: 'Gasolina',
    kilometraje: 0,
    placa: '',
    foto: null,
    activo: true,
});

const previewImage = ref(null);
const processing = ref(false);
const fileInput = ref(null);

// Validar archivo
const validateFile = (file) => {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
        notyf.error('Solo se permiten archivos de imagen (JPG, PNG, WEBP)');
        return false;
    }

    if (file.size > maxSize) {
        notyf.error('El archivo es demasiado grande. Máximo 10MB');
        return false;
    }

    return true;
};

// Manejar la selección de archivos
const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file && validateFile(file)) {
        form.foto = file;
        previewImage.value = URL.createObjectURL(file);
    } else {
        form.foto = null;
        previewImage.value = null;
        event.target.value = '';
    }
};

// Remover imagen
const removeImage = () => {
    form.foto = null;
    previewImage.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// Limpiar formulario
const resetForm = () => {
    Object.assign(form, {
        marca: '',
        modelo: '',
        anio: new Date().getFullYear(),
        color: '',
        precio: '',
        numero_serie: '',
        combustible: 'Gasolina',
        kilometraje: 0,
        placa: '',
        foto: null,
        activo: true,
    });
    previewImage.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// Función para enviar el formulario
const submit = async () => {
    if (processing.value) return;

    try {
        processing.value = true;

        const formData = new FormData();

        // Agregar todos los campos del formulario
        Object.keys(form).forEach(key => {
            if (key === 'foto' && form[key]) {
                formData.append(key, form[key]);
            } else if (key === 'activo') {
                formData.append(key, form[key] ? '1' : '0');
            } else if (form[key] !== null && form[key] !== '') {
                formData.append(key, form[key]);
            }
        });

        await router.post('/carros', formData, {
            forceFormData: true,
            onSuccess: (page) => {
                notyf.success('¡El carro ha sido creado exitosamente!');
                resetForm();

                // Opcional: redirigir a la lista de carros
                // router.visit(route('carros.index'));
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);

                // Mostrar el primer error encontrado
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    notyf.error(Array.isArray(firstError) ? firstError[0] : firstError);
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

<style scoped>
/* Estilos adicionales para mejorar la experiencia */
input[type="file"] {
    display: none;
}

.uppercase {
    text-transform: uppercase;
}

/* Animación para el botón de envío */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

