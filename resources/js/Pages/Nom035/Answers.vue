<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    respondent: Object,
    answers: Array
})

const groupedAnswers = computed(() => {
    const groups = {};
    props.answers.forEach(ans => {
        const category = ans.question?.category || 'General';
        if (!groups[category]) groups[category] = [];
        groups[category].push(ans);
    });
    return groups;
});

const getAnswerText = (val) => {
    const texts = {
        0: 'Nunca',
        1: 'Casi nunca',
        2: 'A veces',
        3: 'Casi siempre',
        4: 'Siempre'
    };
    return texts[val] || val;
};

const getRiskColor = (val) => {
    if (val >= 3) return 'text-red-400 font-bold';
    if (val >= 2) return 'text-orange-400 font-bold';
    return 'text-slate-400';
};
</script>

<template>
    <AppLayout title="Detalle de Respuestas">
        <template #header>
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('nom035.index')" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-full text-slate-400 transition-colors">
                        <font-awesome-icon icon="arrow-left" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-xl text-white leading-tight">
                            Respuestas: {{ respondent.empleado?.name || respondent.name }}
                        </h2>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">
                            {{ respondent.position }} | {{ respondent.department }} | {{ respondent.guide }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <a :href="route('nom035.resultados.pdf', respondent.uuid)" target="_blank" class="px-4 py-2 bg-white text-slate-900 rounded-xl text-xs font-black hover:bg-slate-100 transition-all flex items-center gap-2 shadow-lg shadow-white/5">
                        <font-awesome-icon icon="file-pdf" />
                        PDF Constancia
                    </a>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#0a0f18] min-h-screen">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-12">
                
                <div v-for="(ansList, category) in groupedAnswers" :key="category" class="space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-1 w-12 bg-purple-500 rounded-full shadow-[0_0_10px_rgba(168,85,247,0.5)]"></div>
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ category }}</h3>
                    </div>

                    <div class="bg-[#111827] rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-900/50 text-[10px] uppercase font-black text-slate-500 border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">#</th>
                                    <th class="px-6 py-4">Pregunta</th>
                                    <th class="px-6 py-4 text-center">Respuesta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <tr v-for="(ans, idx) in ansList" :key="ans.id" class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 text-slate-600 font-mono text-[10px]">{{ idx + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <p class="text-slate-300 font-medium leading-relaxed">{{ ans.question?.text }}</p>
                                        <p v-if="ans.question?.domain" class="text-[9px] text-slate-500 mt-1 uppercase tracking-tighter font-bold">Dominio: {{ ans.question.domain }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span :class="getRiskColor(ans.value)" class="text-xs">
                                            {{ getAnswerText(ans.value) }}
                                        </span>
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
