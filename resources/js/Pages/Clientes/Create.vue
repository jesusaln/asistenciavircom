<template>
  <Head title="Crear Cliente" />

  <div class="w-full p-4 md:p-6" :style="cssVars">
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">

      <!-- Header compacto -->
      <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl shadow-sm" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
          </div>
          <div>
            <h1 class="text-lg font-black tracking-tight text-gray-900 dark:text-white">Nuevo Cliente</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">Paso {{ wizard.currentStepIndex + 1 }} de {{ wizard.steps.length }} · {{ wizard.progress }}% completado</p>
          </div>
        </div>
        <div class="hidden items-center gap-2 sm:flex">
          <div class="h-2 w-32 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
            <div class="h-full rounded-full transition-all duration-500 ease-out" :style="{ width: `${wizard.progress}%`, background: `linear-gradient(90deg, ${colors.principal}, ${colors.secundario})` }" />
          </div>
          <span class="text-xs font-bold text-gray-400">{{ wizard.progress }}%</span>
        </div>
      </div>

      <!-- Navegacion de pasos tipo stepper -->
      <div class="flex border-b border-gray-100 dark:border-gray-700">
        <button
          v-for="(step, index) in wizard.steps"
          :key="step.key"
          type="button"
          :disabled="!wizard.canVisitStep(index)"
          @click="wizard.goToStep(index)"
          class="group relative flex flex-1 items-center gap-2.5 px-4 py-3 text-left transition-all disabled:cursor-not-allowed disabled:opacity-40"
          :class="[
            wizard.currentStepIndex === index
              ? 'bg-gray-50 dark:bg-gray-750'
              : wizard.hasServerErrorsForStep(step)
                ? 'bg-rose-50/50 dark:bg-rose-950/20'
                : 'hover:bg-gray-50/50 dark:hover:bg-gray-750/50'
          ]"
        >
          <!-- Indicador activo -->
          <div
            v-if="wizard.currentStepIndex === index"
            class="absolute inset-x-0 bottom-0 h-0.5 rounded-full"
            :style="{ background: `linear-gradient(90deg, ${colors.principal}, ${colors.secundario})` }"
          />

          <!-- Numero/check del paso -->
          <div
            class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-xs font-black transition-all"
            :class="[
              wizard.currentStepIndex === index
                ? 'text-white shadow-sm'
                : index < wizard.currentStepIndex
                  ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                  : wizard.hasServerErrorsForStep(step)
                    ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400'
                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
            ]"
            :style="wizard.currentStepIndex === index ? { background: `linear-gradient(135deg, ${colors.principal}, ${colors.secundario})` } : {}"
          >
            <svg v-if="index < wizard.currentStepIndex" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
            <span v-else>{{ index + 1 }}</span>
          </div>

          <!-- Titulo del paso -->
          <div class="min-w-0">
            <div
              class="truncate text-sm font-bold"
              :class="[
                wizard.currentStepIndex === index
                  ? 'text-gray-900 dark:text-white'
                  : wizard.hasServerErrorsForStep(step)
                    ? 'text-rose-700 dark:text-rose-400'
                    : 'text-gray-600 dark:text-gray-300'
              ]"
            >{{ step.title }}</div>
          </div>
        </button>
      </div>

      <div class="relative p-6">


        <div v-if="hasGlobalErrors" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-5 text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
          <h3 class="text-sm font-black uppercase tracking-[0.2em]">Errores del formulario</h3>
          <ul class="mt-3 space-y-1 text-sm">
            <li v-for="(error, key) in form.errors" :key="key">{{ Array.isArray(error) ? error[0] : error }}</li>
          </ul>
        </div>

        <div
          v-if="wizard.currentStepHasClientIssues"
          class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100"
        >
          <h3 class="text-sm font-black uppercase tracking-[0.2em]">Completa este paso antes de continuar</h3>
          <ul class="mt-3 space-y-1 text-sm">
            <li v-for="issue in wizard.currentStepIssues" :key="issue">{{ issue }}</li>
          </ul>
        </div>

        <div
          v-if="showSuccessMessage"
          class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200"
        >
          Cliente creado exitosamente.
        </div>

        <div
          v-if="showAutoCompleteMessage"
          class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200"
        >
          La direccion se autocompleto con estado y municipio a partir del codigo postal.
        </div>

        <form @submit.prevent="submit" autocomplete="off">
          <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-6 dark:border-gray-700 dark:bg-slate-900/60">
            <div class="mb-5 flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
              <div class="text-lg font-black tracking-tight text-slate-900 dark:text-white">
                {{ wizard.currentStep?.title || 'Cargando...' }}
              </div>
              <span class="text-sm text-slate-500 dark:text-slate-400">—</span>
              <div class="text-sm text-slate-500 dark:text-slate-400">
                {{ wizard.currentStep?.description || '' }}
              </div>
            </div>

            <ClientForm
              :form="form"
              :catalogs="catalogs"
              :is-edit="false"
              :available-colonias="availableColonias"
              :is-loading-cp="isLoadingCp"
              :visible-sections="wizard.currentVisibleSections"
              @factura-change="onFacturaChange"
              @tipo-persona-change="onTipoPersonaChange"
              @cp-input="onCpInput"
            />
          </div>

          <div class="mt-6 flex flex-col gap-4 border-t border-gray-200 pt-5 dark:border-gray-700 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap gap-3">
              <button
                v-if="wizard.currentStepIndex > 0"
                type="button"
                @click="wizard.goPrevious"
                class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
              >
                ← Anterior
              </button>
            </div>

            <div class="flex items-center gap-3">
              <button
                type="submit"
                :disabled="form.processing || !form.nombre_razon_social"
                class="rounded-xl border-2 px-5 py-2.5 text-sm font-bold transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                :style="{ borderColor: colors.principal, color: colors.principal }"
              >
                <span v-if="form.processing">Guardando...</span>
                <span v-else>Guardar</span>
              </button>
              <button
                v-if="!wizard.isLastStep"
                type="button"
                @click="goNextStep"
                class="rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:opacity-95"
                :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
              >
                Siguiente →
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ClientForm from './Partials/ClientForm.vue'
import { useClientWizard } from './Partials/useClientWizard'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

