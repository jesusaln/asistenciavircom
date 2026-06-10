<script setup>
import { ref } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import FormCard from '@/Components/FormCard.vue'
import FormField from '@/Components/FormField.vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const form = ref({ nombre: '', activo: true })
const errors = ref({})

const submit = () => {
    router.post(route('marcas.store'), form.value, {
        onSuccess: () => { form.value = { nombre: '', activo: true } },
        onError: (err) => { errors.value = err },
    })
}

const cancel = () => router.get(route('marcas.index'))
</script>

<template>
    <Head title="Nueva Marca" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Nueva Marca" subtitle="Registra una nueva marca de producto">
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
                    <FormField
                        id="nombre"
                        v-model="form.nombre"
                        label="Nombre"
                        placeholder="Nombre de la marca"
                        :error="errors.nombre"
                        required
                    />

                    <div class="flex items-center gap-3">
                        <button type="button" @click="form.activo = !form.activo"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-all duration-200"
                            :class="form.activo ? 'bg-brand-500' : 'bg-slate-300 dark:bg-slate-600'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                :class="form.activo ? 'translate-x-6' : 'translate-x-1'" />
                        </button>
                        <span class="text-sm text-slate-700 dark:text-slate-300">Activo</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button type="button" @click="cancel"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-all duration-200">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 rounded-xl transition-all duration-200 shadow-sm">
                            Guardar Marca
                        </button>
                    </div>
                </form>
            </FormCard>
        </div>
    </div>
</template>
