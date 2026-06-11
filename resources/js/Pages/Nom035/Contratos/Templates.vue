<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    templates: Array
});

const notyf = new Notyf();

const deleteTemplate = async (id) => {
    const result = await Swal.fire({
        title: 'Eliminar plantilla',
        text: '¿Eliminar esta plantilla?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('contratos.plantillas.destroy', id), {
            onSuccess: () => notyf.success('Plantilla eliminada')
        })
    }
}
</script>

<template>
    <AppLayout title="Plantillas de Contratos">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('comisiones.repse')" class="p-2 bg-slate-100 rounded-lg text-slate-500">
                        <font-awesome-icon icon="arrow-left" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Plantillas de Contratos</h2>
                        <p class="text-sm text-slate-500 mt-1">Configura los textos legales para firma electrónica.</p>
                    </div>
                </div>
                <Link :href="route('contratos.plantillas.create')" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-xs shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all">
                    NUEVA PLANTILLA
                </Link>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 xl:px-12 w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="t in templates" :key="t.id" class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm hover:shadow-xl hover:border-indigo-200 transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                            <font-awesome-icon icon="file-signature" class="text-xl" />
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('contratos.plantillas.edit', t.id)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                <font-awesome-icon icon="edit" />
                            </Link>
                            <button @click="deleteTemplate(t.id)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                <font-awesome-icon icon="trash" />
                            </button>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-black text-slate-800 uppercase leading-tight mb-2">{{ t.nombre }}</h3>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-[10px] px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full font-bold uppercase">{{ t.tipo }}</span>
                        <span class="text-[10px] text-slate-400 font-bold">{{ t.vigencia_meses }} meses</span>
                    </div>

                    <p class="text-xs text-slate-500 line-clamp-3 mb-6 leading-relaxed italic">
                        {{ t.contenido.replace(/<[^>]*>?/gm, '').substring(0, 150) }}...
                    </p>

                    <Link :href="route('contratos.plantillas.edit', t.id)" class="w-full py-3 bg-slate-50 text-slate-600 rounded-xl font-black text-[10px] uppercase tracking-widest group-hover:bg-indigo-600 group-hover:text-white transition-all text-center block">
                        EDITAR CONTENIDO
                    </Link>
                </div>

                <!-- Empty state -->
                <div v-if="templates.length === 0" class="col-span-full py-20 flex flex-col items-center justify-center opacity-30 italic">
                    <font-awesome-icon icon="folder-open" class="text-6xl mb-4" />
                    <p>No hay plantillas creadas. Comienza creando una nueva.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
