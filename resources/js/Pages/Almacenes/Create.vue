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
const usuarios = ref(page.props.usuarios || [])

onMounted(() => {
    const flash = page.props.flash
    if (flash?.success) notyf.success(flash.success)
    if (flash?.error) notyf.error(flash.error)
})

const loading = ref(false)
const errors = ref({})
const form = ref({ nombre: '', descripcion: '', direccion: '', telefono: '', responsable: '', estado: 'activo' })

const submit = () => {
    loading.value = true
    router.post(route('almacenes.store'), form.value, {
        onSuccess: () => { notyf.success('Almacén creado'); router.visit(route('almacenes.index')) },
        onError: (err) => { errors.value = err; notyf.error('Error al crear') },
        onFinish: () => { loading.value = false },
    })
}

const cancel = () => router.visit(route('almacenes.index'))
</script>

<template>
    <Head title="Nuevo Almacén" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Nuevo Almacén" subtitle="Agrega un nuevo almacén al sistema">
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
                    <FormField id="nombre" v-model="form.nombre" label="Nombre del Almacén" placeholder="Nombre del almacén" :error="errors.nombre" required />

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Descripción</label>
                        <textarea v-model="form.descripcion" rows="3"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all resize-none"
                            placeholder="Descripción opcional" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Dirección</label>
                        <textarea v-model="form.direccion" rows="2"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all resize-none"
                            placeholder="Dirección completa del almacén" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField id="telefono" v-model="form.telefono" label="Teléfono" placeholder="Teléfono de contacto" :error="errors.telefono" />
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Responsable</label>
                            <select v-model="form.responsable"
                                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all">
                                <option value="">Seleccionar responsable</option>
                                <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
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
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 text-blue-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm text-sky-800 dark:text-sky-200">
                                <p class="font-medium mb-1">Información importante</p>
                                <ul class="list-disc pl-4 space-y-0.5 text-xs">
                                    <li>El nombre del almacén debe ser único</li>
                                    <li>Los almacenes inactivos no estarán disponibles</li>
                                    <li>Asigna un responsable para el control del almacén</li>
                                </ul>
                            </div>
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
                            {{ loading ? 'Creando...' : 'Crear Almacén' }}
                        </button>
                    </div>
                </form>
            </FormCard>
        </div>
    </div>
</template>
