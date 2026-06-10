<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { ref, onMounted } from 'vue'

const props = defineProps({
    respondent: Object,
    results: Object,
    answers: Array,
    guide: String,
    company_name: { type: String, default: 'Climas del Desierto' },
    needs_signature: Boolean
})

const getRiskClass = (level) => {
    const levels = {
        'Nulo': 'text-blue-400 bg-blue-500/10 border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.1)]',
        'Bajo': 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]',
        'Medio': 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20 shadow-[0_0_15px_rgba(250,204,21,0.1)]',
        'Alto': 'text-orange-400 bg-orange-500/10 border-orange-500/20 shadow-[0_0_15px_rgba(249,115,22,0.1)]',
        'Muy Alto': 'text-red-400 bg-red-500/10 border-red-500/20 shadow-[0_0_15px_rgba(239,68,68,0.1)]',
        'Sin hallazgos críticos detectados': 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]',
        'Se sugiere seguimiento según protocolo': 'text-purple-400 bg-purple-500/10 border-purple-500/20 shadow-[0_0_15px_rgba(168,85,247,0.1)]',
    };
    return levels[level] || 'text-slate-400 bg-slate-500/10 border-slate-500/20';
};

// Signature Logic
const signaturePad = ref(null);
const isDrawing = ref(false);
const hasSignature = ref(false);
const form = useForm({
    signature: null
});

onMounted(() => {
    if (props.needs_signature && signaturePad.value) {
        const canvas = signaturePad.value;
        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = 2;
    }
});

const startDrawing = (e) => {
    isDrawing.value = true;
    draw(e);
};

const stopDrawing = () => {
    isDrawing.value = false;
    hasSignature.value = true;
    const canvas = signaturePad.value;
    const ctx = canvas.getContext('2d');
    ctx.beginPath();
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const canvas = signaturePad.value;
    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;

    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const clearSignature = () => {
    const canvas = signaturePad.value;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSignature.value = false;
};

const saveSignature = () => {
    const canvas = signaturePad.value;
    form.signature = canvas.toDataURL('image/png');
    form.post(route('nom035.questionnaire.signature', props.respondent.uuid), {
        onSuccess: () => {
            // Success handled by Inertia reload
        }
    });
};
</script>

<template>
    <Head title="Resultados de Evaluación NOM-035" />
    
    <div class="min-h-screen bg-[#0a0f18] text-slate-300 flex flex-col items-center py-10 px-4">
        <div class="max-w-2xl w-full bg-[#111827] rounded-3xl shadow-2xl overflow-hidden border border-slate-800 animate-in fade-in slide-in-from-bottom duration-700">
            <!-- Header -->
            <div class="p-10 text-center border-b border-slate-800">
                <div class="h-16 w-16 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-500/30">
                    <font-awesome-icon icon="clipboard-check" class="text-2xl" />
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Evaluación Registrada</h1>
                <p class="text-slate-500 text-sm mt-1">Colaborador: {{ respondent.name }}</p>
            </div>

            <div class="p-8 space-y-8">
                <!-- Risk Level -->
                <div :class="['p-6 rounded-2xl border text-center transition-all', getRiskClass(respondent.risk_level)]">
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1 opacity-70">Resultado Obtenido</p>
                    <h2 class="text-2xl font-black">{{ respondent.risk_level }}</h2>
                </div>

                <!-- Answer Verification -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Tus Respuestas</h3>
                        <span class="text-[10px] bg-slate-800 px-2 py-1 rounded text-slate-500">{{ answers.length }} ítems</span>
                    </div>
                    <div class="max-height-[300px] overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                        <div v-for="(answer, idx) in answers" :key="answer.id" class="p-3 bg-slate-900/50 rounded-xl border border-slate-800 flex gap-4 items-start">
                            <span class="text-slate-600 font-mono text-[10px] mt-1">{{ idx + 1 }}</span>
                            <div class="flex-1">
                                <p class="text-xs text-slate-400 leading-normal">{{ answer.question.text }}</p>
                                <p class="text-xs font-bold text-white mt-1">{{ answer.value === 1 ? 'Sí' : (answer.value === 0 && guide === 'I' ? 'No' : answer.value) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIGNATURE PAD -->
                <div v-if="needs_signature" class="space-y-4 pt-4 border-t border-slate-800">
                    <div class="text-center space-y-2">
                        <h3 class="text-lg font-bold text-white">Certifica tu Evaluación</h3>
                        <p class="text-xs text-slate-400">Por favor, firma en el recuadro para validar la autenticidad de tus respuestas.</p>
                    </div>

                    <div class="relative bg-slate-950 rounded-2xl border-2 border-dashed border-slate-800 overflow-hidden">
                        <canvas 
                            ref="signaturePad" 
                            width="600" 
                            height="200" 
                            class="w-full h-40 touch-none cursor-crosshair"
                            @mousedown="startDrawing"
                            @mousemove="draw"
                            @mouseup="stopDrawing"
                            @touchstart="startDrawing"
                            @touchmove="draw"
                            @touchend="stopDrawing"
                        ></canvas>
                        <button @click="clearSignature" class="absolute top-2 right-2 text-[10px] bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 px-2 py-1 rounded transition-colors uppercase font-bold">
                            Limpiar
                        </button>
                    </div>

                    <div class="bg-blue-500/5 border border-blue-500/10 p-4 rounded-xl">
                        <p class="text-[10px] text-blue-300/70 text-justify leading-relaxed">
                            <strong>CONSENTIMIENTO EXPRESO:</strong> Al firmar digitalmente, autorizo a {{ company_name }} el tratamiento de mi firma y datos para fines de cumplimiento de la NOM-035-STPS-2018. Confirmo que las respuestas presentadas arriba son veraces y corresponden a mi situación personal.
                        </p>
                    </div>

                    <button 
                        @click="saveSignature" 
                        :disabled="!hasSignature || form.processing"
                        class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-2xl font-bold transition-all shadow-lg shadow-emerald-900/20 flex items-center justify-center gap-2"
                    >
                        <font-awesome-icon v-if="form.processing" icon="spinner" spin />
                        {{ form.processing ? 'Guardando...' : 'Registrar Firma y Concluir' }}
                    </button>
                </div>

                <!-- DOWNLOAD SECTION (Only if signed) -->
                <div v-else class="space-y-4 animate-in fade-in slide-in-from-top duration-500">
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-center">
                        <p class="text-xs text-emerald-400 font-bold flex items-center justify-center gap-2">
                            <font-awesome-icon icon="check-circle" />
                            Expediente Firmado y Certificado
                        </p>
                    </div>
                    
                    <a :href="route('nom035.resultados.pdf', respondent.uuid)" target="_blank" class="w-full py-4 bg-white hover:bg-slate-100 text-slate-900 rounded-2xl font-black text-sm flex items-center justify-center gap-3 transition-all shadow-xl shadow-white/5">
                        <font-awesome-icon icon="file-pdf" />
                        Descargar Constancia Oficial (PDF)
                    </a>
                </div>

                <Link :href="route('nom035.index')" class="block w-full py-4 bg-slate-800/50 hover:bg-slate-800 text-slate-300 rounded-2xl font-bold text-sm text-center transition-all">
                    Finalizar y Salir
                </Link>
            </div>

            <div class="p-6 bg-slate-950/50 border-t border-slate-800 text-center">
                <p class="text-[10px] text-slate-600 uppercase tracking-widest font-bold">Autenticación Digital SHA-256</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
