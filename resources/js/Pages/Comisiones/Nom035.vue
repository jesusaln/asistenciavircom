<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    periods: Array,
    activePeriod: Object,
    respondents: Array,
    coverageMetrics: Object,
    advancedStats: Object,
    recommendations: Array
});

const selectedPeriodId = ref(props.activePeriod?.id || (props.periods.length > 0 ? props.periods[0].id : null));

const filteredRespondents = computed(() => {
    if (!selectedPeriodId.value) return props.respondents;
    return props.respondents.filter(r => r.evaluation_period_id === selectedPeriodId.value);
});

const getRiskClass = (level) => {
    const levels = {
        'Nulo': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'Bajo': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'Medio': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'Alto': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'Muy Alto': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
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
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Análisis de factores de riesgo y salud organizacional.</p>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="hidden lg:block text-right">
                        <p class="text-xs font-medium text-[var(--ui-text-soft)] uppercase tracking-wider">Periodo Activo</p>
                        <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ activePeriod?.name || 'Ninguno' }}</p>
                    </div>
                    <select 
                        v-model="selectedPeriodId"
                        class="bg-[var(--ui-surface-soft)] border-[var(--ui-border)] text-[var(--ui-text-main)] rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5 transition-all duration-200"
                    >
                        <option v-for="period in periods" :key="period.id" :value="period.id">
                            {{ period.name }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
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
                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl p-6 shadow-sm group">
                        <div class="flex flex-col">
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
                    </div>

                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl p-6 shadow-sm group">
                        <div class="flex flex-col">
                            <p class="text-xs font-medium text-[var(--ui-text-soft)] uppercase tracking-wider mb-1">Casos de Trauma</p>
                            <div class="flex items-center gap-3 mt-1">
                                <p :class="['text-3xl font-bold', advancedStats.trauma_cases > 0 ? 'text-red-500' : 'text-[var(--ui-text-main)]']">
                                    {{ advancedStats.trauma_cases }}
                                </p>
                                <span v-if="advancedStats.trauma_cases > 0" class="px-2 py-0.5 bg-red-100 text-red-600 text-[10px] rounded-full font-bold animate-pulse">REVISIÓN URGENTE</span>
                            </div>
                            <p class="text-xs text-[var(--ui-text-soft)] mt-2">Colaboradores que requieren valoración clínica.</p>
                        </div>
                    </div>

                    <div v-for="(count, level) in advancedStats.risk_levels" :key="level" 
                        class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl p-6 shadow-sm group"
                    >
                        <p class="text-xs font-medium text-[var(--ui-text-soft)] uppercase tracking-wider mb-1">Riesgo {{ level }}</p>
                        <p class="text-3xl font-bold text-[var(--ui-text-main)]">{{ count }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <div :class="['h-2 w-2 rounded-full', getRiskClass(level)]"></div>
                            <p class="text-xs text-[var(--ui-text-soft)]">{{ ((count / (advancedStats.total_respondents || 1)) * 100).toFixed(1) }}% del total</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Table -->
                <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-6 border-b border-[var(--ui-border)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <h3 class="text-lg font-semibold text-[var(--ui-text-main)] flex items-center gap-2">
                            <font-awesome-icon icon="list-ul" class="text-purple-500" />
                            Matriz de Resultados Detallada
                        </h3>
                        <div class="flex gap-2">
                            <button class="inline-flex items-center px-4 py-2 bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-xl text-sm font-medium text-[var(--ui-text-main)] hover:bg-[var(--ui-border)] transition-colors gap-2">
                                <font-awesome-icon icon="file-pdf" />
                                Exportar Reporte
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead>
                                <tr class="bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)] uppercase text-[10px] tracking-widest border-b border-[var(--ui-border)]">
                                    <th class="px-6 py-4 font-bold">Colaborador</th>
                                    <th class="px-6 py-4 font-bold">Departamento</th>
                                    <th class="px-6 py-4 font-bold text-center">Guía</th>
                                    <th class="px-6 py-4 font-bold text-center">Puntaje</th>
                                    <th class="px-6 py-4 font-bold text-center">Riesgo</th>
                                    <th class="px-6 py-4 font-bold text-center">Trauma</th>
                                    <th class="px-6 py-4 font-bold">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--ui-border)]">
                                <tr v-for="res in filteredRespondents" :key="res.id" class="hover:bg-[var(--ui-surface-soft)]/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-600 font-bold border border-purple-500/20">
                                                {{ res.name?.charAt(0) || '?' }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-[var(--ui-text-main)] group-hover:text-purple-500 transition-colors">{{ res.name }}</div>
                                                <div class="text-[10px] text-[var(--ui-text-soft)]">{{ res.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[var(--ui-text-main)] font-medium">{{ res.department }}</div>
                                        <div class="text-[10px] text-[var(--ui-text-soft)] tracking-wider uppercase">{{ res.position }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-0.5 bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded text-[10px] font-mono text-[var(--ui-text-soft)]">
                                            {{ res.applied_guide || res.guide || 'II' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-black text-[var(--ui-text-main)]">{{ res.total_score || '--' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm', getRiskClass(res.risk_level)]">
                                            {{ res.risk_level || 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <font-awesome-icon 
                                            v-if="res.requires_clinical_valuation" 
                                            icon="exclamation-triangle" 
                                            class="text-red-500 text-lg animate-pulse" 
                                            title="Requiere valoración clínica"
                                        />
                                        <font-awesome-icon 
                                            v-else 
                                            icon="check-circle" 
                                            class="text-green-500/30 text-lg" 
                                        />
                                    </td>
                                    <td class="px-6 py-4 text-[var(--ui-text-soft)] text-xs font-mono">
                                        {{ res.completed_at ? new Date(res.completed_at).toLocaleDateString() : '--' }}
                                    </td>
                                </tr>
                                <tr v-if="filteredRespondents.length === 0">
                                    <td colspan="7" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <font-awesome-icon icon="inbox" class="text-4xl text-[var(--ui-text-soft)]/20" />
                                            <p class="text-[var(--ui-text-soft)] font-medium">No se encontraron evaluaciones en este periodo.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.font-black { font-weight: 900; }
</style>
