<template>
    <Head title="Crear Servicio" />
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-sm rounded-lg">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <h1 class="text-2xl font-semibold text-gray-900">Crear Servicio</h1>
                <p class="text-sm text-gray-600 mt-1">Complete la información del servicio</p>
            </div>

            <!-- Navigation Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button
                        @click="activeTab = 'general'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'general'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Información General
                    </button>
                    <button
                        @click="activeTab = 'pricing'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'pricing'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Precios y Duración
                    </button>
                    <button
                        @click="activeTab = 'additional'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'additional'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Información Adicional
                    </button>
                    <button
                        @click="activeTab = 'sat'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'sat'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Información Fiscal (SAT)
                    </button>
                </nav>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submit" class="p-6">
                <!-- Información General Tab -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Servicio <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.nombre"
                                type="text"
                                id="nombre"
                                placeholder="Ingrese el nombre del servicio"
                                class="input-field"
                                @input="generarCodigo"
                            />
                            <div v-if="form.errors.nombre" class="error-message">{{ form.errors.nombre }}</div>
                        </div>

                        <!-- Código -->
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                                Código <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.codigo"
                                type="text"
                                id="codigo"
                                placeholder="Código único del servicio"
                                class="input-field"
                            />
                            <div v-if="form.errors.codigo" class="error-message">{{ form.errors.codigo }}</div>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.categoria_id" id="categoria_id" class="input-field">
                                <option value="">Seleccione una categoría</option>
                                <option v-for="categoria in localCategorias" :key="categoria.id" :value="categoria.id">
                                    {{ categoria.nombre }}
                                </option>
                            </select>
                            <button type="button" class="mt-2 text-sm text-blue-600 hover:underline" @click="showCategoriaModal = true">+ Nueva categoría</button>
                            <div v-if="form.errors.categoria_id" class="error-message">{{ form.errors.categoria_id }}</div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea
                            v-model="form.descripcion"
                            id="descripcion"
                            rows="4"
                            placeholder="Descripción detallada del servicio"
                            class="input-field resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Precios y Duración Tab -->
                <div v-show="activeTab === 'pricing'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Precio -->
                        <div>
                            <label for="precio" class="block text-sm font-medium text-gray-700 mb-2">
                                Precio <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.precio"
                                    type="number"
                                    step="0.01"
                                    id="precio"
                                    placeholder="$ 0.00"
                                    class="input-field"
                                    min="0"
                                />
                            </div>
                            <div v-if="form.errors.precio" class="error-message">{{ form.errors.precio }}</div>
                        </div>

                        <!-- Duración -->
                        <div>
                            <label for="duracion" class="block text-sm font-medium text-gray-700 mb-2">
                                Duración (minutos) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.duracion"
                                type="number"
                                id="duracion"
                                placeholder="60"
                                class="input-field"
                                min="1"
                            />
                            <div v-if="form.errors.duracion" class="error-message">{{ form.errors.duracion }}</div>
                        </div>

                        <!-- Comisión Vendedor -->
                        <div>
                            <label for="comision_vendedor" class="block text-sm font-medium text-gray-700 mb-2">
                                Comisión Vendedor ($)
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.comision_vendedor"
                                    type="number"
                                    step="0.01"
                                    id="comision_vendedor"
                                    placeholder="$ 0.00"
                                    class="input-field"
                                    min="0"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Monto fijo que recibe el vendedor por cada prestación de este servicio
                            </p>
                            <div v-if="form.errors.comision_vendedor" class="error-message">{{ form.errors.comision_vendedor }}</div>
                        </div>
                    </div>

                    <!-- Vista Previa de Costos -->
                    <div v-if="form.precio && form.duracion" class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Resumen del Servicio</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Precio por minuto:</span>
                                <span class="font-medium text-blue-600 ml-2">
                                    ${{ (parseFloat(form.precio) / parseInt(form.duracion)).toFixed(2) }}/min
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600">Duración estimada:</span>
                                <span class="font-medium text-gray-900 ml-2">
                                    {{ form.duracion }} minutos
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional Tab -->
                <div v-show="activeTab === 'additional'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.estado" id="estado" class="input-field">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                            <div v-if="form.errors.estado" class="error-message">{{ form.errors.estado }}</div>
                        </div>

                        <!-- Es Instalación -->
                        <div>
                            <label for="es_instalacion" class="block text-sm font-medium text-gray-700 mb-2">
                                ¿Es servicio de instalación?
                            </label>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.es_instalacion"
                                        type="radio"
                                        name="es_instalacion"
                                        :value="true"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Sí</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.es_instalacion"
                                        type="radio"
                                        name="es_instalacion"
                                        :value="false"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Si es instalación, se aplicará comisión adicional al técnico
                            </p>
                            <div v-if="form.errors.es_instalacion" class="error-message">{{ form.errors.es_instalacion }}</div>
                        </div>
                    </div>
                </div>

                <!-- Información Fiscal Tab -->
                <div v-show="activeTab === 'sat'" class="space-y-6">
                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg mb-6 flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-blue-800 leading-relaxed">
                            Configure la información necesaria para el timbrado de facturas CFDI 4.0. Estos datos son obligatorios si planea emitir facturas electrónicas para este servicio.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Clave SAT -->
                        <div class="md:col-span-2">
                            <SatClaveProdServSearch 
                                v-model="form.sat_clave_prod_serv"
                                :error="form.errors.sat_clave_prod_serv"
                            />
                        </div>

                        <!-- Clave Unidad SAT -->
                        <div>
                            <label for="sat_clave_unidad" class="block text-sm font-medium text-gray-700 mb-2">
                                Unidad SAT <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.sat_clave_unidad" id="sat_clave_unidad" class="input-field">
                                <option value="">Seleccione una unidad SAT</option>
                                <option v-for="unidad in satCatalogos.unidades" :key="unidad.clave" :value="unidad.clave">
                                    {{ unidad.nombre }} ({{ unidad.clave }})
                                </option>
                            </select>
                            <div v-if="form.errors.sat_clave_unidad" class="error-message">{{ form.errors.sat_clave_unidad }}</div>
                        </div>

                        <!-- Objeto Impuesto -->
                        <div>
                            <label for="sat_objeto_imp" class="block text-sm font-medium text-gray-700 mb-2">
                                Objeto de Impuesto <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.sat_objeto_imp" id="sat_objeto_imp" class="input-field">
                                <option v-for="obj in satCatalogos.objetosImp" :key="obj.clave" :value="obj.clave">
                                    {{ obj.nombre }} ({{ obj.clave }})
                                </option>
                            </select>
                            <div v-if="form.errors.sat_objeto_imp" class="error-message">{{ form.errors.sat_objeto_imp }}</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button
                        type="button"
                        @click="$inertia.visit(route('servicios.index'))"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar Servicio</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal para crear categoría rápida -->
        <div v-if="showCategoriaModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Nueva categoría</h3>
                <input v-model="quickCategoria.nombre" type="text" placeholder="Nombre" class="input-field mb-2" />
                <textarea v-model="quickCategoria.descripcion" placeholder="Descripción (opcional)" class="input-field mb-4"></textarea>
                <div class="flex justify-end space-x-2">
                    <button class="px-3 py-2 bg-gray-200 rounded" @click="closeCategoriaModal">Cancelar</button>
                    <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="crearCategoriaRapida" :disabled="savingQuick">{{ savingQuick ? 'Guardando...' : 'Guardar' }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Recibir las relaciones desde el backend
const props = defineProps({
    categorias: Array,
    satCatalogos: {
        type: Object,
        default: () => ({ unidades: [], objetosImp: [] })
    }
});

// Crear copia reactiva local de categorías para poder modificarla
const localCategorias = ref([...props.categorias]);

// Estado para las pestañas
const activeTab = ref('general');

// Estado para el modal de categorías
const showCategoriaModal = ref(false);
const savingQuick = ref(false);
const quickCategoria = ref({ nombre: '', descripcion: '' });

// Formulario para crear un servicio
const form = useForm({
    nombre: '',
    descripcion: '',
    codigo: '',
    categoria_id: '',
    precio: '',
    duracion: '',
    estado: 'activo',
    es_instalacion: false,
    comision_vendedor: 0,
    sat_clave_prod_serv: '',
    sat_clave_unidad: 'E48',
    sat_objeto_imp: '02',
});

// Generar código automáticamente
const generarCodigo = () => {
    if (!form.codigo && form.nombre) {
        const codigo = form.nombre
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '')
            .substring(0, 10) + '-' + Math.floor(Math.random() * 100).toString().padStart(3, '0');
        form.codigo = codigo;
    }
};

