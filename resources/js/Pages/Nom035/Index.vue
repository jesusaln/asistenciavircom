<script setup>
import { ref, computed } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

const notyf = new Notyf({ duration: 4000 })

const props = defineProps({
    periods: Array,
    activePeriod: Object,
    respondents: Array,
    coverageMetrics: Object,
    advancedStats: Object,
    recommendations: Array,
    complianceWizard: Object,
    reEvaluation: Object,
})

const wizardSteps = computed(() => [
    { 
        id: 1, 
        title: 'Política', 
        desc: 'Definir política.', 
        done: props.complianceWizard?.step1_policy,
        route: 'nom035.config.index'
    },
    { 
        id: 2, 
        title: 'Periodo', 
        desc: 'Activar encuestas.', 
        done: props.complianceWizard?.step2_period,
        action: () => showForm.value = true
    },
    { 
        id: 3, 
        title: 'Asignar', 
        desc: 'Vincular personal.', 
        done: props.complianceWizard?.step3_respondents,
        route: props.activePeriod ? 'nom035.periodos.show' : null,
        routeParam: props.activePeriod?.id
    },
    { 
        id: 4, 
        title: 'Cobertura', 
        desc: 'Lograr >80%.', 
        done: props.complianceWizard?.step4_completion,
        info: `${props.coverageMetrics?.coverage_rate || 0}%`,
        route: props.activePeriod ? 'nom035.periodos.show' : null,
        routeParam: props.activePeriod?.id
    },
    { 
        id: 5, 
        title: 'Clínica', 
        desc: 'Seguimiento médico.', 
        done: props.complianceWizard?.step5_referrals,
        route: props.activePeriod ? 'nom035.periodos.show' : null,
        routeParam: props.activePeriod?.id
    },
    { 
        id: 6, 
        title: 'Control', 
        desc: 'Registrar acciones.', 
        done: props.complianceWizard?.step6_activities,
        route: 'nom035.activities.index'
    },
])

const showForm = ref(false)
const hoy = new Date()
const mesSig = new Date(hoy.getFullYear(), hoy.getMonth() + 1, hoy.getDate())
const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')

const confirmingPeriodDeletion = ref(false)
const periodToDelete = ref(null)

const confirmDeletePeriod = (period) => {
    periodToDelete.value = period
    confirmingPeriodDeletion.value = true
}

const deletePeriod = () => {
    router.delete(route('nom035.periodos.destroy', periodToDelete.value.id), {
        onSuccess: () => {
            confirmingPeriodDeletion.value = false
            periodToDelete.value = null
        }
    })
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return dateString
    // Usamos UTC para evitar desfases de zona horaria en objetos Date de solo fecha
    const utcDate = new Date(date.getTime() + date.getTimezoneOffset() * 60000)
    return utcDate.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const newPeriodo = ref({ 
    name: 'Evaluación ' + hoy.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }),
    start_date: fmt(hoy),
    end_date: fmt(mesSig)
})

const creating = ref(false)
const crearPeriodo = () => {
    if (!newPeriodo.value.name || !newPeriodo.value.start_date || !newPeriodo.value.end_date) {
        notyf.error('Completa todos los campos'); return
    }
    creating.value = true
    router.post(route('nom035.periodos.store'), newPeriodo.value, {
        onSuccess: () => { 
            showForm.value = false
            creating.value = false
            notyf.success('Periodo creado exitosamente') 
        },
        onError: () => creating.value = false
    })
}

