<template>
  <div class="w-full p-6">
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Crear Proveedor</h1>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded-full" :class="formValid ? 'bg-green-500' : 'bg-red-500'"></div>
          <span class="text-sm text-gray-600 dark:text-gray-300">{{ formValid ? 'Formulario válido' : 'Revisar campos' }}</span>
        </div>
      </div>

      <!-- Alert de errores -->
      <div v-if="Object.keys(form.errors).length" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center mb-2">
          <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <h3 class="text-red-800 font-medium">Por favor corrige los siguientes errores:</h3>
        </div>
        <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
          <li v-for="(error, field) in form.errors" :key="field">
            <strong>{{ getFieldLabel(field) }}:</strong> {{ error }}
          </li>
        </ul>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Información General -->
        <div class="border-b border-gray-200 dark:border-slate-800 pb-8">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Información General</h2>
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
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.nombre_razon_social }"
                @blur="convertirAMayusculas('nombre_razon_social')"
                placeholder="Ingresa el nombre o razón social"
                required
              />
              <p v-if="form.errors.nombre_razon_social" class="mt-1 text-red-500 text-sm">
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
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.tipo_persona }"
                @change="onTipoPersonaChange"
                required
              >
                <option value="" disabled>Selecciona el tipo de persona</option>
                <option value="fisica">Persona Física</option>
                <option value="moral">Persona Moral</option>
              </select>
              <p v-if="form.errors.tipo_persona" class="mt-1 text-red-500 text-sm">
                {{ form.errors.tipo_persona }}
              </p>
            </div>

            <!-- RFC -->
            <div>
              <label for="rfc" class="block text-sm font-medium text-gray-700 mb-2">
                RFC *
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  ({{ form.tipo_persona === 'fisica' ? '13 caracteres' : form.tipo_persona === 'moral' ? '12 caracteres' : 'Selecciona tipo de persona' }})
                </span>
              </label>
              <div class="relative">
                <input
                  v-model="form.rfc"
                  type="text"
                  id="rfc"
                  :maxlength="form.tipo_persona === 'fisica' ? 13 : 12"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10"
                  :class="{
                    'border-red-500': form.errors.rfc,
                    'border-green-500': rfcValid && form.rfc
                  }"
                  @input="onRfcInput"
                  :placeholder="form.tipo_persona === 'fisica' ? 'ABCD123456EFG' : 'ABC123456EFG'"
                  :disabled="!form.tipo_persona"
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <svg v-if="rfcValid && form.rfc" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <p v-if="form.errors.rfc" class="mt-1 text-red-500 text-sm">{{ form.errors.rfc }}</p>
            </div>
          </div>
        </div>

        <!-- Información Fiscal -->
        <div class="border-b border-gray-200 dark:border-slate-800 pb-8">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Información Fiscal</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Régimen Fiscal -->
            <div>
              <label for="regimen_fiscal" class="block text-sm font-medium text-gray-700 mb-2">
                Régimen Fiscal *
              </label>
              <select
                v-model="form.regimen_fiscal"
                id="regimen_fiscal"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.regimen_fiscal }"
                required
              >
                <option value="" disabled>Selecciona un régimen fiscal</option>
                <option v-for="regimen in regimenesFiscalesFiltrados" :key="regimen.codigo" :value="regimen.codigo">
                  {{ regimen.codigo }} - {{ regimen.descripcion }}
                </option>
              </select>
              <p v-if="form.errors.regimen_fiscal" class="mt-1 text-red-500 text-sm">
                {{ form.errors.regimen_fiscal }}
              </p>
            </div>


          </div>
        </div>

        <!-- Información de Contacto -->
        <div class="border-b border-gray-200 dark:border-slate-800 pb-8">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Información de Contacto</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Correo Electrónico
              </label>
              <div class="relative">
                <input
                  v-model="form.email"
                  type="email"
                  id="email"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10"
                  :class="{
                    'border-red-500': form.errors.email,
                    'border-green-500': emailValid && form.email
                  }"
                  @input="validateEmail"
                  placeholder="ejemplo@correo.com"
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <svg v-if="emailValid && form.email" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <p v-if="form.errors.email" class="mt-1 text-red-500 text-sm">{{ form.errors.email }}</p>
            </div>

            <!-- Teléfono -->
            <div>
              <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                Teléfono
                <span class="text-xs text-gray-500 dark:text-gray-400">(10 dígitos)</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.telefono"
                  type="tel"
                  id="telefono"
                  maxlength="10"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10"
                  :class="{
                    'border-red-500': form.errors.telefono,
                    'border-green-500': telefonoValid && form.telefono
                  }"
                  @input="validarTelefono"
                  placeholder="6621234567"
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <svg v-if="telefonoValid && form.telefono" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <p v-if="form.errors.telefono" class="mt-1 text-red-500 text-sm">{{ form.errors.telefono }}</p>
            </div>
          </div>
        </div>

        <!-- Dirección -->
        <div class="pb-8">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Dirección</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Calle -->
            <div class="lg:col-span-2">
              <label for="calle" class="block text-sm font-medium text-gray-700 mb-2">
                Calle
              </label>
              <input
                v-model="form.calle"
                type="text"
                id="calle"
                maxlength="100"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.calle }"
                @blur="convertirAMayusculas('calle')"
                placeholder="Nombre de la calle"
              />
              <p v-if="form.errors.calle" class="mt-1 text-red-500 text-sm">{{ form.errors.calle }}</p>
            </div>

            <!-- Número Exterior -->
            <div>
              <label for="numero_exterior" class="block text-sm font-medium text-gray-700 mb-2">
                Número Exterior
              </label>
              <input
                v-model="form.numero_exterior"
                type="text"
                id="numero_exterior"
                maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.numero_exterior }"
                placeholder="123"
              />
              <p v-if="form.errors.numero_exterior" class="mt-1 text-red-500 text-sm">{{ form.errors.numero_exterior }}</p>
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
                maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                placeholder="A, 1, Depto 2, etc."
              />
            </div>

            <!-- Colonia -->
            <div>
              <label for="colonia" class="block text-sm font-medium text-gray-700 mb-2">
                Colonia
              </label>
              <input
                v-model="form.colonia"
                type="text"
                id="colonia"
                maxlength="50"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.colonia }"
                @blur="convertirAMayusculas('colonia')"
                placeholder="Nombre de la colonia"
              />
              <p v-if="form.errors.colonia" class="mt-1 text-red-500 text-sm">{{ form.errors.colonia }}</p>
            </div>

            <!-- Código Postal -->
            <div>
              <label for="codigo_postal" class="block text-sm font-medium text-gray-700 mb-2">
                Código Postal
              </label>
              <input
                v-model="form.codigo_postal"
                type="text"
                id="codigo_postal"
                maxlength="5"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-500': form.errors.codigo_postal }"
                @input="validarCodigoPostal"
                placeholder="83000"
              />
              <p v-if="form.errors.codigo_postal" class="mt-1 text-red-500 text-sm">{{ form.errors.codigo_postal }}</p>
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
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white dark:bg-slate-900 text-gray-600 dark:text-gray-300"
                readonly
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
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white dark:bg-slate-900 text-gray-600 dark:text-gray-300"
                readonly
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
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white dark:bg-slate-900 text-gray-600 dark:text-gray-300"
                readonly
              />
            </div>
          </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-slate-800">
          <button
            type="button"
            @click="resetForm"
            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
          >
            Limpiar Formulario
          </button>

          <div class="flex items-center space-x-4">
            <button
              type="button"
              @click="previewData"
              class="px-6 py-3 border border-blue-300 rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >
              Vista Previa
            </button>

            <button
              type="submit"
              class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
              :disabled="form.processing || !formValid"
            >
              <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ form.processing ? 'Guardando...' : 'Guardar Proveedor' }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Modal de Vista Previa -->
    <div v-if="showPreview" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-white dark:bg-slate-9000 bg-opacity-75" @click="showPreview = false"></div>

        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div class="px-6 py-4 bg-white dark:bg-slate-900">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Vista Previa del Proveedor</h3>
              <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="mt-4 space-y-4">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Nombre/Razón Social:</strong> {{ form.nombre_razon_social || 'No especificado' }}</div>
                <div><strong>Tipo:</strong> {{ form.tipo_persona === 'fisica' ? 'Persona Física' : form.tipo_persona === 'moral' ? 'Persona Moral' : 'No especificado' }}</div>
                <div><strong>RFC:</strong> {{ form.rfc || 'No especificado' }}</div>
                <div><strong>Email:</strong> {{ form.email || 'No especificado' }}</div>
                <div><strong>Teléfono:</strong> {{ form.telefono || 'No especificado' }}</div>
                <div><strong>Régimen Fiscal:</strong> {{ form.regimen_fiscal || 'No especificado' }}</div>
              </div>

              <div class="pt-4 border-t">
                <strong>Dirección:</strong>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                  {{ [form.calle, form.numero_exterior, form.numero_interior].filter(Boolean).join(' ') || 'No especificada' }}<br>
                  {{ form.colonia }}, {{ form.municipio }}<br>
                  {{ form.estado }}, {{ form.pais }} {{ form.codigo_postal }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

defineOptions({ layout: AppLayout });

// Configuración de Notyf
const notyf = new Notyf({
  duration: 4000,
  position: {
    x: 'right',
    y: 'top',
  },
  types: [
    {
      type: 'success',
      background: '#10b981',
      icon: false
    },
    {
      type: 'error',
      background: '#ef4444',
      icon: false
    }
  ]
});

// Estados reactivos
const showPreview = ref(false);
const rfcValid = ref(false);
const emailValid = ref(false);
const telefonoValid = ref(false);

// Manejo de mensajes flash del servidor
const page = usePage();
const flash = computed(() => page.props.flash || {});

// Mostrar mensajes flash cuando cambien
watch(flash, (newFlash) => {
  if (newFlash.success) {
    notyf.success(newFlash.success);
  }
  if (newFlash.error) {
    notyf.error(newFlash.error);
  }
}, { deep: true });

// Listas predefinidas mejoradas
const regimenesFiscales = {
  fisica: [
    { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales' },
    { codigo: '614', descripcion: 'Personas Físicas con Actividades Empresariales' },
    { codigo: '616', descripcion: 'Personas Físicas con Actividades Profesionales' },
    { codigo: '621', descripcion: 'Incorporación Fiscal' },
    { codigo: '626', descripcion: 'Régimen Simplificado de Confianza' },
    { codigo: '629', descripcion: 'De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales' },
    { codigo: '630', descripcion: 'Enajenación de acciones en bolsa de valores' }
  ],
  moral: [
    { codigo: '601', descripcion: 'General de Ley Personas Morales' },
    { codigo: '603', descripcion: 'Personas Morales con Fines no Lucrativos' },
    { codigo: '609', descripcion: 'Consolidación' },
    { codigo: '620', descripcion: 'Sociedades Cooperativas de Producción' },
    { codigo: '622', descripcion: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
    { codigo: '623', descripcion: 'Opcional para Grupos de Sociedades' },
    { codigo: '624', descripcion: 'Coordinados' }
  ]
};

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
  { codigo: 'D05', descripcion: 'Intereses reales efectivamente pagados por créditos hipotecarios' },
  { codigo: 'D06', descripcion: 'Aportaciones voluntarias al SAR' },
  { codigo: 'D07', descripcion: 'Primas por seguros de gastos médicos' },
  { codigo: 'D08', descripcion: 'Gastos de transportación escolar obligatoria' },
  { codigo: 'D09', descripcion: 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones' },
  { codigo: 'D10', descripcion: 'Pagos por servicios educativos (colegiaturas)' }
];

// Computed para regímenes filtrados
const regimenesFiscalesFiltrados = computed(() => {
  if (!form.tipo_persona) return [];
  return regimenesFiscales[form.tipo_persona] || [];
});

// Formulario mejorado
const form = useForm({
  nombre_razon_social: '',
  tipo_persona: '',
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
  municipio: 'HERMOSILLO',
  estado: 'SONORA',
  pais: 'MEXICO'
});

// Mapeo de nombres de campos para errores
const fieldLabels = {
  nombre_razon_social: 'Nombre/Razón Social',
  tipo_persona: 'Tipo de Persona',
  rfc: 'RFC',
  regimen_fiscal: 'Régimen Fiscal',
  uso_cfdi: 'Uso CFDI',
  email: 'Correo Electrónico',
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

// Computed para validar el formulario (MODIFICADO: validación relajada para permitir creación rápida)
const formValid = computed(() => {
  return form.nombre_razon_social &&
         form.tipo_persona &&
         rfcValid.value &&
         form.regimen_fiscal;
         // Campos de dirección y contacto ahora opcionales para facilitar importación desde XML
});

// Inicializar con parámetros de URL si existen
onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  
  if (params.has('nombre_razon_social')) {
    form.nombre_razon_social = params.get('nombre_razon_social');
  }
  
  if (params.has('rfc')) {
    form.rfc = params.get('rfc');
    // Validar el RFC cargado
    if (params.has('tipo_persona')) {
      form.tipo_persona = params.get('tipo_persona');
      // Esperar un tick para que se actualice el v-model y validar
      setTimeout(() => validarRFC(), 100);
    }
  }
  
  if (params.has('regimen_fiscal')) {
    form.regimen_fiscal = params.get('regimen_fiscal');
  }
  
  // Uso CFDI eliminado por solicitud
  // if (params.has('uso_cfdi')) {
  //   form.uso_cfdi = params.get('uso_cfdi');
  // }

  // Notificar si se cargaron datos
  if (params.has('rfc')) {
    notyf.success('Datos precargados desde el XML. Complete la información faltante si es necesario.');
  }
});

// Función para obtener el label del campo
const getFieldLabel = (field) => {
  return fieldLabels[field] || field;
};

// Función para enviar el formulario
const submit = () => {
  // Validaciones finales antes de enviar
  if (!formValid.value) {
    if (!form.nombre_razon_social) form.setError('nombre_razon_social', 'Este campo es obligatorio');
    if (!form.tipo_persona) form.setError('tipo_persona', 'Debe seleccionar un tipo de persona');
    if (!rfcValid.value) form.setError('rfc', 'El RFC no es válido');
    if (!form.regimen_fiscal) form.setError('regimen_fiscal', 'Debe seleccionar un régimen fiscal');
    return;
  }

  // Validaciones adicionales solo si hay datos
  if (form.email && !emailValid.value) {
    form.setError('email', 'El correo electrónico no es válido');
    return;
  }
  if (form.telefono && !telefonoValid.value) {
    form.setError('telefono', 'El teléfono no es válido');
    return;
  }

  form.post(route('proveedores.store'), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      form.reset();
      resetValidationStates();
      // Mostrar mensaje de éxito usando Notyf
      notyf.success('Proveedor creado exitosamente');
    },
    onError: (errors) => {
      console.error('Error al crear proveedor:', errors);
      // Mostrar errores usando Notyf si los hay
      if (errors && Object.keys(errors).length > 0) {
        Object.values(errors).forEach(error => {
          notyf.error(error);
        });
      }
    },
  });
};

