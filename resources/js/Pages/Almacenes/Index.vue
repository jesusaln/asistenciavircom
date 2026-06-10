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
    almacenes: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'nombre', label: 'Nombre' },
    { key: 'direccion', label: 'Dirección', format: (v) => v || 'Sin dirección' },
    { key: 'responsable', label: 'Responsable', format: (_, row) => row.responsable?.name || 'Sin asignar' },
    { key: 'total_articulos', label: 'Artículos', format: (v) => `${v || 0} pzas` },
    { key: 'estado', label: 'Estado', format: (v) => v === 'activo' ? 'Activo' : 'Inactivo' },
]

const onSearch = () => {
    router.get(route('almacenes.index'), { search: search.value }, { preserveState: true, replace: true })
}

const confirmDelete = async (almacen) => {
    const { isConfirmed } = await Swal.fire({ title: '¿Eliminar este almacén?', text: 'Esta acción no se puede deshacer.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
    if (!isConfirmed) return
    router.delete(route('almacenes.destroy', almacen.id), {
        onSuccess: () => notyf.success('Almacén eliminado'),
        onError: () => notyf.error('Error al eliminar'),
    })
}

const toggleStatus = (almacen) => {
    const nuevoEstado = almacen.estado === 'activo' ? 'inactivo' : 'activo'
    router.put(route('almacenes.update', almacen.id), { estado: nuevoEstado }, {
        preserveScroll: true,
        onSuccess: () => notyf.success('Estado actualizado'),
        onError: () => notyf.error('Error al actualizar'),
    })
}
</script>

<template>
    <Head title="Almacenes" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Almacenes" subtitle="Gestión de almacenes">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <input v-model="search" @keyup.enter="onSearch" type="text" placeholder="Buscar..."
                                class="w-48 lg:w-64 px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all" />
                        </div>
                        <Link :href="route('almacenes.create')"
                            class="inline-flex items-center px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nuevo Almacén
                        </Link>
                    </div>
                </template>
            </CrudPageHeader>

            <IndexTable
                :columns="columns"
                :rows="almacenes.data || []"
                empty-text="No hay almacenes registrados"
                empty-subtext="Crea el primer almacén usando el botón Nuevo Almacén"
            >
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1.5">
                        <Link :href="route('almacenes.show', row.id)"
                            class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30"
                            title="Ver Auditoría Unificada">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </Link>
                        <Link :href="route('almacenes.edit', row.id)"
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
                    <div v-if="almacenes.links" class="flex justify-between items-center">
                        <div class="text-sm text-slate-500">
                            Mostrando {{ almacenes.from || 0 }} - {{ almacenes.to || 0 }} de {{ almacenes.total || 0 }}
                        </div>
                        <div class="flex gap-1.5">
                            <Link v-for="(link, i) in almacenes.links" :key="i"
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
