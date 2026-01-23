<template>
  <Teleport to="#app">
    <Transition
      enter-active-class="transition-opacity duration-300"
      leave-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <!-- Overlay -->
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="close"
          ></div>

          <!-- Modal panel -->
          <Transition
            enter-active-class="transition-all duration-300"
            leave-active-class="transition-all duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="show"
              class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
            >
              <div class="bg-white dark:bg-slate-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                      <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                        Crear Nuevo Proveedor
                      </h3>
                      <button
                        @click="close"
                        class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
                      >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>

                    <!-- Formulario -->
                    <form @submit.prevent="submit" class="space-y-6">
                      <!-- Información General -->
                      <div class="border-b border-gray-200 dark:border-slate-800 pb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información General</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div class="md:col-span-2">
                            <label for="modal-nombre_razon_social" class="block text-sm font-medium text-gray-700">
                              Nombre/Razón Social <span class="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              id="modal-nombre_razon_social"
                              v-model="form.nombre_razon_social"
                              @blur="toUpper('nombre_razon_social')"
                              autocomplete="off"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                errores.nombre_razon_social ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="errores.nombre_razon_social" class="mt-1 text-sm text-red-600">
                              {{ errores.nombre_razon_social }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-tipo_persona" class="block text-sm font-medium text-gray-700">
                              Tipo de Persona <span class="text-red-500">*</span>
                            </label>
                            <select
                              id="modal-tipo_persona"
                              v-model="form.tipo_persona"
                              @change="onTipoPersonaChange"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.tipo_persona ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            >
                              <option value="">Selecciona una opción</option>
                              <option value="fisica">Persona Física</option>
                              <option value="moral">Persona Moral</option>
                            </select>
                            <div v-if="form.errors.tipo_persona" class="mt-1 text-sm text-red-600">
                              {{ form.errors.tipo_persona }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-rfc" class="block text-sm font-medium text-gray-700">
                              RFC <span class="text-red-500">*</span>
                              <span class="text-xs text-gray-500 dark:text-gray-400">
                                ({{ form.tipo_persona === 'fisica' ? '13 caracteres' : form.tipo_persona === 'moral' ? '12 caracteres' : 'Selecciona tipo de persona' }})
                              </span>
                            </label>
                            <input
                              type="text"
                              id="modal-rfc"
                              :maxlength="form.tipo_persona === 'fisica' ? 13 : 12"
                              :placeholder="form.tipo_persona === 'fisica' ? 'ABCD123456EFG' : 'ABC123456EFG'"
                              :value="form.rfc"
                              @input="onRfcInput"
                              :disabled="!form.tipo_persona"
                              autocomplete="off"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.rfc ? 'border-red-300 bg-red-50' : 'border-gray-300',
                                !form.tipo_persona ? 'bg-gray-100 text-gray-400' : ''
                              ]"
                              required
                            />
                            <div v-if="form.errors.rfc" class="mt-1 text-sm text-red-600">
                              {{ form.errors.rfc }}
                            </div>
                            <div v-if="!form.tipo_persona" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                              Primero selecciona el tipo de persona
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Información Fiscal -->
                      <div class="border-b border-gray-200 dark:border-slate-800 pb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información Fiscal</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div>
                            <label for="modal-regimen_fiscal" class="block text-sm font-medium text-gray-700">
                              Régimen Fiscal <span class="text-red-500">*</span>
                            </label>
                            <select
                              id="modal-regimen_fiscal"
                              v-model="form.regimen_fiscal"
                              :disabled="!form.tipo_persona"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.regimen_fiscal ? 'border-red-300 bg-red-50' : 'border-gray-300',
                                !form.tipo_persona ? 'bg-gray-100 text-gray-400' : ''
                              ]"
                              required
                            >
                              <option value="">Selecciona una opción</option>
                              <option
                                v-for="regimen in regimenesFiltrados"
                                :key="regimen.codigo"
                                :value="regimen.codigo"
                              >
                                {{ regimen.codigo }} - {{ regimen.descripcion }}
                              </option>
                            </select>
                            <div v-if="form.errors.regimen_fiscal" class="mt-1 text-sm text-red-600">
                              {{ form.errors.regimen_fiscal }}
                            </div>
                            <div v-if="!form.tipo_persona" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                              Primero selecciona el tipo de persona
                            </div>
                          </div>

                          <div>
                            <label for="modal-uso_cfdi" class="block text-sm font-medium text-gray-700">
                              Uso CFDI <span class="text-red-500">*</span>
                            </label>
                            <select
                              id="modal-uso_cfdi"
                              v-model="form.uso_cfdi"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.uso_cfdi ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            >
                              <option value="">Selecciona una opción</option>
                              <option
                                v-for="uso in usosCFDI"
                                :key="uso.codigo"
                                :value="uso.codigo"
                              >
                                {{ uso.codigo }} - {{ uso.descripcion }}
                              </option>
                            </select>
                            <div v-if="form.errors.uso_cfdi" class="mt-1 text-sm text-red-600">
                              {{ form.errors.uso_cfdi }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Información de Contacto -->
                      <div class="border-b border-gray-200 dark:border-slate-800 pb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información de Contacto</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div>
                            <label for="modal-email" class="block text-sm font-medium text-gray-700">
                              Correo Electrónico <span class="text-red-500">*</span>
                            </label>
                            <input
                              type="email"
                              id="modal-email"
                              v-model="form.email"
                              autocomplete="new-password"
                              placeholder="correo@ejemplo.com"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.email ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                              {{ form.errors.email }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-telefono" class="block text-sm font-medium text-gray-700">
                              Teléfono <span class="text-red-500">*</span>
                              <span class="text-xs text-gray-500 dark:text-gray-400">(10 dígitos)</span>
                            </label>
                            <input
                              type="tel"
                              id="modal-telefono"
                              autocomplete="new-password"
                              v-model="form.telefono"
                              @input="validarTelefono"
                              placeholder="6621234567"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.telefono ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="form.errors.telefono" class="mt-1 text-sm text-red-600">
                              {{ form.errors.telefono }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Dirección -->
                      <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Dirección</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                          <div class="lg:col-span-2">
                            <label for="modal-calle" class="block text-sm font-medium text-gray-700">
                              Calle <span class="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              id="modal-calle"
                              v-model="form.calle"
                              @blur="toUpper('calle')"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.calle ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="form.errors.calle" class="mt-1 text-sm text-red-600">
                              {{ form.errors.calle }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-numero_exterior" class="block text-sm font-medium text-gray-700">
                              Número Exterior <span class="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              id="modal-numero_exterior"
                              v-model="form.numero_exterior"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.numero_exterior ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="form.errors.numero_exterior" class="mt-1 text-sm text-red-600">
                              {{ form.errors.numero_exterior }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-numero_interior" class="block text-sm font-medium text-gray-700">
                              Número Interior
                            </label>
                            <input
                              type="text"
                              id="modal-numero_interior"
                              v-model="form.numero_interior"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.numero_interior ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                            />
                            <div v-if="form.errors.numero_interior" class="mt-1 text-sm text-red-600">
                              {{ form.errors.numero_interior }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-colonia" class="block text-sm font-medium text-gray-700">
                              Colonia <span class="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              id="modal-colonia"
                              v-model="form.colonia"
                              @blur="toUpper('colonia')"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.colonia ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="form.errors.colonia" class="mt-1 text-sm text-red-600">
                              {{ form.errors.colonia }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-codigo_postal" class="block text-sm font-medium text-gray-700">
                              Código Postal <span class="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              id="modal-codigo_postal"
                              maxlength="5"
                              pattern="[0-9]{5}"
                              :value="form.codigo_postal"
                              @input="onCpInput"
                              placeholder="83000"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.codigo_postal ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                              required
                            />
                            <div v-if="form.errors.codigo_postal" class="mt-1 text-sm text-red-600">
                              {{ form.errors.codigo_postal }}
                            </div>
                          </div>

                          <div>
                            <label for="modal-municipio" class="block text-sm font-medium text-gray-700">
                              Municipio
                            </label>
                            <input
                              type="text"
                              id="modal-municipio"
                              v-model="form.municipio"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                'bg-gray-50 text-gray-600'
                              ]"
                              readonly
                            />
                          </div>

                          <div>
                            <label for="modal-estado" class="block text-sm font-medium text-gray-700">
                              Estado
                            </label>
                            <input
                              type="text"
                              id="modal-estado"
                              v-model="form.estado"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                'bg-gray-50 text-gray-600'
                              ]"
                              readonly
                            />
                          </div>

                          <div>
                            <label for="modal-pais" class="block text-sm font-medium text-gray-700">
                              País
                            </label>
                            <input
                              type="text"
                              id="modal-pais"
                              v-model="form.pais"
                              @blur="toUpper('pais')"
                              placeholder="MX (México por defecto)"
                              autocomplete="new-password"
                              :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                                form.errors.pais ? 'border-red-300 bg-red-50' : 'border-gray-300'
                              ]"
                            />
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  @click="submit"
                  :disabled="form.processing || !isFormValid"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="form.processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creando...
                  </span>
                  <span v-else>Crear Proveedor</span>
                </button>
                <button
                  type="button"
                  @click="close"
                  :disabled="form.processing"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-slate-900 text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Cancelar
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  catalogs: {
    type: Object,
    default: () => ({})
  },
  nombreInicial: {
    type: String,
    default: ''
  }
})

// Emits
const emit = defineEmits(['close', 'proveedor-creado'])

// Estado
const availableColonias = ref([])
const errores = ref({})

// Listas predefinidas
const regimenesFiscales = {
  fisica: [
    { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales' },
    { codigo: '614', descripcion: 'Personas Físicas con Actividades Empresariales' },
    { codigo: '616', descripcion: 'Personas Físicas con Actividades Profesionales' },
    { codigo: '621', descripcion: 'Incorporación Fiscal' },
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
}

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
]

// Inicializa form con valores por defecto seguros
const initFormData = () => ({
  nombre_razon_social: props.nombreInicial || '',
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
})

const form = ref(initFormData())

// Computed
const hasGlobalErrors = computed(() => Object.keys(form.errors).length > 0)

const requiredFields = [
  'nombre_razon_social', 'tipo_persona', 'rfc',
  'regimen_fiscal', 'uso_cfdi', 'email', 'telefono',
  'calle', 'numero_exterior', 'colonia', 'codigo_postal'
]

const requiredFieldsFilled = computed(() => {
  return requiredFields.every(field => {
    const value = form[field]
    return value !== null &&
           value !== undefined &&
           String(value).trim() !== '' &&
           String(value).trim() !== 'null' &&
           String(value).trim() !== 'undefined'
  })
})

const isFormValid = computed(() => {
  return requiredFieldsFilled.value && !hasGlobalErrors.value && !form.processing
})

const regimenesFiltrados = computed(() => {
  if (!form.tipo_persona) return []
  return regimenesFiscales[form.tipo_persona] || []
})

// Handlers
const onTipoPersonaChange = () => {
  form.rfc = ''
  form.regimen_fiscal = ''
  form.clearErrors(['rfc', 'regimen_fiscal'])
}

const onRfcInput = (event) => {
  const value = event.target ? event.target.value : event
  const cleaned = String(value).toUpperCase().replace(/[^A-ZÑ&0-9]/g, '')
  const maxLen = form.tipo_persona === 'fisica' ? 13 : 12
  form.rfc = cleaned.slice(0, maxLen)
  if (form.rfc && form.errors.rfc) form.clearErrors('rfc')
}

const validarTelefono = () => {
  if (!form.telefono) return
  form.telefono = form.telefono.replace(/\D/g, '').slice(0, 10)
}

const onCpInput = async (event) => {
  const value = event.target ? event.target.value : event
  const digits = String(value).replace(/\D/g, '')
  form.codigo_postal = digits.slice(0, 5)
  if (form.codigo_postal && form.errors.codigo_postal) form.clearErrors('codigo_postal')

  if (form.codigo_postal.length === 5) {
    try {
      const response = await axios.get(`/api/cp/${form.codigo_postal}`)
      if (response.ok) {
        const data = response.data
        form.estado = data.estado
        form.municipio = data.municipio
        form.pais = data.pais
      }
    } catch (error) {
      console.warn('Error al consultar código postal:', error)
    }
  }
}

const toUpper = (campo) => {
  if (form[campo] && typeof form[campo] === 'string') {
    form[campo] = form[campo].toUpperCase().trim()
    if (form[campo] && form.errors[campo]) form.clearErrors(campo)
  }
}

// Acciones
const close = () => {
  emit('close')
}

const submit = async () => {
  if (!isFormValid.value) {
    return
  }

  const dataToSend = { ...form.value }

  try {
    const response = await axios.post(route('proveedores.store'), dataToSend, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    const nuevoProveedor = response.data
    emit('proveedor-creado', nuevoProveedor)
    close()
  } catch (error) {
    console.error('Error al crear proveedor:', error)
    if (error.response && error.response.data && error.response.data.errors) {
      errores.value = error.response.data.errors
    }
  }
}

// Reset form when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    form.value = initFormData()
    errores.value = {}
    availableColonias.value = []
  }
})
</script>

