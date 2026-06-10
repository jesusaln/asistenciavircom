<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    respondent: Object,
    questions: Array,
    currentGuide: String,
    existingAnswers: Object
})

const answers = ref({ ...props.existingAnswers })

const progress = computed(() => {
    const total = props.questions.length
    const answered = Object.keys(answers.value).length
    return Math.round((answered / total) * 100)
})

const isGuideI = computed(() => props.currentGuide === 'I')

const likertOptions = [
    { label: 'Siempre', value: 4 },
    { label: 'Casi siempre', value: 3 },
    { label: 'Algunas veces', value: 2 },
    { label: 'Casi nunca', value: 1 },
    { label: 'Nunca', value: 0 },
]

const yesNoOptions = [
    { label: 'Sí', value: 1 },
    { label: 'No', value: 0 },
]

const submit = () => {
    if (Object.keys(answers.value).length < props.questions.length) {
        alert('Por favor responde todas las preguntas antes de continuar.')
        return
    }
    router.post(route('nom035.questionnaire.submit', props.respondent.uuid), {
        guide: props.currentGuide,
        answers: answers.value
    })
}

const selectAnswer = (questionId, value) => {
    answers.value[questionId] = value
}
</script>

<template>
    <Head :title="'Cuestionario Guía ' + currentGuide" />
    
    <div class="min-h-screen bg-[#0a0f18] text-slate-300 flex flex-col pb-12 selection:bg-purple-500/30">
        <!-- Sticky Header with Progress -->
        <div class="sticky top-0 z-50 bg-[#111827]/80 backdrop-blur-xl border-b border-slate-800">
            <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-500/20">
                        <font-awesome-icon icon="poll" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-white uppercase tracking-tighter">Guía {{ currentGuide }}</h2>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">{{ respondent.name }}</p>
                    </div>
                </div>
                
                <div class="flex flex-col items-end gap-1">
                    <span class="text-[10px] font-black text-purple-400">{{ progress }}% COMPLETADO</span>
                    <div class="w-32 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-purple-600 h-1.5 rounded-full transition-all duration-500" :style="{ width: progress + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <main class="max-w-3xl mx-auto px-6 py-12 space-y-12">
            <!-- Intro Text -->
            <div class="bg-[#111827] p-8 rounded-3xl border border-slate-800 shadow-sm space-y-4">
                <h1 class="text-2xl font-black text-white tracking-tight">Instrucciones</h1>
                <p class="text-slate-400 text-sm leading-relaxed">
                    Lea detenidamente cada una de las siguientes afirmaciones y seleccione la opción que mejor describa su situación laboral. 
                    No hay respuestas correctas o incorrectas, sea lo más sincero posible.
                </p>
            </div>

            <!-- Questions -->
            <div class="space-y-6">
                <div v-for="(q, idx) in questions" :key="q.id" 
                    :class="[
                        'p-8 rounded-3xl border transition-all duration-300',
                        answers[q.id] !== undefined 
                            ? 'bg-[#111827] border-slate-700 shadow-xl scale-100' 
                            : 'bg-[#111827]/40 border-slate-800/50 scale-[0.99] opacity-80'
                    ]"
                >
                    <div class="flex gap-4">
                        <span class="text-xs font-black text-slate-600 mt-1">{{ idx + 1 }}.</span>
                        <p class="text-lg font-bold text-white leading-snug">{{ q.text }}</p>
                    </div>

                    <div class="mt-8 grid grid-cols-1 sm:grid-cols-5 gap-3" v-if="!isGuideI">
                        <button 
                            v-for="opt in likertOptions" :key="opt.value"
                            @click="selectAnswer(q.id, opt.value)"
                            :class="[
                                'px-3 py-4 rounded-2xl text-xs font-black transition-all border-2',
                                answers[q.id] === opt.value 
                                    ? 'bg-purple-600 border-purple-600 text-white shadow-lg shadow-purple-500/20' 
                                    : 'bg-[#1f2937] border-transparent text-slate-400 hover:bg-slate-800'
                            ]"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <div class="mt-8 flex gap-4" v-else>
                        <button 
                            v-for="opt in yesNoOptions" :key="opt.value"
                            @click="selectAnswer(q.id, opt.value)"
                            :class="[
                                'flex-1 py-4 rounded-2xl text-sm font-black transition-all border-2',
                                answers[q.id] === opt.value 
                                    ? (opt.value === 1 ? 'bg-purple-600 border-purple-600 text-white' : 'bg-slate-700 border-slate-700 text-white')
                                    : 'bg-[#1f2937] border-transparent text-slate-400 hover:bg-slate-800'
                            ]"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit Action -->
            <div class="pt-8 flex flex-col items-center gap-4">
                <button 
                    @click="submit"
                    :disabled="progress < 100"
                    class="w-full py-5 bg-purple-600 hover:bg-purple-700 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-3xl font-black text-lg shadow-2xl shadow-purple-500/20 transition-all transform active:scale-[0.98]"
                >
                    Finalizar y Continuar
                </button>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Asegúrate de haber contestado todas las preguntas</p>
            </div>
        </main>
    </div>
</template>
