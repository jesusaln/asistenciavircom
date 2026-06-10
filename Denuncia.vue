<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    success: Boolean,
    folio: String,
    receipt_url: String,
})

const form = ref({
    type: '',
    description: '',
    incident_date: '',
    is_anonymous: false,
    reporter_name: '',
    reporter_email: '',
})

const evidenceFiles = ref([])
const submitting = ref(false)
const errors = ref({})

const showForm = computed(() => !props.success)

const submit = () => {
    submitting.value = true
    errors.value = {}

    const formData = new FormData()
    formData.append('type', form.value.type)
    formData.append('description', form.value.description)
    formData.append('incident_date', form.value.incident_date || '')
    formData.append('is_anonymous', form.value.is_anonymous ? '1' : '0')
    formData.append('reporter_name', form.value.reporter_name || '')
    formData.append('reporter_email', form.value.reporter_email || '')

    evidenceFiles.value.forEach((file) => {
        formData.append('evidence[]', file)
    })

    router.post(route('nom035.denuncia.submit'), formData, {
        onSuccess: () => { submitting.value = false },
        onError: (err) => { errors.value = err; submitting.value = false },
    })
}

const onFileChange = (e) => {
    evidenceFiles.value = Array.from(e.target.files)
}
</script>

<template>
    <Head title="Buzón de Denuncias - NOM-035" />

    <div class="min-h-screen bg-[#0a0f18] flex flex-col items-center justify-center p-4">
        <!-- Success State -->
        <div v-if="success" class="max-w-lg w-full bg-[#111827] rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden border border-slate-800 p-8 text-center">
            <div class="h-16 w-16 bg-green-500/10 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <font-awesome-icon icon="check-circle" class="text-3xl text-green-400" />
            </div>
            <h1 class="text-2xl font-black text-white mb-2">Denuncia Registrada</h1>
            <p class="text-slate-400 mb-6">Tu denuncia ha sido recibida de forma confidencial.</p>

            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 mb-6">
                <p class="text-xs text-slate-500 uppercase tracking-wider font-bold mb-1">Folio de Seguimiento</p>
                <p class="text-3xl font-black text-purple-400 font-mono">{{ folio }}</p>
            </div>

            <p class="text-sm text-slate-400 mb-6">Guarda este folio para dar seguimiento a tu denuncia.</p>

            <a :href="receipt_url" class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl font-bold text-sm hover:bg-purple-700 transition-colors">
                <font-awesome-icon icon="download" />
                Descargar Acuse PDF
            </a>
        </div>

        <!-- Form State -->
        <div v-else class="max-w-xl w-full bg-[#111827] rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden border border-slate-800">
            <div class="p-8 bg-gradient-to-br from-red-600 to-orange-900 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="h-14 w-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center mb-6 border border-white/20">
                        <font-awesome-icon icon="shield-haltered" class="text-2xl" />
                    </div>
                    <h1 class="text-3xl font-black mb-1 tracking-tight">Buzón de Denuncias</h1>
                    <p class="text-red-200/80 font-medium text-sm">Mecanismo confidencial NOM-035-STPS-2018</p>
                </div>
            </div>

            <div class="p-8 space-y-5">
                <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4 text-sm text-slate-300">
                    <p class="font-bold mb-1">Este buzón es confidencial</p>
                    <p class="text-slate-400">Puedes reportar situaciones de violencia laboral, condiciones inseguras, acoso u otras situaciones que afecten tu bienestar. Tu denuncia será tratada con estricta confidencialidad.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tipo de situación *</label>
                    <select v-model="form.type" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:ring-2 focus:ring-red-500/30 transition-all">
                        <option value="" disabled>Selecciona un tipo...</option>
                        <option value="violencia">Violencia Laboral</option>
                        <option value="condiciones">Condiciones Inseguras</option>
                        <option value="acoso">Acoso / Hostigamiento</option>
                        <option value="otro">Otro</option>
                    </select>
                    <p v-if="errors.type" class="text-red-400 text-xs mt-1">{{ errors.type }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Descripción *</label>
                    <textarea v-model="form.description" rows="5" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:ring-2 focus:ring-red-500/30 transition-all" placeholder="Describe la situación con el mayor detalle posible..."></textarea>
                    <p v-if="errors.description" class="text-red-400 text-xs mt-1">{{ errors.description }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Fecha del incidente</label>
                    <input v-model="form.incident_date" type="date" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:ring-2 focus:ring-red-500/30 transition-all" />
                </div>

                <div class="flex items-center gap-3 bg-slate-800/30 border border-slate-700 rounded-xl p-4">
                    <input v-model="form.is_anonymous" type="checkbox" id="anon" class="w-5 h-5 rounded accent-red-600" />
                    <label for="anon" class="text-sm text-slate-300 cursor-pointer select-none">Quiero permanecer anónimo(a)</label>
                </div>

                <template v-if="!form.is_anonymous">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Nombre completo *</label>
                        <input v-model="form.reporter_name" type="text" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:ring-2 focus:ring-red-500/30 transition-all" placeholder="Tu nombre" />
                        <p v-if="errors.reporter_name" class="text-red-400 text-xs mt-1">{{ errors.reporter_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Correo electrónico *</label>
                        <input v-model="form.reporter_email" type="email" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:ring-2 focus:ring-red-500/30 transition-all" placeholder="correo@ejemplo.com" />
                        <p v-if="errors.reporter_email" class="text-red-400 text-xs mt-1">{{ errors.reporter_email }}</p>
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Evidencia (PDF, JPG, PNG)</label>
                    <input @change="onFileChange" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white file:text-xs file:font-bold hover:file:bg-slate-600 transition-all" />
                    <p v-if="errors.evidence" class="text-red-400 text-xs mt-1">{{ errors.evidence }}</p>
                </div>

                <button @click="submit" :disabled="submitting" class="w-full px-6 py-4 bg-red-600 text-white rounded-xl font-black text-sm hover:bg-red-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                    <font-awesome-icon :icon="submitting ? 'spinner' : 'paper-plane'" :spin="submitting" />
                    {{ submitting ? 'Enviando...' : 'Enviar Denuncia' }}
                </button>
            </div>
        </div>
    </div>
</template>
