<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faShieldHalved, faTruckLoading, faFileSignature, faChartBar, faCheckDouble, faCalendarCheck, faEye, faCircleCheck, faCircleExclamation, faTimes, faShieldAlt, faTriangleExclamation, faFileCsv, faFileExcel, faInfoCircle, faFileContract, faExclamationTriangle, faFilePdf, faHeartPulse } from '@fortawesome/free-solid-svg-icons'
import { notyf } from '@/Utils/notyf.js'

library.add(faShieldHalved, faTruckLoading, faFileSignature, faChartBar, faCheckDouble, faCalendarCheck, faEye, faCircleCheck, faCircleExclamation, faTimes, faShieldAlt, faTriangleExclamation, faFileCsv, faFileExcel, faInfoCircle, faFileContract, faExclamationTriangle, faFilePdf, faHeartPulse)

const props = defineProps({
    stats: Object,
    miEmpresa: Object,
    contratistas: Array,
    misContratosCount: Number,
    months: Array
})

const validateSat = (id) => {
    useForm({}).post(route('comisiones.repse.validate_sat', id), {
        preserveScroll: true,
        onSuccess: () => notyf.success('Validación SAT completada')
    })
}

const activeTab = ref('audit') // audit, suppliers, my-contracts, reporting

const steps = [
    { id: 'audit', name: 'Blindaje Propio', icon: 'shield-halved', color: 'text-indigo-500' },
    { id: 'suppliers', name: 'Vigilancia Proveedores', icon: 'truck-loading', color: 'text-emerald-500' },
    { id: 'my-contracts', name: 'Mis Contratos', icon: 'file-signature', color: 'text-blue-500' },
    { id: 'reporting', name: 'Reporteo Fiscal', icon: 'chart-bar', color: 'text-rose-500' },
]

const getStatusColor = (status) => {
    switch(status) {
        case 'validated': return 'text-emerald-500 bg-emerald-500/10'
        case 'pending': return 'text-amber-500 bg-amber-500/10'
        case 'missing': return 'text-slate-400 bg-slate-400/5'
        default: return 'text-slate-400'
    }
}

const showModalDetail = ref(false)
const selectedDetail = ref(null)

const details = {
    repse: { title: 'Constancia REPSE', field: 'repse_number', path: 'repse_constancia_path', label: 'Folio de Registro' },
    firma: { title: 'Firma Legal', field: 'firma_digital', path: 'firma_digital', label: 'Estado de Firma' },
    acta: { title: 'Identidad / Acta', field: 'acta_constitutiva_path', path: 'acta_constitutiva_path', label: 'Archivo Legal' },
    curp: { title: 'CURP', field: 'curp_pdf_path', path: 'curp_pdf_path', label: 'Archivo CURP' },
    csf: { title: 'Constancia Fiscal (CSF)', field: 'csf_pdf_path', path: 'csf_pdf_path', label: 'Archivo CSF' },
    imss: { title: 'Registro Patronal', field: 'registro_patronal_imss', path: null, label: 'Número de Registro (NRP)' },
}

const showDetail = (type) => {
    selectedDetail.value = { ...details[type], type }
    showModalDetail.value = true
}

const currentMonth = new Date().getMonth() + 1

const isReportingMonth = (month) => currentMonth === month

const isQuarterEnd = (month) => [4, 8, 12].includes(month) && currentMonth === month

const pendingReportingCount = computed(() => {
    const now = new Date()
    const currentM = now.getMonth() + 1
    const reportingMonths = [1, 3, 5, 7, 9, 11]
    return reportingMonths.includes(currentM) ? props.misContratosCount : 0
})

const getHealthColor = (score) => {
    if (score >= 90) return 'text-emerald-500 bg-emerald-500/10'
    if (score >= 70) return 'text-amber-500 bg-amber-500/10'
    return 'text-rose-500 bg-rose-500/10'
}
</script>

