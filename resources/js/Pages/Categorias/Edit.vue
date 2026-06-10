<script setup>
import { ref, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import FormCard from '@/Components/FormCard.vue'
import FormField from '@/Components/FormField.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false },
        { type: 'warning', background: '#f59e0b', icon: false }
    ]
})

const page = usePage()
onMounted(() => {
    const flash = page.props.flash
    if (flash?.success) notyf.success(flash.success)
    if (flash?.error) notyf.error(flash.error)
})

const props = defineProps({ categoria: { type: Object, required: true } })
const loading = ref(false)
const errors = ref({})

const form = ref({
    nombre: props.categoria.nombre || '',
    descripcion: props.categoria.descripcion || '',
    estado: props.categoria.estado || 'activo',
})

const submit = () => {
    loading.value = true
    router.put(route('categorias.update', props.categoria.id), form.value, {
        onSuccess: () => {
            notyf.success('Categoría actualizada')
            router.visit(route('categorias.index'))
        },
        onError: (err) => { errors.value = err; notyf.error('Error al actualizar') },
        onFinish: () => { loading.value = false },
    })
}

const cancel = () => router.visit(route('categorias.index'))
</script>

<template>
    <Head title="Editar Categoría" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Editar Categoría" subtitle="Modifica la información de la categoría">
                <template #actions>
                    <button @click="cancel"
                        class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Cancelar
                    </button>
                </template>
            </CrudPageHeader>

            <FormCard>
                <form @submit.prevent="submit" class="space-y-6">
                    <FormField id="nombre" v-model="form.nombre" label="Nombre de la Categoría" placeholder="Nombre de la categoría" :error="errors.nombre" required />

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Descripción</label>
                        <textarea v-model="form.descripcion" rows="3"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all resize-none"
                            placeholder="Descripción opcional" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Estado</label>
                        <select v-model="form.estado"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/30 rounded-xl p-4">
                        <h3 class="text-sm font-medium text-sky-800 dark:text-sky-200 mb-2">Información del registro</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-sky-700 dark:text-sky-300">
                            <div>Creado: <span class="font-medium">{{ new Date(categoria.created_at).toLocaleDateString('es-MX') }}</span></div>
                            <div>Actualizado: <span class="font-medium">{{ new Date(categoria.updated_at).toLocaleDateString('es-MX') }}</span></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button type="button" @click="cancel"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="loading"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 rounded-xl transition-all duration-200 shadow-sm disabled:opacity-50 inline-flex items-center gap-2">
                            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </FormCard>
        </div>
    </div>
</template>
