<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faTrash, faCloudUploadAlt, faFilePdf, faCheckCircle, faExclamationCircle, faBell } from '@fortawesome/free-solid-svg-icons'
import { library } from '@fortawesome/fontawesome-svg-core'
import { notyf } from '@/Utils/notyf.js'

library.add(faPlus, faTrash, faCloudUploadAlt, faFilePdf, faCheckCircle, faExclamationCircle, faBell)

const props = defineProps({
    form: Object
})

// Initialize registro_patronal_imss if it's not an array
if (!Array.isArray(props.form.registro_patronal_imss)) {
    props.form.registro_patronal_imss = props.form.registro_patronal_imss 
        ? [{ nrp: props.form.registro_patronal_imss, description: 'Principal' }]
        : [{ nrp: '', description: '' }]
}

const addRegistro = () => {
    props.form.registro_patronal_imss.push({ nrp: '', description: '' })
}

const removeRegistro = (index) => {
    if (props.form.registro_patronal_imss.length > 1) {
        props.form.registro_patronal_imss.splice(index, 1)
    } else {
        props.form.registro_patronal_imss[0] = { nrp: '', description: '' }
    }
}

const uploading = ref({ repse: false, acta: false, curp: false, csf: false })

const uploadFile = (event, type) => {
    const file = event.target.files[0]
    if (!file) return

    uploading.value[type] = true
    const docForm = useForm({
        type: type,
        file: file
    })

    docForm.post(route('empresa-configuracion.legal-doc.upload'), {
        onSuccess: () => {
            notyf.success('Documento actualizado')
            uploading.value[type] = false
        },
        onError: () => {
            notyf.error('Error al subir documento')
            uploading.value[type] = false
        }
    })
}
</script>

