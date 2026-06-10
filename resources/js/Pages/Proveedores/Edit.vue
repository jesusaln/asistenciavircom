<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import FormCard from '@/Components/FormCard.vue'
import FormField from '@/Components/FormField.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

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

const props = defineProps({
    proveedor: { type: Object, required: true }
})

const isValidEmail = ref(false)
const errors = ref({})

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
]

const form = useForm({
    nombre_razon_social: props.proveedor.nombre_razon_social || '',
    tipo_persona: props.proveedor.tipo_persona || '',
    rfc: props.proveedor.rfc || '',
    regimen_fiscal: props.proveedor.regimen_fiscal || '',
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
})

const originalFormData = { ...form.data() }

const isRfcValid = computed(() => {
    if (!form.rfc || !form.tipo_persona) return false
    const rfcRegexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/
    const rfcRegexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/
    if (form.tipo_persona === 'fisica') return form.rfc.length === 13 && rfcRegexFisica.test(form.rfc)
    if (form.tipo_persona === 'moral') return form.rfc.length === 12 && rfcRegexMoral.test(form.rfc)
    return false
})

const isTelefonoValid = computed(() => {
    if (!form.telefono) return false
    return /^\d{10}$/.test(form.telefono)
})

const isFormValid = computed(() => {
    const requiredFields = ['nombre_razon_social', 'tipo_persona', 'rfc', 'regimen_fiscal', 'email', 'telefono', 'calle', 'numero_exterior', 'colonia', 'codigo_postal']
    const hasRequired = requiredFields.every(f => form[f] && form[f].toString().trim())
    return hasRequired && Object.keys(form.errors).length === 0 && isRfcValid.value && isValidEmail.value && isTelefonoValid.value
})

const convertirAMayusculas = (campo) => {
    if (form[campo]) form[campo] = form[campo].toString().toUpperCase().trim()
}

const handleTipoPersonaChange = () => {
    form.clearErrors('tipo_persona')
    form.rfc = ''
    form.clearErrors('rfc')
}

const handleRfcInput = (event) => {
    form.rfc = event.target.value.toUpperCase()
    validateRFC()
}

const handleTelefonoInput = (event) => {
    form.telefono = event.target.value.replace(/\D/g, '')
    validateTelefono()
}

const validateEmail = () => {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    isValidEmail.value = regex.test(form.email)
    if (form.email && !isValidEmail.value) form.setError('email', 'Email inválido')
    else form.clearErrors('email')
}

const validateRFC = () => {
    if (!form.tipo_persona) { form.setError('rfc', 'Primero seleccione tipo de persona'); return }
    const rf = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/
    const rm = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/
    if (form.tipo_persona === 'fisica' && (form.rfc.length !== 13 || !rf.test(form.rfc))) {
        form.setError('rfc', 'RFC debe tener 13 caracteres válidos')
        return
    }
    if (form.tipo_persona === 'moral' && (form.rfc.length !== 12 || !rm.test(form.rfc))) {
        form.setError('rfc', 'RFC debe tener 12 caracteres válidos')
        return
    }
    form.clearErrors('rfc')
}

const validateTelefono = () => {
    if (form.telefono && !/^\d{10}$/.test(form.telefono)) form.setError('telefono', 'Debe tener 10 dígitos')
    else form.clearErrors('telefono')
}

const validateCodigoPostal = async (event) => {
    const value = event.target.value.replace(/\D/g, '').slice(0, 5)
    form.codigo_postal = value
    if (value.length === 5) {
        try {
            const response = await fetch(`/api/cp/${value}`)
            if (response.ok) {
                const data = await response.json()
                form.estado = data.estado
                form.municipio = data.municipio
                form.pais = data.pais
                if (data.colonias && data.colonias.length === 1) form.colonia = data.colonias[0]
            }
        } catch (e) { console.warn('Error CP:', e) }
    }
}

const resetForm = () => {
    Object.keys(originalFormData).forEach(key => { form[key] = originalFormData[key] })
    form.clearErrors()
    isValidEmail.value = false
}

const submit = () => {
    validateRFC(); validateTelefono(); validateEmail()
    if (!isFormValid.value) return
    form.put(route('proveedores.update', props.proveedor.id), {
        preserveScroll: true,
        onSuccess: () => notyf.success('Proveedor actualizado'),
        onError: (err) => { errors.value = err; notyf.error('Error al actualizar') },
    })
}

const cancel = () => router.visit(route('proveedores.index'))

watch(() => form.email, () => { if (form.email) validateEmail(); else { isValidEmail.value = false; form.clearErrors('email') } })
watch(() => form.rfc, () => { if (form.rfc && form.tipo_persona) validateRFC() })
watch(() => form.telefono, () => { if (form.telefono) validateTelefono() })

if (form.email) validateEmail()
</script>

