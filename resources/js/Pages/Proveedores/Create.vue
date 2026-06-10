<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import FormCard from '@/Components/FormCard.vue'
import FormField from '@/Components/FormField.vue'

defineOptions({ layout: AppLayout })

const showPreview = ref(false)
const rfcValid = ref(false)
const emailValid = ref(false)
const telefonoValid = ref(false)
const errors = ref({})

const page = usePage()

const regimenesFiscales = {
    fisica: [
        { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales' },
        { codigo: '614', descripcion: 'Personas Físicas con Actividades Empresariales' },
        { codigo: '616', descripcion: 'Personas Físicas con Actividades Profesionales' },
        { codigo: '621', descripcion: 'Incorporación Fiscal' },
        { codigo: '626', descripcion: 'Régimen Simplificado de Confianza' },
    ],
    moral: [
        { codigo: '601', descripcion: 'General de Ley Personas Morales' },
        { codigo: '603', descripcion: 'Personas Morales con Fines no Lucrativos' },
        { codigo: '609', descripcion: 'Consolidación' },
        { codigo: '620', descripcion: 'Sociedades Cooperativas de Producción' },
        { codigo: '622', descripcion: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
        { codigo: '623', descripcion: 'Opcional para Grupos de Sociedades' },
        { codigo: '624', descripcion: 'Coordinados' },
    ]
}

const regimenesFiscalesFiltrados = computed(() => {
    if (!form.tipo_persona) return []
    return regimenesFiscales[form.tipo_persona] || []
})

const form = useForm({
    nombre_razon_social: '',
    tipo_persona: '',
    rfc: '',
    regimen_fiscal: '',
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

const formValid = computed(() => {
    return form.nombre_razon_social && form.tipo_persona && rfcValid.value && form.regimen_fiscal
})

onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    if (params.has('nombre_razon_social')) form.nombre_razon_social = params.get('nombre_razon_social')
    if (params.has('rfc')) {
        form.rfc = params.get('rfc')
        if (params.has('tipo_persona')) {
            form.tipo_persona = params.get('tipo_persona')
            setTimeout(() => validarRFC(), 100)
        }
    }
    if (params.has('regimen_fiscal')) form.regimen_fiscal = params.get('regimen_fiscal')
})

const convertirAMayusculas = (campo) => {
    if (form[campo]) form[campo] = form[campo].toUpperCase().trim()
}

const onTipoPersonaChange = () => {
    form.rfc = ''
    rfcValid.value = false
    form.clearErrors('rfc')
    form.regimen_fiscal = ''
    form.clearErrors('regimen_fiscal')
}

const onRfcInput = (event) => {
    form.rfc = event.target.value.toUpperCase()
    validarRFC()
}

const validarRFC = () => {
    if (!form.rfc || !form.tipo_persona) { rfcValid.value = false; return }
    const rfcRegexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/
    const rfcRegexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/
    const palabrasProhibidas = ['BUEI', 'BUEY', 'CACA', 'CACO', 'CAGA', 'CAGO', 'CAKA', 'CAKO', 'COGE', 'COGI', 'COJA', 'COJE', 'COJI', 'COJO', 'COLA', 'CULO', 'FALO', 'FETO', 'GETA', 'GUEY', 'JOTO', 'KACA', 'KACO', 'KAGA', 'KAGO', 'KAKA', 'KAKO', 'KOGE', 'KOGI', 'KOJA', 'KOJE', 'KOJI', 'KOJO', 'KOLA', 'KULO', 'LILO', 'LOCA', 'LOCO', 'LOKA', 'LOKO', 'MAME', 'MAMO', 'MEAR', 'MEAS', 'MEON', 'MIAR', 'MION', 'MOCO', 'MOKO', 'MULA', 'MULO', 'NACA', 'NACO', 'PEDA', 'PEDO', 'PENE', 'PIPI', 'PITO', 'POPO', 'PUTA', 'PUTO', 'QULO', 'RATA', 'ROBA', 'ROBE', 'ROBO', 'RUIN', 'SENO', 'TETA', 'VACA', 'VAGA', 'VAGO', 'VAKA', 'VUEY', 'WUEY', 'ZORRA']

    if (form.tipo_persona === 'fisica') {
        if (form.rfc.length !== 13 || !rfcRegexFisica.test(form.rfc)) { form.setError('rfc', 'RFC inválido para persona física'); rfcValid.value = false; return }
        if (palabrasProhibidas.includes(form.rfc.substring(0, 4))) { form.setError('rfc', 'Combinación no permitida'); rfcValid.value = false; return }
    } else if (form.tipo_persona === 'moral') {
        if (form.rfc.length !== 12 || !rfcRegexMoral.test(form.rfc)) { form.setError('rfc', 'RFC inválido para persona moral'); rfcValid.value = false; return }
        if (palabrasProhibidas.some(p => p.startsWith(form.rfc.substring(0, 3)))) { form.setError('rfc', 'Combinación no permitida'); rfcValid.value = false; return }
    }
    form.clearErrors('rfc')
    rfcValid.value = true
}

const validateEmail = () => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!form.email) { emailValid.value = true; form.clearErrors('email'); return }
    if (!emailRegex.test(form.email) || form.email.length > 100) {
        form.setError('email', 'Email inválido'); emailValid.value = false; return
    }
    form.clearErrors('email')
    emailValid.value = true
}

