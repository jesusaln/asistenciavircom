<template>
    <Head title="Metas de Empleados" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-brand-500/30">
                        <FontAwesomeIcon :icon="['fas', 'bullseye']" class="h-6 w-6" />
                    </div>
                    Metas de Empleados
                </h1>
                <p class="text-slate-500 mt-2">Administra las metas diarias de tu equipo de ventas</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/crm/metas/exportar" class="px-4 py-2 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 bg-emerald-100 rounded-xl hover:bg-emerald-200 flex items-center gap-2" title="Exportar datos para IA">
                    <FontAwesomeIcon :icon="['fas', 'download']" />
                    Exportar CSV
                </a>
                <button @click="showModalImport = true" class="px-4 py-2 text-sky-800 dark:text-sky-200 bg-sky-100 rounded-xl hover:bg-blue-200 flex items-center gap-2" title="Importar metas desde CSV">
                    <FontAwesomeIcon :icon="['fas', 'upload']" />
                    Importar CSV
                </button>
                <Link href="/crm" class="px-4 py-2 text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="mr-2" />
                    Volver
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Panel de Asignación de Metas -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-brand-50 to-white flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Asignar Metas</h3>
                        <button @click="showModalNueva = true" class="px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'plus']" />
                            Nueva Meta
                        </button>
                    </div>
                    
                    <!-- Tabla de Metas Actuales -->
                    <div class="p-6">
                        <div v-if="metas.length" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                        <th class="pb-3">Vendedor</th>
                                        <th class="pb-3">Tipo</th>
                                        <th class="pb-3 text-center">Meta Diaria</th>
                                        <th class="pb-3 text-center">Progreso Hoy</th>
                                        <th class="pb-3 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr v-for="meta in metas" :key="meta.id" class="hover:bg-white">
                                        <td class="py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white flex items-center justify-center font-bold text-sm">
                                                    {{ getInitials(meta.user?.name) }}
                                                </div>
                                                <span class="font-medium text-slate-900">{{ meta.user?.name }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4">
                                            <span class="px-2.5 py-1 rounded-xl text-xs font-medium"
                                                  :class="meta.tipo === 'actividades' ? 'bg-sky-100 text-sky-800 dark:text-sky-200' : 'bg-purple-100 text-purple-700'">
                                                {{ meta.tipo_label }}
                                            </span>
                                        </td>
                                        <td class="py-4 text-center">
                                            <span class="text-lg font-bold text-slate-800">{{ meta.meta_diaria }}</span>
                                        </td>
                                        <td class="py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                                                    <div :class="meta.progreso.cumplida ? 'bg-brand-500' : 'bg-brand-500'"
                                                         :style="{ width: meta.progreso.porcentaje + '%' }"
                                                         class="h-full transition-all duration-200"></div>
                                                </div>
                                                <span class="text-sm font-medium" :class="meta.progreso.cumplida ? 'text-emerald-600' : 'text-slate-500'">
                                                    {{ meta.progreso.realizado }}/{{ meta.progreso.meta }}
                                                </span>
                                                <FontAwesomeIcon v-if="meta.progreso.cumplida" :icon="['fas', 'check-circle']" class="text-emerald-500" />
                                            </div>
                                        </td>
                                        <td class="py-4 text-center">
                                            <button @click="editarMeta(meta)" class="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-50 rounded-xl">
                                                <FontAwesomeIcon :icon="['fas', 'edit']" />
                                            </button>
                                            <button @click="eliminarMeta(meta)" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-slate-50 rounded-xl">
                                                <FontAwesomeIcon :icon="['fas', 'trash']" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400">
                            <FontAwesomeIcon :icon="['fas', 'bullseye']" class="h-12 w-12 mb-4" />
                            <p class="text-lg font-medium">Sin metas configuradas</p>
                            <p class="text-sm mt-1">Crea la primera meta para tu equipo</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl-sm border border-slate-100 overflow-hidden sticky top-6">
                    <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-purple-50 to-white">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'trophy']" class="text-brand-500" />
                            Leaderboard Hoy
                        </h3>
                    </div>
                    <div class="p-4">
                        <div v-if="leaderboard.length" class="space-y-3">
                            <div v-for="(item, index) in leaderboard" :key="item.user_id"
                                 :class="index === 0 ? 'bg-brand-50 dark:bg-brand-900/20 border-brand-200 dark:border-brand-800/30' : index === 1 ? 'bg-slate-100 border-slate-200' : index === 2 ? 'bg-orange-50 border-orange-200' : 'bg-white border-slate-100'"
                                 class="flex items-center gap-2 p-3 rounded-xl border">
                                <!-- Posición -->
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm"
                                     :class="index === 0 ? 'bg-brand-500 text-white' : index === 1 ? 'bg-slate-400 text-white' : index === 2 ? 'bg-orange-400 text-white' : 'bg-slate-200 text-slate-500'">
                                    {{ index + 1 }}
                                </div>
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 truncate">{{ item.nombre }}</p>
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span>{{ item.actividades }} actividades</span>
                                        <span>•</span>
                                        <span>{{ item.prospectos }} prospectos</span>
                                    </div>
                                </div>
                                <!-- Metas cumplidas -->
                                <div class="text-right">
                                    <div class="text-lg font-bold" :class="item.porcentaje_cumplimiento === 100 ? 'text-emerald-600' : 'text-slate-800'">
                                        {{ item.porcentaje_cumplimiento }}%
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ item.metas_cumplidas }}/{{ item.total_metas }} metas
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center text-slate-400">
                            <FontAwesomeIcon :icon="['fas', 'medal']" class="h-8 w-8 mb-2" />
                            <p class="text-sm">Sin actividad hoy</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nueva/Editar Meta -->
        <div v-if="showModalNueva" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar" @click.self="showModalNueva = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900">{{ form.id ? 'Editar' : 'Nueva' }} Meta</h3>
                        <button @click="showModalNueva = false" class="text-slate-400 hover:text-amber-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="guardarMeta">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Vendedor *</label>
                                <select v-model="form.user_id" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                                    <option value="">Seleccionar vendedor...</option>
                                    <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Meta *</label>
                                <select v-model="form.tipo" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500">
                                    <option v-for="(label, key) in tiposMeta" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Meta Diaria *</label>
                                <input v-model.number="form.meta_diaria" type="number" min="1" max="100" required 
                                       class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500" />
                                <p class="text-xs text-slate-500 mt-1">Cuántas actividades o prospectos por día</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha Inicio</label>
                                    <input v-model="form.fecha_inicio" type="date" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha Fin</label>
                                    <input v-model="form.fecha_fin" type="date" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showModalNueva = false" class="px-4 py-2 text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                {{ form.id ? 'Actualizar' : 'Crear' }} Meta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Importar CSV -->
        <div v-if="showModalImport" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar" @click.self="showModalImport = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'upload']" class="text-blue-500" />
                            Importar Metas desde CSV
                        </h3>
                        <button @click="showModalImport = false" class="text-slate-400 hover:text-amber-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <!-- Instrucciones -->
                    <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/30 rounded-xl p-4 mb-6">
                        <h4 class="font-semibold text-sky-800 dark:text-sky-200 mb-2 flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'info-circle']" />
                            Flujo para usar con IA
                        </h4>
                        <ol class="text-sm text-sky-800 dark:text-sky-200 space-y-1 list-decimal list-inside">
                            <li>Haz clic en <strong>"Exportar CSV"</strong> para descargar datos actuales</li>
                            <li>Dale ese archivo a una IA (ChatGPT, Claude, etc.)</li>
                            <li>Pídele que sugiera metas basadas en el rendimiento</li>
                            <li>La IA te dará un CSV con el formato: <code class="bg-sky-100 px-1 rounded-xl">user_id,tipo,meta_diaria,fecha_inicio,fecha_fin</code></li>
                            <li>Sube ese archivo aquí</li>
                        </ol>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
                        <h4 class="font-semibold text-slate-700 mb-2">Formato esperado:</h4>
                        <pre class="text-xs bg-white p-2 rounded-xl border overflow-x-auto">user_id,tipo,meta_diaria,fecha_inicio,fecha_fin
