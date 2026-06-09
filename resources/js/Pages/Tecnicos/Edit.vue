<template>
  <Head title="Editar Técnico" />
  <div class="w-full p-6">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Editar Técnico</h1>
          <p class="text-gray-600 mt-2">Modifica la información del técnico</p>
        </div>
        <Link
          :href="route('tecnicos.index')"
          class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
        >
          <font-awesome-icon :icon="['fas', 'arrow-left']" class="w-4 h-4 mr-2" />
          Volver
        </Link>
      </div>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
      <div class="p-6">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Información Personal -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <font-awesome-icon :icon="['fas', 'user']" class="w-5 h-5 mr-2 text-blue-600" />
              Información Personal
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Nombre -->
              <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                  Nombre *
                </label>
                <input
                  v-model="form.nombre"
                  type="text"
                  id="nombre"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.nombre }"
                  @blur="convertirAMayusculas('nombre')"
                  placeholder="Ingresa el nombre"
                  required
                />
                <p v-if="form.errors.nombre" class="text-red-500 text-sm mt-2 flex items-center">
                  <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                  {{ form.errors.nombre }}
                </p>
              </div>

              <!-- Apellido -->
              <div>
                <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">
                  Apellido *
                </label>
                <input
                  v-model="form.apellido"
                  type="text"
                  id="apellido"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.apellido }"
                  @blur="convertirAMayusculas('apellido')"
                  placeholder="Ingresa el apellido"
                  required
                />
                <p v-if="form.errors.apellido" class="text-red-500 text-sm mt-2 flex items-center">
                  <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                  {{ form.errors.apellido }}
                </p>
              </div>
            </div>
          </div>

          <!-- Información de Contacto -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <font-awesome-icon :icon="['fas', 'phone']" class="w-5 h-5 mr-2 text-green-600" />
              Información de Contacto
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Email -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                  Email *
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <font-awesome-icon :icon="['fas', 'envelope']" class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    v-model="form.email"
                    type="email"
                    id="email"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.email }"
                    placeholder="correo@ejemplo.com"
                    required
                  />
                </div>
                <p v-if="form.errors.email" class="text-red-500 text-sm mt-2 flex items-center">
                  <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                  {{ form.errors.email }}
                </p>
              </div>

              <!-- Teléfono -->
              <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                  Teléfono
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <font-awesome-icon :icon="['fas', 'phone']" class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    v-model="form.telefono"
                    type="text"
                    id="telefono"
                    maxlength="10"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.telefono }"
                    @input="validarTelefono"
                    placeholder="1234567890"
                  />
                </div>
                <p v-if="form.errors.telefono" class="text-red-500 text-sm mt-2 flex items-center">
                  <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                  {{ form.errors.telefono }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-2">
                  Formato: 10 dígitos sin espacios ni guiones
                </p>
              </div>
            </div>
          </div>

          <!-- Dirección -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="w-5 h-5 mr-2 text-purple-600" />
              Dirección
            </h2>

            <div>
              <label for="direccion" class="block text-sm font-medium text-gray-700 mb-2">
                Dirección Completa
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  v-model="form.direccion"
                  type="text"
                  id="direccion"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.direccion }"
                  @blur="convertirAMayusculas('direccion')"
                  placeholder="Calle, número, colonia, ciudad..."
                />
              </div>
              <p v-if="form.errors.direccion" class="text-red-500 text-sm mt-2 flex items-center">
                  <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                  {{ form.errors.direccion }}
              </p>
            </div>
          </div>

          <!-- Usuario Asignado -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <font-awesome-icon :icon="['fas', 'user']" class="w-5 h-5 mr-2 text-orange-600" />
              Usuario Asignado
            </h2>

            <div>
              <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                Usuario del Sistema
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <font-awesome-icon :icon="['fas', 'user']" class="h-5 w-5 text-gray-400" />
                </div>
                <select
                  v-model="form.user_id"
                  id="user_id"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.user_id }"
                >
                  <option value="">Sin usuario asignado</option>
                  <option v-for="usuario in $page.props.usuarios" :key="usuario.id" :value="usuario.id">
                    {{ usuario.name }} ({{ usuario.email }})
                  </option>
                </select>
              </div>
              <p v-if="form.errors.user_id" class="text-red-500 text-sm mt-2 flex items-center">
                  <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                  {{ form.errors.user_id }}
              </p>
              <p class="text-gray-500 text-sm mt-2">
                Opcional: Asigna un usuario del sistema a este técnico para permitir ventas
              </p>
            </div>
          </div>

          <!-- Márgenes y Comisiones -->
          <div class="pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <font-awesome-icon :icon="['fas', 'dollar-sign']" class="w-5 h-5 mr-2 text-green-600" />
              Márgenes y Comisiones
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Margen Venta Productos -->
              <div>
                <label for="margen_venta_productos" class="block text-sm font-medium text-gray-700 mb-2">
                  Margen Productos (%)
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <font-awesome-icon :icon="['fas', 'calculator']" class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    v-model="form.margen_venta_productos"
                    type="number"
                    id="margen_venta_productos"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.margen_venta_productos }"
                    placeholder="0.00"
                  />
                </div>
                <p v-if="form.errors.margen_venta_productos" class="text-red-500 text-sm mt-2 flex items-center">
                    <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                    {{ form.errors.margen_venta_productos }}
                </p>
              </div>

              <!-- Margen Venta Servicios -->
              <div>
                <label for="margen_venta_servicios" class="block text-sm font-medium text-gray-700 mb-2">
                  Margen Servicios (%)
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <font-awesome-icon :icon="['fas', 'calculator']" class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    v-model="form.margen_venta_servicios"
                    type="number"
                    id="margen_venta_servicios"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.margen_venta_servicios }"
                    placeholder="0.00"
                  />
                </div>
                <p v-if="form.errors.margen_venta_servicios" class="text-red-500 text-sm mt-2 flex items-center">
                    <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                    {{ form.errors.margen_venta_servicios }}
                </p>
              </div>

              <!-- Comisión Instalación -->
              <div>
                <label for="comision_instalacion" class="block text-sm font-medium text-gray-700 mb-2">
                  Comisión Instalación ($)
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <font-awesome-icon :icon="['fas', 'dollar-sign']" class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    v-model="form.comision_instalacion"
                    type="number"
                    id="comision_instalacion"
                    step="0.01"
                    min="0"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.comision_instalacion }"
                    placeholder="0.00"
                  />
                </div>
                <p v-if="form.errors.comision_instalacion" class="text-red-500 text-sm mt-2 flex items-center">
                    <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="w-4 h-4 mr-1" />
                    {{ form.errors.comision_instalacion }}
                </p>
              </div>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
            <Link
              :href="route('tecnicos.index')"
              class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-white transition-colors"
            >
              Cancelar
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center"
            >
              <span v-if="form.processing">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Actualizando...
              </span>
              <span v-else class="flex items-center">
                <font-awesome-icon :icon="['fas', 'save']" class="w-4 h-4 mr-2" />
                Guardar Cambios
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Props para recibir los datos del técnico a editar
const props = defineProps({
  tecnico: Object
});

