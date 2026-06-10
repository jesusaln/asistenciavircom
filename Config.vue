<script setup>
import { ref } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

const notyf = new Notyf({ duration: 4000 })

const props = defineProps({
    config: Object
})

const policy_content = ref(props.config.policy_content || '')
const saving = ref(false)
const uploading = ref(false)

const savePolicy = () => {
    saving.value = true
    router.put(route('nom035.config.policy.update'), {
        policy_content: policy_content.value
    }, {
        onSuccess: () => {
            saving.value = false
            notyf.success('Política actualizada correctamente')
        },
        onError: () => saving.value = false
    })
}

const uploadPdf = (e) => {
    const file = e.target.files[0]
    if (!file) return

    uploading.value = true
    const formData = new FormData()
    formData.append('policy_pdf', file)

    router.post(route('nom035.config.policy-pdf.upload'), formData, {
        onSuccess: () => {
            uploading.value = false
            notyf.success('PDF firmado subido correctamente')
        },
        onError: (err) => {
            uploading.value = false
            notyf.error(err.policy_pdf || 'Error al subir el archivo')
        }
    })
}
</script>

<template>
    <AppLayout title="NOM-035 - Configuración y Política">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('nom035.index')" class="p-2 bg-[var(--ui-surface-soft)] rounded-lg text-[var(--ui-text-soft)] hover:text-purple-500 transition-colors">
                    <font-awesome-icon icon="arrow-left" />
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-[var(--ui-text-main)] leading-tight">Configuración NOM-035</h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Gestión de la política de prevención y normativas.</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-[var(--ui-border)] flex items-center justify-between bg-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-600">
                                <font-awesome-icon icon="file-signature" class="text-xl" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-[var(--ui-text-main)]">Política de Prevención</h3>
                                <p class="text-xs text-[var(--ui-text-soft)] font-bold uppercase tracking-widest mt-1">Obligatorio por la STPS</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex gap-3 items-start dark:bg-blue-900/20 dark:border-blue-800">
                            <font-awesome-icon icon="info-circle" class="text-blue-500 mt-0.5" />
                            <div class="text-sm text-blue-800 dark:text-blue-300">
                                <p class="font-bold">¿Qué es esto?</p>
                                <p class="opacity-90">La política debe establecer el compromiso de la empresa para prevenir factores de riesgo psicosocial y promover un entorno favorable. Este texto será visible para todos los empleados desde su aplicación móvil.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-xs font-black text-[var(--ui-text-soft)] uppercase tracking-wider">Contenido de la Política</label>
                            <textarea 
                                v-model="policy_content" 
                                rows="15" 
                                class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-2xl px-6 py-4 text-[var(--ui-text-main)] focus:ring-2 focus:ring-purple-500/20 transition-all text-sm leading-relaxed"
                                placeholder="Escribe aquí la política oficial de la empresa..."
                            ></textarea>
                        </div>

                        <div class="border-t border-[var(--ui-border)] pt-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-600">
                                    <font-awesome-icon icon="file-pdf" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-[var(--ui-text-main)]">Política Firmada (PDF)</p>
                                    <p class="text-xs text-[var(--ui-text-soft)]">{{ props.config.policy_pdf_path ? 'PDF firmado disponible' : 'No se ha subido el documento firmado' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <label class="relative cursor-pointer">
                                    <input type="file" accept=".pdf" @change="uploadPdf" class="absolute inset-0 opacity-0 w-full cursor-pointer" />
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-xl font-bold text-xs hover:bg-purple-700 transition-colors">
                                        <font-awesome-icon :icon="uploading ? 'spinner' : 'upload'" :spin="uploading" />
                                        {{ uploading ? 'Subiendo...' : 'Subir PDF Firmado' }}
                                    </span>
                                </label>
                                <a v-if="props.config.policy_pdf_path" :href="route('nom035.config.policy-pdf.download')" class="inline-flex items-center gap-2 px-4 py-2 bg-[var(--ui-surface-soft)] text-[var(--ui-text-main)] rounded-xl font-bold text-xs border border-[var(--ui-border)] hover:bg-[var(--ui-border)] transition-colors">
                                    <font-awesome-icon icon="download" />
                                    Descargar PDF Actual
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <p class="text-[10px] text-[var(--ui-text-soft)] max-w-xs leading-tight">
                                Al guardar, los empleados recibirán una notificación automática informando sobre la actualización de la política.
                            </p>
                            <button 
                                @click="savePolicy" 
                                :disabled="saving"
                                class="px-8 py-3 bg-purple-600 text-white rounded-xl font-black text-sm shadow-xl shadow-purple-500/20 hover:bg-purple-700 transition-all disabled:opacity-50 flex items-center gap-2"
                            >
                                <font-awesome-icon :icon="saving ? 'spinner' : 'save'" :spin="saving" />
                                {{ saving ? 'Guardando...' : 'Guardar y Difundir' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