const { cssVars, colors } = useCompanyColors()

const props = defineProps({
  catalogs: { type: Object, required: true },
  cliente: { type: Object, default: () => ({}) }
})

const showSuccessMessage = ref(false)
const showAutoCompleteMessage = ref(false)
const availableColonias = ref([])
const isLoadingCp = ref(false)

const estadoMapping = {
  'AGUASCALIENTES': 'AGU', 'BAJA CALIFORNIA': 'BCN', 'BAJA CALIFORNIA SUR': 'BCS', 'CAMPECHE': 'CAM',
  'CHIAPAS': 'CHP', 'CHIHUAHUA': 'CHH', 'CIUDAD DE MEXICO': 'DIF', 'COAHUILA': 'COA', 'COLIMA': 'COL',
  'DURANGO': 'DUR', 'GUANAJUATO': 'GUA', 'GUERRERO': 'GRO', 'HIDALGO': 'HID', 'JALISCO': 'JAL',
  'MEXICO': 'MEX', 'MICHOACAN': 'MIC', 'MORELOS': 'MOR', 'NAYARIT': 'NAY', 'NUEVO LEON': 'NLE',
  'OAXACA': 'OAX', 'PUEBLA': 'PUE', 'QUERETARO': 'QUE', 'QUINTANA ROO': 'ROO', 'SAN LUIS POTOSI': 'SLP',
  'SINALOA': 'SIN', 'SONORA': 'SON', 'TABASCO': 'TAB', 'TAMAULIPAS': 'TAM', 'TLAXCALA': 'TLA',
  'VERACRUZ': 'VER', 'YUCATAN': 'YUC', 'ZACATECAS': 'ZAC'
}

