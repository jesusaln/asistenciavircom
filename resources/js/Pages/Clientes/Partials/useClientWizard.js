import { computed, ref, watch } from 'vue'

const STEP_FIELD_MAP = {
  general: [
    'nombre_razon_social',
    'telefono',
    'price_list_id',
    'activo',
  ],
  status: ['activo'],
  address: ['mostrar_direccion', 'calle', 'numero_exterior', 'numero_interior', 'colonia', 'codigo_postal', 'municipio', 'estado', 'pais'],
  credit: ['email', 'password', 'password_confirmation', 'credito_activo', 'estado_credito', 'limite_credito', 'dias_credito', 'dias_gracia'],
  fiscal: ['tipo_persona', 'rfc', 'curp', 'regimen_fiscal', 'uso_cfdi', 'domicilio_fiscal_cp', 'forma_pago_default'],
  expediente: [],
}

function isBlank(value) {
  return value === null || value === undefined || String(value).trim() === ''
}

function getStepIssues(step, form, isEdit) {
  const issues = []

  if (step.key === 'general') {
    if (isBlank(form.nombre_razon_social)) issues.push('Captura el nombre o razon social.')
  }

  if (step.key === 'address' && form.mostrar_direccion) {
    const cp = String(form.codigo_postal || '')
    if (cp && cp.length !== 5) issues.push('El codigo postal debe tener 5 digitos.')
  }

  if (step.key === 'credit') {
    if (form.password || form.password_confirmation) {
      if (String(form.password || '').length < 8) issues.push('La contrasena debe tener al menos 8 caracteres.')
      if (form.password !== form.password_confirmation) issues.push('La confirmacion de contrasena no coincide.')
    }
    if (form.credito_activo) {
      if (isBlank(form.limite_credito)) issues.push('Define el limite de credito para continuar.')
      if (isBlank(form.dias_credito)) issues.push('Define los dias de credito para continuar.')
    }
  }

  if (step.key === 'fiscal' && form.requiere_factura) {
    if (isBlank(form.tipo_persona)) issues.push('Selecciona el tipo de persona.')
    if (isBlank(form.rfc)) issues.push('Captura el RFC.')
    if (isBlank(form.regimen_fiscal)) issues.push('Selecciona el regimen fiscal.')
    if (isBlank(form.uso_cfdi)) issues.push('Selecciona el uso CFDI.')
    if (isBlank(form.domicilio_fiscal_cp)) issues.push('Captura el codigo postal fiscal.')
  }

  return issues
}

function getStepDefinitions(form, isEdit) {
  const steps = [
    {
      key: 'general',
      title: 'Perfil',
      description: 'Datos base, contacto y estatus del cliente.',
      sections: ['general', 'status'],
    },
    {
      key: 'address',
      title: 'Direccion',
      description: 'Ubicacion y datos de entrega o contacto.',
      sections: ['address'],
    },
    {
      key: 'credit',
      title: 'Credito',
      description: 'Condiciones comerciales y limites de financiamiento.',
      sections: ['credit'],
    },
  ]

  if (form.requiere_factura) {
    steps.push({
      key: 'fiscal',
      title: 'Fiscal',
      description: 'Datos CFDI y configuracion para facturacion.',
      sections: ['fiscal'],
    })
  }

  if (isEdit) {
    steps.push({
      key: 'expediente',
      title: 'Expediente',
      description: 'Revision documental y soporte de credito.',
      sections: ['expediente'],
    })
  }

  return steps
}

export function useClientWizard(form, options = {}) {
  const isEdit = options.isEdit ?? false
  const currentStepIndex = ref(0)
  const attemptedStepKeys = ref([])

  const steps = computed(() => getStepDefinitions(form, isEdit))
  const currentStep = computed(() => steps.value[currentStepIndex.value] ?? steps.value[0] ?? null)
  const progress = computed(() => {
    if (!steps.value.length) return 0
    return Math.round(((currentStepIndex.value + 1) / steps.value.length) * 100)
  })
  const isLastStep = computed(() => steps.value.length > 0 && currentStepIndex.value === steps.value.length - 1)
  const currentVisibleSections = computed(() => {
    const sections = Array.isArray(currentStep.value?.sections) ? currentStep.value.sections : []
    return sections.filter((section) => section !== 'expediente')
  })

  const currentStepIssues = computed(() => {
    if (!currentStep.value) return []
    return getStepIssues(currentStep.value, form, isEdit)
  })

  const currentStepHasClientIssues = computed(() => {
    return attemptedStepKeys.value.includes(currentStep.value?.key) && currentStepIssues.value.length > 0
  })

  const hasServerErrorsForStep = (step) => {
    const sections = Array.isArray(step?.sections) ? step.sections : []
    const fields = sections.flatMap((section) => STEP_FIELD_MAP[section] ?? [])
    return fields.some((field) => Object.prototype.hasOwnProperty.call(form.errors, field))
  }

  const canVisitStep = (index) => {
    if (isEdit) return true
    return index <= currentStepIndex.value
  }

  const goToStep = (index) => {
    if (!canVisitStep(index)) return
    currentStepIndex.value = index
  }

  const goNext = () => {
    const stepKey = currentStep.value?.key
    if (stepKey && !attemptedStepKeys.value.includes(stepKey)) {
      attemptedStepKeys.value = [...attemptedStepKeys.value, stepKey]
    }

    if (currentStepIssues.value.length > 0 || isLastStep.value) return false

    currentStepIndex.value += 1
    return true
  }

  const goPrevious = () => {
    if (currentStepIndex.value > 0) currentStepIndex.value -= 1
  }

  const resetWizard = () => {
    currentStepIndex.value = 0
    attemptedStepKeys.value = []
  }

  watch(
    steps,
    (newSteps, oldSteps) => {
      if (!newSteps.length) return

      if (currentStepIndex.value >= newSteps.length) {
        currentStepIndex.value = newSteps.length - 1
      }

      if (oldSteps && oldSteps.length) {
        const previousStep = oldSteps[currentStepIndex.value]
        if (previousStep) {
          const nextIndex = newSteps.findIndex((s) => s && s.key === previousStep.key)
          if (nextIndex >= 0) currentStepIndex.value = nextIndex
        }
      }
    },
    { immediate: true }
  )

  // Usamos getters de JS para que el template acceda directamente
  // a los valores resueltos de los computed/refs sin problemas de unwrapping.
  return {
    get steps() { return steps.value },
    get currentStepIndex() { return currentStepIndex.value },
    set currentStepIndex(val) { currentStepIndex.value = val },
    get currentStep() { return currentStep.value },
    get progress() { return progress.value },
    get isLastStep() { return isLastStep.value },
    get currentVisibleSections() { return currentVisibleSections.value },
    get currentStepIssues() { return currentStepIssues.value },
    get currentStepHasClientIssues() { return currentStepHasClientIssues.value },
    hasServerErrorsForStep,
    canVisitStep,
    goToStep,
    goNext,
    goPrevious,
    resetWizard,
  }
}
