<template>
  <Head title="Editar Proveedor" />
  <div class="w-full px-4 py-6">
    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Editar Proveedor</h1>
          <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Actualiza la información del proveedor {{ props.proveedor.nombre_razon_social }}
          </p>
        </div>
        <button
          @click="$inertia.visit(route('proveedores.index'))"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Volver
        </button>
      </div>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white dark:bg-slate-900 shadow-xl rounded-lg overflow-hidden">
      <form @submit.prevent="submit" class="divide-y divide-gray-200 dark:divide-slate-800">

        <!-- Global Error Display -->
        <div v-if="Object.keys(form.errors).length" class="bg-red-50 border-l-4 border-red-400 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Errores en el formulario</h3>
              <div class="mt-2 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                  <li v-for="(error, field) in form.errors" :key="field">
                    <span class="font-medium">{{ getFieldLabel(field) }}:</span> {{ error }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 1: Información General -->
        <div class="px-6 py-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Información General
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre/Razón Social -->
            <div class="md:col-span-2">
              <label for="nombre_razon_social" class="block text-sm font-medium text-gray-700 mb-2">
                Nombre/Razón Social *
              </label>
              <input
                v-model="form.nombre_razon_social"
                type="text"
                id="nombre_razon_social"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.nombre_razon_social
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]"
                @blur="convertirAMayusculas('nombre_razon_social')"
                @focus="form.clearErrors('nombre_razon_social')"
                required
                placeholder="Ingrese el nombre o razón social"
              />
              <p v-if="form.errors.nombre_razon_social" class="mt-1 text-sm text-red-600">
                {{ form.errors.nombre_razon_social }}
              </p>
            </div>

            <!-- Tipo de Persona -->
            <div>
              <label for="tipo_persona" class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de Persona *
              </label>
              <select
                v-model="form.tipo_persona"
                id="tipo_persona"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.tipo_persona
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]"
                @change="handleTipoPersonaChange"
                @focus="form.clearErrors('tipo_persona')"
                required
              >
                <option value="">Seleccione...</option>
                <option value="fisica">Persona Física</option>
                <option value="moral">Persona Moral</option>
              </select>
              <p v-if="form.errors.tipo_persona" class="mt-1 text-sm text-red-600">
                {{ form.errors.tipo_persona }}
              </p>
            </div>

            <!-- RFC -->
            <div>
              <label for="rfc" class="block text-sm font-medium text-gray-700 mb-2">
                RFC *
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  ({{ form.tipo_persona === 'fisica' ? '13 caracteres' : form.tipo_persona === 'moral' ? '12 caracteres' : '' }})
                </span>
              </label>
              <input
                v-model="form.rfc"
                type="text"
                id="rfc"
                :maxlength="form.tipo_persona === 'fisica' ? 13 : 12"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors uppercase',
                  form.errors.rfc
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : rfcValidationClass
                ]"
                @input="handleRfcInput"
                @focus="form.clearErrors('rfc')"
                :placeholder="form.tipo_persona === 'fisica' ? 'ABCD123456789' : form.tipo_persona === 'moral' ? 'ABC123456789' : 'Seleccione tipo de persona'"
                :disabled="!form.tipo_persona"
                required
              />
              <div class="mt-1 flex items-center justify-between">
                <p v-if="form.errors.rfc" class="text-sm text-red-600">
                  {{ form.errors.rfc }}
                </p>
                <span v-if="form.rfc && !form.errors.rfc" class="text-xs text-green-600 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  RFC válido
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Información Fiscal -->
        <div class="px-6 py-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Información Fiscal
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Régimen Fiscal -->
            <div>
              <label for="regimen_fiscal" class="block text-sm font-medium text-gray-700 mb-2">
                Régimen Fiscal *
              </label>
              <select
                v-model="form.regimen_fiscal"
                id="regimen_fiscal"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.regimen_fiscal
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]"
                @focus="form.clearErrors('regimen_fiscal')"
                required
              >
                <option value="">Seleccione un régimen...</option>
                <option v-for="regimen in regimenesFiscales" :key="regimen.codigo" :value="regimen.codigo">
                  {{ regimen.codigo }} - {{ regimen.descripcion }}
                </option>
              </select>
              <p v-if="form.errors.regimen_fiscal" class="mt-1 text-sm text-red-600">
                {{ form.errors.regimen_fiscal }}
              </p>
            </div>

            <!-- Uso CFDI -->
            <div>
              <label for="uso_cfdi" class="block text-sm font-medium text-gray-700 mb-2">
                Uso CFDI *
              </label>
              <select
                v-model="form.uso_cfdi"
                id="uso_cfdi"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.uso_cfdi
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]"
                @focus="form.clearErrors('uso_cfdi')"
                required
              >
                <option value="">Seleccione un uso...</option>
                <option v-for="uso in usosCFDI" :key="uso.codigo" :value="uso.codigo">
                  {{ uso.codigo }} - {{ uso.descripcion }}
                </option>
              </select>
              <p v-if="form.errors.uso_cfdi" class="mt-1 text-sm text-red-600">
                {{ form.errors.uso_cfdi }}
              </p>
            </div>
          </div>
        </div>

        <!-- Section 3: Información de Contacto -->
        <div class="px-6 py-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Información de Contacto
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email *
              </label>
              <input
                v-model="form.email"
                type="email"
                id="email"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.email
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : emailValidationClass
                ]"
                @input="validateEmail"
                @focus="form.clearErrors('email')"
                placeholder="correo@ejemplo.com"
                required
              />
              <div class="mt-1 flex items-center justify-between">
                <p v-if="form.errors.email" class="text-sm text-red-600">
                  {{ form.errors.email }}
                </p>
                <span v-if="isValidEmail && !form.errors.email" class="text-xs text-green-600 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Email válido
                </span>
              </div>
            </div>

            <!-- Teléfono -->
            <div>
              <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                Teléfono *
                <span class="text-xs text-gray-500 dark:text-gray-400">(10 dígitos)</span>
              </label>
              <input
                v-model="form.telefono"
                type="tel"
                id="telefono"
                maxlength="10"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.telefono
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : telefonoValidationClass
                ]"
                @input="handleTelefonoInput"
                @focus="form.clearErrors('telefono')"
                placeholder="5512345678"
                required
              />
              <div class="mt-1 flex items-center justify-between">
                <p v-if="form.errors.telefono" class="text-sm text-red-600">
                  {{ form.errors.telefono }}
                </p>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ form.telefono?.length || 0 }}/10 dígitos
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 4: Dirección -->
        <div class="px-6 py-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Dirección
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Calle -->
            <div class="lg:col-span-2">
              <label for="calle" class="block text-sm font-medium text-gray-700 mb-2">
                Calle *
              </label>
              <input
                v-model="form.calle"
                type="text"
                id="calle"
                maxlength="40"
                :class="[
                  'block w-full rounded-md shadow-sm transition-colors',
                  form.errors.calle
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]"
                @blur="convertirAMayusculas('calle')"
                @focus="form.clearErrors('calle')"
                placeholder="Nombre de la calle"
                required
              />
              <p v-if="form.errors.calle" class="mt-1 text-sm text-red-600">
                {{ form.errors.calle }}
              </p>
            </div>

            <!-- Número Exterior -->
            <div>
              <label for="numero_exterior" class="block text-sm font-medium text-gray-700 mb-2">
                Número Exterior *
              </label>
              <input
                v-model="form.numero_exterior"
                type="text"
                id="numero_exterior"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                placeholder="123"
                required
              />
            </div>

            <!-- Número Interior -->
            <div>
              <label for="numero_interior" class="block text-sm font-medium text-gray-700 mb-2">
                Número Interior
              </label>
              <input
                v-model="form.numero_interior"
                type="text"
                id="numero_interior"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                placeholder="A, 101, etc."
              />
            </div>

            <!-- Colonia -->
            <div>
              <label for="colonia" class="block text-sm font-medium text-gray-700 mb-2">
                Colonia *
              </label>
              <input
                v-model="form.colonia"
                type="text"
                id="colonia"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                @blur="convertirAMayusculas('colonia')"
                placeholder="Nombre de la colonia"
                required
              />
            </div>

            <!-- Código Postal -->
            <div>
              <label for="codigo_postal" class="block text-sm font-medium text-gray-700 mb-2">
                Código Postal *
              </label>
              <input
                v-model="form.codigo_postal"
                type="text"
                id="codigo_postal"
                maxlength="5"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                @input="validateCodigoPostal"
                placeholder="12345"
                required
              />
            </div>

            <!-- Municipio -->
            <div>
              <label for="municipio" class="block text-sm font-medium text-gray-700 mb-2">
                Municipio
              </label>
              <input
                v-model="form.municipio"
                type="text"
                id="municipio"
                class="block w-full rounded-md border-gray-300 shadow-sm bg-white dark:bg-slate-900 text-gray-600 dark:text-gray-300"
                readonly
                placeholder="Se autocompleta"
              />
            </div>

            <!-- Estado -->
            <div>
              <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                Estado
              </label>
              <input
                v-model="form.estado"
                type="text"
                id="estado"
                class="block w-full rounded-md border-gray-300 shadow-sm bg-white dark:bg-slate-900 text-gray-600 dark:text-gray-300"
                readonly
                placeholder="Se autocompleta"
              />
            </div>

            <!-- País -->
            <div>
              <label for="pais" class="block text-sm font-medium text-gray-700 mb-2">
                País
              </label>
              <input
                v-model="form.pais"
                type="text"
                id="pais"
                class="block w-full rounded-md border-gray-300 shadow-sm bg-white dark:bg-slate-900 text-gray-600 dark:text-gray-300"
                readonly
                placeholder="Se autocompleta"
              />
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="px-6 py-4 bg-white dark:bg-slate-900 flex items-center justify-between">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Los campos marcados con * son obligatorios
          </div>
          <div class="flex space-x-3">
            <button
              type="button"
              @click="resetForm"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
              :disabled="form.processing"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Resetear
            </button>
            <button
              type="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 hover:shadow-lg transform hover:-translate-y-0.5'
              ]"
            >
              <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              {{ form.processing ? 'Actualizando...' : 'Actualizar Proveedor' }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Success Toast -->
    <div
      v-if="showSuccessToast"
      class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300"
      :class="showSuccessToast ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'"
    >
      <div class="flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        ¡Proveedor actualizado correctamente!
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  proveedor: Object,
});

