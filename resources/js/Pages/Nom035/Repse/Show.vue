<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faArrowLeft, faUpload, faCheckCircle, faTimesCircle, faExternalLinkAlt, faFilePdf } from '@fortawesome/free-solid-svg-icons'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

library.add(faArrowLeft, faUpload, faCheckCircle, faTimesCircle, faExternalLinkAlt, faFilePdf)

const notyf = new Notyf({ duration: 4000 })

const props = defineProps({
    contratista: Object,
    docTypes: Object
})

const showUploadModal = ref(false)
const selectedType = ref('')
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const fileInput = ref(null)
const uploading = ref(false)

const submitDoc = () => {
    if (!selectedType.value || !fileInput.value?.files[0]) {
        notyf.error('Selecciona el tipo de documento y el archivo')
        return
    }

    uploading.value = true
    const formData = new FormData()
    formData.append('type', selectedType.value)
    formData.append('month', selectedMonth.value)
    formData.append('year', selectedYear.value)
    formData.append('file', fileInput.value.files[0])

    router.post(route('comisiones.repse.doc.store', props.contratista.id), formData, {
        onSuccess: () => {
            uploading.value = false
            showUploadModal.value = false
            notyf.success('Documento cargado correctamente')
        },
        onError: () => uploading.value = false
    })
}

const updateStatus = (docId, status) => {
    router.post(route('comisiones.repse.doc.status', docId), { status }, {
        onSuccess: () => notyf.success('Estado actualizado')
    })
}

const getStatusBadge = (status) => {
    switch(status) {
        case 'validated': return 'bg-emerald-500 text-white'
        case 'rejected': return 'bg-rose-500 text-white'
        default: return 'bg-amber-500 text-white'
    }
}
</script>

