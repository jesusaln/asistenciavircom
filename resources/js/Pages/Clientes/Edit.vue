<template>
  <Head title="Editar Cliente" />
  <div class="max-w-4xl mx-auto p-4" :style="cssVars">
    <!-- Card principal con glassmorphism -->
    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl p-8 border border-gray-100">
      <!-- Header moderno -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Editar Cliente</h1>
            <p class="text-sm text-gray-500 mt-0.5">Modifique los datos del cliente seleccionado</p>
          </div>
        </div>
        <div class="text-sm text-gray-500 flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-red-500"></span>
          Campos obligatorios marcados con <span class="text-red-500 ml-1">*</span>
        </div>
      </div>

      <!-- Resumen de errores -->
      <div v-if="hasGlobalErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error en el formulario</h3>
            <div class="mt-2 text-sm text-red-700">
              <ul class="list-disc list-inside space-y-1">
                <li v-for="(error, key) in form.errors" :key="key">
                  {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Mensaje de éxito -->
      <div
        v-if="showSuccessMessage"
        class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md"
        aria-live="polite"
      >
        <p class="text-sm font-medium text-green-800">Cliente actualizado exitosamente</p>
      </div>

      <!-- Mensaje de autocompletado -->
      <div
        v-if="showAutoCompleteMessage"
        class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md"
        aria-live="polite"
      >
        <div class="flex">
           <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-blue-800">Dirección autocompletada</p>
            <p class="text-sm text-blue-700">Los campos de estado y municipio se han completado automáticamente.</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-8" autocomplete="off">
        <ClientForm
          :form="form"
          :catalogs="catalogs"
          :is-edit="true"
          :available-colonias="availableColonias"
          :is-loading-cp="isLoadingCp"
          @factura-change="onFacturaChange"
          @tipo-persona-change="onTipoPersonaChange"
          @cp-input="onCpInput"
        />

        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="resetForm"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Restaurar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5"
              :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
            >
              <span v-if="form.processing" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Guardando...
              </span>
              <span v-else>Guardar Cambios</span>
            </button>
          </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, ref, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ClientForm from './Partials/ClientForm.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

// Colores de empresa
const { cssVars, colors } = useCompanyColors()

const props = defineProps({
  catalogs: { type: Object, required: true },
  cliente: { type: Object, required: true },
  errors: Object
})

const showSuccessMessage = ref(false)
const showAutoCompleteMessage = ref(false)
const availableColonias = ref([])
const isLoadingCp = ref(false)

// Mapeo de estados mexicanos con códigos SAT (Case Insensitive)
const estadoMapping = {
  'AGUASCALIENTES': 'AGU', 'BAJA CALIFORNIA': 'BCN', 'BAJA CALIFORNIA SUR': 'BCS', 'CAMPECHE': 'CAM',
  'CHIAPAS': 'CHP', 'CHIHUAHUA': 'CHH', 'CIUDAD DE MÉXICO': 'DIF', 'COAHUILA': 'COA', 'COLIMA': 'COL',
  'DURANGO': 'DUR', 'GUANAJUATO': 'GUA', 'GUERRERO': 'GRO', 'HIDALGO': 'HID', 'JALISCO': 'JAL',
  'MÉXICO': 'MEX', 'MICHOACÁN': 'MIC', 'MORELOS': 'MOR', 'NAYARIT': 'NAY', 'NUEVO LEÓN': 'NLE',
  'OAXACA': 'OAX', 'PUEBLA': 'PUE', 'QUERÉTARO': 'QUE', 'QUINTANA ROO': 'ROO', 'SAN LUIS POTOSÍ': 'SLP',
  'SINALOA': 'SIN', 'SONORA': 'SON', 'TABASCO': 'TAB', 'TAMAULIPAS': 'TAM', 'TLAXCALA': 'TLA',
  'VERACRUZ': 'VER', 'YUCATÁN': 'YUC', 'ZACATECAS': 'ZAC'
}

const form = useForm({
  cliente_id: props.cliente.id,
  // Información General
  requiere_factura: props.cliente.requiere_factura ?? false,
  nombre_razon_social: props.cliente.nombre_razon_social ?? '',
  email: props.cliente.email ?? '',
  password: '',
  password_confirmation: '',
  telefono: props.cliente.telefono ?? '',
  whatsapp_optin: !!props.cliente.whatsapp_optin,

  // Lista de Precios
  price_list_id: props.cliente.price_list_id ?? '',

  // Dirección
  mostrar_direccion: props.cliente.mostrar_direccion ?? (!!props.cliente.calle || !!props.cliente.codigo_postal),
  calle: props.cliente.calle ?? '',
  numero_exterior: props.cliente.numero_exterior ?? '',
  numero_interior: props.cliente.numero_interior ?? '',
  colonia: props.cliente.colonia ?? '',
  codigo_postal: props.cliente.codigo_postal ?? '',
  municipio: props.cliente.municipio ?? '',
  estado: props.cliente.estado ?? '',
  pais: props.cliente.pais ?? 'MX',

  // Estado y Crédito
  activo: !!props.cliente.activo,
  credito_activo: !!props.cliente.credito_activo,
  limite_credito: props.cliente.limite_credito ?? '',
  dias_credito: props.cliente.dias_credito ?? 30,

  // Información Fiscal
  tipo_persona: props.cliente.tipo_persona ?? 'fisica',
  rfc: props.cliente.rfc ?? '',
  curp: props.cliente.curp ?? '',
  regimen_fiscal: props.cliente.regimen_fiscal ?? '',
  uso_cfdi: props.cliente.uso_cfdi ?? 'G03',
  domicilio_fiscal_cp: props.cliente.domicilio_fiscal_cp ?? '',
  forma_pago_default: props.cliente.forma_pago_default ?? '',
})

const hasGlobalErrors = computed(() => {
  return Object.keys(form.errors).length > 0
})

// === Watchers ===
watch(() => form.tipo_persona, (newVal, oldVal) => {
  if (newVal !== oldVal && oldVal) { 
     // Solo resetear si cambia explícitamente y había valor previo (no inicialización)
     // Pero en Edit, form.tipo_persona se inicializa.
     // Si cambiamos tipo, deberíamos limpiar?
     if (newVal === 'moral') {
        form.curp = ''
        form.clearErrors('curp')
     }
  }
})

watch(() => form.mostrar_direccion, (val) => {
   if (!val) {
     form.calle = ''; form.numero_exterior = ''; form.numero_interior = '';
     form.colonia = ''; form.codigo_postal = ''; form.municipio = '';
     form.estado = ''; form.pais = '';
     form.clearErrors(['calle', 'numero_exterior', 'codigo_postal', 'municipio'])
   }
})

// === Lifecycle ===
onMounted(() => {
    if (form.codigo_postal && form.codigo_postal.length === 5) {
        loadColonias(form.codigo_postal)
    }
})

// === Handlers ===
const onFacturaChange = () => {
    if (!form.requiere_factura) {
        form.tipo_persona = ''; form.rfc = ''; form.curp = '';
        form.regimen_fiscal = ''; form.uso_cfdi = 'G03';
        form.clearErrors(['tipo_persona', 'rfc', 'regimen_fiscal'])
    }
}

const onTipoPersonaChange = () => {
   // En edición, si el usuario cambia el tipo, limpiamos los campos dependientes
   const originalTipo = props.cliente?.tipo_persona
   if (form.tipo_persona !== originalTipo) {
       form.rfc = ''
       form.regimen_fiscal = ''
       form.clearErrors(['rfc', 'regimen_fiscal'])
   }
}

const loadColonias = async (cp) => {
    isLoadingCp.value = true
    try {
        const response = await axios.get(`/api/cp/${cp}`)
        const data = response.data
        availableColonias.value = data.colonias || []
        
        // Si la colonia actual no está en la lista (e.g. cambió CP), manejarlo? 
        // O si está cargando inicial, mantener la que viene del modelo.
    } catch (e) {
        console.warn('Error fetching colonias', e)
        availableColonias.value = []
    } finally {
        isLoadingCp.value = false
    }
}

const onCpInput = async (val) => {
    const digits = String(val).replace(/\D/g, '').slice(0, 5)
    
    if (digits.length === 5) {
        isLoadingCp.value = true
        try {
            const response = await axios.get(`/api/cp/${digits}`)
            const data = response.data
            
            if (data.estado) {
                const nombre = data.estado.trim().toUpperCase()
                const code = estadoMapping[nombre] || Object.entries(estadoMapping).find(([k,v]) => k.includes(nombre))?.[1] || data.estado
                form.estado = code
            }
            if (data.municipio) form.municipio = data.municipio
            if (!form.pais) form.pais = data.pais
            
            availableColonias.value = data.colonias || []
            
            // Lógica de selección inteligente
            if (availableColonias.value.length === 1) {
                form.colonia = availableColonias.value[0]
            } else if (!availableColonias.value.includes(form.colonia)) {
                 form.colonia = '' // Limpiar si la anterior no es válida para el nuevo CP
            }
            
            form.clearErrors(['estado', 'municipio', 'pais'])
            
            if (data.estado || data.municipio) {
                 showAutoCompleteMessage.value = true
                 setTimeout(() => showAutoCompleteMessage.value = false, 3000)
            }
        } catch (e) {
            console.warn('Error CP', e)
            availableColonias.value = []
        } finally {
            isLoadingCp.value = false
        }
    } else {
        availableColonias.value = []
    }
}

const resetForm = () => {
    form.reset()
    form.clearErrors()
    showSuccessMessage.value = false
    // Recargar colonias del CP original
    if (form.codigo_postal && form.codigo_postal.length === 5) {
        loadColonias(form.codigo_postal)
    }
}

const submit = () => {
    form.put(route('clientes.update', props.cliente.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessMessage.value = true
            form.reset('password', 'password_confirmation') // Solo limpiar password
            setTimeout(() => showSuccessMessage.value = false, 3000)
        },
        onError: () => {
             const firstError = Object.keys(form.errors)[0];
             if (firstError) {
                 // Intentar scroll al error?
                 // No tenemos refs fáciles a los inputs dentro del componente hijo sin expose.
                 window.scrollTo({ top: 0, behavior: 'smooth' })
             }
        }
    })
}
</script>