const validarTelefono = () => {
    form.telefono = form.telefono.replace(/\D/g, '')
    if (!form.telefono) { telefonoValid.value = true; form.clearErrors('telefono'); return }
    if (form.telefono.length !== 10) { form.setError('telefono', 'Debe tener 10 dígitos'); telefonoValid.value = false; return }
    if (/^(\d)\1{9}$/.test(form.telefono)) { form.setError('telefono', 'Todos los dígitos iguales'); telefonoValid.value = false; return }
    form.clearErrors('telefono')
    telefonoValid.value = true
}

const validarCodigoPostal = async () => {
    form.codigo_postal = form.codigo_postal.replace(/\D/g, '')
    if (form.codigo_postal.length === 5) {
        form.clearErrors('codigo_postal')
        try {
            const response = await fetch(`/api/cp/${form.codigo_postal}`)
            if (response.ok) {
                const data = await response.json()
                form.estado = data.estado
                form.municipio = data.municipio
                form.pais = data.pais
                if (data.colonias && data.colonias.length === 1) form.colonia = data.colonias[0]
            }
        } catch (e) { console.warn('Error CP:', e) }
    } else if (form.codigo_postal.length > 0) {
        form.setError('codigo_postal', 'Debe tener 5 dígitos')
    }
}

const submit = () => {
    if (!formValid.value) {
        if (!form.nombre_razon_social) form.setError('nombre_razon_social', 'Campo obligatorio')
        if (!form.tipo_persona) form.setError('tipo_persona', 'Seleccione un tipo')
        if (!rfcValid.value) form.setError('rfc', 'RFC inválido')
        if (!form.regimen_fiscal) form.setError('regimen_fiscal', 'Seleccione un régimen')
        return
    }
    if (form.email && !emailValid.value) { form.setError('email', 'Email inválido'); return }
    if (form.telefono && !telefonoValid.value) { form.setError('telefono', 'Teléfono inválido'); return }

    form.post(route('proveedores.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            rfcValid.value = emailValid.value = telefonoValid.value = false
        },
        onError: (err) => { errors.value = err },
    })
}

const resetForm = () => { form.reset(); rfcValid.value = emailValid.value = telefonoValid.value = false }
const previewData = () => { showPreview.value = true }
const cancel = () => router.get(route('proveedores.index'))
</script>

