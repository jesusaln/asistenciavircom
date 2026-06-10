<script setup>
import { ref, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  respondent: Object,
  questions: Array,
  guide: String,
})

const answers = ref({})
const currentIdx = ref(0)
const submitting = ref(false)

const currentQuestion = computed(() => props.questions[currentIdx.value] || null)
const progress = computed(() => ((currentIdx.value + 1) / props.questions.length) * 100)

const selectOption = (val) => {
  answers.value[currentQuestion.value.id] = val
}

const next = () => {
  if (currentIdx.value < props.questions.length - 1) currentIdx.value++
}

const prev = () => {
  if (currentIdx.value > 0) currentIdx.value--
}

const submit = () => {
  submitting.value = true
  router.post(route('nom035.cuestionario.guardar', props.respondent.id), {
    answers: answers.value
  }, { preserveScroll: true })
}

const opts = props.guide === 'I'
  ? [{ v: 0, l: 'No' }, { v: 1, l: 'Sí' }]
  : [{ v: 0, l: 'Nunca' }, { v: 1, l: 'Casi nunca' }, { v: 2, l: 'Algunas veces' }, { v: 3, l: 'Casi siempre' }, { v: 4, l: 'Siempre' }]

const allAnswered = computed(() => props.questions.every(q => answers.value[q.id] !== undefined))
</script>

<template>
  <Head title="Cuestionario NOM-035" />
  <AppLayout>
    <div class="max-w-3xl mx-auto py-8 px-4">
      <div class="mb-6">
        <div class="flex justify-between text-xs text-slate-400 mb-2">
          <span>{{ props.respondent.empleado?.nombre || 'Empleado' }}</span>
          <span>Guía {{ guide }} - {{ currentIdx + 1 }}/{{ questions.length }}</span>
        </div>
        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
          <div class="h-full bg-brand-500 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 shadow-sm">
        <div v-if="currentQuestion" class="space-y-6">
          <div v-if="currentQuestion.section" class="text-[10px] font-bold uppercase tracking-wide text-brand-500 mb-2">{{ currentQuestion.section }}</div>
          <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ currentQuestion.question_text }}</p>
          
          <div class="grid gap-3 pt-4">
            <button v-for="opt in opts" :key="opt.v"
              @click="selectOption(opt.v)"
              class="w-full text-left px-5 py-4 rounded-xl border-2 transition-all font-medium"
              :class="answers[currentQuestion.id] === opt.v
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-amber-300'
                : 'border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-amber-300'">
              {{ opt.l }}
            </button>
          </div>
        </div>

        <div class="flex justify-between mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
          <button @click="prev" :disabled="currentIdx === 0" class="px-5 py-2.5 text-sm font-semibold rounded-xl border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 disabled:opacity-30 hover:bg-slate-50 transition-all">Anterior</button>
          <button v-if="currentIdx < questions.length - 1" @click="next" class="px-5 py-2.5 text-sm font-semibold rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-all shadow-sm">Siguiente</button>
          <button v-else @click="submit" :disabled="!allAnswered || submitting"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition-all shadow-sm disabled:opacity-50">
            {{ submitting ? 'Guardando...' : 'Finalizar cuestionario' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
