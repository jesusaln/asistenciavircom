<template>
    <Head title="Campañas de Productos" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg shadow-purple-500/30">
                        <FontAwesomeIcon :icon="['fas', 'bullhorn']" class="h-6 w-6" />
                    </div>
                    Campañas de Productos
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Configura campañas con scripts generados por IA</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showModalNueva = true" class="px-4 py-2 bg-purple-500 text-white rounded-xl hover:bg-purple-600 flex items-center gap-2 font-medium">
                    <FontAwesomeIcon :icon="['fas', 'plus']" />
                    Nueva Campaña
                </button>
                <Link href="/crm" class="px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 rounded-xl hover:bg-gray-200">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="mr-2" />
                    Volver
                </Link>
            </div>
        </div>

        <!-- Lista de Campañas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="c in campanias" :key="c.id" 
                 class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-100" 
                     :class="c.vigente ? 'bg-gradient-to-r from-purple-50 to-white' : 'bg-white dark:bg-slate-900'">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white truncate">{{ c.nombre }}</h3>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold"
                              :class="c.vigente ? 'bg-green-100 text-green-700' : c.activa ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500 dark:text-gray-400'">
                            {{ c.vigente ? 'Activa' : c.activa ? 'Próxima' : 'Inactiva' }}
                        </span>
                    </div>
                    <p v-if="c.producto" class="text-sm text-purple-600 mt-1 flex items-center gap-1">
                        <FontAwesomeIcon :icon="['fas', 'box']" class="w-3 h-3" />
                        {{ c.producto }}
                    </p>
                </div>

                <!-- Body -->
                <div class="px-5 py-4 space-y-3">
                    <p v-if="c.objetivo" class="text-sm text-gray-600 dark:text-gray-300">{{ c.objetivo }}</p>
                    
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1">
                            <FontAwesomeIcon :icon="['fas', 'calendar']" class="w-4 h-4" />
                            {{ c.fecha_inicio }} - {{ c.fecha_fin }}
                        </span>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg">
                                <FontAwesomeIcon :icon="['fas', 'phone']" class="mr-1" />
                                {{ c.meta_actividades_dia }}/día
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-lg">
                                <FontAwesomeIcon :icon="['fas', 'file-alt']" class="mr-1" />
                                {{ c.scripts_count }} scripts
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-5 py-3 bg-white dark:bg-slate-900 border-t border-gray-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1">
                        <a :href="`/crm/campanias/${c.id}/exportar`" 
                           class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100" 
                           title="Exportar JSON para IA">
                            <FontAwesomeIcon :icon="['fas', 'download']" />
                        </a>
                        <button @click="abrirImportarScripts(c)" 
                                class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100" 
                                title="Importar Scripts">
                            <FontAwesomeIcon :icon="['fas', 'upload']" />
                        </button>
                        <button @click="toggleCampania(c)" 
                                class="p-2 rounded-lg" 
                                :class="c.activa ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-gray-100 text-gray-500 dark:text-gray-400 hover:bg-gray-200'"
                                :title="c.activa ? 'Desactivar' : 'Activar'">
                            <FontAwesomeIcon :icon="['fas', c.activa ? 'pause' : 'play']" />
                        </button>
                    </div>
                    <Link :href="`/crm/campanias/${c.id}`" 
                          class="px-3 py-1.5 bg-purple-500 text-white text-sm rounded-lg hover:bg-purple-600">
                        Ver Scripts →
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!campanias.length" class="col-span-full text-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100">
                <FontAwesomeIcon :icon="['fas', 'bullhorn']" class="h-12 w-12 text-gray-300 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 font-medium mb-2">Sin campañas</p>
                <p class="text-sm text-gray-400 mb-4">Crea tu primera campaña para generar scripts con IA</p>
                <button @click="showModalNueva = true" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                    Nueva Campaña
                </button>
            </div>
        </div>

        <!-- Modal Nueva Campaña -->
        <div v-if="showModalNueva" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalNueva = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-lg w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Nueva Campaña</h3>
                        <button @click="showModalNueva = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-300">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="guardarCampania">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Campaña *</label>
                                <input v-model="form.nombre" type="text" required placeholder="Ej: Promo Minisplits Diciembre"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                                <select v-model="form.producto_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sin producto específico</option>
                                    <option v-for="p in productos" :key="p.id" :value="p.id">
                                        {{ p.codigo }} - {{ p.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo</label>
                                <input v-model="form.objetivo" type="text" placeholder="Ej: Vender 50 unidades"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio *</label>
                                    <input v-model="form.fecha_inicio" type="date" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin *</label>
                                    <input v-model="form.fecha_fin" type="date" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Meta de Actividades/Día *</label>
                                <input v-model.number="form.meta_actividades_dia" type="number" min="1" max="50" required
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cuántas llamadas/seguimientos debe hacer cada vendedor por día</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showModalNueva = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Crear Campaña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Importar Scripts -->
        <div v-if="showModalImport" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalImport = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-lg w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'upload']" class="text-blue-500" />
                            Importar Scripts
                        </h3>
                        <button @click="showModalImport = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-300">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <h4 class="font-semibold text-blue-800 mb-2">Flujo con IA:</h4>
                        <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                            <li>Descarga el JSON (botón verde <FontAwesomeIcon :icon="['fas', 'download']" />)</li>
                            <li>Dáselo a ChatGPT/Claude</li>
                            <li>Pídele que genere scripts en formato CSV</li>
                            <li>Sube el CSV aquí</li>
                        </ol>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-4 mb-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Formato CSV esperado:</h4>
                        <pre class="text-xs bg-white dark:bg-slate-900 p-2 rounded border overflow-x-auto">tipo,nombre,contenido,tips
apertura,Saludo inicial,Buenos días...,Sonreír
presentacion,Características,Este producto...,Destacar ahorro
objecion,Muy caro,Entiendo...,Comparar</pre>
                    </div>

                    <form @submit.prevent="importarScripts">
                        <div class="mb-4">
                            <input type="file" accept=".csv,.txt" @change="archivoCSV = $event.target.files[0]"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showModalImport = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="!archivoCSV || procesando" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Importar
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    campanias: Array,
    productos: Array,
});

const showModalNueva = ref(false);
const showModalImport = ref(false);
const campaniaSeleccionada = ref(null);
const archivoCSV = ref(null);
const procesando = ref(false);

const form = ref({
    nombre: '',
    producto_id: '',
    objetivo: '',
    fecha_inicio: '',
    fecha_fin: '',
    meta_actividades_dia: 5,
});

const guardarCampania = () => {
    procesando.value = true;
    router.post('/crm/campanias', form.value, {
        onSuccess: () => {
            showModalNueva.value = false;
            form.value = { nombre: '', producto_id: '', objetivo: '', fecha_inicio: '', fecha_fin: '', meta_actividades_dia: 5 };
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};

const abrirImportarScripts = (campania) => {
    campaniaSeleccionada.value = campania;
    archivoCSV.value = null;
    showModalImport.value = true;
};

const importarScripts = () => {
    if (!archivoCSV.value || !campaniaSeleccionada.value) return;
    
    procesando.value = true;
    const formData = new FormData();
    formData.append('archivo', archivoCSV.value);
    
    router.post(`/crm/campanias/${campaniaSeleccionada.value.id}/importar-scripts`, formData, {
        forceFormData: true,
        onSuccess: () => {
            showModalImport.value = false;
            archivoCSV.value = null;
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};

const toggleCampania = (campania) => {
    router.patch(`/crm/campanias/${campania.id}/toggle`);
};
</script>