const form = useForm({
  requiere_factura: props.cliente.requiere_factura ?? false,
  nombre_razon_social: props.cliente.nombre_razon_social ?? '',
  email: props.cliente.email ?? '',
  password: '',
  password_confirmation: '',
  telefono: props.cliente.telefono ?? '',
  whatsapp_optin: props.cliente.whatsapp_optin ?? false,
  marketing_optin: props.cliente.marketing_optin ?? false,
  price_list_id: props.cliente.price_list_id || (props.catalogs?.priceLists?.[0]?.value ?? ''),
  mostrar_direccion: props.cliente.mostrar_direccion ?? false,
  calle: props.cliente.calle ?? '',
  numero_exterior: props.cliente.numero_exterior ?? '',
  numero_interior: props.cliente.numero_interior ?? '',
  colonia: props.cliente.colonia ?? '',
  codigo_postal: props.cliente.codigo_postal ?? '',
  municipio: props.cliente.municipio ?? '',
  estado: props.cliente.estado ?? '',
  pais: props.cliente.pais ?? 'MX',
  activo: props.cliente.activo ?? true,
  credito_activo: props.cliente.credito_activo ?? false,
  estado_credito: props.cliente.estado_credito ?? 'sin_credito',
  limite_credito: props.cliente.limite_credito ?? '',
  dias_credito: props.cliente.dias_credito ?? 30,
  dias_gracia: props.cliente.dias_gracia ?? '',
  tipo_persona: props.cliente.tipo_persona ?? 'fisica',
  rfc: props.cliente.rfc ?? '',
  curp: props.cliente.curp ?? '',
  regimen_fiscal: props.cliente.regimen_fiscal ?? '',
  uso_cfdi: props.cliente.uso_cfdi ?? 'G03',
  domicilio_fiscal_cp: props.cliente.domicilio_fiscal_cp ?? '',
  forma_pago_default: props.cliente.forma_pago_default ?? '',
})

const wizard = useClientWizard(form)

const hasGlobalErrors = computed(() => Object.keys(form.errors).length > 0)

watch(() => form.tipo_persona, (newVal) => {
  if (newVal === 'moral') {
    form.curp = ''
    form.clearErrors('curp')
  }
})

watch(() => form.mostrar_direccion, (val) => {
  if (!val) {
    form.calle = ''
    form.numero_exterior = ''
    form.numero_interior = ''
    form.colonia = ''
    form.codigo_postal = ''
    form.municipio = ''
    form.estado = ''
    form.pais = ''
    form.clearErrors(['calle', 'numero_exterior', 'codigo_postal', 'municipio'])
  }
})

const onFacturaChange = () => {
  if (!form.requiere_factura) {
    form.tipo_persona = ''
    form.rfc = ''
    form.curp = ''
    form.regimen_fiscal = ''
    form.uso_cfdi = 'G03'
    form.domicilio_fiscal_cp = ''
    form.forma_pago_default = ''
    form.clearErrors(['tipo_persona', 'rfc', 'regimen_fiscal', 'domicilio_fiscal_cp'])
  } else if (!form.tipo_persona) {
    form.tipo_persona = 'fisica'
  }
}

const onTipoPersonaChange = () => {
  form.rfc = ''
  form.regimen_fiscal = ''
  form.clearErrors(['rfc', 'regimen_fiscal'])
}

const normalizeStateName = (value) => {
  return value
    .trim()
    .toUpperCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

const onCpInput = async (val) => {
  const digits = String(val).replace(/\D/g, '').slice(0, 5)

  if (digits.length === 5) {
    isLoadingCp.value = true
    try {
      const response = await axios.get(`/api/cp/${digits}`)
      const data = response.data

      if (data.estado) {
        const nombre = normalizeStateName(data.estado)
        const code = estadoMapping[nombre] || data.estado
        form.estado = code
      }
      if (data.municipio) form.municipio = data.municipio
      if (!form.pais) form.pais = data.pais

      availableColonias.value = data.colonias || []
      if (availableColonias.value.length === 1) {
        form.colonia = availableColonias.value[0]
      }

      form.clearErrors(['estado', 'municipio', 'pais'])

      if (data.estado || data.municipio) {
        showAutoCompleteMessage.value = true
        setTimeout(() => {
          showAutoCompleteMessage.value = false
        }, 3000)
      }
    } catch (error) {
      console.warn('Error CP', error)
      availableColonias.value = []
    } finally {
      isLoadingCp.value = false
    }
  } else {
    availableColonias.value = []
  }
}

const goNextStep = () => {
  if (!wizard.goNext()) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const resetForm = () => {
  form.reset()
  form.clearErrors()
  availableColonias.value = []
  showSuccessMessage.value = false
  wizard.resetWizard()
}

const submit = () => {
  form.post(route('clientes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showSuccessMessage.value = true
      form.reset()
      availableColonias.value = []
      wizard.resetWizard()
      setTimeout(() => {
        showSuccessMessage.value = false
      }, 3000)
    },
    onError: () => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  })
}
</script>
