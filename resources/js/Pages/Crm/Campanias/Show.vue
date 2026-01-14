<template>
    <Head :title="`Campaña: ${campania.nombre}`" />

    <div class="container mx-auto px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <Link href="/crm/campanias" class="text-purple-600 hover:text-purple-800 text-sm flex items-center gap-1 mb-2">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                    Volver a Campañas
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">{{ campania.nombre }}</h1>
                <p v-if="campania.objetivo" class="text-gray-500 mt-1">{{ campania.objetivo }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a :href="`/crm/campanias/${campania.id}/exportar`" 
                   class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'download']" />
                    Exportar para IA
                </a>
                <button @click="showModalImport = true" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'upload']" />
                    Importar Scripts
                </button>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-if="campania.producto" class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-purple-100">
                        <FontAwesomeIcon :icon="['fas', 'box']" class="text-purple-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Producto</p>
                        <p class="font-medium">{{ campania.producto.nombre }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-amber-100">
                        <FontAwesomeIcon :icon="['fas', 'calendar']" class="text-amber-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Periodo</p>
                        <p class="font-medium">{{ campania.fecha_inicio }} al {{ campania.fecha_fin }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-blue-100">
                        <FontAwesomeIcon :icon="['fas', 'phone']" class="text-blue-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Meta Diaria</p>
                        <p class="font-medium">{{ campania.meta_actividades_dia }} actividades</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-green-100">
                        <FontAwesomeIcon :icon="['fas', 'file-alt']" class="text-green-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Scripts</p>
                        <p class="font-medium">{{ totalScripts }} scripts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts por Tipo -->
        <div class="space-y-6">
            <!-- Apertura -->
            <div v-if="scripts.apertura?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-green-50 to-white border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'door-open']" class="text-green-600" />
                        Apertura ({{ scripts.apertura.length }})
                    </h3>
                </div>
                <div class="p-5 space-y-4">
                    <div v-for="s in scripts.apertura" :key="s.id" class="border-l-4 border-green-400 pl-4">
                        <h4 class="font-semibold text-gray-800">{{ s.nombre }}</h4>
                        <p class="text-gray-600 mt-1 whitespace-pre-wrap">{{ s.contenido }}</p>
                        <p v-if="s.tips" class="text-sm text-green-600 mt-2 italic">💡 {{ s.tips }}</p>
                    </div>
                </div>
            </div>

            <!-- Presentación -->
            <div v-if="scripts.presentacion?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-blue-50 to-white border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'presentation']" class="text-blue-600" />
                        Presentación ({{ scripts.presentacion.length }})
                    </h3>
                </div>
                <div class="p-5 space-y-4">
                    <div v-for="s in scripts.presentacion" :key="s.id" class="border-l-4 border-blue-400 pl-4">
                        <h4 class="font-semibold text-gray-800">{{ s.nombre }}</h4>
                        <p class="text-gray-600 mt-1 whitespace-pre-wrap">{{ s.contenido }}</p>
                        <p v-if="s.tips" class="text-sm text-blue-600 mt-2 italic">💡 {{ s.tips }}</p>
                    </div>
                </div>
            </div>

            <!-- Objeciones -->
            <div v-if="scripts.objecion?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-amber-50 to-white border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'shield-alt']" class="text-amber-600" />
                        Manejo de Objeciones ({{ scripts.objecion.length }})
                    </h3>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="s in scripts.objecion" :key="s.id" class="border-l-4 border-amber-400 pl-4 bg-amber-50/50 p-3 rounded-r-lg">
                        <h4 class="font-semibold text-amber-800">"{{ s.nombre }}"</h4>
                        <p class="text-gray-700 mt-1 whitespace-pre-wrap">{{ s.contenido }}</p>
                        <p v-if="s.tips" class="text-sm text-amber-600 mt-2 italic">💡 {{ s.tips }}</p>
                    </div>
                </div>
            </div>

            <!-- Cierre -->
            <div v-if="scripts.cierre?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-purple-50 to-white border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'handshake']" class="text-purple-600" />
                        Cierre ({{ scripts.cierre.length }})
                    </h3>
                </div>
                <div class="p-5 space-y-4">
                    <div v-for="s in scripts.cierre" :key="s.id" class="border-l-4 border-purple-400 pl-4">
                        <h4 class="font-semibold text-gray-800">{{ s.nombre }}</h4>
                        <p class="text-gray-600 mt-1 whitespace-pre-wrap">{{ s.contenido }}</p>
                        <p v-if="s.tips" class="text-sm text-purple-600 mt-2 italic">💡 {{ s.tips }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="totalScripts === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <FontAwesomeIcon :icon="['fas', 'file-alt']" class="h-12 w-12 text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-700 mb-2">Sin scripts todavía</h3>
                <p class="text-gray-500 mb-4">Exporta el JSON, dáselo a una IA, y luego importa los scripts generados</p>
                <div class="flex items-center justify-center gap-3">
                    <a :href="`/crm/campanias/${campania.id}/exportar`" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        1. Exportar JSON
                    </a>
                    <button @click="showModalImport = true" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        2. Importar Scripts
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Importar -->
        <div v-if="showModalImport" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalImport = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Importar Scripts</h3>
                    <div class="bg-gray-50 border rounded-lg p-3 mb-4">
                        <pre class="text-xs overflow-x-auto">tipo,nombre,contenido,tips
apertura,Saludo,Buenos días...,Sonreír
objecion,Muy caro,Entiendo...,Comparar</pre>
                    </div>
                    <form @submit.prevent="importarScripts">
                        <input type="file" accept=".csv,.txt" @change="archivoCSV = $event.target.files[0]" class="w-full mb-4 px-4 py-2 border rounded-lg" />
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showModalImport = false" class="px-4 py-2 bg-gray-100 rounded-lg">Cancelar</button>
                            <button type="submit" :disabled="!archivoCSV" class="px-4 py-2 bg-blue-500 text-white rounded-lg disabled:opacity-50">Importar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    campania: Object,
    scripts: Object,
    metas: Array,
});

const showModalImport = ref(false);
const archivoCSV = ref(null);

const totalScripts = computed(() => {
    return Object.values(props.scripts || {}).reduce((sum, arr) => sum + (arr?.length || 0), 0);
});

const importarScripts = () => {
    if (!archivoCSV.value) return;
    const formData = new FormData();
    formData.append('archivo', archivoCSV.value);
    router.post(`/crm/campanias/${props.campania.id}/importar-scripts`, formData, {
        forceFormData: true,
        onSuccess: () => { showModalImport.value = false; archivoCSV.value = null; },
    });
};
</script>
