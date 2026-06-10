<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import IndexTable from '@/Components/IndexTable.vue'
import Swal from '@/Utils/Swal'
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

const props = defineProps({
    categorias: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'nombre', label: 'Nombre' },
    { key: 'descripcion', label: 'Descripción', format: (v) => v || 'Sin descripción' },
    { key: 'created_at', label: 'Creado', format: (v) => v ? new Date(v).toLocaleDateString('es-MX') : '-' },
    { key: 'estado', label: 'Estado', format: (v) => v === 'activo' ? 'Activo' : 'Inactivo' },
]

const onSearch = () => {
    router.get(route('categorias.index'), { search: search.value }, { preserveState: true, replace: true })
}

const confirmDelete = async (categoria) => {
    const { isConfirmed } = await Swal.fire({ title: '¿Eliminar categoría?', text: '¿Eliminar esta categoría?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
    if (!isConfirmed) return
    router.delete(route('categorias.destroy', categoria.id), {
        onSuccess: () => notyf.success('Categoría eliminada'),
        onError: () => notyf.error('Error al eliminar'),
    })
}

const toggleStatus = (categoria) => {
    const nuevoEstado = categoria.estado === 'activo' ? 'inactivo' : 'activo'
    router.put(route('categorias.update', categoria.id), { estado: nuevoEstado }, {
        preserveScroll: true,
        onSuccess: () => notyf.success('Estado actualizado'),
        onError: () => notyf.error('Error al actualizar'),
    })
}
</script>

<template>
    <Head title="Categorías" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Categorías" subtitle="Gestión de categorías de productos">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <input v-model="search" @keyup.enter="onSearch" type="text" placeholder="Buscar..."
                                class="w-48 lg:w-64 px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all" />
                        </div>
                        <Link :href="route('categorias.create')"
                            class="inline-flex items-center px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nueva Categoría
                        </Link>
                    </div>
                </template>
            </CrudPageHeader>

            <IndexTable
                :columns="columns"
                :rows="categorias.data || []"
                empty-text="No hay categorías registradas"
                empty-subtext="Crea la primera categoría usando el botón Nueva Categoría"
            >
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1.5">
                        <button @click="toggleStatus(row)"
                            class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200"
                            :class="row.estado === 'activo'
                                ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'
                                : 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-600'"
                            :title="row.estado === 'activo' ? 'Desactivar' : 'Activar'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242m-4.242-4.242L9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                        <Link :href="route('categorias.edit', row.id)"
                            class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/30"
                            title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>
                        <button @click="confirmDelete(row)"
                            class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30"
                            title="Eliminar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>
                <template #pagination>
                    <div v-if="categorias.links" class="flex justify-between items-center">
                        <div class="text-sm text-slate-500">
                            Mostrando {{ categorias.from || 0 }} - {{ categorias.to || 0 }} de {{ categorias.total || 0 }}
                        </div>
                        <div class="flex gap-1.5">
                            <Link v-for="(link, i) in categorias.links" :key="i"
                                :href="link.url || '#'"
                                v-html="link.label"
                                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-150"
                                :class="link.active
                                    ? 'bg-brand-500 text-white'
                                    : link.url ? 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' : 'text-slate-300 cursor-default'" />
                        </div>
                    </div>
                </template>
            </IndexTable>
        </div>
    </div>
</template>