<template>
    <AppLayout title="Wizard de Blindaje REPSE">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">
                        Asistente de Blindaje REPSE
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Cumplimiento 360° en subcontratación especializada.</p>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Estado: Auditoría Lista</span>
                </div>
            </div>
        </template>

        <div class="py-12 px-6 max-w-[1600px] mx-auto">
            <!-- Navigation Wizard -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                <button 
                    v-for="step in steps" 
                    :key="step.id"
                    @click="activeTab = step.id"
                    :class="[
                        'p-6 rounded-[2rem] border-2 transition-all duration-500 text-left group relative overflow-hidden',
                        activeTab === step.id 
                            ? 'bg-[var(--ui-surface-soft)] border-indigo-500 shadow-xl shadow-indigo-500/10' 
                            : 'bg-[var(--ui-surface)] border-[var(--ui-border)] hover:border-indigo-500/30'
                    ]"
                >
                    <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110', step.color, activeTab === step.id ? 'bg-current/10' : 'bg-slate-100 dark:bg-white/5']">
                        <font-awesome-icon :icon="step.icon" class="text-xl" />
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[var(--ui-text-main)]">{{ step.name }}</h3>
                    <p class="text-[10px] text-[var(--ui-text-soft)] mt-1 font-bold">Verificar estado</p>
                    
                    <div v-if="activeTab === step.id" class="absolute bottom-0 left-0 w-full h-1 bg-indigo-500"></div>
                </button>
            </div>

            <!-- Tab Content: Audit (Blindaje Propio) -->
            <div v-if="activeTab === 'audit'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] p-10">
                        <h3 class="text-xl font-black text-[var(--ui-text-main)] uppercase tracking-tight mb-8">Estatus de Mi Registro</h3>
                        
                        <div class="space-y-6">
                            <div class="flex items-start gap-4 p-6 bg-white dark:bg-white/5 rounded-3xl border border-[var(--ui-border)]">
                                <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-500">
                                    <font-awesome-icon icon="check-double" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Registro Vigente</p>
                                    <p class="text-sm font-bold text-[var(--ui-text-main)]">Tu empresa tiene los campos REPSE configurados.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 p-6 bg-white dark:bg-white/5 rounded-3xl border border-[var(--ui-border)]">
                                <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500">
                                    <font-awesome-icon icon="calendar-check" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Próxima Renovación</p>
                                    <p class="text-sm font-bold text-[var(--ui-text-main)]">Estimada para 2027 (basada en ciclo de 3 años).</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-10">
                            <Link :href="route('nom035.config.index')" class="text-xs font-black text-indigo-500 uppercase tracking-widest hover:underline">
                                ACTUALIZAR DATOS LEGALES →
                            </Link>
                        </div>
                    </div>

                    <div class="bg-indigo-600 rounded-[2.5rem] p-10 text-white relative overflow-hidden shadow-2xl">
                        <font-awesome-icon icon="shield-halved" class="absolute -right-10 -bottom-10 text-[15rem] opacity-10" />
                        <h3 class="text-xl font-black uppercase tracking-tight mb-6">Checklist de Auditoría</h3>
                        <ul class="space-y-4">
                            <li class="group cursor-pointer" @click="showDetail('repse')">
                                <div class="flex items-center gap-3 text-sm font-bold transition-all group-hover:translate-x-2">
                                    <font-awesome-icon :icon="miEmpresa?.repse_number ? 'circle-check' : 'circle-exclamation'" :class="miEmpresa?.repse_number ? 'text-emerald-400' : 'text-amber-300'" /> 
                                    Constancia REPSE en PDF
                                    <font-awesome-icon icon="eye" class="ml-auto opacity-0 group-hover:opacity-100 transition-all text-[10px]" />
                                </div>
                                <p v-if="miEmpresa?.repse_number" class="text-[9px] opacity-60 ml-7 mt-0.5">REGISTRO: {{ miEmpresa.repse_number }}</p>
                            </li>
                            
                            <li class="group cursor-pointer" @click="showDetail('firma')">
                                <div class="flex items-center gap-3 text-sm font-bold transition-all group-hover:translate-x-2">
                                    <font-awesome-icon :icon="miEmpresa?.firma_digital ? 'circle-check' : 'circle-exclamation'" :class="miEmpresa?.firma_digital ? 'text-emerald-400' : 'text-amber-300'" /> 
                                    Firma del Responsable Legal
                                    <font-awesome-icon icon="eye" class="ml-auto opacity-0 group-hover:opacity-100 transition-all text-[10px]" />
                                </div>
                                <p v-if="miEmpresa?.firma_digital" class="text-[9px] opacity-60 ml-7 mt-0.5">CARGADA CORRECTAMENTE</p>
                            </li>

                            <li class="group cursor-pointer" @click="showDetail('acta')">
                                <div class="flex items-center gap-3 text-sm font-bold transition-all group-hover:translate-x-2">
                                    <font-awesome-icon :icon="miEmpresa?.acta_constitutiva_path || (miEmpresa?.curp_pdf_path && miEmpresa?.csf_pdf_path) ? 'circle-check' : 'circle-exclamation'" :class="miEmpresa?.acta_constitutiva_path || (miEmpresa?.curp_pdf_path && miEmpresa?.csf_pdf_path) ? 'text-emerald-400' : 'text-amber-300'" /> 
                                    {{ miEmpresa?.rfc?.length === 13 ? 'Documentación Personal (CSF / CURP)' : 'Acta Constitutiva con Objeto' }}
                                    <font-awesome-icon icon="eye" class="ml-auto opacity-0 group-hover:opacity-100 transition-all text-[10px]" />
                                </div>
                                <p v-if="miEmpresa?.acta_constitutiva_path || (miEmpresa?.curp_pdf_path && miEmpresa?.csf_pdf_path)" class="text-[9px] opacity-60 ml-7 mt-0.5">IDENTIDAD VERIFICADA</p>
                                <p v-else-if="miEmpresa?.rfc?.length === 13" class="text-[9px] text-amber-500 ml-7 mt-0.5">FALTA {{ !miEmpresa?.csf_pdf_path ? 'CSF' : 'CURP' }}</p>
                            </li>

                            <li class="group cursor-pointer" @click="showDetail('imss')">
                                <div class="flex items-center gap-3 text-sm font-bold transition-all group-hover:translate-x-2">
                                    <font-awesome-icon :icon="miEmpresa?.registro_patronal_imss?.length > 0 ? 'circle-check' : 'circle-exclamation'" :class="miEmpresa?.registro_patronal_imss?.length > 0 ? 'text-emerald-400' : 'text-amber-300'" /> 
                                    Registro Patronal IMSS
                                    <font-awesome-icon icon="eye" class="ml-auto opacity-0 group-hover:opacity-100 transition-all text-[10px]" />
                                </div>
                                <div v-if="miEmpresa?.registro_patronal_imss?.length > 0" class="ml-7 mt-0.5">
                                    <p v-for="(r, idx) in miEmpresa.registro_patronal_imss" :key="idx" class="text-[9px] opacity-60 italic">NRP: {{ r.nrp }} ({{ r.description }})</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Suppliers -->
            <div v-if="activeTab === 'suppliers'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] overflow-hidden">
                    <div class="p-8 border-b border-[var(--ui-border)] bg-[var(--ui-surface)] flex items-center justify-between">
                        <h3 class="font-black text-sm uppercase tracking-widest">Matriz de Vigilancia de Proveedores</h3>
                        <Link :href="route('proveedores.index')" class="text-xs font-black text-indigo-500 uppercase">Ver todos →</Link>
                    </div>
                    
                    <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-6 bg-white dark:bg-white/5 rounded-3xl border border-[var(--ui-border)] text-center">
                            <p class="text-3xl font-black text-[var(--ui-text-main)]">{{ stats.total_contratistas }}</p>
                            <p class="text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">CONTRATISTAS BAJO VIGILANCIA</p>
                        </div>
                        <div class="p-6 bg-white dark:bg-white/5 rounded-3xl border border-[var(--ui-border)] text-center">
                            <p class="text-3xl font-black text-amber-500">{{ stats.docs_pendientes }}</p>
                            <p class="text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">DOCUMENTOS POR VALIDAR</p>
                        </div>
                        <div class="p-6 bg-white dark:bg-white/5 rounded-3xl border border-[var(--ui-border)] text-center">
                            <p class="text-3xl font-black text-rose-500">{{ stats.vencimientos_proximos }}</p>
                            <p class="text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">PRÓXIMOS VENCIMIENTOS (90 DÍAS)</p>
                        </div>
                    </div>

                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-[var(--ui-surface)] border-b border-[var(--ui-border)]">
                                <th class="px-8 py-4 text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Proveedor</th>
                                <th v-for="m in months" :key="m.month" class="px-4 py-4 text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">{{ m.label }}</th>
                                <th class="px-8 py-4 text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Health Score</th>
                                <th class="px-8 py-4 text-[9px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in contratistas.slice(0, 5)" :key="c.id" class="border-b border-[var(--ui-border)]/50">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-bold text-[var(--ui-text-main)]">{{ c.nombre_razon_social }}</p>
                                        <span v-if="c.sat_status === 'active'" class="w-2 h-2 rounded-full bg-emerald-500" title="Activo SAT"></span>
                                        <span v-if="c.sat_status === 'blacklisted'" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse" title="LISTA NEGRA SAT"></span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <p class="text-[9px] text-[var(--ui-text-soft)] font-mono">{{ c.rfc }}</p>
                                        <button @click="validateSat(c.id)" class="text-[8px] font-black text-indigo-400 uppercase hover:text-indigo-600 transition-all">
                                            [Validar SAT]
                                        </button>
                                    </div>
                                </td>
                                <td v-for="(m, idx) in months" :key="m.month" class="px-4 py-5 text-center">
                                    <div class="w-6 h-6 rounded-md mx-auto" :class="getStatusColor(c.compliance_matrix ? c.compliance_matrix[idx] : 'missing')"></div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex flex-col items-end">
                                        <div :class="['px-3 py-1 rounded-full text-[10px] font-black flex items-center gap-2', getHealthColor(c.health_score || 0)]">
                                            <font-awesome-icon icon="heart-pulse" />
                                            {{ c.health_score || 0 }}%
                                        </div>
                                        <p class="text-[7px] text-slate-400 mt-1 uppercase tracking-tighter">Confianza Auditoría</p>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right flex flex-col items-end gap-1">
                                    <Link :href="route('comisiones.repse.show', c.id)" class="text-[10px] font-black text-indigo-500 underline">EXPEDIENTE</Link>
                                    <p v-if="c.last_sat_validation_at" class="text-[7px] text-slate-400 uppercase">Validado: {{ c.last_sat_validation_at }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: My Contracts -->
            <div v-if="activeTab === 'my-contracts'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] p-10">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">Mis Servicios Prestados</h3>
                            <Link :href="route('comisiones.repse.my_contracts')" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest">Gestionar Contratos</Link>
                        </div>
                        
                        <div class="space-y-4">
                            <Link :href="route('comisiones.repse.my_contracts')" class="p-6 bg-white dark:bg-white/5 rounded-3xl border border-[var(--ui-border)] flex items-center justify-between hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-500 group-hover:scale-110 transition-all">
                                        <font-awesome-icon icon="file-contract" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-[var(--ui-text-main)] uppercase">Contratos Activos</p>
                                        <p class="text-[10px] text-[var(--ui-text-soft)] font-bold">SERVICIOS REPORTABLES AL IMSS/INFONAVIT</p>
                                        <p class="text-[9px] text-indigo-500 font-black mt-1">CLIC PARA GESTIONAR →</p>
                                    </div>
                                </div>
                                <p class="text-3xl font-black text-[var(--ui-text-main)]">{{ misContratosCount }}</p>
                            </Link>
                        </div>
                    </div>

                    <div class="bg-blue-600 rounded-[2.5rem] p-10 text-white shadow-2xl">
                        <h3 class="text-lg font-black uppercase tracking-tight mb-4">Aviso de Prevención</h3>
                        <p class="text-sm leading-relaxed opacity-90 italic">
                            "Recuerda que si prestas servicios en las instalaciones de tu cliente, debes reportar el contrato a través de ICSOE en el portal del IMSS."
                        </p>
                        <div class="mt-8 pt-8 border-t border-white/20">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 bg-white rounded-full"></div>
                                <p class="text-[10px] font-black uppercase">Próximo Reporte: Septiembre 2026</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Reporting -->
            <div v-if="activeTab === 'reporting'" class="animate-in fade-in slide-in-from-bottom-4 duration-700 py-20 bg-[var(--ui-surface-soft)] rounded-[2.5rem] border border-[var(--ui-border)]">
                <div class="text-center">
                    <font-awesome-icon icon="chart-bar" class="text-6xl text-[var(--ui-text-soft)] opacity-20 mb-6" />
                    <h3 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">Cierre de Cuatrimestre</h3>
                    <p class="text-sm text-[var(--ui-text-soft)] max-w-md mx-auto mt-2">Genera los archivos necesarios para cumplir con tus obligaciones informativas ante el IMSS e INFONAVIT.</p>
                </div>

                <div class="max-w-3xl mx-auto mt-10 space-y-6">
                    <div class="bg-white dark:bg-white/5 rounded-2xl border border-[var(--ui-border)] p-6">
                        <h4 class="text-xs font-black text-[var(--ui-text-main)] uppercase tracking-widest mb-3">Calendario de Reporteo</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isReportingMonth(1) ? 'bg-indigo-500/10 border-indigo-500 text-indigo-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                ENE<br><span class="text-[8px] opacity-60">ICSOE Nov-Dic</span>
                            </div>
                            <div class="p-3 rounded-xl text-center border bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)] text-[10px] font-black">
                                FEB<br><span class="text-[8px] opacity-60">—</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isReportingMonth(3) ? 'bg-emerald-500/10 border-emerald-500 text-emerald-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                MAR<br><span class="text-[8px] opacity-60">ICSOE Ene-Feb</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isQuarterEnd(4) ? 'bg-blue-500/10 border-blue-500 text-blue-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                ABR<br><span class="text-[8px] opacity-60">SISUB 1er Cuat.</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isReportingMonth(5) ? 'bg-emerald-500/10 border-emerald-500 text-emerald-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                MAY<br><span class="text-[8px] opacity-60">ICSOE Mar-Abr</span>
                            </div>
                            <div class="p-3 rounded-xl text-center border bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)] text-[10px] font-black">
                                JUN<br><span class="text-[8px] opacity-60">—</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isReportingMonth(7) ? 'bg-emerald-500/10 border-emerald-500 text-emerald-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                JUL<br><span class="text-[8px] opacity-60">ICSOE May-Jun</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isQuarterEnd(8) ? 'bg-blue-500/10 border-blue-500 text-blue-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                AGO<br><span class="text-[8px] opacity-60">SISUB 2do Cuat.</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isReportingMonth(9) ? 'bg-emerald-500/10 border-emerald-500 text-emerald-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                SEP<br><span class="text-[8px] opacity-60">ICSOE Jul-Ago</span>
                            </div>
                            <div class="p-3 rounded-xl text-center border bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)] text-[10px] font-black">
                                OCT<br><span class="text-[8px] opacity-60">—</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isReportingMonth(11) ? 'bg-emerald-500/10 border-emerald-500 text-emerald-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                NOV<br><span class="text-[8px] opacity-60">ICSOE Sep-Oct</span>
                            </div>
                            <div :class="['p-3 rounded-xl text-center border text-[10px] font-black', isQuarterEnd(12) ? 'bg-blue-500/10 border-blue-500 text-blue-500' : 'bg-slate-50 dark:bg-white/5 border-[var(--ui-border)] text-[var(--ui-text-soft)]']">
                                DIC<br><span class="text-[8px] opacity-60">SISUB 3er Cuat.</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-4 bg-amber-500/5 border border-amber-500/20 rounded-2xl">
                        <font-awesome-icon icon="triangle-exclamation" class="text-amber-500" />
                        <span class="text-xs font-bold text-[var(--ui-text-main)]">{{ misContratosCount }} contrato(s) registrado(s) — {{ pendingReportingCount }} pendiente(s) de reportar este período</span>
                    </div>

                    <div class="flex justify-center gap-4">
                        <a :href="route('comisiones.repse.export_global_icsoe')" class="px-8 py-4 bg-emerald-500 text-white rounded-2xl font-black text-xs shadow-xl shadow-emerald-500/20 hover:scale-105 transition-all inline-flex items-center">
                            <font-awesome-icon icon="file-csv" class="mr-2" /> DESCARGAR ICSOE (IMSS)
                        </a>
                        <a :href="route('comisiones.repse.export_global_sisub')" class="px-8 py-4 bg-blue-500 text-white rounded-2xl font-black text-xs shadow-xl shadow-blue-500/20 hover:scale-105 transition-all inline-flex items-center">
                            <font-awesome-icon icon="file-excel" class="mr-2" /> DESCARGAR SISUB (INFONAVIT)
                        </a>
                    </div>
                    <p class="text-center text-[9px] text-[var(--ui-text-soft)] font-bold">Selecciona un contrato en Mis Contratos para exportar ICSOE o SISUB individualmente.</p>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="showModalDetail" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-[var(--ui-surface)] w-full max-w-lg rounded-[2.5rem] border border-[var(--ui-border)] shadow-2xl p-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-500">
                        <font-awesome-icon icon="info-circle" />
                    </div>
                    <h3 class="text-xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">{{ selectedDetail.title }}</h3>
                </div>

                <div class="space-y-6">
                    <div class="p-6 bg-[var(--ui-surface-soft)] rounded-3xl border border-[var(--ui-border)]">
                        <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">{{ selectedDetail.label }}</p>
                        
                        <template v-if="selectedDetail.type === 'imss'">
                            <div v-if="miEmpresa?.registro_patronal_imss?.length > 0" class="space-y-2 mt-2">
                                <div v-for="(r, idx) in miEmpresa.registro_patronal_imss" :key="idx" class="p-3 bg-white dark:bg-white/5 rounded-xl border border-[var(--ui-border)]">
                                    <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ r.nrp }}</p>
                                    <p class="text-[10px] text-[var(--ui-text-soft)] uppercase font-black">{{ r.description }}</p>
                                </div>
                            </div>
                            <p v-else class="text-sm font-bold text-[var(--ui-text-main)] opacity-40 italic">NO REGISTRADO</p>
                        </template>
                        <p v-else class="text-sm font-bold text-[var(--ui-text-main)]">
                            {{ selectedDetail.path === 'acta_constitutiva_path' && miEmpresa?.rfc?.length === 13 ? 'Documentación de Identidad' : (miEmpresa?.[selectedDetail.field] || 'NO REGISTRADO') }}
                        </p>
                    </div>

                    <div v-if="selectedDetail.path === 'acta_constitutiva_path' && miEmpresa?.rfc?.length === 13" class="space-y-4">
                        <div v-if="miEmpresa?.csf_pdf_path" class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <font-awesome-icon icon="file-pdf" class="text-emerald-500" />
                                <span class="text-xs font-bold text-emerald-800">CSF Disponible</span>
                            </div>
                            <a :href="'/storage/' + miEmpresa.csf_pdf_path" target="_blank" class="text-[10px] font-black text-emerald-600 underline">VER CSF</a>
                        </div>
                        <div v-if="miEmpresa?.curp_pdf_path" class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <font-awesome-icon icon="file-pdf" class="text-emerald-500" />
                                <span class="text-xs font-bold text-emerald-800">CURP Disponible</span>
                            </div>
                            <a :href="'/storage/' + miEmpresa.curp_pdf_path" target="_blank" class="text-[10px] font-black text-emerald-600 underline">VER CURP</a>
                        </div>
                    </div>
                    <div v-else-if="selectedDetail.path && miEmpresa?.[selectedDetail.path]" class="p-6 bg-emerald-500/5 rounded-3xl border border-emerald-500/10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <font-awesome-icon icon="file-pdf" class="text-emerald-500" />
                            <span class="text-xs font-bold text-emerald-800">Documento disponible</span>
                        </div>
                        <a :href="'/storage/' + miEmpresa[selectedDetail.path]" target="_blank" class="text-[10px] font-black text-emerald-600 underline">VER ARCHIVO</a>
                    </div>
                    <div v-else-if="selectedDetail.path" class="p-6 bg-amber-500/5 rounded-3xl border border-amber-500/10 flex items-center gap-3">
                        <font-awesome-icon icon="exclamation-triangle" class="text-amber-500" />
                        <span class="text-xs font-bold text-amber-800">Falta subir el archivo PDF</span>
                    </div>
                </div>

                <div class="mt-10 flex gap-4">
                    <button @click="showModalDetail = false" class="flex-1 py-4 bg-slate-100 dark:bg-white/5 rounded-2xl font-black text-xs hover:bg-slate-200 transition-all">CERRAR</button>
                    <Link :href="route('nom035.config.index')" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs text-center shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all">CONFIGURAR</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-in {
    animation-fill-mode: forwards;
}
</style>