// Formulario para editar un técnico
const form = useForm({
  nombre: props.tecnico.nombre,
  apellido: props.tecnico.apellido,
  email: props.tecnico.email,
  telefono: props.tecnico.telefono,
  direccion: props.tecnico.direccion,
  user_id: props.tecnico.user?.id || '',
  margen_venta_productos: props.tecnico.margen_venta_productos || '',
  margen_venta_servicios: props.tecnico.margen_venta_servicios || '',
  comision_instalacion: props.tecnico.comision_instalacion || ''
});

// Función para enviar el formulario
const submit = () => {
  form.put(route('tecnicos.update', props.tecnico.id), {
    onSuccess: () => {
      // Redirigir o mostrar un mensaje de éxito
    },
  });
};

// Método para convertir a mayúsculas
const convertirAMayusculas = (campo) => {
  if (form[campo]) {
    form[campo] = form[campo].toUpperCase();
  }
};

// Validación del teléfono
const validarTelefono = () => {
  const telefonoRegex = /^\d{10}$/;
  if (form.telefono && !telefonoRegex.test(form.telefono)) {
    form.setError('telefono', 'El teléfono debe tener exactamente 10 dígitos numéricos.');
  } else if (form.telefono) {
    form.clearErrors('telefono');
  }
};
</script>