<template>
    <Head title="Nuevo Proveedor" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Nuevo Proveedor" subtitle="Registra un nuevo proveedor">
                <template #actions>
                    <button @click="cancel"
                        class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Cancelar
                    </button>
                </template>
            </CrudPageHeader>

            <div v-if="Object.keys(form.errors).length" class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/30 rounded-xl">
                <div class="flex items-center mb-2">
                    <svg class="w-4 h-4 text-rose-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <h3 class="text-rose-800 dark:text-rose-200 font-medium text-sm">Por favor corrige los siguientes errores:</h3>
                </div>
                <ul class="list-disc list-inside text-rose-800 dark:text-rose-200 text-sm space-y-1">
                    <li v-for="(error, field) in form.errors" :key="field"><strong>{{ field }}:</strong> {{ error }}</li>
                </ul>
            </div>

            <FormCard>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Información General -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Información General</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <FormField id="nombre_razon_social" v-model="form.nombre_razon_social" label="Nombre/Razón Social" placeholder="Nombre o razón social" :error="form.errors.nombre_razon_social" @blur="convertirAMayusculas('nombre_razon_social')" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tipo de Persona <span class="text-rose-500">*</span></label>
                                <select v-model="form.tipo_persona" @change="onTipoPersonaChange"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    :class="form.errors.tipo_persona ? 'border-rose-300' : 'border-slate-300 dark:border-slate-600'">
                                    <option value="" disabled>Selecciona tipo</option>
                                    <option value="fisica">Persona Física</option>
                                    <option value="moral">Persona Moral</option>
                                </select>
                                <p v-if="form.errors.tipo_persona" class="mt-1 text-xs text-rose-500">{{ form.errors.tipo_persona }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">RFC <span class="text-rose-500">*</span>
                                    <span class="text-xs text-slate-500">({{ form.tipo_persona === 'fisica' ? '13 caracteres' : form.tipo_persona === 'moral' ? '12 caracteres' : '' }})</span>
                                </label>
                                <div class="relative">
                                    <input v-model="form.rfc" @input="onRfcInput" type="text" :maxlength="form.tipo_persona === 'fisica' ? 13 : 12" :disabled="!form.tipo_persona" required
                                        class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all uppercase pr-10"
                                        :class="form.errors.rfc ? 'border-rose-300' : rfcValid && form.rfc ? 'border-emerald-400' : 'border-slate-300 dark:border-slate-600'"
                                        :placeholder="form.tipo_persona === 'fisica' ? 'ABCD123456EFG' : form.tipo_persona === 'moral' ? 'ABC123456EFG' : 'Seleccione tipo primero'" />
                                    <div v-if="rfcValid && form.rfc" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </div>
                                </div>
                                <p v-if="form.errors.rfc" class="mt-1 text-xs text-rose-500">{{ form.errors.rfc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información Fiscal -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Información Fiscal</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Régimen Fiscal <span class="text-rose-500">*</span></label>
                                <select v-model="form.regimen_fiscal"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    :class="form.errors.regimen_fiscal ? 'border-rose-300' : 'border-slate-300 dark:border-slate-600'">
                                    <option value="" disabled>Selecciona régimen</option>
                                    <option v-for="regimen in regimenesFiscalesFiltrados" :key="regimen.codigo" :value="regimen.codigo">{{ regimen.codigo }} - {{ regimen.descripcion }}</option>
                                </select>
                                <p v-if="form.errors.regimen_fiscal" class="mt-1 text-xs text-rose-500">{{ form.errors.regimen_fiscal }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contacto -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Información de Contacto</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <FormField id="email" v-model="form.email" label="Correo Electrónico" type="email" placeholder="ejemplo@correo.com" :error="form.errors.email" @input="validateEmail" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Teléfono <span class="text-xs text-slate-500">(10 dígitos)</span></label>
                                <input v-model="form.telefono" @input="validarTelefono" type="tel" maxlength="10"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    :class="form.errors.telefono ? 'border-rose-300' : telefonoValid && form.telefono ? 'border-emerald-400' : 'border-slate-300 dark:border-slate-600'"
                                    placeholder="6621234567" />
                                <p v-if="form.errors.telefono" class="mt-1 text-xs text-rose-500">{{ form.errors.telefono }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Dirección</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="lg:col-span-2">
                                <FormField id="calle" v-model="form.calle" label="Calle" placeholder="Nombre de la calle" :error="form.errors.calle" @blur="convertirAMayusculas('calle')" />
                            </div>
                            <div>
                                <FormField id="numero_exterior" v-model="form.numero_exterior" label="Número Exterior" placeholder="123" :error="form.errors.numero_exterior" />
                            </div>
                            <div>
                                <FormField id="numero_interior" v-model="form.numero_interior" label="Número Interior" placeholder="A, 1, Depto 2" />
                            </div>
                            <div>
                                <FormField id="colonia" v-model="form.colonia" label="Colonia" placeholder="Nombre de la colonia" :error="form.errors.colonia" @blur="convertirAMayusculas('colonia')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Código Postal</label>
                                <input v-model="form.codigo_postal" @input="validarCodigoPostal" type="text" maxlength="5"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    :class="form.errors.codigo_postal ? 'border-rose-300' : 'border-slate-300 dark:border-slate-600'"
                                    placeholder="83000" />
                                <p v-if="form.errors.codigo_postal" class="mt-1 text-xs text-rose-500">{{ form.errors.codigo_postal }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Municipio</label>
                                <input v-model="form.municipio" type="text" readonly
                                    class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Estado</label>
                                <input v-model="form.estado" type="text" readonly
                                    class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">País</label>
                                <input v-model="form.pais" type="text" readonly
                                    class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex gap-3">
                            <button type="button" @click="resetForm"
                                class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                                Limpiar
                            </button>
                            <button type="button" @click="previewData"
                                class="px-5 py-2.5 text-sm font-semibold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-900/20 border border-sky-300 dark:border-sky-700 rounded-xl hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-all duration-200">
                                Vista Previa
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="cancel"
                                class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing || !formValid"
                                class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 rounded-xl transition-all duration-200 shadow-sm disabled:opacity-50 inline-flex items-center gap-2">
                                <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ form.processing ? 'Guardando...' : 'Guardar Proveedor' }}
                            </button>
                        </div>
                    </div>
                </form>
            </FormCard>

            <!-- Preview Modal -->
            <div v-if="showPreview" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showPreview = false">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">Vista Previa del Proveedor</h3>
                        <button @click="showPreview = false" class="text-slate-400 hover:text-brand-600 dark:hover:text-slate-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>Nombre/Razón Social:</strong> {{ form.nombre_razon_social || 'No especificado' }}</div>
                            <div><strong>Tipo:</strong> {{ form.tipo_persona === 'fisica' ? 'Física' : form.tipo_persona === 'moral' ? 'Moral' : 'No especificado' }}</div>
                            <div><strong>RFC:</strong> {{ form.rfc || 'No especificado' }}</div>
                            <div><strong>Email:</strong> {{ form.email || 'No especificado' }}</div>
                            <div><strong>Teléfono:</strong> {{ form.telefono || 'No especificado' }}</div>
                            <div><strong>Régimen Fiscal:</strong> {{ form.regimen_fiscal || 'No especificado' }}</div>
                        </div>
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                            <strong>Dirección:</strong>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                {{ [form.calle, form.numero_exterior, form.numero_interior].filter(Boolean).join(' ') || 'No especificada' }}<br />
                                {{ form.colonia }}, {{ form.municipio }}<br />
                                {{ form.estado }}, {{ form.pais }} {{ form.codigo_postal }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
