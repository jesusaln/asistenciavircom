<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    company_name: String,
    center_type: String,
    prefilled_employee: Object
})

const form = ref({
    email: props.prefilled_employee?.user?.email || '',
    name: props.prefilled_employee?.nombre || '',
    department: props.prefilled_employee?.departamento || '',
    position: props.prefilled_employee?.puesto || '',
    shift: '',
    accepted_privacy: false
})

const submit = () => {
    router.post(route('nom035.questionnaire.start'), form.value)
}
</script>

<template>
    <Head title="Bienvenido a la Evaluación NOM-035" />
    
    <div class="min-h-screen bg-[#0a0f18] flex flex-col items-center justify-center p-4 selection:bg-purple-500/30">
        <div class="max-w-xl w-full bg-[#111827] rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden border border-slate-800 animate-in fade-in zoom-in duration-500">
            <!-- Header -->
            <div class="p-8 bg-gradient-to-br from-purple-600 to-indigo-900 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="h-14 w-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center mb-6 border border-white/20">
                        <font-awesome-icon icon="brain" class="text-2xl" />
                    </div>
                    <h1 class="text-3xl font-black mb-1 tracking-tight">Evaluación NOM-035</h1>
                    <p class="text-purple-200/80 font-medium text-sm">Factores de Riesgo Psicosocial en el Trabajo</p>
                </div>
                <!-- Abstract patterns -->
                <div class="absolute top-0 right-0 h-full w-1/3 bg-white/5 -skew-x-12 translate-x-1/2"></div>
            </div>

            <div class="p-8 space-y-8">
                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-white">Tu bienestar es nuestra prioridad</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Esta evaluación tiene como objetivo identificar, analizar y prevenir los factores de riesgo psicosocial, 
                        así como promover un entorno organizacional favorable en <strong class="text-purple-400">{{ company_name }}</strong>.
                    </p>
                    <div class="flex gap-3 items-start p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl text-blue-300 text-[11px] italic">
                        <font-awesome-icon icon="info-circle" class="mt-0.5" />
                        <span>Tus respuestas son confidenciales y serán analizadas de forma estadística para mejorar nuestro entorno laboral.</span>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-500 ml-1">Correo Electrónico Laboral</label>
                            <input v-model="form.email" type="email" required placeholder="ejemplo@climasdeldesierto.com" 
                                class="w-full bg-[#1f2937] border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-purple-500/20 transition-all border outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-500 ml-1">Nombre Completo</label>
                            <input v-model="form.name" type="text" required placeholder="Tu nombre" 
                                class="w-full bg-[#1f2937] border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-purple-500/20 transition-all border outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-500 ml-1">Departamento</label>
                                <input v-model="form.department" type="text" required placeholder="Ej. Operaciones" 
                                    class="w-full bg-[#1f2937] border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-purple-500/20 transition-all border outline-none" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-500 ml-1">Turno</label>
                                <select v-model="form.shift" required class="w-full bg-[#1f2937] border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-purple-500/20 transition-all border outline-none">
                                    <option value="" disabled class="bg-[#1f2937]">Selecciona...</option>
                                    <option value="Matutino" class="bg-[#1f2937]">Matutino</option>
                                    <option value="Vespertino" class="bg-[#1f2937]">Vespertino</option>
                                    <option value="Nocturno" class="bg-[#1f2937]">Nocturno</option>
                                    <option value="Mixto" class="bg-[#1f2937]">Mixto</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <input v-model="form.accepted_privacy" type="checkbox" required class="mt-1 h-4 w-4 bg-[#1f2937] border-slate-700 text-purple-600 rounded focus:ring-purple-500/50" />
                        <p class="text-[11px] text-slate-400 leading-tight">
                            He leído y acepto el aviso de privacidad. Entiendo que mis datos serán tratados conforme a la NOM-035-STPS-2018.
                        </p>
                    </div>

                    <button type="submit" class="w-full py-4 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl font-black text-sm shadow-xl shadow-purple-500/20 transition-all transform active:scale-[0.98]">
                        Comenzar Evaluación
                    </button>
                </form>
            </div>
            
            <!-- Footer info -->
            <div class="p-6 bg-slate-900/50 border-t border-slate-800 text-center">
                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">STPS - Secretaría del Trabajo y Previsión Social</p>
            </div>
        </div>
        
        <p class="mt-8 text-slate-500 text-[10px] font-medium">© {{ new Date().getFullYear() }} {{ company_name }} · Desarrollado por Antigravity</p>
    </div>
</template>