<template>
    <div class="space-y-10">
        <!-- REPSE Info -->
        <section>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-600">
                    <FontAwesomeIcon icon="shield-alt" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Registro REPSE Propio</h3>
                    <p class="text-xs text-slate-500">Datos de tu registro ante la STPS</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Número de Registro (Folio)</label>
                    <input v-model="form.repse_number" type="text" class="w-full bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20" placeholder="Ej. AR12345/2021" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Vencimiento</label>
                    <input v-model="form.repse_expiry" type="date" class="w-full bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20" />
                </div>
                <div class="md:col-span-2 space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Actividad Especializada Autorizada</label>
                    <textarea v-model="form.repse_activity" class="w-full bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm h-24 focus:ring-2 focus:ring-indigo-500/20" placeholder="Describe la actividad como aparece en tu constancia..."></textarea>
                </div>
            </div>
        </section>

        <!-- Responsable Legal NOM-035 -->
        <section>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-600">
                    <FontAwesomeIcon icon="pen-nib" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Responsable Legal de la NOM-035</h3>
                    <p class="text-xs text-slate-500">Persona que firma y valida los procesos de cumplimiento</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-amber-500/5 rounded-[2rem] border border-amber-500/10">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre del Responsable</label>
                    <input v-model="form.responsible_name" type="text" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500/20" placeholder="Nombre completo" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Cargo / Puesto</label>
                    <input v-model="form.responsible_position" type="text" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500/20" placeholder="Ej. Representante Legal" />
                </div>
                <div class="md:col-span-2 flex items-start gap-2 mt-2">
                    <FontAwesomeIcon icon="exclamation-circle" class="text-amber-500 mt-0.5 text-[10px]" />
                    <p class="text-[10px] text-slate-500 font-medium italic">
                        * Este nombre aparecerá en los formatos oficiales de canalización médica y constancias legales.
                    </p>
                </div>
            </div>
        </section>

        <!-- Preferencias y Alertas -->
        <section>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center text-rose-600">
                    <font-awesome-icon icon="bell" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Preferencias y Alertas</h3>
                    <p class="text-xs text-slate-500">Configura cómo quieres que el sistema te cuide</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-rose-500/5 rounded-[2rem] border border-rose-500/10">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Días de Anticipación para Alertas</label>
                    <div class="flex items-center gap-3">
                        <input v-model="form.repse_alert_days" type="number" class="w-24 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-500/20" />
                        <span class="text-xs font-bold text-slate-500">Días antes del vencimiento</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo para Notificaciones de Auditoría</label>
                    <input v-model="form.audit_contact_email" type="email" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-500/20" placeholder="auditoria@empresa.com" />
                </div>
                <div class="md:col-span-2 flex items-start gap-2 mt-2">
                    <font-awesome-icon icon="info-circle" class="text-rose-500 mt-0.5 text-[10px]" />
                    <p class="text-[10px] text-slate-500 font-medium italic">
                        * El sistema enviará un resumen de cumplimiento semanal y alertas críticas a este correo.
                    </p>
                </div>
            </div>
        </section>

        <!-- Registros Patronales -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-600">
                        <FontAwesomeIcon icon="building" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Registros Patronales IMSS</h3>
                        <p class="text-xs text-slate-500">Agrega todos tus números de registro patronal</p>
                    </div>
                </div>
                <button @click="addRegistro" type="button" class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-xs font-bold hover:bg-emerald-600 transition-all">
                    <FontAwesomeIcon icon="plus" class="mr-2" /> AGREGAR NRP
                </button>
            </div>

            <div class="space-y-3">
                <div v-for="(reg, index) in form.registro_patronal_imss" :key="index" class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-700 group transition-all">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input v-model="reg.nrp" type="text" class="w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm" placeholder="Número de Registro Patronal" />
                        <input v-model="reg.description" type="text" class="w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm" placeholder="Descripción (Ej. Matriz, Sucursal 1)" />
                    </div>
                    <button @click="removeRegistro(index)" type="button" class="p-2 text-slate-300 hover:text-rose-500 transition-all opacity-0 group-hover:opacity-100">
                        <FontAwesomeIcon icon="trash" />
                    </button>
                </div>
            </div>
        </section>

        <!-- Legal Documents -->
        <section>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-600">
                    <FontAwesomeIcon icon="file-pdf" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Documentación Legal</h3>
                    <p class="text-xs text-slate-500">Archivos para soporte de auditoría</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Constancia REPSE -->
                <div class="p-6 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm transition-all hover:shadow-md">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Constancia de Registro (PDF)</p>
                    <div class="flex items-center justify-between">
                        <a v-if="form.repse_constancia_path" :href="'/storage/' + form.repse_constancia_path" target="_blank" class="flex items-center gap-3 group">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-all">
                                <FontAwesomeIcon icon="check-circle" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-indigo-600 transition-all">
                                    {{ form.repse_constancia_name || 'Documento Actualizado' }}
                                </span>
                                <span class="text-[9px] text-slate-400 uppercase font-black">Clic para visualizar →</span>
                            </div>
                        </a>
                        <div v-else class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="exclamation-circle" />
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Pendiente</span>
                        </div>

                        <label class="cursor-pointer px-4 py-2 bg-indigo-600 text-white rounded-lg text-[10px] font-black hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                            <FontAwesomeIcon :icon="uploading.repse ? 'spinner' : 'cloud-upload-alt'" :spin="uploading.repse" class="mr-2" />
                            {{ form.repse_constancia_path ? 'REEMPLAZAR' : 'SUBIR' }}
                            <input type="file" class="hidden" accept="application/pdf" @change="uploadFile($event, 'repse')" />
                        </label>
                    </div>
                </div>

                <!-- Documento de Identidad (Acta o CSF/CURP) -->
                <div v-if="form.rfc?.length !== 13" class="p-6 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm transition-all hover:shadow-md">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Acta Constitutiva (PDF)</p>
                    <div class="flex items-center justify-between">
                        <a v-if="form.acta_constitutiva_path" :href="'/storage/' + form.acta_constitutiva_path" target="_blank" class="flex items-center gap-3 group">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-all">
                                <FontAwesomeIcon icon="check-circle" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-indigo-600 transition-all">
                                    {{ form.acta_constitutiva_name || 'Documento Actualizado' }}
                                </span>
                                <span class="text-[9px] text-slate-400 uppercase font-black">Clic para visualizar →</span>
                            </div>
                        </a>
                        <div v-else class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="exclamation-circle" />
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Pendiente</span>
                        </div>

                        <label class="cursor-pointer px-4 py-2 bg-indigo-600 text-white rounded-lg text-[10px] font-black hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                            <FontAwesomeIcon :icon="uploading.acta ? 'spinner' : 'cloud-upload-alt'" :spin="uploading.acta" class="mr-2" />
                            {{ form.acta_constitutiva_path ? 'REEMPLAZAR' : 'SUBIR' }}
                            <input type="file" class="hidden" accept="application/pdf" @change="uploadFile($event, 'acta')" />
                        </label>
                    </div>
                </div>

                <!-- Persona Física Specific Docs -->
                <template v-else>
                    <!-- CSF -->
                    <div class="p-6 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm transition-all hover:shadow-md">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Constancia de Situación Fiscal (PDF)</p>
                        <div class="flex items-center justify-between">
                            <a v-if="form.csf_pdf_path" :href="'/storage/' + form.csf_pdf_path" target="_blank" class="flex items-center gap-3 group">
                                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-all">
                                    <FontAwesomeIcon icon="check-circle" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-indigo-600 transition-all">
                                        {{ form.csf_pdf_name || 'CSF Actualizada' }}
                                    </span>
                                    <span class="text-[9px] text-slate-400 uppercase font-black">Clic para visualizar →</span>
                                </div>
                            </a>
                            <div v-else class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                                    <FontAwesomeIcon icon="exclamation-circle" />
                                </div>
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Pendiente</span>
                            </div>

                            <label class="cursor-pointer px-4 py-2 bg-indigo-600 text-white rounded-lg text-[10px] font-black hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                                <FontAwesomeIcon :icon="uploading.csf ? 'spinner' : 'cloud-upload-alt'" :spin="uploading.csf" class="mr-2" />
                                {{ form.csf_pdf_path ? 'REEMPLAZAR' : 'SUBIR' }}
                                <input type="file" class="hidden" accept="application/pdf" @change="uploadFile($event, 'csf')" />
                            </label>
                        </div>
                    </div>

                    <!-- CURP -->
                    <div class="p-6 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm transition-all hover:shadow-md">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">CURP (PDF)</p>
                        <div class="flex items-center justify-between">
                            <a v-if="form.curp_pdf_path" :href="'/storage/' + form.curp_pdf_path" target="_blank" class="flex items-center gap-3 group">
                                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-all">
                                    <FontAwesomeIcon icon="check-circle" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-indigo-600 transition-all">
                                        {{ form.curp_pdf_name || 'CURP Actualizada' }}
                                    </span>
                                    <span class="text-[9px] text-slate-400 uppercase font-black">Clic para visualizar →</span>
                                </div>
                            </a>
                            <div v-else class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                                    <FontAwesomeIcon icon="exclamation-circle" />
                                </div>
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Pendiente</span>
                            </div>

                            <label class="cursor-pointer px-4 py-2 bg-indigo-600 text-white rounded-lg text-[10px] font-black hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                                <FontAwesomeIcon :icon="uploading.curp ? 'spinner' : 'cloud-upload-alt'" :spin="uploading.curp" class="mr-2" />
                                {{ form.curp_pdf_path ? 'REEMPLAZAR' : 'SUBIR' }}
                                <input type="file" class="hidden" accept="application/pdf" @change="uploadFile($event, 'curp')" />
                            </label>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>
</template>