const getRiskClass = (level) => {
    const levels = {
        'Nulo': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'Bajo': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'Medio': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'Alto': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'Muy Alto': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'Sin hallazgos críticos detectados': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'Se sugiere seguimiento según protocolo': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return levels[level] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
};
</script>

<template>
    <AppLayout title="NOM-035 - Matriz de Riesgo">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-[var(--ui-text-main)] leading-tight flex items-center gap-3">
                        <div class="p-2 bg-purple-500/10 rounded-lg">
                            <font-awesome-icon icon="brain" class="text-purple-500" />
                        </div>
                        NOM-035: Centro de Cumplimiento
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Gestión administrativa y análisis de factores de riesgo.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('nom035.config.index')"
                        class="inline-flex items-center px-4 py-2 bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] text-[var(--ui-text-main)] rounded-xl text-sm font-bold hover:bg-[var(--ui-border)] transition-all gap-2"
                    >
                        <font-awesome-icon icon="cog" class="text-purple-500" />
                        Configuración
                    </Link>
                    <a 
                        v-if="activePeriod"
                        :href="route('nom035.periodos.pdf', activePeriod.id)"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] text-[var(--ui-text-main)] rounded-xl text-sm font-bold hover:bg-[var(--ui-border)] transition-all gap-2"
                    >
                        <font-awesome-icon icon="file-pdf" class="text-red-500" />
                        Reporte General
                    </a>
                    <button 
                        @click="showForm = !showForm"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-purple-500/20 transition-all gap-2"
                    >
                        <font-awesome-icon :icon="showForm ? 'times' : 'plus'" />
                        {{ showForm ? 'Cancelar' : 'Nuevo Periodo' }}
                    </button>
                    <a 
                        v-if="complianceWizard?.is_complete"
                        :href="route('nom035.certificate')"
                        target="_blank"
                        class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-amber-400 to-yellow-600 text-white rounded-xl text-sm font-black shadow-lg shadow-amber-500/30 hover:scale-105 transition-all gap-2 animate-bounce"
                    >
                        <font-awesome-icon icon="trophy" />
                        DESCARGAR RECONOCIMIENTO 100%
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-full mx-auto px-4 sm:px-10 lg:px-16 space-y-8">
                
                <!-- Compliance Wizard -->
                <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-3xl p-6 shadow-lg overflow-hidden relative">
                    <div class="absolute top-0 right-0 p-8 opacity-[0.03] pointer-events-none">
                        <font-awesome-icon icon="clipboard-check" class="text-9xl rotate-12" />
                    </div>
                    
                    <div class="flex items-center gap-3 mb-6">
                        <div class="h-10 w-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500 border border-indigo-500/20">
                            <font-awesome-icon icon="list-check" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-[var(--ui-text-main)] uppercase tracking-tight">Guía de Cumplimiento Legal (NOM-035)</h3>
                            <div class="flex items-center gap-2">
                                <p class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-widest">Sigue estos pasos para evitar multas de la STPS</p>
                                <span v-if="complianceWizard?.total_percentage" class="px-2 py-0.5 bg-indigo-100 text-indigo-600 text-[9px] font-black rounded-full">{{ complianceWizard.total_percentage }}% COMPLETADO</span>
                            </div>
                        </div>
                    </div>

                    <!-- 100% Completion Badge -->
                    <div v-if="complianceWizard?.is_complete" class="mb-8 p-6 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/30 rounded-3xl relative overflow-hidden animate-in zoom-in duration-700">
                        <div class="absolute -right-8 -top-8 text-emerald-500/10 rotate-12">
                            <font-awesome-icon icon="award" size="10x" />
                        </div>
                        <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                            <div class="h-20 w-20 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-xl shadow-emerald-500/20 rotate-3">
                                <font-awesome-icon icon="crown" size="2xl" />
                            </div>
                            <div class="text-center md:text-left">
                                <h3 class="text-xl font-black text-emerald-700 dark:text-emerald-400">¡Felicidades! Cumplimiento Total Alcanzado</h3>
                                <p class="text-sm text-emerald-600/80 dark:text-emerald-500/80 font-medium">Has completado todos los requisitos de la NOM-035 para este ciclo. Tu empresa cuenta con un entorno organizacional favorable avalado digitalmente.</p>
                            </div>
                            <div class="md:ml-auto flex flex-col sm:flex-row gap-3">
                                <a :href="route('nom035.auditoria')" target="_blank" class="px-6 py-3 bg-white/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 rounded-xl font-black text-xs hover:bg-white/20 transition-all uppercase tracking-widest text-center">
                                    Expediente Auditoría
                                </a>
                                <a :href="route('nom035.quarterly_report')" target="_blank" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-black text-xs hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20 uppercase tracking-widest text-center">
                                    Reporte Trimestral Q{{ Math.floor((new Date().getMonth() + 3) / 3) }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                        <div v-for="step in wizardSteps" :key="step.id" 
                             :class="['relative p-4 rounded-2xl border transition-all duration-300', 
                                      step.done ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-[var(--ui-surface)] border-[var(--ui-border)]']">
                            
                            <div class="flex items-center justify-between mb-2">
                                <span :class="['text-[9px] font-black w-5 h-5 rounded-lg flex items-center justify-center border', 
                                               step.done ? 'bg-emerald-500 text-white border-emerald-400' : 'bg-slate-100 text-slate-500 border-slate-200']">
                                    <font-awesome-icon v-if="step.done" icon="check" />
                                    <span v-else>{{ step.id }}</span>
                                </span>
                                <span v-if="step.info" class="text-[8px] font-black text-indigo-500 uppercase">{{ step.info }}</span>
                            </div>
                            
                            <h4 :class="['text-[10px] font-black uppercase tracking-tight mb-1', step.done ? 'text-emerald-600' : 'text-[var(--ui-text-main)]']">
                                {{ step.title }}
                            </h4>
                            <p class="text-[9px] font-bold text-[var(--ui-text-soft)] leading-tight">{{ step.desc }}</p>
                            
                            <div class="mt-4 pt-4 border-t border-[var(--ui-border)]/50">
                                <template v-if="step.done">
                                    <Link v-if="step.route" :href="route(step.route, step.routeParam)" class="flex items-center justify-center gap-2 py-2 px-4 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all border border-emerald-500/20">
                                        REVISAR <font-awesome-icon icon="eye" class="text-[7px]" />
                                    </Link>
                                    <div v-else class="flex items-center justify-center gap-1.5 py-2 px-3 bg-emerald-500/10 rounded-xl text-emerald-600 text-[8px] font-black uppercase tracking-widest border border-emerald-500/20">
                                        <font-awesome-icon icon="check-circle" /> Completado
                                    </div>
                                </template>
                                <template v-else>
                                    <Link v-if="step.route" :href="route(step.route, step.routeParam)" class="flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-md shadow-indigo-500/20">
                                        {{ step.id <= 2 ? 'COMENZAR' : 'CONFIGURAR' }}
                                        <font-awesome-icon icon="arrow-right" class="text-[7px]" />
                                    </Link>
                                    <button v-else-if="step.action" @click="step.action" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-md shadow-indigo-500/20">
                                        COMENZAR
                                        <font-awesome-icon icon="arrow-right" class="text-[7px]" />
                                    </button>
                                    <div v-else class="flex items-center justify-center py-2 px-3 bg-slate-100 rounded-xl text-slate-400 text-[8px] font-black uppercase tracking-widest italic border border-slate-200">
                                        Pendiente
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form: New Period -->
                <div v-if="showForm" class="bg-[var(--ui-surface)] border border-purple-500/30 rounded-2xl p-6 shadow-xl shadow-purple-500/5 animate-in fade-in slide-in-from-top-4 duration-300">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="h-8 w-8 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-600">
                            <font-awesome-icon icon="calendar-plus" />
                        </div>
                        <h3 class="text-lg font-bold text-[var(--ui-text-main)]">Configurar Nuevo Periodo de Evaluación</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-[var(--ui-text-soft)] uppercase ml-1">Nombre Descriptivo</label>
                            <input v-model="newPeriodo.name" type="text" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-[var(--ui-text-main)] focus:ring-2 focus:ring-purple-500/30 transition-all" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-[var(--ui-text-soft)] uppercase ml-1">Fecha de Inicio</label>
                            <input v-model="newPeriodo.start_date" type="date" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-[var(--ui-text-main)] focus:ring-2 focus:ring-purple-500/30 transition-all" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-[var(--ui-text-soft)] uppercase ml-1">Fecha de Cierre</label>
                            <input v-model="newPeriodo.end_date" type="date" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-[var(--ui-text-main)] focus:ring-2 focus:ring-purple-500/30 transition-all" />
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button @click="crearPeriodo" :disabled="creating" class="px-6 py-2.5 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-all disabled:opacity-50">
                            {{ creating ? 'Guardando...' : 'Activar Periodo' }}
                        </button>
                    </div>
                </div>

                <!-- Next Evaluation Countdown -->
                <div v-if="!reEvaluation?.due && reEvaluation?.next_evaluation_date" class="p-4 rounded-2xl border flex gap-4 items-start shadow-sm bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300">
                    <font-awesome-icon icon="clock" class="text-xl mt-1" />
                    <div class="flex-1">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                            <div>
                                <h4 class="font-bold">📅 Próxima Evaluación Obligatoria</h4>
                                <p class="text-sm opacity-90">
                                    Según el Numeral 7.3 de la NOM-035, la próxima evaluación masiva debe realizarse antes del: 
                                    <span class="font-black">{{ formatDate(reEvaluation.next_evaluation_date) }}</span>
                                </p>
                            </div>
                            <div class="bg-blue-500/10 px-4 py-2 rounded-xl border border-blue-500/20 text-center">
                                <div class="text-xs font-black uppercase tracking-widest text-blue-600">Faltan aproximadamente</div>
                                <div class="text-xl font-black">
                                    {{ reEvaluation.months_remaining }} meses 
                                    <span class="text-sm font-bold opacity-60">({{ reEvaluation.days_remaining }} días)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Re-Evaluation Alert (Numeral 7.3) -->
                <div v-if="reEvaluation?.due" class="p-4 rounded-2xl border flex gap-4 items-start shadow-sm bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300">
                    <font-awesome-icon icon="calendar-exclamation" class="text-xl mt-1" />
                    <div>
                        <h4 class="font-bold">⚠️ Re-evaluación Vencida — Numeral 7.3 NOM-035</h4>
                        <p class="text-sm opacity-90">
                            Última evaluación cerrada: {{ formatDate(reEvaluation.last_evaluation_date) }}. 
                            La ley exige evaluar al menos cada 2 años.
                            <span v-if="reEvaluation.months_overdue > 0" class="font-bold">
                                {{ reEvaluation.months_overdue }} mes(es) de retraso.
                            </span>
                            <span v-else class="font-bold">Vence en menos de 3 meses.</span>
                        </p>
                        <p class="text-xs mt-1 font-medium italic opacity-75">
                            Crea un nuevo periodo de evaluación para cumplir con la NOM-035-STPS-2018.
                        </p>
                    </div>
                </div>
                <!-- Recommendations / Alerts -->
                <div v-if="recommendations.length > 0" class="space-y-4">
                    <div v-for="(rec, index) in recommendations" :key="index" 
                        :class="[
                            'p-4 rounded-2xl border flex gap-4 items-start shadow-sm',
                            rec.priority === 'Crítica' || rec.priority === 'Urgente' 
                                ? 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300'
                                : 'bg-orange-50 border-orange-200 text-orange-800 dark:bg-orange-900/20 dark:border-orange-800 dark:text-orange-300'
                        ]"
                    >
                        <font-awesome-icon :icon="rec.priority === 'Urgente' ? 'exclamation-triangle' : 'shield-halved'" class="text-xl mt-1" />
                        <div>
                            <h4 class="font-bold flex items-center gap-2">
                                {{ rec.scope }}: {{ rec.priority }}
                            </h4>
                            <p class="text-sm opacity-90">{{ rec.action }}</p>
                            <p class="text-xs mt-1 font-medium italic opacity-75">{{ rec.details }}</p>
                        </div>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-medium text-[var(--ui-text-soft)] uppercase tracking-wider mb-1">Participación</p>
                        <div class="flex items-end justify-between">
                            <p class="text-3xl font-bold text-[var(--ui-text-main)]">{{ coverageMetrics.coverage_rate }}%</p>
                            <div class="text-xs text-[var(--ui-text-soft)] mb-1">
                                {{ coverageMetrics.completed_respondents }} / {{ coverageMetrics.evaluable_employees }}
                            </div>
                        </div>
                        <div class="w-full bg-[var(--ui-surface-soft)] rounded-full h-1.5 mt-4">
                            <div class="bg-purple-500 h-1.5 rounded-full" :style="{ width: coverageMetrics.coverage_rate + '%' }"></div>
                        </div>
                    </div>

                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-medium text-[var(--ui-text-soft)] uppercase tracking-wider mb-1">Casos de Trauma</p>
                        <div class="flex items-center gap-3 mt-1">
                            <p :class="['text-3xl font-bold', (advancedStats.trauma_cases || 0) > 0 ? 'text-red-500' : 'text-[var(--ui-text-main)]']">
                                {{ advancedStats.trauma_cases || 0 }}
                            </p>
                            <span v-if="(advancedStats.trauma_cases || 0) > 0" class="px-2 py-0.5 bg-red-100 text-red-600 text-[10px] rounded-full font-bold">REVISIÓN</span>
                        </div>
                        <p class="text-xs text-[var(--ui-text-soft)] mt-2">Valoración clínica necesaria.</p>
                    </div>

                    <div v-for="(count, level) in advancedStats.risk_levels" :key="level" 
                        class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl p-6 shadow-sm"
                    >
                        <p class="text-xs font-medium text-[var(--ui-text-soft)] uppercase tracking-wider mb-1">Riesgo {{ level }}</p>
                        <p class="text-3xl font-bold text-[var(--ui-text-main)]">{{ count }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <div :class="['h-2 w-2 rounded-full', getRiskClass(level)]"></div>
                            <p class="text-[9px] text-[var(--ui-text-soft)]">{{ ((count / (advancedStats.total_respondents || 1)) * 100).toFixed(1) }}%</p>
                        </div>
                    </div>
                </div>

                <!-- History and Active Period -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Periods List -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl overflow-hidden shadow-sm">
                            <div class="p-4 border-b border-[var(--ui-border)] bg-[var(--ui-surface-soft)]/50">
                                <h3 class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-widest flex items-center gap-2">
                                    <font-awesome-icon icon="clock-rotate-left" />
                                    Historial de Periodos
                                </h3>
                            </div>
                            <div class="divide-y divide-[var(--ui-border)]">
                                <div v-for="p in periods" :key="p.id" 
                                    class="p-4 flex items-center justify-between hover:bg-[var(--ui-surface-soft)] transition-colors group"
                                >
                                    <Link :href="route('nom035.periodos.show', p.id)" class="flex-1">
                                        <p class="text-sm font-bold text-[var(--ui-text-main)] group-hover:text-purple-500 transition-colors">{{ p.name }}</p>
                                        <p class="text-[10px] text-[var(--ui-text-soft)]">{{ formatDate(p.start_date) }} al {{ formatDate(p.end_date) }}</p>
                                    </Link>
                                    <div class="flex items-center gap-2">
                                        <div :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tighter', p.active ? 'bg-green-100 text-green-700' : 'bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)]']">
                                            {{ p.active ? 'Activo' : 'Cerrado' }}
                                        </div>
                                        <button 
                                            v-if="!p.active"
                                            @click="confirmDeletePeriod(p)"
                                            class="h-6 w-6 bg-rose-500/10 text-rose-600 rounded flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all"
                                        >
                                            <font-awesome-icon icon="trash" class="text-[10px]" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table -->
                    <div class="lg:col-span-2">
                        <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl shadow-sm overflow-hidden">
                            <div class="p-6 border-b border-[var(--ui-border)] flex items-center justify-between">
                                <h3 class="text-lg font-bold text-[var(--ui-text-main)] flex items-center gap-2">
                                    <font-awesome-icon icon="users" class="text-purple-500" />
                                    Resultados del Periodo Actual
                                </h3>
                                <Link :href="route('nom035.periodos.show', activePeriod?.id)" class="text-sm text-purple-500 font-bold hover:underline" v-if="activePeriod">
                                    Gestionar Periodo →
                                </Link>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left border-collapse">
                                    <thead>
                                        <tr class="bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)] uppercase text-[10px] tracking-widest border-b border-[var(--ui-border)]">
                                            <th class="px-6 py-4 font-bold">Colaborador</th>
                                            <th class="px-6 py-4 font-bold text-center">Riesgo</th>
                                            <th class="px-6 py-4 font-bold text-center">Trauma</th>
                                            <th class="px-6 py-4 font-bold">Estado</th>
                                            <th class="px-6 py-4 font-bold text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--ui-border)]">
                                        <tr v-for="res in respondents.filter(r => r.evaluation_period_id === activePeriod?.id).slice(0, 10)" :key="res.id" class="hover:bg-[var(--ui-surface-soft)]/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-[var(--ui-text-main)]">{{ res.empleado?.name || res.name }}</div>
                                                <div class="text-[10px] text-[var(--ui-text-soft)]">{{ res.empleado?.departamento || res.department }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm', getRiskClass(res.risk_level)]">
                                                    {{ res.risk_level || 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <!-- Caso Crítico (Requiere Valoración) -->
                                                    <template v-if="res.requires_clinical_valuation">
                                                        <div class="flex items-center gap-1 px-2 py-0.5 bg-red-50 border border-red-100 rounded-md">
                                                            <font-awesome-icon icon="exclamation-triangle" class="text-red-600 text-[10px]" />
                                                            <span class="text-[9px] font-black text-red-600 uppercase tracking-tighter">ALERTA CRÍTICA</span>
                                                        </div>
                                                    </template>
                                                    <!-- Trauma Detectado (Sección I) pero no crítico -->
                                                    <template v-else-if="res.results?.counts?.section_i">
                                                        <div class="flex items-center gap-1 px-2 py-0.5 bg-orange-50 border border-orange-100 rounded-md">
                                                            <font-awesome-icon icon="exclamation-circle" class="text-orange-600 text-[10px]" />
                                                            <span class="text-[9px] font-black text-orange-600 uppercase tracking-tighter">TRAUMA DETECTADO</span>
                                                        </div>
                                                    </template>
                                                    <!-- Sin Trauma -->
                                                    <template v-else>
                                                        <span class="text-slate-300 font-bold">-</span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs" :class="res.status === 'completed' ? 'text-green-500' : 'text-orange-500'">
                                                    {{ res.status === 'completed' ? 'Completado' : 'En proceso' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <Link v-if="res.status === 'completed'" :href="route('nom035.resultados', res.uuid)" class="p-2 hover:bg-purple-500/10 text-purple-500 rounded-lg transition-colors" title="Ver Resultados">
                                                        <font-awesome-icon icon="eye" />
                                                    </Link>
                                                    <Link v-if="res.status === 'completed'" :href="route('nom035.respuestas', res.uuid)" class="p-2 hover:bg-blue-500/10 text-blue-500 rounded-lg transition-colors" title="Ver Respuestas">
                                                        <font-awesome-icon icon="list-ul" />
                                                    </Link>
                                                    <a v-if="res.status === 'completed'" :href="route('nom035.resultados.pdf', res.uuid)" target="_blank" class="p-2 hover:bg-red-500/10 text-red-500 rounded-lg transition-colors" title="Descargar PDF">
                                                        <font-awesome-icon icon="file-pdf" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!activePeriod">
                                            <td colspan="4" class="px-6 py-12 text-center text-[var(--ui-text-soft)]">
                                                Selecciona o crea un periodo para ver resultados.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>

    <!-- Confirmation Modal for Deletion -->
    <div v-if="confirmingPeriodDeletion" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="max-w-md w-full bg-[#111827] rounded-[2.5rem] p-8 border border-slate-800 shadow-2xl scale-in-center">
            <div class="h-20 w-20 bg-rose-500/10 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-rose-500/20">
                <font-awesome-icon icon="exclamation-triangle" size="3x" class="animate-pulse" />
            </div>
            
            <h3 class="text-2xl font-black text-white text-center mb-2 uppercase tracking-tight">¿Eliminar permanentemente?</h3>
            <p class="text-sm text-slate-400 text-center mb-8 leading-relaxed">
                Estás a punto de borrar el periodo <span class="text-rose-400 font-bold">"{{ periodToDelete?.name }}"</span>. 
                Esta acción eliminará <span class="text-white font-bold">TODOS</span> los cuestionarios, resultados y firmas de los colaboradores asociados. 
                <br><br>
                <span class="text-rose-500 font-black uppercase text-[10px] tracking-widest bg-rose-500/10 px-2 py-1 rounded">ESTA ACCIÓN ES IRREVERSIBLE</span>
            </p>

            <div class="flex flex-col gap-3">
                <button @click="deletePeriod" class="w-full py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-black text-sm transition-all shadow-lg shadow-rose-600/20 uppercase tracking-widest">
                    Sí, eliminar todo definitivamente
                </button>
                <button @click="confirmingPeriodDeletion = false" class="w-full py-4 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl font-black text-sm transition-all uppercase tracking-widest">
                    Cancelar y mantener datos
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
</style>