// Función para limpiar el formulario
const resetForm = () => {
  form.reset();
  resetValidationStates();
};

// Función para resetear estados de validación
const resetValidationStates = () => {
  rfcValid.value = false;
  emailValid.value = false;
  telefonoValid.value = false;
};

// Función para mostrar vista previa
const previewData = () => {
  showPreview.value = true;
};

// Convertir a mayúsculas
const convertirAMayusculas = (campo) => {
  if (form[campo]) {
    form[campo] = form[campo].toUpperCase().trim();
  }
};

// Manejar cambio de tipo de persona
const onTipoPersonaChange = () => {
  // Limpiar RFC cuando cambie el tipo de persona
  form.rfc = '';
  rfcValid.value = false;
  form.clearErrors('rfc');

  // Limpiar régimen fiscal cuando cambie el tipo
  form.regimen_fiscal = '';
  form.clearErrors('regimen_fiscal');

  // Validar RFC si ya hay valor
  if (form.rfc) {
    validarRFC();
  }
};

// Manejar input del RFC
const onRfcInput = (event) => {
  // Convertir a mayúsculas automáticamente
  form.rfc = event.target.value.toUpperCase();
  validarRFC();
};

// Validación mejorada del RFC
const validarRFC = () => {
  if (!form.rfc || !form.tipo_persona) {
    rfcValid.value = false;
    return;
  }

  const rfcRegexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/;
  const rfcRegexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;

  // Verificar palabras prohibidas en RFC
  const palabrasProhibidas = [
    'BUEI', 'BUEY', 'CACA', 'CACO', 'CAGA', 'CAGO', 'CAKA', 'CAKO',
    'COGE', 'COGI', 'COJA', 'COJE', 'COJI', 'COJO', 'COLA', 'CULO',
    'FALO', 'FETO', 'GETA', 'GUEY', 'JOTO', 'KACA', 'KACO', 'KAGA',
    'KAGO', 'KAKA', 'KAKO', 'KOGE', 'KOGI', 'KOJA', 'KOJE', 'KOJI',
    'KOJO', 'KOLA', 'KULO', 'LILO', 'LOCA', 'LOCO', 'LOKA', 'LOKO',
    'MAME', 'MAMO', 'MEAR', 'MEAS', 'MEON', 'MIAR', 'MION', 'MOCO',
    'MOKO', 'MULA', 'MULO', 'NACA', 'NACO', 'PEDA', 'PEDO', 'PENE',
    'PIPI', 'PITO', 'POPO', 'PUTA', 'PUTO', 'QULO', 'RATA', 'ROBA',
    'ROBE', 'ROBO', 'RUIN', 'SENO', 'TETA', 'VACA', 'VAGA', 'VAGO',
    'VAKA', 'VUEY', 'WUEY', 'ZORRA'
  ];

  if (form.tipo_persona === 'fisica') {
    if (form.rfc.length !== 13) {
      form.setError('rfc', 'El RFC de persona física debe tener exactamente 13 caracteres.');
      rfcValid.value = false;
      return;
    }

    if (!rfcRegexFisica.test(form.rfc)) {
      form.setError('rfc', 'Formato de RFC inválido para persona física (AAAA######AAA).');
      rfcValid.value = false;
      return;
    }

    // Verificar palabras prohibidas
    const primerosCuatro = form.rfc.substring(0, 4);
    if (palabrasProhibidas.includes(primerosCuatro)) {
      form.setError('rfc', 'El RFC contiene una combinación no permitida.');
      rfcValid.value = false;
      return;
    }

  } else if (form.tipo_persona === 'moral') {
    if (form.rfc.length !== 12) {
      form.setError('rfc', 'El RFC de persona moral debe tener exactamente 12 caracteres.');
      rfcValid.value = false;
      return;
    }

    if (!rfcRegexMoral.test(form.rfc)) {
      form.setError('rfc', 'Formato de RFC inválido para persona moral (AAA######AAA).');
      rfcValid.value = false;
      return;
    }

    // Verificar palabras prohibidas
    const primerosTres = form.rfc.substring(0, 3);
    if (palabrasProhibidas.some(palabra => palabra.startsWith(primerosTres))) {
      form.setError('rfc', 'El RFC contiene una combinación no permitida.');
      rfcValid.value = false;
      return;
    }
  }

  // Si llegamos aquí, el RFC es válido
  form.clearErrors('rfc');
  rfcValid.value = true;
};