1,actividades,10,2025-01-01,2025-12-31
2,prospectos,5,2025-01-01,2025-12-31</pre>
                    </div>

                    <form @submit.prevent="importarCSV">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Archivo CSV</label>
                            <input 
                                type="file" 
                                accept=".csv,.txt"
                                @change="archivoCSV = $event.target.files[0]"
                                class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                            />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showModalImport = false" class="px-4 py-2 text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="!archivoCSV || procesando" class="px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-blue-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Importar Metas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    vendedores: Array,
    metas: Array,
    tiposMeta: Object,
    leaderboard: Array,
});

const showModalNueva = ref(false);
const showModalImport = ref(false);
const archivoCSV = ref(null);
const procesando = ref(false);

const initForm = () => ({
    id: null,
    user_id: '',
    tipo: 'actividades',
    meta_diaria: 10,
    fecha_inicio: '',
    fecha_fin: '',
});

const form = ref(initForm());

const getInitials = (nombre) => {
    if (!nombre) return '?';
    const parts = nombre.split(' ');
    return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : nombre.substring(0, 2).toUpperCase();
};

const editarMeta = (meta) => {
    form.value = {
        id: meta.id,
        user_id: meta.user_id,
        tipo: meta.tipo,
        meta_diaria: meta.meta_diaria,
        fecha_inicio: meta.fecha_inicio || '',
        fecha_fin: meta.fecha_fin || '',
    };
    showModalNueva.value = true;
};

const guardarMeta = () => {
    procesando.value = true;
    router.post('/crm/metas', form.value, {
        onSuccess: () => {
            showModalNueva.value = false;
            form.value = initForm();
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};

const eliminarMeta = async (meta) => {
    const result = await Swal.fire({
        title: 'Eliminar meta',
        text: `¿Eliminar la meta de ${meta.user?.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(`/crm/metas/${meta.id}`);
    }
};

const importarCSV = () => {
    if (!archivoCSV.value) return;
    
    procesando.value = true;
    const formData = new FormData();
    formData.append('archivo', archivoCSV.value);
    
    router.post('/crm/metas/importar', formData, {
        forceFormData: true,
        onSuccess: () => {
            showModalImport.value = false;
            archivoCSV.value = null;
            procesando.value = false;
        },
        onError: () => {
            procesando.value = false;
        },
    });
};
</script>
