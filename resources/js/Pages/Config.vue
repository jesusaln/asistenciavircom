<template>

    <Head title="Configuracion" />
        <div>
            <h1 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-gray-100">Configuracion de la empresa</h1>
            <!-- Formulario de creación de clientes -->
            <form @submit.prevent="submit">
                <div v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</div>
                <div class="space-y-4">
                    <!-- Nombre/Razón Social -->
                    <div>
                        <label for="nombre_razon_social" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre/Razón Social</label>
                        <input
                            v-model="form.nombre_razon_social"
                            type="text"
                            id="nombre_razon_social"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            @blur="convertirAMayusculas('nombre_razon_social')"
                        />
                        <p v-if="form.errors.nombre_razon_social" class="text-red-500 dark:text-red-400 text-sm">{{ form.errors.nombre_razon_social }}</p>
                    </div>

                    <!-- RFC -->
                    <div>
                        <label for="rfc" class="block text-sm font-medium text-gray-700 dark:text-gray-300">RFC</label>
                        <input
                            v-model="form.rfc"
                            type="text"
                            id="rfc"
                            maxlength="13"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            @blur="validarRFC"
                            required
                        />
                        <p v-if="form.errors.rfc" class="text-red-500 dark:text-red-400 text-sm">{{ form.errors.rfc }}</p>
                    </div>

                   <!-- Régimen Fiscal -->
                   <div>
                        <label for="regimen_fiscal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Régimen Fiscal</label>
                        <select
                            v-model="form.regimen_fiscal"
                            id="regimen_fiscal"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            required
                        >
                            <option value="" disabled>Selecciona un régimen fiscal</option>
                            <option v-for="regimen in regimenesFiscales" :key="regimen" :value="regimen">{{ regimen }}</option>
                        </select>
                    </div>

                    <!-- Uso CFDI -->
                    <div>
                        <label for="uso_cfdi" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Uso CFDI</label>
                        <select
                            v-model="form.uso_cfdi"
                            id="uso_cfdi"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            required
                        >
                            <option value="" disabled>Selecciona un uso CFDI</option>
                            <option v-for="uso in usosCFDI" :key="uso" :value="uso">{{ uso }}</option>
                        </select>
                        <p v-if="form.errors.uso_cfdi" class="text-red-500 dark:text-red-400 text-sm">{{ form.errors.uso_cfdi }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            id="email"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            required
                        />
                        <p v-if="form.errors.email" class="text-red-500 dark:text-red-400 text-sm">{{ form.errors.email }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                        <input
                            v-model="form.telefono"
                            type="text"
                            id="telefono"
                            maxlength="10"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            @input="validarTelefono"
                            required
                        />
                        <p v-if="form.errors.telefono" class="text-red-500 dark:text-red-400 text-sm">{{ form.errors.telefono }}</p>
                    </div>

                    <!-- Calle -->
                    <div>
                        <label for="calle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Calle</label>
                        <input
                            v-model="form.calle"
                            type="text"
                            id="calle"
                            maxlength="40"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            @blur="convertirAMayusculas('calle')"
                            required
                        />
                        <p v-if="form.errors.calle" class="text-red-500 dark:text-red-400 text-sm">{{ form.errors.calle }}</p>
                    </div>

                    <!-- Número Exterior -->
                    <div>
                        <label for="numero_exterior" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número Exterior</label>
                        <input
                            v-model="form.numero_exterior"
                            type="text"
                            id="numero_exterior"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            required
                        />
                    </div>

                    <!-- Número Interior -->
                    <div>
                        <label for="numero_interior" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número Interior</label>
                        <input
                            v-model="form.numero_interior"
                            type="text"
                            id="numero_interior"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                        />
                    </div>

                    <!-- Colonia -->
                    <div>
                        <label for="colonia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Colonia</label>
                        <input
                            v-model="form.colonia"
                            type="text"
                            id="colonia"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            @blur="convertirAMayusculas('colonia')"
                            required
                        />
                    </div>

                    <!-- Código Postal -->
                    <div>
                        <label for="codigo_postal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código Postal</label>
                        <input
                            v-model="form.codigo_postal"
                            type="text"
                            id="codigo_postal"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            required
                        />
                    </div>

                    <!-- Municipio -->
                    <div>
                        <label for="municipio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</label>
                        <input
                            v-model="form.municipio"
                            type="text"
                            id="municipio"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            readonly
                        />
                    </div>

                    <!-- Estado -->
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                        <input
                            v-model="form.estado"
                            type="text"
                            id="estado"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            readonly
                        />
                    </div>

                    <!-- País -->
                    <div>
                        <label for="pais" class="block text-sm font-medium text-gray-700 dark:text-gray-300">País</label>
                        <input
                            v-model="form.pais"
                            type="text"
                            id="pais"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                            readonly
                        />
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-blue-500 dark:bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-600 dark:hover:bg-blue-500">
                        Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </template>
    <script setup>
    import { Head, useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';


    // Define el layout del dashboard
    defineOptions({ layout: AppLayout });

    // Listas predefinidas
    const regimenesFiscales = [
        'Persona Física con Actividad Empresarial',
        'Régimen General de Ley Personas Morales',
        'Régimen Simplificado de Confianza'
    ];

    const usosCFDI = [
        'Gastos en general',
        'Adquisición de mercancías',
        'Honorarios médicos, dentales y gastos hospitalarios'
    ];

    // Formulario para crear un cliente
    const form = useForm({
        nombre_razon_social: '',
        rfc: '',
        regimen_fiscal: '',
        uso_cfdi: '',
        email: '',
        telefono: '',
        calle: '',
        numero_exterior: '',
        numero_interior: '',
        colonia: '',
        codigo_postal: '83000',
        municipio: 'HERMOSILLO', // Valor predeterminado
        estado: 'SONORA',       // Valor predeterminado
        pais: 'MEXICO'          // Valor predeterminado
    });

    // Función para enviar el formulario
    const submit = () => {


        form.post(route('clientes.store'), {
            onSuccess: () => {
                form.reset(); // Limpia el formulario después de guardar
            },
        });
    };

    // Método para convertir a mayúsculas
    const convertirAMayusculas = (campo) => {
        if (form[campo]) {
            form[campo] = form[campo].toUpperCase();
        }
    };

    // Validación del RFC
    const validarRFC = () => {
        const rfcRegex = /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/; // Expresión regular para RFC
        const minLength = 12; // Mínimo de 12 caracteres
        const maxLength = 13; // Máximo de 13 caracteres

        // Convertir el RFC a mayúsculas para validar
        const rfcValue = form.rfc.toUpperCase();

        // Verificar longitud mínima
        if (rfcValue.length < minLength) {
            form.setError('rfc', 'El RFC debe tener al menos 12 caracteres.');
            return;
        }

        // Verificar longitud máxima
        if (rfcValue.length > maxLength) {
            form.setError('rfc', 'El RFC no puede tener más de 13 caracteres.');
            return;
        }

        // Verificar formato con expresión regular
        if (!rfcRegex.test(rfcValue)) {
            form.setError('rfc', 'El RFC no es válido.');
            return;
        }

        // Si pasa todas las validaciones, limpiar el error
        form.clearErrors('rfc');
    };

    // Validación del teléfono
    const validarTelefono = () => {
        const telefonoRegex = /^\d{10}$/;
        if (!telefonoRegex.test(form.telefono)) {
            form.setError('telefono', 'El teléfono debe tener 10 dígitos.');
        }
    };
    </script>