// Reactive references
const showSuccessToast = ref(false);
const isValidEmail = ref(false);

// Listas predefinidas (más completas)
// Listas predefinidas (objeto con códigos)
const regimenesFiscales = [
  { codigo: '601', descripcion: 'General de Ley Personas Morales' },
  { codigo: '603', descripcion: 'Personas Morales con Fines no Lucrativos' },
  { codigo: '605', descripcion: 'Sueldos y Salarios e Ingresos Asimilados a Salarios' },
  { codigo: '606', descripcion: 'Arrendamiento' },
  { codigo: '607', descripcion: 'Régimen de Enajenación o Adquisición de Bienes' },
  { codigo: '608', descripcion: 'Demás ingresos' },
  { codigo: '610', descripcion: 'Residentes en el Extranjero sin Establecimiento Permanente en México' },
  { codigo: '611', descripcion: 'Ingresos por Dividendos (socios y accionistas)' },
  { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales' },
  { codigo: '614', descripcion: 'Ingresos por intereses' },
  { codigo: '615', descripcion: 'Régimen de los ingresos por obtención de premios' },
  { codigo: '616', descripcion: 'Sin obligaciones fiscales' },
  { codigo: '620', descripcion: 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos' },
  { codigo: '621', descripcion: 'Incorporación Fiscal' },
  { codigo: '622', descripcion: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
  { codigo: '623', descripcion: 'Opcional para Grupos de Sociedades' },
  { codigo: '624', descripcion: 'Coordinados' },
  { codigo: '625', descripcion: 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas' },
  { codigo: '626', descripcion: 'Régimen Simplificado de Confianza' }
];

const usosCFDI = [
  { codigo: 'G01', descripcion: 'Adquisición de mercancías' },
  { codigo: 'G02', descripcion: 'Devoluciones, descuentos o bonificaciones' },
  { codigo: 'G03', descripcion: 'Gastos en general' },
  { codigo: 'I01', descripcion: 'Construcciones' },
  { codigo: 'I02', descripcion: 'Mobilario y equipo de oficina por inversiones' },
  { codigo: 'I03', descripcion: 'Equipo de transporte' },
  { codigo: 'I04', descripcion: 'Equipo de computo y accesorios' },
  { codigo: 'I05', descripcion: 'Dados, troqueles, moldes, matrices y herramental' },
  { codigo: 'I06', descripcion: 'Comunicaciones telefónicas' },
  { codigo: 'I07', descripcion: 'Comunicaciones satelitales' },
  { codigo: 'I08', descripcion: 'Otra maquinaria y equipo' },
  { codigo: 'D01', descripcion: 'Honorarios médicos, dentales y gastos hospitalarios' },
  { codigo: 'D02', descripcion: 'Gastos médicos por incapacidad o discapacidad' },
  { codigo: 'D03', descripcion: 'Gastos funerales' },
  { codigo: 'D04', descripcion: 'Donativos' },
  { codigo: 'D05', descripcion: 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación)' },
  { codigo: 'D06', descripcion: 'Aportaciones voluntarias al SAR' },
  { codigo: 'D07', descripcion: 'Primas por seguros de gastos médicos' },
  { codigo: 'D08', descripcion: 'Gastos de transportación escolar obligatoria' },
  { codigo: 'D09', descripcion: 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones' },
  { codigo: 'D10', descripcion: 'Pagos por servicios educativos (colegiaturas)' },
  { codigo: 'P01', descripcion: 'Por definir' },
  { codigo: 'S01', descripcion: 'Sin efectos fiscales' }
];

// Field labels mapping for better error display
const fieldLabels = {
  nombre_razon_social: 'Nombre/Razón Social',
  tipo_persona: 'Tipo de Persona',
  rfc: 'RFC',
  regimen_fiscal: 'Régimen Fiscal',
  uso_cfdi: 'Uso CFDI',
  email: 'Email',
  telefono: 'Teléfono',
  calle: 'Calle',
  numero_exterior: 'Número Exterior',
  numero_interior: 'Número Interior',
  colonia: 'Colonia',
  codigo_postal: 'Código Postal',
  municipio: 'Municipio',
  estado: 'Estado',
  pais: 'País'
};

// Form initialization
const form = useForm({
  nombre_razon_social: props.proveedor.nombre_razon_social || '',
  tipo_persona: props.proveedor.tipo_persona || '',
  rfc: props.proveedor.rfc || '',
  regimen_fiscal: props.proveedor.regimen_fiscal || '',
  uso_cfdi: props.proveedor.uso_cfdi || '',
  email: props.proveedor.email || '',
  telefono: props.proveedor.telefono || '',
  calle: props.proveedor.calle || '',
  numero_exterior: props.proveedor.numero_exterior || '',
  numero_interior: props.proveedor.numero_interior || '',
  colonia: props.proveedor.colonia || '',
  codigo_postal: props.proveedor.codigo_postal || '',
  municipio: props.proveedor.municipio || '',
  estado: props.proveedor.estado || '',
  pais: props.proveedor.pais || 'México'
});

// Store original form data for reset functionality
const originalFormData = { ...form.data() };

// Computed properties for validation classes
const rfcValidationClass = computed(() => {
  if (!form.rfc) return 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
  if (form.errors.rfc) return 'border-red-300 focus:border-red-500 focus:ring-red-500';
  return isRfcValid.value ? 'border-green-300 focus:border-green-500 focus:ring-green-500' : 'border-red-300 focus:border-red-500 focus:ring-red-500';
});

const emailValidationClass = computed(() => {
  if (!form.email) return 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
  if (form.errors.email) return 'border-red-300 focus:border-red-500 focus:ring-red-500';
  return isValidEmail.value ? 'border-green-300 focus:border-green-500 focus:ring-green-500' : 'border-red-300 focus:border-red-500 focus:ring-red-500';
});

const telefonoValidationClass = computed(() => {
  if (!form.telefono) return 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
  if (form.errors.telefono) return 'border-red-300 focus:border-red-500 focus:ring-red-500';
  return isTelefonoValid.value ? 'border-green-300 focus:border-green-500 focus:ring-green-500' : 'border-red-300 focus:border-red-500 focus:ring-red-500';
});

// Validation computed properties
const isRfcValid = computed(() => {
  if (!form.rfc || !form.tipo_persona) return false;

  const rfcRegexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/;
  const rfcRegexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;

  if (form.tipo_persona === 'fisica') {
    return form.rfc.length === 13 && rfcRegexFisica.test(form.rfc);
  } else if (form.tipo_persona === 'moral') {
    return form.rfc.length === 12 && rfcRegexMoral.test(form.rfc);
  }

  return false;
});

const isTelefonoValid = computed(() => {
  if (!form.telefono) return false;
  const telefonoRegex = /^\d{10}$/;
  return telefonoRegex.test(form.telefono);
});

const isFormValid = computed(() => {
  const requiredFields = [
    'nombre_razon_social', 'tipo_persona', 'rfc', 'regimen_fiscal',
    'uso_cfdi', 'email', 'telefono', 'calle', 'numero_exterior',
    'colonia', 'codigo_postal'
  ];

  const hasRequiredFields = requiredFields.every(field => form[field] && form[field].toString().trim());
  const hasNoErrors = Object.keys(form.errors).length === 0;
  const hasValidations = isRfcValid.value && isValidEmail.value && isTelefonoValid.value;

  return hasRequiredFields && hasNoErrors && hasValidations;
});

// Helper functions
const getFieldLabel = (field) => {
  return fieldLabels[field] || field;
};

const convertirAMayusculas = (campo) => {
  if (form[campo]) {
    form[campo] = form[campo].toString().toUpperCase().trim();
  }
};

const showToast = () => {
  showSuccessToast.value = true;
  setTimeout(() => {
    showSuccessToast.value = false;
  }, 4000);
};

// Event handlers
const handleTipoPersonaChange = () => {
  form.clearErrors('tipo_persona');
  form.rfc = ''; // Clear RFC when person type changes
  form.clearErrors('rfc');
};

const handleRfcInput = (event) => {
  // Convert to uppercase and validate
  form.rfc = event.target.value.toUpperCase();
  validateRFC();
};

const handleTelefonoInput = (event) => {
  // Only allow numeric input
  const value = event.target.value.replace(/\D/g, '');
  form.telefono = value;
  validateTelefono();
};

const validateEmail = () => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  isValidEmail.value = emailRegex.test(form.email);

  if (form.email && !isValidEmail.value) {
    form.setError('email', 'Por favor ingrese un email válido');
  } else {
    form.clearErrors('email');
  }
};

const validateRFC = () => {
  if (!form.tipo_persona) {
    form.setError('rfc', 'Primero seleccione el tipo de persona');
    return;
  }

  const rfcRegexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/;
  const rfcRegexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;

  if (form.tipo_persona === 'fisica') {
    if (form.rfc.length !== 13 || !rfcRegexFisica.test(form.rfc)) {
      form.setError('rfc', 'El RFC debe tener 13 caracteres y ser válido para una persona física');
      return;
    }
  } else if (form.tipo_persona === 'moral') {
    if (form.rfc.length !== 12 || !rfcRegexMoral.test(form.rfc)) {
      form.setError('rfc', 'El RFC debe tener 12 caracteres y ser válido para una persona moral');
      return;
    }
  }

  form.clearErrors('rfc');
};

const validateTelefono = () => {
  const telefonoRegex = /^\d{10}$/;
  if (form.telefono && !telefonoRegex.test(form.telefono)) {
    form.setError('telefono', 'El teléfono debe tener exactamente 10 dígitos');
  } else {
    form.clearErrors('telefono');
  }
};

const validateCodigoPostal = async (event) => {
  // Only allow numeric input and limit to 5 digits
  const value = event.target.value.replace(/\D/g, '').slice(0, 5);
  form.codigo_postal = value;

  // Autocompletar cuando tenga 5 dígitos
  if (value.length === 5) {
    try {
      const response = await fetch(`/api/cp/${value}`);
      if (response.ok) {
        const data = await response.json();
        form.estado = data.estado;
        form.municipio = data.municipio;
        form.pais = data.pais;

        // Si solo hay una colonia, la seleccionamos automáticamente
        if (data.colonias && data.colonias.length === 1) {
          form.colonia = data.colonias[0];
        }
      }
    } catch (error) {
      console.warn('Error al consultar código postal:', error);
      // No mostramos error al usuario, solo continuamos
    }
  }
};

const resetForm = () => {
  // Reset form to original values
  Object.keys(originalFormData).forEach(key => {
    form[key] = originalFormData[key];
  });

  // Clear all errors
  form.clearErrors();

  // Reset validation states
  isValidEmail.value = false;
};

const submit = () => {
  // Final validation before submit
  validateRFC();
  validateTelefono();
  validateEmail();

  if (!isFormValid.value) {
    return;
  }

  form.put(route('proveedores.update', props.proveedor.id), {
    preserveScroll: true,
    onSuccess: () => {
      showToast();
    },
    onError: (errors) => {
      console.error('Errores al actualizar:', errors);

      // Handle specific errors
      Object.keys(errors).forEach(field => {
        form.setError(field, errors[field]);
      });

      // Scroll to first error
      const firstErrorField = Object.keys(errors)[0];
      const element = document.getElementById(firstErrorField);
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.focus();
      }
    },
    onFinish: () => {
      // Additional cleanup if needed
    }
  });
};

// Watchers for real-time validation
watch(() => form.email, () => {
  if (form.email) {
    validateEmail();
  } else {
    isValidEmail.value = false;
    form.clearErrors('email');
  }
});

watch(() => form.rfc, () => {
  if (form.rfc && form.tipo_persona) {
    validateRFC();
  }
});

watch(() => form.telefono, () => {
  if (form.telefono) {
    validateTelefono();
  }
});

// Initialize email validation state
if (form.email) {
  validateEmail();
}
</script>

<style>
/* Custom styles for better visual feedback */
.form-field-error {
  border-color: #fca5a5; /* border-red-300 */
  transition: border-color 150ms cubic-bezier(0.4, 0, 0.2, 1),
              box-shadow 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.form-field-error:focus {
  border-color: #ef4444; /* border-red-500 */
  box-shadow: 0 0 0 1px #ef4444;
}

.form-field-success {
  border-color: #86efac; /* border-green-300 */
  transition: border-color 150ms cubic-bezier(0.4, 0, 0.2, 1),
              box-shadow 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.form-field-success:focus {
  border-color: #22c55e; /* border-green-500 */
  box-shadow: 0 0 0 1px #22c55e;
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Smooth transitions for all elements */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Focus styles */
input:focus, select:focus, textarea:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Disabled state */
input:disabled, select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>

