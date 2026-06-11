<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faCamera, faFileExcel, faFilePdf, faCloudUploadAlt, faImages, faTimes, faArrowLeft } from '@fortawesome/free-solid-svg-icons'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

library.add(faCamera, faFileExcel, faFilePdf, faCloudUploadAlt, faImages, faTimes, faArrowLeft)

const notyf = new Notyf({ duration: 4000 })

const props = defineProps({
    contracts: Array,
    clientes: Array,
    empleados: Array
})

const showModal = ref(false)
const showEvidenceModal = ref(false)
const showDetailModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const selectedContract = ref(null)

const form = useForm({
    cliente_id: '',
    contract_number: '',
    service_object: '',
    start_date: '',
    end_date: '',
    amount: '',
    employee_ids: [],
    file: null
})

const evidenceForm = useForm({
    file: null,
    description: '',
    evidence_date: new Date().toISOString().split('T')[0]
})

const openEvidence = (contract) => {
    selectedContract.value = contract
    showEvidenceModal.value = true
}

const openDetail = (contract) => {
    selectedContract.value = contract
    showDetailModal.value = true
}

const editContract = (contract) => {
    isEditing.value = true
    editingId.value = contract.id
    form.cliente_id = contract.cliente_id
    form.contract_number = contract.contract_number
    form.service_object = contract.service_object
    form.start_date = contract.start_date
    form.end_date = contract.end_date
    form.employee_ids = contract.empleados.map(e => e.id)
    showModal.value = true
}

const openNewModal = () => {
    isEditing.value = false
    editingId.value = null
    form.reset()
    showModal.value = true
}

const submit = () => {
    if (isEditing.value) {
        form.patch(route('comisiones.repse.my_contracts.update', editingId.value), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
                notyf.success('Contrato actualizado')
            }
        })
    } else {
        form.post(route('comisiones.repse.my_contracts.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
                notyf.success('Contrato registrado')
            }
        })
    }
}

const submitEvidence = () => {
    evidenceForm.post(route('comisiones.repse.my_contracts.evidence.store', selectedContract.value.id), {
        onSuccess: () => {
            evidenceForm.reset('file', 'description')
            notyf.success('Evidencia guardada')
        }
    })
}
</script>