// Validar formulario y navegar a la tab con errores
const validateAndNavigate = () => {
    // Validar campos de la tab 'general'
    if (!form.nombre || !form.codigo || !form.categoria_id) {
        activeTab.value = 'general';
        return false;
    }
    // Validar campos de la tab 'pricing'
    if (!form.precio || !form.duracion) {
        activeTab.value = 'pricing';
        return false;
    }
    // Validar campos de la tab 'additional'
    if (!form.estado) {
        activeTab.value = 'additional';
        return false;
    }
    return true;
};

// Enviar formulario
const submit = () => {
    if (!validateAndNavigate()) {
        return;
    }
    
    // Asegurar que comision_vendedor no sea null o vacío
    if (!form.comision_vendedor && form.comision_vendedor !== 0) {
        form.comision_vendedor = 0;
    }
    
    form.post(route('servicios.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};

// Funciones para manejar el modal de categorías
const closeCategoriaModal = () => {
    showCategoriaModal.value = false;
    quickCategoria.value = { nombre: '', descripcion: '' };
};

const crearCategoriaRapida = async () => {
    if (!quickCategoria.value.nombre?.trim()) return;
    savingQuick.value = true;

    try {
        const apiUrl = `${window.location.origin}/api/categorias`;
        console.log('Creando categoría en:', apiUrl);

        // Obtener token CSRF de manera más segura
        const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
        if (!csrfTokenElement) {
            throw new Error('Token CSRF no encontrado');
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfTokenElement.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                nombre: quickCategoria.value.nombre.trim(),
                descripcion: quickCategoria.value.descripcion?.trim() || null
            })
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', response.status, errorText);
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        const nuevaCategoria = await response.json();
        console.log('Categoría creada exitosamente:', nuevaCategoria);

        // Agregar la nueva categoría a la lista local (usando ref local)
        localCategorias.value.push(nuevaCategoria);

        // Seleccionar automáticamente la nueva categoría
        form.categoria_id = nuevaCategoria.id;

        // Cerrar modal y limpiar formulario
        closeCategoriaModal();

        // Mostrar mensaje de éxito
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: 'Categoría creada exitosamente'
        });

    } catch (error) {
        console.error('Error completo creando categoría:', error);

        let errorMessage = 'Error al crear la categoría. ';
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            errorMessage += 'Verifique su conexión a internet.';
        } else if (error.message.includes('Token CSRF no encontrado')) {
            errorMessage += 'Error de seguridad. Recargue la página.';
        } else if (error.message.includes('403')) {
            errorMessage += 'No tiene permisos para crear categorías.';
        } else if (error.message.includes('422')) {
            errorMessage += 'El nombre de la categoría ya existe o los datos no son válidos.';
        } else {
            errorMessage += 'Por favor, inténtelo de nuevo.';
        }

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage
        });
    } finally {
        savingQuick.value = false;
    }
};
</script>

<style scoped>
@import "tailwindcss" reference;
.input-field {
    width: 100%;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-width: 1px;
    border-style: solid;
    border-color: #D1D5DB; /* gray-300 */
    border-radius: 0.375rem; /* rounded-md */
    box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); /* shadow-sm */
    color: #111827; /* text-gray-900 */
}

.input-field::placeholder {
    color: #9CA3AF; /* gray-400 */
}

.input-field:focus {
    outline: none;
    border-color: #3B82F6; /* blue-500 */
    box-shadow: 0 0 0 2px rgba(59,130,246,0.15); /* approximate focus ring */
}

.input-field option {
    color: #111827; /* text-gray-900 */
    background-color: #ffffff; /* bg-white */
}

.error-message {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #DC2626; /* red-600 */
}

/* Estilos adicionales para el modal */
.input-field:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

