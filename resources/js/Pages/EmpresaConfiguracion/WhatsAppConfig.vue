<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empresa: {
    type: Object,
    default: () => ({
      whatsapp_enabled: false,
      whatsapp_business_account_id: '',
      whatsapp_phone_number_id: '',
      whatsapp_sender_phone: '',
      whatsapp_access_token: '',
      whatsapp_app_secret: '',
      whatsapp_webhook_verify_token: '',
      whatsapp_default_language: 'es_MX',
      whatsapp_template_payment_reminder: '',
    })
  }
})

/* =========================
   Configuración de notificaciones
========================= */
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

/* =========================
   Estado del formulario
========================= */
const form = ref({ ...props.empresa })
const loading = ref(false)
const testing = ref(false)

/* =========================
   Validación del formulario
========================= */
const errors = ref({})

const validateForm = () => {
  errors.value = {}

  if (form.value.whatsapp_enabled) {
    if (!form.value.whatsapp_business_account_id) {
      errors.value.whatsapp_business_account_id = 'El ID de cuenta empresarial es requerido'
    }

    if (!form.value.whatsapp_phone_number_id) {
      errors.value.whatsapp_phone_number_id = 'El ID de número de teléfono es requerido'
    }

    if (!form.value.whatsapp_sender_phone) {
      errors.value.whatsapp_sender_phone = 'El número de teléfono es requerido'
    } else if (!isValidE164Phone(form.value.whatsapp_sender_phone)) {
      errors.value.whatsapp_sender_phone = 'El número debe estar en formato E.164 (ej: +52551234567)'
    }

    if (!form.value.whatsapp_access_token) {
      errors.value.whatsapp_access_token = 'El token de acceso es requerido'
    }

    if (!form.value.whatsapp_app_secret) {
      errors.value.whatsapp_app_secret = 'El secreto de la aplicación es requerido'
    }

    if (!form.value.whatsapp_webhook_verify_token) {
      errors.value.whatsapp_webhook_verify_token = 'El token de verificación del webhook es requerido'
    }

    if (!form.value.whatsapp_template_payment_reminder) {
      errors.value.whatsapp_template_payment_reminder = 'La plantilla de recordatorio de pago es requerida'
    }
  }

  return Object.keys(errors.value).length === 0
}