<template>
    <Head title="Editar Proveedor" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Editar Proveedor" subtitle="Modifica la información del proveedor">
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
                    <h3 class="text-rose-800 dark:text-rose-200 font-medium text-sm">Errores en el formulario</h3>
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
                                <select v-model="form.tipo_persona" @change="handleTipoPersonaChange"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    :class="form.errors.tipo_persona ? 'border-rose-300' : 'border-slate-300 dark:border-slate-600'">
                                    <option value="">Seleccione...</option>
                                    <option value="fisica">Persona Física</option>
                                    <option value="moral">Persona Moral</option>
                                </select>
                                <p v-if="form.errors.tipo_persona" class="mt-1 text-xs text-rose-500">{{ form.errors.tipo_persona }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">RFC <span class="text-rose-500">*</span>
                                    <span class="text-xs text-slate-500">({{ form.tipo_persona === 'fisica' ? '13' : form.tipo_persona === 'moral' ? '12' : '' }} caracteres)</span>
                                </label>
                                <input v-model="form.rfc" @input="handleRfcInput" type="text" :maxlength="form.tipo_persona === 'fisica' ? 13 : 12" :disabled="!form.tipo_persona" required
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all uppercase"
                                    :class="form.errors.rfc ? 'border-rose-300' : isRfcValid && form.rfc ? 'border-emerald-400' : 'border-slate-300 dark:border-slate-600'"
                                    :placeholder="form.tipo_persona === 'fisica' ? 'ABCD123456789' : form.tipo_persona === 'moral' ? 'ABC123456789' : 'Seleccione tipo' " />
                                <div class="mt-1 flex items-center justify-between">
                                    <p v-if="form.errors.rfc" class="text-xs text-rose-500">{{ form.errors.rfc }}</p>
                                    <span v-if="isRfcValid && form.rfc && !form.errors.rfc" class="text-xs text-emerald-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        RFC válido
                                    </span>
                                </div>
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
                                    <option value="">Seleccione un régimen...</option>
                                    <option v-for="regimen in regimenesFiscales" :key="regimen.codigo" :value="regimen.codigo">{{ regimen.codigo }} - {{ regimen.descripcion }}</option>
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
                                <FormField id="email" v-model="form.email" label="Email" type="email" placeholder="correo@ejemplo.com" :error="form.errors.email" required @input="validateEmail" />
                                <span v-if="isValidEmail && form.email && !form.errors.email" class="text-xs text-emerald-600 flex items-center mt-1">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    Email válido
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Teléfono <span class="text-rose-500">*</span> <span class="text-xs text-slate-500">(10 dígitos)</span></label>
                                <input v-model="form.telefono" @input="handleTelefonoInput" type="tel" maxlength="10" required
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    :class="form.errors.telefono ? 'border-rose-300' : isTelefonoValid && form.telefono ? 'border-emerald-400' : 'border-slate-300 dark:border-slate-600'"
                                    placeholder="5512345678" />
                                <div class="mt-1 flex items-center justify-between">
                                    <p v-if="form.errors.telefono" class="text-xs text-rose-500">{{ form.errors.telefono }}</p>
                                    <span class="text-xs text-slate-500">{{ form.telefono?.length || 0 }}/10 dígitos</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Dirección</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="lg:col-span-2">
                                <FormField id="calle" v-model="form.calle" label="Calle" placeholder="Nombre de la calle" :error="form.errors.calle" required @blur="convertirAMayusculas('calle')" />
                            </div>
                            <div>
                                <FormField id="numero_exterior" v-model="form.numero_exterior" label="Número Exterior" placeholder="123" required />
                            </div>
                            <div>
                                <FormField id="numero_interior" v-model="form.numero_interior" label="Número Interior" placeholder="A, 101, etc." />
                            </div>
                            <div>
                                <FormField id="colonia" v-model="form.colonia" label="Colonia" placeholder="Nombre de la colonia" required @blur="convertirAMayusculas('colonia')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Código Postal <span class="text-rose-500">*</span></label>
                                <input v-model="form.codigo_postal" @input="validateCodigoPostal" type="text" maxlength="5" required
                                    class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"
                                    placeholder="12345" />
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

                    <div class="bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl p-4">
                        <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">Información del registro</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <div>Creado: <span class="font-medium">{{ new Date(proveedor.created_at).toLocaleDateString('es-MX') }}</span></div>
                            <div>Actualizado: <span class="font-medium">{{ new Date(proveedor.updated_at).toLocaleDateString('es-MX') }}</span></div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div class="text-sm text-slate-500 dark:text-slate-400">Los campos marcados con * son obligatorios</div>
                        <div class="flex gap-3">
                            <button type="button" @click="cancel"
                                class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing || !isFormValid"
                                class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 rounded-xl transition-all duration-200 shadow-sm disabled:opacity-50 inline-flex items-center gap-2">
                                <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ form.processing ? 'Actualizando...' : 'Actualizar Proveedor' }}
                            </button>
                        </div>
                    </div>
                </form>
            </FormCard>
        </div>
    </div>
</template>