<template>
    <AppLayout title="Mis Contratos REPSE (Reporteo)">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('comisiones.repse')" class="p-2 bg-[var(--ui-surface-soft)] rounded-lg text-[var(--ui-text-soft)]">
                        <font-awesome-icon icon="arrow-left" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">Mis Contratos REPSE</h2>
                        <p class="text-sm text-[var(--ui-text-soft)] mt-1">Gestión de servicios especializados prestados a clientes.</p>
                    </div>
                </div>
                <button @click="openNewModal" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-xs shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all">
                    NUEVO CONTRATO
                </button>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 xl:px-12 w-full">
            <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] overflow-hidden shadow-xl">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-[var(--ui-surface)] border-b border-[var(--ui-border)]">
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Cliente / Contrato</th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Vigencia</th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">Personal</th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Reporteo / Archivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--ui-border)]/50">
                        <tr v-for="c in contracts" :key="c.id" class="hover:bg-indigo-500/5 transition-all">
                            <td class="px-8 py-6 cursor-pointer group" @click="openDetail(c)">
                                <p class="text-sm font-bold text-[var(--ui-text-main)] group-hover:text-indigo-600 transition-colors">{{ c.cliente.nombre_razon_social }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <p class="text-[10px] text-indigo-500 font-black uppercase tracking-widest">#{{ c.contract_number }}</p>
                                    <span class="text-[10px] px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full font-bold">REPSE</span>
                                </div>
                                <p class="text-[9px] text-slate-500 mt-2 font-medium italic line-clamp-2 max-w-xs group-hover:text-slate-700">{{ c.service_object }}</p>
                                <p class="text-[10px] font-bold text-indigo-500 mt-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                    <font-awesome-icon icon="mouse-pointer" class="text-[8px]" /> CLIC PARA VER DETALLES
                                </p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-[11px] font-bold text-[var(--ui-text-main)]">{{ c.start_date }}</p>
                                <p class="text-[9px] text-[var(--ui-text-soft)]">al {{ c.end_date || 'Indefinido' }}</p>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <div class="flex -space-x-2 justify-center mb-1">
                                    <div v-for="e in c.empleados.slice(0, 3)" :key="e.id" class="w-8 h-8 rounded-full bg-indigo-100 border-2 border-[var(--ui-surface)] flex items-center justify-center text-[10px] font-black text-indigo-600" :title="e.name">
                                        {{ e.name?.charAt(0) }}
                                    </div>
                                    <div v-if="c.empleados.length > 3" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-[var(--ui-surface)] flex items-center justify-center text-[10px] font-black text-slate-500">
                                        +{{ c.empleados.length - 3 }}
                                    </div>
                                </div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">{{ c.empleados.length }} asignados</p>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center gap-2">
                                        <button @click="openEvidence(c)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg font-bold text-[10px] hover:bg-indigo-200 transition-all">
                                            <font-awesome-icon icon="camera" /> EVIDENCIAS ({{ c.evidences?.length || 0 }})
                                        </button>
                                        <a :href="route('comisiones.repse.my_contracts.export', c.id)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-500 text-white rounded-lg font-bold text-[10px] hover:bg-emerald-600 transition-all">
                                            <font-awesome-icon icon="file-excel" /> EXPORTAR ICSOE
                                        </a>
                                    </div>
                                    <button @click="openDetail(c)" class="text-[10px] font-black text-indigo-500 flex items-center gap-1 hover:underline">
                                        <font-awesome-icon icon="eye" /> VER DETALLES
                                    </button>
                                    <button @click="editContract(c)" class="text-[10px] font-black text-slate-400 flex items-center gap-1 hover:text-indigo-600 transition-colors mt-1">
                                        <font-awesome-icon icon="edit" /> EDITAR CONTRATO
                                    </button>
                                    <a v-if="c.file_path" :href="route('comisiones.repse.my_contracts.file', c.id)" target="_blank" class="text-[10px] font-black text-emerald-600 flex items-center gap-1 hover:underline">
                                        <font-awesome-icon icon="file-pdf" /> CONTRATO PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="contracts.length === 0">
                            <td colspan="4" class="px-8 py-20 text-center opacity-40 italic">
                                No hay contratos registrados para reporteo.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Evidence Modal -->
        <div v-if="showEvidenceModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="bg-[var(--ui-surface)] w-full max-w-4xl rounded-[2.5rem] border border-[var(--ui-border)] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-8 border-b border-[var(--ui-border)] flex items-center justify-between bg-indigo-500/5">
                    <div>
                        <h3 class="text-xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">Evidencias Fotográficas</h3>
                        <p class="text-[10px] text-indigo-500 font-black uppercase tracking-widest mt-1">Contrato: #{{ selectedContract?.contract_number }}</p>
                    </div>
                    <button @click="showEvidenceModal = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-white/5 border border-[var(--ui-border)] text-[var(--ui-text-soft)] hover:text-rose-500 transition-all">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Form side -->
                    <div class="lg:col-span-1 space-y-6 border-r border-[var(--ui-border)] pr-8">
                        <p class="text-xs font-bold text-[var(--ui-text-main)] mb-4">Añadir Nueva Evidencia</p>
                        <form @submit.prevent="submitEvidence" class="space-y-4">
                            <div>
                                <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Fotografía del Servicio</label>
                                <div class="relative group">
                                    <div class="h-32 flex flex-col items-center justify-center bg-indigo-500/5 border-2 border-dashed border-indigo-200 rounded-2xl group-hover:bg-indigo-500/10 transition-all">
                                        <font-awesome-icon icon="cloud-upload-alt" class="text-indigo-400 mb-2" />
                                        <input type="file" @input="evidenceForm.file = $event.target.files[0]" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" />
                                        <p class="text-[9px] text-indigo-500 font-bold uppercase">{{ evidenceForm.file ? 'Imagen lista' : 'Seleccionar' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Descripción</label>
                                <textarea v-model="evidenceForm.description" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-2 text-xs h-20" placeholder="¿Qué se observa en la foto?"></textarea>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Fecha</label>
                                <input v-model="evidenceForm.evidence_date" type="date" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-2 text-xs" />
                            </div>
                            <button type="submit" :disabled="evidenceForm.processing || !evidenceForm.file" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 disabled:opacity-50 transition-all">
                                GUARDAR EVIDENCIA
                            </button>
                        </form>
                    </div>

                    <!-- Gallery side -->
                    <div class="lg:col-span-2">
                        <p class="text-xs font-bold text-[var(--ui-text-main)] mb-6">Galería de Cumplimiento</p>
                        <div v-if="selectedContract?.evidences?.length > 0" class="grid grid-cols-2 gap-4">
                            <div v-for="ev in selectedContract.evidences" :key="ev.id" class="group relative aspect-square rounded-2xl overflow-hidden border border-[var(--ui-border)] bg-slate-100">
                                <img :src="'/storage/' + ev.file_path" class="w-full h-full object-cover transition-transform group-hover:scale-110" />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent p-4 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-all">
                                    <p class="text-[10px] text-white font-bold">{{ ev.evidence_date }}</p>
                                    <p class="text-[9px] text-slate-200 line-clamp-1">{{ ev.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center h-64 opacity-30 italic text-sm">
                            <font-awesome-icon icon="images" class="text-4xl mb-4" />
                            Aún no hay evidencias para este contrato.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-[var(--ui-surface)] w-full max-w-2xl rounded-[2.5rem] border border-[var(--ui-border)] shadow-2xl p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <h3 class="text-xl font-black text-[var(--ui-text-main)] uppercase tracking-tight mb-8">
                    {{ isEditing ? 'Editar Contrato de Servicio' : 'Registrar Contrato de Servicio' }}
                </h3>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Cliente</label>
                            <select v-model="form.cliente_id" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">Selecciona cliente...</option>
                                <option v-for="cl in clientes" :key="cl.id" :value="cl.id">{{ cl.nombre_razon_social }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Número de Contrato</label>
                            <input v-model="form.contract_number" type="text" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm" placeholder="Ej. CONT-2026-001" />
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Objeto del Servicio Especializado</label>
                        <textarea v-model="form.service_object" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm h-24" placeholder="Describe la actividad según tu registro REPSE..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Fecha Inicio</label>
                            <input v-model="form.start_date" type="date" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1.5 block">Fecha Fin (Estimada)</label>
                            <input v-model="form.end_date" type="date" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-3 block">Personal Asignado</label>
                            <div class="h-40 overflow-y-auto p-4 bg-[var(--ui-surface-soft)] rounded-2xl border border-[var(--ui-border)]">
                                <label v-for="e in empleados" :key="e.id" class="flex items-center gap-3 p-2 hover:bg-white/5 rounded-lg cursor-pointer transition-colors">
                                    <input type="checkbox" :value="e.id" v-model="form.employee_ids" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-xs font-bold text-[var(--ui-text-main)]">{{ e.name }}</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-3 block">Contrato Firmado (PDF)</label>
                            <div class="h-40 flex flex-col items-center justify-center p-4 bg-indigo-500/5 rounded-2xl border border-dashed border-indigo-200">
                                <font-awesome-icon icon="cloud-upload-alt" class="text-2xl text-indigo-400 mb-2" />
                                <input type="file" @input="form.file = $event.target.files[0]" class="text-[10px] text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <p class="text-[9px] text-slate-400 mt-2">Máx 10MB (PDF)</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex gap-4">
                        <button type="button" @click="showModal = false" class="flex-1 py-4 border border-[var(--ui-border)] rounded-2xl font-black text-xs hover:bg-slate-50 transition-all">CANCELAR</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/20 disabled:opacity-50">
                            {{ isEditing ? 'GUARDAR CAMBIOS' : 'REGISTRAR CONTRATO' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Detail Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="bg-[var(--ui-surface)] w-full max-w-2xl rounded-[2.5rem] border border-[var(--ui-border)] shadow-2xl overflow-hidden p-10">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">Detalles del Contrato</h3>
                        <p class="text-xs text-indigo-500 font-black uppercase tracking-widest mt-1">#{{ selectedContract?.contract_number }}</p>
                    </div>
                    <button @click="showDetailModal = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 dark:bg-white/5 border border-[var(--ui-border)] text-[var(--ui-text-soft)] hover:text-rose-500 transition-all">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <div class="space-y-8">
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cliente</p>
                            <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ selectedContract?.cliente?.nombre_razon_social }}</p>
                            <p class="text-[10px] text-slate-500 font-mono mt-0.5">{{ selectedContract?.cliente?.rfc }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Vigencia</p>
                            <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ selectedContract?.start_date }} - {{ selectedContract?.end_date || 'Indefinido' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Objeto del Servicio Especializado</p>
                        <div class="p-6 bg-slate-50 dark:bg-white/5 rounded-3xl border border-[var(--ui-border)] italic text-xs leading-relaxed text-[var(--ui-text-soft)]">
                            "{{ selectedContract?.service_object }}"
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Personal Técnico Asignado ({{ selectedContract?.empleados?.length }})</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="e in selectedContract?.empleados" :key="e.id" class="p-3 bg-white dark:bg-white/5 rounded-xl border border-[var(--ui-border)] flex items-center gap-3">
                                <div class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500 font-black text-[10px]">
                                    {{ e.name?.charAt(0) }}
                                </div>
                                <p class="text-[11px] font-bold text-[var(--ui-text-main)]">{{ e.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Documentación del Contrato</p>
                        <div v-if="selectedContract?.file_path" class="p-4 bg-emerald-500/5 border border-emerald-500/20 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                                    <font-awesome-icon icon="file-pdf" />
                                </div>
                                <div>
                                    <p class="text-[11px] font-black text-emerald-700 uppercase">Contrato Digitalizado</p>
                                    <p class="text-[9px] text-emerald-600/70 font-bold">Archivo PDF verificado</p>
                                </div>
                            </div>
                            <a :href="route('comisiones.repse.my_contracts.file', selectedContract.id)" target="_blank" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/10">
                                VER ARCHIVO
                            </a>
                        </div>
                        <div v-else class="p-4 bg-amber-500/5 border border-amber-500/20 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                                    <font-awesome-icon icon="exclamation-triangle" />
                                </div>
                                <div>
                                    <p class="text-[11px] font-black text-amber-700 uppercase">Sin Documento</p>
                                    <p class="text-[9px] text-amber-600/70 font-bold italic">No se ha subido el archivo del contrato</p>
                                </div>
                            </div>
                            <button @click="editContract(selectedContract); showDetailModal = false" class="px-4 py-2 bg-amber-600 text-white rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-amber-700 transition-all">
                                SUBIR AHORA
                            </button>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-[var(--ui-border)] flex justify-end">
                        <button @click="showDetailModal = false" class="px-8 py-3 bg-slate-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest">CERRAR DETALLES</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