/* =========================
   Envío del formulario
========================= */
const submitForm = () => {
  if (!validateForm()) {
    notyf.error('Por favor corrija los errores del formulario')
    return
  }

  loading.value = true

  router.put(route('empresa-configuracion.whatsapp.update'), form.value, {
    onStart: () => {
      notyf.success('Guardando configuración...')
    },
    onSuccess: () => {
      notyf.success('Configuración guardada correctamente')
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al guardar la configuración')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

/* =========================
   Función de prueba
========================= */
const testConfiguration = async () => {
  if (!form.value.whatsapp_enabled) {
    notyf.error('Debe habilitar WhatsApp primero')
    return
  }

  if (!validateForm()) {
    notyf.error('Corrija los errores antes de probar')
    return
  }

  testing.value = true

  try {
    const response = await fetch('/admin/whatsapp/test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        telefono: form.value.whatsapp_sender_phone,
        mensaje: 'Mensaje de prueba - Configuración de WhatsApp funcionando correctamente'
      })
    })

    const data = await response.json()

    if (response.ok && data.success) {
      notyf.success('Configuración válida: ' + data.message)
    } else {
      notyf.error('Error en configuración: ' + (data.message || 'Error desconocido'))
    }
  } catch (error) {
    console.error('Error en prueba:', error)
    notyf.error('Error de conexión: ' + error.message)
  } finally {
    testing.value = false
  }
}

/* =========================
   Funciones auxiliares
========================= */
const isValidE164Phone = (phone) => {
  return /^\+[1-9]\d{1,14}$/.test(phone)
}

const generateWebhookToken = () => {
  const token = Math.random().toString(36).substring(2, 15) +
               Math.random().toString(36).substring(2, 15)
  form.value.whatsapp_webhook_verify_token = token
}

const opcionesIdioma = [
  { value: 'es_MX', label: 'Español (México)' },
  { value: 'en_US', label: 'English (US)' },
]

const opcionesPlantilla = [
  { value: 'recordarorio_de_instalacion', label: 'Recordatorio de Instalación' },
  { value: 'saludo', label: 'Mensaje de Bienvenida (Saludo)' },
  { value: 'mensaje_de_bienvenida_climas_del_desierto', label: 'Bienvenida Climas del Desierto' },
  { value: 'hello_world', label: 'Hello World (Prueba)' },
  { value: 'payment_reminder', label: 'Recordatorio de Pago (Nueva)' },
]
</script>

<template>
  <Head title="Configuración de WhatsApp" />

  <div class="whatsapp-config min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Configuración de WhatsApp Business</h1>
            <p class="text-gray-600 mt-2">Configure la integración con WhatsApp Business API para envío de recordatorios automáticos</p>
          </div>
          <router-link
            :to="route('empresa-configuracion.index')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          >
            ← Volver a Configuración
          </router-link>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-8">
        <!-- Formulario principal -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Configuración de WhatsApp</h2>
          </div>

          <form @submit.prevent="submitForm" class="p-6 space-y-6">
            <!-- Habilitar WhatsApp -->
            <div>
              <div class="flex items-center">
                <input
                  id="whatsapp_enabled"
                  v-model="form.whatsapp_enabled"
                  type="checkbox"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="whatsapp_enabled" class="ml-2 block text-sm font-medium text-gray-700">
                  Habilitar WhatsApp Business
                </label>
              </div>
              <p class="mt-1 text-sm text-gray-500">
                Active esta opción para habilitar el envío automático de recordatorios por WhatsApp
              </p>
            </div>

            <!-- Información de la API -->
            <div v-if="form.whatsapp_enabled" class="space-y-6">
              <div class="border-t border-gray-200 pt-6">
                <h3 class="text-md font-medium text-gray-900 mb-4">Configuración de la API</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Business Account ID -->
                  <div>
                    <label for="whatsapp_business_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                      Business Account ID *
                    </label>
                    <input
                      id="whatsapp_business_account_id"
                      v-model="form.whatsapp_business_account_id"
                      type="text"
                      placeholder="1234567890123456"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                      :class="{ 'border-red-300': errors.whatsapp_business_account_id }"
                    />
                    <p v-if="errors.whatsapp_business_account_id" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_business_account_id }}</p>
                    <p class="mt-1 text-xs text-gray-500">Obtenible en Facebook Business Manager</p>
                  </div>

                  <!-- Phone Number ID -->
                  <div>
                    <label for="whatsapp_phone_number_id" class="block text-sm font-medium text-gray-700 mb-2">
                      Phone Number ID *
                    </label>
                    <input
                      id="whatsapp_phone_number_id"
                      v-model="form.whatsapp_phone_number_id"
                      type="text"
                      placeholder="123456789012345"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                      :class="{ 'border-red-300': errors.whatsapp_phone_number_id }"
                    />
                    <p v-if="errors.whatsapp_phone_number_id" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_phone_number_id }}</p>
                    <p class="mt-1 text-xs text-gray-500">ID del número de WhatsApp Business</p>
                  </div>

                  <!-- Número de teléfono -->
                  <div>
                    <label for="whatsapp_sender_phone" class="block text-sm font-medium text-gray-700 mb-2">
                      Número de Teléfono *
                    </label>
                    <input
                      id="whatsapp_sender_phone"
                      v-model="form.whatsapp_sender_phone"
                      type="text"
                      placeholder="+52551234567"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                      :class="{ 'border-red-300': errors.whatsapp_sender_phone }"
                    />
                    <p v-if="errors.whatsapp_sender_phone" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_sender_phone }}</p>
                    <p class="mt-1 text-xs text-gray-500">Formato E.164 con código de país</p>
                  </div>

                  <!-- Idioma por defecto -->
                  <div>
                    <label for="whatsapp_default_language" class="block text-sm font-medium text-gray-700 mb-2">
                      Idioma por Defecto
                    </label>
                    <select
                      id="whatsapp_default_language"
                      v-model="form.whatsapp_default_language"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option v-for="opcion in opcionesIdioma" :key="opcion.value" :value="opcion.value">
                        {{ opcion.label }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Credenciales de seguridad -->
              <div class="border-t border-gray-200 pt-6">
                <h3 class="text-md font-medium text-gray-900 mb-4">Credenciales de Seguridad</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Access Token -->
                  <div class="md:col-span-2">
                    <label for="whatsapp_access_token" class="block text-sm font-medium text-gray-700 mb-2">
                      Access Token *
                    </label>
                    <textarea
                      id="whatsapp_access_token"
                      v-model="form.whatsapp_access_token"
                      rows="3"
                      placeholder="EAAG..."
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                      :class="{ 'border-red-300': errors.whatsapp_access_token }"
                    ></textarea>
                    <p v-if="errors.whatsapp_access_token" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_access_token }}</p>
                    <p class="mt-1 text-xs text-gray-500">Token de acceso permanente de Facebook</p>
                  </div>

                  <!-- App Secret -->
                  <div class="md:col-span-2">
                    <label for="whatsapp_app_secret" class="block text-sm font-medium text-gray-700 mb-2">
                      App Secret *
                    </label>
                    <textarea
                      id="whatsapp_app_secret"
                      v-model="form.whatsapp_app_secret"
                      rows="3"
                      placeholder="abcd1234..."
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                      :class="{ 'border-red-300': errors.whatsapp_app_secret }"
                    ></textarea>
                    <p v-if="errors.whatsapp_app_secret" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_app_secret }}</p>
                    <p class="mt-1 text-xs text-gray-500">Secreto de la aplicación de Facebook</p>
                  </div>

                  <!-- Webhook Verify Token -->
                  <div>
                    <label for="whatsapp_webhook_verify_token" class="block text-sm font-medium text-gray-700 mb-2">
                      Token de Verificación Webhook *
                    </label>
                    <div class="flex space-x-2">
                      <input
                        id="whatsapp_webhook_verify_token"
                        v-model="form.whatsapp_webhook_verify_token"
                        type="text"
                        placeholder="token123456"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-300': errors.whatsapp_webhook_verify_token }"
                      />
                      <button
                        type="button"
                        @click="generateWebhookToken"
                        class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        Generar
                      </button>
                    </div>
                    <p v-if="errors.whatsapp_webhook_verify_token" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_webhook_verify_token }}</p>
                    <p class="mt-1 text-xs text-gray-500">Token personalizado para verificar webhooks</p>
                  </div>

                  <!-- Plantilla de recordatorio -->
                  <div>
                    <label for="whatsapp_template_payment_reminder" class="block text-sm font-medium text-gray-700 mb-2">
                      Plantilla de Recordatorio *
                    </label>
                    <select
                      id="whatsapp_template_payment_reminder"
                      v-model="form.whatsapp_template_payment_reminder"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                      :class="{ 'border-red-300': errors.whatsapp_template_payment_reminder }"
                    >
                      <option value="">Seleccionar plantilla...</option>
                      <option v-for="opcion in opcionesPlantilla" :key="opcion.value" :value="opcion.value">
                        {{ opcion.label }}
                      </option>
                    </select>
                    <p v-if="errors.whatsapp_template_payment_reminder" class="mt-1 text-sm text-red-600">{{ errors.whatsapp_template_payment_reminder }}</p>
                    <p class="mt-1 text-xs text-gray-500">Plantilla aprobada por Meta para recordatorios</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Información de ayuda -->
            <div v-if="form.whatsapp_enabled" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h4 class="text-sm font-medium text-blue-900 mb-2">Información Importante</h4>
              <div class="text-sm text-blue-800 space-y-1">
                <p>• Asegúrese de que las plantillas estén aprobadas en Meta Business</p>
                <p>• El número de teléfono debe estar en formato E.164 (ej: +52551234567)</p>
                <p>• Configure el webhook en Meta apuntando a: <code class="bg-blue-100 px-1 rounded">https://sudominio.com/api/webhooks/whatsapp</code></p>
                <p>• Use el mismo token de verificación en la configuración de Meta</p>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
              <button
                v-if="form.whatsapp_enabled"
                type="button"
                @click="testConfiguration"
                :disabled="testing"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
              >
                <span v-if="testing" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Probando...
                </span>
                <span v-else>🧪 Probar Configuración</span>
              </button>

              <div class="flex items-center space-x-3">
                <button
                  type="button"
                  @click="$inertia.get(route('empresa-configuracion.index'))"
                  class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                >
                  ❌ Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  <span v-if="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Guardando...
                  </span>
                  <span v-else>💾 Guardar Configuración</span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.whatsapp-config {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>
