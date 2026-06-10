<script setup>
import { ref, onMounted } from 'vue'
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
    proveedores: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'nombre_razon_social', label: 'Nombre/Razón Social' },
    { key: 'rfc', label: 'RFC', format: (v) => v || '-' },
    { key: 'email', label: 'Email', format: (v) => v || '-' },
    { key: 'telefono', label: 'Teléfono', format: (v) => v || '-' },
    { key: 'created_at', label: 'Creado', format: (v) => v ? new Date(v).toLocaleDateString('es-MX') : '-' },
    { key: 'is_repse', label: 'REPSE', format: (v) => v ? '✅' : '❌' },
    { key: 'activo', label: 'Estado', format: (v) => v !== false ? 'Activo' : 'Inactivo' },
]

const toggleRepse = (proveedor) => {
    router.post(route('comisiones.repse.toggle', proveedor.id), {}, {
        preserveScroll: true,
        onSuccess: () => notyf.success('Estatus REPSE actualizado'),
    })
}

const onSearch = () => {
    router.get(route('proveedores.index'), { search: search.value }, { preserveState: true, replace: true })
}

const confirmDelete = async (proveedor) => {
    const { isConfirmed } = await Swal.fire({ title: '¿Eliminar proveedor?', text: '¿Eliminar este proveedor?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
    if (!isConfirmed) return
    router.delete(route('proveedores.destroy', proveedor.id), {
        onSuccess: () => notyf.success('Proveedor eliminado'),
        onError: () => notyf.error('Error al eliminar'),
    })
}

const toggleStatus = (proveedor) => {
    router.put(route('proveedores.toggle', proveedor.id), {}, {
        preserveScroll: true,
        onSuccess: () => notyf.success('Estado actualizado'),
        onError: () => notyf.error('Error al actualizar'),
    })
}
</script>

<template>
    <Head title="Proveedores" />
    <div class="min-h-screen">
        <div class="w-full px-4 sm:px-6 py-6">
            <CrudPageHeader title="Proveedores" subtitle="Gestión de proveedores">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <input v-model="search" @keyup.enter="onSearch" type="text" placeholder="Buscar..."
                                class="w-48 lg:w-64 px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all" />
                        </div>
                        <Link :href="route('proveedores.create')"
                            class="inline-flex items-center px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nuevo Proveedor
                        </Link>
                    </div>
                </template>
            </CrudPageHeader>

            <IndexTable
                :columns="columns"
                :rows="proveedores.data || []"
                empty-text="No hay proveedores registrados"
                empty-subtext="Crea el primer proveedor usando el botón Nuevo Proveedor"
            >
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1.5">
                        <button @click="toggleStatus(row)"
                            class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200"
                            :class="row.activo !== false
                                ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'
                                : 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-600'"
                            :title="row.activo !== false ? 'Desactivar' : 'Activar'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242m-4.242-4.242L9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                        <button @click="toggleRepse(row)"
                             class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200"
                             :class="row.is_repse
                                 ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30'
                                 : 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-600'"
                             :title="row.is_repse ? 'Quitar de REPSE' : 'Marcar como REPSE'">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                             </svg>
                         </button>
                        <Link :href="route('proveedores.edit', row.id)"
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
                    <div v-if="proveedores.links" class="flex justify-between items-center">
                        <div class="text-sm text-slate-500">
                            Mostrando {{ proveedores.from || 0 }} - {{ proveedores.to || 0 }} de {{ proveedores.total || 0 }}
                        </div>
                        <div class="flex gap-1.5">
                            <Link v-for="(link, i) in proveedores.links" :key="i"
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