<template>
    <AppLayout :title="'Expediente: ' + contratista.nombre_razon_social">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('comisiones.repse')" class="p-2 bg-[var(--ui-surface-soft)] rounded-lg text-[var(--ui-text-soft)] hover:text-indigo-500 transition-colors">
                    <font-awesome-icon icon="arrow-left" />
                </Link>
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">
                        Expediente Digital
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">{{ contratista.nombre_razon_social }} ({{ contratista.rfc }})</p>
                </div>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 xl:px-12 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-3xl p-8">
                        <div class="flex flex-col items-center text-center mb-8">
                            <div class="h-20 w-20 bg-indigo-500/10 rounded-[2rem] flex items-center justify-center text-indigo-500 text-3xl font-black border border-indigo-500/20 mb-4">
                                {{ contratista.nombre_razon_social.charAt(0) }}
                            </div>
                            <h3 class="font-black text-lg text-[var(--ui-text-main)] leading-tight">{{ contratista.nombre_razon_social }}</h3>
                            <span class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-2 px-3 py-1 bg-indigo-500/10 rounded-full border border-indigo-500/20">REPSE ACTIVO</span>
                        </div>

                        <div class="space-y-4 pt-6 border-t border-[var(--ui-border)]">
                            <div>
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Número REPSE</p>
                                <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ contratista.repse_number || 'No registrado' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Vigencia</p>
                                <p class="text-sm font-bold text-[var(--ui-text-main)]" :class="{'text-rose-500': new Date(contratista.repse_expiry) < new Date()}">
                                    {{ contratista.repse_expiry ? new Date(contratista.repse_expiry).toLocaleDateString() : 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Actividad</p>
                                <p class="text-xs text-[var(--ui-text-soft)] leading-relaxed italic">{{ contratista.repse_activity || 'Sin descripción' }}</p>
                            </div>
                        </div>
                    </div>

                    <button @click="showUploadModal = true" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
                        <font-awesome-icon icon="upload" />
                        SUBIR DOCUMENTO
                    </button>

                    <a :href="route('comisiones.repse.dossier', contratista.id)" target="_blank" class="w-full py-4 bg-[var(--ui-surface)] border-2 border-indigo-500 text-indigo-500 rounded-2xl font-black text-sm hover:bg-indigo-50 transition-all flex items-center justify-center gap-3">
                        <font-awesome-icon icon="file-pdf" />
                        DESCARGAR DOSSIER PDF
                    </a>
                </div>

                <!-- History Table -->
                <div class="lg:col-span-2">
                    <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] overflow-hidden">
                        <div class="p-8 border-b border-[var(--ui-border)] bg-[var(--ui-surface)] flex items-center justify-between">
                            <h3 class="font-black text-sm uppercase tracking-widest">Historial de Documentos</h3>
                        </div>

                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-[var(--ui-surface)] border-b border-[var(--ui-border)]">
                                    <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Documento</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">Periodo</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">Estado</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--ui-border)]/50">
                                <tr v-for="doc in contratista.repse_docs" :key="doc.id" class="hover:bg-indigo-500/5 transition-all">
                                    <td class="px-8 py-5">
                                        <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ docTypes[doc.type] || doc.type }}</p>
                                        <p class="text-[10px] text-[var(--ui-text-soft)] mt-0.5 uppercase tracking-tighter">Subido el {{ new Date(doc.created_at).toLocaleDateString() }}</p>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <span class="text-xs font-black uppercase text-indigo-500">{{ doc.month }}/{{ doc.year }}</span>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <span :class="['px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest', getStatusBadge(doc.status)]">
                                            {{ doc.status }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right flex items-center justify-end gap-2">
                                        <button v-if="doc.status === 'pending'" @click="updateStatus(doc.id, 'validated')" class="p-2 text-emerald-500 hover:bg-emerald-500/10 rounded-lg transition-colors" title="Validar">
                                            <font-awesome-icon icon="check-circle" />
                                        </button>
                                        <button v-if="doc.status === 'pending'" @click="updateStatus(doc.id, 'rejected')" class="p-2 text-rose-500 hover:bg-rose-500/10 rounded-lg transition-colors" title="Rechazar">
                                            <font-awesome-icon icon="times-circle" />
                                        </button>
                                        <a :href="'/storage/' + doc.file_path" target="_blank" class="p-2 text-indigo-500 hover:bg-indigo-500/10 rounded-lg transition-colors" title="Ver PDF">
                                            <font-awesome-icon icon="external-link-alt" />
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="contratista.repse_docs.length === 0">
                                    <td colspan="4" class="px-8 py-20 text-center opacity-40 italic text-sm">
                                        No se han cargado documentos para este contratista.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-[var(--ui-surface)] w-full max-w-md rounded-[2.5rem] border border-[var(--ui-border)] shadow-2xl overflow-hidden p-8">
                <h3 class="text-xl font-black text-[var(--ui-text-main)] uppercase tracking-tight mb-6">Subir Documento</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Tipo de Documento</label>
                        <select v-model="selectedType" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            <option value="" disabled>Selecciona...</option>
                            <option v-for="(name, val) in docTypes" :key="val" :value="val">{{ name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Mes</label>
                            <select v-model="selectedMonth" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                <option v-for="n in 12" :key="n" :value="n">{{ n }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Año</label>
                            <input v-model="selectedYear" type="number" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all" />
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Archivo (PDF)</label>
                        <input type="file" ref="fileInput" accept=".pdf,.jpg,.png" class="w-full text-xs text-[var(--ui-text-soft)] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-indigo-500 file:text-white hover:file:bg-indigo-600 transition-all" />
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button @click="showUploadModal = false" class="flex-1 py-3 border border-[var(--ui-border)] rounded-xl font-bold text-xs hover:bg-[var(--ui-surface-soft)] transition-all">CANCELAR</button>
                    <button @click="submitDoc" :disabled="uploading" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-black text-xs hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50">
                        {{ uploading ? 'SUBIENDO...' : 'SUBIR ARCHIVO' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