// Validación del email
const validateEmail = () => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!form.email) {
    emailValid.value = true;
    form.clearErrors('email');
    return;
  }

  if (!emailRegex.test(form.email)) {
    form.setError('email', 'El formato del correo electrónico no es válido.');
    emailValid.value = false;
    return;
  }

  // Validaciones adicionales
  if (form.email.length > 100) {
    form.setError('email', 'El correo electrónico es demasiado largo (máximo 100 caracteres).');
    emailValid.value = false;
    return;
  }

  form.clearErrors('email');
  emailValid.value = true;
};

// Validación mejorada del teléfono
const validarTelefono = () => {
  // Solo permitir números
  form.telefono = form.telefono.replace(/\D/g, '');

  if (!form.telefono) {
    telefonoValid.value = true;
    form.clearErrors('telefono');
    return;
  }

  if (form.telefono.length !== 10) {
    form.setError('telefono', 'El teléfono debe tener exactamente 10 dígitos.');
    telefonoValid.value = false;
    return;
  }

  // Validar que no sean todos números iguales
  if (/^(\d)\1{9}$/.test(form.telefono)) {
    form.setError('telefono', 'El teléfono no puede tener todos los dígitos iguales.');
    telefonoValid.value = false;
    return;
  }

  // Validar códigos de área válidos para México
  const codigosArea = ['33', '55', '81', '662', '664', '668', '669', '686', '687'];
  const codigoArea = form.telefono.startsWith('33') || form.telefono.startsWith('55') || form.telefono.startsWith('81')
    ? form.telefono.substring(0, 2)
    : form.telefono.substring(0, 3);

  form.clearErrors('telefono');
  telefonoValid.value = true;
};

// Validación del código postal
const validarCodigoPostal = async () => {
  // Solo permitir números
  form.codigo_postal = form.codigo_postal.replace(/\D/g, '');

  if (form.codigo_postal.length === 5) {
    form.clearErrors('codigo_postal');

    // Autocompletar cuando tenga 5 dígitos
    try {
      const response = await fetch(`/api/cp/${form.codigo_postal}`);
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
  } else if (form.codigo_postal.length > 0) {
    form.setError('codigo_postal', 'El código postal debe tener exactamente 5 dígitos.');
  }
};
</script>

